<?php

namespace LicenseManagerForWooCommerce\API\v2;

use Exception;
use LicenseManagerForWooCommerce\Abstracts\RestController as LMFWC_REST_Controller;
use LicenseManagerForWooCommerce\Models\Resources\LicenseInstance as LicenseInstanceResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\LicenseInstance as LicenseInstanceResourceRepository;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

class LicenseInstances extends LMFWC_REST_Controller
{
    /**
     * @var string
     */
    protected $namespace = 'lmfwc/v2';

    /**
     * @var string
     */
    protected $rest_base = '/license-instances';

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
         * GET license-instances
         *
         * Retrieves all the available license instances from the database.
         */
        register_rest_route(
            $this->namespace, $this->rest_base, array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array($this, 'getLicenseInstances'),
                )
            )
        );

        /**
         * GET license-instances/{license_key}/{instance_key}
         *
         * Retrieves a single license instance for a given license key from the database.
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/(?P<license_key>[\w-]+)/(?P<instance_key>[\w-]+)', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array($this, 'getLicenseInstance'),
                    'args'     => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string',
                        ),
                        'instance_key' => array(
                            'description' => 'Instance Key',
                            'type'        => 'string',
                        )
                    )
                )
            )
        );

        /**
         * GET license-instances/activate/{license_key}/{instance_key}
         *
         * Activates a license key with an instance key
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/activate/(?P<license_key>[\w-]+)/(?P<instance_key>[\w-]+)', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array($this, 'activateLicenseInstance'),
                    'args'     => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string',
                        ),
                        'instance_key' => array(
                            'description' => 'Instance Key',
                            'type'        => 'string',
                        )
                    )
                )
            )
        );

        /**
         * GET license-instances/deactivate/{license_key}/{instance_key}
         *
         * Deactivates a license key with an instance key
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/deactivate/(?P<license_key>[\w-]+)/(?P<instance_key>[\w-]+)', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array($this, 'deactivateLicenseInstance'),
                    'args'     => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string'
                        ),
                        'instance_key' => array(
                            'description' => 'Instance Key',
                            'type'        => 'string'
                        )
                    )
                )
            )
        );

        /**
         * GET license-instances/validate/{license_key}/{instance_key}
         *
         * Validates a license key with an instance key
         */
        register_rest_route(
            $this->namespace, $this->rest_base . '/validate/(?P<license_key>[\w-]+)/(?P<instance_key>[\w-]+)', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array($this, 'validateLicenseInstance'),
                    'args'     => array(
                        'license_key' => array(
                            'description' => 'License Key',
                            'type'        => 'string',
                        ),
                        'instance_key' => array(
                            'description' => 'Instance Key',
                            'type'        => 'string',
                        )
                    )
                )
            )
        );
    }

    /**
     * Callback for the GET license-instances route. Retrieves all instance keys from the database.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function getLicenseInstances()
    {
        if (!$this->isRouteEnabled($this->settings, '021')) {
            return $this->routeDisabledError();
        }

        try {
            /** @var LicenseInstanceResourceRepository[] $licenseInstances */
            $licenseInstances = LicenseInstanceResourceRepository::instance()->findAll();
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$licenseInstances) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'No License Keys available',
                array('status' => 404)
            );
        }

        $response = array();

        /** @var LicenseInstanceResourceRepository $license */
        foreach ($licenseInstances as $licenseInstance) {
            $licenseInstanceData = $licenseInstance->toArray();

            // Remove the hash, decrypt the license key, and add it to the response
            unset($licenseInstanceData['instanceHash']);
            $licenseInstanceData['instanceKey'] = $licenseInstance->getDecryptedLicenseInstanceKey();
            $response[] = $licenseInstanceData;
        }

        return $this->response(true, $response, 200, 'v2/licenses');
    }

    /**
     * Callback for the GET license-instances/{license_key}/{instance_key} route. Retrieves a single instance key for a given license key from the database.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function getLicenseInstance(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '022')) {
            return $this->routeDisabledError();
        }

        $licenseKey  = sanitize_text_field($request->get_param('license_key'));
        $instanceKey = sanitize_text_field($request->get_param('instance_key'));
		$licenseId   = 0;

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License Key ID invalid.',
                array('status' => 404)
            );
        }

        if (!$instanceKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License Instance Key ID invalid.',
                array('status' => 404)
            );
        }

        try {
            /** @var LicenseResourceRepository $license */
            $license = LicenseResourceRepository::instance()->findBy(
                array(
                    'hash' => apply_filters('lmfwc_hash', $licenseKey)
                )
            );
			
			if ( $license ) {
				$licenseId = $license->getId();
				
				/** @var LicenseInstanceResourceRepository $licenseInstance */
				$licenseInstance = LicenseInstanceResourceRepository::instance()->findBy(
					array(
						'id'   => $licenseId,
						'instance_hash' => apply_filters('lmfwc_hash', $instanceKey)
					)
				);
			}
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license || !$licenseInstance) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s with instance key %s could not be found.',
                    $licenseKey,
					$instanceKey
                ),
                array('status' => 404)
            );
        }

        $licenseInstanceData = $licenseInstance->toArray();

        // Remove the hash and decrypt the license key
        unset($licenseInstanceData['instanceHash']);
        $licenseInstanceData['licenseKey'] = $license->getDecryptedLicenseKey();
        $licenseInstanceData['instanceKey'] = $licenseInstance->getDecryptedLicenseInstanceKey();

        return $this->response(true, $licenseInstanceData, 200, 'v2/license-instances/{license_key}/{instance_key}');
    }

    /**
     * Callback for the GET license-instances/activate/{license_key}/{instance_key} route. This will activate an instance key for a given license key (if possible)
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function activateLicenseInstance(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '023')) {
            return $this->routeDisabledError();
        }

        $licenseKey  = sanitize_text_field($request->get_param('license_key'));
        $instanceKey = sanitize_text_field($request->get_param('instance_key'));
		$licenseId   = 0;

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        if (!$instanceKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'Instance key is invalid.',
                array('status' => 404)
            );
        }

        try {
            /** @var LicenseResourceRepository $license */
            $license = LicenseResourceRepository::instance()->findBy(
                array(
                    'hash' => apply_filters('lmfwc_hash', $licenseKey)
                )
            );
			
			if ( $license ) {
				$licenseId = $license->getId();
				
				/** @var LicenseInstanceResourceRepository $licenseInstance */
				$licenseInstance = LicenseInstanceResourceRepository::instance()->findBy(
					array(
						'license_id'   => $licenseId,
						'instance_hash' => apply_filters('lmfwc_hash', $instanceKey)
					)
				);
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
                sprintf(
                    'License Key: %s could not be found.',
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        if ($licenseInstance) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'Instance Key: %s for license key %s already activated.',
					$instanceKey,
                    $licenseKey
                ),
                array('status' => 404)
            );
        }

        // Check if the license key can be activated
        $timesActivated    = absint($license->getTimesActivated());
        $timesActivatedMax = absint($license->getTimesActivatedMax());

        if (!$timesActivatedMax) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s can not be activated (times_activated_max not set).',
                    $licenseKey
                ),
                array('status' => 404)
            );
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
			/** @var LicenseInstanceResourceModel $licenseInstance */
			$licenseInstance = LicenseInstanceResourceRepository::instance()->insert(
				array(
					'license_id'    => $licenseId,
					'instance_key'  => apply_filters('lmfwc_encrypt', $instanceKey),
					'instance_hash' => apply_filters('lmfwc_hash',    $instanceKey)
				)
			);

			if ( $licenseInstance ) {
				if (!$timesActivated) {
					$timesActivatedNew = 1;
				}

				else {
					$timesActivatedNew = intval($timesActivated) + 1;
				}

				/** @var LicenseResourceModel $updatedLicense */
				$updatedLicense = LicenseResourceRepository::instance()->update(
					$licenseId,
					array(
						'times_activated' => $timesActivatedNew
					)
				);
			}
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        $licenseInstanceData = $updatedLicense->toArray();

        // Remove the hash and decrypt the license key
        unset($licenseInstanceData['instanceHash']);
        unset($licenseInstanceData['hash']);
        $licenseInstanceData['instanceKey'] = $instanceKey;
        $licenseInstanceData['licenseKey']  = $licenseKey;

        return $this->response(true, $licenseInstanceData, 200, 'v2/licenses/activate/{license_key}/{instance_key}');
    }

    /**
     * Callback for the GET license-instances/deactivate/{license_key}/{instance_key} route. This will deactivate an instance key for a given license key (if possible)
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function deactivateLicenseInstance(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '024')) {
            return $this->routeDisabledError();
        }

        $licenseKey  = sanitize_text_field($request->get_param('license_key'));
        $instanceKey = sanitize_text_field($request->get_param('instance_key'));
		$licenseId   = 0;

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        if (!$instanceKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'Instance key is invalid.',
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
			
			if ( $license ) {
				$licenseId = $license->getId();

				/** @var LicenseInstanceResourceRepository $licenseInstance */
				$licenseInstance = LicenseInstanceResourceRepository::instance()->findBy(
					array(
						'license_id'   => $licenseId,
						'instance_hash' => apply_filters('lmfwc_hash', $instanceKey)
					)
				);
			}
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license || !$licenseInstance) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s with instance key %s could not be found.',
                    $licenseKey,
					$instanceKey
                ),
                array('status' => 404)
            );
        }

        // Check if the license key can be deactivated
        $timesActivated    = absint($license->getTimesActivated());
        $timesActivatedMax = absint($license->getTimesActivatedMax());

        if (!$timesActivatedMax) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s can not be deactivated (times_activated_max not set).',
                    $licenseKey
                ),
                array('status' => 404)
            );
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
            LicenseInstanceResourceRepository::instance()->delete( [ $licenseInstance->getId() ] );

            $timesActivatedNew = intval($timesActivated) - 1;

            /** @var LicenseResourceRepository $updatedLicense */
            $updatedLicense = LicenseResourceRepository::instance()->update(
                $licenseId,
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

		$result = array(
		  "license_key" => $licenseKey,
		  "instance_key" => $instanceKey,
		  "deactivated" => true
		);

        return $this->response(true, $result, 200, 'v2/licenses/deactivate/{license_key}/{instance_key}');
    }

    /**
     * Callback for the GET license-instances/validate/{license_key}/{instance_key} route. This will check and verify the activation status of a
     * given license key with a given instance key.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function validateLicenseInstance(WP_REST_Request $request)
    {
        if (!$this->isRouteEnabled($this->settings, '025')) {
            return $this->routeDisabledError();
        }

        $licenseKey  = sanitize_text_field($request->get_param('license_key'));
        $instanceKey = sanitize_text_field($request->get_param('instance_key'));
		$licenseId   = 0;

        if (!$licenseKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'License key is invalid.',
                array('status' => 404)
            );
        }

        if (!$instanceKey) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                'Instance key is invalid.',
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
			
			if ( $license ) {
				$licenseId = $license->getId();
				
				/** @var LicenseInstanceResourceRepository $licenseInstance */
				$licenseInstance = LicenseInstanceResourceRepository::instance()->findBy(
					array(
						'license_id'   => $licenseId,
						'instance_hash' => apply_filters('lmfwc_hash', $instanceKey)
					)
				);
			}
        } catch (Exception $e) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                $e->getMessage(),
                array('status' => 404)
            );
        }

        if (!$license || !$licenseInstance) {
            return new WP_Error(
                'lmfwc_rest_data_error',
                sprintf(
                    'License Key: %s with instance key %s could not be found.',
                    $licenseKey,
					$instanceKey
                ),
                array('status' => 404)
            );
        }

        $licenseInstanceData = $licenseInstance->toArray();

        // Remove the hash and decrypt the license key
        unset($licenseInstanceData['instanceHash']);
        $licenseInstanceData['instanceKey'] = $instanceKey;
        $licenseInstanceData['licenseKey']  = $licenseKey;

        return $this->response(true, $licenseInstanceData, 200, 'v2/licenses/validate/{license_key}/{instance_key}');
    }
}