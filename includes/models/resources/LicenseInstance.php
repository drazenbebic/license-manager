<?php

namespace LicenseManagerForWooCommerce\Models\Resources;

use LicenseManagerForWooCommerce\Abstracts\ResourceModel as AbstractResourceModel;
use LicenseManagerForWooCommerce\Interfaces\Model as ModelInterface;
use stdClass;

defined('ABSPATH') || exit;

class LicenseInstance extends AbstractResourceModel implements ModelInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $licenseID;

    /**
     * @var string
     */
    protected $instanceKey;

    /**
     * @var string
     */
    protected $instanceHash;

    /**
     * @var string
     */
    protected $createdAt;

    /**
     * @var int
     */
    protected $createdBy;

    /**
     * @var string
     */
    protected $updatedAt;

    /**
     * @var int
     */
    protected $updatedBy;

    /**
     * License constructor.
     *
     * @param stdClass $license
     */
    public function __construct($licenseinstance)
    {
        if (!$licenseinstance instanceof stdClass) {
            return;
        }

        $this->id                = $licenseinstance->id;
        $this->licenseID         = $licenseinstance->license_id;
        $this->instanceKey       = $licenseinstance->instance_key;
        $this->instanceHash      = $licenseinstance->instance_hash;
        $this->createdAt         = $licenseinstance->created_at;
        $this->createdBy         = $licenseinstance->created_by;
        $this->updatedAt         = $licenseinstance->updated_at;
        $this->updatedBy         = $licenseinstance->updated_by;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getLicenseID()
    {
        return $this->licenseID;
    }

    /**
     * @param int $licenseID
     */
    public function setLicenseID($licenseID)
    {
        $this->licenseID = $licenseID;
    }

    /**
     * @return string
     */
    public function getInstanceKey()
    {
        return $this->instanceKey;
    }

    /**
     * @param string $instanceKey
     */
    public function setInstanceKey($instanceKey)
    {
        $this->instanceKey = $instanceKey;
    }

    /**
     * @return string
     */
    public function getDecryptedLicenseInstanceKey()
    {
        return apply_filters('lmfwc_decrypt', $this->instanceKey);
    }

    /**
     * @return string
     */
    public function getInstanceHash()
    {
        return $this->instanceHash;
    }

    /**
     * @param string $instanceHash
     */
    public function setInstanceHash($instanceHash)
    {
        $this->instanceHash = $hash;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param int $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param string $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return int
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @param int $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }
}