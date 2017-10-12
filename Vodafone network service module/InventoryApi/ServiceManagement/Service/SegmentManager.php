<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 5/06/2016
 * Time: 10:38 AM
 */

namespace InventoryApi\ServiceManagement\Service;

use CommonConstant;
use Event;
use InventoryApi\LogicalConfiguration\Repository\LogicalConfigurationRepository;
use InventoryApi\ServiceManagement\Entity\LogiServiceSegment;
use InventoryApi\ServiceManagement\Repository\LogiServiceSegmentRepository;

class SegmentManager
{

    private $em;
    /**
     * @var \EventManager
     */
    private $eventManager;

    /**
     * @var \EventDependencyManager
     */
    private $eventDependencyManager;
    private $genericEventEntityManager;
    private $logiServiceSegmentRepository;
    private $logicalConfigurationRepository;

    /**
     *
     * @param EntityManager $em
     * @param \EventManager $eventManager
     * @param \EventDependencyManager $eventDependencyManager ,
     * @param LogiServiceSegmentRepository $logiServiceSegmentRepository
     * @param LogicalConfigurationRepository $logicalConfigurationRepository
     * @param \GenericEventEntityManager $genericEventEntityManager
     *
     */
    function __construct(
        $em,
        \EventManager $eventManager,
        \EventDependencyManager $eventDependencyManager,
        LogiServiceSegmentRepository $logiServiceSegmentRepository,
        LogicalConfigurationRepository $logicalConfigurationRepository,
        \GenericEventEntityManager $genericEventEntityManager

    )
    {
        $this->logiServiceSegmentRepository = $logiServiceSegmentRepository;
        $this->logicalConfigurationRepository = $logicalConfigurationRepository;
        $this->em = $em;
        $this->eventManager = $eventManager;
        $this->eventDependencyManager = $eventDependencyManager;
        $this->genericEventEntityManager = $genericEventEntityManager;
    }


    function proposeNewSegment(
        array $segmentData,
        $serviceDescriptorHash,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL


    )
    {
        $assocEventId = null;
        $impactedEventId = null;
        $designStatus = "PROPOSE_NEW";

        $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::PROPOSED, $userId);

        $segment = $this->logiServiceSegmentRepository->createOrUpdateSegmentRevision(
            $serviceDescriptorHash,
            $segmentData,
            $ttrId,
            $userId,
            $event->getEventId(),
            null,
            $designStatus);

        if (isset($segmentData["SEGMENT_VIA"])) {//$segmentData["SEGMENT_LOG_CON_NAME"]
            $this->saveViaSegments($segmentData["SEGMENT_LOG_CON_ID"], $segmentData["SEGMENT_VIA"], $ttrId, $userId);
        }

        $event->setSourceObjectId($segment->getSegmentObjectId());
        $event->setSourcePhyId($segment->getSegmentObjectId());
        $this->em->persist($event);
        $this->em->flush();

        //object relation dependency
        $parent1ObjectId = $segmentData["LOGI_CONF1_ID"];
        $parent2ObjectId = $segmentData["LOGI_CONF2_ID"];
        if ($parent1ObjectId != null) {
            $this->addObjectDependenciesToEvent($segment, $event, $parent1ObjectId, $ttrId, $userId, "Propose");
        }
        if ($parent2ObjectId != null) {
            $this->addObjectDependenciesToEvent($segment, $event, $parent2ObjectId, $ttrId, $userId, "Propose");
        }

        // TODO: create event dependency

        return $segment;
    }

    private function saveViaSegments($logConId, $logConSegmentViaStr, $ttrId, $userId)
    {
        $viaStr = rtrim($logConSegmentViaStr, ",");
        $viaStrParts = explode(",", $viaStr);

        foreach ($viaStrParts as $viaStrPart) {
            $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::PROPOSED, $userId);
            $assocEventId = $event->getEventId();
            $segment = $this->logiServiceSegmentRepository->saveViaSegment($logConId, $viaStrPart, $assocEventId);

            $event->setSourceObjectId($segment->getSegmentObjectId());
            $event->setSourcePhyId($segment->getSegmentObjectId());

            $this->em->persist($event);
            $this->em->flush();

        }

    }


    function proposeDecomSegment(
        array $segmentData,
        $serviceDescriptorHash,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL


    )
    {
        $assocEventId = null;
        $impactedEventId = null;
        $designStatus = "PROPOSE_DECOM";


        $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::DECOMMISSIONED, $userId);

        //$segmentData

        $segment = $this->getSegmentByLogiConfObjectId($segmentData["SEGMENT_LOGI_CONF1_ID"], $segmentData["SEGMENT_LOGI_CONF2_ID"]);

        if ($segment == null) {
            return null;
        }
        // Adding dependency on the decommission card event
        $segment->setImpactedEventId($event->getEventId());
        $segment->setProposedDecommission(1);
        $segment->setAsIs(0);

        //$this->em->persist($segment);
        $this->em->flush();

        $event->setTargetObjectId($segment->getSegmentObjectId());
        $event->setTargetPhyId($segment->getSegmentPhysicalId());

        $this->em->persist($event);
        $this->em->flush();

        //object relation dependency
        /* temp bypassing dependencies to make report decom work
         $parent1ObjectId=$segmentData["LOGI_CONF1_ID"];
         $parent2ObjectId=$segmentData["LOGI_CONF2_ID"];
         if($parent1ObjectId!=null) {
             $this->addObjectDependenciesToEvent($segment, $event, $parent1ObjectId, $ttrId, $userId, "Decommission");
         }
         if($parent2ObjectId!=null) {
             $this->addObjectDependenciesToEvent($segment, $event, $parent2ObjectId, $ttrId, $userId, "Decommission");
         }*/

        // TODO: create event dependency

        return $segment;
    }

    function reportNewSegment(
        array $segmentData,
        $serviceDescriptorHash,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL


    )
    {
        $assocEventId = null;
        $impactedEventId = null;
        $designStatus = "REPORT_NEW";


        $segment = $this->proposeNewSegment(
            $segmentData,
            $serviceDescriptorHash,
            $ttrId,
            $source,
            $userId,
            $familyEvent
        );

        $result = $this->eventManager->executeEvent($segment->getAssociateEventId());//$event->getEventId());//


        if (!$result[\CommonConstant::IS_SUCCESS]) {
            throw new \DomainException($result['errorMessage']);
        }


        $this->em->flush();

        return $segment;
    }


    function reportNewSegmentDirectly(
        array $segmentData,
        $serviceDescriptorHash

    )
    {
        $assocEventId = null;
        $impactedEventId = null;
        $designStatus = "REPORT_NEW_DIRECTLY";

        $segment = $this->logiServiceSegmentRepository->createOrUpdateSegmentRevision(
            $serviceDescriptorHash,
            $segmentData,
            1,
            -1,
            -1,
            null,
            $designStatus);
        $segment->setAsIs(true);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }


    function addObjectDependenciesToEvent($segment, $event, $logConfObjectId, $ttrid, $userId, $action = 'Decommission')
    {
        $parentObjArray = [];

        //$dependencies = $this->genericEventEntityManager->checkEntityModifiable($segment, $ttrid, $parentObjArray, $action, false);

        //$eventDependencies  = $dependencies['eventDependencies'];
        //$objectDependencies = $dependencies['objectDependencies'];

        $parentObjArray = [];
        $parentObjArray[] = array(
            \CommonConstant::ENTITY_OBJECT_ID => $logConfObjectId,
            \CommonConstant::EVENT_ENTITY_ID => \CommonConstant::LOG_CONF_ENTITY,
            \CommonConstant::LOG_ENTITY_ID => "",
        );

        /*foreach ($objectDependencies as $objectDependency) {
            $parentObjArray[] = array(
                CommonConstant::ENTITY_OBJECT_ID => $objectDependency[CommonConstant::COL_ENTITY_OBJECT_ID],
                CommonConstant::EVENT_ENTITY_ID  => $objectDependency[CommonConstant::COL_EVENT_ENTITY_ID],
                CommonConstant::LOG_ENTITY_ID    => $objectDependency[CommonConstant::COL_LOGICAL_ENTITY_ID],
            );
        }*/

        $this->genericEventEntityManager->addAllParentObjsToEvent($event, $parentObjArray);

    }

    function reportDecomSegment(
        array $segmentData,
        $serviceDescriptorHash,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL


    )
    {
        $assocEventId = null;
        $impactedEventId = null;
        $designStatus = "REPORT_DECOM";

        $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::DECOMMISSIONED, $userId);

        $segment = $this->proposeDecomSegment(
            $segmentData,
            $serviceDescriptorHash,
            $ttrId,
            $source,
            $userId,
            $familyEvent
        );


        $event->setTargetObjectId($segment->getSegmentObjectId());
        $event->setTargetPhyId($segment->getSegmentPhysicalId());

        $result = $this->eventManager->executeEvent($event->getEventId());//$segment->getImpactedEvent());

        if (!$result[\CommonConstant::IS_SUCCESS]) {
            throw new \DomainException($result['errorMessage']);
        }

        $this->em->persist($event);

        $this->em->flush();

        // TODO: create object relation depencey
        // TODO: create event dependency

        return $segment;
    }


    /*
    * This will dismiss the node identified with this logical configuration id and will do the updates to all
    * services that will be affected through this disposal
    *
    *
    * @param integer $logConfIdOfNewNode : configuration which is triggering this addition
    */
    function dismissServiceNode($logConfIdOfDecommisionedNode, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {
        $this->logiServiceSegmentRepository->removeSegmentsHavingLogicalConfiguration($logConfIdOfDecommisionedNode, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);
    }

    function getSegmentByObjectId($segmentObjectId)
    {
        $foundSegment = $this->logiServiceSegmentRepository->findOneBy(["segmentObjectId" => $segmentObjectId]);

        return $foundSegment;
    }

    function getSegmentByPhysicalId($segmentPhysicalId)
    {
        $foundSegment = $this->logiServiceSegmentRepository->findOneBy(["segmentPhysicalId" => $segmentPhysicalId]);
        return $foundSegment;
    }

    function getSegmentByLogiConfObjectId($logConf1Id, $logConf2Id)
    {
        $foundSegment = $this->logiServiceSegmentRepository->findOneBy(["logiConf1Id" => $logConf1Id, "logiConf2Id" => $logConf2Id]);
        if ($foundSegment == null) {
            $foundSegment = $this->logiServiceSegmentRepository->findOneBy(["logiConf2Id" => $logConf1Id, "logiConf1Id" => $logConf2Id]);
        }
        return $foundSegment;
    }

    public function moveSegmentToHistory(LogiServiceSegment $logiServiceSegment, $undo = 0)
    {
        return $this->logiServiceSegmentRepository->moveSegmentToHistory($logiServiceSegment, $undo);
    }

    public function moveSegmentsPassingFromLogConfIdToHistory($logConfIdOfDecomNode)
    {
        $duDecumSegments = $this->logiServiceSegmentRepository->getAllSegmentsPassingThroughLogConfId($logConfIdOfDecomNode);
        $this->logiServiceSegmentHistoryRepository->saveSegments($duDecumSegments);
        //$this->logiServiceSegmentRepository->deleteAllSegmentsPassingThroughLogConfId($logConfIdOfDecomNode);
    }

    public function addSegmentAsIs(LogiServiceSegment $segment)
    {

        $segment->setAssociateEventId(null);
        $segment->setAsIs(true);
        $now = new \DateTime();
        $now->setTimestamp(time());
        $segment->setLastUpdated($now);

    }


    public function getAllServiceIdsPassingThroughThisLogConf($logConfId)
    {


        $serviceIdRecs = $this->logiServiceSegmentRepository->findDistinctServiceIdsPassingThroughLogConfId($logConfId);
        $serviceIds = [];
        foreach ($serviceIdRecs as $serviceIdRec) {
            $serviceIds[] = $serviceIdRec["SEGMENT_SERVICE_ID"];
        }

        return $serviceIds;
    }





//PROBABLE FUTURE use:


    /* This is depreciated as each propose modify is translated beforehand to propose decom and propose new pair
        function proposeModifySegment(
            array $segmentData,
            $serviceDescriptorHash,
            $ttrId,
            \Source $source,
            $userId,
            Event $familyEvent = NULL


        ){
            $assocEventId=null;
            $impactedEventId=null;
            $designStatus="PROPOSE_MOD";


            // TODO: Create new Event Entity record for Segment Id = 6
            $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::MODIFIED, $userId);

            $segment = $this->logiServiceSegmentRepository->createOrUpdateSegmentRevision(
                $serviceDescriptorHash,
                $segmentData,
                $ttrId,
                $userId,
                $event->getEventId(),
                null,
                $designStatus);

            $event->setSourceObjectId($segment);
            $event->setSourcePhyId($segment);

            $this->em->flush();

            // TODO: create object relation depencey
            // TODO: create event dependency

            return $segment;
        }*/

    /* This is depreciated as each report modify is translated to one report decom and report new pair
        function reportModifySegment(
            array $segmentData,
            $serviceDescriptorHash,
            $ttrId,
            \Source $source,
            $userId,
            Event $familyEvent = NULL


        ){
            $assocEventId=null;
            $impactedEventId=null;
            $designStatus="REPORT_MOD";


            // TODO: Create new Event Entity record for Segment Id = 6
            $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::MODIFIED, $userId);

            $segment = $this->logiServiceSegmentRepository->createOrUpdateSegmentRevision(
                $serviceDescriptorHash,
                $segmentData,
                $ttrId,
                $userId,
                $event->getEventId(),
                null,
                $designStatus);

            $event->setSourceObjectId($segment);
            $event->setSourcePhyId($segment);

            $this->em->flush();

            // TODO: create object relation depencey
            // TODO: create event dependency

            return $segment;
        }*/

    /* Depreciated as no need to do things directly assuming we have eanough time running on a repetative,timely cron job
        function reportModifySegmentDirectly(
            array $segmentData,
            $serviceDescriptorHash,
            $ttrId,
            \Source $source,
            $userId,
            Event $familyEvent = NULL


        ){
            $assocEventId=null;
            $impactedEventId=null;
            $designStatus="REPORT_MOD_DIRECTLY";


            // TODO: Create new Event Entity record for Segment Id = 6
            $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::MODIFIED, $userId);

            $segment = $this->logiServiceSegmentRepository->createOrUpdateSegmentRevision(
                $serviceDescriptorHash,
                $segmentData,
                $ttrId,
                $userId,
                $event->getEventId(),
                null,
                $designStatus);

            $event->setSourceObjectId($segment);
            $event->setSourcePhyId($segment);

            $this->em->flush();

            // TODO: create object relation depencey
            // TODO: create event dependency

            return $segment;
        }
    */


    /* Depreciated as no need to do things directly assuming we have eanough time running on a repetative,timely cron job
        function reportDecomSegmentDirectly(
            array $segmentData,
            $serviceDescriptorHash,
            $ttrId,
            \Source $source,
            $userId,
            Event $familyEvent = NULL


        ){
            $assocEventId=null;
            $impactedEventId=null;
            $designStatus="REPORT_DECOM_DIRECTLY";


            // TODO: Create new Event Entity record for Segment Id = 6
            $event = $this->eventManager->createNewEvent(6, $ttrId, \CommonConstant::DECOMMISSIONED, $userId);

            $segment = $this->logiServiceSegmentRepository->createOrUpdateSegmentRevision(
                $serviceDescriptorHash,
                $segmentData,
                $ttrId,
                $userId,
                $event->getEventId(),
                null,
                $designStatus);

            $event->setSourceObjectId($segment);
            $event->setSourcePhyId($segment);

            $this->em->flush();

            // TODO: create object relation depencey
            // TODO: create event dependency

            return $segment;
        }*/
}