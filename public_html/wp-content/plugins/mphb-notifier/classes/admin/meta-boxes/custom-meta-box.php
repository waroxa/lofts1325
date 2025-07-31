<?php

namespace MPHB\Notifier\Admin\MetaBoxes;

use MPHB\Admin\Groups\MetaBoxGroup;

/**
 * @since 1.0
 */
class CustomMetaBox extends MetaBoxGroup
{
    /**
     * @param string $name
     * @param string $label
     * @param string $postType
     * @param string $context Optional. The context within the screen where the
     *                        boxes should display. "normal", "side" or
     *                        "advanced"). "advanced" by default.
     * @param string $priority Optional. The priority within the context where
     *                         the boxes should show. "high", "default" or
     *                         "low". "default" by default.
     */
    public function __construct($name, $label, $postType, $context = 'advanced', $priority = 'default')
    {
        parent::__construct($name, $label, $postType, $context, $priority);

        $this->addFields($this->generateFields());

        // Register current instance of meta box in Hotel Booking - the plugin
        // will call the register() and save() methods
        add_action('mphb_edit_page_field_groups', array($this, 'registerThis'), 10, 2);
    }

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

    /**
     * @return array
     */
    protected function generateFields()
    {
        return [];
    }
}
