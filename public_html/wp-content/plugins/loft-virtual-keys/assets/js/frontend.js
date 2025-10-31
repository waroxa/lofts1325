(function() {
    var COLUMN_COUNT = 8;

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

    function createEmptyRow(message) {
        var row = document.createElement('tr');
        var cell = document.createElement('td');
        cell.colSpan = COLUMN_COUNT;
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

    function renderTable(container, keychains) {
        var tbody = container.querySelector('.loft-vk__table tbody');
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '';

        if (!keychains || !keychains.length) {
            tbody.appendChild(createEmptyRow('No active keychains found.'));
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
        var paginationEl = container.querySelector('.loft-vk__pagination');
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

    function handleFetchResponse(response) {
        if (!response.ok) {
            return response.json().then(function(error) {
                var message = (error && error.message) ? error.message : 'Unexpected server error.';
                throw new Error(message);
            });
        }

        return response.json();
    }

    function fetchKeychains(container, page) {
        var restUrl = container.getAttribute('data-rest-url');
        var nonce = container.getAttribute('data-rest-nonce');

        if (!restUrl || !nonce) {
            renderStatus(container, 'Missing REST endpoint configuration.', true);
            return;
        }

        var url = restUrl;
        if (page && page > 1) {
            url += (restUrl.indexOf('?') === -1 ? '?' : '&') + 'page=' + page;
        }

        renderStatus(container, 'Loading keychains…');

        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': nonce
            }
        })
            .then(handleFetchResponse)
            .then(function(data) {
                renderStatus(container, '');
                renderTable(container, data.keychains || []);
                renderPagination(container, data.pagination || {});
            })
            .catch(function(error) {
                console.error(error);
                renderStatus(container, 'Unable to load keychains. Please refresh and try again.', true);
                renderTable(container, []);
                renderPagination(container, null);
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var containers = document.querySelectorAll('.loft-vk');
        containers.forEach(function(container) {
            fetchKeychains(container, 1);
        });
    });
})();
