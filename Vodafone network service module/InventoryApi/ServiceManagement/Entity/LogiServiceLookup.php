<?php

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 1/03/2016
 * Time: 12:18 PM
 */

namespace InventoryApi\ServiceManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Service
 *
 * @ORM\Table(name="LOGI_SERVICE_LOOKUP")
 * @ORM\Entity
 */
class LogiServiceLookup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_LOOKUP_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="LOGI_SERVICE_LOOKUP_ID_seq", allocationSize=1, initialValue=1)
     */
    private $serviceLookupId;


    /**
     * @var \LogiServiceType
     *
     * @ORM\ManyToOne(targetEntity="LogiServiceType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SERVICE_TYPE_ID", referencedColumnName="SERVICE_TYPE_ID")
     * })
     */
    private $serviceType;


    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_LOOKUP_KEY_ID", type="integer",  nullable=false)
     */
    private $serviceLookupKeyId;//0=> service_id ,1=>interface_id,2=>active


    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_LOOKUP_KEY_VALUE", type="integer",  nullable=false)
     */
    private $serviceLookupKeyValue;//values of above key type


    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_LOOKUP_VALUE", type="integer",  nullable=false)
     */
    private $serviceLookupValue;//is usually a logical configuration


    /**
     * Get serviceLookupId
     *
     * @return integer
     */
    public function getServiceLookupId()
    {
        return $this->serviceLookupId;
    }

    /**
     * Set serviceLookupId
     *
     * @param integer $serviceLookupId
     * @return LogiServiceLookup
     */
    public function setServiceLookupId($serviceLookupId)
    {
        $this->serviceLookupId = $serviceLookupId;

        return $this;
    }
}
