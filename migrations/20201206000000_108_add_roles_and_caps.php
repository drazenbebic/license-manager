<?php

defined('ABSPATH') || exit;

/**
 * @var string $migrationMode
 */

use LicenseManagerForWooCommerce\Migration;
use LicenseManagerForWooCommerce\Setup;

/**
 * Upgrade
 */
if ($migrationMode === Migration::MODE_UP) {
    Setup::createRoles();
}

/**
 * Downgrade
 */
if ($migrationMode === Migration::MODE_DOWN) {
    Setup::removeRoles();
}
