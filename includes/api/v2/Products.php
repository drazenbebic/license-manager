<?php

namespace LicenseManagerForWooCommerce\API\v2;

use Exception;
use LicenseManagerForWooCommerce\Abstracts\RestController as LMFWC_REST_Controller;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use LicenseManagerForWooCommerce\Repositories\Resources\ProductInstalledOn as ProductInstalledOnResourceRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class Products extends LMFWC_REST_Controller {
	/**
	 * @var string
	 */
	protected $namespace = 'lmfwc/v2';

	/**
	 * @var string
	 */
	protected $rest_base = '/products';

	/**
	 * @var array
	 */
	protected $settings = [];

	/**
	 * Licenses constructor.
	 */
	public function __construct() {
		$this->settings = get_option( 'lmfwc_settings_general', [] );
	}

	/**
	 * Register all the needed routes for this resource.
	 */
	public function register_routes(): void {
		/**
		 * GET products/update/{license_key}
		 *
		 * Retrieves update information's about a WooCommerce product e.g. a WordPress plugin
		 */
		register_rest_route( $this->namespace, $this->rest_base . '/update/(?P<license_key>[\w-]+)', [
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'checkProductUpdate' ],
					'permission_callback' => [ $this, 'permissionCallback' ],
					'args'                => [
						'license_key' => [
							'description' => 'License Key',
							'type'        => 'string',
						]
					]
				]
			]
		);

		/**
		 * GET products/download/{license_key}
		 *
		 * Deliver update file for a WooCommerce product e.g. a WordPress plugin
		 */
		register_rest_route( $this->namespace, $this->rest_base . '/download/latest/(?P<license_key>[\w-]+)', [
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'downloadProductUpdate' ],
					'permission_callback' => [ $this, 'permissionCallback' ],
					'args'                => [
						'license_key' => [
							'description' => 'License Key',
							'type'        => 'string',
						]
					]
				]
			]
		);

		/**
		 * POST products/ping
		 *
		 * Defines the endpoint for product pings like installed on website(s) ping
		 */
		register_rest_route( $this->namespace, $this->rest_base . '/ping', [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'processPing' ],
					'permission_callback' => [ $this, 'permissionCallback' ],
					'args'                => [
						'license_key' => [
							'description' => 'License Key',
							'type'        => 'string',
						]
					]
				]
			]
		);
	}

	/**
	 * Callback for the GET products/update/{license_key} route. Checks if a
	 * product update is available.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function checkProductUpdate( WP_REST_Request $request ) {
		if ( ! $this->isRouteEnabled( $this->settings, '022' ) ) {
			return $this->routeDisabledError();
		}

		if ( ! $this->capabilityCheck( 'update_product' ) ) {
			return new WP_Error( 'lmfwc_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'license-manager-for-woocommerce' ), [ 'status' => $this->authorizationRequiredCode() ] );
		}

		$licenseKey = sanitize_text_field( $request->get_param( 'license_key' ) );

		if ( empty( $licenseKey ) ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'License Key ID invalid.', [ 'status' => 404 ] );
		}

		try {
			/** @var LicenseResourceModel $license */
			$license = LicenseResourceRepository::instance()->findBy( [ 'hash' => apply_filters( 'lmfwc_hash', $licenseKey ) ] );
		} catch ( Exception $e ) {
			return new WP_Error( 'lmfwc_rest_data_error', $e->getMessage(), [ 'status' => 404 ] );
		}

		if ( $license === null ) {
			return new WP_Error( 'lmfwc_rest_data_error', sprintf( 'License Key: %s could not be found.', $licenseKey ), [ 'status' => 404 ] );
		}

		$productId = $license->getProductId();

		if ( $productId === null ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'No product assigned to license.', [ 'status' => 404 ] );
		}

		$decryptedLicenseKey = $license->getDecryptedLicenseKey();
		$product             = wc_get_product( $license->getProductId() );
		$productDownloads    = $product->get_downloads();

		if ( ! empty( $productDownloads ) ) {
			$productDownloadFile = ABSPATH . ltrim( wp_make_link_relative( $product->get_file_download_path( lmfwc_array_key_first( $productDownloads ) ) ), '/' );

			if ( file_exists( $productDownloadFile ) ) {
				$lastUpdated = date( 'Y-m-d H:i:s', filemtime( $productDownloadFile ) );
			}
		}

		$consumerKey    = '';
		$consumerSecret = '';

		// If the $_GET parameters are present, use those first.
		if ( ! empty( $_GET['consumer_key'] ) && ! empty( $_GET['consumer_secret'] ) ) {
			$consumerKey    = $_GET['consumer_key'];
			$consumerSecret = $_GET['consumer_secret'];
		}

		// If the above is not present, we will do full basic auth.
		if ( ! $consumerKey && ! empty( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_SERVER['PHP_AUTH_PW'] ) ) {
			$consumerKey    = $_SERVER['PHP_AUTH_USER'];
			$consumerSecret = $_SERVER['PHP_AUTH_PW'];
		}

		// Add key and secret to update url
		$packageUrl = add_query_arg( [
			'consumer_key'    => $consumerKey,
			'consumer_secret' => $consumerSecret
		], get_rest_url() . $this->namespace . $this->rest_base . '/download/latest/' . $decryptedLicenseKey );

		// Calculate rating in percent (WordPress 5 star rating system)
		$rating = $product->get_average_rating();

		if ( ! empty( $rating ) ) {
			$rating_percent = $rating / 5 * 100;
		}

		$updateData = [
			'license_key'  => $license->getDecryptedLicenseKey(),
			'url'          => $product->get_permalink(),
			'new_version'  => $product->get_meta( 'lmfwc_licensed_product_version' ),
			'package'      => $packageUrl, // Link to download the latest update
			'tested'       => $product->get_meta( 'lmfwc_licensed_product_tested' ), // Testes up to WP version
			'requires'     => $product->get_meta( 'lmfwc_licensed_product_requires' ), // Required WP version
			'requires_php' => $product->get_meta( 'lmfwc_licensed_product_requires_php' ),
			'last_updated' => $lastUpdated ?? '',
			'rating'       => $rating_percent ?? 0,
			'num_ratings'  => $product->get_rating_count(),
			'sections'     => [
				'changelog' => preg_replace( "/\r\n|\r|\n/", '', wpautop( $product->get_meta( 'lmfwc_licensed_product_changelog' ) ) )
			]
		];

		return $this->response( true, $updateData, 'v2/products/update/{license_key}' );
	}

	/**
	 * Callback for the GET products/download/latest/{license_key} route. Performs a product download.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function downloadProductUpdate( WP_REST_Request $request ) {
		if ( ! $this->isRouteEnabled( $this->settings, '023' ) ) {
			return $this->routeDisabledError();
		}

		if ( ! $this->capabilityCheck( 'download_product' ) ) {
			return new WP_Error( 'lmfwc_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'license-manager-for-woocommerce' ), [ 'status' => $this->authorizationRequiredCode() ] );
		}

		$licenseKey = sanitize_text_field( $request->get_param( 'license_key' ) );

		if ( ! $licenseKey ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'License Key ID invalid.', [ 'status' => 404 ] );
		}

		try {
			/** @var LicenseResourceModel $license */
			$license = LicenseResourceRepository::instance()->findBy( [ 'hash' => apply_filters( 'lmfwc_hash', $licenseKey ) ] );
		} catch ( Exception $e ) {
			return new WP_Error( 'lmfwc_rest_data_error', $e->getMessage(), [ 'status' => 404 ] );
		}

		if ( $license === null ) {
			return new WP_Error( 'lmfwc_rest_data_error', sprintf( 'License Key: %s could not be found.', $licenseKey ), [ 'status' => 404 ] );
		}

		$productId = $license->getProductId();

		if ( $productId === null ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'No product assigned to license.', [ 'status' => 404 ] );
		}

		$product = wc_get_product( $license->getProductId() );

		if ( $product === null ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'The assigned product could not be found.', [ 'status' => 404 ] );
		}

		$productDownloads    = $product->get_downloads();
		$productDownloadFile = ABSPATH . ltrim( wp_make_link_relative( $product->get_file_download_path( lmfwc_array_key_first( $productDownloads ) ) ), '/' );

		if ( empty( $productDownloadFile ) || ! file_exists( $productDownloadFile ) ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'Requested file not found.', [ 'status' => 404 ] );
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename=' . basename( $productDownloadFile ) );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $productDownloadFile ) );
		ob_clean();
		flush();
		readfile( $productDownloadFile );

		$fileDetailsData = [
			'filename'       => basename( $productDownloadFile ),
			'content-length' => filesize( $productDownloadFile )
		];

		return $this->response( true, $fileDetailsData, 'v2/products/download/latest/{license_key}' );
	}

	/**
	 * Callback for the POST products/ping route. Registers a product ping and shows it in the installed products table
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function processPing( WP_REST_Request $request ) {
		if ( ! $this->isRouteEnabled( $this->settings, '024' ) ) {
			return $this->routeDisabledError();
		}

		if ( ! $this->capabilityCheck( 'product_ping' ) ) {
			return new WP_Error( 'lmfwc_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'license-manager-for-woocommerce' ), [ 'status' => $this->authorizationRequiredCode() ] );
		}

		$body = $request->get_params();

		$licenseKey  = isset( $body['license_key'] ) ? sanitize_text_field( $body['license_key'] ) : null;
		$productName = isset( $body['product_name'] ) ? sanitize_text_field( $body['product_name'] ) : null;
		$host        = isset( $body['host'] ) ? sanitize_text_field( $body['host'] ) : null;
		$licenseId   = null;

		if ( ! empty( $licenseKey ) ) {
			try {
				/** @var LicenseResourceModel $license */
				$license = LicenseResourceRepository::instance()->findBy( [ 'hash' => apply_filters( 'lmfwc_hash', $licenseKey ) ] );
			} catch ( Exception $e ) {
				return new WP_Error( 'lmfwc_rest_data_error', $e->getMessage(), [ 'status' => 404 ] );
			}

			if ( ! $license ) {
				return new WP_Error( 'lmfwc_rest_data_error', sprintf( 'License Key: %s could not be found.', $licenseKey ), [ 'status' => 404 ] );
			}

			$licenseId = $license->getId();
		}

		$productInstalledOn = ProductInstalledOnResourceRepository::instance()->insertUpdate( [
			'product_name' => $productName,
			'license_id'   => $licenseId,
			'host'         => $host,
			'last_ping'    => gmdate( 'Y-m-d H:i:s' )
		], [
			'product_name' => $productName,
			'license_id'   => $licenseId,
			'host'         => $host,
		], false );

		if ( ! $productInstalledOn ) {
			return new WP_Error( 'lmfwc_rest_data_error', 'The product installed on could not be added to the database.', [ 'status' => 404 ] );
		}

		return $this->response( true, null, 'v2/products/ping' );
	}
}
