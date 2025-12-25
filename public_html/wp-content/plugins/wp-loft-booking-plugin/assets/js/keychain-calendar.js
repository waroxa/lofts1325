(function () {
    const settings = window.loftKeychainCalendar || {};

    const state = {
        view: settings.initialView || 'week',
        focusDate: settings.initialDate ? new Date(settings.initialDate) : new Date(),
        resources: [],
        events: [],
        filters: {
            search: '',
            unit: '',
            admin: false,
            virtualKeys: false,
        },
        loading: false,
    };

    const views = {
        day: { step: 24, formatter: (d) => `${pad(d.getHours())}:00` },
        week: { step: 7, formatter: (d) => d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }) },
        month: { step: 30, formatter: (d) => d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) },
        year: { step: 12, formatter: (d) => d.toLocaleDateString(undefined, { month: 'short' }) },
    };

    const container = document.getElementById('loft-keychain-calendar');
    const summary = document.querySelector('.loft-keychain-calendar__summary');
    const unitSelect = document.getElementById('loft-keychain-unit-filter');
    const searchInput = document.getElementById('loft-keychain-search');
    const adminToggle = document.getElementById('loft-keychain-admin-filter');
    const vkToggle = document.getElementById('loft-keychain-vk-filter');

    function pad(value) {
        return value.toString().padStart(2, '0');
    }

    function clone(date) {
        return new Date(date.getTime());
    }

    function setView(view) {
        state.view = view;
        fetchData();
    }

    function shiftRange(direction) {
        const current = state.focusDate;
        const view = state.view;
        const next = clone(current);

        if (view === 'day') {
            next.setDate(current.getDate() + direction);
        } else if (view === 'week') {
            next.setDate(current.getDate() + direction * 7);
        } else if (view === 'month') {
            next.setMonth(current.getMonth() + direction);
        } else {
            next.setFullYear(current.getFullYear() + direction);
        }

        state.focusDate = next;
        fetchData();
    }

    function startOfRange(date, view) {
        const d = clone(date);

        if (view === 'week') {
            const day = d.getDay();
            d.setDate(d.getDate() - day);
        } else if (view === 'month') {
            d.setDate(1);
        } else if (view === 'year') {
            d.setMonth(0, 1);
        }

        d.setHours(0, 0, 0, 0);
        return d;
    }

    function endOfRange(date, view) {
        const d = clone(startOfRange(date, view));

        if (view === 'day') {
            d.setDate(d.getDate() + 1);
        } else if (view === 'week') {
            d.setDate(d.getDate() + 7);
        } else if (view === 'month') {
            d.setMonth(d.getMonth() + 1);
        } else {
            d.setFullYear(d.getFullYear() + 1);
        }

        return d;
    }

    function slotsForRange(start, end, view) {
        const slots = [];
        const cursor = clone(start);

        if (view === 'day') {
            for (let i = 0; i < 24; i++) {
                cursor.setHours(i, 0, 0, 0);
                slots.push(new Date(cursor));
            }
        } else if (view === 'week' || view === 'month') {
            while (cursor < end) {
                slots.push(new Date(cursor));
                cursor.setDate(cursor.getDate() + 1);
            }
        } else {
            for (let i = 0; i < 12; i++) {
                cursor.setMonth(i, 1);
                slots.push(new Date(cursor));
            }
        }

        return slots;
    }

    function buildTimeline() {
        if (!container) return;

        container.innerHTML = '';

        const start = startOfRange(state.focusDate, state.view);
        const end = endOfRange(state.focusDate, state.view);
        const slots = slotsForRange(start, end, state.view);

        const grid = document.createElement('div');
        grid.className = 'loft-keychain-calendar__grid';

        const header = document.createElement('div');
        header.className = 'loft-keychain-calendar__grid-header';

        const resourceHead = document.createElement('div');
        resourceHead.className = 'loft-keychain-calendar__resource-row';
        resourceHead.innerHTML = `<strong>${state.resources.length} keychains</strong><span class="loft-keychain-calendar__resource-meta">${settings.todayLabel || ''}</span>`;

        const timelineHead = document.createElement('div');
        timelineHead.className = 'loft-keychain-calendar__timeline-header';

        const formatter = views[state.view].formatter;
        slots.forEach((slot) => {
            const label = document.createElement('div');
            label.textContent = formatter(slot);
            if (isToday(slot)) {
                label.classList.add('is-today');
            }
            timelineHead.appendChild(label);
        });

        header.appendChild(resourceHead);
        header.appendChild(timelineHead);

        const body = document.createElement('div');
        body.className = 'loft-keychain-calendar__grid-body';

        const resourcesCol = document.createElement('div');
        resourcesCol.className = 'loft-keychain-calendar__resources';

        const timelineCol = document.createElement('div');
        timelineCol.className = 'loft-keychain-calendar__timelines';

        if (!state.resources.length) {
            const empty = document.createElement('div');
            empty.className = 'loft-keychain-calendar__empty';
            empty.textContent = settings.labels?.noResults || 'No keychains found.';
            resourcesCol.appendChild(empty);
        }

        const fragmentRes = document.createDocumentFragment();
        const fragmentTime = document.createDocumentFragment();

        state.resources.forEach((resource) => {
            const resRow = document.createElement('div');
            resRow.className = 'loft-keychain-calendar__resource-row';
            resRow.innerHTML = `
                <strong>${escapeHtml(resource.title)}</strong>
                <span class="loft-keychain-calendar__resource-meta">${escapeHtml(resource.unit || 'Unassigned')}</span>
                <span class="loft-keychain-calendar__resource-meta">${escapeHtml(resource.tenant || '')}</span>
            `;
            fragmentRes.appendChild(resRow);

            const row = document.createElement('div');
            row.className = 'loft-keychain-calendar__timeline-row';

            const gridLine = document.createElement('div');
            gridLine.className = 'loft-keychain-calendar__timeline-grid';

            slots.forEach((slot) => {
                const cell = document.createElement('div');
                if (isToday(slot)) {
                    cell.classList.add('is-today');
                }
                gridLine.appendChild(cell);
            });

            row.appendChild(gridLine);

            const events = state.events.filter((evt) => evt.resourceId === resource.id);
            events.forEach((evt) => {
                const bar = document.createElement('div');
                bar.className = `loft-keychain-calendar__event loft-keychain-calendar__event--${evt.status}`;
                if (evt.admin) {
                    bar.classList.add('loft-keychain-calendar__event--admin');
                }
                bar.textContent = `${evt.keychain || ''}`.trim() || resource.title;

                const positions = positionEvent(evt, start, end, state.view);
                bar.style.left = `${positions.left}%`;
                bar.style.width = `${positions.width}%`;

                bar.dataset.details = JSON.stringify({
                    ...evt,
                    resource,
                });

                bar.addEventListener('mouseenter', (e) => showTooltip(e, evt, resource));
                bar.addEventListener('mouseleave', hideTooltip);
                bar.addEventListener('click', () => openModal(evt, resource));

                row.appendChild(bar);
            });

            fragmentTime.appendChild(row);
        });

        resourcesCol.appendChild(fragmentRes);
        timelineCol.appendChild(fragmentTime);

        body.appendChild(resourcesCol);
        body.appendChild(timelineCol);

        grid.appendChild(header);
        grid.appendChild(body);
        container.appendChild(grid);

        const total = state.resources.length;
        if (summary) {
            summary.textContent = total
                ? `${total} keychains shown from ${formatDate(start)} to ${formatDate(new Date(end.getTime() - 1))}`
                : settings.labels?.noResults || '';
        }
    }

    function isToday(date) {
        const today = new Date();
        return (
            date.getFullYear() === today.getFullYear() &&
            date.getMonth() === today.getMonth() &&
            date.getDate() === today.getDate()
        );
    }

    function formatDate(date) {
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function positionEvent(evt, start, end, view) {
        const eventStart = new Date(evt.start);
        const eventEnd = new Date(evt.end);

        const rangeMs = end.getTime() - start.getTime();
        const effectiveStart = Math.max(eventStart.getTime(), start.getTime());
        const effectiveEnd = Math.min(eventEnd.getTime(), end.getTime());
        const left = ((effectiveStart - start.getTime()) / rangeMs) * 100;
        const width = Math.max(2, ((effectiveEnd - effectiveStart) / rangeMs) * 100);

        return { left, width };
    }

    function escapeHtml(value) {
        const span = document.createElement('span');
        span.textContent = value || '';
        return span.innerHTML;
    }

    function showTooltip(event, data, resource) {
        hideTooltip();
        const tooltip = document.createElement('div');
        tooltip.className = 'loft-keychain-calendar__tooltip';
        tooltip.innerHTML = `
            <strong>${escapeHtml(resource.title)}</strong><br />
            ${escapeHtml(resource.unit || '')}<br />
            ${escapeHtml(data.tenant || '')}<br />
            ${settings.labels?.virtualKeys || 'Virtual keys'}: ${data.virtual}
        `;

        document.body.appendChild(tooltip);
        const rect = tooltip.getBoundingClientRect();
        tooltip.style.left = `${event.clientX + 12}px`;
        tooltip.style.top = `${event.clientY - rect.height - 6}px`;
    }

    function hideTooltip() {
        const tooltip = document.querySelector('.loft-keychain-calendar__tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    function openModal(evt, resource) {
        const overlay = document.createElement('div');
        overlay.className = 'loft-keychain-calendar__modal-backdrop';

        const modal = document.createElement('div');
        modal.className = 'loft-keychain-calendar__modal';
        modal.innerHTML = `
            <h2>${escapeHtml(resource.title)}</h2>
            <p class="loft-keychain-calendar__resource-meta">${escapeHtml(resource.unit || '')}</p>
            <p>${escapeHtml(settings.labels?.tenant || 'Tenant')}: ${escapeHtml(evt.tenant || resource.tenant || '—')}</p>
            <p>${escapeHtml(settings.labels?.virtualKeys || 'Virtual keys')}: ${evt.virtual}</p>
            <p>${escapeHtml(settings.labels?.people || 'People')}: ${resource.people_count || 0}</p>
            <p>${formatDate(new Date(evt.start))} → ${formatDate(new Date(evt.end))}</p>
            <footer>
                <button class="button button-secondary" data-close>Close</button>
                <a class="button button-primary" href="${settings.editBase}${resource.id}">Open keychain</a>
            </footer>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.hasAttribute('data-close')) {
                overlay.remove();
            }
        });
    }

    function populateUnits() {
        if (!Array.isArray(settings.units)) return;
        settings.units.forEach((unit) => {
            const option = document.createElement('option');
            option.value = unit;
            option.textContent = unit;
            unitSelect?.appendChild(option);
        });
    }

    function bindControls() {
        document.querySelectorAll('.loft-keychain-calendar__views button').forEach((button) => {
            button.addEventListener('click', () => {
                setView(button.dataset.view);
            });
        });

        document.querySelectorAll('.loft-keychain-calendar__nav').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.dataset.nav;
                if (action === 'prev') shiftRange(-1);
                if (action === 'next') shiftRange(1);
                if (action === 'today') {
                    state.focusDate = new Date();
                    fetchData();
                }
            });
        });

        searchInput?.addEventListener('input', (e) => {
            state.filters.search = e.target.value || '';
            debounceFetch();
        });

        unitSelect?.addEventListener('change', (e) => {
            state.filters.unit = e.target.value || '';
            fetchData();
        });

        adminToggle?.addEventListener('change', (e) => {
            state.filters.admin = e.target.checked;
            fetchData();
        });

        vkToggle?.addEventListener('change', (e) => {
            state.filters.virtualKeys = e.target.checked;
            fetchData();
        });
    }

    let debounceTimer;
    function debounceFetch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchData, 250);
    }

    async function fetchData() {
        if (state.loading) return;
        state.loading = true;
        container && (container.innerHTML = '<div class="loft-keychain-calendar__empty">Loading…</div>');

        const start = startOfRange(state.focusDate, state.view).toISOString();
        const end = endOfRange(state.focusDate, state.view).toISOString();

        const params = new URLSearchParams({
            action: 'loft_keychain_calendar_data',
            nonce: settings.nonce,
            start,
            end,
            search: state.filters.search || '',
            unit: state.filters.unit || '',
            admin: state.filters.admin ? '1' : '0',
            virtual_keys: state.filters.virtualKeys ? '1' : '0',
            limit: '600',
        });

        try {
            const response = await fetch(`${settings.ajaxUrl}?${params.toString()}`, {
                credentials: 'same-origin',
            });
            const json = await response.json();
            if (!json.success) throw new Error(json.data?.message || 'Unable to load keychains');

            state.resources = json.data.resources || [];
            state.events = json.data.events || [];
            buildTimeline();
        } catch (error) {
            container && (container.innerHTML = `<div class="loft-keychain-calendar__empty">${error.message}</div>`);
        } finally {
            state.loading = false;
        }
    }

    populateUnits();
    bindControls();
    fetchData();
})();
