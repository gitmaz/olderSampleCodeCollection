<?php

namespace InventoryApi\ServiceManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogiServiceSegment
 *
 * @ORM\Table(name="LOGI_SERVICE_SEGMENT_HISTORY")
 * @ORM\Entity
 */
class LogiServiceSegmentHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="LOGI_SERVICE_SEGMENT_ID_seq", allocationSize=1, initialValue=1)
     */
    private $serviceSegmentId;


    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_SERVICE_ID", type="float",  nullable=false)
     */
    private $serviceId;//(foreignkey)


    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_LOGI_CONF1_ID", type="integer",  nullable=false)
     */
    private $logiConf1Id;

    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_LOGI_CONF2_ID", type="integer",  nullable=false)
     */
    private $logiConf2Id;

    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_PHYS_CON_ID", type="integer",  nullable=true)
     */
    private $physConId;

    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_LOG_CON_ID", type="float",  nullable=true)
     */
    private $logConId;


    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_PHYSICAL_ID", type="integer",  nullable=false)
     */
    private $segmentPhysicalId;

    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_OBJECT_ID", type="integer",  nullable=false)
     */
    private $segmentObjectId;

    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_AS_IS", type="integer",  nullable=false)
     */
    private $segmentAsIs;

    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_PROPOSED_DECOMMISSION", type="integer", nullable=true)
     */
    private $segmentProposedDecommission;

    /**
     * @var integer
     *
     * @ORM\Column(name="ASSOCIATE_EVENT_ID", type="integer", nullable=true)
     */
    private $segmentAssociateEventId;


    /**
     * @var integer
     *
     * @ORM\Column(name="IMPACTED_EVENT_ID", type="integer", nullable=true)
     */
    private $segmentImpactedEventId;


    /**
     * @var integer
     *
     * @ORM\Column(name="SOURCE_ID", type="integer", nullable=true)
     */
    private $segmentSourceId;

    /**
     * @var integer
     *
     * @ORM\Column(name="IS_UNDONE", type="integer", nullable=true)
     */
    private $segmentIsUndone;


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


    /* todo: depreciate in favor of online forming of names from service table
     * @var string
     *
     * @ORM\Column(name="SEGMENT_PHYS_CON_NAME", type="string", length=100, nullable=true)
     *
    private $physConName;
*/

    //todo: depreciate in favor of online forming of names from service table
    /**
     * @var string
     *
     * @ORM\Column(name="SEGMENT_LOG_CON_NAME", type="string", length=100, nullable=true)
     */
    private $logConName;


    /**
     * Get serviceSegmentId
     *
     * @return integer
     */
    public function getServiceSegmentId()
    {
        return $this->serviceSegmentId;
    }

    /**
     * Get serviceSegmentId
     *
     * @return integer
     */
    public function setServiceSegmentId($serviceSegmentId)
    {
        $this->serviceSegmentId = $serviceSegmentId;
        return $this;
    }


    /**
     * Set serviceId
     *
     * @param double $segmentServiceId
     * @return LogiServiceSegment
     */
    public function setSegmentServiceId($segmentServiceId)
    {
        $this->serviceId = $segmentServiceId;

        return $this;
    }


    /**
     * Set serviceId
     *
     * @param double $segmentServiceId
     * @return LogiServiceSegment
     */
    public function getSegmentServiceId()
    {
        return $this->serviceId;

    }


    /**
     * get logiConf1Id
     *
     * @return integer logiConf1Id
     */
    public function getSegmentLogiConf1Id()
    {
        return $this->logiConf1Id;
    }


    /**
     * Set logiConf1Id
     *
     * @param integer $serviceLogiConf1Id
     * @return LogiServiceSegment
     */
    public function setSegmentLogiConf1Id($serviceLogiConf1Id)
    {
        $this->logiConf1Id = $serviceLogiConf1Id;

        return $this;
    }


    /**
     * get logiConf2Id
     *
     * @return integer logiConf2Id
     */
    public function getSegmentLogiConf2Id()
    {
        return $this->logiConf2Id;
    }


    /**
     * Set logiConf2Id
     *
     * @param integer $serviceLogiConf2Id
     * @return LogiServiceSegment
     */
    public function setSegmentLogiConf2Id($serviceLogiConf2Id)
    {
        $this->logiConf2Id = $serviceLogiConf2Id;

        return $this;
    }

    /**
     * get physConId
     *
     * @return integer $servicePhysConId
     */
    public function getSegmentPhysConId($physConId)
    {
        return $this->physConId;
    }

    /**
     * Set physConId
     *
     * @param integer $physConId
     * @return LogiServiceSegment
     */
    public function setSegmentPhysConId($physConId)
    {
        $this->physConId = $physConId;

        return $this;
    }

    /**
     * get logConId
     *
     * @return integer $logConId
     */
    public function getSegmentLogConId()
    {
        return $this->logConId;
    }

    /**
     * Set logConId
     *
     * @param integer $logConId
     * @return LogiServiceSegment
     */
    public function setSegmentLogConId($logConId)
    {
        $this->logConId = $logConId;

        return $this;
    }


    /**
     * Set SegmentObjectId
     *
     * @param integer $segmentObjectId
     * @return LogiSegment
     */
    public function setSegmentObjectId($segmentObjectId)
    {
        $this->segmentObjectId = $segmentObjectId;

        return $this;
    }

    /**
     * Get SegmentObjectId
     *
     * @return integer
     */
    public function getSegmentObjectId()
    {
        return $this->segmentObjectId;
    }

    /**
     * Set associateEventId
     *
     * @param integer $associateEventId
     * @return LogiSegment
     */
    public function setAssociateEventId($associateEventId)
    {
        $this->segmentAssociateEventId = $associateEventId;

        return $this;
    }

    /**
     * Get associateEventId
     *
     * @return integer
     */
    public function getAssociateEventId()
    {
        return $this->segmentAssociateEventId;
    }

    /**
     * Set asIs
     *
     * @param integer $asIs
     * @return LogiSegment
     */
    public function setAsIs($asIs)
    {
        $this->segmentAsIs = $asIs;

        return $this;
    }

    /**
     * Get asIs
     *
     * @return string
     */
    public function getAsIs()
    {
        return $this->segmentAsIs;
    }

    /**
     * Set SegmentCreated
     *
     * @param \DateTime $Created
     * @return LogiSegment
     */
    public function setSegmentCreated($segmentCreated)
    {
        $this->created = $segmentCreated;

        return $this;
    }

    /**
     * Get SegmentCreated
     *
     * @return \DateTime
     */
    public function getSegmentCreated()
    {
        return $this->created;
    }


    /**
     * Set servicePhysicalId
     *
     * @param integer $servicePhysicalId
     * @return LogiService
     */
    public function setSegmentPhysicalId($segmentPhysicalId)
    {
        $this->segmentPhysicalId = $segmentPhysicalId;

        return $this;
    }

    /**
     * Get servicePhysicalId
     *
     * @return integer
     */
    public function getSegmentPhysicalId()
    {
        return $this->segmentPhysicalId;
    }

    /**
     * Set impactedEventId
     *
     * @param integer $impactedEventId
     * @return LogiService
     */
    public function setImpactedEventId($impactedEventId)
    {
        $this->segmentImpactedEventId = $impactedEventId;

        return $this;
    }

    /**
     * Get impactedEventId
     *
     * @return integer
     */
    public function getImpactedEventId()
    {
        return $this->segmentImpactedEventId;
    }


    /**
     * Set proposedDecommission
     *
     * @param integer $proposedDecommission
     * @return LogiService
     */
    public function setProposedDecommission($proposedDecommission)
    {
        $this->segmentProposedDecommission = $proposedDecommission;
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
     * @return LogiService
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
     * @return LogiService
     */
    public function setIsUndone($isUndone)
    {
        $this->segmentIsUndone = $isUndone;
        return $this;
    }

    /**
     * Get isUndone
     *
     * @return integer
     */
    public function getIsUndone()
    {
        return $this->segmentIsUndone;
    }


    public function setLogConName($logConName)
    {

        $this->logConName = $logConName;
        return $this;
    }

    public function setLastChanged($lastChanged)
    {
        $this->lastChanged = $lastChanged;
        return $this;
    }

    public function getLastChanged()
    {
        return $this->lastChanged;
    }

    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    /**
     * Get Segment Via.
     *
     * @return string
     */
    public function getSegmentVia()
    {
        return $this->segmentVia;
    }

    /**
     * Set Segment Via.
     *
     * @param string $segmentVia
     *
     * @return object
     */
    public function setSegmentVia($segmentVia)
    {
        $this->segmentVia = $segmentVia;
        return $this;
    }


}
