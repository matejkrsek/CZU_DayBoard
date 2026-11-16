/**
 * DayBoard – Osobní nastavení: řádky cílů cest a štítky oblíbených zastávek.
 * Formulář funguje i bez JavaScriptu (tři řádky cílů, zastávka se přidá po uložení);
 * skript jen přidává pohodlnější ovládání.
 */
(function () {
    'use strict';

    const { announce } = window.DayBoard;

    // --- Cíle cest: prázdné řádky schovat a nabídnout tlačítko „Přidat cíl cesty“ ---------
    const rows = [...document.querySelectorAll('[data-dest-row]')];
    const addDestination = document.getElementById('dest-add');

    if (rows.length > 0 && addDestination) {
        const inputs = (row) => [...row.querySelectorAll('input[type="text"]')];
        const isEmpty = (row) => inputs(row).every((input) => input.value.trim() === '');
        const refresh = () => {
            addDestination.hidden = rows.every((row) => !row.hidden);
        };

        rows.forEach((row, index) => {
            const remove = row.querySelector('[data-dest-remove]');
            remove.hidden = false;
            // První řádek necháváme vidět i prázdný, aby bylo jasné, kam psát.
            row.hidden = isEmpty(row) && index > 0;

            remove.addEventListener('click', () => {
                inputs(row).forEach((input) => {
                    input.value = '';
                });
                row.querySelector('input[type="hidden"]').value = '0';
                row.hidden = rows.some((other) => other !== row && !other.hidden);
                refresh();
                addDestination.hidden ? inputs(rows.find((other) => !other.hidden))[0].focus() : addDestination.focus();
                announce('Cíl cesty odebrán. Změna se projeví po uložení.');
            });
        });

        addDestination.addEventListener('click', () => {
            const next = rows.find((row) => row.hidden);
            if (next) {
                next.hidden = false;
                inputs(next)[0].focus();
            }
            refresh();
        });

        refresh();
    }

    // --- Oblíbené zastávky: štítky s křížkem --------------------------------------------
    const stopInput = document.getElementById('stop-input');
    const stopAdd = document.getElementById('stop-add');
    const stopList = document.getElementById('stop-list');

    if (stopInput && stopAdd && stopList) {
        const MAX_STOPS = 6;
        const names = () => [...stopList.querySelectorAll('input[type="hidden"]')].map((input) => input.value);

        const createChip = (name) => {
            const chip = document.createElement('li');
            chip.className = 'chip';
            const value = document.createElement('input');
            value.type = 'hidden';
            value.name = 'stops[]';
            value.value = name;
            const label = document.createElement('span');
            label.textContent = name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'chip__remove';
            remove.dataset.chipRemove = '';
            remove.setAttribute('aria-label', `Odebrat zastávku ${name}`);
            remove.textContent = '×';
            chip.append(value, label, remove);
            return chip;
        };

        const addStop = () => {
            const name = stopInput.value.trim();
            if (name === '') {
                stopInput.focus();
                return;
            }
            if (names().some((existing) => existing.toLocaleLowerCase('cs') === name.toLocaleLowerCase('cs'))) {
                announce(`Zastávka ${name} už na seznamu je.`);
            } else if (names().length >= MAX_STOPS) {
                announce(`Oblíbených zastávek může být nejvýše ${MAX_STOPS}.`);
                return;
            } else {
                stopList.append(createChip(name));
                announce(`Zastávka ${name} přidána. Změna se projeví po uložení.`);
            }
            stopInput.value = '';
            stopInput.focus();
        };

        stopAdd.hidden = false;
        stopList.querySelectorAll('[data-chip-remove]').forEach((button) => {
            button.hidden = false;
        });

        stopAdd.addEventListener('click', addStop);
        stopInput.addEventListener('keydown', (event) => {
            // Enter v poli zastávky přidá štítek místo odeslání celého formuláře.
            if (event.key === 'Enter') {
                event.preventDefault();
                addStop();
            }
        });
        stopList.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-chip-remove]');
            if (remove) {
                remove.closest('.chip').remove();
                stopInput.focus();
                announce('Zastávka odebrána. Změna se projeví po uložení.');
            }
        });
    }
})();
