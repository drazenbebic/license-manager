<?php

namespace LicenseManagerForWooCommerce\Controllers;

use Exception;
use LicenseManagerForWooCommerce\AdminMenus;
use LicenseManagerForWooCommerce\AdminNotice;
use LicenseManagerForWooCommerce\Models\Resources\LicenseInstance as LicenseInstanceResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\LicenseInstance as LicenseInstanceResourceRepository;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;

defined('ABSPATH') || exit;

class LicenseInstance
{
    /**
     * License constructor.
     */
    public function __construct()
    {
        // Admin POST requests
        add_action('admin_post_lmfwc_add_license_instance_key',    array($this, 'addLicenseInstance'),       10);
        add_action('admin_post_lmfwc_update_license_instance_key', array($this, 'updateLicenseInstance'),    10);

        // AJAX calls
        add_action('wp_ajax_lmfwc_show_license_instance_key',      array($this, 'showLicenseInstanceKey'),     10);
        add_action('wp_ajax_lmfwc_show_all_license_instance_keys', array($this, 'showAllLicenseInstanceKeys'), 10);
    }

    /**
     * Add a single license instance ID to the database.
     */
    public function addLicenseInstance()
    {
        // Check the nonce
        check_admin_referer('lmfwc_add_license_instance_key');

        $licenseKey = sanitize_text_field( $_POST['license_key'] );

        /** @var LicenseResourceModel $license */
        $license = LicenseResourceRepository::instance()->findBy(
            array(
                'hash' => apply_filters('lmfwc_hash', $licenseKey)
            )
        );
		if ($license) {
			$licenseId = $license->getId();

			if (apply_filters('lmfwc_duplicate_instance', $_POST['instance_key'], $licenseId)) {
				AdminNotice::error(__('The instance key for this license already exists.', 'lmfwc'));
			}
			
			else {
				$timesActivated    = absint( $license->getTimesActivated() );
				$timesActivatedMax = absint( $license->getTimesActivatedMax() );
				if ($timesActivatedMax && ($timesActivated >= $timesActivatedMax)) {
					AdminNotice::error(sprintf(__('The license key (%s) has been already activated the maximum number of times!', 'lmfwc'), $licenseKey));
				}
				
				else {

					/** @var LicenseInstanceResourceModel $licenseInstance */
					$licenseInstance = LicenseInstanceResourceRepository::instance()->insert(
						array(
							'license_id'    => $licenseId,
							'instance_key'  => apply_filters('lmfwc_encrypt', $_POST['instance_key']),
							'instance_hash' => apply_filters('lmfwc_hash',    $_POST['instance_key'])
						)
					);

					// Redirect with message
					if ($licenseInstance) {
						LicenseResourceRepository::instance()->update(
							$licenseId,
							array(
								"times_activated" => $timesActivated+1
							)
						);
						
						AdminNotice::success(__('1 license instance activated successfully.', 'lmfwc'));
					}

					else {
						AdminNotice::error(__('There was a problem activating the license instance.', 'lmfwc'));
					}
				}
			}
		}
		
		else {
			AdminNotice::error(sprintf(__('License key not found: %s', 'lmfwc'), $licenseKey));
		}

        wp_redirect(sprintf('admin.php?page=%s&action=add', AdminMenus::LICENSE_INSTANCES_PAGE));
        exit();
    }

    /**
     * Updates an existing license instance ID for a given license key.
     *
     * @throws Exception
     */
    public function updateLicenseInstance()
    {
        // Check the nonce
        check_admin_referer('lmfwc_update_license_instance_key');

        $licenseInstanceId = absint($_POST['instance_id']);
        $instanceKey       = sanitize_text_field($_POST['instance_key']);
        $licenseKey        = sanitize_text_field($_POST['license_key']);
		
        /** @var LicenseInstanceResourceRepository $licenseInstance */
		$licenseInstance = LicenseInstanceResourceRepository::instance()->find($licenseInstanceId);
		if ( $licenseInstance ) {
            $originalLicenseId = $licenseInstance->getLicenseID();

			/** @var LicenseResourceModel $originalLicense */
			$originalLicense = LicenseResourceRepository::instance()->find(absint($originalLicenseId));

			/** @var LicenseResourceModel $license */
			$license = LicenseResourceRepository::instance()->findBy(
				array(
					'hash' => apply_filters('lmfwc_hash', $licenseKey)
				)
			);

			if ($license && $originalLicense) {
				$licenseId = $license->getId();

				// check activated times if license key has been changed
				$timesActivated    = absint( $license->getTimesActivated() );
				$timesActivatedMax = absint( $license->getTimesActivatedMax() );
				if ($originalLicenseId != $licenseId && $timesActivatedMax && $timesActivated >= $timesActivatedMax) {
					AdminNotice::error(sprintf(__('The license key (%s) has been already activated the maximum number of times!', 'lmfwc'), $licenseKey));
				}
				
				else {
					// Check for duplicates
					if (apply_filters('lmfwc_duplicate_instance', $instanceKey, $licenseId, $licenseInstanceId)) {
						AdminNotice::error(sprintf(__('The instance key for the given license key (%s) already exists.', 'lmfwc'), $licenseKey));
					}
					
					else {

						/** @var LicenseInstanceResourceModel $licenseInstance */
						$licenseInstance = LicenseInstanceResourceRepository::instance()->update(
							$licenseInstanceId,
							array(
								'license_id'          => $licenseId,
								'instance_key'        => apply_filters('lmfwc_encrypt', $_POST['instance_key']),
								'instance_hash'       => apply_filters('lmfwc_hash', $_POST['instance_key'])
							)
						);
						
						// Add a message and redirect
						if ($licenseInstance) {
							if ( $originalLicenseId != $licenseId ) {
								// update activated times if license key has changed
								LicenseResourceRepository::instance()->update(
									$originalLicenseId,
									array(
										"times_activated" => $originalLicense->getTimesActivated()-1
									)
								);

								LicenseResourceRepository::instance()->update(
									$licenseId,
									array(
										"times_activated" => $timesActivated+1
									)
								);
							}

							AdminNotice::success(__('Your instance key for the given license key has been updated successfully.', 'lmfwc'));
						}

						else {
							AdminNotice::error(__('There was a problem updating the instance key for the given license key.', 'lmfwc'));
						}
					}
				}
			}

			else {
				AdminNotice::error(sprintf(__('License key not found: %s', 'lmfwc'), $licenseKey));
			}

		}

		else {
			AdminNotice::error(sprintf(__('License instance (ID %d) not found in the database!', 'lmfwc'), $licenseInstanceId));
		}

        wp_redirect(sprintf('admin.php?page=%s&action=edit&id=%d', AdminMenus::LICENSE_INSTANCES_PAGE, $licenseInstanceId));
        exit();
    }

    /**
     * Show a single license key.
     */
    public function showLicenseInstanceKey()
    {
        // Validate request.
        check_ajax_referer('lmfwc_show_license_instance_key', 'show');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die(__('Invalid request.', 'lmfwc'));
        }

        /** @var LicenseInstanceResourceModel $license */
        $licenseInstance = LicenseInstanceResourceRepository::instance()->findBy(array('id' => $_POST['id']));

        wp_send_json($licenseInstance->getDecryptedLicenseInstanceKey());

        wp_die();
    }

    /**
     * Shows all visible license keys.
     */
    public function showAllLicenseInstanceKeys()
    {
        // Validate request.
        check_ajax_referer('lmfwc_show_all_license_instance_keys', 'show_all');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            wp_die(__('Invalid request.', 'lmfwc'));
        }

        $licenseInstanceKeysIds = array();

        foreach (json_decode($_POST['ids']) as $licenseInstanceKeyId) {
            /** @var LicenseInstanceResourceModel $license */
            $license = LicenseInstanceResourceRepository::instance()->find($licenseInstanceKeyId);

            $licenseInstanceKeysIds[$licenseInstanceKeyId] = $license->getDecryptedLicenseInstanceKey();
        }

        wp_send_json($licenseInstanceKeyId);
    }
}