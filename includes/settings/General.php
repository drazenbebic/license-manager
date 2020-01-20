<?php

namespace LicenseManagerForWooCommerce\Settings;

defined('ABSPATH') || exit;

class General
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
        $this->settings = (array)get_option('lmfwc_settings_general');

        /**
         * @see https://developer.wordpress.org/reference/functions/register_setting/#parameters
         */
        $args = array();

        // Register the initial settings group.
        register_setting('lmfwc_settings_group_general', 'lmfwc_settings_general', $args);

        // Initialize the individual sections
        $this->initSectionLicenseKeys();
        $this->initSectionAPI();
    }

    private function initSectionLicenseKeys()
    {
        // Add the settings sections.
        add_settings_section(
            'license_keys_section',
            __('License keys', 'lmfwc'),
            null,
            'lmfwc_license_keys'
        );

        // lmfwc_security section fields.
        add_settings_field(
            'lmfwc_hide_license_keys',
            __('Obscure licenses', 'lmfwc'),
            array($this, 'fieldHideLicenseKeys'),
            'lmfwc_license_keys',
            'license_keys_section'
        );

        add_settings_field(
            'lmfwc_auto_delivery',
            __('Automatic delivery', 'lmfwc'),
            array($this, 'fieldAutoDelivery'),
            'lmfwc_license_keys',
            'license_keys_section'
        );
    }

    private function initSectionAPI()
    {
        // Add the settings sections.
        add_settings_section(
            'lmfwc_rest_api_section',
            __('REST API', 'lmfwc'),
            null,
            'lmfwc_rest_api'
        );

        add_settings_field(
            'lmfwc_disable_api_ssl',
            __('API & SSL', 'lmfwc'),
            array($this, 'fieldEnableApiOnNonSsl'),
            'lmfwc_rest_api',
            'lmfwc_rest_api_section'
        );

        add_settings_field(
            'lmfwc_enabled_api_routes',
            __('Enable/disable API routes', 'lmfwc'),
            array($this, 'fieldEnabledApiRoutes'),
            'lmfwc_rest_api',
            'lmfwc_rest_api_section'
        );
    }

    /**
     * Callback for the "hide_license_keys" field.
     */
    public function fieldHideLicenseKeys()
    {
        $field = 'lmfwc_hide_license_keys';
        (array_key_exists($field, $this->settings)) ? $value = true : $value = false;

        $html = '<fieldset>';
        $html .= sprintf('<label for="%s">', $field);
        $html .= sprintf(
            '<input id="%s" type="checkbox" name="lmfwc_settings_general[%s]" value="1" %s/>',
            $field,
            $field,
            checked(true, $value, false)
        );
        $html .= sprintf('<span>%s</span>', __('Hide license keys in the admin dashboard.', 'lmfwc'));
        $html .= '</label>';
        $html .= sprintf(
            '<p class="description">%s</p>',
            __('All license keys will be hidden and only displayed when the \'Show\' action is clicked.', 'lmfwc')
        );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Callback for the "lmfwc_auto_delivery" field.
     */
    public function fieldAutoDelivery()
    {
        $field = 'lmfwc_auto_delivery';
        (array_key_exists($field, $this->settings)) ? $value = true : $value = false;

        $html = '<fieldset>';
        $html .= sprintf('<label for="%s">', $field);
        $html .= sprintf(
            '<input id="%s" type="checkbox" name="lmfwc_settings_general[%s]" value="1" %s/>',
            $field,
            $field,
            checked(true, $value, false)
        );
        $html .= sprintf(
            '<span>%s</span>',
            __('Automatically send license keys when an order is set to \'Complete\'.', 'lmfwc')
        );
        $html .= '</label>';
        $html .= sprintf(
            '<p class="description">%s</p>',
            __('If this setting is off, you must manually send out all license keys for completed orders.', 'lmfwc')
        );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Callback for the "lmfwc_disable_api_ssl" field.
     */
    public function fieldEnableApiOnNonSsl()
    {
        $field = 'lmfwc_disable_api_ssl';
        (array_key_exists($field, $this->settings)) ? $value = true : $value = false;

        $html = '<fieldset>';
        $html .= sprintf('<label for="%s">', $field);
        $html .= sprintf(
            '<input id="%s" type="checkbox" name="lmfwc_settings_general[%s]" value="1" %s/>',
            $field,
            $field,
            checked(true, $value, false)
        );
        $html .= sprintf(
            '<span>%s</span>',
            __('Enable the plugin API routes over insecure HTTP connections.', 'lmfwc')
        );
        $html .= '</label>';
        $html .= sprintf(
            '<p class="description">%s</p>',
            __('This should only be activated for development purposes.', 'lmfwc')
        );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Callback for the "lmfwc_enabled_api_routes" field.
     */
    public function fieldEnabledApiRoutes()
    {
        $field = 'lmfwc_enabled_api_routes';
        $value = array();
        $routes = array(
            array(
                'id'         => '000',
                'name'       => 'v1/licenses',
                'method'     => 'GET',
                'deprecated' => true,
            ),
            array(
                'id'         => '001',
                'name'       => 'v1/licenses/{license_key OR id}',
                'method'     => 'GET',
                'deprecated' => true,
            ),
            array(
                'id'         => '002',
                'name'       => 'v1/licenses',
                'method'     => 'POST',
                'deprecated' => true,
            ),
            array(
                'id'         => '003',
                'name'       => 'v1/licenses/{license_key OR id}',
                'method'     => 'PUT',
                'deprecated' => true,
            ),
            array(
                'id'         => '004',
                'name'       => 'v1/licenses/activate/{license_key OR id}',
                'method'     => 'PUT',
                'deprecated' => true,
            ),
            array(
                'id'         => '005',
                'name'       => 'v1/licenses/validate/{license_key OR id}',
                'method'     => 'GET',
                'deprecated' => true,
            ),
            array(
                'id'         => '006',
                'name'       => 'v1/generators',
                'method'     => 'GET',
                'deprecated' => true,
            ),
            array(
                'id'         => '007',
                'name'       => 'v1/generators/{id}',
                'method'     => 'GET',
                'deprecated' => true,
            ),
            array(
                'id'         => '008',
                'name'       => 'v1/generators',
                'method'     => 'POST',
                'deprecated' => true,
            ),
            array(
                'id'         => '009',
                'name'       => 'v1/generators/{id}',
                'method'     => 'PUT',
                'deprecated' => true,
            ),
            array(
                'id'         => '010',
                'name'       => 'v2/licenses',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '011',
                'name'       => 'v2/licenses/{license_key}',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '012',
                'name'       => 'v2/licenses',
                'method'     => 'POST',
                'deprecated' => false,
            ),
            array(
                'id'         => '013',
                'name'       => 'v2/licenses/{license_key}',
                'method'     => 'PUT',
                'deprecated' => false,
            ),
            array(
                'id'         => '014',
                'name'       => 'v2/licenses/activate/{license_key}',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '015',
                'name'       => 'v2/licenses/deactivate/{license_key}',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '016',
                'name'       => 'v2/licenses/validate/{license_key}',
                'method'     => 'GET',
                'deprecated' => false,
            ),

            array(
                'id'         => '017',
                'name'       => 'v2/generators',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '018',
                'name'       => 'v2/generators/{id}',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '019',
                'name'       => 'v2/generators',
                'method'     => 'POST',
                'deprecated' => false,
            ),
            array(
                'id'         => '020',
                'name'       => 'v2/generators/{id}',
                'method'     => 'GET',
                'deprecated' => false,
            ),

            array(
                'id'         => '021',
                'name'       => 'v2/license-instances',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '022',
                'name'       => 'v2/license-instances/{license_key}/{instance_key}',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '023',
                'name'       => 'v2/license-instances/activate/{license_key}/{instance_key}',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '024',
                'name'       => 'v2/license-instances/deactivate/{license_key}/{instance_key}',
                'method'     => 'GET',
                'deprecated' => false,
            ),
            array(
                'id'         => '025',
                'name'       => 'v2/license-instances/validate/{license_key}/{instance_key}',
                'method'     => 'GET',
                'deprecated' => false,
            )
        );

        if (array_key_exists($field, $this->settings)) {
            $value = $this->settings[$field];
        }

        $html = '<fieldset>';

        foreach ($routes as $route) {
            $checked = false;

            if (array_key_exists($route['id'], $value) && $value[$route['id']] === '1') {
                $checked = true;
            }

            $html .= sprintf('<label for="%s-%s">', $field, $route['id']);
            $html .= sprintf(
                '<input id="%s-%s" type="checkbox" name="lmfwc_settings_general[%s][%s]" value="1" %s>',
                $field,
                $route['id'],
                $field,
                $route['id'],
                checked(true, $checked, false)
            );
            $html .= sprintf('<code><b>%s</b> - %s</code>', $route['method'], $route['name']);

            if (true === $route['deprecated']) {
                $html .= sprintf(
                    '<code class="text-info"><b>%s</b></code>',
                    strtoupper(__('Deprecated', 'lmfwc'))
                );
            }

            $html .= '</label>';
            $html .= '<br>';
        }

        $html .= sprintf(
            '<p class="description">%s %s</p>',
            __('Please note that the v1 routes are currently being deprecated. This means that, while they are still available to use, they will eventually be removed from the plugin. Please adjust any existing implementations to use the v2 routes.', 'lmfwc'),
            sprintf(
                __('The complete <b>API documentation</b> can be found <a href="%s" target="_blank" rel="noopener">here</a>.', 'lmfwc'),
                'https://documenter.getpostman.com/view/6103231/S1ETQGZ1?version=latest'
            )
        );
        $html .= '</fieldset>';

        echo $html;
    }
}