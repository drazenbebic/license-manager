<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php esc_html_e('Add a single license instance key', 'lmfwc'); ?></h1>
<hr class="wp-header-end">

<form method="post" action="<?php echo esc_html(admin_url('admin-post.php'));?>">
    <input type="hidden" name="action" value="lmfwc_add_license_instance_key">
    <?php wp_nonce_field('lmfwc_add_license_instance_key'); ?>

    <table class="form-table">
        <tbody>
        <!-- INSTANCE KEY -->
        <tr scope="row">
            <th scope="row"><label for="single__instance_key"><?php esc_html_e('Instance key', 'lmfwc');?></label></th>
            <td>
                <input name="instance_key" id="single__instance_key" class="regular-text" type="text">
                <p class="description"><?php esc_html_e('The license instance key will be encrypted before it is stored inside the database.', 'lmfwc');?></p>
            </td>
        </tr>

        <!-- LICENSE -->
        <tr scope="row">
            <th scope="row"><label for="single__license"><?php esc_html_e('License key', 'lmfwc');?></label></th>
            <td>
                <input name="license_key" id="single__license" class="regular-text" type="text">
                <p class="description"><?php esc_html_e('The license key to which the instance key will be activated.', 'lmfwc');?></p>
            </td>
        </tr>
        </tbody>
    </table>

    <p class="submit">
        <input name="submit" id="single__submit" class="button button-primary" value="<?php esc_html_e('Add' ,'lmfwc');?>" type="submit">
    </p>
</form>
