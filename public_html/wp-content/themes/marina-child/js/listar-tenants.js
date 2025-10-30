jQuery(function($) {
    'use strict';

    var settings = window.listarTenantsSettings || {};
    var ajaxUrl = settings.ajaxUrl || (window.ajaxurl && window.ajaxurl.ajax_url) || window.ajaxurl || '';
    var defaultBuildingId = settings.defaultBuildingId || 0;
    var i18n = $.extend(true, {
        loading: 'Loading tenants…',
        error: 'We could not load the tenant list. Please try again.',
        empty: 'No tenants were returned for this building.',
        retry: 'Retry',
        columns: {
            full_name: 'Name',
            email: 'Email',
            phone: 'Phone',
            unit_label: 'Unit',
            lease_start: 'Lease start',
            lease_end: 'Lease end'
        }
    }, settings.i18n || {});

    if (!ajaxUrl) {
        return;
    }

    function escapeHtml(value) {
        return $('<div />').text(value).html();
    }

    function normaliseTenants(payload) {
        if (!payload) {
            return [];
        }

        if (Array.isArray(payload)) {
            return payload;
        }

        if ($.isPlainObject(payload)) {
            if (Array.isArray(payload.tenants)) {
                return payload.tenants;
            }

            if (Array.isArray(payload.items)) {
                return payload.items;
            }

            if (Array.isArray(payload.data)) {
                return payload.data;
            }

            if (Object.keys(payload).length) {
                return [payload];
            }
        }

        return [];
    }

    $('.listar-tenants').each(function() {
        var $component = $(this);
        var $button = $component.find('.listar-tenants__button').first();
        var $results = $component.find('.listar-tenants__results').first();

        if (!$button.length || !$results.length) {
            return;
        }

        var originalButtonText = $button.text();
        var columnDefinitions = [
            { key: 'full_name', label: i18n.columns.full_name || 'Name' },
            { key: 'email', label: i18n.columns.email || 'Email' },
            { key: 'phone', label: i18n.columns.phone || 'Phone' },
            { key: 'unit_label', label: i18n.columns.unit_label || 'Unit' },
            { key: 'lease_start', label: i18n.columns.lease_start || 'Lease start' },
            { key: 'lease_end', label: i18n.columns.lease_end || 'Lease end' }
        ];

        function getBuildingId() {
            var buttonBuildingId = $button.data('building-id');
            var componentBuildingId = $component.data('building-id');

            return buttonBuildingId || componentBuildingId || defaultBuildingId || '';
        }

        function shouldAutoload() {
            var autoloadAttr = $button.data('autoload');

            if (typeof autoloadAttr === 'undefined') {
                autoloadAttr = $component.data('autoload');
            }

            if (typeof autoloadAttr === 'undefined') {
                return true;
            }

            if (typeof autoloadAttr === 'string') {
                autoloadAttr = autoloadAttr.toLowerCase();
                return autoloadAttr === 'true' || autoloadAttr === '1';
            }

            return Boolean(autoloadAttr);
        }

        function setLoading(isLoading) {
            if (isLoading) {
                $component.addClass('listar-tenants--loading');
                $button.data('original-text', originalButtonText);
                $button.prop('disabled', true).text(i18n.loading);
                $results.attr('aria-busy', 'true').html(
                    '<div class="listar-tenants__spinner" role="status">' +
                        '<span class="screen-reader-text">' + escapeHtml(i18n.loading) + '</span>' +
                        escapeHtml(i18n.loading) +
                    '</div>'
                );
            } else {
                $component.removeClass('listar-tenants--loading');
                $button.prop('disabled', false).text($button.data('original-text') || originalButtonText);
                $results.removeAttr('aria-busy');
            }
        }

        function renderTenants(payload) {
            $results.attr('aria-live', 'polite');

            if (typeof payload === 'string') {
                $results.html(payload);
                return;
            }

            var tenants = normaliseTenants(payload);

            if (!tenants.length) {
                $results.html('<p class="listar-tenants__empty">' + escapeHtml(i18n.empty) + '</p>');
                return;
            }

            var availableColumns = columnDefinitions.filter(function(column) {
                return tenants.some(function(tenant) {
                    return tenant[column.key];
                });
            });

            if (!availableColumns.length) {
                $results.html('<pre class="listar-tenants__raw">' + escapeHtml(JSON.stringify(payload, null, 2)) + '</pre>');
                return;
            }

            var tableHtml = '<div class="listar-tenants__table-wrapper"><table class="listar-tenants__table"><thead><tr>';

            availableColumns.forEach(function(column) {
                tableHtml += '<th scope="col">' + escapeHtml(column.label) + '</th>';
            });

            tableHtml += '</tr></thead><tbody>';

            tenants.forEach(function(tenant) {
                tableHtml += '<tr>';

                availableColumns.forEach(function(column) {
                    var value = tenant[column.key];

                    if (Array.isArray(value)) {
                        value = value.join(', ');
                    } else if ($.isPlainObject(value)) {
                        value = Object.values(value).join(', ');
                    }

                    if (typeof value === 'number') {
                        value = value.toString();
                    }

                    var displayValue = value ? escapeHtml(value) : '&mdash;';
                    tableHtml += '<td>' + displayValue + '</td>';
                });

                tableHtml += '</tr>';
            });

            tableHtml += '</tbody></table></div>';

            $results.html(tableHtml);
        }

        function handleError(message) {
            $results.attr('aria-live', 'assertive');

            var errorMessage = message ? escapeHtml(message) : escapeHtml(i18n.error);
            var retryButton = '<button type="button" class="listar-tenants__retry button button-secondary">' + escapeHtml(i18n.retry) + '</button>';

            $results.html(
                '<div class="listar-tenants__error" role="alert">' +
                    errorMessage +
                    retryButton +
                '</div>'
            );
        }

        function requestTenants() {
            var buildingId = getBuildingId();

            if (!ajaxUrl) {
                handleError('Missing AJAX endpoint.');
                return;
            }

            setLoading(true);

            $.ajax({
                url: ajaxUrl,
                method: 'GET',
                dataType: 'json',
                data: {
                    action: 'listar_tenants_building',
                    building_id: buildingId
                }
            })
                .done(function(response) {
                    if (response && response.success) {
                        renderTenants(response.data);
                    } else if (response && response.data) {
                        handleError(typeof response.data === 'string' ? response.data : i18n.error);
                    } else {
                        handleError();
                    }
                })
                .fail(function(jqXHR, textStatus) {
                    var responseJSON = jqXHR && jqXHR.responseJSON;
                    var message = responseJSON && responseJSON.data ? responseJSON.data : (textStatus || i18n.error);
                    handleError(message);
                })
                .always(function() {
                    setLoading(false);
                });
        }

        $results.on('click', '.listar-tenants__retry', function(event) {
            event.preventDefault();
            requestTenants();
        });

        $button.on('click', function(event) {
            event.preventDefault();
            requestTenants();
        });

        if (shouldAutoload()) {
            requestTenants();
        }
    });
});
