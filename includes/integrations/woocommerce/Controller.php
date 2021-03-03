<?php

namespace LicenseManagerForWooCommerce\Integrations\WooCommerce;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use LicenseManagerForWooCommerce\Abstracts\IntegrationController as AbstractIntegrationController;
use LicenseManagerForWooCommerce\Enums\LicenseSource;
use LicenseManagerForWooCommerce\Enums\LicenseStatus;
use LicenseManagerForWooCommerce\Interfaces\IntegrationController as IntegrationControllerInterface;
use LicenseManagerForWooCommerce\Models\Resources\Generator as GeneratorResourceModel;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product_Simple;
use WC_Product_Variation;
use WP_User;
use WP_User_Query;

defined('ABSPATH') || exit;

class Controller extends AbstractIntegrationController implements IntegrationControllerInterface
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->bootstrap();

        add_filter('lmfwc_get_customer_license_keys',     array($this, 'getCustomerLicenseKeys'),     10, 1);
        add_filter('lmfwc_get_all_customer_license_keys', array($this, 'getAllCustomerLicenseKeys'),  10, 1);
        add_filter('lmfwc_insert_generated_license_keys', array($this, 'insertGeneratedLicenseKeys'), 10, 5);
        add_filter('lmfwc_insert_imported_license_keys',  array($this, 'insertImportedLicenseKeys'),  10, 7);
        add_action('lmfwc_sell_imported_license_keys',    array($this, 'sellImportedLicenseKeys'),    10, 3);
        add_action('wp_ajax_lmfwc_dropdown_user_search',  array($this, 'dropdownUserSearch'),         10);
        add_action('wp_ajax_lmfwc_dropdown_order_search', array($this, 'dropdownOrderSearch'),        10);
    }

    /**
     * Initializes the integration component
     */
    private function bootstrap()
    {
        new Stock();
        new Order();
        new Email();
        new ProductData();
        new Settings();
        new MyAccount();
    }

    /**
     * Retrieves ordered license keys.
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

            /** @var WC_Product_Simple|WC_Product_Variation $product */
            $product = $itemData->get_product();

            // Check if the product has been activated for selling.
            if (!lmfwc_is_licensed_product($product->get_id())) {
                continue;
            }

            /** @var LicenseResourceModel[] $licenses */
            $licenses = LicenseResourceRepository::instance()->findAllBy(
                array(
                    'order_id' => $order->get_id(),
                    'product_id' => $product->get_id()
                )
            );

            $data[$product->get_id()]['name'] = $product->get_name();
            $data[$product->get_id()]['keys'] = $licenses;
        }

        $args['data'] = $data;

        return $args;
    }

    /**
     * Retrieves all license keys for a user.
     *
     * @param int $userId
     *
     * @return array
     */
    public function getAllCustomerLicenseKeys($userId)
    {
        global $wpdb;

        $table = $wpdb->prefix . LicenseResourceRepository::TABLE;
        $userId = $wpdb->prepare('%d', $userId);
        $result = array();

        $sql = "
            SELECT
                DISTINCT(order_id)
            FROM
                {$table}
            WHERE
                `user_id` = {$userId}
            ORDER BY
                created_at DESC
            ;
        ";

        $orderIds = $wpdb->get_col($sql);

        if (!$orderIds || empty($orderIds)) {
            return $result;
        }

        foreach ($orderIds as $orderId) {
            $order = wc_get_order($orderId);

            $result[] = array(
                'order' => $order,
                'orderId' => $orderId,
                'licenses' => lmfwc_get_licenses(
                    array(
                        'user_id' => $userId,
                        'order_id' => $orderId
                    ),
                    array(
                        'created_at' => 'DESC'
                    )
                )
            );
        }

        return $result;
    }

    /**
     * Save the license keys for a given product to the database.
     *
     * @param int                    $orderId     WooCommerce Order ID
     * @param int                    $productId   WooCommerce Product ID
     * @param string[]               $licenseKeys License keys to be stored
     * @param int                    $status      License key status
     * @param GeneratorResourceModel $generator   Generator used
     *
     * @throws Exception
     */
    public function insertGeneratedLicenseKeys($orderId, $productId, $licenseKeys, $status, $generator)
    {
        $cleanLicenseKeys = array();
        $cleanOrderId     = ($orderId)   ? absint($orderId)   : null;
        $cleanProductId   = ($productId) ? absint($productId) : null;
        $cleanStatus      = ($status)    ? absint($status)    : null;
        $userId           = null;

        if (!$cleanStatus || !in_array($cleanStatus, LicenseStatus::$status)) {
            throw new Exception('License Status is invalid.');
        }

        if (!is_array($licenseKeys)) {
            throw new Exception('License Keys must be provided as array');
        }

        foreach ($licenseKeys as $licenseKey) {
            array_push($cleanLicenseKeys, sanitize_text_field($licenseKey));
        }

        if (count($cleanLicenseKeys) === 0) {
            throw new Exception('No License Keys were provided');
        }

        /** @var WC_Order $order */
        if ($order = wc_get_order($orderId)) {
            $userId = $order->get_user_id();
        }

        $gmtDate           = new DateTime('now', new DateTimeZone('GMT'));
        $invalidKeysAmount = 0;
        $expiresAt         = null;

        if ($generator->getExpiresIn() && $status == LicenseStatus::SOLD) {
            $dateInterval  = 'P' . $generator->getExpiresIn() . 'D';
            $dateExpiresAt = new DateInterval($dateInterval);
            $expiresAt     = $gmtDate->add($dateExpiresAt)->format('Y-m-d H:i:s');
        }

        lmfwc_update_order_downloads_expiration($expiresAt, $orderId);

        // Add the keys to the database table.
        foreach ($cleanLicenseKeys as $licenseKey) {
            // Key exists, up the invalid keys count.
            if (apply_filters('lmfwc_duplicate', $licenseKey)) {
                $invalidKeysAmount++;
                continue;
            }

            // Key doesn't exist, add it to the database table.
            $encryptedLicenseKey = apply_filters('lmfwc_encrypt', $licenseKey);
            $hashedLicenseKey    = apply_filters('lmfwc_hash', $licenseKey);

            // Save to database.
            LicenseResourceRepository::instance()->insert(
                array(
                    'order_id'            => $cleanOrderId,
                    'product_id'          => $cleanProductId,
                    'user_id'             => $userId,
                    'license_key'         => $encryptedLicenseKey,
                    'hash'                => $hashedLicenseKey,
                    'expires_at'          => $expiresAt,
                    'valid_for'           => $generator->getExpiresIn(),
                    'source'              => LicenseSource::GENERATOR,
                    'status'              => $cleanStatus,
                    'times_activated_max' => $generator->getTimesActivatedMax() ?: null
                )
            );
        }

        // There have been duplicate keys, regenerate and add them.
        if ($invalidKeysAmount > 0) {
            $newKeys = apply_filters('lmfwc_generate_license_keys', $invalidKeysAmount, $generator);

            $this->insertGeneratedLicenseKeys(
                $cleanOrderId,
                $cleanProductId,
                $newKeys,
                $cleanStatus,
                $generator
            );
        }

        else {
            // Keys have been generated and saved, this order is now complete.
            update_post_meta($cleanOrderId, 'lmfwc_order_complete', 1);
        }
    }

    /**
     * Imports an array of un-encrypted license keys.
     *
     * @param array $licenseKeys       License keys to be stored
     * @param int   $status            License key status
     * @param int   $orderId           WooCommerce Order ID
     * @param int   $productId         WooCommerce Product ID
     * @param int   $userId            WordPress User ID
     * @param int   $validFor          Validity period (in days)
     * @param int   $timesActivatedMax Maximum activation count
     *
     * @return array
     * @throws Exception
     */
    public function insertImportedLicenseKeys(
        $licenseKeys,
        $status,
        $orderId,
        $productId,
        $userId,
        $validFor,
        $timesActivatedMax
    ) {
        $result                 = array();
        $cleanLicenseKeys       = array();
        $cleanStatus            = $status            ? absint($status)            : null;
        $cleanOrderId           = $orderId           ? absint($orderId)           : null;
        $cleanProductId         = $productId         ? absint($productId)         : null;
        $cleanUserId            = $userId            ? absint($userId)            : null;
        $cleanValidFor          = $validFor          ? absint($validFor)          : null;
        $cleanTimesActivatedMax = $timesActivatedMax ? absint($timesActivatedMax) : null;

        if (!is_array($licenseKeys)) {
            throw new Exception('License Keys must be an array');
        }

        if (!$cleanStatus) {
            throw new Exception('Status enumerator is missing');
        }

        if (!in_array($cleanStatus, LicenseStatus::$status)) {
            throw new Exception('Status enumerator is invalid');
        }

        foreach ($licenseKeys as $licenseKey) {
            array_push($cleanLicenseKeys, sanitize_text_field($licenseKey));
        }

        $result['added']  = 0;
        $result['failed'] = 0;

        // Add the keys to the database table.
        foreach ($cleanLicenseKeys as $licenseKey) {
            $license = LicenseResourceRepository::instance()->insert(
                array(
                    'order_id'            => $cleanOrderId,
                    'product_id'          => $cleanProductId,
                    'user_id'             => $cleanUserId,
                    'license_key'         => apply_filters('lmfwc_encrypt', $licenseKey),
                    'hash'                => apply_filters('lmfwc_hash', $licenseKey),
                    'valid_for'           => $cleanValidFor,
                    'source'              => LicenseSource::IMPORT,
                    'status'              => $cleanStatus,
                    'times_activated_max' => $cleanTimesActivatedMax,
                )
            );

            if ($license) {
                if ($validFor) {
                    $date         = new DateTime();
                    $dateInterval = new DateInterval('P' . $validFor . 'D');
                    $expiresAt    = $date->add($dateInterval)->format('Y-m-d H:i:s');

                    lmfwc_update_order_downloads_expiration($expiresAt, $orderId);
                }

                $result['added']++;
            }

            else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Mark the imported license keys as sold.
     *
     * @param LicenseResourceModel[] $licenses License key resource models
     * @param int                    $orderId  WooCommerce Order ID
     * @param int                    $amount   Amount to be marked as sold
     *
     * @throws Exception
     * @throws Exception
     */
    public function sellImportedLicenseKeys($licenses, $orderId, $amount)
    {
        $cleanLicenseKeys = $licenses;
        $cleanOrderId     = $orderId ? absint($orderId) : null;
        $cleanAmount      = $amount  ? absint($amount)  : null;
        $userId           = null;

        if (!is_array($licenses) || count($licenses) <= 0) {
            throw new Exception('License Keys are invalid.');
        }

        if (!$cleanOrderId) {
            throw new Exception('Order ID is invalid.');
        }

        if (!$cleanOrderId) {
            throw new Exception('Amount is invalid.');
        }

        /** @var WC_Order $order */
        if ($order = wc_get_order($cleanOrderId)) {
            $userId = $order->get_user_id();
        }

        for ($i = 0; $i < $cleanAmount; $i++) {
            $license   = $cleanLicenseKeys[$i];
            $validFor  = (int)$license->getValidFor();
            $expiresAt = $license->getExpiresAt();

            if ($validFor) {
                $date         = new DateTime();
                $dateInterval = new DateInterval('P' . $validFor . 'D');
                $expiresAt    = $date->add($dateInterval)->format('Y-m-d H:i:s');
            }

            LicenseResourceRepository::instance()->update(
                $license->getId(),
                array(
                    'order_id'   => $cleanOrderId,
                    'user_id'    => $userId,
                    'expires_at' => $expiresAt,
                    'status'     => LicenseStatus::SOLD
                )
            );
        }
    }

    /**
     * Performs a paginated data search for users to be used inside a select2
     * dropdown.
     */
    public function dropdownUserSearch()
    {
        check_ajax_referer('lmfwc_dropdown_user_search', 'security');

        $page    = 1;
        $limit   = 10;
        $results = array();
        $term    = isset($_POST['term']) ? (string)wc_clean(wp_unslash($_POST['term'])) : '';
        $more    = true;
        $offset  = 0;

        if (!$term) {
            wp_die();
        }

        if (array_key_exists('page', $_POST)) {
            $page = (int)$_POST['page'];
        }

        if ($page > 1) {
            $offset = ($page - 1) * $limit;
        }

        $args =array(
            'search' => '*' . esc_attr($term) . '*',
            'search_columns' => array(
                'user_id',
                'user_login',
                'user_nicename',
                'user_email',
                'user_url',
            ),
            'number' => $limit,
            'offset' => $offset
        );

        $users = new WP_User_Query(apply_filters('lmfwc_dropdown_user_search_args', $args));

        if (count($users->get_results()) < $limit) {
            $more = false;
        }

        /** @var WP_User $user */
        foreach ($users->get_results() as $user) {
            $results[] = array(
                'id' => $user->ID,
                'text' => sprintf(
                /* translators: $1: user nicename, $2: user id, $3: user email */
                    '%1$s (#%2$d - %3$s)',
                    $user->user_nicename,
                    $user->ID,
                    $user->user_email
                )
            );
        }

        wp_send_json(
            array(
                'page'       => $page,
                'results'    => $results,
                'pagination' => array(
                    'more' => $more
                )
            )
        );
    }

    /**
     * Performs a paginated data search for orders to be used inside a select2
     * dropdown.
     */
    public function dropdownOrderSearch()
    {
        check_ajax_referer('lmfwc_dropdown_order_search', 'security');

        $page    = 1;
        $limit   = 10;
        $results = array();
        $term    = isset($_POST['term']) ? (string)wc_clean(wp_unslash($_POST['term'])) : '';
        $more    = true;
        $offset  = 0;
        $ids     = array();

        if (!$term) {
            wp_die();
        }

        if (isset($_POST['page'])) {
            $page = (int)$_POST['page'];
        }

        if ($page > 1) {
            $offset = ($page - 1) * $limit;
        }

        if (is_numeric($term)) {
            /** @var WC_Order $order */
            $order = wc_get_order((int)$term);

            // Order exists.
            if ($order && $order instanceof WC_Order) {
                $text = sprintf(
                /* translators: $1: order id, $2: customer name, $3: customer email */
                    '#%1$s %2$s <%3$s>',
                    $order->get_id(),
                    $order->get_formatted_billing_full_name(),
                    $order->get_billing_email()
                );

                $results[] = array(
                    'id' => $order->get_id(),
                    'text' => $text
                );
            }
        }

        if (empty($ids)) {
            $args = array(
                'limit' => $limit,
                'offset' => $offset,
                'customer' => $term,
                'orderby' => 'date',
                'order' => 'DESC'
            );

            /** @var WC_Order[] $orders */
            $orders = wc_get_orders(apply_filters('lmfwc_dropdown_order_search_args', $args));

            if (count($orders) < $limit) {
                $more = false;
            }

            foreach ($orders as $order) {
                $text = sprintf(
                /* translators: $1: order id, $2 customer name, $3 customer email */
                    '#%1$s %2$s <%3$s>',
                    $order->get_id(),
                    $order->get_formatted_billing_full_name(),
                    $order->get_billing_email()
                );

                $results[] = array(
                    'id' => $order->get_id(),
                    'text' => $text
                );
            }
        }

        wp_send_json(
            array(
                'page' => $page,
                'results' => $results,
                'pagination' => array(
                    'more' => $more
                )
            )
        );
    }
}
