<?php

namespace LicenseManagerForWooCommerce\API;

use WP_Error;

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

        // Init related actions and filters.
        add_filter('lmfwc_rest_api_pre_response', array($this, 'preResponse'), 1, 3);
        add_filter('lmfwc_rest_api_validation',   array($this, 'validate'),    1, 1);
    }

    /**
     * Initializes the plugin API controllers.
     */
    public function registerRoutes()
    {
        $controllers = array(
            // REST API v1 controllers.
            '\LicenseManagerForWooCommerce\API\v1\Licenses',
            '\LicenseManagerForWooCommerce\API\v1\Generators',
            // REST API v2 controllers.
            '\LicenseManagerForWooCommerce\API\v2\Licenses',
            '\LicenseManagerForWooCommerce\API\v2\Generators',
            '\LicenseManagerForWooCommerce\API\v2\LicenseInstances'
        );

        foreach ($controllers as $controller) {
            $this->$controller = new $controller();
            $this->$controller->register_routes();
        }
    }

    /**
     * Allows developers to hook in and modify the response of any API route
     * right before being sent out.
     *
     * @param string $method Contains the HTTP method which was used in the request
     * @param string $route  Contains the request endpoint name
     * @param array  $data   Contains the response data
     *
     * @return array
     */
    public function preResponse($method, $route, $data)
    {
        return $data;
    }

    /**
     * Allows developers to hook in and perform additional validation on any
     * and all routes.
     *
     * @param int $httpStatus The response HTTP status in case of an error, default is 403
     *
     * @return mixed|WP_Error
     */
    public function validate($httpStatus)
    {
        return $httpStatus;
    }
}