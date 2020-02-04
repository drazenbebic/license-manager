<?php

namespace LicenseManagerForWooCommerce;

defined('ABSPATH') || exit;

class Settings
{
    /**
     * @var string
     */
    const SECTION_GENERAL = 'lmfwc_settings_general';

    /**
     * @var string
     */
    const SECTION_ORDER_STATUS = 'lmfwc_settings_order_status';

    /**
     * Settings Constructor.
     */
    public function __construct()
    {
        // Initialize the settings classes
        new Settings\General();
        new Settings\OrderStatus();
    }

    /**
     * Helper function to get a setting by name.
     *
     * @param string $field
     * @param string $section
     *
     * @return bool|mixed
     */
    public static function get($field, $section = self::SECTION_GENERAL)
    {
        $settings = get_option($section, array());
        $value    = false;

        if (array_key_exists($field, $settings)) {
            $value = $settings[$field];
        }

        return $value;
    }
}