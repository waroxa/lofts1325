document.addEventListener("DOMContentLoaded", function () {
    // Manejo del formulario de reservas (lo que ya tenías)
    const bookingForm = document.getElementById("loft-booking-form");
    if (bookingForm) {
        bookingForm.addEventListener("submit", function (event) {
            event.preventDefault();

            var formData = new FormData(this);

            // AJAX request to handle booking submission
            fetch(ajax_object.ajax_url + "?action=wp_loft_booking_submit", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then((response) => response.json())
                .then((data) => {
                    var messageDiv = document.getElementById("booking-message");
                    if (data.success) {
                        messageDiv.innerHTML = '<span style="color: green;">' + data.data + "</span>";
                        document.getElementById("loft-booking-form").reset();
                    } else {
                        messageDiv.innerHTML = '<span style="color: red;">' + data.data + "</span>";
                    }
                })
                .catch((error) => {
                    document.getElementById("booking-message").innerHTML = '<span style="color: red;">Failed to submit booking.</span>';
                });
        });
    }

    // Manejo del botón "Sync Tenants"
    const syncButton = document.getElementById("sync-tenants-button");
    if (syncButton) {
        syncButton.addEventListener("click", function() {
            syncButton.disabled = true; // Desactiva el botón mientras se sincroniza
            syncButton.textContent = "Syncing..."; // Cambia el texto del botón

            // Solicitud AJAX para sincronizar inquilinos
            fetch(ajax_object.ajax_url + "?action=wp_loft_booking_sync_tenants", {
                method: "POST",
                credentials: "same-origin"
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert("¡Inquilinos sincronizados con éxito!");
                        location.reload(); // Recarga la página para mostrar los inquilinos actualizados
                    } else {
                        alert("Error al sincronizar inquilinos: " + (data.data || "Desconocido"));
                        syncButton.disabled = false;
                        syncButton.textContent = "Sync Tenants";
                    }
                })
                .catch((error) => {
                    alert("Error en la solicitud AJAX: " + error.message);
                    syncButton.disabled = false;
                    syncButton.textContent = "Sync Tenants";
                });
        });
    }
});