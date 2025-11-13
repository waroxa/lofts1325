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

        var searchComponent = searchCard.querySelector('.nd_booking_search_elem_component_l3');
        if (!searchComponent || searchComponent.classList.contains('loft1325-mobile-home__search--enhanced')) {
            return;
        }

        searchComponent.classList.add('loft1325-mobile-home__search--enhanced');
        searchComponent.classList.add('loft1325-mobile-home__search-component');

        var form = searchComponent.querySelector('form');
        if (!form) {
            return;
        }

        form.classList.add('loft1325-mobile-home__search-form-inner');

        var fieldsWrapper = form.querySelector('.nd_booking_section_box_search_field');
        if (!fieldsWrapper) {
            return;
        }

        fieldsWrapper.classList.add('loft1325-mobile-home__search-fields');

        var dateWrappers = fieldsWrapper.querySelectorAll('.nd_booking_width_50_percentage');
        var guestsWrapper = fieldsWrapper.querySelector('.nd_booking_border_top_1_solid_grey');

        if (dateWrappers.length === 2) {
            var datesField = document.createElement('div');
            datesField.className = 'loft1325-mobile-home__search-field loft1325-mobile-home__search-field--dates';

            var datesLabel = document.createElement('span');
            datesLabel.className = 'loft1325-mobile-home__search-label';
            datesLabel.textContent = 'Quand';
            datesField.appendChild(datesLabel);

            var datesGroup = document.createElement('div');
            datesGroup.className = 'loft1325-mobile-home__search-date-group';
            datesField.appendChild(datesGroup);

            Array.prototype.forEach.call(dateWrappers, function (wrapper, index) {
                wrapper.classList.add('loft1325-mobile-home__search-date');

                var label = wrapper.querySelector('.nd_options_color_grey');
                if (label) {
                    label.classList.add('loft1325-mobile-home__search-subheading');
                    label.textContent = index === 0 ? 'Arrivée' : 'Départ';
                }

                datesGroup.appendChild(wrapper);
            });

            if (guestsWrapper) {
                fieldsWrapper.insertBefore(datesField, guestsWrapper);
            } else {
                fieldsWrapper.appendChild(datesField);
            }
        }

        if (guestsWrapper) {
            guestsWrapper.classList.add('loft1325-mobile-home__search-field', 'loft1325-mobile-home__search-field--guests');
            guestsWrapper.classList.remove('nd_booking_border_top_1_solid_grey');

            var guestsLabel = guestsWrapper.querySelector('.nd_options_color_grey');
            if (guestsLabel) {
                guestsLabel.classList.add('loft1325-mobile-home__search-label');
                guestsLabel.textContent = 'Invités';
            }

            var spacers = guestsWrapper.querySelectorAll(
                '.nd_booking_section.nd_booking_height_15, .nd_booking_section.nd_booking_height_10, .nd_booking_section.nd_booking_height_7, .nd_booking_section.nd_booking_height_5'
            );
            Array.prototype.forEach.call(spacers, function (spacer) {
                spacer.style.display = 'none';
            });

            var guestsNumber = guestsWrapper.querySelector('.nd_booking_guests_number');
            if (guestsNumber) {
                var updateGuestLabel = function () {
                    var value = parseInt(guestsNumber.textContent, 10);
                    if (isNaN(value) || value < 0) {
                        value = 0;
                    }
                    guestsNumber.setAttribute('data-suffix', value === 1 ? 'invité' : 'invités');
                };

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
        }

        var submitRow = form.querySelector('.nd_booking_text_align_center');
        if (submitRow) {
            submitRow.classList.add('loft1325-mobile-home__search-actions');
        }

        var submitButton = form.querySelector('input[type="submit"]');
        if (submitButton) {
            submitButton.value = 'Rechercher';
        }

        var labels = fieldsWrapper.querySelectorAll('.nd_booking_label_search');
        Array.prototype.forEach.call(labels, function (label) {
            label.classList.add('loft1325-mobile-home__search-label');
        });

        var dateInputs = fieldsWrapper.querySelectorAll('input[type="text"], input[type="date"]');
        Array.prototype.forEach.call(dateInputs, function (input) {
            if (!input.placeholder) {
                input.placeholder = 'Sélectionner les dates';
            }
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
