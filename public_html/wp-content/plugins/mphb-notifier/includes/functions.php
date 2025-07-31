<?php

/**
 * @return \MPHB\Notifier\Plugin
 *
 * @since 1.0
 */
function mphb_notifier()
{
    return \MPHB\Notifier\Plugin::getInstance();
}

/**
 * @return string
 *
 * @since 1.0
 */
function mphb_notifier_get_notification_post_type()
{
    return mphb_notifier()->postTypes()->notification()->getPostType();
}

/**
 * @return array
 *
 * @since 1.0
 */
function mphb_notifier_get_notification_types()
{
    return apply_filters('mphb_notification_types', [
        'email' => esc_html__('Email', 'mphb-notifier')
    ]);
}

/**
 * @param int $notificationId
 * @return \MPHB\Notifier\Entities\Notification|null
 *
 * @since 1.0
 */
function mphb_notifier_get_notification($notificationId)
{
    return mphb_notifier()->repositories()->notification()->findById($notificationId);
}

/**
 * @param \MPHB\Entities\Booking|int $booking Entity object or its ID.
 * @param \MPHB\Notifier\Entities\Notification|int $notification Entity
 *     object or its ID.
 * @return bool
 *
 * @since 1.0
 */
function mphb_notifier_trigger_emails($booking, $notification)
{
    if (is_int($booking)) {
        $booking = mphb()->getBookingRepository()->findById($booking);
    }

    if (is_int($notification)) {
        $notification = mphb_notifier_get_notification($notification);
    }

    $emails = !is_null($notification) ? mphb_notifier()->emails()->notification($notification) : null;

    if (is_null($booking) || is_null($emails)) {
        return false;
    }

    $sent = $emails->trigger($booking);

    // Mark notification as sent for this booking
    if ($sent) {
        //add_post_meta($booking->getId(), '_mphb_notification_sent', $notification->id);
        add_post_meta($booking->getId(), '_mphb_notification_sent', $notification->originalId);
    }

    return $sent;
}

/**
 * @param int $period
 * @param string $compare before|after
 * @return \DateTime[]
 *
 * @since 1.0
 */
function mphb_notifier_get_trigger_dates($period, $compare)
{
    switch ($compare) {
        case 'before':
            $offset = "+{$period} days"; // "+2 days" for "2 days before check-in"
            $bound  = '+' . ($period - 1) . ' day'; // "+1 day"
            break;

        case 'after':
            $offset = "-{$period} day"; // "-1 day" for "1 day after check-out"
            $bound  = '-' . ($period + 1) . ' days'; // "-2 days"
            break;
    }

    $fromDate = new \DateTime($bound);
    $toDate   = new \DateTime($offset);

    return ['from' => $fromDate, 'to' => $toDate];
}

/**
 * @return array
 *
 * @since 1.0
 */
function mphb_notifier_get_trigger_comparisons()
{
    return apply_filters('mphb_notification_trigger_comparisons', [
        'before' => esc_html_x('before', 'Before some day', 'mphb-notifier'),
        'after'  => esc_html_x('after', 'After some day', 'mphb-notifier')
    ]);
}

/**
 * @return array
 *
 * @since 1.0
 */
function mphb_notifier_get_trigger_fields()
{
    return apply_filters('mphb_notification_trigger_fields', [
        'check-in'  => esc_html__('check-in', 'mphb-notifier'),
        'check-out' => esc_html__('check-out', 'mphb-notifier')
    ]);
}

/**
 * @param array $trigger [period, operator, date]
 * @return string String like "2 days before check-in".
 *
 * @since 1.0
 */
function mphb_notifier_convert_trigger_to_text($trigger)
{
    $days = esc_html(_n('day', 'days', $trigger['period'], 'mphb-notifier'));

    $comparisons = mphb_notifier_get_trigger_comparisons();
    $compare = array_key_exists($trigger['compare'], $comparisons) ? $comparisons[$trigger['compare']] : '';

    $fields = mphb_notifier_get_trigger_fields();
    $field = array_key_exists($trigger['field'], $fields) ? $fields[$trigger['field']] : '';

    return sprintf('%d %s %s %s', $trigger['period'], $days, $compare, $field);
}

/**
 * @return bool
 *
 * @since 1.0
 */
function mphb_notifier_use_edd_license()
{
    return (bool)apply_filters('mphb_notifier_use_edd_license', true);
}

if (!function_exists('wp_doing_ajax')) {
    /**
     * @return bool
     *
     * @since 1.0
     * @since WordPress 4.7
     */
    function wp_doing_ajax()
    {
        return apply_filters('wp_doing_ajax', defined('DOING_AJAX') && DOING_AJAX);
    }
}
