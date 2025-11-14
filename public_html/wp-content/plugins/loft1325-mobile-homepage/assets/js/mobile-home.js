(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
            return;
        }
        document.addEventListener('DOMContentLoaded', fn);
    }

    function initializeNav(body) {
        var nav = document.getElementById('loft1325-mobile-nav');
        var toggle = document.querySelector('[data-loft1325-mobile-nav-toggle]');
        var overlay = document.querySelector('[data-loft1325-mobile-nav-overlay]');

        if (!nav || !toggle || !overlay) {
            return;
        }

        function openNav() {
            nav.removeAttribute('hidden');
            nav.setAttribute('aria-hidden', 'false');
            body.classList.add('loft1325-mobile-home--nav-open');
            toggle.setAttribute('aria-expanded', 'true');
            overlay.removeAttribute('hidden');
        }

        function closeNav() {
            nav.setAttribute('aria-hidden', 'true');
            body.classList.remove('loft1325-mobile-home--nav-open');
            toggle.setAttribute('aria-expanded', 'false');
            overlay.setAttribute('hidden', 'hidden');
            window.setTimeout(function () {
                if (nav.getAttribute('aria-hidden') === 'true') {
                    nav.setAttribute('hidden', 'hidden');
                }
            }, 280);
        }

        function toggleNav(event) {
            event.preventDefault();
            var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                closeNav();
            } else {
                openNav();
            }
        }

        toggle.addEventListener('click', toggleNav);
        overlay.addEventListener('click', closeNav);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && body.classList.contains('loft1325-mobile-home--nav-open')) {
                closeNav();
            }
        });

        nav.addEventListener('click', function (event) {
            if (event.target.tagName && event.target.tagName.toLowerCase() === 'a') {
                closeNav();
            }
        });

        var observer = new MutationObserver(function () {
            if (nav.getAttribute('aria-hidden') === 'false') {
                nav.classList.add('loft1325-mobile-home__nav--open');
            } else {
                nav.classList.remove('loft1325-mobile-home__nav--open');
            }
        });

        observer.observe(nav, { attributes: true, attributeFilter: ['aria-hidden'] });
    }

    function initializeSearchForm() {
        var searchCard = document.getElementById('loft1325-mobile-home-search');
        if (!searchCard) {
            return;
        }

        var form = searchCard.querySelector('.loft-search-toolbar__form');
        if (!form) {
            return;
        }

        var dataset = searchCard.dataset || {};
        var guestsSingular = dataset.guestsSingular || 'invité';
        var guestsPlural = dataset.guestsPlural || 'invités';
        var nightsSingular = dataset.nightsSingular || 'nuit';
        var nightsPlural = dataset.nightsPlural || 'nuits';

        var checkInInput = form.querySelector('#nd_booking_archive_form_date_range_from');
        var checkOutInput = form.querySelector('#nd_booking_archive_form_date_range_to');
        var guestInput = form.querySelector('#nd_booking_archive_form_guests');
        var guestDisplay = form.querySelector('#loft_search_guest_display');
        var nightsDisplay = form.querySelector('#nd_booking_nights_display');
        var guestButtons = form.querySelectorAll('.loft-search-toolbar__guest-btn');
        var dateControls = form.querySelectorAll('.loft-search-toolbar__control--date');

        var MIN_GUESTS = 1;
        var MAX_GUESTS = 12;
        var ONE_DAY = 86400000;

        function parseDate(value) {
            if (typeof value !== 'string' || !value) {
                return null;
            }

            var parts = value.split('/');
            if (parts.length !== 3) {
                return null;
            }

            var month = parseInt(parts[0], 10) - 1;
            var day = parseInt(parts[1], 10);
            var year = parseInt(parts[2], 10);

            if (parts[2].length === 2) {
                year += year < 70 ? 2000 : 1900;
            }

            if (isNaN(month) || isNaN(day) || isNaN(year)) {
                return null;
            }

            var date = new Date(year, month, day);
            return isNaN(date.getTime()) ? null : date;
        }

        function formatDateForInput(date) {
            if (!(date instanceof Date)) {
                return '';
            }

            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var year = date.getFullYear();
            return month + '/' + day + '/' + year;
        }

        function clampGuests(value) {
            var parsed = parseInt(value, 10);
            if (isNaN(parsed) || parsed < MIN_GUESTS) {
                parsed = MIN_GUESTS;
            }
            if (parsed > MAX_GUESTS) {
                parsed = MAX_GUESTS;
            }
            return parsed;
        }

        function formatGuests(value) {
            return value + ' ' + (value === 1 ? guestsSingular : guestsPlural);
        }

        function formatNights(value) {
            return value + ' ' + (value === 1 ? nightsSingular : nightsPlural);
        }

        function updateGuestDisplay() {
            if (!guestInput || !guestDisplay) {
                return;
            }

            var current = clampGuests(guestInput.value);
            guestInput.value = current;
            guestDisplay.textContent = formatGuests(current);
        }

        function adjustGuests(direction) {
            if (!guestInput) {
                return;
            }

            var value = clampGuests(guestInput.value);
            if (direction === 'up') {
                value = Math.min(MAX_GUESTS, value + 1);
            } else if (direction === 'down') {
                value = Math.max(MIN_GUESTS, value - 1);
            }
            guestInput.value = value;
            updateGuestDisplay();
        }

        Array.prototype.forEach.call(guestButtons, function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var direction = button.getAttribute('data-direction');
                adjustGuests(direction === 'down' ? 'down' : 'up');
            });
        });

        updateGuestDisplay();

        function updateNightsDisplay() {
            if (!nightsDisplay || !checkInInput) {
                return;
            }

            var start = parseDate(checkInInput.value);
            var end = checkOutInput ? parseDate(checkOutInput.value) : null;

            if (!start) {
                nightsDisplay.textContent = '—';
                return;
            }

            if (!end || end <= start) {
                end = new Date(start.getTime() + ONE_DAY);

                if (checkOutInput) {
                    checkOutInput.value = formatDateForInput(end);
                }

                if (window.jQuery && typeof window.jQuery.fn.datepicker === 'function' && checkOutInput) {
                    window.jQuery(checkOutInput).datepicker('setDate', end);
                    window.jQuery(checkOutInput).datepicker('option', 'minDate', end);
                }
            }

            var nights = Math.max(1, Math.round((end - start) / ONE_DAY));
            nightsDisplay.textContent = formatNights(nights);
        }

        var $ = window.jQuery;
        if ($ && typeof $.fn.datepicker === 'function') {
            if (checkInInput) {
                $(checkInInput).datepicker({
                    defaultDate: '+0',
                    minDate: 0,
                    dateFormat: 'mm/dd/yy',
                    firstDay: 0,
                    numberOfMonths: 1,
                    onClose: function (selectedDate) {
                        var parsed = parseDate(selectedDate);
                        if (parsed && checkOutInput) {
                            var minCheckout = new Date(parsed.getTime() + ONE_DAY);
                            $(checkOutInput).datepicker('option', 'minDate', minCheckout);

                            var currentCheckout = $(checkOutInput).datepicker('getDate');
                            if (!currentCheckout || currentCheckout <= parsed) {
                                $(checkOutInput).datepicker('setDate', minCheckout);
                            }
                        }

                        updateNightsDisplay();
                    }
                });
            }

            if (checkOutInput) {
                $(checkOutInput).datepicker({
                    defaultDate: '+1',
                    minDate: '+1d',
                    dateFormat: 'mm/dd/yy',
                    firstDay: 0,
                    numberOfMonths: 1,
                    onClose: function () {
                        updateNightsDisplay();
                    }
                });
            }

            if (checkInInput && checkOutInput) {
                var initialStart = parseDate(checkInInput.value);
                if (initialStart) {
                    var initialMinCheckout = new Date(initialStart.getTime() + ONE_DAY);
                    $(checkOutInput).datepicker('option', 'minDate', initialMinCheckout);
                }
            }

            Array.prototype.forEach.call(dateControls, function (control) {
                control.addEventListener('click', function () {
                    var input = control.querySelector('.loft-search-toolbar__input');
                    if (input) {
                        $(input).datepicker('show');
                    }
                });
            });
        } else {
            Array.prototype.forEach.call(dateControls, function (control) {
                control.addEventListener('click', function () {
                    var input = control.querySelector('.loft-search-toolbar__input');
                    if (input) {
                        input.focus();
                    }
                });
            });
        }

        if (checkInInput) {
            checkInInput.addEventListener('change', updateNightsDisplay);
        }

        if (checkOutInput) {
            checkOutInput.addEventListener('change', updateNightsDisplay);
        }

        if (guestInput) {
            guestInput.addEventListener('change', updateGuestDisplay);
        }

        updateNightsDisplay();

        form.addEventListener('submit', function () {
            if (guestInput) {
                guestInput.value = clampGuests(guestInput.value);
            }
        });
    }

    ready(function () {
        var body = document.body;
        if (!body || !body.classList.contains('loft1325-mobile-home-active')) {
            return;
        }

        initializeNav(body);
        initializeSearchForm();
    });
})();
