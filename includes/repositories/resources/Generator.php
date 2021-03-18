<?php

namespace LicenseManagerForWooCommerce\Repositories\Resources;

use LicenseManagerForWooCommerce\Abstracts\ResourceRepository as AbstractResourceRepository;
use LicenseManagerForWooCommerce\Enums\ColumnType as ColumnTypeEnum;
use LicenseManagerForWooCommerce\Models\Resources\Generator as GeneratorResourceModel;

defined( 'ABSPATH' ) || exit;

class Generator extends AbstractResourceRepository {
	/**
	 * @var string
	 */
	const TABLE = 'lmfwc_generators';

	/**
	 * Country constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->table      = $wpdb->prefix . self::TABLE;
		$this->primaryKey = 'id';
		$this->model      = GeneratorResourceModel::class;
		$this->mapping    = array(
			'name'                => ColumnTypeEnum::VARCHAR,
			'charset'             => ColumnTypeEnum::VARCHAR,
			'chunks'              => ColumnTypeEnum::INT,
			'chunk_length'        => ColumnTypeEnum::INT,
			'times_activated_max' => ColumnTypeEnum::INT,
			'separator'           => ColumnTypeEnum::VARCHAR,
			'prefix'              => ColumnTypeEnum::VARCHAR,
			'suffix'              => ColumnTypeEnum::VARCHAR,
			'expires_in'          => ColumnTypeEnum::INT,
		);
	}
}
