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

        var datesGroup = document.createElement('div');
        datesGroup.className = 'loft1325-mobile-home__search-date-group';
        datesField.appendChild(datesGroup);

        var dateWrappers = fieldsSource.querySelectorAll('.nd_booking_width_50_percentage');
        Array.prototype.forEach.call(dateWrappers, function (wrapper, index) {
            wrapper.classList.add('loft1325-mobile-home__search-date');
            wrapper.classList.remove('nd_booking_width_50_percentage', 'nd_booking_width_100_percentage_all_iphone', 'nd_booking_float_left');

            var label = wrapper.querySelector('.nd_options_color_grey');
            if (label) {
                label.classList.add('loft1325-mobile-home__search-subheading');
                label.textContent = index === 0 ? arrivalLabel : departureLabel;
            }

            datesGroup.appendChild(wrapper);
        });

        if (dateWrappers.length) {
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

        var dateInputs = form.querySelectorAll('#nd_booking_archive_form_date_range_from, #nd_booking_archive_form_date_range_to');
        Array.prototype.forEach.call(dateInputs, function (input) {
            if (!input.placeholder || input.placeholder === 'Check In' || input.placeholder === 'Check Out') {
                input.placeholder = datePlaceholder;
            }
        });

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
