<?php
/**
 * Main plugin file.
 * PHP Version: 5.6
 * 
 * @category WordPress
 * @package  LicenseManagerForWooCommerce
 * @author   Dražen Bebić <drazen.bebic@outlook.com>
 * @license  GNUv3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     https://www.licensemanager.at/
 */

namespace LicenseManagerForWooCommerce;

use LicenseManagerForWooCommerce\Abstracts\Singleton;
use LicenseManagerForWooCommerce\Controllers\ApiKey as ApiKeyController;
use LicenseManagerForWooCommerce\Controllers\Generator as GeneratorController;
use LicenseManagerForWooCommerce\Controllers\License as LicenseController;

defined('ABSPATH') || exit;

/**
 * LicenseManagerForWooCommerce
 *
 * @category WordPress
 * @package  LicenseManagerForWooCommerce
 * @author   Dražen Bebić <drazen.bebic@outlook.com>
 * @license  GNUv3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version  Release: <2.1.2>
 * @link     https://www.licensemanager.at/
 */
final class Main extends Singleton
{
    /**
     * @var string
     */
    public $version = '2.1.2';

    /**
     * Main constructor.
     */
    public function __construct()
    {
        $this->_defineConstants();
        $this->_initHooks();

        add_action('init', array($this, 'init'));

        new API\Authentication();
    }

    /**
     * Define plugin constants.
     */
    private function _defineConstants()
    {
        if (!defined('ABSPATH_LENGTH')) {
            define('ABSPATH_LENGTH', strlen(ABSPATH));
        }

        define('LMFWC_VERSION',         $this->version);
        define('LMFWC_ABSPATH',         dirname(LMFWC_PLUGIN_FILE) . '/');
        define('LMFWC_PLUGIN_BASENAME', plugin_basename(LMFWC_PLUGIN_FILE));

        // Directories
        define('LMFWC_ASSETS_DIR',     LMFWC_ABSPATH       . 'assets/');
        define('LMFWC_LOG_DIR',        LMFWC_ABSPATH       . 'logs/');
        define('LMFWC_TEMPLATES_DIR',  LMFWC_ABSPATH       . 'templates/');
        define('LMFWC_MIGRATIONS_DIR', LMFWC_ABSPATH       . 'migrations/');
        define('LMFWC_CSS_DIR',        LMFWC_ASSETS_DIR    . 'css/');

        // URL's
        define('LMFWC_ASSETS_URL', LMFWC_PLUGIN_URL . 'assets/');
        define('LMFWC_ETC_URL',    LMFWC_ASSETS_URL . 'etc/');
        define('LMFWC_CSS_URL',    LMFWC_ASSETS_URL . 'css/');
        define('LMFWC_JS_URL',     LMFWC_ASSETS_URL . 'js/');
        define('LMFWC_IMG_URL',    LMFWC_ASSETS_URL . 'img/');
    }

    /**
     * Include JS and CSS files.
     */
    public function adminEnqueueScripts()
    {
        // Select2
        wp_register_style(
            'lmfwc_select2_cdn',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css'
        );
        wp_register_script(
            'lmfwc_select2_cdn',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js'
        );
        wp_register_style(
            'lmfwc_select2',
            LMFWC_CSS_URL . 'select2.css'
        );

        // CSS
        wp_enqueue_style('lmfwc_admin_css', LMFWC_CSS_URL . 'main.css');

        // JavaScript
        wp_enqueue_script('lmfwc_admin_js', LMFWC_JS_URL . 'script.js');

        // jQuery UI
        wp_register_style(
            'lmfwc-jquery-ui-datepicker',
            'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
            array(),
            '1.12.1'
        );

        if (isset($_GET['page']) && 
            (
                $_GET['page'] == AdminMenus::LICENSES_PAGE
                || $_GET['page'] == AdminMenus::GENERATORS_PAGE
                || $_GET['page'] == AdminMenus::SETTINGS_PAGE
            )
        ) {
            wp_enqueue_script('lmfwc_select2_cdn');
            wp_enqueue_style('lmfwc_select2_cdn');
            wp_enqueue_style('lmfwc_select2');
        }

        // Licenses page
        if (isset($_GET['page']) && $_GET['page'] == AdminMenus::LICENSES_PAGE) {
            wp_enqueue_script('lmfwc_licenses_page_js', LMFWC_JS_URL . 'licenses_page.js');

            wp_localize_script('lmfwc_licenses_page_js', 'i18n', array(
                'placeholderSearchOrders'   => __('Search by order ID or customer email', 'license-manager-for-woocommerce'),
                'placeholderSearchProducts' => __('Search by product ID or product name', 'license-manager-for-woocommerce')
            ));

            wp_localize_script('lmfwc_licenses_page_js', 'security', array(
                'dropdownSearch' => wp_create_nonce('lmfwc_dropdown_search')
            ));
        }

        // Generators page
        if (isset($_GET['page']) && $_GET['page'] == AdminMenus::GENERATORS_PAGE) {
            wp_enqueue_script('lmfwc_generators_page_js', LMFWC_JS_URL . 'generators_page.js');

            wp_localize_script('lmfwc_generators_page_js', 'i18n', array(
                'placeholderSearchOrders'   => __('Search by order ID or customer email', 'license-manager-for-woocommerce'),
                'placeholderSearchProducts' => __('Search by product ID or product name', 'license-manager-for-woocommerce')
            ));

            wp_localize_script('lmfwc_generators_page_js', 'security', array(
                'dropdownSearch' => wp_create_nonce('lmfwc_dropdown_search')
            ));
        }

        // Settings page
        if (isset($_GET['page']) && $_GET['page'] == AdminMenus::SETTINGS_PAGE) {
            wp_enqueue_script('lmfwc_settings_page_js', LMFWC_JS_URL . 'settings_page.js');
        }

        // Script localization
        wp_localize_script(
            'lmfwc_admin_js', 'license', array(
                'show'     => wp_create_nonce('lmfwc_show_license_key'),
                'show_all' => wp_create_nonce('lmfwc_show_all_license_keys'),
            )
        );
    }

    /**
     * Add additional links to the plugin row meta.
     * 
     * @param array  $links Array of already present links
     * @param string $file  File name
     * 
     * @return array
     */
    public function pluginRowMeta($links, $file)
    {
        if (strpos($file, 'license-manager-for-woocommerce.php') !== false ) {
            $newLinks = array(
                'github' => sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    'https://github.com/drazenbebic/license-manager',
                    'GitHub'
                ),
                'docs' => sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    'https://www.licensemanager.at/docs/',
                    __('Documentation', 'license-manager-for-woocommerce')
                ),
                'donate' => sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    'https://www.licensemanager.at/donate/',
                    __('Donate', 'license-manager-for-woocommerce')
                )
            );
            
            $links = array_merge($links, $newLinks);
        }

        return $links;
    }

    /**
     * Hook into actions and filters.
     */
    private function _initHooks()
    {
        register_activation_hook(
            LMFWC_PLUGIN_FILE,
            array('\LicenseManagerForWooCommerce\Setup', 'install')
        );
        register_deactivation_hook(
            LMFWC_PLUGIN_FILE,
            array('\LicenseManagerForWooCommerce\Setup', 'deactivate')
        );
        register_uninstall_hook(
            LMFWC_PLUGIN_FILE,
            array('\LicenseManagerForWooCommerce\Setup', 'uninstall')
        );

        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
        add_filter('plugin_row_meta', array($this, 'pluginRowMeta'), 10, 2);
    }

    /**
     * Init LicenseManagerForWooCommerce when WordPress Initialises.
     */
    public function init()
    {
        Setup::migrate();

        $this->publicHooks();

        new Crypto();
        new Import();
        new Export();
        new AdminMenus();
        new AdminNotice();
        new Generator();
        new Repositories\PostMeta();
        new Repositories\Users();
        new LicenseController();
        new GeneratorController();
        new ApiKeyController();
        new API\Setup();

        if ($this->isPluginActive('woocommerce/woocommerce.php')) {
            new Integrations\WooCommerce\Controller();
        }
    }

    /**
     * Defines all public hooks
     */
    protected function publicHooks()
    {
        add_filter(
            'lmfwc_license_keys_table_heading',
            function($text) {
                $default = __('Your license key(s)', 'license-manager-for-woocommerce');

                if (!$text) {
                    return $default;
                }

                return sanitize_text_field($text);
            },
            10,
            1
        );

        add_filter(
            'lmfwc_license_keys_table_valid_until',
            function($text) {
                $default = __('Valid until', 'license-manager-for-woocommerce');

                if (!$text) {
                    return $default;
                }

                return sanitize_text_field($text);
            },
            10,
            1
        );
    }

    /**
     * Checks if a plugin is active.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    private function isPluginActive($pluginName)
    {
        return in_array($pluginName, apply_filters('active_plugins', get_option('active_plugins')));
    }
}
