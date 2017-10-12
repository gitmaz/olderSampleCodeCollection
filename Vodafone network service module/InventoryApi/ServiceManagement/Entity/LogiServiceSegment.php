<?php

namespace InventoryApi\ServiceManagement\Entity;

use Doctrine\ORM\Mapping as ORM;
use InventoryApi\Event\Entity\TimeMachineObjectInterface;

/**
 * LogiServiceSegment
 *
 * @ORM\Table(name="LOGI_SERVICE_SEGMENT")
 * @ORM\Entity
 */
class LogiServiceSegment implements TimeMachineObjectInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SEGMENT_ID", type="float", nullable=false)
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


    /**
     * @var string
     *
     * @ORM\Column(name="SEGMENT_PHYS_CON_NAME", type="string", length=100, nullable=true)
     */
    private $physConName;


    /**
     * @var string
     *
     * @ORM\Column(name="SEGMENT_LOG_CON_NAME", type="string", length=100, nullable=true)
     */
    private $logConName;


    /**
     * @var \Event
     *
     * @ORM\ManyToOne(targetEntity="\Event")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ASSOCIATE_EVENT_ID", referencedColumnName="EVENT_ID")
     * })
     */
    //this is quick fix to mutual exclusiveness of associateEvent and associateEventId
    //private $associateEvent;

    /**
     * @var \Event
     *
     * @ORM\ManyToOne(targetEntity="\Event")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="IMPACTED_EVENT_ID", referencedColumnName="EVENT_ID")
     * })
     */
    private $impactedEvent;

    /**
     * @var string
     *
     * @ORM\Column(name="SEGMENT_VIA", type="string", length=100, nullable=true)
     */
    private $segmentVia;


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
    public function getSegmentPhysConId()
    {
        return $this->physConId;
    }

    /**
     * Set physConId
     *
     * @param integer $servicePhysConId
     * @return LogiServiceSegment
     */
    public function setSegmentPhysConId($servicePhysConId)
    {
        $this->physConId = $servicePhysConId;

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
        return $this->segmentProposedDecommission;
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

    public function getLogConName($logConName)
    {
        return $this->logConName;
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


    public function moveSegmentToHistory(LogiServiceSegment $logiServiceSegment, $undo = 0)
    {
        /*$invSegmentHistory = new InvSegmentHistory();
        $invSegmentHistory->setSegmentId($invSegment->getSegmentId());
        $this->em->persist($invSegmentHistory);
        $invSegmentHistory->setAsIs($invSegment->getAsIs());
        $now = new \DateTime();
        $now->setTimestamp(time());
        $invSegmentHistory->setSegmentHistoryCreated($now);
        $invSegmentHistory->setIsUndone($undo);
        $invSegmentHistory->setProposedDecommission($invSegment->getProposedDecommission());
        $invSegmentHistory->setSegmentObjectId($invSegment->getSegmentObjectId());
        $invSegmentHistory->setSegmentPhysicalId($invSegment->getSegmentPhysicalId());
        $invSegmentHistory->setSegmentName($invSegment->getSegmentName());
        $invSegmentHistory->setCabinType($invSegment->getCabinType());
        $invSegmentHistory->setBatchId($invSegment->getBatchId());
        $invSegmentHistory->setHuaweiId($invSegment->getHuaweiId());
        $invSegmentHistory->setJvId($invSegment->getJvId());
        $invSegmentHistory->setLat($invSegment->getLat());
        $invSegmentHistory->setLocation($invSegment->getLocation());
        $invSegmentHistory->setLon($invSegment->getLon());
        $invSegmentHistory->setLong($invSegment->getLong());
        $invSegmentHistory->setM2000Id($invSegment->getM2000Id());
        $invSegmentHistory->setMslSegmentId($invSegment->getMslSegmentId());
        $invSegmentHistory->setState($invSegment->getState());
        $invSegmentHistory->setStateOwner($invSegment->getStateOwner());
        $invSegmentHistory->setVhaRegion($invSegment->getVhaRegion());
        $invSegmentHistory->setSegmentType($invSegment->getSegmentType());
        $invSegmentHistory->setSegmentCategory($invSegment->getSegmentCategory());
        $invSegmentHistory->setInvSegmentCategoryId($invSegment->getInvSegmentCategory()->getInvSegmentCategoryId());

        if ($invSegment->getAssociateEvent()) {
            $invSegmentHistory->setAssociateEventId($invSegment->getAssociateEvent()
                ->getEventId());
        }
        if ($invSegment->getImpactedEvent()) {
            $invSegmentHistory->setImpactedEventId($invSegment->getImpactedEvent()
                ->getEventId());
        }

        $invSegmentHistory->setLastChanged($invSegment->getLastChanged());
        $invSegmentHistory->setLastUpdated($invSegment->getLastUpdated());
        if ($invSegment->getSource()) {
            $invSegmentHistory->setSourceId($invSegment->getSource()
                ->getSourceId());
        }
        $this->em->flush();*/
    }


    /**
     * Get Physical ID.
     *
     * @return integer
     */
    public function getPhysicalId()
    {
    }

    /**
     * Set Physical ID.
     *
     * @param integer $physicalId
     *
     * @return object
     */
    public function setPhysicalId($physicalId)
    {
    }

    /**
     * Get Object ID.
     *
     * @return integer
     */
    public function getObjectId()
    {
        return $this->segmentObjectId;
    }

    /**
     * Set Object ID.
     *
     * @param integer $objectId
     *
     * @return object
     */
    public function setObjectId($objectId)
    {
        $this->segmentObjectId = $objectId;
        return $this;
    }

    /**
     * As Is flag.
     *
     * @return boolean
     */
    public function isAsIs()
    {
        return $this->segmentAsIs;
    }


    /**
     * Is Proposed Decommissioning flag.
     *
     * @return boolean
     */
    public function isProposedDecommission()
    {
        return $this->segmentProposedDecommission;
    }


    /**
     * Get Associate Event
     *
     * @return \Event
     */
    public function getAssociateEvent()
    {
        //this is quick fix to mutual exclusiveness of associateEvent and associateEventId
        return null;//$this->associateEvent;
    }

    /**
     * Set Associate Event
     *
     * @param \Event $associateEvent
     * @return object
     */
    public function setAssociateEvent(\Event $associateEvent = NULL)
    {
        //$this->associateEvent=$associateEvent;
        return $this;
    }

    /**
     * Get Impacted Event
     *
     * @return \Event
     */
    public function getImpactedEvent()
    {
        return $this->impactedEvent;
    }

    /**
     * Set Impacted Event
     *
     * @param \Event $impactedEvent
     * @return object
     */
    public function setImpactedEvent(\Event $impactedEvent = NULL)
    {
        $this->impactedEvent = $impactedEvent;
        return $this;
    }


    /**
     * Get the event of the particular object
     * This function can be generically used to get the event for that object
     *
     * @return Event
     */
    public function getObjectEvent()
    {
        return NULL;
    }

    /**
     * Check if object is modified or not. Return true if it has impacted event and
     * not decomissioned by same TTR.
     *
     * @param integer $ttr Ttr for which it is being check
     *
     * @return boolean return true if it has impacted event and
     *         not decomissioned by same ttr
     */
    public function isObjectInStateForUndo($ttr)
    {
        return true;
    }

    /**
     * Check if object is final.
     *
     * It means whether there is no impacted event.
     *
     * @return boolean
     */
    public function isObjectFinal()
    {

    }

    /**
     * Get Event Status ID.
     *
     * @return integer
     */
    public function getEventStatusId()
    {
        return null;
    }

    /**
     * Is Binded flag.
     *
     * @return boolean
     */
    public function isBinded()
    {
        return false;
    }

    /**
     * Set Is Binded flag
     *
     * @param boolean $isBinded
     */
    public function setIsBinded($isBinded)
    {

        return $this;
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
