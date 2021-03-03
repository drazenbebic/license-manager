<?php

namespace LicenseManagerForWooCommerce\Abstracts;

use LicenseManagerForWooCommerce\Enums\LicenseStatus;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

abstract class RestController extends WP_REST_Controller
{
    /**
     * Returns a structured response object for the API.
     *
     * @param bool   $success Indicates whether the request was successful or not
     * @param array  $data    Contains the response data
     * @param int    $code    Contains the response HTTP status code
     * @param string $route   Contains the request route name
     *
     * @return WP_REST_Response
     */
    protected function response($success, $data, $code = 200, $route)
    {
        return new WP_REST_Response(
            array(
                'success' => $success,
                'data'    => apply_filters('lmfwc_rest_api_pre_response', $data, $_SERVER['REQUEST_METHOD'], $route)
            ),
            $code
        );
    }

    /**
     * Checks if the given string is a JSON object.
     *
     * @param string $string
     * 
     * @return bool
     */
    protected function isJson($string)
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Checks whether a specific API route is enabled.
     *
     * @param array  $settings Plugin settings array
     * @param string $routeId  Unique plugin API endpoint ID
     *
     * @return bool
     */
    protected function isRouteEnabled($settings, $routeId)
    {
        if (!array_key_exists('lmfwc_enabled_api_routes', $settings)
            || !array_key_exists($routeId, $settings['lmfwc_enabled_api_routes'])
            || !$settings['lmfwc_enabled_api_routes'][$routeId]
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns the default error for disabled routes.
     *
     * @return WP_Error
     */
    protected function routeDisabledError()
    {
        return new WP_Error(
            'lmfwc_rest_route_disabled_error',
            'This route is disabled via the plugin settings.',
            array('status' => 404)
        );
    }

    /**
     * Converts the passed status string to a valid enumerator value.
     *
     * @param string $enumerator
     *
     * @return int
     */
    protected function getLicenseStatus($enumerator)
    {
        $status = LicenseStatus::INACTIVE;

        if (strtoupper($enumerator) === 'SOLD') {
            return LicenseStatus::SOLD;
        }

        if (strtoupper($enumerator) === 'DELIVERED') {
            return LicenseStatus::DELIVERED;
        }

        if (strtoupper($enumerator) === 'ACTIVE') {
            return LicenseStatus::ACTIVE;
        }

        if (strtoupper($enumerator) === 'INACTIVE') {
            return LicenseStatus::INACTIVE;
        }

        if (strtoupper($enumerator) === 'DISABLED') {
            return LicenseStatus::DISABLED;
        }

        return $status;
    }

    /**
     * Callback method for the "permission_callback" argument of the
     * "register_rest_route" method.
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function permissionCallback($request)
    {
        $error = apply_filters('lmfwc_rest_permission_callback', $request);

        if ($error instanceof WP_Error) {
            return $error;
        }

        return true;
    }

    /**
     * Checks if the current user has permission to perform the request.
     *
     * @param string $cap Capability slug
     *
     * @return bool
     */
    protected function capabilityCheck($cap)
    {
        $hasPermission = current_user_can($cap);

        return apply_filters('lmfwc_rest_capability_check', $hasPermission, $cap);
    }

    /**
     * Returns a contextual HTTP error code for authorization failure.
     *
     * @return int
     */
    protected function authorizationRequiredCode()
    {
        return is_user_logged_in() ? 403 : 401;
    }
}