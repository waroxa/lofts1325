<?php

namespace MPHB\Notifier\Containers;

use MPHB\Notifier\Repositories;
use MPHB\Persistences\CPTPersistence;

/**
 * @since 1.0
 */
class RepositoriesContainer
{
    protected $notificationRepository = null;

    /**
     * @return \MPHB\Notifier\Repositories\NotificationRepository
     */
    public function notification()
    {
        if (is_null($this->notificationRepository)) {
            $persistence = new CPTPersistence(mphb_notifier_get_notification_post_type());
            $this->notificationRepository = new Repositories\NotificationRepository($persistence);
        }

        return $this->notificationRepository;
    }
}
