<?php

namespace MPHB\Notifier\Admin\MetaBoxes;

use MPHB\Notifier\Utils\BookingUtils;

class TestMetaBox extends CustomMetaBox
{
    /**
     * @param \MPHB\Admin\Groups\MetaBoxGroup[] $metaBoxes
     * @param string $postType
     * @return \MPHB\Admin\Groups\MetaBoxGroup[]
     */
    public function registerThis($metaBoxes, $postType)
    {
        if ($postType == $this->postType) {
            $metaBoxes[] = $this;
        }

        return $metaBoxes;
    }

    public function render()
    {
        parent::render();

        echo '<p class="mphb-notifier-test-email-description">';
            echo esc_html__('Save and send this notification to the administrator email address. To test custom notices, make sure you’ve added Accommodation Notice 1/Notice 2 in the Accommodation type menu.', 'mphb-notifier');
        echo '</p>';

        echo '<p class="mphb-notifier-test-email-submit">';
            echo '<input name="send_notification" type="submit" class="button button-secondary button-large" value="' . esc_attr__('Send Email', 'mphb-notifier') . '" />';
        echo '</p>';
    }

    public function save()
    {    
        parent::save();

        // Maybe send this notification to the admin (as a test)
        if (isset($_POST['send_notification']) && $this->isValidRequest()) {
            $email = mphb_notifier()->emails()->notification($this->getEditingPostId(), true);

            if (!is_null($email)) {
                $testBooking = BookingUtils::getTestBooking();
                $email->trigger($testBooking, ['test_mode' => true]);
            }
        }
    }

    protected function getEditingPostId()
    {
        $postId = 0;

        if (isset($_REQUEST['post_ID']) && is_numeric($_REQUEST['post_ID'])) {
            $postId = intval($_REQUEST['post_ID']); // On post update ($_POST)

        } else if (isset($_REQUEST['post']) && is_numeric($_REQUEST['post'])) {
            $postId = intval($_REQUEST['post']); // On post edit page ($_GET)
        }

        return $postId;
    }
}
