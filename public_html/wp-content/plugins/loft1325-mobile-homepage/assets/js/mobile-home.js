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

    function enhanceSearchForm() {
        var searchCard = document.getElementById('loft1325-mobile-home-search');
        if (!searchCard) {
            return;
        }

        var searchComponent = searchCard.querySelector('.nd_booking_search_component_l2, .nd_booking_search_elem_component_l3');
        if (!searchComponent || searchComponent.classList.contains('loft1325-mobile-home__search--enhanced')) {
            return;
        }

        searchComponent.classList.add('loft1325-mobile-home__search--enhanced');
        searchComponent.classList.add('loft1325-mobile-home__search-component');

        var header = searchComponent.querySelector('.nd_booking_section.nd_booking_text_align_center');
        if (header) {
            header.remove();
        }

        var form = searchComponent.querySelector('form');
        if (!form) {
            return;
        }

        form.classList.add('loft1325-mobile-home__search-form-inner');

        var dataset = searchCard.dataset || {};
        var dateLabel = dataset.dateLabel || 'Quand';
        var arrivalLabel = dataset.arrivalLabel || 'Arrivée';
        var departureLabel = dataset.departureLabel || 'Départ';
        var guestsLabel = dataset.guestsLabel || 'Invités';
        var submitLabel = dataset.submitLabel || 'Rechercher';
        var datePlaceholder = dataset.datePlaceholder || 'Sélectionner les dates';
        var guestsSingular = dataset.guestsSingular || 'invité';
        var guestsPlural = dataset.guestsPlural || 'invités';

        var wrappers = form.querySelectorAll('.nd_booking_width_100_percentage');
        var fieldsSource = null;
        Array.prototype.forEach.call(wrappers, function (wrapper) {
            if (!fieldsSource && (wrapper.querySelector('#nd_booking_open_calendar_from') || wrapper.querySelector('#nd_booking_open_calendar_to'))) {
                fieldsSource = wrapper;
            }
        });

        if (!fieldsSource) {
            return;
        }

        var fieldsContainer = document.createElement('div');
        fieldsContainer.className = 'loft1325-mobile-home__search-fields';
        form.insertBefore(fieldsContainer, fieldsSource);

        var datesField = document.createElement('div');
        datesField.className = 'loft1325-mobile-home__search-field loft1325-mobile-home__search-field--dates';

        var datesLabel = document.createElement('span');
        datesLabel.className = 'loft1325-mobile-home__search-label';
        datesLabel.textContent = dateLabel;
        datesField.appendChild(datesLabel);

        var dateDisplay = null;
        var dateDisplayValue = null;
        var calendarTrigger = null;

        var dateWrappers = fieldsSource.querySelectorAll('.nd_booking_width_50_percentage');
        if (dateWrappers.length) {
            dateDisplay = document.createElement('button');
            dateDisplay.type = 'button';
            dateDisplay.className = 'loft1325-mobile-home__search-input loft1325-mobile-home__search-input--calendar';
            dateDisplay.setAttribute('data-has-value', 'false');
            dateDisplay.setAttribute('aria-label', dateLabel);
            dateDisplay.setAttribute('aria-haspopup', 'dialog');

            dateDisplayValue = document.createElement('span');
            dateDisplayValue.className = 'loft1325-mobile-home__search-input-value';
            dateDisplayValue.textContent = datePlaceholder;
            dateDisplay.appendChild(dateDisplayValue);

            datesField.appendChild(dateDisplay);

            var hiddenDates = document.createElement('div');
            hiddenDates.className = 'loft1325-mobile-home__search-hidden';
            datesField.appendChild(hiddenDates);

            Array.prototype.forEach.call(dateWrappers, function (wrapper) {
                wrapper.classList.add('loft1325-mobile-home__search-date');
                wrapper.classList.remove('nd_booking_width_50_percentage', 'nd_booking_width_100_percentage_all_iphone', 'nd_booking_float_left');

                var potentialTrigger = wrapper.querySelector('[id^="nd_booking_open_calendar_"]');
                if (!calendarTrigger && potentialTrigger) {
                    calendarTrigger = potentialTrigger;
                }

                hiddenDates.appendChild(wrapper);
            });

            fieldsContainer.appendChild(datesField);
        }

        var guestsWrapper = null;
        Array.prototype.forEach.call(fieldsSource.children, function (child) {
            if (!guestsWrapper && child.querySelector && child.querySelector('#nd_booking_archive_form_guests')) {
                guestsWrapper = child;
            }
        });

        if (guestsWrapper) {
            guestsWrapper.classList.add('loft1325-mobile-home__search-field', 'loft1325-mobile-home__search-field--guests');
            guestsWrapper.classList.remove('nd_booking_width_100_percentage', 'nd_booking_width_100_percentage_all_iphone', 'nd_booking_float_left', 'nd_booking_border_top_1_solid_grey');

            var guestsLabelEl = guestsWrapper.querySelector('.nd_options_color_grey');
            if (guestsLabelEl) {
                guestsLabelEl.classList.add('loft1325-mobile-home__search-label');
                guestsLabelEl.textContent = guestsLabel;
            }

            var spacers = guestsWrapper.querySelectorAll(
                '.nd_booking_section.nd_booking_height_15, .nd_booking_section.nd_booking_height_10, .nd_booking_section.nd_booking_height_7, .nd_booking_section.nd_booking_height_5'
            );
            Array.prototype.forEach.call(spacers, function (spacer) {
                spacer.style.display = 'none';
            });

            fieldsContainer.appendChild(guestsWrapper);
        }

        if (fieldsSource.parentNode) {
            fieldsSource.parentNode.removeChild(fieldsSource);
        }

        var spacer = form.querySelector('.nd_booking_section.nd_booking_height_30');
        if (spacer) {
            spacer.remove();
        }

        var submitRow = form.querySelector('.nd_booking_width_100_percentage.nd_booking_bg_greydark');
        if (submitRow) {
            submitRow.classList.add('loft1325-mobile-home__search-actions');
            submitRow.classList.remove('nd_booking_bg_greydark');
        }

        var submitButton = form.querySelector('input[type="submit"]');
        if (submitButton) {
            submitButton.value = submitLabel;
        }

        var fromInput = form.querySelector('#nd_booking_archive_form_date_range_from');
        var toInput = form.querySelector('#nd_booking_archive_form_date_range_to');

        var dateInputs = [fromInput, toInput];
        Array.prototype.forEach.call(dateInputs, function (input) {
            if (input && (!input.placeholder || input.placeholder === 'Check In' || input.placeholder === 'Check Out')) {
                input.placeholder = datePlaceholder;
            }
        });

        function formatDateValue(value) {
            if (typeof value !== 'string' || value.trim() === '') {
                return null;
            }

            var parts = value.split('/');
            if (parts.length < 3) {
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
            if (isNaN(date.getTime())) {
                return null;
            }

            return date.toLocaleDateString('fr-CA', { day: 'numeric', month: 'short' }).replace('.', '');
        }

        function updateDateDisplay() {
            if (!dateDisplay || !dateDisplayValue) {
                return;
            }

            var fromValue = fromInput ? formatDateValue(fromInput.value) : null;
            var toValue = toInput ? formatDateValue(toInput.value) : null;

            if (fromValue && toValue) {
                dateDisplayValue.textContent = fromValue + ' – ' + toValue;
                dateDisplay.setAttribute('data-has-value', 'true');
            } else if (fromValue) {
                dateDisplayValue.textContent = fromValue;
                dateDisplay.setAttribute('data-has-value', 'true');
            } else {
                dateDisplayValue.textContent = datePlaceholder;
                dateDisplay.setAttribute('data-has-value', 'false');
            }

            dateDisplay.setAttribute('aria-label', dateLabel + ': ' + dateDisplayValue.textContent);
        }

        if (dateDisplay) {
            var openCalendar = function () {
                if (window.jQuery && typeof window.jQuery.fn.datepicker === 'function') {
                    window.jQuery('#nd_booking_archive_form_date_range_from').datepicker('show');
                } else if (calendarTrigger && typeof calendarTrigger.click === 'function') {
                    calendarTrigger.click();
                } else if (fromInput) {
                    fromInput.focus();
                }
            };

            dateDisplay.addEventListener('click', openCalendar);
        }

        Array.prototype.forEach.call(dateInputs, function (input) {
            if (!input) {
                return;
            }

            input.addEventListener('change', updateDateDisplay);
            input.addEventListener('input', updateDateDisplay);

            var observer = new MutationObserver(updateDateDisplay);
            observer.observe(input, { attributes: true, attributeFilter: ['value'] });
        });

        updateDateDisplay();

        var guestsNumber = form.querySelector('.nd_booking_guests_number');
        var guestsWord = form.querySelector('.nd_booking_guests_number_word');

        if (guestsWord) {
            guestsWord.textContent = guestsSingular;
        }

        var updateGuestLabel = function () {
            if (!guestsNumber) {
                return;
            }

            var value = parseInt(guestsNumber.textContent, 10);
            if (isNaN(value) || value < 0) {
                value = 0;
            }

            var suffix = value === 1 ? guestsSingular : guestsPlural;
            guestsNumber.setAttribute('data-suffix', suffix);

            if (guestsWord) {
                guestsWord.textContent = suffix;
            }
        };

        if (guestsNumber) {
            updateGuestLabel();

            var guestsObserver = new MutationObserver(function () {
                updateGuestLabel();
            });

            guestsObserver.observe(guestsNumber, {
                childList: true,
                subtree: true,
                characterData: true,
            });
        }

        var labels = form.querySelectorAll('.nd_booking_label_search');
        Array.prototype.forEach.call(labels, function (label) {
            label.classList.add('loft1325-mobile-home__search-label');
        });
    }

    ready(function () {
        var body = document.body;
        if (!body || !body.classList.contains('loft1325-mobile-home-active')) {
            return;
        }

        initializeNav(body);
        enhanceSearchForm();
    });
})();
