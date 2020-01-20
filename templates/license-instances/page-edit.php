<?php
    use LicenseManagerForWooCommerce\Models\Resources\LicenseInstance as LicenseInstanceResourceModel;
    use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;

    defined('ABSPATH') || exit;

    /** @var LicenseInstanceResourceModel $licenseInstance */
	$licenseKey = '(error: associated license key not found)';
	$licenseId = $licenseInstance->getLicenseId();
	if ($licenseId) {
		/** @var LicenseResourceRepository $license */
		$license = LicenseResourceRepository::instance()->find($licenseId);
		if ($license) {
			$licenseKey = $license->getDecryptedLicenseKey();
		}
	}
?>

<h1 class="wp-heading-inline"><?php esc_html_e('Edit license instance key', 'lmfwc'); ?></h1>
<hr class="wp-header-end">

<form method="post" action="<?php echo admin_url('admin-post.php');?>">
    <input type="hidden" name="source" value="<?php echo esc_html($license->getSource()); ?>">
    <input type="hidden" name="action" value="lmfwc_update_license_instance_key">
    <?php wp_nonce_field('lmfwc_update_license_instance_key'); ?>

    <table class="form-table">
        <tbody>
            <tr scope="row">
                <th scope="row"><label for="edit__instance_id"><?php esc_html_e('ID', 'lmfwc');?></label></th>
                <td>
                    <input name="instance_id" id="edit__instance_id" class="regular-text" type="text" value="<?php echo esc_html($licenseInstance->getId()); ?>" readonly>
                </td>
            </tr>

            <!-- INSTANCE KEY -->
            <tr scope="row">
                <th scope="row"><label for="edit__instance_key"><?php esc_html_e('Instance key', 'lmfwc');?></label></th>
                <td>
                    <input name="instance_key" id="edit__instance_key" class="regular-text" type="text" value="<?php echo esc_html($instanceKey); ?>">
                    <p class="description"><?php esc_html_e('The license instance key will be encrypted before it is stored inside the database.', 'lmfwc');?></p>
                </td>
            </tr>

            <!-- LICENSE -->
            <tr scope="row">
                <th scope="row"><label for="edit__license"><?php esc_html_e('License key', 'lmfwc');?></label></th>
                <td>
					<input name="license_key" id="single__license" class="regular-text" type="text" value="<?php echo esc_html($licenseKey); ?>">
                    <p class="description"><?php esc_html_e('The license to which the license instance key will be assigned.', 'lmfwc');?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <p class="submit">
        <input name="submit" id="edit__submit" class="button button-primary" value="<?php esc_html_e('Save' ,'lmfwc');?>" type="submit">
    </p>
</form>
