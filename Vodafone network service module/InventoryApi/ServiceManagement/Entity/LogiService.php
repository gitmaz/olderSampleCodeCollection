<?php

namespace InventoryApi\ServiceManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Service
 *
 * @ORM\Table(name="LOGI_SERVICE")
 * @ORM\Entity
 */
class LogiService
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_ID", type="float", nullable=false)
     * @ORM\Id
     */
    private $serviceId;

    /**
     * @var string
     *
     * @ORM\Column(name="SERVICE_NAME", type="string", length=45, nullable=true)
     */
    private $serviceName;


    /*
     *  @var integer
     *
     *  @ORM\Column(name="SERVICE_TYPE_ID", type="integer", nullable=true)
     */
    private $serviceTypeId;


    /**
     * @var string
     *
     * @ORM\Column(name="SERVICE_DESCRIPTOR", type="string", length=100, nullable=true)
     */
    private $serviceDescriptor;


    //this comment is for making git realize we are changed( it was keeping the corrupted file after merge)
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CREATED", type="datetime", nullable=true)
     */
    private $created;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="LAST_CHANGED", type="datetime", nullable=true)
     */
    private $lastChanged;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="LAST_UPDATED", type="datetime", nullable=true)
     */
    private $lastUpdated;


    /**
     * Get serviceId
     *
     * @return integer
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * Set serviceId
     *
     * @param integer $serviceId
     * @return LogiService
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;

        return $this;
    }

    /**
     * Get serviceName
     *
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * Set serviceName
     *
     * @param string $serviceName
     * @return LogiService
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Get serviceDescriptor
     *
     * @return string
     */
    public function getServiceDescriptor()
    {
        return $this->serviceDescriptor;
    }

    /**
     * Set serviceDescriptor
     *
     * @param string $serviceDescriptor
     * @return LogiService
     */
    public function setServiceDescriptor($serviceDescriptor)
    {
        $this->serviceDescriptor = $serviceDescriptor;

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
     * Set $serviceTypeId
     *
     * @param integer $serviceTypeId
     * @return LogiService
     */
    public function setServiceTypeId($serviceTypeId)
    {
        //echo $serviceTypeId;
        $this->serviceTypeId = $serviceTypeId;

        return $this;
    }

    /**
     * Get lastChanged
     *
     * @return \DateTime
     */
    public function getLastChanged()
    {
        return $this->lastChanged;
    }

    /**
     * Set lastChanged
     *
     * @param \DateTime $lastChanged
     * @return LogiService
     */
    public function setLastChanged($lastChanged)
    {
        $this->lastChanged = $lastChanged;

        return $this;
    }

    /**
     * Get lastUpdated
     *
     * @return \DateTime
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    /**
     * Set lastUpdated
     *
     * @param \DateTime $lastUpdated
     * @return LogiService
     */
    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }


}
