<?php
/**
 * LicenseManager for WooCommerce Stock Functions
 *
 * Functions related to license key stock manipulation. Available on both the
 * front-end and admin.
 */

use LicenseManagerForWooCommerce\Settings;

defined('ABSPATH') || exit;

/**
 * Increases the available stock of a WooCommerce Product by $amount.
 *
 * @param int|WC_Product $product WooCommerce Product object
 * @param int            $amount  Increment amount
 * @return bool|WC_Product
 */
function lmfwc_stock_increase($product, $amount = 1) {
    return lmfwc_stock_modify($product,'increase', $amount);
}

/**
 * Decreases the available stock of a WooCommerce Product by $amount.
 *
 * @param int|WC_Product $product WooCommerce Product object
 * @param int            $amount  Decrement amount
 * @return bool|WC_Product
 */
function lmfwc_stock_decrease($product, $amount = 1) {
    return lmfwc_stock_modify($product,'decrease', $amount);
}

/**
 * Function used to modify the stock amount.
 *
 * @param int|WC_Product $product
 * @param string         $action
 * @param int            $amount
 * @return bool|WC_Product
 */
function lmfwc_stock_modify($product, $action, $amount = 1) {
    // Check if the setting is enabled
    if (!Settings::get('lmfwc_enable_stock_manager')) {
        return false;
    }

    // Retrieve the WooCommerce Product if we're given an ID
    if (is_numeric($product)) {
        $product = wc_get_product($product);
    }

    // No need to modify if WooCommerce is not managing the stock
    if (!$product instanceof WC_Product || !$product->managing_stock()) {
        return false;
    }

    // Retrieve the current stock
    $stock = $product->get_stock_quantity();

    // Normalize
    if ($stock === null) {
        $stock = 0;
    }

    // Add or subtract the given amount to the stock
    if ($action === 'increase') {
        $stock += $amount;
    } elseif ($action === 'decrease') {
        $stock -= $amount;
    }

    $stock = apply_filters('lmfwc_pre_manipulate_stock', $stock, $product, $action, $amount);

    // Set and save
    $product->set_stock_quantity($stock);
    $product->save();

    return $product;
}