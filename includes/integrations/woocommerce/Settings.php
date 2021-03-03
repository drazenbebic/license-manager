<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerce;

defined('ABSPATH') || exit;

class Settings
{
    /**
     * Settings constructor.
     */
    public function __construct()
    {
        add_filter('woocommerce_get_settings_advanced', array($this, 'addEndpointAccountSettings'));
    }

    public function addEndpointAccountSettings($settings)
    {
        $licensesEndpointSetting = array(
            'title'    => __('Licenses', 'license-manager-for-woocommerce'),
            'desc'     => __('Endpoint for the "My Account &rarr; Licenses" page', 'license-manager-for-woocommerce'),
            'id'       => 'woocommerce_myaccount_licenses_endpoint',
            'type'     => 'text',
            'default'  => 'licenses',
            'desc_tip' => true
        );

        $this->insertSettingsAfter(
            $settings,
            'woocommerce_myaccount_view_order_endpoint',
            $licensesEndpointSetting
        );

        return $settings;
    }

    private function insertSettingsAfter(&$settings, $insertAfterSettingId, $newSetting, $insertType = 'single_setting')
    {
        if (!is_array($settings)) {
            return;
        }

        $originalSettings = $settings;
        $settings         = array();

        foreach ($originalSettings as $setting) {
            $settings[] = $setting;

            if (isset($setting['id']) && $insertAfterSettingId === $setting['id']) {
                if ('single_setting' === $insertType) {
                    $settings[] = $newSetting;
                } else {
                    $settings = array_merge($settings, $newSetting);
                }
            }
        }
    }
}