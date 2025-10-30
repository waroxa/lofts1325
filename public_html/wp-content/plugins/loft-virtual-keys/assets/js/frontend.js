(function() {
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

    function renderKeys(container, keys) {
        var tbody = container.querySelector('.loft-vk__table tbody');
        if (!tbody) {
            return;
        }

        tbody.innerHTML = '';

        if (!keys || !keys.length) {
            var emptyRow = document.createElement('tr');
            var emptyCell = document.createElement('td');
            emptyCell.colSpan = 2;
            emptyCell.textContent = 'No virtual keys have been generated yet.';
            emptyRow.appendChild(emptyCell);
            tbody.appendChild(emptyRow);
            return;
        }

        keys.forEach(function(item) {
            var row = document.createElement('tr');
            var keyCell = document.createElement('td');
            var createdCell = document.createElement('td');
            var keyText = document.createElement('code');
            var copyButton = document.createElement('button');

            keyText.textContent = item.key;
            copyButton.type = 'button';
            copyButton.className = 'button button-secondary loft-vk__copy';
            copyButton.textContent = 'Copy';
            copyButton.addEventListener('click', function() {
                navigator.clipboard.writeText(item.key).then(function() {
                    renderStatus(container, 'Key copied to clipboard.', false);
                }).catch(function() {
                    renderStatus(container, 'Unable to copy the key automatically. Please copy it manually.', true);
                });
            });

            keyCell.appendChild(keyText);
            keyCell.appendChild(document.createTextNode(' '));
            keyCell.appendChild(copyButton);

            createdCell.textContent = item.created_at ? new Date(item.created_at.replace(' ', 'T')).toLocaleString() : '';

            row.appendChild(keyCell);
            row.appendChild(createdCell);
            tbody.appendChild(row);
        });
    }

    function fetchKeys(container) {
        var restUrl = container.getAttribute('data-rest-url');
        var nonce = container.getAttribute('data-rest-nonce');

        if (!restUrl || !nonce) {
            renderStatus(container, 'Missing REST endpoint configuration.', true);
            return;
        }

        renderStatus(container, 'Loading keys…');

        fetch(restUrl, {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': nonce
            }
        })
            .then(handleFetchResponse)
            .then(function(data) {
                renderStatus(container, '');
                renderKeys(container, data.keys || []);
            })
            .catch(function(error) {
                console.error(error);
                renderStatus(container, 'Unable to load keys. Please refresh and try again.', true);
            });
    }

    function generateKey(container) {
        var restUrl = container.getAttribute('data-rest-url');
        var nonce = container.getAttribute('data-rest-nonce');

        if (!restUrl || !nonce) {
            renderStatus(container, 'Missing REST endpoint configuration.', true);
            return;
        }

        renderStatus(container, 'Generating key…');

        fetch(restUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            },
            body: JSON.stringify({})
        })
            .then(handleFetchResponse)
            .then(function(data) {
                renderStatus(container, 'New key generated successfully.');
                renderKeys(container, data.keys || []);
            })
            .catch(function(error) {
                console.error(error);
                renderStatus(container, 'Unable to generate a key. Please try again.', true);
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

    function init(container) {
        var generateButton = container.querySelector('.loft-vk__generate');
        if (generateButton) {
            generateButton.addEventListener('click', function() {
                generateKey(container);
            });
        }

        fetchKeys(container);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var containers = document.querySelectorAll('.loft-vk');
        containers.forEach(function(container) {
            init(container);
        });
    });
})();
