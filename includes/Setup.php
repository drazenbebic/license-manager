<?php

namespace LicenseManagerForWooCommerce;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key as DefuseCryptoKey;
use Exception;
use WP_Roles;
use function dbDelta;

defined('ABSPATH') || exit;

class Setup
{
    /**
     * @var int
     */
    const DB_VERSION = 109;

    /**
     * @var string
     */
    const LICENSES_TABLE_NAME = 'lmfwc_licenses';

    /**
     * @var string
     */
    const GENERATORS_TABLE_NAME = 'lmfwc_generators';

    /**
     * @var string
     */
    const API_KEYS_TABLE_NAME = 'lmfwc_api_keys';

    /**
     * @var string
     */
    const LICENSE_META_TABLE_NAME = 'lmfwc_licenses_meta';

    /**
     * Installation script.
     *
     * @throws EnvironmentIsBrokenException
     * @throws Exception
     */
    public static function install()
    {
        self::checkRequirements();
        self::createTables();
        self::setDefaultFilesAndFolders();
        self::setDefaultSettings();
        self::createRoles();
    }

    /**
     * Deactivation script.
     */
    public static function deactivate()
    {
        // Nothing for now...
    }

    /**
     * Uninstall script.
     */
    public static function uninstall()
    {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . self::LICENSES_TABLE_NAME,
            $wpdb->prefix . self::GENERATORS_TABLE_NAME,
            $wpdb->prefix . self::API_KEYS_TABLE_NAME,
            $wpdb->prefix . self::LICENSE_META_TABLE_NAME
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        self::removeRoles();

        foreach (self::getDefaultSettings() as $group => $setting) {
            delete_option($group);
        }

        // After cleanup remove version reference
        delete_option('lmfwc_db_version');
    }

    /**
     * Migration script.
     */
    public static function migrate()
    {
        $currentDatabaseVersion = get_option('lmfwc_db_version');

        if ($currentDatabaseVersion != self::DB_VERSION) {
            if ($currentDatabaseVersion < self::DB_VERSION) {
                Migration::up($currentDatabaseVersion);
            }

            if ($currentDatabaseVersion > self::DB_VERSION) {
                Migration::down($currentDatabaseVersion);
            }
        }
    }

    /**
     * Checks if all required plugin components are present.
     *
     * @throws Exception
     */
    public static function checkRequirements()
    {
        if (version_compare(phpversion(), '5.3.29', '<=')) {
            throw new Exception('PHP 5.3 or lower detected. License Manager for WooCommerce requires PHP 5.6 or greater.');
        }
    }

    /**
     * Create the necessary database tables.
     */
    public static function createTables()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table1 = $wpdb->prefix . self::LICENSES_TABLE_NAME;
        $table2 = $wpdb->prefix . self::GENERATORS_TABLE_NAME;
        $table3 = $wpdb->prefix . self::API_KEYS_TABLE_NAME;
        $table4 = $wpdb->prefix . self::LICENSE_META_TABLE_NAME;

        dbDelta("
            CREATE TABLE IF NOT EXISTS $table1 (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `order_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `product_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `user_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `license_key` LONGTEXT NOT NULL,
                `hash` LONGTEXT NOT NULL,
                `expires_at` DATETIME NULL DEFAULT NULL,
                `valid_for` INT(32) UNSIGNED NULL DEFAULT NULL,
                `source` VARCHAR(255) NOT NULL,
                `status` TINYINT(1) UNSIGNED NOT NULL,
                `times_activated` INT(10) UNSIGNED NULL DEFAULT NULL,
                `times_activated_max` INT(10) UNSIGNED NULL DEFAULT NULL,
                `created_at` DATETIME NULL,
                `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `updated_at` DATETIME NULL DEFAULT NULL,
                `updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        dbDelta("
            CREATE TABLE IF NOT EXISTS $table2 (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `charset` VARCHAR(255) NOT NULL,
                `chunks` INT(10) UNSIGNED NOT NULL,
                `chunk_length` INT(10) UNSIGNED NOT NULL,
                `times_activated_max` INT(10) UNSIGNED NULL DEFAULT NULL,
                `separator` VARCHAR(255) NULL DEFAULT NULL,
                `prefix` VARCHAR(255) NULL DEFAULT NULL,
                `suffix` VARCHAR(255) NULL DEFAULT NULL,
                `expires_in` INT(10) UNSIGNED NULL DEFAULT NULL,
                `created_at` DATETIME NULL,
                `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `updated_at` DATETIME NULL DEFAULT NULL,
                `updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        dbDelta("
            CREATE TABLE IF NOT EXISTS $table3 (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` BIGINT(20) UNSIGNED NOT NULL,
                `description` VARCHAR(200) NULL DEFAULT NULL,
                `permissions` VARCHAR(10) NOT NULL,
                `consumer_key` CHAR(64) NOT NULL,
                `consumer_secret` CHAR(43) NOT NULL,
                `nonces` LONGTEXT NULL,
                `truncated_key` CHAR(7) NOT NULL,
                `last_access` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NULL,
                `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `updated_at` DATETIME NULL DEFAULT NULL,
                `updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `consumer_key` (`consumer_key`),
                INDEX `consumer_secret` (`consumer_secret`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        dbDelta("
            CREATE TABLE IF NOT EXISTS $table4 (
                `meta_id` BIGINT(20) UNSIGNED AUTO_INCREMENT,
                `license_id` BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
                `meta_key` VARCHAR(255) NULL,
                `meta_value` LONGTEXT NULL,
                `created_at` DATETIME NULL,
                `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                `updated_at` DATETIME NULL DEFAULT NULL,
                `updated_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (`meta_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ");
    }

    /**
     * Sets up the default folder structure and creates the default files,
     * if needed.
     *
     * @throws EnvironmentIsBrokenException
     */
    public static function setDefaultFilesAndFolders()
    {
        /**
         * When the cryptographic secrets are loaded into these constants,
         * no other files are needed.
         *
         * @see https://www.licensemanager.at/docs/handbook/setup/security/
         */
        if (defined('LMFWC_PLUGIN_SECRET') && defined('LMFWC_PLUGIN_DEFUSE')) {
            return;
        }

        $uploads      = wp_upload_dir(null, false);
        $dirLmfwc     = $uploads['basedir'] . '/lmfwc-files';
        $fileHtaccess = $dirLmfwc . '/.htaccess';
        $fileDefuse   = $dirLmfwc . '/defuse.txt';
        $fileSecret   = $dirLmfwc . '/secret.txt';

        $oldUmask = umask(0);

        // wp-contents/lmfwc-files/
        if (!file_exists($dirLmfwc)) {
            @mkdir($dirLmfwc, 0775, true);
        } else {
            $permsDirLmfwc = substr(sprintf('%o', fileperms($dirLmfwc)), -4);

            if ($permsDirLmfwc != '0775') {
                @chmod($permsDirLmfwc, 0775);
            }
        }

        // wp-contents/lmfwc-files/.htaccess
        if (!file_exists($fileHtaccess)) {
            $fileHandle = @fopen($fileHtaccess, 'w');

            if ($fileHandle) {
                fwrite($fileHandle, 'deny from all');
                fclose($fileHandle);
            }

            @chmod($fileHtaccess, 0664);
        } else {
            $permsFileHtaccess = substr(sprintf('%o', fileperms($fileHtaccess)), -4);

            if ($permsFileHtaccess != '0664') {
                @chmod($permsFileHtaccess, 0664);
            }
        }

        // wp-contents/lmfwc-files/defuse.txt
        if (!file_exists($fileDefuse)) {
            $defuse = DefuseCryptoKey::createNewRandomKey();
            $fileHandle = @fopen($fileDefuse, 'w');

            if ($fileHandle) {
                fwrite($fileHandle, $defuse->saveToAsciiSafeString());
                fclose($fileHandle);
            }

            @chmod($fileDefuse, 0664);
        } else {
            $permsFileDefuse = substr(sprintf('%o', fileperms($fileDefuse)), -4);

            if ($permsFileDefuse != '0664') {
                @chmod($permsFileDefuse, 0664);
            }
        }

        // wp-contents/lmfwc-files/secret.txt
        if (!file_exists($fileSecret)) {
            $fileHandle = @fopen($fileSecret, 'w');

            if ($fileHandle) {
                fwrite($fileHandle, bin2hex(openssl_random_pseudo_bytes(32)));
                fclose($fileHandle);
            }

            @chmod($fileSecret, 0664);
        } else {
            $permsFileSecret = substr(sprintf('%o', fileperms($fileSecret)), -4);

            if ($permsFileSecret != '0664') {
                @chmod($permsFileSecret, 0664);
            }
        }

        umask($oldUmask);
    }

    /**
     * Set the default plugin options.
     */
    public static function setDefaultSettings()
    {
        // Only update user settings if they don't exist already
        foreach (self::getDefaultSettings() as $group => $setting) {
            if (!get_option($group, false)) {
                update_option($group, $setting);
            }
        }

        // Database version is always updated
        update_option('lmfwc_db_version', self::DB_VERSION);
    }

    /**
     * Returns an associative array of the default plugin settings. The key
     * represents the setting group, and the value the individual settings
     * fields with their corresponding values.
     *
     * @return array
     */
    public static function getDefaultSettings()
    {
        return array(
            'lmfwc_settings_general' => array(
                'lmfwc_hide_license_keys'         => '1',
                'lmfwc_auto_delivery'             => '1',
                'lmfwc_product_downloads'         => '1',
                'lmfwc_download_expires'          => '1',
                'lmfwc_allow_duplicates'          => '0',
                'lmfwc_enable_stock_manager'      => '0',
                'lmfwc_allow_users_to_activate'   => '0',
                'lmfwc_allow_users_to_deactivate' => '0',
                'lmfwc_disable_api_ssl'           => '0',
                'lmfwc_enabled_api_routes'        => array(
                    '010' => '1',
                    '011' => '1',
                    '012' => '1',
                    '013' => '1',
                    '014' => '1',
                    '015' => '1',
                    '016' => '1',
                    '017' => '1',
                    '018' => '1',
                    '019' => '1',
                    '020' => '1',
                    '021' => '1',
                    '022' => '1',
                    '023' => '1'
                )
            ),
            'lmfwc_settings_order_status' => array(
                'lmfwc_license_key_delivery_options' => array(
                    'wc-completed' => array(
                        'send' => '1'
                    )
                )
            ),
            'lmfwc_settings_tools' => array(
                'lmfwc_csv_export_columns' => array(
                    'id'                  => '1',
                    'order_id'            => '1',
                    'product_id'          => '1',
                    'user_id'             => '1',
                    'license_key'         => '1',
                    'expires_at'          => '1',
                    'valid_for'           => '1',
                    'status'              => '1',
                    'times_activated'     => '1',
                    'times_activated_max' => '1',
                    'created_at'          => '1',
                    'created_by'          => '1',
                    'updated_at'          => '1',
                    'updated_by'          => '1',
                )
            ),
            'woocommerce_myaccount_licenses_endpoint' => 'licenses'
        );
    }

    /**
     * Add License Manager for WooCommerce roles.
     */
    public static function createRoles()
    {
        global $wp_roles;

        if (!class_exists('\WP_Roles')) {
            return;
        }

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        // Dummy gettext calls to get strings in the catalog.
        /* translators: user role */
        _x('Licensing agent', 'User role', 'license-manager-for-woocommerce');
        /* translators: user role */
        _x('License manager', 'User role', 'license-manager-for-woocommerce');

        // Licensing agent role.
        add_role(
            'licensing_agent',
            'Licensing agent',
            array()
        );

        // Shop manager role.
        add_role(
            'license_manager',
            'License manager',
            array(
                'level_9'                => true,
                'level_8'                => true,
                'level_7'                => true,
                'level_6'                => true,
                'level_5'                => true,
                'level_4'                => true,
                'level_3'                => true,
                'level_2'                => true,
                'level_1'                => true,
                'level_0'                => true,
                'read'                   => true,
                'read_private_pages'     => true,
                'read_private_posts'     => true,
                'edit_posts'             => true,
                'edit_pages'             => true,
                'edit_published_posts'   => true,
                'edit_published_pages'   => true,
                'edit_private_pages'     => true,
                'edit_private_posts'     => true,
                'edit_others_posts'      => true,
                'edit_others_pages'      => true,
                'publish_posts'          => true,
                'publish_pages'          => true,
                'delete_posts'           => true,
                'delete_pages'           => true,
                'delete_private_pages'   => true,
                'delete_private_posts'   => true,
                'delete_published_pages' => true,
                'delete_published_posts' => true,
                'delete_others_posts'    => true,
                'delete_others_pages'    => true,
                'manage_categories'      => true,
                'manage_links'           => true,
                'moderate_comments'      => true,
                'upload_files'           => true,
                'export'                 => true,
                'import'                 => true,
                'list_users'             => true,
                'edit_theme_options'     => true,
            )
        );

        foreach (self::getRestApiCapabilities() as $capGroup) {
            foreach ($capGroup as $cap) {
                $wp_roles->add_cap('licensing_agent', $cap);
                $wp_roles->add_cap('administrator', $cap);
            }
        }

        foreach (self::getCoreCapabilities() as $capGroup) {
            foreach ($capGroup as $cap) {
                $wp_roles->add_cap('license_manager', $cap);
                $wp_roles->add_cap('administrator', $cap);
            }
        }
    }

    /**
     * Remove License Manager for WooCommerce roles
     */
    public static function removeRoles()
    {
        global $wp_roles;

        if (!class_exists('\WP_Roles')) {
            return;
        }

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        foreach (self::getRestApiCapabilities() as $capGroup) {
            foreach ($capGroup as $cap) {
                $wp_roles->remove_cap('licensing_agent', $cap);
                $wp_roles->remove_cap('administrator', $cap);
            }
        }

        foreach (self::getCoreCapabilities() as $capGroup) {
            foreach ($capGroup as $cap) {
                $wp_roles->remove_cap('license_manager', $cap);
                $wp_roles->remove_cap('administrator', $cap);
            }
        }

        remove_role('licensing_agent');
        remove_role('license_manager');
    }

    /**
     * Returns the plugin's core capabilities.
     *
     * @return array
     */
    public static function getCoreCapabilities()
    {
        $capabilities = array();

        $capabilities['core'] = array(
            'manage_license_manager_for_woocommerce'
        );

        $capabilities['licensing'] = array(
            'activate_license' => true,
            'deactivate_license' => true,
            'validate_license' => true
        );

        $capabilityTypes = array('license', 'generator', 'rest_api_key');

        foreach ($capabilityTypes as $capabilityType) {
            $capabilities[$capabilityType] = array(
                "create_{$capabilityType}",
                "edit_{$capabilityType}",
                "read_{$capabilityType}",
                "delete_{$capabilityType}",
                "create_{$capabilityType}s",
                "edit_{$capabilityType}s",
                "read_{$capabilityType}s",
                "delete_{$capabilityType}s"
            );
        }

        return $capabilities;
    }

    /**
     * Return's the plugin's REST API capabilities.
     *
     * @return array
     */
    public static function getRestApiCapabilities()
    {
        $capabilities = array();

        $capabilities['license'] = array(
            'read_licenses',
            'read_license',
            'create_license',
            'edit_license',
            'activate_license',
            'deactivate_license',
            'validate_license'
        );

        $capabilities['generator'] = array(
            'read_generators',
            'read_generator',
            'create_generator',
            'edit_generator',
            'use_generator'
        );

        $capabilities['product'] = array(
            'update_product',
            'download_product'
        );

        return $capabilities;
    }
}
