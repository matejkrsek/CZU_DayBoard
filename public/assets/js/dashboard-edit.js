/**
 * DayBoard – režim úprav nástěnky.
 *
 * Widgety leží v mřížce o pevném počtu sloupců; každý má pozici (x, y) a velikost
 * (w, h) v buňkách. Přesunout a zvětšit je lze myší, dotykem i klávesnicí.
 * Po každé změně se rozložení „setřese“: widgety se nesmí překrývat a vystoupají
 * co nejvýš, aby nevznikaly mezery. Stejná pravidla hlídá server (Layouts.php),
 * který poslané rozložení před uložením ověří.
 */
(function () {
    'use strict';

    const { api, url, toast, confirm, announce } = window.DayBoard;
    const grid = document.getElementById('grid');
    const configElement = document.getElementById('editor-config');
    if (!grid || !configElement) {
        return;
    }

    const config = JSON.parse(configElement.textContent);
    const COLS = config.cols;
    const MAX_H = config.maxH;
    const catalog = new Map(config.catalog.map((item) => [item.key, item]));

    const board = document.getElementById('board');
    const editbar = document.getElementById('editbar');
    const palette = document.getElementById('palette');
    const paletteList = document.getElementById('palette-list');
    const paletteSearch = document.getElementById('palette-search');
    const emptyNote = document.getElementById('board-empty');
    const startButton = document.getElementById('edit-start');
    const saveButton = document.getElementById('edit-save');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const placeholder = document.createElement('div');
    placeholder.className = 'grid__placeholder';
    placeholder.setAttribute('aria-hidden', 'true');

    let editing = false;
    let dirty = false;
    /** Aktuální rozložení: [{ key, x, y, w, h, el }] */
    let layout = [];
    /** Stav při vstupu do režimu úprav (pro tlačítko Zrušit). */
    let original = [];
    /** Prvky widgetů odebraných během úprav – při přidání zpět se znovu použijí. */
    const detached = new Map();
    let drag = null;

    // =====================================================================================
    // Pravidla rozložení (bez práce s DOM)
    // =====================================================================================

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const sorted = (items) => [...items].sort((a, b) => a.y - b.y || a.x - b.x);

    function collides(a, b) {
        return a.key !== b.key && a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
    }

    /** Svislé zhuštění: každý widget vystoupá co nejvýš, překryvy se odsunou dolů. */
    function compact(items) {
        const placed = [];
        for (const item of sorted(items)) {
            while (item.y > 0 && !placed.some((other) => collides(other, { ...item, y: item.y - 1 }))) {
                item.y -= 1;
            }
            let hit;
            while ((hit = placed.find((other) => collides(other, item)))) {
                item.y = hit.y + hit.h;
            }
            item.moved = false;
            placed.push(item);
        }
    }

    /**
     * Přesune widget na danou buňku a widgety, které mu stojí v cestě, odsune.
     * Při akci uživatele (byUser) se překážející widget nejdřív zkusí přehodit
     * nad přesouvaný – díky tomu si dva sousedé při tažení přirozeně vymění místo.
     */
    function moveItem(items, item, x, y, byUser) {
        if (item.x === x && item.y === y) {
            return;
        }
        const movingUp = item.y >= y;
        item.x = x;
        item.y = y;
        item.moved = true;

        let order = sorted(items);
        if (movingUp) {
            order = order.reverse();
        }
        for (const other of order.filter((candidate) => collides(candidate, item))) {
            if (other.moved) {
                continue;
            }
            if (byUser) {
                const above = { key: '__probe__', x: other.x, y: Math.max(item.y - other.h, 0), w: other.w, h: other.h };
                if (!items.some((candidate) => collides(candidate, above))) {
                    moveItem(items, other, other.x, above.y, false);
                    continue;
                }
            }
            moveItem(items, other, other.x, other.y + 1, false);
        }
    }

    /** První volné místo pro widget dané velikosti (zleva doprava, shora dolů). */
    function findSpot(items, w, h) {
        for (let y = 0; ; y += 1) {
            for (let x = 0; x <= COLS - w; x += 1) {
                const probe = { key: '__probe__', x, y, w, h };
                if (!items.some((item) => collides(item, probe))) {
                    return { x, y };
                }
            }
        }
    }

    // =====================================================================================
    // Propojení s DOM
    // =====================================================================================

    const itemOf = (element) => layout.find((item) => item.el === element);
    const title = (item) => catalog.get(item.key).title;

    function readLayout() {
        return [...grid.querySelectorAll('.widget')].map((el) => ({
            key: el.dataset.widget,
            x: Number(el.dataset.x),
            y: Number(el.dataset.y),
            w: Number(el.dataset.w),
            h: Number(el.dataset.h),
            el,
        }));
    }

    /** Zapíše pozici a velikost do CSS proměnných, podle kterých se widget umístí v mřížce. */
    function place(element, item) {
        element.style.setProperty('--c', item.x + 1);
        element.style.setProperty('--r', item.y + 1);
        element.style.setProperty('--w', item.w);
        element.style.setProperty('--h', item.h);
        element.style.setProperty('--w2', Math.min(2, item.w));
        if (element !== placeholder) {
            Object.assign(element.dataset, { x: item.x, y: item.y, w: item.w, h: item.h });
            element.querySelector('.widget__size').textContent = `${item.w} × ${item.h} buňky`;
            if (editing) {
                element.setAttribute('aria-label', describe(item));
            }
        }
    }

    function describe(item) {
        return `${title(item)}: sloupec ${item.x + 1}, řádek ${item.y + 1}, velikost ${item.w} × ${item.h}`;
    }

    /**
     * Překreslí rozložení. Widgety, které změnily místo, na něj plynule dojedou
     * (technika FLIP: změříme polohu před a po a rozdíl přehrajeme jako animaci).
     */
    function render({ skip = null, animate = true } = {}) {
        const moving = animate && !reduceMotion.matches;
        const before = moving ? new Map(layout.map((item) => [item, item.el.getBoundingClientRect()])) : null;

        for (const item of layout) {
            if (item !== skip) {
                place(item.el, item);
            }
        }

        if (before) {
            for (const item of layout) {
                if (item === skip) {
                    continue;
                }
                const from = before.get(item);
                const to = item.el.getBoundingClientRect();
                const dx = from.left - to.left;
                const dy = from.top - to.top;
                if (dx !== 0 || dy !== 0) {
                    item.el.animate(
                        [{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }],
                        { duration: 170, easing: 'ease-out' }
                    );
                }
            }
        }
        renderPalette();
    }

    /** Pořadí prvků v HTML = pořadí čtení (kvůli klávesnici a čtečkám obrazovky). */
    function syncDomOrder() {
        const focused = document.activeElement;
        for (const item of sorted(layout)) {
            grid.append(item.el);
        }
        if (focused instanceof HTMLElement && grid.contains(focused)) {
            focused.focus({ preventScroll: true });
        }
    }

    function markDirty() {
        dirty = true;
    }

    function prepareTile(element, active) {
        element.querySelector('[data-widget-remove]').hidden = !active;
        if (active) {
            element.tabIndex = 0;
            element.dataset.labelledby = element.getAttribute('aria-labelledby') || element.dataset.labelledby || '';
            element.removeAttribute('aria-labelledby');
            element.setAttribute('aria-roledescription', 'přesouvatelný widget');
        } else {
            element.removeAttribute('tabindex');
            element.removeAttribute('aria-label');
            element.removeAttribute('aria-roledescription');
            element.setAttribute('aria-labelledby', element.dataset.labelledby);
            element.classList.remove('is-active', 'is-dragging');
            element.style.transform = '';
        }
    }

    /** Prvek widgetu: dříve odebraný, nebo nový ze šablony vykreslené serverem. */
    function takeElement(key) {
        if (detached.has(key)) {
            const element = detached.get(key);
            detached.delete(key);
            return element;
        }
        const template = document.getElementById(`widget-template-${key}`);
        return template.content.firstElementChild.cloneNode(true);
    }

    // =====================================================================================
    // Nabídka dostupných widgetů
    // =====================================================================================

    function renderPalette() {
        const query = paletteSearch.value.trim().toLocaleLowerCase('cs');
        const items = config.catalog.filter((item) => query === '' || item.title.toLocaleLowerCase('cs').includes(query));

        paletteList.replaceChildren(...items.map((item) => {
            const row = document.createElement('li');
            row.className = 'palette__item';
            const name = document.createElement('span');
            name.className = 'palette__name';
            name.textContent = item.title;
            row.append(name);

            const onBoard = layout.some((entry) => entry.key === item.key);
            if (item.status === 'allowed' && !onBoard) {
                const add = document.createElement('button');
                add.type = 'button';
                add.className = 'btn btn--primary btn--small';
                add.textContent = 'Přidat';
                add.setAttribute('aria-label', `Přidat widget ${item.title}`);
                add.addEventListener('click', () => addWidget(item.key));
                row.append(add);
            } else {
                const state = document.createElement('span');
                state.className = 'palette__state';
                state.textContent = item.status === 'extension' ? 'Možné rozšíření'
                    : item.status === 'disabled' ? 'Zakázáno správcem' : 'Na nástěnce';
                row.classList.toggle('is-unavailable', item.status !== 'allowed');
                row.append(state);
            }
            return row;
        }));

        if (items.length === 0) {
            const none = document.createElement('li');
            none.className = 'palette__none';
            none.textContent = 'Žádný widget neodpovídá hledání.';
            paletteList.append(none);
        }
    }

    function addWidget(key) {
        const definition = catalog.get(key);
        const element = takeElement(key);
        const item = { key, w: definition.w, h: definition.h, el: element, ...findSpot(layout, definition.w, definition.h) };
        layout.push(item);
        grid.append(element);
        prepareTile(element, true);
        markDirty();
        render({ animate: false });
        syncDomOrder();
        element.focus();
        element.scrollIntoView({ block: 'nearest', behavior: reduceMotion.matches ? 'auto' : 'smooth' });
        announce(`Widget ${definition.title} přidán. ${describe(item)}.`);
    }

    async function removeWidget(item) {
        if (config.confirmRemove && !(await confirm(`Odebrat widget „${title(item)}“ z nástěnky?`, 'Odebrat'))) {
            item.el.focus();
            return;
        }
        const index = sorted(layout).indexOf(item);
        layout = layout.filter((entry) => entry !== item);
        item.el.remove();
        detached.set(item.key, item.el);
        compact(layout);
        markDirty();
        render();
        announce(`Widget ${title(item)} odebrán.`);

        // Fokus přesuneme na sousední widget, ať se uživatel klávesnice neztratí.
        const neighbours = sorted(layout);
        const next = neighbours[Math.min(index, neighbours.length - 1)];
        (next ? next.el : paletteSearch).focus();
    }

    // =====================================================================================
    // Tažení a změna velikosti ukazatelem (myš, dotyk, pero)
    // =====================================================================================

    /** Rozměry buněk mřížky v pixelech. */
    function metrics() {
        const rect = grid.getBoundingClientRect();
        const style = getComputedStyle(grid);
        const gap = parseFloat(style.columnGap) || 0;
        const rowHeight = parseFloat(style.gridAutoRows) || 100;
        return {
            left: rect.left,
            top: rect.top + window.scrollY,
            gap,
            rowHeight,
            colWidth: (rect.width - gap * (COLS - 1)) / COLS,
        };
    }

    grid.addEventListener('pointerdown', (event) => {
        if (!editing || event.button !== 0 || drag) {
            return;
        }
        const element = event.target.closest('.widget');
        if (!element || event.target.closest('[data-widget-remove]')) {
            return;
        }
        const resizing = Boolean(event.target.closest('[data-widget-resize]'));
        // Na dotykové obrazovce se táhne jen za záhlaví – jinak by nešlo stránkou posouvat.
        if (!resizing && event.pointerType !== 'mouse' && !event.target.closest('.widget__head')) {
            return;
        }

        event.preventDefault();
        element.focus({ preventScroll: true });
        const item = itemOf(element);
        const rect = element.getBoundingClientRect();
        drag = {
            item,
            element,
            resizing,
            started: false,
            startX: event.clientX,
            startY: event.clientY + window.scrollY,
            clientX: event.clientX,
            clientY: event.clientY,
            rect: { left: rect.left, top: rect.top + window.scrollY, width: rect.width, height: rect.height },
            requested: { x: item.x, y: item.y },
            frame: 0,
        };
        element.setPointerCapture(event.pointerId);
        element.addEventListener('pointermove', onPointerMove);
        element.addEventListener('pointerup', onPointerEnd);
        element.addEventListener('pointercancel', onPointerEnd);
    });

    function onPointerMove(event) {
        drag.clientX = event.clientX;
        drag.clientY = event.clientY;
        updateDrag();
    }

    function updateDrag() {
        const { item, element } = drag;
        const dx = drag.clientX - drag.startX;
        const dy = drag.clientY + window.scrollY - drag.startY;

        if (!drag.started) {
            if (Math.hypot(dx, dy) < 5) {
                return; // drobné cuknutí při kliknutí není tažení
            }
            drag.started = true;
            element.classList.add('is-active');
            if (!drag.resizing) {
                element.classList.add('is-dragging');
                place(placeholder, item);
                grid.append(placeholder);
            }
            drag.frame = requestAnimationFrame(autoScroll);
        }

        const m = metrics();
        if (drag.resizing) {
            const definition = catalog.get(item.key);
            const w = clamp(Math.round((drag.rect.width + dx + m.gap) / (m.colWidth + m.gap)), definition.minW, COLS - item.x);
            const h = clamp(Math.round((drag.rect.height + dy + m.gap) / (m.rowHeight + m.gap)), definition.minH, MAX_H);
            if (w !== item.w || h !== item.h) {
                item.w = w;
                item.h = h;
                compact(layout);
                markDirty();
                render();
            }
            return;
        }

        // Widget sleduje ukazatel; zástupný rámeček ukazuje, kam po puštění dosedne.
        element.style.transform = `translate(${dx}px, ${dy}px)`;
        const x = clamp(Math.round((drag.rect.left + dx - m.left) / (m.colWidth + m.gap)), 0, COLS - item.w);
        const y = Math.max(0, Math.round((drag.rect.top + dy - m.top) / (m.rowHeight + m.gap)));
        if (x !== drag.requested.x || y !== drag.requested.y) {
            drag.requested = { x, y };
            moveItem(layout, item, x, y, true);
            compact(layout);
            markDirty();
            render({ skip: item });
            place(placeholder, item);
        }
    }

    /** Při tažení k okraji okna se stránka sama posouvá. */
    function autoScroll() {
        if (!drag) {
            return;
        }
        const edge = 70;
        let delta = 0;
        if (drag.clientY < edge) {
            delta = -Math.ceil((edge - drag.clientY) / 5);
        } else if (drag.clientY > window.innerHeight - edge) {
            delta = Math.ceil((drag.clientY - (window.innerHeight - edge)) / 5);
        }
        if (delta !== 0) {
            const before = window.scrollY;
            window.scrollBy(0, delta);
            if (window.scrollY !== before) {
                updateDrag();
            }
        }
        drag.frame = requestAnimationFrame(autoScroll);
    }

    function onPointerEnd(event) {
        const { item, element, started, resizing } = drag;
        cancelAnimationFrame(drag.frame);
        element.removeEventListener('pointermove', onPointerMove);
        element.removeEventListener('pointerup', onPointerEnd);
        element.removeEventListener('pointercancel', onPointerEnd);
        if (element.hasPointerCapture(event.pointerId)) {
            element.releasePointerCapture(event.pointerId);
        }
        drag = null;

        element.classList.remove('is-active', 'is-dragging');
        if (!started) {
            return;
        }
        if (!resizing) {
            // Widget plynule dosedne z místa, kde ho uživatel pustil, do své buňky.
            const from = element.getBoundingClientRect();
            element.style.transform = '';
            placeholder.remove();
            place(element, item);
            const to = element.getBoundingClientRect();
            if (!reduceMotion.matches) {
                element.animate(
                    [{ transform: `translate(${from.left - to.left}px, ${from.top - to.top}px)` }, { transform: 'none' }],
                    { duration: 150, easing: 'ease-out' }
                );
            }
        }
        syncDomOrder();
        announce(describe(item));
    }

    // =====================================================================================
    // Ovládání klávesnicí: šipky přesouvají, Shift + šipky mění velikost, Delete odebírá
    // =====================================================================================

    const ARROWS = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };

    grid.addEventListener('keydown', (event) => {
        if (!editing || !event.target.classList.contains('widget')) {
            return;
        }
        const item = itemOf(event.target);
        const arrow = ARROWS[event.key];

        if (arrow) {
            event.preventDefault();
            const changed = event.shiftKey ? resizeBy(item, arrow[0], arrow[1]) : moveBy(item, arrow[0], arrow[1]);
            if (changed) {
                markDirty();
                render();
                syncDomOrder();
                item.el.scrollIntoView({ block: 'nearest' });
            }
            announce(describe(item));
        } else if (event.key === 'Delete' || event.key === 'Backspace') {
            event.preventDefault();
            removeWidget(item);
        }
    });

    function moveBy(item, dx, dy) {
        const before = `${item.x},${item.y}`;
        const x = clamp(item.x + dx, 0, COLS - item.w);
        let y = Math.max(0, item.y + dy);

        // Svisle se widget přesouvá přes celého souseda (ten může být vyšší než jedna buňka).
        if (dy !== 0 && y !== item.y) {
            const blockers = layout.filter((other) => collides(other, { ...item, y }));
            if (blockers.length > 0) {
                y = dy > 0
                    ? Math.max(...blockers.map((other) => other.y + other.h))
                    : Math.min(...blockers.map((other) => other.y));
            }
        }
        moveItem(layout, item, x, y, true);
        compact(layout);
        return before !== `${item.x},${item.y}`;
    }

    function resizeBy(item, dw, dh) {
        const definition = catalog.get(item.key);
        const w = clamp(item.w + dw, definition.minW, COLS - item.x);
        const h = clamp(item.h + dh, definition.minH, MAX_H);
        if (w === item.w && h === item.h) {
            return false;
        }
        item.w = w;
        item.h = h;
        compact(layout);
        return true;
    }

    // =====================================================================================
    // Vstup do režimu úprav, uložení, zrušení
    // =====================================================================================

    function warnBeforeLeave(event) {
        if (dirty) {
            event.preventDefault();
        }
    }

    function setMode(active) {
        editing = active;
        grid.classList.toggle('is-editing', active);
        board.classList.toggle('is-editing', active);
        document.body.classList.toggle('is-editing-dashboard', active);
        editbar.hidden = !active;
        palette.hidden = !active;
        startButton.hidden = active;
        emptyNote.hidden = active || layout.length > 0;
        layout.forEach((item) => prepareTile(item.el, active));
    }

    function enter() {
        layout = readLayout();
        original = layout.map((item) => ({ ...item }));
        detached.clear();
        dirty = false;
        paletteSearch.value = '';
        setMode(true);
        render({ animate: false });
        window.addEventListener('beforeunload', warnBeforeLeave);
        const first = sorted(layout)[0];
        (first ? first.el : paletteSearch).focus({ preventScroll: true });
        announce('Režim úprav nástěnky. Šipkami widget přesunete, se Shiftem změníte velikost, klávesou Delete ho odeberete.');
    }

    function leave() {
        window.removeEventListener('beforeunload', warnBeforeLeave);
        setMode(false);
        startButton.focus();
    }

    function cancel() {
        // Vrátíme přesně ten stav, který byl při vstupu do režimu úprav.
        const keep = new Set(original.map((item) => item.el));
        layout.filter((item) => !keep.has(item.el)).forEach((item) => item.el.remove());
        layout = original.map((item) => ({ ...item }));
        layout.forEach((item) => {
            if (!item.el.isConnected) {
                grid.append(item.el);
            }
        });
        dirty = false;
        render({ animate: false });
        syncDomOrder();
        leave();
    }

    function reset() {
        layout.forEach((item) => {
            item.el.remove();
            detached.set(item.key, item.el);
        });
        layout = config.defaultLayout.map((entry) => {
            const item = { ...entry, el: takeElement(entry.key) };
            grid.append(item.el);
            prepareTile(item.el, true);
            return item;
        });
        markDirty();
        render({ animate: false });
        announce('Obnoveno výchozí rozložení. Uložte ho tlačítkem Uložit rozložení.');
    }

    async function save() {
        saveButton.disabled = true;
        try {
            await api(url('/api/layout'), {
                method: 'POST',
                body: { items: layout.map(({ key, x, y, w, h }) => ({ key, x, y, w, h })) },
            });
            dirty = false;
            // Po uložení načteme stránku znovu, aby se nově přidané widgety naplnily daty.
            window.location.reload();
        } catch (error) {
            saveButton.disabled = false;
            toast(error.message, 'error');
        }
    }

    startButton.hidden = false;
    startButton.addEventListener('click', enter);
    document.getElementById('edit-cancel').addEventListener('click', cancel);
    document.getElementById('edit-reset').addEventListener('click', reset);
    saveButton.addEventListener('click', save);
    paletteSearch.addEventListener('input', renderPalette);

    grid.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-widget-remove]');
        if (remove && editing) {
            removeWidget(itemOf(remove.closest('.widget')));
        }
    });

    document.addEventListener('keydown', async (event) => {
        // Escape ukončí režim úprav (pokud zrovna není otevřené potvrzovací okno).
        // Neuložené změny se nezahodí bez zeptání.
        if (event.key !== 'Escape' || !editing || drag || document.querySelector('dialog[open]')) {
            return;
        }
        if (!dirty || (await confirm('Zahodit neuložené změny rozložení?', 'Zahodit'))) {
            cancel();
        }
    });
})();
