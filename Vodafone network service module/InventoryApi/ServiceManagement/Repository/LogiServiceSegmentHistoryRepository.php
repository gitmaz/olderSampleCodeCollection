<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 25/05/2016
 * Time: 9:48 AM
 */

namespace InventoryApi\ServiceManagement\Repository;

namespace InventoryApi\ServiceManagement\Repository;

use InventoryApi\Repository\AbstractEntityRepository;
use InventoryApi\ServiceManagement\Entity\LogiServiceSegmentHistory;

class LogiServiceSegmentHistoryRepository extends AbstractEntityRepository
{

    protected $entityClassName = \EntityNames::LOGI_SERVICE_SEGMENT_HISTORY;

    /*
     * @VAR \EntityManager $em
     */
    public function __construct($em)
    {
        $this->_em = $em;

        $this->class = $this->entityClassName;

        //todo : delete this patch properly (was being used in findBy)
        $this->_entityName = $this->entityClassName;
    }

    /*
     * @param LogiServiceSegment $logiServiceSegment
     */
    public function addServiceSegmentToHistory($logiServiceSegment)
    {

        $logiServiceSegmentHistory = new LogiServiceSegmentHistory();

        $logiServiceSegmentHistory->setServiceSegmentId($logiServiceSegment->getServiceSegmentId());
        $logiServiceSegmentHistory->setSegmentServiceId($logiServiceSegment->getSegmentServiceId());
        $logiServiceSegmentHistory->setSegmentLogiConf1Id($logiServiceSegment->getSegmentLogiConf1Id());
        $logiServiceSegmentHistory->setSegmentLogiConf2Id($logiServiceSegment->getSegmentLogiConf2Id());
        $logiServiceSegmentHistory->setSegmentPhysConId($logiServiceSegment->getSegmentPhysConId());
        $logiServiceSegmentHistory->setSegmentLogConId($logiServiceSegment->getSegmentLogConId());
        $logiServiceSegmentHistory->setSegmentObjectId($logiServiceSegment->getSegmentObjectId());
        $logiServiceSegmentHistory->setAssociateEventId($logiServiceSegment->getAssociateEventId());
        $logiServiceSegmentHistory->setAsIs($logiServiceSegment->getAsIs());
        $logiServiceSegmentHistory->setSegmentPhysicalId($logiServiceSegment->getSegmentPhysicalId());
        $logiServiceSegmentHistory->setImpactedEventId($logiServiceSegment->getImpactedEventId());
        $logiServiceSegmentHistory->setProposedDecommission($logiServiceSegment->getProposedDecommission());
        $logiServiceSegmentHistory->setSourceId($logiServiceSegment->getSourceId());
        $logiServiceSegmentHistory->setIsUndone($logiServiceSegment->getIsUndone());
        $logiServiceSegmentHistory->setLastChanged($logiServiceSegment->getLastChanged());
        $logiServiceSegmentHistory->setLastUpdated($logiServiceSegment->getLastUpdated());

        $this->_em->persist($logiServiceSegmentHistory);
        $this->_em->flush();
    }

    /*
   * @param [[key=>value],...] $segments
   */
    public function saveSegments($segments)
    {

        foreach ($segments as $segment) {
            $logiServiceSegmentHistory = new LogiServiceSegmentHistory();

            $logiServiceSegmentHistory->setServiceSegmentId($segment["SEGMENT_ID"]);
            $logiServiceSegmentHistory->setSegmentServiceId($segment["SEGMENT_SERVICE_ID"]);
            $logiServiceSegmentHistory->setSegmentLogiConf1Id($segment["SEGMENT_LOGI_CONF1_ID"]);
            $logiServiceSegmentHistory->setSegmentLogiConf2Id($segment["SEGMENT_LOGI_CONF2_ID"]);
            $logiServiceSegmentHistory->setSegmentPhysConId($segment["SEGMENT_PHYS_CON_ID"]);
            $logiServiceSegmentHistory->setSegmentLogConId($segment["SEGMENT_LOG_CON_ID"]);
            $logiServiceSegmentHistory->setSegmentObjectId($segment["SEGMENT_OBJECT_ID"]);
            $logiServiceSegmentHistory->setAssociateEventId($segment["ASSOCIATE_EVENT_ID"]);
            $logiServiceSegmentHistory->setAsIs($segment["SEGMENT_AS_IS"]);
            $logiServiceSegmentHistory->setSegmentPhysicalId($segment["SEGMENT_PHYSICAL_ID"]);
            $logiServiceSegmentHistory->setImpactedEventId($segment["IMPACTED_EVENT_ID"]);
            $logiServiceSegmentHistory->setProposedDecommission($segment["SEGMENT_PROPOSED_DECOMMISSION"]);
            $logiServiceSegmentHistory->setSourceId($segment["SOURCE_ID"]);
            $logiServiceSegmentHistory->setIsUndone($segment["IS_UNDONE"]);
            $logiServiceSegmentHistory->setLastChanged($segment["LAST_CHANGED"]);
            $logiServiceSegmentHistory->setCreated($segment["CREATED"]);
            $logiServiceSegmentHistory->setLastUpdated($segment["LAST_UPDATED"]);//SEGMENT_PHYS_CON_NAME, SEGMENT_LOG_CON_NAME)

            $this->_em->persist($logiServiceSegmentHistory);
            $this->_em->flush();
        }
    }

    public function deleteAllSegmentsPassingThroughLogConfId($logConfId)
    {
        $sql = "delete from logi_service_segment_history where segment_logi_conf1_id=$logConfId or  segment_logi_conf2_id=$logConfId";
        $this->execute($sql);
    }

}