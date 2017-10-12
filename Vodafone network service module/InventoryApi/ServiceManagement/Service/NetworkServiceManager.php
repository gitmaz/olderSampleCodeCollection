<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 21/03/2016
 * Time: 10:40 AM
 */
namespace InventoryApi\ServiceManagement\Service;

use Doctrine\ORM\EntityManager;
use EntityNames;
use Event;
use InventoryApi\LogicalConfiguration\Entity\LogicalConfiguration;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


class NetworkServiceManager
{
    /**
     * @var EntityManager
     */
    public $em;

    /**
     * @var SegmentDiscovery $segmentDiscovery
     */
    private $segmentDiscovery;

    /**
     * @var SegmentHeaderFactory $segmentHeaderFactory
     */
    private $segmentHeaderFactory;

    /**
     * @var SegmentManager $segmentManager
     */
    private $segmentManager;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;


//PROPOSE
    /**
     * Constructor
     * @param EntityManager $em
     * @param NetworkSetupFromInventory $segmentDiscovery
     * @param SegmentHeaderFactory $segmentHeaderFactory
     * @param SegmentManager $segmentManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    function __construct(
        EntityManager $em,
        SegmentDiscovery $segmentDiscovery,
        SegmentHeaderFactory $segmentHeaderFactory,
        SegmentManager $segmentManager,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->em = $em;
        $this->segmentDiscovery = $segmentDiscovery;
        $this->segmentHeaderFactory = $segmentHeaderFactory;
        $this->segmentManager = $segmentManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Propose New Node.
     *
     * @param array $logConfValues
     * @param integer $ttrId
     * @param \Source $source
     * @param string $userId
     * @param Event $familyEvent
     *
     * @return ServiceSegmentsInfo $updatedServiceSegmentsInfo
     *
     * @throws \UnexpectedValueException
     * @throws \DomainException
     * @throws \Exception
     */
    public function proposeNewNode(
        LogicalConfiguration $logConf,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL
    )
    {

        try {
            $this->em->getConnection()->beginTransaction();
            if (!$ttrId || ($ttrId == -1)) {
                throw new \UnexpectedValueException('TTR is invalid.');
            }

            $serviceSegmentsInfo = $this->segmentDiscovery->discoverSegmentsRadiatingFrom($logConf->getLogConfObjectId());
            if ($serviceSegmentsInfo == null) {
                //echo "No match found,No segments found -it might be that the logconfId is not of a type service.";
                return null;
            }

            if ($serviceSegmentsInfo->status == "not found") {
                // echo "No segments found for descriptor {$serviceSegmentsInfo->descriptor}";
                return null;
            }

            $this->createServiceHeader($serviceSegmentsInfo);

            $this->proposeNewSegments($serviceSegmentsInfo,
                $ttrId,
                $source,
                $userId,
                $familyEvent);


            /*$networkServiceChangeEventData = new NetworkServiceChangeEventData($logConfIdOfNewNode, 0, $source, NULL, $userId);
            $this->eventDispatcher->dispatch(NetworkServiceChangeEvent::NETWORK_SERVICE_PROPOSE_NEW_DIRECTLY_AFTER, $networkServiceChangeEventData);*/

            $this->em->getConnection()->commit();

            return $serviceSegmentsInfo;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            \ExceptionDbLogger::logException($e, get_class($this) . "::proposeNewNode");
            throw $e;
        }
    }


    /**
     * Propose Decommission Node.
     *
     * @param array $logConfIdOfDecommisionedNode
     * @param integer $ttrId
     * @param \Source $source
     * @param string $userId
     * @param Event $familyEvent
     *
     * @return $updatedServiceNodes
     *
     * @throws \UnexpectedValueException
     * @throws \DomainException
     * @throws \Exception
     *
     *
     * LogicalConfiguration $logConf,
     * $ttrId,
     * \Source $source,
     * $userId,
     * Event $familyEvent = NULL
     */
    public function proposeDecommissionNode(
        $logConf,
        $ttrId,
        $source,
        $userId,
        $familyEvent
    )
    {
        $updatedServicesIds = [];


        $this->em->getConnection()->beginTransaction();
        try {

            $serviceSegmentsInfo = $this->segmentDiscovery->discoverSegmentsRadiatingFrom($logConf->getLogConfObjectId());

            $this->createServiceHeader($serviceSegmentsInfo);
            $this->proposeDecomSegments(
                $serviceSegmentsInfo,
                $ttrId,
                $source,
                $userId,
                $familyEvent);

            /*$networkServiceChangeEventData = new NetworkServiceChangeEventData($nodeToDecommission, $ttrId, NULL, $familyEvent, $userId);
            $this->eventDispatcher->dispatch(NetworkServiceChangeEvent::NETWORK_SERVICE_PROPOSE_DECOM_AFTER, $networkServiceChangeEventData);*/

            $this->em->getConnection()->commit();

            return $serviceSegmentsInfo;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            \ExceptionDbLogger::logException($e, get_class($this) . "::proposeDecomissionNode");
            throw $e;
        }


    }

    /**
     * Propose Modification to Node.
     *
     * @param LogicalConfiguration $logConfToModify
     * @param integer $ttrId
     * @param \Source $source
     * @param string $userId
     * @param  Event $familyEvent = NULL
     *
     * @return $updatedServicesIds
     *
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    public function proposeModificationToNode(
        LogicalConfiguration $logConfToModify,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL
    )
    {
        $updatedServicesIds = [];
        try {
            $this->em->getConnection()->beginTransaction();

            //todo : if only modification is on parameters involved in discovery ,we need to do this otherwise ,do nothing

            //first,ask previous segments to be deccommisioned

            //get object id of this logical configuration prior to the change:
            //if only we are in Propose Mod ro Report Mod status of log conf, also update segments of previous state with a propose or report decom
            $prevToModConfigObjectId = $this->netDataProvider->calculatePrevToModLogicalConfigurationId($logConfToModify->getLogConfObjectId());

            //todo : form $logConfPrevToModify by consulting to logConfRepo
            $this->proposeDecommissionNode($prevToModConfigObjectId, $ttrId, $source, $userId, $familyEvent);
            $serviceSegmentsInfo = $this->proposeNewNode($logConfToModify, $ttrId, $source, $userId, $familyEvent);

            $this->em->getConnection()->commit();

            return $serviceSegmentsInfo;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            \ExceptionDbLogger::logException($e, get_class($this) . "::proposeModificationToLogicalConfiguration");
            throw $e;
        }
    }

//REPORT

    /**
     * Report New Node
     *
     * @param array $logConfIdOfNewNode
     * @param integer $ttrId
     * @param \Source $source
     * @param string $userId
     * @param Event $familyEvent
     *
     * @return $updatedServicesIds
     *
     * @throws \UnexpectedValueException
     * @throws \DomainException
     * @throws \Exception
     */
    public function reportNewNode(
        LogicalConfiguration $logConf,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL
    )
    {
        $updatedServicesIds = [];

        try {
            $this->em->getConnection()->beginTransaction();

            $serviceSegmentsInfo = $this->segmentDiscovery->discoverSegmentsRadiatingFrom($logConf->getLogConfObjectId());
            if ($serviceSegmentsInfo == null) {
                //echo "No match found,No segments found -it might be that the logconfId is not of a type service.";
                return null;
            }

            if ($serviceSegmentsInfo->status == "not found") {
                // echo "No segments found for descriptor {$serviceSegmentsInfo->descriptor}";
                return null;
            }

            $this->createServiceHeader($serviceSegmentsInfo);

            $this->reportNewSegments($serviceSegmentsInfo,
                $ttrId,
                $source,
                $userId,
                $familyEvent);

            /* $networkServiceChangeEventData = new NetworkServiceChangeEventData($logConfIdOfNewNode, 0, $source, NULL, $userId);
             $this->eventDispatcher->dispatch(NetworkServiceChangeEvent::NETWORK_SERVICE_REPORT_NEW_DIRECTLY_AFTER, $networkServiceChangeEventData);*/

            $this->em->flush();
            $this->em->getConnection()->commit();

            return $updatedServicesIds;
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            \ExceptionDbLogger::logException($e, get_class($this) . "::reportNewNode");
            throw $e;
        }
    }


    //REPORT

    /**
     * Report New Node Directly
     *
     * @param array $logConfIdOfNewNode
     * @return $updatedServicesIds
     *
     * @throws \UnexpectedValueException
     * @throws \DomainException
     * @throws \Exception
     */
    public function reportNewNodeDirectly(
        LogicalConfiguration $logConf
    )
    {
        $updatedServicesIds = [];

        try {
            $this->em->getConnection()->beginTransaction();

            $serviceSegmentsInfo = $this->segmentDiscovery->discoverSegmentsRadiatingFrom($logConf->getLogConfObjectId());
            if ($serviceSegmentsInfo == null) {
                //echo "No match found,No segments found -it might be that the logconfId is not of a type service.";
                return null;
            }

            if ($serviceSegmentsInfo->status == "not found") {
                // echo "No segments found for descriptor {$serviceSegmentsInfo->descriptor}";
                return null;
            }

            $this->createServiceHeader($serviceSegmentsInfo);

            $this->reportNewSegmentsDirectly($serviceSegmentsInfo);

            /* $networkServiceChangeEventData = new NetworkServiceChangeEventData($logConfIdOfNewNode, 0, $source, NULL, $userId);
             $this->eventDispatcher->dispatch(NetworkServiceChangeEvent::NETWORK_SERVICE_REPORT_NEW_DIRECTLY_AFTER, $networkServiceChangeEventData);*/

            $this->em->flush();
            $this->em->getConnection()->commit();

            return $updatedServicesIds;
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            \ExceptionDbLogger::logException($e, get_class($this) . "::reportNewNodeDirectly");
            throw $e;
        }
    }


    /**
     * Report Decommissioned Node .
     *
     * @param array $logConfIdOfDecommisionedNode
     * @param integer $ttrId
     * @param \Source $source
     * @param string $userId
     * @param Event $familyEvent
     *
     * @return $updatedServicesIds
     *
     * @throws \UnexpectedValueException
     * @throws \DomainException
     * @throws \Exception
     */
    public function reportDecomNode(
        $logConf,/*LogicalConfigurationHistory*/
        $ttrId,
        $source,/*\Source*/
        $userId,
        $familyEvent = NULL /*Event*/
    )
    {
        try {
            $this->em->getConnection()->beginTransaction();


            $serviceSegmentsInfo = $this->segmentDiscovery->resolveNeighborhoodServiceSegmentsFromDb($logConf->getLogConfObjectId());


            // $serviceSegmentsInfo=$this->segmentDiscovery->discoverSegmentsRadiatingFrom($logConf->getLogConfObjectId());
            if ($serviceSegmentsInfo == null) {
                //echo "No match found,No segments found -it might be that the logconfId is not of a type service.";

                return null;
            }

            if ($serviceSegmentsInfo->status == "not found") {
                // echo "No segments found for descriptor {$serviceSegmentsInfo->descriptor}";
                return null;
            }

            //todo : code for removing header if no segments remained after decom of this node
            //$this->createServiceHeader($serviceSegmentsInfo);

            $this->reportDecomSegments($serviceSegmentsInfo,
                $ttrId,
                $source,
                $userId,
                $familyEvent);


            /*$networkServiceChangeEventData = new NetworkServiceChangeEventData($logConfIdOfDecomNode, 0, $source=1, NULL, $userId=1);
            $this->eventDispatcher->dispatch(NetworkServiceChangeEvent::NETWORK_SERVICE_REPORT_DECOM_DIRECTLY_AFTER, $networkServiceChangeEventData);*/

            $this->em->getConnection()->commit();

            return null;

        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            \ExceptionDbLogger::logException($e, get_class($this) . "::reportDecomNode");
            throw $e;
        }
    }

    /**
     * Report Modification to Node.
     *
     * @param LogicalConfiguration $logConfToModify
     * @param integer $ttrId
     * @param \Source $source
     * @param string $userId
     * @param  Event $familyEvent = NULL
     *
     * @return $updatedServicesIds
     *
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    public function reportModificationToNode(
        LogicalConfiguration $logConfToModify,
        $ttrId,
        \Source $source,
        $userId,
        Event $familyEvent = NULL
    )
    {
        $updatedServicesIds = [];
        try {
            $this->em->getConnection()->beginTransaction();

            //todo : if only modification is on parameters involved in discovery ,we need to do this otherwise ,do nothing

            //first,ask previous segments to be decommissioned

            //get object id of this logical configuration prior to the change: //todo: use event table instead
            //if only we are in Propose Mod ro Report Mod status of log conf, also update segments of previous state with a propose or report decom
            $prevToModConfigObjectId = $this->netDataProvider->calculatePrevToModLogicalConfigurationId($logConfToModify->getLogConfObjectId());

            $this->reportDecomNode($prevToModConfigObjectId, $ttrId, $source, $userId, $familyEvent);
            $serviceSegmentsInfo = $this->reportNewNode($logConfToModify, $ttrId, $source, $userId, $familyEvent);

            $this->em->getConnection()->commit();

            return $serviceSegmentsInfo;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            \ExceptionDbLogger::logException($e, get_class($this) . "::proposeModificationToLogicalConfiguration");
            throw $e;
        }
    }


    public function createServiceHeader($serviceSegmentsInfo)
    {
        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceId();
        $serviceDescriptor = $serviceSegmentsInfo->getServiceDescriptor();
        $serviceTypeId = null;
        $this->segmentHeaderFactory->create($serviceDescriptorHash, $serviceTypeId, $serviceDescriptor, $serviceDescriptor);

        return $serviceDescriptorHash;
    }

    private function createServiceHeaderForSubservice($logiServiceSegment)
    {
        $serviceDescriptorHash = $logiServiceSegment->getSegmentLogConId();
        if ($serviceDescriptorHash == null) { //return if we are not a subservice
            return;
        }
        $serviceDescriptor = $logiServiceSegment->getLogConName();
        $serviceTypeId = null;
        $this->segmentHeaderFactory->create($serviceDescriptorHash, $serviceTypeId, $serviceDescriptor, $serviceDescriptor);

        return $serviceDescriptorHash;
    }


    private function proposeNewSegments(
        $serviceSegmentsInfo,
        $ttrId,
        $source,
        $userId,
        $familyEvent
    )
    {
        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceId();
        $segments = $serviceSegmentsInfo->getSegments();
        foreach ($segments as $segmentData) {
            $logiServiceSegment = $this->segmentManager->proposeNewSegment(
                $segmentData,
                $serviceDescriptorHash,
                $ttrId,
                $source,
                $userId,
                $familyEvent);

            $this->createServiceHeaderForSubservice($logiServiceSegment);
        }

        /*//if we are downstream of an upstream,save segments we will produced in upstream (as subservice)
            $upstreamServiceSegmentsInfo=$serviceSegmentsInfo->getUpstream();

            if($upstreamServiceSegmentsInfo!=null){
                $this->saveService($upstreamServiceSegmentsInfo,$ttrId, $userId, $assocEventId,$impactedEventId, $designStatus);
            }*/

    }

    private function proposeDecomSegments(
        $serviceSegmentsInfo,
        $ttrId,
        $source,
        $userId,
        $familyEvent
    )
    {
        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceId();
        $segments = $serviceSegmentsInfo->getSegments();
        foreach ($segments as $segmentData) {
            $this->segmentManager->proposeDecomSegment(
                $segmentData,
                $serviceDescriptorHash,
                $ttrId,
                $source,
                $userId,
                $familyEvent);
        }

        /*//if we are downstream of an upstream,save segments we will produced in upstream (as subservice)
            $upstreamServiceSegmentsInfo=$serviceSegmentsInfo->getUpstream();

            if($upstreamServiceSegmentsInfo!=null){
                $this->saveService($upstreamServiceSegmentsInfo,$ttrId, $userId, $assocEventId,$impactedEventId, $designStatus);
            }*/

    }


    private function reportNewSegments(
        $serviceSegmentsInfo,
        $ttrId,
        $source,
        $userId,
        $familyEvent
    )
    {
        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceId();
        $segments = $serviceSegmentsInfo->getSegments();
        foreach ($segments as $segmentData) {
            $this->segmentManager->reportNewSegment(
                $segmentData,
                $serviceDescriptorHash,
                $ttrId,
                $source,
                $userId,
                $familyEvent);
        }

        /*//if we are downstream of an upstream,save segments we will produced in upstream (as subservice)
            $upstreamServiceSegmentsInfo=$serviceSegmentsInfo->getUpstream();

            if($upstreamServiceSegmentsInfo!=null){
                $this->saveService($upstreamServiceSegmentsInfo,$ttrId, $userId, $assocEventId,$impactedEventId, $designStatus);
            }*/

    }

    private function reportNewSegmentsDirectly(
        $serviceSegmentsInfo
    )
    {
        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceId();
        $segments = $serviceSegmentsInfo->getSegments();
        foreach ($segments as $segmentData) {
            $this->segmentManager->reportNewSegmentDirectly($segmentData, $serviceDescriptorHash);
        }

        /*//if we are downstream of an upstream,save segments we will produced in upstream (as subservice)
            $upstreamServiceSegmentsInfo=$serviceSegmentsInfo->getUpstream();

            if($upstreamServiceSegmentsInfo!=null){
                $this->saveService($upstreamServiceSegmentsInfo,$ttrId, $userId, $assocEventId,$impactedEventId, $designStatus);
            }*/

    }


    private function reportDecomSegments(
        $serviceSegmentsInfo,
        $ttrId,
        $source,
        $userId,
        $familyEvent
    )
    {
        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceId();
        $segments = $serviceSegmentsInfo->getSegments();
        foreach ($segments as $segmentData) {
            $this->segmentManager->reportDecomSegment(
                $segmentData,
                $serviceDescriptorHash,
                $ttrId,
                $source,
                $userId,
                $familyEvent);
        }

        /*//if we are downstream of an upstream,save segments we will produced in upstream (as subservice)
            $upstreamServiceSegmentsInfo=$serviceSegmentsInfo->getUpstream();

            if($upstreamServiceSegmentsInfo!=null){
                $this->saveService($upstreamServiceSegmentsInfo,$ttrId, $userId, $assocEventId,$impactedEventId, $designStatus);
            }*/

    }


    public function getAllServiceNamesAffectedByThisLogConfDecommission($logConfToDecomId)
    {
        $serviceIds = $this->segmentManager->getAllServiceIdsPassingThroughThisLogConf($logConfToDecomId);
        $serviceNames = $this->segmentHeaderFactory->resolveServiceNamesFromIds($serviceIds);
        return $serviceNames;
    }


    public function gotCalled($calledFrom)
    {
        $logger = new Loggit();
        $logger->log("ServiceManager is called from " . $calledFrom);
    }

    public function getLogicalConfiguration($logConfId)
    {
        return $this->segmentDiscovery->getLogicalConfiguration($logConfId);
    }

    public function getLogicalConfigurationFromHistory($logConfId)
    {
        return $this->em->getRepository(EntityNames::LOGICAL_CONFIGURATION_HISTORY)->findOneBy(["logConfId" => $logConfId]);
    }


}