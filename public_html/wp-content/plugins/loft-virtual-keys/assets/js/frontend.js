(function() {
    var KEYCHAIN_COLUMN_COUNT = 8;
    var LOFT_COLUMN_COUNT = 5;

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

    function promptForGuestInfo(container, loft) {
        var guestName = window.prompt('Nom du client / Guest name');
        if (guestName === null) {
            return null;
        }
        guestName = guestName.trim();
        if (!guestName) {
            renderStatus(container, 'Le nom du client est requis. / Guest name is required.', true);
            return null;
        }

        var guestEmail = window.prompt('Courriel du client / Guest email');
        if (guestEmail === null) {
            return null;
        }
        guestEmail = guestEmail.trim();
        if (!guestEmail) {
            renderStatus(container, 'Le courriel du client est requis. / Guest email is required.', true);
            return null;
        }

        var guestPhonePrompt = window.prompt('Téléphone du client / Guest phone (optionnel)');
        if (guestPhonePrompt === null) {
            return null;
        }
        var guestPhone = guestPhonePrompt.trim();

        var checkin = window.prompt('Date d\'arrivée (YYYY-MM-DD) / Check-in date');
        if (checkin === null) {
            return null;
        }
        checkin = checkin.trim();
        if (!checkin) {
            renderStatus(container, 'La date d\'arrivée est requise. / Check-in date is required.', true);
            return null;
        }

        var checkout = window.prompt('Date de départ (YYYY-MM-DD) / Check-out date');
        if (checkout === null) {
            return null;
        }
        checkout = checkout.trim();
        if (!checkout) {
            renderStatus(container, 'La date de départ est requise. / Check-out date is required.', true);
            return null;
        }

        return {
            unit_id: loft && loft.id ? loft.id : null,
            guest_name: guestName,
            guest_email: guestEmail,
            guest_phone: guestPhone,
            checkin_date: checkin,
            checkout_date: checkout
        };
    }

    function renderLoftsTable(container, lofts, emptyMessage) {
        var panel = getPanel(container, 'lofts');
        if (!panel) {
            return;
        }

        var tbody = panel.querySelector('tbody');
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '';

        if (!lofts || !lofts.length) {
            var message = emptyMessage || 'No lofts available.';
            tbody.appendChild(createEmptyRow(message, LOFT_COLUMN_COUNT));
            return;
        }

        lofts.forEach(function(loft) {
            var row = document.createElement('tr');

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
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'button button-secondary loft-vk__generate';
            button.textContent = 'Generate Virtual Key';
            button.disabled = !loft.can_generate;
            button.addEventListener('click', function() {
                handleGenerateClick(container, button, loft);
            });
            if (!loft.can_generate) {
                button.title = 'This loft is not available for key generation.';
            }
            actionsCell.appendChild(button);
            row.appendChild(actionsCell);

            tbody.appendChild(row);
        });
    }

    function handleGenerateClick(container, button, loft) {
        if (!loft || !loft.id) {
            return;
        }

        var payload = promptForGuestInfo(container, loft);
        if (!payload) {
            return;
        }

        var nonce = container.getAttribute('data-rest-nonce');
        var base = container.getAttribute('data-generate-url');

        if (!nonce || !base) {
            renderStatus(container, 'Missing generation endpoint configuration.', true);
            return;
        }

        var baseUrl = base.replace(/\/$/, '');
        var url = baseUrl + '/' + loft.id + '/generate-key';

        button.disabled = true;
        var statusMessage = 'Création de la clé virtuelle pour ' + (loft.unit || 'cette unité') + '… / Generating virtual key…';
        renderStatus(container, statusMessage);

        fetch(url, {
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
                renderStatus(container, message);
                return Promise.all([
                    fetchKeychains(container, 1, { showStatus: false }),
                    fetchLofts(container, { showStatus: false })
                ]);
            })
            .catch(function(error) {
                console.error(error);
                renderStatus(container, error.message || 'Unable to generate virtual key.', true);
                button.disabled = false;
            });
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
            fetchKeychains(container, 1);
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

        activateTab(container, 'keys');
    }

    document.addEventListener('DOMContentLoaded', function() {
        var containers = document.querySelectorAll('.loft-vk');
        containers.forEach(function(container) {
            setupTabs(container);
        });
    });
})();
