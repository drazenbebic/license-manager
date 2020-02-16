<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerce;

defined('ABSPATH') || exit;

class MyAccount
{
    /**
     * MyAccount constructor.
     */
    public function __construct()
    {
        add_rewrite_endpoint('view-license-keys', EP_ROOT | EP_PAGES);

        add_filter('woocommerce_account_menu_items',                 array($this, 'accountMenuItems'), 10, 1);
        add_action('woocommerce_account_view-license-keys_endpoint', array($this, 'viewLicenseKeys'));
    }

    /**
     * Adds the plugin pages to the "My account" section.
     *
     * @param array $items
     *
     * @return array
     */
    public function accountMenuItems($items)
    {
        $items['view-license-keys'] = __('License keys', 'license-manager-for-woocommerce');

        return $items;
    }

    /**
     * Creates an overview of all purchased license keys.
     */
    public function viewLicenseKeys()
    {
        $user = wp_get_current_user();

        if (!$user) {
            return;
        }

        wp_enqueue_style('lmfwc_admin_css', LMFWC_CSS_URL . 'main.css');

        global $wp_query;

        $page = 1;

        if ($wp_query->query['view-license-keys']) {
            $page = intval($wp_query->query['view-license-keys']);
        }

        $licenseKeys = apply_filters('lmfwc_get_all_customer_license_keys', $user->ID);

        echo wc_get_template_html(
            'myaccount/lmfwc-view-license-keys.php',
            array(
                'dateFormat'  => get_option('date_format'),
                'licenseKeys' => $licenseKeys,
                'page'        => $page
            ),
            '',
            LMFWC_TEMPLATES_DIR
        );
    }
}