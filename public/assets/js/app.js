/**
 * DayBoard – společné funkce pro všechny obrazovky aplikace.
 * Skripty se načítají s atributem defer, takže neblokují vykreslení stránky.
 * Vše je dostupné přes objekt window.DayBoard.
 */
(function () {
    'use strict';

    const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
    const basePath = meta('base-path');
    let leafletPromise = null;

    const DayBoard = {
        /** Adresa uvnitř aplikace (respektuje umístění v podsložce serveru). */
        url(path) {
            return basePath + path;
        },

        /**
         * Volání JSON API. Přidává CSRF token a sjednocuje chyby do výjimky
         * s českou zprávou, kterou lze rovnou ukázat uživateli.
         */
        async api(url, { method = 'GET', body } = {}) {
            const headers = { Accept: 'application/json' };
            if (method !== 'GET') {
                headers['X-CSRF-Token'] = meta('csrf-token');
            }
            if (body !== undefined) {
                headers['Content-Type'] = 'application/json';
            }

            let response;
            try {
                response = await fetch(url, {
                    method,
                    headers,
                    body: body !== undefined ? JSON.stringify(body) : undefined,
                    credentials: 'same-origin',
                });
            } catch {
                throw new Error('Server není dostupný. Zkontrolujte připojení.');
            }

            if (response.status === 401) {
                window.location.href = DayBoard.url('/prihlaseni');
                throw new Error('Přihlášení vypršelo.');
            }

            let data = null;
            try {
                data = await response.json();
            } catch {
                // odpověď není JSON (např. chybová stránka serveru)
            }
            if (!response.ok) {
                throw new Error(data?.error || `Požadavek se nezdařil (HTTP ${response.status}).`);
            }
            return data;
        },

        /** Potvrzovací okno. Vrací Promise s true (potvrzeno) nebo false. */
        confirm(text, okLabel = 'Potvrdit') {
            const dialog = document.getElementById('confirm-dialog');
            if (!dialog || typeof dialog.showModal !== 'function') {
                return Promise.resolve(window.confirm(text));
            }
            dialog.querySelector('[data-confirm-text]').textContent = text;
            dialog.querySelector('[data-confirm-ok]').textContent = okLabel;
            dialog.returnValue = 'cancel';
            dialog.showModal();
            return new Promise((resolve) => {
                dialog.addEventListener('close', () => resolve(dialog.returnValue === 'ok'), { once: true });
            });
        },

        /** Krátká zpráva nahoře na stránce (typ: success | error | info). */
        toast(message, type = 'info') {
            let container = document.getElementById('flashes');
            if (!container) {
                container = document.createElement('div');
                container.id = 'flashes';
                container.className = 'flashes';
                document.getElementById('obsah').prepend(container);
            }
            const flash = document.createElement('div');
            flash.className = `flash flash--${type}`;
            flash.setAttribute('role', type === 'error' ? 'alert' : 'status');
            const text = document.createElement('p');
            text.textContent = message;
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'flash__close';
            close.dataset.flashClose = '';
            close.setAttribute('aria-label', 'Zavřít zprávu');
            close.textContent = '×';
            flash.append(text, close);
            container.append(flash);
            flash.scrollIntoView({ block: 'nearest' });
            scheduleAutoHide(flash);
        },

        /** Oznámení pro čtečky obrazovky (neviditelná živá oblast). */
        announce(text) {
            const region = document.getElementById('live-region');
            if (region) {
                region.textContent = '';
                window.setTimeout(() => {
                    region.textContent = text;
                }, 30);
            }
        },

        /**
         * Knihovna Leaflet (mapy) se načítá až ve chvíli, kdy je opravdu potřeba –
         * stránku bez viditelné mapy tak nezdržuje.
         */
        loadLeaflet() {
            if (!leafletPromise) {
                leafletPromise = new Promise((resolve, reject) => {
                    const css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.href = DayBoard.url('/assets/vendor/leaflet/leaflet.css');
                    document.head.append(css);

                    const script = document.createElement('script');
                    script.src = DayBoard.url('/assets/vendor/leaflet/leaflet.js');
                    script.onload = () => resolve(window.L);
                    script.onerror = () => {
                        leafletPromise = null;
                        reject(new Error('Mapu se nepodařilo načíst.'));
                    };
                    document.head.append(script);
                });
            }
            return leafletPromise;
        },

        /** Mapové dlaždice OpenStreetMap včetně povinného uvedení zdroje. */
        addMapTiles(L, map) {
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
            }).addTo(map);
        },

        escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = String(value);
            return div.innerHTML;
        },
    };

    window.DayBoard = DayBoard;

    // --- Zprávy: zavření křížkem, úspěšné po chvíli zmizí samy ---------------------------
    function scheduleAutoHide(flash) {
        if (flash.classList.contains('flash--success') || flash.classList.contains('flash--info')) {
            window.setTimeout(() => flash.remove(), 7000);
        }
    }

    document.querySelectorAll('#flashes .flash').forEach(scheduleAutoHide);

    document.addEventListener('click', (event) => {
        const closeFlash = event.target.closest('[data-flash-close]');
        if (closeFlash) {
            closeFlash.closest('.flash').remove();
            return;
        }

        // Zavření překryvného okna tlačítkem nebo kliknutím mimo něj
        const closeDialog = event.target.closest('[data-dialog-close]');
        if (closeDialog) {
            closeDialog.closest('dialog').close();
            return;
        }
        if (event.target instanceof HTMLDialogElement && event.target.open) {
            const box = event.target.getBoundingClientRect();
            const inside = event.clientX >= box.left && event.clientX <= box.right && event.clientY >= box.top && event.clientY <= box.bottom;
            if (!inside) {
                event.target.close();
            }
        }
    });

    // --- Tlačítka s potvrzením: <button data-confirm="Opravdu…?"> --------------------------
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-confirm]');
        if (!button || button.dataset.confirmed === '1') {
            return;
        }
        event.preventDefault();
        if (await DayBoard.confirm(button.dataset.confirm, button.dataset.confirmLabel || 'Potvrdit')) {
            button.dataset.confirmed = '1';
            button.click();
        }
    });
})();
