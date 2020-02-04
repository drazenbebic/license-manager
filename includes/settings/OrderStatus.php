<?php

namespace LicenseManagerForWooCommerce\Settings;

defined('ABSPATH') || exit;

class OrderStatus
{
    /**
     * @var array
     */
    private $settings;

    /**
     * General constructor.
     */
    public function __construct()
    {
        $this->settings = get_option('lmfwc_settings_order_status', array());

        /**
         * @see https://developer.wordpress.org/reference/functions/register_setting/#parameters
         */
        $args = array(
            'sanitize_callback' => array($this, 'sanitize')
        );

        // Register the initial settings group.
        register_setting('lmfwc_settings_group_order_status', 'lmfwc_settings_order_status', $args);

        // Initialize the individual sections
        $this->initSectionsLicenseDelivery();
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    public function sanitize($settings)
    {
        if ($settings === null) {
            return array();
        }

        return $settings;
    }

    private function initSectionsLicenseDelivery()
    {
        add_settings_section(
            'license_key_delivery_section',
            __('License key delivery', 'lmfwc'),
            null,
            'lmfwc_license_key_delivery'
        );

        add_settings_field(
            'lmfwc_license_key_delivery_options',
            __('Define license key delivery', 'lmfwc'),
            array($this, 'fieldLicenseKeyDeliveryOptions'),
            'lmfwc_license_key_delivery',
            'license_key_delivery_section'
        );
    }

    public function fieldLicenseKeyDeliveryOptions($foo)
    {
        $field = 'lmfwc_license_key_delivery_options';
        $html  = '';

        $html .= '<table class="wp-list-table widefat fixed striped posts">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<td><strong>' . __('Order status', 'lmfwc') .'</strong></td>';
        $html .= '<td><strong>' . __('Send', 'lmfwc') .'</strong></td>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        foreach (wc_get_order_statuses() as $slug => $name) {
            $send   = false;

            if (array_key_exists($field, $this->settings) && array_key_exists($slug, $this->settings[$field])) {
                if (array_key_exists('send', $this->settings[$field][$slug]) && $this->settings[$field][$slug]) {
                    $send = true;
                }
            }

            $html .= '<tr>';
            $html .= '<td>' . $name . '</td>';

            $html .= '<td>';
            $html .= sprintf(
                '<input type="checkbox" name="lmfwc_settings_order_status[%s][%s][send]" value="1" %s>',
                $field,
                $slug,
                $send ? 'checked="checked"' : ''
            );
            $html .= '</td>';


            $html .= '</tr>';
        }

        $html .= '</tbody>';

        $html .= '</table>';

        echo $html;
    }
}