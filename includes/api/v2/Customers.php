<?php

namespace LicenseManagerForWooCommerce\API\v2;

use Exception;
use LicenseManagerForWooCommerce\Abstracts\RestController as LMFWC_REST_Controller;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class Customers extends LMFWC_REST_Controller {
	/**
	 * @var string
	 */
	protected $namespace = 'lmfwc/v2';

	/**
	 * @var string
	 */
	protected $rest_base = '/customers';

	/**
	 * @var array
	 */
	protected $settings = [];

	/**
	 * Customers constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'lmfwc_settings_general', [] );
	}

	/**
	 * Register all the needed routes for this resource
	 */
	public function register_routes(): void {
		/**
		 * GET customers/{customer_id}/licenses
		 *
		 * Retrieves all licensed owned by a customer
		 */
		register_rest_route( $this->namespace, $this->rest_base . '/(?P<customer_id>[\d]+)/licenses', [
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'getLicenses' ],
					'permission_callback' => [ $this, 'permissionCallback' ],
					'args'                => [
						'customer_id' => [
							'description' => 'Unique identifier of the customer',
							'type'        => 'integer',
						]
					]
				]
			]
		);
	}

	/**
	 * Callback for the GET customers/{customer_id}/licenses route
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function getLicenses( WP_REST_Request $request ) {
		if ( ! $this->isRouteEnabled( $this->settings, '025' ) ) {
			return $this->routeDisabledError();
		}

		if ( ! $this->capabilityCheck( 'read_licenses' ) ) {
			return new WP_Error( 'lmfwc_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'license-manager-for-woocommerce' ), [ 'status' => $this->authorizationRequiredCode() ] );
		}

		$customerId = absint( $request->get_param( 'customer_id' ) );

		if ( empty( $customerId ) ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'Customer ID invalid.', [ 'status' => 404 ] );
		}

		try {
			/** @var LicenseResourceModel $licenses */
			$licenses = LicenseResourceRepository::instance()->findAllBy( [ 'user_id' => $customerId ] );
		} catch ( Exception $e ) {
			return new WP_Error( 'lmfwc_rest_data_error', $e->getMessage(), [ 'status' => 404 ] );
		}

		if ( $licenses === null ) {
			return new WP_Error( 'lmfwc_rest_data_error', sprintf( 'License keys could not be found for customer %s.', $customerId ), [ 'status' => 404 ] );
		}

		$response = [];

		foreach ( $licenses as $license ) {
			$licenseData = $license->toArray();

			// Remove the hash, decrypt the license key, and add it to the response
			unset( $licenseData['hash'] );
			$licenseData['licenseKey'] = $license->getDecryptedLicenseKey();
			$response[]                = $licenseData;
		}

		return $this->response( true, $response, 'v2/customers/{customer_id}/licenses' );
	}
}
