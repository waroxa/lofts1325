<?php

namespace MPHB\Notifier\Containers;

use MPHB\Notifier\Emails;
use MPHB\Emails\Templaters\EmailTemplater;

/**
 * @since 1.0
 */
class EmailsContainer
{
    protected $emailTemplater = null;

    /**
     * @return \MPHB\Emails\Templaters\EmailTemplater
     */
    public function templater()
    {
        if (is_null($this->emailTemplater)) {
            $this->emailTemplater = EmailTemplater::create([
                'global'            => true,
                'booking'           => true,
                'booking_details'   => true,
                'user_confirmation' => true,
                'user_cancellation' => true
            ]);

            $this->emailTemplater->setupTags();
        }

        return $this->emailTemplater;
    }

    /**
     * @param \MPHB\Notifier\Entities\Notification|int $notification
     * @param bool $testMode Optional. FALSE by default.
     * @return \MPHB\Notifier\Emails\NotificationEmail|null
     */
    public function notification($notification, $testMode = false)
    {
        if (is_int($notification)) {
            $notification = mphb_notifier_get_notification($notification);
        }

        if (!is_null($notification)) {
            $notificationEmail = new Emails\NotificationEmail(
                [
                    'id'           => $notification->getSlug(),
                    'notification' => $notification
                ],
                $this->templater()
            );

            $notificationEmail->initStrings();

            return $notificationEmail;
        } else {
            return null;
        }
    }
}
