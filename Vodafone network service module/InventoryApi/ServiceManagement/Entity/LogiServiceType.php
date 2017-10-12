<?php

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 23/02/2016
 * Time: 3:00 PM
 */
namespace InventoryApi\ServiceManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ServiceType
 *
 * @ORM\Table(name="LOGI_SERVICE_TYPE")
 * @ORM\Entity
 */
class LogiServiceType
{

    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_TYPE_ID", type="integer", nullable=false)
     * @ORM\Id
     */
    public $serviceTypeId;// depreciated attribute to copy id from log entities :  * @ORM\GeneratedValue(strategy="SEQUENCE") * @ORM\SequenceGenerator(sequenceName="LOGI_SERVICE_TYPE_ID_seq", allocationSize=1, initialValue=1)


    /**
     * @var string
     *
     * @ORM\Column(name="SERVICE_TYPE_NAME",type="string", length=45, nullable=true)
     */
    public $serviceTypeName;


    /**
     * @ORM\OneToMany(targetEntity="Service", mappedBy="ServiceType")
     */
    //private $services;


    /**
     * @ORM\Column(name="SERVICE_TYPE_DISCOVERY_KEYS",type="string", length=200, nullable=true)
     */
    public $serviceTypeDiscoveryKeys;


    /**
     * @ORM\Column(name="SERVICE_TYPE_HAND_OVER_KEYS",type="string", length=200, nullable=true)
     */
    public $serviceTypeHandoverKeys;


    /**
     * @ORM\Column(name="SERVICE_TYPE_MAIN_KEY_NAME",type="string", length=20, nullable=true)
     */
    public $serviceTypeMainKeyName;

    /**
     * Set serviceTypeId
     *
     * @param string
     * @return LogiServiceType
     */
    public function setServiceTypeId($serviceTypeId)
    {
        $this->serviceTypeId = $serviceTypeId;

        return $this;
    }

    /**
     * Get serviceTypeId
     *
     * @return integer
     */
    public function getServiceTypeId()
    {
        return $this->serviceTypeId;
    }


    /**
     * Set serviceTypeName
     *
     * @param string $serviceTypeName
     * @return LogiServiceType
     */
    public function setServiceTypeName($serviceTypeName)
    {
        $this->serviceTypeName = $serviceTypeName;

        return $this;
    }

    /**
     * Get serviceTypeName
     *
     * @return string
     */
    public function getServiceTypeName()
    {
        return $this->serviceTypeName;
    }

    /**
     * Set serviceTypeDiscoveryKeys
     *
     * @param string $serviceTypeDiscoveryKeys
     * @return LogiServiceType
     */
    public function setServiceTypeDiscoveryKeys($serviceTypeDiscoveryKeys)
    {
        $this->serviceTypeDiscoveryKeys = $serviceTypeDiscoveryKeys;

        return $this;
    }

    /**
     * Get serviceTypeDiscoveryKeys
     *
     * @return string
     */
    public function getServiceTypeDiscoveryKeys()
    {
        return $this->serviceTypeDiscoveryKeys;
    }

    /**
     * Set serviceTypeHandOverKeys
     *
     * @param string $serviceTypeHandoverKeys
     * @return LogiServiceType
     */
    public function setServiceTypeHandoverKeys($serviceTypeHandoverKeys)
    {
        $this->serviceTypeHandoverKeys = $serviceTypeHandoverKeys;

        return $this;
    }

    /**
     * Get serviceTypeHandoverKeys
     *
     * @return string
     */
    public function getServiceTypeHandoverKeys()
    {
        return $this->serviceTypeHandoverKeys;
    }


    /**
     * Set serviceTypeMainKeyName
     *
     * @param string $serviceTypeMainKeyName
     * @return LogiServiceType
     */
    public function setServiceTypeMainKeyName($serviceTypeMainKeyName)
    {
        $this->serviceTypeMainKeyName = $serviceTypeMainKeyName;

        return $this;
    }

    /**
     * Get serviceTypeMainKeyName
     *
     * @return string
     */
    public function getServiceTypeMainKeyName()
    {
        return $this->serviceTypeMainKeyName;
    }
}