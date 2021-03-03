<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerceSubscriptions\API\v2;

use WC_Subscription;

defined('ABSPATH') || exit;

class Licenses
{
    /**
     * Licenses constructor.
     */
    public function __construct()
    {
        add_filter('lmfwc_rest_api_pre_response', array($this, 'addSubscriptionId'), 10, 3);
    }

    /**
     * Adds the subscription ID to the REST API response.
     *
     * @param array $requestMethod
     * @param string $route
     * @param array $data
     * @return array
     */
    public function addSubscriptionId($data, $requestMethod, $route)
    {
        $routes = array(
            'v2/licenses',
            'v2/licenses/{license_key}',
            'v2/licenses/activate/{license_key}',
            'v2/licenses/deactivate/{license_key}',
            'v2/licenses/validate/{license_key}'
        );

        if (!in_array($route, $routes)) {
            return $data;
        }

        switch ($route) {
            case 'v2/licenses' && $requestMethod === 'GET':
                // Pass by reference!
                foreach ($data as &$license) {
                    $license['subscriptionIds'] = array();

                    if (!isset($license['orderId'])) {
                        continue;
                    }

                    $license['subscriptionIds'] = $this->getOrderSubscriptionIds((int)$license['orderId']);
                }
                break;
            case 'v2/licenses/{license_key}' && $requestMethod === 'GET':
            case 'v2/licenses' && $requestMethod === 'POST':
            case 'v2/licenses/{license_key}' && $requestMethod === 'PUT':
            case 'v2/licenses/activate/{license_key}' && $requestMethod === 'GET':
            case 'v2/licenses/deactivate/{license_key}' && $requestMethod === 'GET':
            case 'v2/licenses/validate/{license_key}' && $requestMethod === 'GET':
                $data['subscriptionIds'] = array();

                if (!isset($data['orderId'])) {
                    return $data;
                }

                $data['subscriptionIds'] = $this->getOrderSubscriptionIds((int)$data['orderId']);
                break;
        }

        return $data;
    }

    private function getOrderSubscriptionIds($orderId)
    {
        $subscriptionIds = array();

        /** @var false|WC_Subscription[] $subscriptions */
        $subscriptions = wcs_get_subscriptions_for_order($orderId);

        if (!$subscriptions or empty($subscriptions)) {
            return $subscriptionIds;
        }

        foreach ($subscriptions as $i => $subscription) {
            $subscriptionIds[] = $subscription->get_id();
        }

        return $subscriptionIds;
    }
}