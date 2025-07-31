<?php

namespace MPHBR\Admin;

use MPHBR\Admin\Groups\LicenseSettingsGroup;
use MPHB\Admin\Fields\FieldFactory;
use MPHB\Admin\Groups\SettingsGroup;
use MPHB\Admin\Tabs\SettingsSubTab;

class ExtensionSettings
{
    public function __construct()
    {
        add_action('mphb_generate_extension_settings', [$this, 'registerSettings'], 10, 1);
    }

    /**
     * @param \MPHB\Admin\Tabs\SettingsTab $tab
     */
    public function registerSettings($tab)
    {
        $subtab = new SettingsSubTab('reviews',
            esc_html__('Reviews', 'mphb-reviews'), $tab->getPageName(), $tab->getName());

        $mainGroup = new SettingsGroup('mphbr_main', '', $subtab->getOptionGroupName());
        $mainFields = [
            FieldFactory::create('mphbr_text_limit', [
                'type'        => 'number',
                'label'       => esc_html__('Review Length', 'mphb-reviews'),
                'description' => esc_html__('The number of letters displayed before the Read More link.', 'mphb-reviews'),
                'default'     => 180,
                'min'         => 0
            ])
        ];
        $mainGroup->addFields($mainFields);
        $subtab->addGroup($mainGroup);

        if (MPHBR()->getSettings()->license()->isEnabled()) {
            $licenseGroup = new LicenseSettingsGroup('mphbr_license',
                esc_html__('License', 'mphb-reviews'), $subtab->getOptionGroupName());

            $subtab->addGroup($licenseGroup);
        }

        $tab->addSubTab($subtab);
    }
}
