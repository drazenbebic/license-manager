<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerce;

use Exception;
use LicenseManagerForWooCommerce\Settings;

defined('ABSPATH') || exit;

class MyAccount
{
    /**
     * @var array
     */
    protected $queryVars;

    /**
     * MyAccount constructor.
     */
    public function __construct()
    {
        $this->queryVars = array(
            'licenses' => get_option('woocommerce_myaccount_licenses_endpoint', 'licenses')
        );

        add_filter('woocommerce_get_query_vars',            array($this, 'initQueryVars'),    10, 1);
        add_filter('woocommerce_account_menu_items',        array($this, 'licenseMenuItem'),  10, 1);
        add_action('woocommerce_account_licenses_endpoint', array($this, 'licensesEndpoint'), 10, 1);
        add_action('woocommerce_endpoint_licenses_title',   array($this, 'licensesTitle'),    10, 2);
    }

    /**
     * Initialize the plugin's query parameters.
     *
     * @param array $queryVars
     * @return array
     */
    public function initQueryVars($queryVars)
    {
        return array_merge($queryVars, $this->queryVars);
    }

    /**
     * Adds the plugin pages to the "My account" section.
     *
     * @param array $menuItems
     * @return array
     */
    public function licenseMenuItem($menuItems)
    {
        // If the Licenses endpoint setting is empty, don't display it in line with core WC behaviour.
        if (empty($this->queryVars['licenses'])) {
            return $menuItems;
        }

        $label = apply_filters(
            'lmfwc_myaccount_licenses_page_title',
            __('Licenses', 'license-manager-for-woocommerce')
        );

        // Add our menu item after the Orders tab if it exists, otherwise just add it to the end
        if (array_key_exists('orders', $menuItems)) {
            $menuItems = lmfwc_array_insert_after('orders', $menuItems, 'licenses', $label);
        } else {
            $menuItems['licenses'] = $label;
        }

        return $menuItems;
    }

    /**
     * Creates an overview of all purchased license keys.
     *
     * @param int $page
     */
    public function licensesEndpoint($page)
    {
        $user = wp_get_current_user();

        if (!$user) {
            return;
        }

        if (array_key_exists('action', $_POST)) {
            $licenseKey = sanitize_text_field($_POST['license']);

            if ($_POST['action'] === 'activate' && Settings::get('lmfwc_allow_users_to_activate')) {
                $nonce = wp_verify_nonce($_POST['_wpnonce'], 'lmfwc_myaccount_activate_license');

                if ($nonce) {
                    try {
                        lmfwc_activate_license($licenseKey);
                    } catch (Exception $e) {
                    }
                }
            }

            if ($_POST['action'] === 'deactivate' && Settings::get('lmfwc_allow_users_to_deactivate')) {
                $nonce = wp_verify_nonce($_POST['_wpnonce'],'lmfwc_myaccount_deactivate_license');

                if ($nonce) {
                    try {
                        lmfwc_deactivate_license($licenseKey);
                    } catch (Exception $e) {
                    }
                }
            }
        }

        wp_enqueue_style('lmfwc_admin_css', LMFWC_CSS_URL . 'main.css');

        echo wc_get_template_html(
            'myaccount/lmfwc-licenses.php',
            array(
                'date_format'       => get_option('date_format'),
                'customer_licenses' => apply_filters('lmfwc_get_all_customer_license_keys', $user->ID),
                'page'              => (int)$page,
                'can_activate'      => Settings::get('lmfwc_allow_users_to_activate'),
                'can_deactivate'    => Settings::get('lmfwc_allow_users_to_deactivate'),
            ),
            '',
            LMFWC_TEMPLATES_DIR
        );
    }

    /**
     * Sets the page title for the "My account -> Licenses" page.
     *
     * @param string $title
     * @param string $endpoint
     * @return string
     */
    public function licensesTitle($title, $endpoint)
    {
        if ($endpoint === 'licenses') {
            $title = __('Licenses', 'license-manager-for-woocommerce');
        }

        return $title;
    }
}
