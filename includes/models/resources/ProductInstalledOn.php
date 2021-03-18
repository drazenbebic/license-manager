<?php

namespace LicenseManagerForWooCommerce\Models\Resources;

use LicenseManagerForWooCommerce\Abstracts\ResourceModel as AbstractResourceModel;
use LicenseManagerForWooCommerce\Interfaces\Model as ModelInterface;
use stdClass;

defined( 'ABSPATH' ) || exit;

class ProductInstalledOn extends AbstractResourceModel implements ModelInterface {
	/**
	 * @var int
	 */
	protected $id;

	/**
	 * @var int
	 */
	protected $licenseId;

	/**
	 * @var string
	 */
	protected $host;

	/**
	 * @var string
	 */
	protected $lastPing;

	/**
	 * ProductInstalledOn constructor.
	 *
	 * @param $productInstalledOn
	 */
	public function __construct( $productInstalledOn ) {
		if ( ! $productInstalledOn instanceof stdClass ) {
			return;
		}

		$this->id        = $productInstalledOn->id === null ? null : (int) $productInstalledOn->id;
		$this->licenseId = $productInstalledOn->license_id === null ? null : (int) $productInstalledOn->license_id;
		$this->host      = $productInstalledOn->host;
		$this->lastPing  = $productInstalledOn->last_ping;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId( $id ) {
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getLicenseId() {
		return $this->licenseId;
	}

	/**
	 * @param $licenseId
	 */
	public function setLicenseId( $licenseId ) {
		$this->licenseId = $licenseId;
	}

	/**
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @param $host
	 */
	public function setHost( $host ) {
		$this->host = $host;
	}

	/**
	 * @return string
	 */
	public function getLastPing() {
		return $this->lastPing;
	}

	/**
	 * @param $lastPing
	 */
	public function setLastPing( $lastPing ) {
		$this->lastPing = $lastPing;
	}
}
