/**
 * DayBoard – Správa domácnosti: okno pro přidání / úpravu člena, hledání
 * lokality s náhledem na mapě a výběr rozhlasových stanic.
 */
(function () {
    'use strict';

    const { api, url, announce, loadLeaflet, addMapTiles } = window.DayBoard;

    // =====================================================================================
    // Členové: jedno okno slouží pro přidání i úpravu
    // =====================================================================================

    const memberDialog = document.getElementById('member-dialog');
    const memberForm = document.getElementById('member-form');

    if (memberDialog && memberForm && typeof memberDialog.showModal === 'function') {
        const field = (name) => memberForm.elements.namedItem(name);
        const password = document.getElementById('member-password');

        const openMemberDialog = (member) => {
            const editing = Boolean(member);
            memberForm.reset();
            memberForm.action = memberForm.dataset.baseAction + (editing ? `/${member.id}` : '');
            document.getElementById('member-dialog-title').textContent = editing ? 'Upravit člena domácnosti' : 'Přidat člena domácnosti';
            document.getElementById('member-submit').textContent = editing ? 'Uložit' : 'Přidat';
            document.getElementById('member-password-label').textContent = editing ? 'Nové heslo (nepovinné)' : 'Dočasné heslo';
            document.getElementById('member-password-hint').textContent = editing
                ? 'Vyplňte jen tehdy, když chcete členovi heslo obnovit. Alespoň 8 znaků.'
                : 'Alespoň 8 znaků.';
            password.required = !editing;

            if (editing) {
                field('display_name').value = member.name;
                field('login').value = member.login;
                memberForm.querySelector(`input[name="role"][value="${member.role}"]`).checked = true;
            }
            memberDialog.showModal();
            field('display_name').focus();
        };

        document.querySelectorAll('[data-member-add]').forEach((button) => {
            button.hidden = false;
            button.addEventListener('click', () => openMemberDialog(null));
        });
        document.querySelectorAll('[data-member-edit]').forEach((button) => {
            button.hidden = false;
            button.addEventListener('click', () => openMemberDialog(button.dataset));
        });
    }

    // =====================================================================================
    // Lokalita domácnosti: hledání obce a náhled na mapě
    // =====================================================================================

    const locationInput = document.getElementById('location_name');
    const locationButton = document.getElementById('location-search');
    const locationResults = document.getElementById('location-results');
    const locationStatus = document.getElementById('location-status');
    const preview = document.getElementById('location-preview');
    let previewMap = null;
    let previewMarker = null;

    async function showPreview(lat, lon) {
        if (!preview) {
            return;
        }
        try {
            const L = await loadLeaflet();
            if (!previewMap) {
                preview.replaceChildren();
                previewMap = L.map(preview, {
                    zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false,
                    boxZoom: false, keyboard: false, touchZoom: false,
                });
                addMapTiles(L, previewMap);
                previewMarker = L.circleMarker([lat, lon], { radius: 9, color: '#c2410c', weight: 3, fillOpacity: 0.35 }).addTo(previewMap);
            }
            previewMarker.setLatLng([lat, lon]);
            previewMap.setView([lat, lon], 11);
        } catch (error) {
            preview.querySelector('p').textContent = error.message;
        }
    }

    if (preview && preview.dataset.lat !== '') {
        // Mapa se načte až ve chvíli, kdy se k ní správce dostane.
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                showPreview(Number(preview.dataset.lat), Number(preview.dataset.lon));
            }
        }, { rootMargin: '150px' });
        observer.observe(preview);
    }

    if (locationInput && locationButton && locationResults) {
        const chooseLocation = (place) => {
            locationInput.value = place.name;
            document.getElementById('location_lat').value = place.lat;
            document.getElementById('location_lon').value = place.lon;
            // Server souřadnicím uvěří jen tehdy, když patří k odeslanému názvu.
            document.getElementById('location_resolved').value = place.name;
            locationResults.hidden = true;
            locationResults.replaceChildren();
            locationStatus.textContent = `Vybráno: ${place.label}. Změna se projeví po uložení.`;
            showPreview(place.lat, place.lon);
            locationInput.focus();
        };

        const searchLocation = async () => {
            const query = locationInput.value.trim();
            if (query.length < 2) {
                locationStatus.textContent = 'Zadejte alespoň dva znaky názvu obce.';
                return;
            }
            locationStatus.textContent = 'Hledám…';
            locationResults.hidden = true;
            try {
                const data = await api(url(`/api/geocode?q=${encodeURIComponent(query)}`));
                if (data.results.length === 0) {
                    locationStatus.textContent = 'Nic nenalezeno. Zkuste jiný název obce.';
                    return;
                }
                locationResults.replaceChildren(...data.results.map((place) => {
                    const item = document.createElement('li');
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'picker__item';
                    button.textContent = place.label;
                    button.addEventListener('click', () => chooseLocation(place));
                    item.append(button);
                    return item;
                }));
                locationResults.hidden = false;
                locationStatus.textContent = `Nalezeno míst: ${data.results.length}. Vyberte jedno ze seznamu.`;
                locationResults.querySelector('button').focus();
            } catch (error) {
                locationStatus.textContent = error.message;
            }
        };

        locationButton.hidden = false;
        locationButton.addEventListener('click', searchLocation);
        locationInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchLocation();
            }
        });
    }

    // =====================================================================================
    // Rozhlasové stanice: hledání v katalogu Radio Browser a štítky vybraných stanic
    // =====================================================================================

    const stationList = document.getElementById('station-list');
    const stationAdd = document.getElementById('station-add');
    const stationDialog = document.getElementById('station-dialog');
    const stationResults = document.getElementById('station-results');
    const stationStatus = document.getElementById('station-status');

    if (stationList && stationAdd && stationDialog && typeof stationDialog.showModal === 'function') {
        const maxStations = Number(stationAdd.dataset.max);
        const chosenUrls = () => [...stationList.querySelectorAll('input[name$="[stream_url]"]')].map((input) => input.value);

        /** Po každé změně přečíslovat pole formuláře: stations[0][…], stations[1][…]… */
        const reindex = () => {
            [...stationList.children].forEach((chip, index) => {
                chip.querySelectorAll('input[type="hidden"]').forEach((input) => {
                    input.name = input.name.replace(/^stations\[\d*\]/, `stations[${index}]`);
                });
            });
            stationAdd.hidden = stationList.children.length >= maxStations;
        };

        const createChip = (station) => {
            const chip = document.createElement('li');
            chip.className = 'chip';
            for (const key of ['name', 'stream_url', 'station_uuid']) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `stations[][${key}]`;
                input.value = station[key] ?? '';
                chip.append(input);
            }
            const label = document.createElement('span');
            label.textContent = station.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'chip__remove';
            remove.dataset.chipRemove = '';
            remove.setAttribute('aria-label', `Odebrat stanici ${station.name}`);
            remove.textContent = '×';
            chip.append(label, remove);
            return chip;
        };

        const renderResults = (stations) => {
            const chosen = chosenUrls();
            stationResults.replaceChildren(...stations.map((station) => {
                const item = document.createElement('li');
                item.className = 'picker__item';
                const text = document.createElement('span');
                const name = document.createElement('strong');
                name.textContent = station.name;
                const info = document.createElement('span');
                info.className = 'picker__meta';
                info.textContent = station.info;
                text.append(name, info);

                const add = document.createElement('button');
                add.type = 'button';
                add.className = 'btn btn--small';
                const already = chosen.includes(station.stream_url);
                add.textContent = already ? 'Přidáno' : 'Přidat';
                add.disabled = already;
                add.setAttribute('aria-label', `Přidat stanici ${station.name}`);
                add.addEventListener('click', () => {
                    if (stationList.children.length >= maxStations) {
                        stationStatus.textContent = `Stanic může být nejvýše ${maxStations}. Některou nejdřív odeberte.`;
                        return;
                    }
                    stationList.append(createChip(station));
                    reindex();
                    add.textContent = 'Přidáno';
                    add.disabled = true;
                    stationStatus.textContent = `Stanice ${station.name} přidána. Změna se projeví po uložení.`;
                });
                item.append(text, add);
                return item;
            }));
        };

        document.getElementById('station-search-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const query = document.getElementById('station-query').value.trim();
            if (query.length < 2) {
                return;
            }
            stationStatus.textContent = 'Hledám…';
            stationResults.replaceChildren();
            try {
                const data = await api(url(`/api/radio/search?q=${encodeURIComponent(query)}`));
                renderResults(data.results);
                stationStatus.textContent = data.results.length === 0
                    ? 'Žádná stanice nenalezena. Zkuste jiný název.'
                    : `Nalezeno stanic: ${data.results.length}.`;
            } catch (error) {
                stationStatus.textContent = error.message;
            }
        });

        stationList.querySelectorAll('[data-chip-remove]').forEach((button) => {
            button.hidden = false;
        });
        stationList.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-chip-remove]');
            if (remove) {
                remove.closest('.chip').remove();
                reindex();
                stationAdd.focus();
                announce('Stanice odebrána. Změna se projeví po uložení.');
            }
        });

        stationAdd.addEventListener('click', () => {
            stationDialog.showModal();
            document.getElementById('station-query').focus();
        });
        reindex();
    }
})();
