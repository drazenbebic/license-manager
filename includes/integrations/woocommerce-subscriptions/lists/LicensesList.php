<?php


namespace LicenseManagerForWooCommerce\Integrations\WooCommerceSubscriptions\lists;

use WC_Subscription;

defined('ABSPATH') || exit;

class LicensesList
{
    /**
     * LicensesList constructor.
     */
    public function __construct()
    {
        add_filter('lmfwc_table_licenses_column_name',  array($this, 'addSubscriptionColumn'),      10, 1);
        add_filter('lmfwc_table_licenses_column_value', array($this, 'addSubscriptionColumnValue'), 10, 2);
    }

    /**
     * Adds the "Subscription" column to the licenses table.
     *
     * @param array $columns
     * @return array
     */
    public function addSubscriptionColumn($columns)
    {
        $orderIdPosition = array_search('order_id', array_keys($columns));

        return array_slice($columns, 0, $orderIdPosition, true)
            + array('subscriptions' => __('Subscriptions', 'license-manager-for-woocommerce'))
            + array_slice($columns, $orderIdPosition, count($columns), true);
    }

    /**
     * Adds the HTML markup for the subscriptions column.
     *
     * @param array  $item       Associative array of column name and value pairs
     * @param string $columnName Name of the current column
     * @return array
     */
    public function addSubscriptionColumnValue($item, $columnName)
    {
        if ($columnName !== 'subscriptions') {
            return $item;
        }

        $html    = '';
        $orderId = $item['order_id'];

        if ($orderId) {
            /** @var false|WC_Subscription[] $subscriptions */
            $subscriptions = wcs_get_subscriptions_for_order($orderId);

            if ($subscriptions) {
                foreach ($subscriptions as $i => $subscription) {
                    $html .= '<a href="' . esc_url($subscription->get_edit_order_url()) . '">#' . $subscription->get_id() . '</a>';

                    if ($i !== count($subscriptions)) {
                        $html .= '<br>';
                    }
                }
            }
        }

        $item[$columnName] = $html;

        return $item;
    }
}