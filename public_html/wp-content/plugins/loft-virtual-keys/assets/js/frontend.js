(function() {
    var KEYCHAIN_COLUMN_COUNT = 8;
    var LOFT_COLUMN_COUNT = 5;

    var guestFormManagers = new WeakMap();

    function renderStatus(container, message, isError) {
        var statusEl = container.querySelector('.loft-vk__status');
        if (!statusEl) {
            return;
        }

        statusEl.textContent = message || '';
        if (isError) {
            statusEl.classList.add('loft-vk__status--error');
        } else {
            statusEl.classList.remove('loft-vk__status--error');
        }
    }

    function createEmptyRow(message, columnCount) {
        var row = document.createElement('tr');
        var cell = document.createElement('td');
        cell.colSpan = columnCount || KEYCHAIN_COLUMN_COUNT;
        cell.className = 'loft-vk__muted';
        cell.textContent = message;
        row.appendChild(cell);
        return row;
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }

        var normalized = value.replace(' ', 'T');
        var parsed = new Date(normalized);

        if (isNaN(parsed.getTime())) {
            return value;
        }

        return parsed.toLocaleString();
    }

    function removeToast(toast) {
        if (!toast) {
            return;
        }

        toast.classList.remove('loft-vk__toast--visible');

        window.setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    function showToast(container, message) {
        if (!container || !message) {
            return;
        }

        var toastContainer = container.querySelector('.loft-vk__toast-container');

        if (!toastContainer) {
            return;
        }

        var toast = document.createElement('div');
        toast.className = 'loft-vk__toast';
        toast.setAttribute('role', 'alert');

        var icon = document.createElement('span');
        icon.className = 'loft-vk__toast-icon';
        icon.textContent = '✨';

        var text = document.createElement('span');
        text.className = 'loft-vk__toast-message';
        text.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'loft-vk__toast-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = '×';
        close.addEventListener('click', function() {
            removeToast(toast);
        });

        toast.appendChild(icon);
        toast.appendChild(text);
        toast.appendChild(close);

        toastContainer.appendChild(toast);

        window.requestAnimationFrame(function() {
            toast.classList.add('loft-vk__toast--visible');
        });

        var timeoutId = window.setTimeout(function() {
            removeToast(toast);
        }, 6000);

        toast.addEventListener('mouseenter', function() {
            window.clearTimeout(timeoutId);
        });

        toast.addEventListener('mouseleave', function() {
            timeoutId = window.setTimeout(function() {
                removeToast(toast);
            }, 2500);
        });
    }

    function buildDetails(summary, items) {
        var details = document.createElement('details');
        var summaryEl = document.createElement('summary');
        summaryEl.textContent = summary;
        details.appendChild(summaryEl);

        var list = document.createElement('ul');
        list.className = 'loft-vk__list';
        items.forEach(function(item) {
            list.appendChild(item);
        });

        details.appendChild(list);
        return details;
    }

    function buildPeopleCell(people) {
        if (!people || !people.length) {
            var empty = document.createElement('span');
            empty.className = 'loft-vk__muted';
            empty.textContent = 'None';
            return empty;
        }

        var items = people.map(function(person) {
            var li = document.createElement('li');
            var name = person.name || 'Unnamed';
            li.textContent = name;

            if (person.type) {
                var type = document.createElement('span');
                type.className = 'loft-vk__muted';
                type.textContent = ' — ' + person.type;
                li.appendChild(type);
            }

            if (person.email) {
                var emailLink = document.createElement('a');
                emailLink.href = 'mailto:' + person.email;
                emailLink.textContent = person.email;
                emailLink.className = 'loft-vk__link';
                li.appendChild(document.createElement('br'));
                li.appendChild(emailLink);
            }

            return li;
        });

        return buildDetails(people.length + ' people', items);
    }

    function buildVirtualKeysCell(keys) {
        if (!keys || !keys.length) {
            var empty = document.createElement('span');
            empty.className = 'loft-vk__muted';
            empty.textContent = 'None';
            return empty;
        }

        var items = keys.map(function(key) {
            var li = document.createElement('li');

            var labelParts = [];
            if (key.name) {
                labelParts.push(key.name);
            }
            if (key.type) {
                labelParts.push('(' + key.type + ')');
            }
            if (key.status) {
                labelParts.push('[' + key.status + ']');
            }

            li.textContent = labelParts.join(' ');

            if (key.id) {
                var code = document.createElement('code');
                code.textContent = key.id;
                li.appendChild(document.createElement('br'));
                li.appendChild(code);
            }

            return li;
        });

        return buildDetails(keys.length + ' keys', items);
    }

    function getPanel(container, name) {
        return container.querySelector('.loft-vk__panel[data-panel="' + name + '"]');
    }

    function renderKeychainsTable(container, keychains) {
        var panel = getPanel(container, 'keys');
        if (!panel) {
            return;
        }

        var tbody = panel.querySelector('tbody');
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '';

        if (!keychains || !keychains.length) {
            tbody.appendChild(createEmptyRow('No active keychains found.', KEYCHAIN_COLUMN_COUNT));
            return;
        }

        keychains.forEach(function(item) {
            var row = document.createElement('tr');

            var idCell = document.createElement('td');
            idCell.textContent = item.id != null ? item.id : '';
            row.appendChild(idCell);

            var nameCell = document.createElement('td');
            nameCell.textContent = item.name || '';
            row.appendChild(nameCell);

            var tenantCell = document.createElement('td');
            tenantCell.textContent = item.tenant || '';
            row.appendChild(tenantCell);

            var unitCell = document.createElement('td');
            unitCell.textContent = item.unit || '';
            row.appendChild(unitCell);

            var peopleCell = document.createElement('td');
            peopleCell.appendChild(buildPeopleCell(item.people));
            row.appendChild(peopleCell);

            var keysCell = document.createElement('td');
            keysCell.appendChild(buildVirtualKeysCell(item.virtual_keys));
            row.appendChild(keysCell);

            var validFromCell = document.createElement('td');
            validFromCell.textContent = formatDate(item.valid_from);
            row.appendChild(validFromCell);

            var validUntilCell = document.createElement('td');
            validUntilCell.textContent = formatDate(item.valid_until);
            row.appendChild(validUntilCell);

            tbody.appendChild(row);
        });
    }

    function renderPagination(container, pagination) {
        var paginationEl = container.querySelector('.loft-vk__panel[data-panel="keys"] .loft-vk__pagination');
        if (!paginationEl) {
            return;
        }

        paginationEl.innerHTML = '';

        if (!pagination || pagination.total_pages <= 1) {
            paginationEl.hidden = true;
            return;
        }

        paginationEl.hidden = false;

        var currentPage = pagination.page || 1;
        var totalPages = pagination.total_pages || 1;

        var prevButton = document.createElement('button');
        prevButton.type = 'button';
        prevButton.className = 'button loft-vk__page';
        prevButton.textContent = '« Prev';
        prevButton.disabled = currentPage <= 1;
        prevButton.addEventListener('click', function() {
            if (currentPage > 1) {
                fetchKeychains(container, currentPage - 1);
            }
        });
        paginationEl.appendChild(prevButton);

        var pageInfo = document.createElement('span');
        pageInfo.className = 'loft-vk__page-info';
        pageInfo.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        paginationEl.appendChild(pageInfo);

        var nextButton = document.createElement('button');
        nextButton.type = 'button';
        nextButton.className = 'button loft-vk__page';
        nextButton.textContent = 'Next »';
        nextButton.disabled = currentPage >= totalPages;
        nextButton.addEventListener('click', function() {
            if (currentPage < totalPages) {
                fetchKeychains(container, currentPage + 1);
            }
        });
        paginationEl.appendChild(nextButton);
    }

    function buildStatusLabel(status, label) {
        var span = document.createElement('span');
        span.className = 'loft-vk__status-label';
        var normalized = (status || '').toLowerCase();
        if (normalized) {
            span.className += ' loft-vk__status-label--' + normalized;
        }
        span.textContent = label || status || '';
        return span;
    }

    function ensureGuestForm(container) {
        if (!container) {
            return null;
        }

        if (guestFormManagers.has(container)) {
            return guestFormManagers.get(container);
        }

        var manager = setupGuestForm(container);

        if (manager) {
            guestFormManagers.set(container, manager);
        }

        return manager;
    }

    function formatDateForInputValue(date) {
        if (!date || typeof date.getFullYear !== 'function') {
            return '';
        }

        var month = (date.getMonth() + 1).toString().padStart(2, '0');
        var day = date.getDate().toString().padStart(2, '0');

        return date.getFullYear() + '-' + month + '-' + day;
    }

    function setupGuestForm(container) {
        var dialog = container.querySelector('.loft-vk__dialog');
        var form = dialog ? dialog.querySelector('.loft-vk__form') : null;

        if (!dialog || !form) {
            return null;
        }

        var loftLabel = dialog.querySelector('.loft-vk__dialog-loft');
        var errorEl = form.querySelector('.loft-vk__form-error');
        var nameInput = form.querySelector('input[name="guest_name"]');
        var emailInput = form.querySelector('input[name="guest_email"]');
        var phoneInput = form.querySelector('input[name="guest_phone"]');
        var checkinInput = form.querySelector('input[name="checkin_date"]');
        var checkoutInput = form.querySelector('input[name="checkout_date"]');
        var cancelButtons = dialog.querySelectorAll('[data-dialog-cancel]');
        var backdrop = dialog.querySelector('.loft-vk__dialog-backdrop');
        var activeLoft = null;
        var resolver = null;
        var isOpen = false;
        var previouslyFocused = null;

        function clearErrors() {
            if (errorEl) {
                errorEl.textContent = '';
            }

            [nameInput, emailInput, checkinInput, checkoutInput].forEach(function(input) {
                if (!input) {
                    return;
                }
                input.classList.remove('loft-vk__form-input--error');
                input.setAttribute('aria-invalid', 'false');
            });
        }

        function setError(message, inputs) {
            if (errorEl) {
                errorEl.textContent = message || '';
            }

            (inputs || []).forEach(function(input) {
                if (!input) {
                    return;
                }

                input.classList.add('loft-vk__form-input--error');
                input.setAttribute('aria-invalid', 'true');
            });
        }

        function close(result) {
            if (!isOpen) {
                return;
            }

            isOpen = false;
            activeLoft = null;
            dialog.classList.remove('loft-vk__dialog--visible');
            window.setTimeout(function() {
                dialog.setAttribute('hidden', '');
            }, 200);
            container.classList.remove('loft-vk--dialog-open');
            if (document.body) {
                document.body.classList.remove('loft-vk-dialog-open');
            }
            document.removeEventListener('keydown', onKeyDown);

            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus();
            }

            previouslyFocused = null;

            if (resolver) {
                var resolveFn = resolver;
                resolver = null;
                resolveFn(result || null);
            }
        }

        function cancel() {
            close(null);
        }

        function onKeyDown(event) {
            if (event.key === 'Escape' || event.key === 'Esc') {
                event.preventDefault();
                cancel();
            }
        }

        cancelButtons.forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                cancel();
            });
        });

        if (backdrop) {
            backdrop.addEventListener('click', function(event) {
                event.preventDefault();
                cancel();
            });
        }

        [nameInput, emailInput, phoneInput, checkinInput, checkoutInput].forEach(function(input) {
            if (!input) {
                return;
            }

            input.addEventListener('input', function() {
                input.classList.remove('loft-vk__form-input--error');
                input.setAttribute('aria-invalid', 'false');
                if (errorEl) {
                    errorEl.textContent = '';
                }
            });
        });

        if (checkinInput && checkoutInput) {
            checkinInput.addEventListener('change', function() {
                if (!checkinInput.value) {
                    return;
                }

                checkoutInput.min = checkinInput.value;
                if (checkoutInput.value && checkoutInput.value < checkinInput.value) {
                    checkoutInput.value = checkinInput.value;
                }
            });
        }

        form.addEventListener('submit', function(event) {
            event.preventDefault();

            clearErrors();

            var guestName = nameInput ? nameInput.value.trim() : '';
            var guestEmail = emailInput ? emailInput.value.trim() : '';
            var guestPhone = phoneInput ? phoneInput.value.trim() : '';
            var checkinValue = checkinInput ? checkinInput.value : '';
            var checkoutValue = checkoutInput ? checkoutInput.value : '';
            var invalidInputs = [];
            var errorMessage = '';
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var datePattern = /^\d{4}-\d{2}-\d{2}$/;

            if (!guestName) {
                invalidInputs.push(nameInput);
                errorMessage = errorMessage || 'Le nom du client est requis. / Guest name is required.';
            }

            if (!guestEmail) {
                invalidInputs.push(emailInput);
                errorMessage = errorMessage || 'Le courriel du client est requis. / Guest email is required.';
            } else if (!emailPattern.test(guestEmail)) {
                invalidInputs.push(emailInput);
                errorMessage = errorMessage || 'Veuillez saisir un courriel valide. / Please enter a valid email address.';
            }

            if (!datePattern.test(checkinValue)) {
                invalidInputs.push(checkinInput);
                errorMessage = errorMessage || 'Veuillez sélectionner une date d\'arrivée valide. / Please select a valid check-in date.';
            }

            if (!datePattern.test(checkoutValue)) {
                invalidInputs.push(checkoutInput);
                errorMessage = errorMessage || 'Veuillez sélectionner une date de départ valide. / Please select a valid check-out date.';
            }

            if (!invalidInputs.includes(checkinInput) && !invalidInputs.includes(checkoutInput)) {
                var checkinDate = new Date(checkinValue + 'T00:00:00');
                var checkoutDate = new Date(checkoutValue + 'T00:00:00');

                if (!(checkoutDate > checkinDate)) {
                    invalidInputs.push(checkoutInput);
                    errorMessage = 'La date de départ doit être après l\'arrivée. / The check-out date must be after check-in.';
                }
            }

            if (invalidInputs.length) {
                setError(errorMessage || 'Veuillez vérifier les informations saisies. / Please review the highlighted fields.', invalidInputs);
                return;
            }

            var payload = {
                unit_id: activeLoft && activeLoft.id ? activeLoft.id : null,
                guest_name: guestName,
                guest_email: guestEmail,
                guest_phone: guestPhone,
                checkin_date: checkinValue,
                checkout_date: checkoutValue
            };

            close(payload);
        });

        function open(loft) {
            clearErrors();

            activeLoft = loft || null;
            previouslyFocused = document.activeElement;

            var today = new Date();
            var tomorrow = new Date();
            tomorrow.setDate(today.getDate() + 1);

            var checkinDefault = formatDateForInputValue(today);
            var checkoutDefault = formatDateForInputValue(tomorrow);

            form.reset();
            form.dataset.unitId = activeLoft && activeLoft.id ? String(activeLoft.id) : '';

            if (loftLabel) {
                loftLabel.textContent = loft && loft.unit ? loft.unit : '';
            }

            if (checkinInput) {
                checkinInput.value = checkinDefault;
                checkinInput.min = checkinDefault;
            }

            if (checkoutInput) {
                checkoutInput.value = checkoutDefault;
                checkoutInput.min = checkinDefault;
            }

            if (dialog.hasAttribute('hidden')) {
                dialog.removeAttribute('hidden');
            }

            document.addEventListener('keydown', onKeyDown);
            if (document.body) {
                document.body.classList.add('loft-vk-dialog-open');
            }
            container.classList.add('loft-vk--dialog-open');

            window.requestAnimationFrame(function() {
                dialog.classList.add('loft-vk__dialog--visible');
            });

            window.setTimeout(function() {
                if (nameInput) {
                    nameInput.focus();
                }
            }, 120);

            isOpen = true;

            return new Promise(function(resolve) {
                resolver = resolve;
            });
        }

        return {
            open: open
        };
    }
    function createGenerateButton(container, loft) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button-secondary loft-vk__generate';
        button.textContent = 'Generate Virtual Key';
        button.disabled = !loft.can_generate;

        if (!loft.can_generate) {
            button.title = 'This loft is not available for key generation.';
        }

        button.addEventListener('click', function() {
            handleGenerateClick(container, button, loft);
        });

        return button;
    }

    function renderLoftCardsLoading(cardsContainer, message) {
        if (!cardsContainer) {
            return;
        }

        cardsContainer.innerHTML = '';

        var loadingCard = document.createElement('div');
        loadingCard.className = 'loft-vk__card loft-vk__card--loading';
        loadingCard.setAttribute('role', 'listitem');
        loadingCard.textContent = message || 'Loading lofts…';
        cardsContainer.appendChild(loadingCard);
    }

    function renderLoftCards(cardsContainer, lofts, emptyMessage, container) {
        if (!cardsContainer) {
            return;
        }

        cardsContainer.innerHTML = '';

        if (!lofts || !lofts.length) {
            var emptyCard = document.createElement('div');
            emptyCard.className = 'loft-vk__card loft-vk__card--empty';
            emptyCard.setAttribute('role', 'listitem');
            emptyCard.textContent = emptyMessage || 'No lofts available.';
            cardsContainer.appendChild(emptyCard);
            return;
        }

        lofts.forEach(function(loft) {
            var card = document.createElement('article');
            card.className = 'loft-vk__card';
            card.setAttribute('role', 'listitem');

            if (loft.id != null) {
                card.dataset.loftId = String(loft.id);
            }

            var header = document.createElement('div');
            header.className = 'loft-vk__card-header';

            var title = document.createElement('h3');
            title.className = 'loft-vk__card-title';
            title.textContent = loft.unit || '';
            header.appendChild(title);

            var statusWrap = document.createElement('div');
            statusWrap.className = 'loft-vk__card-status';
            statusWrap.appendChild(buildStatusLabel(loft.status || '', loft.status_label || loft.status || ''));
            header.appendChild(statusWrap);

            card.appendChild(header);

            if (loft.building_id) {
                var building = document.createElement('p');
                building.className = 'loft-vk__card-building';
                building.textContent = 'Building ' + loft.building_id;
                card.appendChild(building);
            }

            var details = document.createElement('dl');
            details.className = 'loft-vk__card-details';

            var idRow = document.createElement('div');
            idRow.className = 'loft-vk__card-detail';
            var idTerm = document.createElement('dt');
            idTerm.textContent = 'ButterflyMX ID';
            var idValue = document.createElement('dd');
            idValue.textContent = loft.butterflymx_unit_id || '—';
            idRow.appendChild(idTerm);
            idRow.appendChild(idValue);
            details.appendChild(idRow);

            var availabilityRow = document.createElement('div');
            availabilityRow.className = 'loft-vk__card-detail';
            var availabilityTerm = document.createElement('dt');
            availabilityTerm.textContent = 'Available Until';
            var availabilityValue = document.createElement('dd');
            availabilityValue.textContent = formatDate(loft.availability_until) || '—';
            availabilityRow.appendChild(availabilityTerm);
            availabilityRow.appendChild(availabilityValue);
            details.appendChild(availabilityRow);

            card.appendChild(details);

            var actions = document.createElement('div');
            actions.className = 'loft-vk__card-actions';
            actions.appendChild(createGenerateButton(container, loft));
            card.appendChild(actions);

            cardsContainer.appendChild(card);
        });
    }

    function renderLoftsTable(container, lofts, emptyMessage) {
        var panel = getPanel(container, 'lofts');
        if (!panel) {
            return;
        }

        var tbody = panel.querySelector('tbody');
        var cardsContainer = panel.querySelector('.loft-vk__cards');

        if (!tbody) {
            renderLoftCards(cardsContainer, lofts, emptyMessage, container);
            return;
        }

        tbody.innerHTML = '';

        if (!lofts || !lofts.length) {
            var message = emptyMessage || 'No lofts available.';
            tbody.appendChild(createEmptyRow(message, LOFT_COLUMN_COUNT));
            renderLoftCards(cardsContainer, [], message, container);
            return;
        }

        lofts.forEach(function(loft) {
            var row = document.createElement('tr');

            if (loft.id != null) {
                row.dataset.loftId = String(loft.id);
            }

            var unitCell = document.createElement('td');
            var nameEl = document.createElement('strong');
            nameEl.textContent = loft.unit || '';
            unitCell.appendChild(nameEl);

            if (loft.building_id) {
                var buildingMeta = document.createElement('div');
                buildingMeta.className = 'loft-vk__muted';
                buildingMeta.textContent = 'Building ' + loft.building_id;
                unitCell.appendChild(buildingMeta);
            }

            row.appendChild(unitCell);

            var idCell = document.createElement('td');
            idCell.textContent = loft.butterflymx_unit_id || '—';
            row.appendChild(idCell);

            var statusCell = document.createElement('td');
            statusCell.appendChild(buildStatusLabel(loft.status || '', loft.status_label || loft.status || ''));
            row.appendChild(statusCell);

            var availabilityCell = document.createElement('td');
            var availability = formatDate(loft.availability_until);
            availabilityCell.textContent = availability || '—';
            row.appendChild(availabilityCell);

            var actionsCell = document.createElement('td');
            actionsCell.className = 'loft-vk__actions';
            actionsCell.appendChild(createGenerateButton(container, loft));
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });

        renderLoftCards(cardsContainer, lofts, emptyMessage, container);
    }

    function handleGenerateClick(container, button, loft) {
        if (!loft || !loft.id) {
            renderStatus(container, "Impossible de déterminer le loft sélectionné. / Unable to determine the selected loft.", true);
            return;
        }

        var formManager = ensureGuestForm(container);

        if (!formManager || typeof formManager.open !== 'function') {
            renderStatus(container, 'Le formulaire invité est indisponible. Veuillez rafraîchir la page. / Guest form unavailable. Please refresh the page.', true);
            return;
        }

        formManager.open(loft).then(function(payload) {
            if (!payload) {
                return;
            }

            var originalText = button.textContent;
            var resetButtonState = function() {
                button.disabled = false;
                button.classList.remove('loft-vk__generate--loading');
                button.removeAttribute('aria-busy');
                button.textContent = originalText;
            };

            var nonce = container.getAttribute('data-rest-nonce');
            var base = container.getAttribute('data-generate-url');

            if (!nonce || !base) {
                renderStatus(container, 'Missing generation endpoint configuration.', true);
                return;
            }

            var baseUrl = base.replace(/\/$/, '');
            var url = baseUrl + '/' + loft.id + '/generate-key';

            button.disabled = true;
            button.classList.add('loft-vk__generate--loading');
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Création… / Generating…';
            var statusMessage = 'Création de la clé virtuelle pour ' + (loft.unit || 'cette unité') + '… / Generating virtual key…';
            renderStatus(container, statusMessage);

            var request = fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': nonce,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(handleFetchResponse)
                .then(function(data) {
                    var message = (data && data.message) ? data.message : 'Virtual key created.';
                    var refreshPlanned = !!(data && data.refresh_scheduled);
                    var successStatus = refreshPlanned
                        ? message + ' ButterflyMX keychains will refresh shortly to update availability.'
                        : message;

                    renderStatus(container, successStatus);
                    showToast(container, message);

                    if (refreshPlanned) {
                        showToast(container, 'ButterflyMX keychains will refresh shortly to update availability.');
                    }

                    return Promise.all([
                        fetchKeychains(container, 1, { showStatus: false }),
                        fetchLofts(container, { showStatus: false })
                    ]).then(function() {
                        triggerSyncUnitsButton();
                        return data;
                    });
                })
                .catch(function(error) {
                    console.error(error);
                    renderStatus(container, error.message || 'Unable to generate virtual key.', true);
                    throw error;
                });

            if (request && typeof request.finally === 'function') {
                request.finally(function() {
                    resetButtonState();
                });
            } else {
                // Fallback for older browsers without Promise.prototype.finally
                request.then(resetButtonState, resetButtonState);
            }
        }).catch(function(error) {
            if (error) {
                console.error(error);
                renderStatus(container, error.message || 'Unable to open the guest form.', true);
            }
        });
    }
    function triggerSyncUnitsButton() {
        var syncButton = document.getElementById('sync-units-button');
        if (syncButton) {
            syncButton.click();
        }
    }

    function handleFetchResponse(response) {
        if (!response.ok) {
            return response.json().then(function(error) {
                var message = (error && error.message) ? error.message : 'Unexpected server error.';
                throw new Error(message);
            });
        }

        return response.json();
    }

    function fetchKeychains(container, page, options) {
        options = options || {};
        var showStatus = options.showStatus !== false;

        var restUrl = container.getAttribute('data-rest-url');
        var nonce = container.getAttribute('data-rest-nonce');
        var panel = getPanel(container, 'keys');
        var tbody = panel ? panel.querySelector('tbody') : null;

        if (!restUrl || !nonce) {
            if (showStatus) {
                renderStatus(container, 'Missing REST endpoint configuration.', true);
            }
            return Promise.resolve();
        }

        if (tbody) {
            tbody.innerHTML = '';
            var loadingMessage = showStatus ? 'Loading keychains…' : 'Updating keychains…';
            tbody.appendChild(createEmptyRow(loadingMessage, KEYCHAIN_COLUMN_COUNT));
        }

        var url = restUrl;
        if (page && page > 1) {
            url += (restUrl.indexOf('?') === -1 ? '?' : '&') + 'page=' + page;
        }

        if (showStatus) {
            renderStatus(container, 'Loading keychains…');
        }

        return fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': nonce
            }
        })
            .then(handleFetchResponse)
            .then(function(data) {
                if (showStatus) {
                    renderStatus(container, '');
                }
                renderKeychainsTable(container, data.keychains || []);
                renderPagination(container, data.pagination || {});
                return data;
            })
            .catch(function(error) {
                console.error(error);
                renderStatus(container, 'Unable to load keychains. Please refresh and try again.', true);
                renderKeychainsTable(container, []);
                renderPagination(container, null);
                throw error;
            });
    }

    function fetchLofts(container, options) {
        options = options || {};
        var showStatus = options.showStatus !== false;

        var loftsUrl = container.getAttribute('data-lofts-url');
        var nonce = container.getAttribute('data-rest-nonce');
        var panel = getPanel(container, 'lofts');
        var tbody = panel ? panel.querySelector('tbody') : null;
        var cardsContainer = panel ? panel.querySelector('.loft-vk__cards') : null;

        if (!loftsUrl || !nonce) {
            if (showStatus) {
                renderStatus(container, 'Missing lofts endpoint configuration.', true);
            }
            return Promise.resolve();
        }

        if (tbody) {
            tbody.innerHTML = '';
            var loadingMessage = showStatus ? 'Loading lofts…' : 'Updating lofts…';
            tbody.appendChild(createEmptyRow(loadingMessage, LOFT_COLUMN_COUNT));
        }

        if (cardsContainer) {
            var loadingCardMessage = showStatus ? 'Loading lofts…' : 'Updating lofts…';
            renderLoftCardsLoading(cardsContainer, loadingCardMessage);
        }

        if (showStatus) {
            renderStatus(container, 'Loading lofts…');
        }

        return fetch(loftsUrl, {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': nonce
            }
        })
            .then(handleFetchResponse)
            .then(function(data) {
                if (showStatus) {
                    renderStatus(container, '');
                }
                renderLoftsTable(container, data.lofts || []);
                return data;
            })
            .catch(function(error) {
                console.error(error);
                renderStatus(container, 'Unable to load lofts. Please refresh and try again.', true);
                renderLoftsTable(container, [], 'Unable to load lofts.');
                throw error;
            });
    }

    function activateTab(container, tabName) {
        var current = container.getAttribute('data-active-tab');
        if (current === tabName) {
            return;
        }

        var tabs = container.querySelectorAll('.loft-vk__tab');
        tabs.forEach(function(tab) {
            var isActive = tab.getAttribute('data-tab') === tabName;
            tab.classList.toggle('loft-vk__tab--active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        var panels = container.querySelectorAll('.loft-vk__panel');
        panels.forEach(function(panel) {
            var isActive = panel.getAttribute('data-panel') === tabName;
            panel.hidden = !isActive;
            panel.classList.toggle('loft-vk__panel--active', isActive);
        });

        container.setAttribute('data-active-tab', tabName);
        renderStatus(container, '');

        if (tabName === 'keys') {
            fetchKeychains(container, 1);
        } else if (tabName === 'lofts') {
            fetchLofts(container);
        }
    }

    function setupTabs(container) {
        var tabs = container.querySelectorAll('.loft-vk__tab');

        if (!tabs.length) {
            fetchLofts(container);
            return;
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                activateTab(container, tab.getAttribute('data-tab'));
            });

            tab.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    tab.click();
                }
            });
        });

        ensureGuestForm(container);
        activateTab(container, 'lofts');
    }

    document.addEventListener('DOMContentLoaded', function() {
        var containers = document.querySelectorAll('.loft-vk');
        containers.forEach(function(container) {
            setupTabs(container);
        });
    });
})();
