<?php

defined('ABSPATH') || exit;

/**
 * @var string $migrationMode
 */

use LicenseManagerForWooCommerce\Migration;

/**
 * Upgrade
 */
if ($migrationMode === Migration::MODE_UP) {
    update_option('woocommerce_myaccount_licenses_endpoint', 'licenses');
}

/**
 * Downgrade
 */
if ($migrationMode === Migration::MODE_DOWN) {
    delete_option('woocommerce_myaccount_licenses_endpoint');
}
