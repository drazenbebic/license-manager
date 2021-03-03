<?php

namespace LicenseManagerForWooCommerce\API;

defined('ABSPATH') || exit;

class Setup
{
    /**
     * Setup class constructor.
     */
    public function __construct()
    {
        // REST API was included starting WordPress 4.4.
        if (!class_exists('\WP_REST_Server')) {
            return;
        }

        // Init REST API routes.
        add_action('rest_api_init', array($this, 'registerRoutes'), 10);
    }

    /**
     * Initializes the plugin API controllers.
     */
    public function registerRoutes()
    {
        $controllers = array(
            // REST API v2 controllers.
            '\LicenseManagerForWooCommerce\API\v2\Licenses',
            '\LicenseManagerForWooCommerce\API\v2\Products',
            '\LicenseManagerForWooCommerce\API\v2\Generators'
        );

        foreach ($controllers as $controller) {
            $this->$controller = new $controller();
            $this->$controller->register_routes();
        }
    }
}
