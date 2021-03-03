<?php


namespace LicenseManagerForWooCommerce\Integrations\WooCommerceSubscriptions;

use DateTime;
use Exception;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use WC_Order;
use WC_Subscription;
use WC_Subscriptions_Product;

defined('ABSPATH') || exit;

class Order
{
    /**
     * Order constructor.
     */
    public function __construct()
    {
        add_filter('lmfwc_maybe_skip_subscription_renewals', array($this, 'maybeSkipSubscriptionRenewal'), 10, 2);
    }


    /**
     * Checks if the order is a renewal of a WooCommerce Subscription, in which
     * case it extends the license(s) by the subscription duration.
     *
     * @param int $orderId   Subscription Renewal Order ID
     * @param int $productId WooCommerce Product ID
     * @return bool
     */
    public function maybeSkipSubscriptionRenewal($orderId, $productId)
    {
        // Return if this is not a renewal order
        if (!wcs_order_contains_renewal($orderId)) {
            return false;
        }

        // Return if the product hasn't been configured to extend subscriptions
        if (lmfwc_get_subscription_renewal_action($productId) !== 'extend_existing_license') {
            return false;
        }

        /** @var WC_Subscription[] $subscriptions */
        $subscriptions = wcs_get_subscriptions_for_renewal_order($orderId);

        if (!$subscriptions) {
            return false;
        }

        foreach ($subscriptions as $subscription) {
            $parentOrderArray = $subscription->get_related_orders('ids', 'parent');

            if (!$parentOrderArray || count($parentOrderArray) !== 1) {
                return false;
            }

            $parentOrderId = intval(reset($parentOrderArray));

            if (!$parentOrderId) {
                return false;
            }

            /** @var false|WC_Order $parentOrder */
            $parentOrder = wc_get_order($parentOrderId);

            if (!$parentOrder) {
                return false;
            }

            // Extend the license either by the subscription, or user-defined customer interval/period.
            if (lmfwc_get_subscription_renewal_interval_type($productId) === 'subscription') {
                $subscriptionInterval = intval(WC_Subscriptions_Product::get_interval($productId));
                $subscriptionPeriod   = WC_Subscriptions_Product::get_period($productId);
            } else {
                $subscriptionInterval = lmfwc_get_subscription_renewal_custom_interval($productId);
                $subscriptionPeriod   = lmfwc_get_subscription_renewal_custom_period($productId);
            }

            /** @var false|LicenseResourceModel[] $licenses */
            $licenses = lmfwc_get_licenses(
                array(
                    'order_id' => $parentOrder->get_id(),
                    'product_id' => $productId
                )
            );

            if (!$licenses) {
                return false;
            }

            /** @var LicenseResourceModel $license */
            foreach ($licenses as $license) {
                try {
                    $dateNewExpiresAt = new DateTime($license->getExpiresAt());
                } catch (Exception $e) {
                    return false;
                }

                // Singular form, i.e. "+1 week"
                $modifyString = '+' . $subscriptionInterval . ' ' . $subscriptionPeriod;

                // Plural form, i.e. "+3 weeks"
                if ($subscriptionInterval > 1) {
                    $modifyString .= 's';
                }

                $dateNewExpiresAt->modify($modifyString);

                try {
                    lmfwc_update_license(
                        $license->getDecryptedLicenseKey(),
                        array(
                            'expires_at' => $dateNewExpiresAt->format('Y-m-d H:i:s')
                        )
                    );
                } catch (Exception $e) {
                    return false;
                }
            }
        }

        return true;
    }

}