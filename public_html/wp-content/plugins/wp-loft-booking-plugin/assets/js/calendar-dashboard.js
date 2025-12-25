(function ($) {
    'use strict';

    const settings = window.wpLoftCalendarData || {};
    const initial = settings.payload || {};
    const today = initial.today || new Date().toISOString().slice(0, 10);

    const state = {
        bookings: decorateBookings(initial.bookings || []),
        cleaning: initial.cleaning || [],
        keys: decorateKeychains(initial.keys || []),
        today,
        view: {
            bookings: initial.today ? new Date(initial.today) : new Date(),
            cleaning: initial.today ? new Date(initial.today) : new Date(),
            keys: initial.today ? new Date(initial.today) : new Date(),
        },
        statuses: settings.statuses || {},
        keyStatuses: settings.keyStatuses || {},
        filters: {
            keys: [],
        },
    };

    const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const MS_IN_DAY = 1000 * 60 * 60 * 24;

    const colorPalette = [
        '#2563eb',
        '#f59e0b',
        '#10b981',
        '#f43f5e',
        '#0ea5e9',
        '#8b5cf6',
        '#14b8a6',
        '#e11d48',
        '#6366f1',
        '#22c55e',
    ];

    function loftColor(loftName) {
        const name = (loftName || '').toLowerCase();
        if (!name) return colorPalette[0];

        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }

        return colorPalette[Math.abs(hash) % colorPalette.length];
    }

    function shadeColor(hex, percent) {
        const num = parseInt(hex.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const r = (num >> 16) + amt;
        const g = ((num >> 8) & 0x00ff) + amt;
        const b = (num & 0x0000ff) + amt;
        return (
            '#' +
            (
                0x1000000 +
                (r < 255 ? (r < 0 ? 0 : r) : 255) * 0x10000 +
                (g < 255 ? (g < 0 ? 0 : g) : 255) * 0x100 +
                (b < 255 ? (b < 0 ? 0 : b) : 255)
            )
                .toString(16)
                .slice(1)
        );
    }

    function decorateBookings(bookings) {
        return (bookings || []).map((booking) => {
            const loftLabel = booking.loft_label || booking.loft || '';
            return {
                ...booking,
                color: booking.color || loftColor(loftLabel),
                loft_label: loftLabel,
            };
        });
    }

    function decorateKeychains(keys) {
        return (keys || []).map((key) => {
            const loftLabel = key.loft_label || key.loft || '';
            return {
                ...key,
                color: key.color || loftColor(loftLabel),
                loft_label: loftLabel,
            };
        });
    }

    function expandBookingsAcrossStay(bookings) {
        const expanded = [];

        bookings.forEach((booking) => {
            if (!booking.start || !booking.end) return;

            const start = new Date(`${booking.start}T12:00:00`);
            const end = new Date(`${booking.end}T12:00:00`);

            if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;

            const totalNights = Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24)) || 1);

            for (let i = 0; i < totalNights; i++) {
                const night = new Date(start);
                night.setDate(start.getDate() + i);

                expanded.push({
                    ...booking,
                    stay_length: totalNights,
                    night_index: i + 1,
                    day_key: toKey(night),
                });
            }
        });

        return expanded;
    }

    function expandKeychainsAcrossValidity(keys) {
        const expanded = [];

        (keys || []).forEach((keychain) => {
            if (!keychain.start || !keychain.end) return;

            const start = new Date(`${keychain.start}T12:00:00`);
            const end = new Date(`${keychain.end}T12:00:00`);

            if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;

            const totalDays = Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1);

            for (let i = 0; i < totalDays; i++) {
                const day = new Date(start);
                day.setDate(start.getDate() + i);

                expanded.push({
                    ...keychain,
                    day_key: toKey(day),
                    validity_length: totalDays,
                    validity_index: i + 1,
                });
            }
        });

        return expanded;
    }

    function captureFilters(type) {
        const group = $(`.loft-calendar__filters[data-calendar-target="${type}"]`);
        if (!group.length) return [];

        const selected = group
            .find('input[type="checkbox"]:checked')
            .map((_, input) => $(input).val())
            .get()
            .filter(Boolean);

        return selected;
    }

    function syncFilters(type) {
        state.filters = state.filters || {};
        const selections = captureFilters(type);
        state.filters[type] = selections.length ? selections : [];
    }

    function pad(num) {
        return num.toString().padStart(2, '0');
    }

    function toKey(date) {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    function friendlyDate(dateString) {
        if (!dateString) return 'Date TBC';
        const date = new Date(dateString + 'T12:00:00');
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function fullDate(dateString) {
        if (!dateString) return 'Date TBC';
        const date = new Date(dateString + 'T12:00:00');
        return date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
    }

    function buildMonth(baseDate) {
        const startOfMonth = new Date(baseDate.getFullYear(), baseDate.getMonth(), 1);
        const startDay = startOfMonth.getDay();
        const start = new Date(startOfMonth);
        start.setDate(start.getDate() - startDay);

        const days = [];

        for (let i = 0; i < 42; i++) {
            const current = new Date(start);
            current.setDate(start.getDate() + i);
            days.push({
                date: current,
                key: toKey(current),
                isCurrentMonth: current.getMonth() === baseDate.getMonth(),
                isToday: toKey(current) === state.today,
            });
        }

        return days;
    }

    function groupEvents(events, accessor) {
        return events.reduce((acc, event) => {
            const key = accessor(event);
            if (!key) return acc;

            acc[key] = acc[key] || [];
            acc[key].push(event);
            return acc;
        }, {});
    }

    function renderNav(type) {
        const nav = $(`.loft-calendar__nav[data-calendar-target="${type}"]`);
        const date = state.view[type];
        const monthLabel = date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

        nav.html(`
            <button type="button" class="button button-secondary" data-nav="prev" data-type="${type}">‹</button>
            <span style="font-weight:700;min-width:160px;text-align:center;display:inline-block;">${monthLabel}</span>
            <button type="button" class="button button-secondary" data-nav="next" data-type="${type}">›</button>
        `);
    }


    function renderKeyTimeline(days, keys) {
        if (!days.length) return '';

        const dayColumns = days.length || 1;
        const firstDay = days[0]?.date ? new Date(days[0].date) : new Date();
        const lastDay = days[days.length - 1]?.date ? new Date(days[days.length - 1].date) : new Date();
        const dayCells = days
            .map((day) => {
                const classes = ['loft-calendar__timeline-day'];
                if (!day.isCurrentMonth) classes.push('loft-calendar__timeline-day--muted');
                if (day.isToday) classes.push('loft-calendar__timeline-day--today');

                return `
                    <div class="${classes.join(' ')}">
                        <span class="loft-calendar__timeline-day-weekday">${day.date.toLocaleDateString(undefined, {
                            weekday: 'short',
                        })}</span>
                        <span class="loft-calendar__timeline-day-date">${day.date.getDate()}</span>
                    </div>
                `;
            })
            .join('');

        const rows = (keys || [])
            .map((keychain) => {
                if (!keychain.start || !keychain.end) return '';

                const startDate = new Date(`${keychain.start}T12:00:00`);
                const endDate = new Date(`${keychain.end}T12:00:00`);

                if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return '';

                if (endDate < firstDay || startDate > lastDay) return '';

                const color = keychain.color || loftColor(keychain.loft_label || keychain.loft);
                const darker = shadeColor(color, -18);
                const nameLabel = keychain.key_label || 'Keychain';
                const keyNames = keychain.key_names && keychain.key_names.length ? keychain.key_names.join(', ') : nameLabel;
                const statusLabel = state.keyStatuses[keychain.status] || keychain.status || '';

                const msPerDay = 1000 * 60 * 60 * 24;
                const startOffset = Math.max(0, Math.floor((startDate - firstDay) / msPerDay));
                const endOffset = Math.min(
                    days.length - 1,
                    Math.max(startOffset, Math.floor((endDate - firstDay) / msPerDay))
                );
                const span = Math.max(1, Math.min(days.length, endOffset - startOffset + 1));

                return `
                    <div class="loft-calendar__timeline-row">
                        <div class="loft-calendar__timeline-label">
                            <span class="loft-calendar__pill">${keychain.loft_label || keychain.loft || 'Loft'}</span>
                            <div class="loft-calendar__timeline-label-text">
                                <p class="loft-calendar__timeline-title">${nameLabel}</p>
                                <p class="loft-calendar__timeline-meta">${friendlyDate(keychain.start)} → ${friendlyDate(keychain.end)}</p>
                                <p class="loft-calendar__timeline-meta loft-calendar__timeline-meta--keys">🔑 ${keyNames}</p>
                                <p class="loft-calendar__timeline-meta loft-calendar__timeline-meta--status">${statusLabel}</p>
                            </div>
                        </div>
                        <div class="loft-calendar__timeline-track" style="grid-template-columns: repeat(${dayColumns}, minmax(32px, 1fr));">
                            <div class="loft-calendar__timeline-bar" style="grid-column: ${startOffset + 1} / span ${span}; background: linear-gradient(135deg, ${color}, ${darker}); border-left: 5px solid ${darker};">
                                <span class="loft-calendar__timeline-bar-title">${nameLabel}</span>
                                <span class="loft-calendar__timeline-bar-meta">${statusLabel}</span>
                            </div>
                        </div>
                    </div>
                `;
            })
            .filter(Boolean)
            .join('');

        return `
            <div class="loft-calendar__timeline">
                <div class="loft-calendar__timeline-header">
                    <div class="loft-calendar__timeline-label loft-calendar__timeline-label--header">Keychain</div>
                    <div class="loft-calendar__timeline-days" style="grid-template-columns: repeat(${dayColumns}, minmax(32px, 1fr));">${dayCells}</div>
                </div>
                <div class="loft-calendar__timeline-body">
                    ${rows || '<p class="loft-calendar__empty">No keychains match your filters for this month.</p>'}
                </div>
            </div>
        `;
    }

    function renderCalendar(type) {
        const container = $(`#loft-${type}-calendar`);
        const viewDate = state.view[type];
        const days = buildMonth(viewDate);

        const keysSource =
            type === 'keys'
                ? state.keys.filter((key) => {
                      const filters = state.filters?.keys || [];
                      if (!filters.length) return true;
                      return filters.includes(key.status);
                  })
                : state.keys;

        let eventsByDay = {};

        if (type === 'bookings') {
            eventsByDay = groupEvents(
                expandBookingsAcrossStay(state.bookings),
                (event) => event.day_key
            );
        } else if (type === 'cleaning') {
            eventsByDay = groupEvents(state.cleaning, (event) => event.cleaning_date);
        } else if (type === 'keys') {
            eventsByDay = groupEvents(
                expandKeychainsAcrossValidity(keysSource),
                (event) => event.day_key
            );
        }

        const weekdayRow = dayLabels
            .map((label) => `<div class="loft-calendar__weekday">${label}</div>`) // accessibility
            .join('');

        const dayCells = days
            .map((day) => {
                const events = eventsByDay[day.key] || [];
                const classes = ['loft-calendar__day'];
                if (!day.isCurrentMonth) classes.push('loft-calendar__day--muted');
                const dateClass = ['loft-calendar__date'];
                if (day.isToday) dateClass.push('loft-calendar__date--today');

                const eventHtml = events
                    .map((event) => {
                        if (type === 'bookings') {
                            const color = event.color || loftColor(event.loft_label || event.loft);
                            const darker = shadeColor(color, -18);
                            const keyLabel =
                                event.virtual_key_label ||
                                (event.virtual_keys && event.virtual_keys.length
                                    ? event.virtual_keys.join(', ')
                                    : 'Virtual key pending');
                            const stayBadge = event.stay_length > 1
                                ? `<span class="loft-calendar__pill loft-calendar__pill--subtle">Night ${event.night_index} of ${event.stay_length}</span>`
                                : '';

                            return `
                                <div class="loft-calendar__event" aria-label="Booking for ${event.guest}" style="background: linear-gradient(135deg, ${color}, ${darker}); border-left: 5px solid ${darker};">
                                    <div class="loft-calendar__event-heading">
                                        <span class="loft-calendar__pill">${event.loft_label || event.loft}</span>
                                        ${stayBadge}
                                    </div>
                                    <p class="loft-calendar__event-title">${event.guest}</p>
                                    <p class="loft-calendar__meta">${friendlyDate(event.start)} → ${friendlyDate(event.end)} · ${event.nights || event.stay_length || 1} night(s)</p>
                                    <p class="loft-calendar__meta loft-calendar__meta--keys">🔑 ${keyLabel}</p>
                                    <p class="loft-calendar__meta">${event.amount || ''} · ${event.status || ''}</p>
                                </div>
                            `;
                        }

                        if (type === 'keys') {
                            const color = event.color || loftColor(event.loft_label || event.loft);
                            const darker = shadeColor(color, -18);
                            const nameLabel = event.key_label || 'Keychain';
                            const keyNames = event.key_names && event.key_names.length ? event.key_names.join(', ') : nameLabel;
                            const validityBadge =
                                event.validity_length > 1
                                    ? `<span class="loft-calendar__pill loft-calendar__pill--subtle">Day ${event.validity_index} of ${event.validity_length}</span>`
                                    : '';
                            const statusLabel = state.keyStatuses[event.status] || event.status || '';

                            return `
                                <div class="loft-calendar__event" aria-label="Keychain for ${event.loft}" style="background: linear-gradient(135deg, ${color}, ${darker}); border-left: 5px solid ${darker};">
                                    <div class="loft-calendar__event-heading">
                                        <span class="loft-calendar__pill">${event.loft_label || event.loft}</span>
                                        ${validityBadge}
                                    </div>
                                    <p class="loft-calendar__event-title">${nameLabel}</p>
                                    <p class="loft-calendar__meta">${friendlyDate(event.start)} → ${friendlyDate(event.end)}</p>
                                    <p class="loft-calendar__meta loft-calendar__meta--keys">🔑 ${keyNames}</p>
                                    <p class="loft-calendar__meta">${statusLabel}</p>
                                </div>
                            `;
                        }

                        const statusClass = `loft-status-pill loft-status-pill--${event.status}`;
                        const attentionClass = event.needs_attention ? 'loft-calendar__event--attention' : 'loft-calendar__event--cleaning';
                        const issueClass = event.status === 'issue' ? 'loft-calendar__event--issue' : attentionClass;
                        return `
                            <div class="loft-calendar__event ${issueClass}" aria-label="Cleaning for ${event.loft}">
                                <h4>${event.loft}</h4>
                                <p>${event.guest}</p>
                                <p>${friendlyDate(event.cleaning_date)} · Ready before ${friendlyDate(event.arrival)}</p>
                                <span class="${statusClass}">${state.statuses[event.status] || event.status}</span>
                            </div>
                        `;
                    })
                    .join('');

                return `
                    <div class="${classes.join(' ')}" data-date="${day.key}">
                        <div class="${dateClass.join(' ')}">${day.date.getDate()}</div>
                        ${eventHtml}
                    </div>
                `;
            })
            .join('');

        const timeline = type === 'keys' ? renderKeyTimeline(days, keysSource) : '';

        container.html(`
            ${timeline ? `<div class="loft-calendar__timeline-container">${timeline}</div>` : ''}
            <div class="loft-calendar__weekdays">${weekdayRow}</div>
            <div class="loft-calendar__days">${dayCells}</div>
        `);

        renderNav(type);
    }

    function renderQueue() {
        const queue = $('#loft-cleaning-queue');
        if (!queue.length) return;

        const tasks = [...state.cleaning].sort((a, b) => {
            return new Date(a.cleaning_date) - new Date(b.cleaning_date);
        });

        const cards = tasks.map((task) => {
            const statusClass = `loft-status-pill loft-status-pill--${task.status}`;
            const accent = task.needs_attention ? '<span class="loft-chip loft-chip--alert">Needs approval</span>' : '';

            return `
                <article class="loft-calendar__card" aria-label="Cleaning task for ${task.loft}">
                    <h3>${task.loft}</h3>
                    <div class="loft-calendar__meta">Checkout ${fullDate(task.cleaning_date)} · Guest ${task.guest}</div>
                    <div class="loft-calendar__meta">Arrival ${fullDate(task.arrival)}</div>
                    <div class="loft-calendar__meta"><span class="${statusClass}">${state.statuses[task.status] || task.status}</span> ${accent}</div>
                    ${task.note ? `<p class="loft-calendar__meta">Note: ${task.note}</p>` : ''}
                    <div class="loft-calendar__actions-row">
                        <button type="button" class="button" data-booking="${task.booking_id}" data-status="in_progress">Start cleaning</button>
                        <button type="button" class="button button-primary" data-booking="${task.booking_id}" data-status="done">Approve & ready</button>
                        <button type="button" class="button button-secondary" data-booking="${task.booking_id}" data-status="issue">Flag issue</button>
                    </div>
                </article>
            `;
        });

        queue.html(cards.join(''));
    }

    function updateView(type, direction) {
        const current = state.view[type];
        const next = new Date(current);
        next.setMonth(current.getMonth() + (direction === 'next' ? 1 : -1));
        state.view[type] = next;
        renderCalendar(type);
    }

    function refreshFromSnapshot(snapshot) {
        state.bookings = decorateBookings(snapshot.bookings || []);
        state.cleaning = snapshot.cleaning || [];
        state.today = snapshot.today || state.today;
        if ($('#loft-bookings-calendar').length) {
            renderCalendar('bookings');
        }

        if ($('#loft-cleaning-calendar').length) {
            renderCalendar('cleaning');
            renderQueue();
        }

        if ($('#loft-keys-calendar').length) {
            renderCalendar('keys');
        }
    }

    function updateStatus(bookingId, status) {
        if (!settings.ajaxUrl || !settings.nonce) return;

        const payload = {
            action: 'wp_loft_booking_update_cleaning_status',
            booking_id: bookingId,
            status,
            nonce: settings.nonce,
        };

        $.post(settings.ajaxUrl, payload)
            .done((response) => {
                if (response && response.success && response.data && response.data.snapshot) {
                    refreshFromSnapshot(response.data.snapshot);
                }
            });
    }

    $(document).on('click', '.loft-calendar__nav button', function () {
        const type = $(this).data('type');
        const direction = $(this).data('nav');
        updateView(type, direction);
    });

    $(document).on('click', '.loft-calendar__actions-row button', function () {
        const bookingId = $(this).data('booking');
        const status = $(this).data('status');
        updateStatus(bookingId, status);
    });

    $(document).on('change', '.loft-calendar__filters input[type="checkbox"]', function () {
        const type = $(this).closest('.loft-calendar__filters').data('calendar-target');
        if (!type) return;

        syncFilters(type);
        renderCalendar(type);
    });

    if ($('#loft-bookings-calendar').length) {
        renderCalendar('bookings');
    }

    if ($('#loft-cleaning-calendar').length) {
        renderCalendar('cleaning');
        renderQueue();
    }

    if ($('#loft-keys-calendar').length) {
        syncFilters('keys');
        renderCalendar('keys');
    }
})(jQuery);
