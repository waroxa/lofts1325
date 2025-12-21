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

        var checkInInput = form.querySelector('#nd_booking_archive_form_date_range_from');
        var checkOutInput = form.querySelector('#nd_booking_archive_form_date_range_to');
        var totalGuestsInput = form.querySelector('#nd_booking_archive_form_guests');
        var adultInput = form.querySelector('#loft_booking_adults');
        var childInput = form.querySelector('#loft_booking_children');
        var dateDisplay = form.querySelector('#loft_booking_date_display');
        var dateTrigger = form.querySelector('#loft_booking_date_trigger');
        var guestGroups = form.querySelectorAll('[data-guest-group]');
        var promoToggle = form.querySelector('[data-promo-toggle]');
        var promoField = form.querySelector('[data-promo-field]');
        var promoInput = form.querySelector('#loft_booking_coupon');
        var promoCheckoutInput = form.querySelector('#loft_booking_coupon_checkout');
        var submitButton = form.querySelector('.loft-search-toolbar__submit');

        var MIN_ADULTS = 1;
        var MAX_ADULTS = 10;
        var MIN_CHILDREN = 0;
        var MAX_CHILDREN = 10;
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

        function clampAdults(value) {
            var parsed = parseInt(value, 10);
            if (isNaN(parsed) || parsed < MIN_ADULTS) {
                parsed = MIN_ADULTS;
            }
            if (parsed > MAX_ADULTS) {
                parsed = MAX_ADULTS;
            }
            return parsed;
        }

        function clampChildren(value) {
            var parsed = parseInt(value, 10);
            if (isNaN(parsed) || parsed < MIN_CHILDREN) {
                parsed = MIN_CHILDREN;
            }
            if (parsed > MAX_CHILDREN) {
                parsed = MAX_CHILDREN;
            }
            return parsed;
        }

        function updateTotalGuests() {
            if (!totalGuestsInput || !adultInput || !childInput) {
                return;
            }
            var adults = clampAdults(adultInput.value);
            var children = clampChildren(childInput.value);
            totalGuestsInput.value = adults + children;
        }

        function updateGuestGroupDisplay(group) {
            if (!group) {
                return;
            }
            var valueEl = group.querySelector('.loft-search-toolbar__guests-value');
            var hiddenInput = group.querySelector('input[type=\"hidden\"]');
            var direction = group.getAttribute('data-guest-group');

            if (!valueEl || !hiddenInput) {
                return;
            }

            var value = direction === 'children' ? clampChildren(hiddenInput.value) : clampAdults(hiddenInput.value);
            hiddenInput.value = value;
            valueEl.textContent = value;
        }

        function adjustGroup(group, direction) {
            if (!group) {
                return;
            }
            var input = group.querySelector('input[type=\"hidden\"]');
            if (!input) {
                return;
            }
            var isChildren = group.getAttribute('data-guest-group') === 'children';
            var current = isChildren ? clampChildren(input.value) : clampAdults(input.value);
            if (direction === 'up') {
                current = current + 1;
                current = isChildren ? clampChildren(current) : clampAdults(current);
            } else {
                current = current - 1;
                current = isChildren ? clampChildren(current) : clampAdults(current);
            }
            input.value = current;
            updateGuestGroupDisplay(group);
            updateTotalGuests();
        }

        Array.prototype.forEach.call(guestGroups, function (group) {
            var buttons = group.querySelectorAll('.loft-search-toolbar__guest-btn');
            Array.prototype.forEach.call(buttons, function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    var dir = button.getAttribute('data-direction') === 'down' ? 'down' : 'up';
                    adjustGroup(group, dir);
                });
            });
            updateGuestGroupDisplay(group);
        });

        updateTotalGuests();

        function updateDateDisplay() {
            if (!dateDisplay) {
                return;
            }
            var placeholder = dateDisplay.getAttribute('data-placeholder') || '';
            var start = parseDate(checkInInput ? checkInInput.value : '');
            var end = parseDate(checkOutInput ? checkOutInput.value : '');

            if (start && end) {
                dateDisplay.textContent = formatDateForInput(start) + ' - ' + formatDateForInput(end);
            } else {
                dateDisplay.textContent = placeholder;
            }
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

                        updateDateDisplay();
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
                        updateDateDisplay();
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

            if (dateTrigger && checkInInput) {
                dateTrigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    $(checkInInput).datepicker('show');
                });
            }
        } else {
            if (dateTrigger && checkInInput) {
                dateTrigger.addEventListener('click', function () {
                    checkInInput.focus();
                });
            }
        }

        if (checkInInput) {
            checkInInput.addEventListener('change', updateDateDisplay);
        }

        if (checkOutInput) {
            checkOutInput.addEventListener('change', updateDateDisplay);
        }

        updateDateDisplay();

        if (promoToggle && promoField) {
            promoToggle.addEventListener('click', function (event) {
                event.preventDefault();
                var isHidden = promoField.hasAttribute('hidden');
                if (isHidden) {
                    promoField.removeAttribute('hidden');
                } else {
                    promoField.setAttribute('hidden', 'hidden');
                }
            });
        }

        if (promoInput && promoCheckoutInput) {
            promoInput.addEventListener('input', function () {
                promoCheckoutInput.value = promoInput.value;
            });
        }

        function buildSearchUrl() {
            var action = form.getAttribute('action') || window.location.href;
            var url = null;

            try {
                url = new URL(action, window.location.origin);
            } catch (error) {
                url = null;
            }

            var params = new URLSearchParams();

            if (checkInInput && checkInInput.value) {
                params.set('nd_booking_archive_form_date_range_from', checkInInput.value);
            }

            if (checkOutInput && checkOutInput.value) {
                params.set('nd_booking_archive_form_date_range_to', checkOutInput.value);
            }

            if (totalGuestsInput && totalGuestsInput.value) {
                params.set('nd_booking_archive_form_guests', totalGuestsInput.value);
            }

            if (adultInput && adultInput.value) {
                params.set('nd_booking_archive_form_adults', adultInput.value);
            }

            if (childInput && childInput.value) {
                params.set('nd_booking_archive_form_children', childInput.value);
            }

            if (promoInput && promoInput.value) {
                params.set('nd_booking_booking_form_coupon', promoInput.value);
                params.set('nd_booking_checkout_form_coupon', promoInput.value);
            }

            if (url) {
                params.forEach(function (value, key) {
                    url.searchParams.set(key, value);
                });

                return url.toString();
            }

            var query = params.toString();
            return query ? action + (action.indexOf('?') === -1 ? '?' : '&') + query : action;
        }

        function handleSubmit(event) {
            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            updateTotalGuests();

            var destination = buildSearchUrl();
            if (destination) {
                window.location.assign(destination);
            }
        }

        if (submitButton) {
            submitButton.addEventListener('click', handleSubmit);
        }

        form.addEventListener('submit', handleSubmit);
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
