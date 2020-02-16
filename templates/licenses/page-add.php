<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php esc_html_e('Add a single license key', 'license-manager-for-woocommerce'); ?></h1>
<hr class="wp-header-end">

<form method="post" action="<?php echo esc_html(admin_url('admin-post.php'));?>">
    <input type="hidden" name="action" value="lmfwc_add_license_key">
    <?php wp_nonce_field('lmfwc_add_license_key'); ?>

    <table class="form-table">
        <tbody>
        <!-- LICENSE KEY -->
        <tr scope="row">
            <th scope="row"><label for="single__license_key"><?php esc_html_e('License key', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <input name="license_key" id="single__license_key" class="regular-text" type="text">
                <p class="description"><?php esc_html_e('The license key will be encrypted before it is stored inside the database.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>

        <!-- VALID FOR -->
        <tr scope="row">
            <th scope="row"><label for="single__valid_for"><?php esc_html_e('Valid for (days)', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <input name="valid_for" id="single__valid_for" class="regular-text" type="text">
                <p class="description"><?php esc_html_e('Number of days for which the license key is valid after purchase. Leave blank if the license key does not expire. Cannot be used at the same time as the "Expires at" field.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>

        <!-- EXPIRES AT -->
        <tr scope="row">
            <th scope="row"><label for="single__expires_at"><?php esc_html_e('Expires at', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <input name="expires_at" id="single__expires_at" class="regular-text" type="text">
                <p class="description"><?php esc_html_e('The exact date this license key expires on. Leave blank if the license key does not expire. Cannot be used at the same time as the "Valid for (days)" field.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>

        <!-- TIMES ACTIVATED MAX -->
        <tr scope="row">
            <th scope="row"><label for="single__times_activated_max"><?php esc_html_e('Maximum activation count', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <input name="times_activated_max" id="single__times_activated_max" class="regular-text" type="number">
                <p class="description"><?php esc_html_e('Define how many times the license key can be marked as "activated" by using the REST API. Leave blank if you do not use the API.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>

        <!-- STATUS -->
        <tr scope="row">
            <th scope="row"><label for="edit__status"><?php esc_html_e('Status', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <select id="edit__status" name="status" class="regular-text">
                    <?php foreach($statusOptions as $option): ?>
                        <option value="<?php echo esc_html($option['value']); ?>"><?php echo esc_html($option['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <!-- ORDER -->
        <tr scope="row">
            <th scope="row"><label for="single__order"><?php esc_html_e('Order', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <select name="order_id" id="single__order" class="regular-text"></select>
                <p class="description"><?php esc_html_e('The order to which the license keys will be assigned.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>

        <!-- PRODUCT -->
        <tr scope="row">
            <th scope="row"><label for="single__product"><?php esc_html_e('Product', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <select name="product_id" id="single__product" class="regular-text"></select>
                <p class="description"><?php esc_html_e('The product to which the license keys will be assigned.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>
        </tbody>
    </table>

    <p class="submit">
        <input name="submit" id="single__submit" class="button button-primary" value="<?php esc_html_e('Add' ,'license-manager-for-woocommerce');?>" type="submit">
    </p>
</form>
