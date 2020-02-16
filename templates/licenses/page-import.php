<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php esc_html_e('Add license keys in bulk', 'license-manager-for-woocommerce'); ?></h1>
<hr class="wp-header-end">

<form method="post" action="<?php echo esc_html(admin_url('admin-post.php')) ;?>" enctype="multipart/form-data">
    <input type="hidden" name="action" value="lmfwc_import_license_keys">
    <?php wp_nonce_field('lmfwc_import_license_keys'); ?>

    <table class="form-table">
        <tbody>
        <!-- SOURCE -->
        <tr class="row">
            <th class="row"><?php esc_html_e('Source', 'license-manager-for-woocommerce'); ?></th>
            <td>
                <label style="display: block; margin-bottom: 1em;">
                    <input type="radio" id="bulk__type_file" class="bulk__type regular-text" name="source" value="file" checked="checked">
                    <span><?php _e('File upload', 'license-manager-for-woocommerce'); ?></span>
                </label>
                <label style="display: block;">
                    <input type="radio" id="bulk__type_clipboard" class="bulk__type regular-text" name="source" value="clipboard">
                    <span><?php _e('Clipboard', 'license-manager-for-woocommerce'); ?></span>
                </label>
                <p class="description" style="margin-top: 1em;"><?php _e('You can either upload a file containing the license keys, or copy-paste them into a text field.', 'license-manager-for-woocommerce'); ?></p>
            </td>
        </tr>

        <!-- FILE -->
        <tr scope="row" id="bulk__source_file" class="bulk__source_row">
            <th scope="row"><label for="bulk__file"><?php esc_html_e('File', 'license-manager-for-woocommerce'); ?> <kbd>CSV</kbd> <kbd>TXT</kbd></label></th>
            <td>
                <input name="file" id="bulk__file" class="regular-text" type="file" accept=".csv,.txt">
                <p class="description">
                    <b class="text-danger"><?php esc_html_e('Important', 'license-manager-for-woocommerce'); ?>:</b>
                    <span><?php esc_html_e('One line per license key.', 'license-manager-for-woocommerce');?></span>
                </p>
            </td>
        </tr>

        <!-- Clipboard -->
        <tr scope="row" id="bulk__source_clipboard" class="bulk__source_row hidden">
            <th scope="row"><label for="bulk__clipboard"><?php esc_html_e('License keys', 'license-manager-for-woocommerce'); ?></label></th>
            <td>
                <textarea name="clipboard" id="bulk__clipboard" cols="49" rows="10" ></textarea>
                <p class="description">
                    <b class="text-danger"><?php esc_html_e('Important', 'license-manager-for-woocommerce'); ?>:</b>
                    <span><?php esc_html_e('One line per license key.', 'license-manager-for-woocommerce');?></span>
                </p>
            </td>
        </tr>

        <!-- VALID FOR -->
        <tr scope="row">
            <th scope="row"><label for="bulk__valid_for"><?php esc_html_e('Valid for (days)', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <input name="valid_for" id="bulk__valid_for" class="regular-text" type="text">
                <p class="description"><?php esc_html_e('Number of days for which the license key is valid after purchase. Leave blank if the license key does not expire.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>

        <!-- TIMES ACTIVATED MAX -->
        <tr scope="row">
            <th scope="row"><label for="bulk__times_activated_max"><?php esc_html_e('Maximum activation count', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <input name="times_activated_max" id="bulk__times_activated_max" class="regular-text" type="number">
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
            <th scope="row"><label for="bulk__order"><?php esc_html_e('Order', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <select name="order_id" id="bulk__order" class="regular-text"></select>
                <p class="description"><?php esc_html_e('The order to which the license keys will be assigned.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>

        <!-- PRODUCT -->
        <tr scope="row">
            <th scope="row"><label for="bulk__product"><?php esc_html_e('Product', 'license-manager-for-woocommerce');?></label></th>
            <td>
                <select name="product_id" id="bulk__product" class="regular-text"></select>
                <p class="description"><?php esc_html_e('The product to which the license keys will be assigned.', 'license-manager-for-woocommerce');?></p>
            </td>
        </tr>
        </tbody>
    </table>

    <p class="submit">
        <input name="submit" id="bulk__submit" class="button button-primary" value="<?php esc_html_e('Import' ,'license-manager-for-woocommerce');?>" type="submit">
    </p>
</form>
