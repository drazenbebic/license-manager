<?php

namespace LicenseManagerForWooCommerce\Repositories\Resources;

use stdClass;
use LicenseManagerForWooCommerce\Abstracts\ResourceRepository as AbstractResourceRepository;
use LicenseManagerForWooCommerce\Interfaces\ResourceRepository as ResourceRepositoryInterface;
use LicenseManagerForWooCommerce\Models\Resources\LicenseInstance as LicenseInstanceResourceModel;

defined('ABSPATH') || exit;

class LicenseInstance extends AbstractResourceRepository implements ResourceRepositoryInterface
{
    /**
     * @var string
     */
    const TABLE = 'lmfwc_licenses_instances';

    /**
     * Country constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->table      = $wpdb->prefix . self::TABLE;
        $this->primaryKey = 'id';
    }

    /**
     * @param stdClass $dataObject
     *
     * @return mixed|LicenseInstanceResourceModel
     */
    public function createResourceModel($dataObject)
    {
        return new LicenseInstanceResourceModel($dataObject);
    }
}