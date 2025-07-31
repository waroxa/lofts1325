<?php

function loft_booking_cleaning_calendar_page() {
    echo '<div class="wrap">';
    echo '<h2>Loft Cleaning Schedule</h2>';
    echo '<iframe src="https://calendar.google.com/calendar/embed?src=e964e301b54d0e795b44a76ebfb9d2cfbd2f6517a822429c5af62bc2cb94de20%40group.calendar.google.com&ctz=America%2FToronto" style="border:0" width="100%" height="600" frameborder="0" scrolling="no"></iframe>';
    echo '</div>';
}

function schedule_cleaning_task($summary, $start_time) {
    $calendar_id = get_option('loft_booking_cleaning_calendar_id');
    return create_google_event($summary, 'Scheduled cleaning after guest checkout.', date('c', strtotime($start_time)), date('c', strtotime($start_time . ' +1 hour')), $calendar_id);
}
