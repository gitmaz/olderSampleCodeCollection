<?php

namespace InventoryApi\ServiceManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ServiceHistory
 *
 * @ORM\Table(name="LOGI_SERVICE_HISTORY")
 * @ORM\Entity
 */
class LogiServiceHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="LOGI_SERVICE_HISTORY_ID_seq", allocationSize=1, initialValue=1)
     */
    private $serviceId;

    /**
     * @var string
     *
     * @ORM\Column(name="SERVICE_NAME", type="string", length=45, nullable=true)
     */
    private $serviceName;

    /**
     * @var \LogiServiceType
     *
     * @ORM\ManyToOne(targetEntity="LogiServiceType",inversedBy="services")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SERVICE_TYPE_ID", referencedColumnName="SERVICE_TYPE_ID")
     * })
     */
    private $serviceType;

    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_PHYSICAL_ID", type="integer",  nullable=false)
     */
    private $servicePhysicalId;

    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_OBJECT_ID", type="integer",  nullable=false)
     */
    private $serviceObjectId;

    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_AS_IS", type="integer",  nullable=false)
     */
    private $serviceAsIs;

    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_PROPOSED_DECOMMISSION", type="integer", nullable=true)
     */
    private $serviceProposedDecommission;

    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_ASSOCIATE_EVENT_ID", type="integer", nullable=true)
     */
    private $serviceAssociateEventId;


    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_IMPACTED_EVENT_ID", type="integer", nullable=true)
     */
    private $serviceImpactedEventId;


    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_SOURCE_ID", type="integer", nullable=true)
     */
    private $serviceSourceId;


    /**
     * @var integer
     *
     * @ORM\Column(name="SERVICE_IS_UNDONE", type="integer", nullable=true)
     */
    private $serviceIsUndone;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="SERVICE_HISTORY_CREATED", type="datetime", nullable=true)
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
     * Set serviceName
     *
     * @param string $serviceName
     * @return Service
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;

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
     * Set serviceObjectId
     *
     * @param integer $serviceObjectId
     * @return LogiServiceHistory
     */
    public function setServiceObjectId($serviceObjectId)
    {
        $this->serviceObjectId = $serviceObjectId;

        return $this;
    }

    /**
     * Get serviceObjectId
     *
     * @return integer
     */
    public function getServiceObjectId()
    {
        return $this->serviceObjectId;
    }

    /**
     * Set associateEventId
     *
     * @param integer $associateEventId
     * @return LogiServiceHistory
     */
    public function setAssociateEventId($associateEventId)
    {
        $this->serviceAssociateEventId = $associateEventId;

        return $this;
    }

    /**
     * Get associateEventId
     *
     * @return integer
     */
    public function getAssociateEventId()
    {
        return $this->serviceAssociateEventId;
    }

    /**
     * Set asIs
     *
     * @param string $asIs
     * @return LogiServiceHistory
     */
    public function setAsIs($asIs)
    {
        $this->asIs = $asIs;

        return $this;
    }

    /**
     * Get asIs
     *
     * @return string
     */
    public function getAsIs()
    {
        return $this->serviceAsIs;
    }

    /**
     * Set serviceHistoryCreated
     *
     * @param \DateTime $Created
     * @return LogiServiceHistory
     */
    public function setServiceHistoryCreated($serviceHistoryCreated)
    {
        $this->Created = $serviceHistoryCreated;

        return $this;
    }

    /**
     * Get serviceHistoryCreated
     *
     * @return \DateTime
     */
    public function getServiceHistoryCreated()
    {
        return $this->Created;
    }

    /**
     * Set serviceId
     *
     * @param integer $serviceId
     * @return LogiServiceHistory
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;

        return $this;
    }

    /**
     * Set serviceModelId
     *
     * @param integer $serviceTypeId
     * @return LogiServiceHistory
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
     * Set servicePhysicalId
     *
     * @param integer $servicePhysicalId
     * @return LogiServiceHistory
     */
    public function setServicePhysicalId($servicePhysicalId)
    {
        $this->servicePhysicalId = $servicePhysicalId;

        return $this;
    }

    /**
     * Get servicePhysicalId
     *
     * @return integer
     */
    public function getServicePhysicalId()
    {
        return $this->servicePhysicalId;
    }

    /**
     * Set impactedEventId
     *
     * @param integer $impactedEventId
     * @return LogiServiceHistory
     */
    public function setImpactedEventId($impactedEventId)
    {
        $this->serviceImpactedEventId = $impactedEventId;

        return $this;
    }

    /**
     * Get impactedEventId
     *
     * @return integer
     */
    public function getImpactedEventId()
    {
        return $this->serviceImpactedEventId;
    }


    /**
     * Set lastChanged
     *
     * @param \DateTime $lastChanged
     * @return LogiServiceHistory
     */
    public function setLastChanged($lastChanged)
    {
        $this->lastChanged = $lastChanged;

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
     * Set lastUpdated
     *
     * @param \DateTime $lastUpdated
     * @return LogiServiceHistory
     */
    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;

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
     * Set proposedDecommission
     *
     * @param integer $proposedDecommission
     * @return LogiServiceHistory
     */
    public function setProposedDecommission($proposedDecommission)
    {
        $this->serviceProposedDecommission = $proposedDecommission;

        return $this;
    }

    /**
     * Get proposedDecommission
     *
     * @return integer
     */
    public function getProposedDecommission()
    {
        return $this->serviceProposedDecommission;
    }


    /**
     * Set sourceId
     *
     * @param integer $sourceId
     * @return LogiServiceHistory
     */
    public function setSourceId($sourceId)
    {
        $this->serviceSourceId = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->serviceSourceId;
    }


    /**
     * Set isUndone
     *
     * @param integer $isUndone
     * @return LogiServiceHistory
     */
    public function setIsUndone($isUndone)
    {
        $this->serviceIsUndone = $isUndone;

        return $this;
    }

    /**
     * Get isUndone
     *
     * @return integer
     */
    public function getIsUndone()
    {
        return $this->serviceIsUndone;
    }

}
