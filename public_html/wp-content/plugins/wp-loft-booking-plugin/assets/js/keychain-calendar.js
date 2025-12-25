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
        expanded: new Set(),
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
        const today = new Date();
        const todayInRange = today >= start && today <= end;

        const grid = document.createElement('div');
        grid.className = 'loft-keychain-calendar__grid';

        const header = document.createElement('div');
        header.className = 'loft-keychain-calendar__grid-header';

        const resourceHead = document.createElement('div');
        resourceHead.className = 'loft-keychain-calendar__resource-row';
        resourceHead.innerHTML = `
            <div class="loft-keychain-calendar__resource-label">${escapeHtml('Unit')}</div>
            <span class="loft-keychain-calendar__resource-meta">${settings.todayLabel || ''}</span>
        `;

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

        if (todayInRange) {
            const line = document.createElement('div');
            line.className = 'loft-keychain-calendar__today-line';
            const position = positionEvent(
                { start: today.toISOString(), end: today.toISOString() },
                start,
                end,
                state.view
            );
            line.style.left = `${position.left}%`;
            timelineHead.appendChild(line);
        }

        header.appendChild(resourceHead);
        header.appendChild(timelineHead);

        const body = document.createElement('div');
        body.className = 'loft-keychain-calendar__grid-body';

        const resourcesCol = document.createElement('div');
        resourcesCol.className = 'loft-keychain-calendar__resources';

        const timelineCol = document.createElement('div');
        timelineCol.className = 'loft-keychain-calendar__timelines';

        if (todayInRange) {
            const line = document.createElement('div');
            line.className = 'loft-keychain-calendar__today-line';
            const position = positionEvent(
                { start: today.toISOString(), end: today.toISOString() },
                start,
                end,
                state.view
            );
            line.style.left = `${position.left}%`;
            timelineCol.appendChild(line);
        }

        if (!state.resources.length) {
            const empty = document.createElement('div');
            empty.className = 'loft-keychain-calendar__empty';
            empty.textContent = settings.labels?.noResults || 'No keychains found.';
            resourcesCol.appendChild(empty);
        }

        const fragmentRes = document.createDocumentFragment();
        const fragmentTime = document.createDocumentFragment();

        state.resources.forEach((resource, index) => {
            const resRow = document.createElement('div');
            resRow.className = 'loft-keychain-calendar__resource-row';
            if (index % 2 === 1) {
                resRow.classList.add('is-striped');
            }
            resRow.innerHTML = `
                <strong>${escapeHtml(resource.title)}</strong>
                <span class="loft-keychain-calendar__resource-meta">${resource.keys_total || 0} keys • ${resource.active_keys || 0} active in range</span>
            `;
            fragmentRes.appendChild(resRow);

            const row = document.createElement('div');
            row.className = 'loft-keychain-calendar__timeline-row';
            if (index % 2 === 1) {
                row.classList.add('is-striped');
            }

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
            const lanes = layoutLanes(events);
            const visibleLaneCount = state.expanded.has(resource.id) ? lanes.length : Math.min(4, lanes.length);
            const rowHeight = Math.max(64, visibleLaneCount * 40);
            resRow.style.minHeight = `${rowHeight}px`;
            row.style.minHeight = `${rowHeight}px`;

            for (let l = 0; l < visibleLaneCount; l++) {
                const laneEl = document.createElement('div');
                laneEl.className = 'loft-keychain-calendar__lane';

                lanes[l].forEach((evt) => {
                    const bar = document.createElement('div');
                    bar.className = `loft-keychain-calendar__event loft-keychain-calendar__event--${evt.status}`;
                    if (evt.admin) {
                        bar.classList.add('loft-keychain-calendar__event--admin');
                    }

                    const label = eventLabel(evt);
                    bar.textContent = label.truncated;
                    bar.title = label.full;

                    const positions = positionEvent(evt, start, end, state.view);
                    bar.style.left = `${positions.left}%`;
                    bar.style.width = `${positions.width}%`;

                    bar.addEventListener('mouseenter', (e) => showTooltip(e, evt, resource));
                    bar.addEventListener('mouseleave', hideTooltip);
                    bar.addEventListener('click', () => openModal(evt, resource));

                    laneEl.appendChild(bar);
                });

                row.appendChild(laneEl);
            }

            if (lanes.length > visibleLaneCount) {
                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'loft-keychain-calendar__more';
                toggle.textContent = state.expanded.has(resource.id)
                    ? 'Show less'
                    : `+${lanes.length - visibleLaneCount} more`;
                toggle.addEventListener('click', () => {
                    if (state.expanded.has(resource.id)) {
                        state.expanded.delete(resource.id);
                    } else {
                        state.expanded.add(resource.id);
                    }
                    buildTimeline();
                });
                row.appendChild(toggle);
            }

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
                ? `${state.events.length} keychains across ${total} units from ${formatDate(start)} to ${formatDate(
                      new Date(end.getTime() - 1)
                  )}`
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
        if (effectiveEnd < effectiveStart) {
            return { left: 0, width: 0 };
        }
        const left = ((effectiveStart - start.getTime()) / rangeMs) * 100;
        const width = Math.max(2, ((effectiveEnd - effectiveStart) / rangeMs) * 100);

        return { left, width };
    }

    function layoutLanes(events) {
        const sorted = [...events].sort((a, b) => new Date(a.start) - new Date(b.start));
        const lanes = [];

        sorted.forEach((event) => {
            const startTime = new Date(event.start).getTime();
            let placed = false;

            for (let i = 0; i < lanes.length; i++) {
                const lane = lanes[i];
                const last = lane[lane.length - 1];
                if (new Date(last.end).getTime() <= startTime) {
                    lane.push(event);
                    placed = true;
                    break;
                }
            }

            if (!placed) {
                lanes.push([event]);
            }
        });

        return lanes;
    }

    function eventLabel(evt) {
        let label = '';

        if (evt.keychain) {
            label = `Key: ${evt.keychain}`;
        } else if (evt.tenant) {
            label = `Tenant: ${evt.tenant}`;
        } else if (typeof evt.virtual === 'number') {
            label = `VK: ${evt.virtual}`;
        } else {
            label = 'Key';
        }

        const truncated = label.length > 28 ? `${label.slice(0, 27)}…` : label;
        return { full: label, truncated };
    }

    function escapeHtml(value) {
        const span = document.createElement('span');
        span.textContent = value || '';
        return span.innerHTML;
    }

    function capitalize(value) {
        if (!value) return '';
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function showTooltip(event, data, resource) {
        hideTooltip();
        const tooltip = document.createElement('div');
        tooltip.className = 'loft-keychain-calendar__tooltip';
        tooltip.innerHTML = `
            <strong>${escapeHtml(data.keychain || 'Keychain')}</strong><br />
            <div>${escapeHtml(data.unit || resource.title)}</div>
            <div>${escapeHtml(settings.labels?.tenant || 'Tenant')}: ${escapeHtml(data.tenant || '—')}</div>
            <div>${escapeHtml(settings.labels?.virtualKeys || 'Virtual keys')}: ${data.virtual}</div>
            <div>${escapeHtml('Valid from')}: ${formatDate(new Date(data.start))}</div>
            <div>${escapeHtml('Valid until')}: ${formatDate(new Date(data.end))}</div>
            <div>Status: ${escapeHtml(capitalize(data.status || ''))}</div>
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
            <h2>${escapeHtml(evt.keychain || 'Keychain')}</h2>
            <p class="loft-keychain-calendar__resource-meta">${escapeHtml(evt.unit || resource.title)}</p>
            <p>${escapeHtml(settings.labels?.tenant || 'Tenant')}: ${escapeHtml(evt.tenant || '—')}</p>
            <p>${escapeHtml(settings.labels?.virtualKeys || 'Virtual keys')}: ${evt.virtual}</p>
            <p>Status: ${escapeHtml(capitalize(evt.status))}</p>
            <p>${formatDate(new Date(evt.start))} → ${formatDate(new Date(evt.end))}</p>
            <footer>
                <button class="button button-secondary" data-close>Close</button>
                <a class="button button-primary" href="${settings.editBase}${evt.keychain_id}">Open keychain</a>
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

        const unknownLabel = 'Unassigned / Unknown';
        if (!settings.units.includes(unknownLabel)) {
            const option = document.createElement('option');
            option.value = unknownLabel;
            option.textContent = unknownLabel;
            unitSelect?.appendChild(option);
        }
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
            state.expanded = new Set();
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
