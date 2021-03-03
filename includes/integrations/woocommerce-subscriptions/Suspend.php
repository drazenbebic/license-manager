<?php


namespace LicenseManagerForWooCommerce\Integrations\WooCommerceSubscriptions;

use DateTime;
use Exception;
use DateTimeZone;
use WC_Subscription;
use LicenseManagerForWooCommerce\Settings;
use LicenseManagerForWooCommerce\Enums\LicenseStatus as LicenseStatusEnum;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;

defined('ABSPATH') || exit;

class Suspend
{

    /**
     * @var string
     */
    const STATUS_META_KEY = 'previous_subscription_status_for_%s';

    /**
     * Suspend constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_subscription_status_updated', array($this, 'updateRelatedLicenses'), 90, 3);
    }

    /**
     * Finds all the related license(s) to the WooCommerce Subscription and sets
     * the status to the given new status.
     * 
     * It will be triggered for all status changes defined by WooCommerce Subscriptions, 
     * including: pending, active, on-hold, pending-cancel, cancelled, or expired; 
     * as well as any custom subscription statuses.
     *
     * @param WC_Subscription $subscription   Subscription that has been put on hold
     * @param string $newWCSubStatus   The string representation of the new status applied to the subscription.
     * @param string $oldWCSubStatus   The string representation of the subscriptions status before the change was applied.
     * @return bool
     */
    public function updateRelatedLicenses($subscription, $newWCSubStatus, $oldWCSubStatus)
    {
        if ($newWCSubStatus === 'cancelled' || $newWCSubStatus === 'on-hold') {
            $newLicenseStatus = LicenseStatusEnum::DISABLED;
        } else if ($newWCSubStatus === 'active' && $oldWCSubStatus === 'on-hold') {
            $newLicenseStatus = Settings::get('lmfwc_auto_delivery') ? LicenseStatusEnum::DELIVERED : LicenseStatusEnum::SOLD;
        } else {
            return false;
        }

        /** @var int[] $parentOrderArray */
        $parentOrderArray = $subscription->get_related_orders('ids', 'any');

        if (!$parentOrderArray || count($parentOrderArray) === 0) {
            return false;
        }

        /** @var int $parentOrderId */
        foreach ($parentOrderArray as $parentOrderId) {

            /** @var false|LicenseResourceModel[] $licenses */
            $licenses = lmfwc_get_licenses(
                array(
                    'order_id' => $parentOrderId
                )
            );

            if (!$licenses) {
                continue;
            }

            /** @var LicenseResourceModel $license */
            foreach ($licenses as $license) {

                $expiresAt = $license->getExpiresAt();
                if ($expiresAt && $expiresAt !== '') {
                    try {
                        $dateExpiresAt = new DateTime($license->getExpiresAt());
                    } catch (Exception $e) {
                        continue;
                    }
    
                    $interval = $dateExpiresAt->diff(new DateTime('now', new DateTimeZone('UTC')))->format('%r%a');
                    if (intval($interval) > 0) {
                        continue;
                    }
                }

                $previousStatus = lmfwc_get_license_meta($license->getId(), sprintf(self::STATUS_META_KEY, $oldWCSubStatus), true);
                if ($previousStatus && in_array($previousStatus, LicenseStatusEnum::$values)) {
                    $newLicenseStatus = $previousStatus;
                }

                $currentLicenseStatus = $license->getStatus();
                if ($newWCSubStatus !== 'active') {
                    $metaKey = sprintf(self::STATUS_META_KEY, $newWCSubStatus);
                    if (!lmfwc_update_license_meta($license->getId(), $metaKey, $currentLicenseStatus)) {
                        lmfwc_add_license_meta($license->getId(), $metaKey, $currentLicenseStatus);
                    }
                }

                try {
                    lmfwc_update_license(
                        $license->getDecryptedLicenseKey(),
                        array(
                            'status' => intval($newLicenseStatus)
                        )
                    );
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        return true;
    }

}