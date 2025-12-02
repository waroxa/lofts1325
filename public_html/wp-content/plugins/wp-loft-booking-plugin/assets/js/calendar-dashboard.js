(function ($) {
    'use strict';

    const settings = window.wpLoftCalendarData || {};
    const initial = settings.payload || {};

    const state = {
        bookings: initial.bookings || [],
        cleaning: initial.cleaning || [],
        today: initial.today || new Date().toISOString().slice(0, 10),
        view: {
            bookings: initial.today ? new Date(initial.today) : new Date(),
            cleaning: initial.today ? new Date(initial.today) : new Date(),
        },
        statuses: settings.statuses || {},
    };

    const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

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

    function renderCalendar(type) {
        const container = $(`#loft-${type}-calendar`);
        const viewDate = state.view[type];
        const days = buildMonth(viewDate);
        const eventsByDay = groupEvents(
            type === 'bookings' ? state.bookings : state.cleaning,
            (event) => (type === 'bookings' ? event.start : event.cleaning_date)
        );

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
                            return `
                                <div class="loft-calendar__event" aria-label="Booking for ${event.guest}">
                                    <h4>${event.loft}</h4>
                                    <p>${event.guest}</p>
                                    <p>${friendlyDate(event.start)} → ${friendlyDate(event.end)} · ${event.nights || 1} night(s)</p>
                                    <p style="opacity:0.85;">${event.amount || ''} · ${event.status || ''}</p>
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

        container.html(`
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
        state.bookings = snapshot.bookings || [];
        state.cleaning = snapshot.cleaning || [];
        state.today = snapshot.today || state.today;
        renderCalendar('bookings');
        renderCalendar('cleaning');
        renderQueue();
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

    renderCalendar('bookings');
    renderCalendar('cleaning');
    renderQueue();
})(jQuery);
