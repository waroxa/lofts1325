jQuery(document).ready(function($) {
    console.log('Documento listo');

    $('#listarTenantsBtn').click(function() {
        console.log('Boton clicado');

        $.ajax({
            url: ajaxurl.ajax_url,
            type: 'GET',
            data: {
                action: 'listar_tenants_building',
                building_id: 60892
            },
            success: function(response) {
                if (response.success) {
                    console.log('Respuesta exitosa:', response.data);
                    $('#resultadoTenants').html('<pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                } else {
                    console.log('Error en la respuesta:', response.data);
                    $('#resultadoTenants').html('<p>Error en la respuesta: ' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log('Error en la solicitud AJAX:', error);
                $('#resultadoTenants').html('<p>Error en la solicitud AJAX: ' + error + '</p>');
            }
        });
    });
});
