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
        var dateInput = form.querySelector('#loft_booking_date_range');
        var dateClear = form.querySelector('[data-date-clear]');
        var guestGroups = form.querySelectorAll('[data-guest-group]');
        var promoToggle = form.querySelector('[data-promo-toggle]');
        var promoField = form.querySelector('[data-promo-field]');
        var promoInput = form.querySelector('#loft_booking_coupon');
        var promoCheckoutInput = form.querySelector('#loft_booking_coupon_checkout');
        var submitButton = form.querySelector('.loft-search-toolbar__submit');
        var language = (form.getAttribute('data-language') || 'fr').toLowerCase() === 'en' ? 'en' : 'fr';
        var overlay = document.createElement('div');
        overlay.className = 'loft-datepicker-overlay';
        document.body.appendChild(overlay);

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

        function formatDisplayRange(start, end) {
            var locale = language === 'en' ? 'en-CA' : 'fr-CA';
            var formatter = new Intl.DateTimeFormat(locale, {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });
            return formatter.format(start) + ' – ' + formatter.format(end);
        }

        function updateDateFields(dates) {
            if (!Array.isArray(dates)) {
                dates = [];
            }

            var start = dates[0] ? new Date(dates[0]) : null;
            var end = dates[1] ? new Date(dates[1]) : null;

            if (start && end && end < start) {
                var temp = start;
                start = end;
                end = temp;
            }

            if (!start && checkInInput && checkInInput.value) {
                start = parseDate(checkInInput.value);
            }

            if (!end && checkOutInput && checkOutInput.value) {
                end = parseDate(checkOutInput.value);
            }

            if (start && end) {
                if (checkInInput) {
                    checkInInput.value = formatDateForInput(start);
                }
                if (checkOutInput) {
                    checkOutInput.value = formatDateForInput(end);
                }
                if (dateInput) {
                    dateInput.value = formatDisplayRange(start, end);
                    dateInput.setAttribute('data-has-value', 'true');
                }
            } else {
                if (checkInInput) {
                    checkInInput.value = '';
                }
                if (checkOutInput) {
                    checkOutInput.value = '';
                }
                if (dateInput) {
                    dateInput.value = '';
                    dateInput.setAttribute('data-has-value', 'false');
                }
            }
        }

        var fpInstance = null;
        if (window.flatpickr && dateInput) {
            var mobileQuery = window.matchMedia('(max-width: 480px)');

            function isMobileViewport() {
                return mobileQuery && mobileQuery.matches;
            }

            function setOverlayVisibility(isVisible) {
                if (isVisible) {
                    document.body.classList.add('loft-datepicker-open');
                    overlay.classList.add('is-visible');
                } else {
                    document.body.classList.remove('loft-datepicker-open');
                    overlay.classList.remove('is-visible');
                }
            }

            function applyCalendarLayout(instance) {
                var calendar = instance && instance.calendarContainer;
                if (!calendar) {
                    return;
                }

                if (isMobileViewport()) {
                    calendar.classList.add('flatpickr-bottom-sheet');
                    setOverlayVisibility(true);
                } else {
                    calendar.classList.remove('flatpickr-bottom-sheet');
                    setOverlayVisibility(false);
                }
            }

            if (mobileQuery) {
                var handleMobileChange = function () {
                    if (!fpInstance) {
                        return;
                    }
                    var shouldShowOverlay = fpInstance.isOpen && isMobileViewport();
                    applyCalendarLayout(fpInstance);
                    if (!shouldShowOverlay) {
                        setOverlayVisibility(false);
                    }
                };
                if (mobileQuery.addEventListener) {
                    mobileQuery.addEventListener('change', handleMobileChange);
                } else if (mobileQuery.addListener) {
                    mobileQuery.addListener(handleMobileChange);
                }
            }

            fpInstance = window.flatpickr(dateInput, {
                mode: 'range',
                dateFormat: 'm/d/Y',
                minDate: 'today',
                defaultDate: [],
                monthSelectorType: 'static',
                clickOpens: true,
                inline: false,
                static: false,
                locale: language === 'en' ? 'default' : 'fr',
                onOpen: function (selectedDates, dateStr, instance) {
                    applyCalendarLayout(instance);
                },
                onClose: function () {
                    setOverlayVisibility(false);
                },
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2 && selectedDates[1] < selectedDates[0]) {
                        var swapped = [selectedDates[1], selectedDates[0]];
                        instance.setDate(swapped, false);
                        selectedDates = swapped;
                    }
                    updateDateFields(selectedDates);
                    if (!isMobileViewport() && selectedDates.length === 2) {
                        instance.close();
                    }
                }
            });
        }

        if (dateClear && fpInstance) {
            dateClear.addEventListener('click', function (event) {
                event.preventDefault();
                fpInstance.clear();
                updateDateFields([]);
            });
        }

        overlay.addEventListener('click', function () {
            if (fpInstance) {
                fpInstance.close();
            }
        });

        var initialDates = [];
        var initialStart = parseDate(checkInInput ? checkInInput.value : '');
        var initialEnd = parseDate(checkOutInput ? checkOutInput.value : '');
        if (initialStart) {
            initialDates.push(initialStart);
        }
        if (initialEnd) {
            initialDates.push(initialEnd);
        }
        if (fpInstance && initialDates.length) {
            fpInstance.setDate(initialDates, false);
        }
        updateDateFields(initialDates);

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
