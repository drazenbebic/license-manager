<?php

namespace LicenseManagerForWooCommerce\API\v2;

use DateTime;
use DateTimeZone;
use Exception;
use LicenseManagerForWooCommerce\Abstracts\RestController as LMFWC_REST_Controller;
use LicenseManagerForWooCommerce\Enums\LicenseSource;
use LicenseManagerForWooCommerce\Enums\LicenseStatus;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

class Licenses extends LMFWC_REST_Controller
{
    /**
     * @var string
     */
    protected $namespace = 'lmfwc/v2';

    /**
     * @var string
     */
    protected $rest_base = '/licenses';

    /**
     * @var array
     */
    protected $settings = array();

    /**
     * Licenses constructor.
     */
    public function __construct()
    {
        $this->settings = (array)get_option('lmfwc_settings_general');
    }

    /**
     * Register all the needed routes for this resource.
     */
    public function register_routes()
    {
        /**
         * GET licenses
         *
         * Retrieves all the available licenses from the database.
         */
        register_rest_route(
            $this->namespace, $this->rest_base, array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'getLicenses'),
                    'permission_callback' => array($this, 'permissionCallback')
                )
            )
        );

        /**
         * GET licenses/{license_key}
         *
         * Retrieves a single licenses from the database.
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/(?P<license_key>[\w-]+)', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'getLicense'),
                    'permission_callback' => array($this, 'permissionCallback'),
                    'args'                => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string',
                        )
                    )
                )
            )
        );

        /**
         * POST licenses
         *
         * Creates a new license in the database
         */
        register_rest_route(
            $this->namespace, $this->rest_base, array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'createLicense'),
                    'permission_callback' => array($this, 'permissionCallback')
                )
            )
        );

        /**
         * PUT licenses/{license_key}
         *
         * Updates an already existing license in the database
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/(?P<license_key>[\w-]+)', array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'updateLicense'),
                    'permission_callback' => array($this, 'permissionCallback'),
                    'args'                => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string',
                        ),
                    ),
                )
            )
        );

        /**
         * GET licenses/activate/{license_key}
         *
         * Activates a license key
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/activate/(?P<license_key>[\w-]+)', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'activateLicense'),
                    'permission_callback' => array($this, 'permissionCallback'),
                    'args'                => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string',
                        ),
                    ),
                )
            )
        );

        /**
         * GET licenses/deactivate/{license_key}
         *
         * Deactivates a license key
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/deactivate/(?P<license_key>[\w-]+)', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'deactivateLicense'),
                    'permission_callback' => array($this, 'permissionCallback'),
                    'args'                => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string'
                        )
                    )
                )
            )
        );

        /**
         * PUT licenses/activate/{license_key}
         *
         * Activates a license key
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/validate/(?P<license_key>[\w-]+)', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'validateLicense'),
                    'permission_callback' => array($this, 'permissionCallback'),
                    'args'                => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string',
                        ),
                    ),
                )
            )
        );
    }

    /**
     * Callback for the GET licenses route. Retrieves all license keys from the database.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function getLicenses()
    {
        if (!$this->isRouteEnabled($this->settings, '010')) {
            return $this->routeDisabledError();
        }

        if (!$this->capabilityCheck('read_licenses')) {
            return new WP_Error(
                'lmfwc_rest_cannot_view',
                __('Sorry, you cannot list resources.', 'license-manager-for-woocommerce'),
                array(
                    'status' => $this->authorizationRequiredCode()
                )
            );
        }

        try {
            /** @var LicenseResourceModel[] $licenses */
            $licenses = LicenseResourceRepository::instance()->findAll();
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$licenses) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'No License Keys available',
                array('status' => 404)
            );
        }

        $response = array();

        foreach ($licenses as $license) {
            $licenseData = $license->toArray();

            // Remove the hash, decrypt the license key, and add it to the response
            unset($licenseData['hash']);
            $licenseData['licenseKey'] = $license->getDecryptedLicenseKey();
            $response[] = $licenseData;
        }

        return $this->response(true, $response, 200, 'v2/licenses');
    }

    /**
     * Callback for the GET licenses/{license_key} route. Retrieves a single license key from the database.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function getLicense(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '011')) {
            return $this->routeDisabledError();
        }

        if (!$this->capabilityCheck('read_license')) {
            return new WP_Error(
                'lmfwc_rest_cannot_view',
                __('Sorry, you cannot view this resource.', 'license-manager-for-woocommerce'),
                array(
                    'status' => $this->authorizationRequiredCode()
                )
            );
        }

        $licenseKey = sanitize_text_field($request->get_param('license_key'));

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License Key ID invalid.',
                array('status' => 404)
            );
        }

        try {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->findBy(
                array(
                    'hash' => apply_filters('lmfwc_hash', $licenseKey)
                )
            );
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s could not be found.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        $licenseData = $license->toArray();

        // Remove the hash and decrypt the license key
        unset($licenseData['hash']);
        $licenseData['licenseKey'] = $license->getDecryptedLicenseKey();

        return $this->response(true, $licenseData, 200, 'v2/licenses/{license_key}');
    }

    /**
     * Callback for the POST licenses route. Creates a new license key in the
     * database.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function createLicense(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '012')) {
            return $this->routeDisabledError();
        }

        if (!$this->capabilityCheck('create_license')) {
            return new WP_Error(
                'lmfwc_rest_cannot_create',
                __('Sorry, you are not allowed to create resources.', 'license-manager-for-woocommerce'),
                array(
                    'status' => $this->authorizationRequiredCode()
                )
            );
        }

        $body = $request->get_params();

        $orderId           = isset($body['order_id'])            ? absint($body['order_id'])                 : null;
        $productId         = isset($body['product_id'])          ? absint($body['product_id'])               : null;
        $userId            = isset($body['user_id'])             ? absint($body['user_id'])                  : null;
        $licenseKey        = isset($body['license_key'])         ? sanitize_text_field($body['license_key']) : null;
        $validFor          = isset($body['valid_for'])           ? absint($body['valid_for'])                : null;
        $validFor          = $validFor                           ? $validFor                                 : null;
        $expiresAt         = isset($body['expires_at'])          ? sanitize_text_field($body['expires_at'])  : null;
        $timesActivatedMax = isset($body['times_activated_max']) ? absint($body['times_activated_max'])      : null;
        $statusEnum        = isset($body['status'])              ? sanitize_text_field($body['status'])      : null;
        $status            = null;

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        if (apply_filters('lmfwc_duplicate', $licenseKey)) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'This license key already exists.',
                array('status' => 404)
            );
        }

        if ($statusEnum && !in_array($statusEnum, LicenseStatus::$enumArray)) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License Key status is invalid',
                array('status' => 404)
            );
        }

        else {
            $status = LicenseStatus::$values[$statusEnum];
        }

        if ($expiresAt) {
            try {
                $expiresAtDateTime = new DateTime($expiresAt);
                $expiresAt = $expiresAtDateTime->format('Y-m-d H:i:s');
                $validFor  = null;
            } catch (Exception $e) {
                return new WP_Error(
                    'lmfwc_rest_data_error',
                    $e->getMessage(),
                    array('status' => 404)
                );
            }
        }

        try {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->insert(
                array(
                    'order_id'            => $orderId,
                    'product_id'          => $productId,
                    'user_id'             => $userId,
                    'license_key'         => apply_filters('lmfwc_encrypt', $licenseKey),
                    'hash'                => apply_filters('lmfwc_hash', $licenseKey),
                    'valid_for'           => $validFor,
                    'expires_at'          => $expiresAt,
                    'source'              => LicenseSource::API,
                    'status'              => $status,
                    'times_activated_max' => $timesActivatedMax
                )
            );

            if ( ( $expiresAt !== null || $validFor !== null ) && $orderId !== null ) {
                if ( empty( $expiresAt ) ) {
                    $expiresAt = lmfwc_convert_valid_for_to_expires_at( $validFor );
                }

                lmfwc_update_order_downloads_expiration( $expiresAt, $orderId );
            }
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'The license key could not be added to the database.',
                array('status' => 404)
            );
        }

        // Update the stock
        if ($license->getProductId() !== null && $license->getStatus() === LicenseStatus::ACTIVE) {
            lmfwc_stock_increase($license->getProductId());
        }

        $licenseData = $license->toArray();

        // Remove the hash and decrypt the license key
        unset($licenseData['hash']);
        $licenseData['licenseKey'] = $license->getDecryptedLicenseKey();

        return $this->response(true, $licenseData, 200, 'v2/licenses');
    }

    /**
     * Callback for the PUT licenses/{license_key} route. Updates an existing license key in the database.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function updateLicense(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '013')) {
            return $this->routeDisabledError();
        }

        if (!$this->capabilityCheck('edit_license')) {
            return new WP_Error(
                'lmfwc_rest_cannot_edit',
                __('Sorry, you are not allowed to edit resources.', 'license-manager-for-woocommerce'),
                array(
                    'status' => $this->authorizationRequiredCode()
                )
            );
        }

        $body      = null;
        $urlParams = $request->get_url_params();

        if (!array_key_exists('license_key', $urlParams)) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'No license key was provided.',
                array('status' => 404)
            );
        }

        $licenseKey = sanitize_text_field($urlParams['license_key']);

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License Key invalid.',
                array('status' => 404)
            );
        }

        if ($this->isJson($request->get_body())) {
            $body = json_decode($request->get_body());
        }

        // Validate basic parameters
        if (!$body) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'No parameters were provided.',
                array('status' => 404)
            );
        }

        /** @var LicenseResourceModel $license */
        $license = LicenseResourceRepository::instance()->findBy(
            array(
                'hash' => apply_filters('lmfwc_hash', $licenseKey)
            )
        );

        if (!$license) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s could not be found.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        $updateData = (array)$body;

        if (empty($updateData)) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'No parameters were provided.',
                array('status' => 404)
            );
        }

        if (array_key_exists('license_key', $updateData)) {
            if (apply_filters('lmfwc_duplicate', $updateData['license_key'], $license->getId())) {
                return new WP_Error(
                    'lmfwc_rest_data_error',
                    'This license key already exists.',
                    array('status' => 404)
                );
            }

            $updateData['hash']        = apply_filters('lmfwc_hash', $updateData['license_key']);
            $updateData['license_key'] = apply_filters('lmfwc_encrypt', $updateData['license_key']);
        }

        if (array_key_exists('status', $updateData)) {
            $updateData['status'] = $this->getLicenseStatus($updateData['status']);
        }

        if (array_key_exists('hash', $updateData)) {
            unset($updateData['hash']);
        }

        if (array_key_exists('expires_at', $updateData)) {
            $updateData['valid_for'] = null;
        }

        // Update the stock
        if ($license->getProductId() !== null && $license->getStatus() === LicenseStatus::ACTIVE) {
            lmfwc_stock_decrease($license->getProductId());
        }

        /** @var LicenseResourceModel $updatedLicense */
        $updatedLicense = LicenseResourceRepository::instance()->update($license->getId(), $updateData);

        if (!$updatedLicense) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'The license key could not be updated.',
                array('status' => 404)
            );
        }

        // Update the stock
        if ($updatedLicense->getProductId() !== null && $updatedLicense->getStatus() === LicenseStatus::ACTIVE) {
            lmfwc_stock_increase($updatedLicense->getProductId());
        }

        $licenseData = $updatedLicense->toArray();

        if ( ( isset( $updateData['expires_at'] ) || isset( $updateData['valid_for'] ) ) && isset( $licenseData['orderId'] ) ) {
            if ( isset($updateData['expires_at'] ) ) {
                $expiresAt = $updateData['expires_at'];
            } else {
                $expiresAt = lmfwc_convert_valid_for_to_expires_at( $updateData['valid_for'] );
            }

            lmfwc_update_order_downloads_expiration( $expiresAt, $licenseData['orderId'] );
        }

        // Remove the hash and decrypt the license key
        unset($licenseData['hash']);
        $licenseData['licenseKey'] = $updatedLicense->getDecryptedLicenseKey();

        return $this->response(true, $licenseData, 200, 'v2/licenses/{license_key}');
    }

    /**
     * Callback for the GET licenses/activate/{license_key} route. This will activate a license key (if possible)
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function activateLicense(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '014')) {
            return $this->routeDisabledError();
        }

        if (!$this->capabilityCheck('activate_license')) {
            return new WP_Error(
                'lmfwc_rest_cannot_edit',
                __('Sorry, you are not allowed to edit this resource.', 'license-manager-for-woocommerce'),
                array(
                    'status' => $this->authorizationRequiredCode()
                )
            );
        }

        $licenseKey = sanitize_text_field($request->get_param('license_key'));

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        try {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->findBy(
                array(
                    'hash' => apply_filters('lmfwc_hash', $licenseKey)
                )
            );
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s could not be found.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        if (false !== $licenseExpired = $this->hasLicenseExpired($license)) {
            return $licenseExpired;
        }

        if (false !== $licenseDisabled = $this->isLicenseDisabled($license)) {
            return $licenseDisabled;
        }

        $timesActivated    = null;
        $timesActivatedMax = null;

        if ($license->getTimesActivated() !== null) {
            $timesActivated = absint($license->getTimesActivated());
        }

        if ($license->getTimesActivatedMax() !== null) {
            $timesActivatedMax = absint($license->getTimesActivatedMax());
        }

        if ($timesActivatedMax && ($timesActivated >= $timesActivatedMax)) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s reached maximum activation count.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        // Activate the license key
        try {
            if (!$timesActivated) {
                $timesActivatedNew = 1;
            }

            else {
                $timesActivatedNew = (int)$timesActivated + 1;
            }

            /** @var LicenseResourceModel $updatedLicense */
            $updatedLicense = LicenseResourceRepository::instance()->update(
                $license->getId(),
                array(
                    'times_activated' => $timesActivatedNew
                )
            );
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        $licenseData = $updatedLicense->toArray();

        // Remove the hash and decrypt the license key
        unset($licenseData['hash']);
        $licenseData['licenseKey'] = $updatedLicense->getDecryptedLicenseKey();

        return $this->response(true, $licenseData, 200, 'v2/licenses/activate/{license_key}');
    }

    /**
     * Callback for the GET licenses/deactivate/{license_key} route. This will deactivate a license key (if possible)
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function deactivateLicense(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '015')) {
            return $this->routeDisabledError();
        }

        if (!$this->capabilityCheck('deactivate_license')) {
            return new WP_Error(
                'lmfwc_rest_cannot_edit',
                __('Sorry, you are not allowed to edit this resource.', 'license-manager-for-woocommerce'),
                array(
                    'status' => $this->authorizationRequiredCode()
                )
            );
        }

        $licenseKey = sanitize_text_field($request->get_param('license_key'));

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        try {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->findBy(
                array(
                    'hash' => apply_filters('lmfwc_hash', $licenseKey)
                )
            );
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s could not be found.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        if (false !== $licenseExpired = $this->hasLicenseExpired($license)) {
            return $licenseExpired;
        }

        if (false !== $licenseDisabled = $this->isLicenseDisabled($license)) {
            return $licenseDisabled;
        }

        $timesActivated = null;

        if ($license->getTimesActivated() !== null) {
            $timesActivated = absint($license->getTimesActivated());
        }

        if (!$timesActivated || $timesActivated == 0) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s has not been activated yet.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        // Deactivate the license key
        try {
            $timesActivatedNew = (int)$timesActivated - 1;

            /** @var LicenseResourceModel $updatedLicense */
            $updatedLicense = LicenseResourceRepository::instance()->update(
                $license->getId(),
                array(
                    'times_activated' => $timesActivatedNew
                )
            );
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        $licenseData = $updatedLicense->toArray();

        // Remove the hash and decrypt the license key
        unset($licenseData['hash']);
        $licenseData['licenseKey'] = $updatedLicense->getDecryptedLicenseKey();

        return $this->response(true, $licenseData, 200, 'v2/licenses/deactivate/{license_key}');
    }

    /**
     * Callback for the GET licenses/validate/{license_key} route. This check and verify the activation status of a
     * given license key.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function validateLicense(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '016')) {
            return $this->routeDisabledError();
        }

        if (!$this->capabilityCheck('validate_license')) {
            return new WP_Error(
                'lmfwc_rest_cannot_view',
                __('Sorry, you cannot view this resource.', 'license-manager-for-woocommerce'),
                array(
                    'status' => $this->authorizationRequiredCode()
                )
            );
        }

        $urlParams = $request->get_url_params();

        if (!array_key_exists('license_key', $urlParams)) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        $licenseKey = sanitize_text_field($urlParams['license_key']);

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        try {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->findBy(
                array(
                    'hash' => apply_filters('lmfwc_hash', $licenseKey)
                )
            );
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s could not be found.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        $result = array(
            'timesActivated'       => (int)$license->getTimesActivated(),
            'timesActivatedMax'    => (int)$license->getTimesActivatedMax(),
            'remainingActivations' => (int)$license->getTimesActivatedMax() - (int)$license->getTimesActivated()
        );

        return $this->response(true, $result, 200, 'v2/licenses/validate/{license_key}');
    }

    /**
     * Checks if the license has an expiry date and if it has expired already.
     *
     * @param LicenseResourceModel $license
     * @return false|WP_Error
     */
    private function hasLicenseExpired($license)
    {
        if ($expiresAt = $license->getExpiresAt()) {
            try {
                $dateExpiresAt = new DateTime($expiresAt);
                $dateNow = new DateTime('now', new DateTimeZone('UTC'));
            } catch (Exception $e) {
                return new WP_Error('lmfwc_rest_license_expired', $e->getMessage());
            }

            if ($dateNow > $dateExpiresAt) {
                return new WP_Error(
                    'lmfwc_rest_license_expired',
                    sprintf('The license Key expired on %s (UTC).', $license->getExpiresAt()),
                    array('status' => 405)
                );
            }
        }

        return false;
    }

    /**
     * Checks if the license is disabled.
     *
     * @param LicenseResourceModel $license
     * @return false|WP_Error
     */
    private function isLicenseDisabled($license)
    {
        if ($license->getStatus() === LicenseStatus::DISABLED) {
            return new WP_Error(
                'lmfwc_rest_license_disabled',
                'The license Key is disabled.',
                array('status' => 405)
            );
        }
        
        return false;
    }
}
