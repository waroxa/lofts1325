document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("loft-booking-form").addEventListener("submit", function (event) {
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
});


