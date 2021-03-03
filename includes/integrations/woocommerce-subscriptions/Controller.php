<?php


namespace LicenseManagerForWooCommerce\Integrations\WooCommerceSubscriptions;

use LicenseManagerForWooCommerce\Abstracts\IntegrationController as AbstractIntegrationController;
use LicenseManagerForWooCommerce\Interfaces\IntegrationController as IntegrationControllerInterface;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WC_Subscriptions_Renewal_Order;

defined('ABSPATH') || exit;

class Controller extends AbstractIntegrationController implements IntegrationControllerInterface
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->bootstrap();

        add_filter('lmfwc_get_customer_license_keys', array($this, 'getCustomerLicenseKeys'), 11, 1);
    }

    /**
     * Initializes the integration component
     */
    public function bootstrap()
    {
        new API\v2\Licenses();
        new Lists\LicensesList();
        new Order();
        new ProductData();
        new Suspend();
    }

    /**
     * Some products can be configured to extend the licenses of the original
     * order instead of issuing new ones. This hooks accounts for that, and
     * retrieves the properly configured licenses.
     *
     * @param array $args
     * @return array
     */
    public function getCustomerLicenseKeys($args)
    {
        /** @var WC_Order $order */
        $order = $args['order'];
        $data  = array();

        /** @var WC_Order_Item_Product $itemData */
        foreach ($order->get_items() as $itemData) {

            /** @var WC_Product $product */
            $product = $itemData->get_product();
            $orderId = $order->get_id();

            // Check if the product has been activated for selling.
            if (!lmfwc_is_licensed_product($product->get_id())) {
                continue;
            }

            // Check if the original license should have been extended, and
            // include it instead.
            if (wcs_order_contains_renewal($orderId)
                && lmfwc_is_license_expiration_extendable_for_subscriptions($product->get_id())
            ) {
                /** @var WC_Order $parentOrder */
                $parentOrder = WC_Subscriptions_Renewal_Order::get_parent_order($orderId);

                if ($parentOrder) {
                    $orderId = $parentOrder->get_id();
                }
            }

            /** @var LicenseResourceModel[] $licenses */
            $licenses = LicenseResourceRepository::instance()->findAllBy(
                array(
                    'order_id' => $orderId,
                    'product_id' => $product->get_id()
                )
            );

            $data[$product->get_id()]['name'] = $product->get_name();
            $data[$product->get_id()]['keys'] = $licenses;
        }

        $args['data'] = $data;

        return $args;
    }
}