<?php

namespace LicenseManagerForWooCommerce\Repositories\Resources;

use LicenseManagerForWooCommerce\Abstracts\ResourceRepository as AbstractResourceRepository;
use LicenseManagerForWooCommerce\Enums\ColumnType;
use LicenseManagerForWooCommerce\Models\Resources\LicenseMeta as LicenseMetaResourceModel;

defined( 'ABSPATH' ) || exit;

class LicenseMeta extends AbstractResourceRepository {
	/**
	 * @var string
	 */
	const TABLE = 'lmfwc_licenses_meta';

	/**
	 * Country constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->table      = $wpdb->prefix . self::TABLE;
		$this->primaryKey = 'meta_id';
		$this->model      = LicenseMetaResourceModel::class;
		$this->mapping    = array(
			'license_id' => ColumnType::BIGINT,
			'meta_key'   => ColumnType::VARCHAR,
			'meta_value' => ColumnType::LONGTEXT,
		);
	}
}
