<?php

defined('ABSPATH') || exit;

/**
 * @var string $migrationMode
 */

use LicenseManagerForWooCommerce\Setup;
use LicenseManagerForWooCommerce\Migration;

$tableLicenseInstance = $wpdb->prefix . Setup::LICENSE_INSTANCES_TABLE_NAME;

/**
 * Upgrade
 */
if ($migrationMode === Migration::MODE_UP) {

    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }

    dbDelta("
        CREATE TABLE IF NOT EXISTS $tableLicenseInstance (
            `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `license_id` BIGINT(20) NOT NULL,
            `instance_key` longtext NOT NULL,
            `instance_hash` longtext NOT NULL,
            `created_at` datetime DEFAULT NULL,
            `created_by` bigint(20) DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            `updated_by` bigint(20) DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `license_id` (`license_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
}

/**
 * Downgrade
 */
if ($migrationMode === Migration::MODE_DOWN) {
    $wpdb->query("DROP TABLE IF EXISTS {$tableLicenseInstance}");
}