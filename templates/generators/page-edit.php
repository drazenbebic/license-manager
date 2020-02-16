<?php

use LicenseManagerForWooCommerce\Models\Resources\Generator as GeneratorResourceModel;

defined('ABSPATH') || exit;

/** @var GeneratorResourceModel $generator */

?>

<h1 class="wp-heading-inline"><?php esc_html_e('Edit generator', 'license-manager-for-woocommerce'); ?></h1>
<hr class="wp-header-end">

<?php if ($products): ?>
    <p><?php esc_html_e('This generator is assigned to the following product(s)', 'license-manager-for-woocommerce');?>:</p>

    <ul>
        <?php foreach ($products as $product): ?>
            <li>
                <a href="<?php esc_html_e(get_edit_post_link($product->get_id()));?>">
                    <span><?php esc_html_e($product->get_name());?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="lmfwc_update_generator">
    <input type="hidden" name="id" value="<?php echo esc_html(absint($_GET['id']));?>">
    <?php wp_nonce_field('lmfwc_update_generator'); ?>

    <table class="form-table">
        <tbody>
            <!-- NAME -->
            <tr scope="row">
                <th scope="row">
                    <label for="name"><?php esc_html_e('Name', 'license-manager-for-woocommerce');?></label>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="name" id="name" class="regular-text" type="text" value="<?php echo esc_html($generator->getName()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'license-manager-for-woocommerce');?></b>
                        <span><?php esc_html_e('A short name to describe the generator.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>

            <!-- TIMES ACTIVATED MAX -->
            <tr scope="row">
                <th scope="row"><label><?php esc_html_e('Maximum activation count', 'license-manager-for-woocommerce');?></label></th>
                <td>
                    <input name="times_activated_max" id="times_activated_max" class="regular-text" type="number" value="<?php echo esc_html($generator->getTimesActivatedMax()); ?>">
                    <p class="description" id="tagline-description"><?php esc_html_e('Define how many times the license key can be marked as "activated" by using the REST API. Leave blank if you do not use the API.', 'license-manager-for-woocommerce');?></p>
                </td>
            </tr>

            <!-- CHARSET -->
            <tr scope="row">
                <th scope="row">
                    <label for="charset"><?php esc_html_e('Character map', 'license-manager-for-woocommerce');?></label>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="charset" id="charset" class="regular-text" type="text" value="<?php echo esc_html($generator->getCharset()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'license-manager-for-woocommerce');?></b>
                        <span><?php _e('The characters which will be used for generating a license key, i.e. for <code>12-AB-34-CD</code> the character map is <code>ABCD1234</code>.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>

            <!-- NUMBER OF CHUNKS -->
            <tr scope="row">
                <th scope="row">
                    <label for="chunks"><?php esc_html_e('Number of chunks', 'license-manager-for-woocommerce');?></label>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="chunks" id="chunks" class="regular-text" type="text" value="<?php echo esc_html($generator->getChunks()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'license-manager-for-woocommerce');?></b>
                        <span><?php _e('The number of separated character sets, i.e. for <code>12-AB-34-CD</code> the number of chunks is <code>4</code>.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>

            <!-- CHUNK LENGTH -->
            <tr scope="row">
                <th scope="row">
                    <label for="chunk_length"><?php esc_html_e('Chunk length', 'license-manager-for-woocommerce');?></label>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="chunk_length" id="chunk_length" class="regular-text" type="text" value="<?php echo esc_html($generator->getChunkLength()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'license-manager-for-woocommerce');?></b>
                        <span><?php _e('The character length of an individual chunk, i.e. for <code>12-AB-34-CD</code> the chunk length is <code>2</code>.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>

            <!-- SEPARATOR -->
            <tr scope="row">
                <th scope="row"><label for="separator"><?php esc_html_e('Separator', 'license-manager-for-woocommerce');?></label></th>
                <td>
                    <input name="separator" id="separator" class="regular-text" type="text" value="<?php echo esc_html($generator->getSeparator()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'license-manager-for-woocommerce');?></b>
                        <span><?php _e('The special character separating the individual chunks, i.e. for <code>12-AB-34-CD</code> the separator is <code>-</code>.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>

            <!-- PREFIX -->
            <tr scope="row">
                <th scope="row"><label for="prefix"><?php esc_html_e('Prefix', 'license-manager-for-woocommerce');?></label></th>
                <td>
                    <input name="prefix" id="prefix" class="regular-text" type="text" value="<?php echo esc_html($generator->getPrefix()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'license-manager-for-woocommerce');?></b>
                        <span><?php _e('Adds a character set at the start of a license key (separator <b>not</b> included), i.e. for <code>PRE-12-AB-34-CD</code> the prefix is <code>PRE-</code>.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>

            <!-- SUFFIX -->
            <tr scope="row">
                <th scope="row"><label for="suffix"><?php esc_html_e('Suffix', 'license-manager-for-woocommerce');?></label></th>
                <td>
                    <input name="suffix" id="suffix" class="regular-text" type="text" value="<?php echo esc_html($generator->getSuffix()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'license-manager-for-woocommerce');?></b>
                        <span><?php _e('Adds a character set at the end of a license key (separator <b>not</b> included), i.e. for <code>12-AB-34-CD-SUF</code> the suffix is <code>-SUF</code>.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>

            <!-- EXPIRES IN -->
            <tr scope="row">
                <th scope="row"><label for="expires_in"><?php esc_html_e('Expires in', 'license-manager-for-woocommerce');?></label></th>
                <td>
                    <input name="expires_in" id="expires_in" class="regular-text" type="text" value="<?php echo esc_html($generator->getExpiresIn()); ?>">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'license-manager-for-woocommerce');?></b>
                        <span><?php esc_html_e('The number of days for which the license key is valid after purchase. Leave blank if it doesn\'t expire.', 'license-manager-for-woocommerce');?></span>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>

    <?php submit_button(__('Update', 'license-manager-for-woocommerce')); ?>

</form>
