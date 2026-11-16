/**
 * DayBoard – nástěnka: načítání widgetů, nákupní seznam, rádio a mapa.
 * (Režim úprav rozložení je v souboru dashboard-edit.js.)
 */
(function () {
    'use strict';

    const { api, url, toast, announce, escapeHtml } = window.DayBoard;
    const grid = document.getElementById('grid');
    if (!grid) {
        return;
    }

    const isEditing = () => document.body.classList.contains('is-editing-dashboard');
    const widget = (key) => grid.querySelector(`.widget[data-widget="${key}"]`);
    const bodyOf = (element) => element.querySelector('[data-widget-body]');

    // =====================================================================================
    // Načítání obsahu widgetů AJAXem a průběžná obnova
    // =====================================================================================

    const lastLoaded = new Map();

    /** Chybový stav sestavený přes DOM – text chyby se nikdy nevkládá jako HTML. */
    function showError(element, message) {
        const box = document.createElement('div');
        box.className = 'widget-state widget-state--error';
        const text = document.createElement('p');
        text.textContent = message;
        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'btn btn--small';
        retry.dataset.widgetRetry = '';
        retry.textContent = 'Zkusit znovu';
        box.append(text, retry);
        bodyOf(element).replaceChildren(box);
    }

    /**
     * Načte obsah widgetu ze serveru. Při tiché obnově (silent) se po chybě
     * ponechá dosavadní obsah – starší data jsou lepší než chybová hláška.
     */
    async function loadWidget(element, { silent = false } = {}) {
        const key = element.dataset.widget;
        if (element.dataset.loading === '1') {
            return;
        }
        element.dataset.loading = '1';
        try {
            const data = await api(element.dataset.src || url(`/api/widgets/${key}`));
            setBody(element, data.html);
            lastLoaded.set(key, Date.now());
        } catch (error) {
            if (!silent || !lastLoaded.has(key)) {
                showError(element, error.message);
            }
        } finally {
            delete element.dataset.loading;
        }
    }

    /** Vymění obsah widgetu (HTML vykreslil a ošetřil server) a zachová pozici posuvníku. */
    function setBody(element, html) {
        const body = bodyOf(element);
        const scroll = body.scrollTop;

        // U nákupního seznamu nesmí obnova smazat rozepsanou položku ani fokus.
        const input = body.querySelector('#shopping-new');
        const draft = input ? { value: input.value, focused: document.activeElement === input } : null;

        body.innerHTML = html;
        body.scrollTop = scroll;

        if (draft) {
            const fresh = body.querySelector('#shopping-new');
            if (fresh) {
                fresh.value = draft.value;
                if (draft.focused) {
                    fresh.focus();
                }
            }
        }
    }

    function refreshDue() {
        if (document.hidden || isEditing()) {
            return;
        }
        grid.querySelectorAll('.widget').forEach((element) => {
            const seconds = Number(element.dataset.refresh);
            const key = element.dataset.widget;
            if (seconds > 0 && key !== 'map' && Date.now() - (lastLoaded.get(key) ?? 0) >= seconds * 1000) {
                loadWidget(element, { silent: true });
            }
        });
    }

    // První načtení: widgety s atributem data-src mají zatím jen kostru.
    grid.querySelectorAll('.widget').forEach((element) => {
        if (element.dataset.src) {
            loadWidget(element);
        } else {
            lastLoaded.set(element.dataset.widget, Date.now());
        }
    });

    // Obnova podle intervalu widgetu; na skryté záložce se nic nenačítá.
    window.setInterval(refreshDue, 10000);
    document.addEventListener('visibilitychange', refreshDue);

    grid.addEventListener('click', (event) => {
        const retry = event.target.closest('[data-widget-retry]');
        if (retry) {
            const element = retry.closest('.widget');
            bodyOf(element).innerHTML = '<div class="skeleton" aria-hidden="true"><span></span><span></span><span></span></div>';
            loadWidget(element);
        }
    });

    // =====================================================================================
    // Nákupní seznam
    // =====================================================================================

    async function shoppingAction(path, body, successMessage) {
        const element = widget('shopping');
        try {
            const data = await api(url(path), { method: 'POST', body });
            setBody(element, data.html);
            lastLoaded.set('shopping', Date.now());
            if (successMessage) {
                announce(successMessage);
            }
        } catch (error) {
            toast(error.message, 'error');
            loadWidget(element, { silent: true });
        }
    }

    grid.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-shopping-add]');
        if (!form) {
            return;
        }
        event.preventDefault();
        const input = form.querySelector('input[name="name"]');
        const name = input.value.trim();
        if (name === '') {
            input.focus();
            return;
        }
        input.value = '';
        shoppingAction('/api/shopping', { name }, `Položka ${name} přidána na seznam.`).then(() => {
            widget('shopping')?.querySelector('#shopping-new')?.focus();
        });
    });

    grid.addEventListener('change', (event) => {
        const toggle = event.target.closest('[data-shopping-toggle]');
        if (toggle) {
            shoppingAction(`/api/shopping/${toggle.dataset.shoppingToggle}/toggle`);
        }
    });

    grid.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-shopping-delete]');
        if (remove) {
            shoppingAction(`/api/shopping/${remove.dataset.shoppingDelete}/delete`, undefined, 'Položka smazána.');
            return;
        }
        if (event.target.closest('[data-shopping-clear]')) {
            shoppingAction('/api/shopping/clear-done', undefined, 'Koupené položky smazány.');
        }
    });

    // =====================================================================================
    // Rádio – přehrávač se vytvoří až při prvním kliknutí
    // =====================================================================================

    let audio = null;
    const VOLUME_KEY = 'dayboard.radio.volume';

    function radioRoot() {
        return grid.querySelector('[data-radio]');
    }

    function setRadioStatus(text, playing) {
        const root = radioRoot();
        if (!root) {
            return;
        }
        root.querySelector('[data-radio-status]').textContent = text;
        root.classList.toggle('is-playing', playing);
        root.querySelector('[data-radio-toggle]').setAttribute('aria-label', playing ? 'Zastavit přehrávání' : 'Přehrát');
    }

    function storedVolume() {
        const value = Number(window.localStorage.getItem(VOLUME_KEY));
        return Number.isFinite(value) && window.localStorage.getItem(VOLUME_KEY) !== null ? Math.min(100, Math.max(0, value)) : 70;
    }

    function playStation(button) {
        const root = radioRoot();
        if (!audio) {
            audio = new Audio();
            audio.preload = 'none';
            audio.addEventListener('playing', () => setRadioStatus('Přehrává se', true));
            audio.addEventListener('waiting', () => setRadioStatus('Načítá se…', true));
            audio.addEventListener('error', () => {
                if (audio.getAttribute('src')) {
                    setRadioStatus('Stanici se nepodařilo přehrát.', false);
                }
            });
        }
        root.querySelectorAll('[data-radio-station]').forEach((item) => {
            item.setAttribute('aria-pressed', String(item === button));
        });
        root.querySelector('[data-radio-name]').textContent = button.dataset.name;

        audio.src = button.dataset.url;
        audio.volume = storedVolume() / 100;
        setRadioStatus('Načítá se…', true);
        audio.play().catch(() => setRadioStatus('Stanici se nepodařilo přehrát.', false));

        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({ title: button.dataset.name, artist: 'DayBoard – rádio' });
        }
    }

    function stopRadio() {
        if (audio) {
            // U živého vysílání nemá pauza smysl – stream se zahodí a příště načte znovu.
            audio.pause();
            audio.removeAttribute('src');
            audio.load();
        }
        setRadioStatus('Zastaveno', false);
    }

    grid.addEventListener('click', (event) => {
        const root = radioRoot();
        if (!root) {
            return;
        }
        const station = event.target.closest('[data-radio-station]');
        if (station) {
            playStation(station);
            return;
        }
        if (event.target.closest('[data-radio-toggle]')) {
            if (root.classList.contains('is-playing')) {
                stopRadio();
            } else {
                playStation(root.querySelector('[data-radio-station][aria-pressed="true"]') || root.querySelector('[data-radio-station]'));
            }
        }
    });

    grid.addEventListener('input', (event) => {
        const slider = event.target.closest('[data-radio-volume]');
        if (slider) {
            window.localStorage.setItem(VOLUME_KEY, slider.value);
            if (audio) {
                audio.volume = Number(slider.value) / 100;
            }
        }
    });

    const volumeSlider = grid.querySelector('[data-radio-volume]');
    if (volumeSlider) {
        volumeSlider.value = String(storedVolume());
    }

    // =====================================================================================
    // Poloha členů – mapa se načte, až když je widget vidět
    // =====================================================================================

    const mapState = { map: null, markers: new Map(), L: null };

    function markerIcon(L, member) {
        return L.divIcon({
            className: 'map-marker',
            html: `<span style="background:${escapeHtml(member.color)}">${escapeHtml(member.initial)}</span>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14],
        });
    }

    function drawMembers(data) {
        const { map, markers, L } = mapState;
        const seen = new Set();
        const points = [[data.home.lat, data.home.lon]];

        data.members.forEach((member) => {
            seen.add(member.id);
            points.push([member.lat, member.lon]);
            const label = `${member.name} – ${member.place}`;
            if (markers.has(member.id)) {
                markers.get(member.id).setLatLng([member.lat, member.lon]).setTooltipContent(label);
            } else {
                const marker = L.marker([member.lat, member.lon], { icon: markerIcon(L, member), title: label, keyboard: false });
                marker.bindTooltip(label).addTo(map);
                markers.set(member.id, marker);
            }
        });
        markers.forEach((marker, id) => {
            if (!seen.has(id)) {
                marker.remove();
                markers.delete(id);
            }
        });
        return points;
    }

    function drawMemberList(root, members) {
        const list = root.querySelector('[data-members-list]');
        list.replaceChildren(...members.map((member) => {
            const item = document.createElement('li');
            item.className = 'members__item';
            const dot = document.createElement('span');
            dot.className = 'members__dot';
            dot.style.background = member.color;
            dot.setAttribute('aria-hidden', 'true');
            dot.textContent = member.initial;
            const name = document.createElement('span');
            name.className = 'members__name';
            name.textContent = member.name;
            const place = document.createElement('span');
            place.className = 'members__place';
            place.textContent = member.place;
            item.append(dot, name, place);
            return item;
        }));
    }

    async function initMap(element) {
        const root = element.querySelector('[data-members]');
        const mapElement = root.querySelector('[data-map]');
        const data = JSON.parse(root.querySelector('[data-members-data]').textContent);

        let L;
        try {
            L = await window.DayBoard.loadLeaflet();
        } catch (error) {
            mapElement.querySelector('p').textContent = error.message;
            return;
        }

        mapElement.replaceChildren();
        const map = L.map(mapElement, { zoomControl: false, scrollWheelZoom: false });
        window.DayBoard.addMapTiles(L, map);
        L.marker([data.home.lat, data.home.lon], {
            icon: L.divIcon({ className: 'map-marker map-marker--home', html: '<span>⌂</span>', iconSize: [28, 28], iconAnchor: [14, 14] }),
            title: `Domov – ${data.home.name}`,
            keyboard: false,
            zIndexOffset: -100,
        }).bindTooltip(`Domov – ${data.home.name}`).addTo(map);

        Object.assign(mapState, { map, L });
        map.fitBounds(drawMembers(data), { padding: [28, 28], maxZoom: 14 });

        // Po změně velikosti widgetu (nebo návratu z režimu úprav) mapu překreslit.
        new ResizeObserver(() => map.invalidateSize()).observe(mapElement);

        window.setInterval(async () => {
            if (document.hidden || isEditing()) {
                return;
            }
            try {
                const fresh = await api(url('/api/locations'));
                drawMembers(fresh);
                drawMemberList(root, fresh.members);
            } catch {
                // při výpadku zůstanou poslední známé polohy
            }
        }, 30000);
    }

    const mapWidget = widget('map');
    if (mapWidget) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                initMap(mapWidget);
            }
        }, { rootMargin: '200px' });
        observer.observe(mapWidget);
    }
})();
