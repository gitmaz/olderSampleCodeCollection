<?php
/*
 *
 *  This is used to query on devices (card or equipment) for fetching the ones with an specific service logical configuration.
 *  The out put is in segment format having information of end point devices as well as connection (either physical or logical- ie another service)
 *  .The result,in most complete case, will also contain connections on the device ,which are being used in propagation of that service
 * (when using getEquipmentsHavingLogicalConfigurationWithTheirConnectionsUsingPureSql)
 */

namespace InventoryApi\ServiceManagement\Repository;

use InventoryApi\Repository\AbstractEntityRepository;
use InventoryApi\ServiceManagement\Entity\LogiServiceSegment;

class LogiServiceSegmentRepository extends AbstractEntityRepository
{

    protected $entityClassName = \EntityNames::LOGI_SERVICE_SEGMENT;

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

//PURE SQL ORM access

    /*  All in one go service resolution (gets equipments with specific services with their connections)
     *  (combined for better performance)
     *  //Note: logical configuration table is modified to contain two search facility ids when this configuration is used for services
     *  these are called service_type and service_id
     *
     * for more general queries traversing multiple services having same configuration ,use next function
     *
     *  synonym : getEquipmentsWithTheirConnectionsHavingServiceUsingPureSql
     * @VAR int $serviceType
     * @VAR int $serviceId
     *
     * @RETURN array  sample:["SITE1"=>"mosman","SITE2"=>"mosman","EQUIPMENT_NAME1"=>"A","EQUIPMENT_NAME2"=>"B","EQUIPMENT_ID1"=>"A","EQUIPMENT_ID2"=>"B","CONNECTION_ID"=>"con1","INTERFACE_OBJECT_ID1"=>"a1","INTERFACE_OBJECT_ID2"=>"b1"],...]
     */
    public function resolveService($serviceType, $serviceId)
    {

        $basicSqlSelect = $this->getBasicSqlSelectForMainNodes(true);

        //complete end to end connection device resolution    AND lc2.service_id = :serviceId

        $sql = "$basicSqlSelect
                       WHERE
                         lciv.conf_obj_type_id = :serviceType
                        AND lciv.string_val = :serviceId
               ";


        $stmt = $this->_em->getConnection()->prepare($sql);

        $params['serviceType'] = $serviceType;
        $params['serviceId'] = $serviceId;

        $stmt->execute($params);

        $equipmentHavingServicesWithTheirConnectionsSummary = $stmt->fetchAll();

        return $equipmentHavingServicesWithTheirConnectionsSummary;
    }

    /*  As above but with more detailed filtering on configuration
    * this is for general querries traversing multiple services having same configuration details ,for simpler querries which
    * only traverse one service, use above function
    *
    * @VAR array $configIds  array of configuration items that the criteria of selection have been true (filtered through device configuration discovery)
    *
    * @var integer $subserviceConfigType refers to logical_entity_id which the service is based on
    * @RETURN array  [[]]
    */
    public function resolveServiceHavingConfigs($configIds, $serviceRole, $serviceName, $serviceId, $subServiceId, $subServiceTypeId)
    {
        if ($serviceRole == "main") {

            return $this->resolveServiceHavingConfigsForMainServiceNodes($configIds, $serviceRole, $serviceName, $serviceId);

        } else { //subservice

            return $this->resolveServiceHavingConfigsForSubServiceNodes($configIds, $serviceRole, $serviceName, $serviceId, $subServiceId, $subServiceTypeId);
        }
    }

    private function resolveServiceHavingConfigsForMainServiceNodes($configIds, $serviceRole, $serviceName, $serviceId)
    {

        $basicSqlSelect = $this->getBasicSqlSelectForMainNodes($configIds, $serviceRole, $serviceName, $serviceId);

        $stmt = $this->_em->getConnection()->prepare($basicSqlSelect);

        $stmt->execute();

        $equipmentHavingServicesWithTheirConnectionsSummary = $stmt->fetchAll();

        return $equipmentHavingServicesWithTheirConnectionsSummary;


    }


    public function resolveSegmentsForStandAloneMainServiceNodes($configIds, $serviceRole, $serviceName, $serviceId)
    {


        $basicSqlSelect = $this->getBasicSqlSelectForUnconnectedMainNodes($configIds, $serviceRole, $serviceName, $serviceId);


        $stmt = $this->_em->getConnection()->prepare($basicSqlSelect);

        $stmt->execute();

        $equipmentHavingServicesWithTheirConnectionsSummary = $stmt->fetchAll();

        return $equipmentHavingServicesWithTheirConnectionsSummary;
    }


    private function resolveServiceHavingConfigsForSubServiceNodes($configIds, $serviceRole, $serviceName, $serviceId, $subServiceId, $subServiceTypeId)
    {

        $basicSqlSelect = $this->getBasicSqlSelectForSubServiceNodes($configIds, $serviceRole, $serviceName, $serviceId, $subServiceId, $subServiceTypeId);


        $stmt = $this->_em->getConnection()->prepare($basicSqlSelect);


        $stmt->execute(null);

        $subserviceSegment = $stmt->fetchAll();

        return $subserviceSegment;
    }


    /*
    * @var $configs ids of already discovered subservices plus their name in the form  [["id"=>x ,"config_type"=>x_new_conf_type_Id ],...]
    *
    * @return [segment] where segment contains Service Segment info
    */
    public function resolveExplicitSubserviceSegmentsHavingConfigs($deviceSubservicesConfigsInfo, $serviceId)
    {

        //now add sub services
        $allsubserviceSegments = [];
        //$isEven=false;
        $SubservicesConfigIds = [];

        foreach ($deviceSubservicesConfigsInfo as $deviceSubservicesConfig) {

            $serviceName = $deviceSubservicesConfig["service_name"];
            $subServiceId = $deviceSubservicesConfig["service_id"];
            $subServiceTypeId = $deviceSubservicesConfig["service_type_id"];
            $SubservicesConfigIds = $deviceSubservicesConfig["main_conf_ids"];

            $subserviceSegments = $this->resolveServiceHavingConfigs($SubservicesConfigIds, "subservice", $serviceName, $serviceId, $subServiceId, $subServiceTypeId);

            //put all segments in one array
            $allsubserviceSegments = array_merge($allsubserviceSegments, $subserviceSegments);
        }

        return $allsubserviceSegments;
    }

    /*
     * @param ServiceSegmentsInfo $serviceSegmentsInfo containing [[[key=>value],...],...]  $serviceSegments
     */
    public function saveSegments($serviceSegmentsInfo, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        /* @var $serviceSegmentsInfo ServiceSegmentsInfo */
        $serviceSegments = $serviceSegmentsInfo->getSegments();
        $em = $this->_em;

        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceDescriptorHash();
        //$serviceDescriptor=$serviceSegmentsInfo->getServiceDescriptorHash();

        foreach ((array)$serviceSegments as $ind => $serviceSegmentdata) {

            $this->createNewSegmentRevision($serviceDescriptorHash, $serviceSegmentdata, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);

        }
        $em->flush(); //Persist objects that did not make up an entire batch
        $em->clear();

    }

    /*
     * @param double $serviceDescriptorHash
     * @param [] $serviceSegment [key=>value,...]
     *
     * @return LogiServiceSegment
     */
    public function createNewSegment($serviceDescriptorHash, $serviceSegmentData, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        $em = $this->_em;
        $logiServiceSegment = new LogiServiceSegment();

        //set service id from hash created for logi_service record
        $serviceId = $serviceDescriptorHash;//use this instead of human readable $serviceSegmentData["SERVICE_ID"] as identifier
        $logiServiceSegment->setSegmentServiceId($serviceId);

        $logiServiceSegment->setSegmentLogiConf1Id($serviceSegmentData["LOGI_CONF1_ID"]);
        $logiServiceSegment->setSegmentLogiConf2Id($serviceSegmentData["LOGI_CONF2_ID"]);
        if (isset($serviceSegmentData["CONNECTION_ID"])) {

            $logiServiceSegment->setSegmentPhysConId($serviceSegmentData["CONNECTION_ID"]);
        }


        if (isset($serviceSegmentData["SEGMENT_LOG_CON_ID"])) {
            $logConId = $serviceSegmentData["SEGMENT_LOG_CON_ID"];

            if (isset($serviceSegmentData["SEGMENT_LOG_CON_NAME"])) {
                $logConName = $serviceSegmentData["SEGMENT_LOG_CON_NAME"];
                $logiServiceSegment->setSegmentLogConId($logConId);
                $logiServiceSegment->setLogConName($logConName);
                if (isset($serviceSegmentData["SEGMENT_VIA"])) {
                    $logiServiceSegment->setSegmentVia($serviceSegmentData["SEGMENT_VIA"]);
                    //also ,if we are an implicit connection through MW or Leased line,do save our composition segments
                }
            } else {

                $originalLogConId = $logConId;
                $subserviceserviceId = null;
                if (!is_numeric($logConId)) {//for autogenerated patch panneled links
                    $subserviceSegmentDatasInfo = new ServiceSegmentsInfo();
                    $subserviceSegmentDatasInfo->setDescriptor($logConId);
                    $subserviceserviceId = $subserviceSegmentDatasInfo->getServiceId();
                    $logConId = $subserviceserviceId;
                }
                $logiServiceSegment->setSegmentLogConId($logConId);

                if ($subserviceserviceId == null) {
                    $logConName = $this->resolveLogicalConnectionName($originalLogConId);
                } else {
                    $logConName = $originalLogConId;
                }
                $logiServiceSegment->setLogConName($logConName);
            }

        }

        //set as is as false denoting we are in design phase
        $logiServiceSegment->setAsIs(0);
        $logiServiceSegment->setSourceId(1);
        $logiServiceSegment->setIsUndone(false);
        $logiServiceSegment->setAssociateEventId($assocEventId);
        $logiServiceSegment->setImpactedEventId($impactedEventId);


        //todo: @@@ IMPORTANT run this only once per Orion launch
        $this->fixTimestampIssue();
        //$dbConn = \DatabaseConnectionFactory::getInstance("inventory");
        $now = new \DateTime();
        $now->format("Y-m-d H:i:s");
        $logiServiceSegment->setSegmentCreated($now);

        $em->persist($logiServiceSegment);
        $em->flush();

        //now use the db allocated autoinc id as new segment physical id and persist it again
        $newSegmentId = $logiServiceSegment->getServiceSegmentId();
        $logiServiceSegment->setSegmentPhysicalId($newSegmentId);

        //Objectification:
        //now that we are substantiated,also assign id of us as our object id
        //$allocatedSegmentId=$logiServiceSegment->getServiceSegmentId();
        $logiServiceSegment->setSegmentObjectId($newSegmentId);

        $em->flush();


        return $logiServiceSegment;
    }

    public function fixTimestampIssue()
    {
        $sql = 'ALTER SESSION SET NLS_DATE_FORMAT = \'YY-mm-dd hh24:mi:ss\'';
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

        $sql = 'ALTER SESSION SET NLS_TIMESTAMP_FORMAT = \'YY-mm-dd hh24:mi:ss\'';
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

    }


    function saveViaSegment($logConId, $viaSegmentPhysConId, $assocEventId)
    {
        $em = $this->_em;
        $logiServiceSegment = $this->findOneBy(["physConId" => $viaSegmentPhysConId], ["serviceSegmentId" => "ASC"]);
        if ($logiServiceSegment == null) {
            $logiServiceSegment = new LogiServiceSegment();
        }

        $logiServiceSegment->setSegmentServiceId($logConId);
        //$logiServiceSegment->setServiceName($logConName);
        $logiServiceSegment->setSegmentPhysConId($viaSegmentPhysConId);

        $em->persist($logiServiceSegment);
        $em->flush();
        $segmentId = $logiServiceSegment->getServiceSegmentId();
        $logiServiceSegment->setSegmentObjectId($segmentId);
        $logiServiceSegment->setSegmentPhysicalId($segmentId);
        $logiServiceSegment->setAsIs(1);//for now we assume all MW and LL status as as is
        $logiServiceSegment->setAssociateEventId($assocEventId);
        $em->persist($logiServiceSegment);
        $em->flush();

        return $logiServiceSegment;
    }


    /*
     * @param double $serviceDescriptorHash,
     * @param [key=value,...] $serviceSegmentdata
     * @param integr $ttrId
     * @param integer $userId
     * @param integer $assocEvenIds
     * @param integer $impactedEventIds
     * @param integer $segmentsNewDDocStatus
     * @param integer $designStatus
     *
     * @return LogiServiceSegment
     */
    public function createNewSegmentRevision($serviceDescriptorHash, $serviceSegmentdata, $ttrId = null, $userId = null, $assocEvenId = null, $impactedEventId = null, $designStatus = null)
    {

        //first find segment having same log conf Ids and assume its physicalId as our physical Id
        //note: findBy is used instead of findOneBy,as we might need to move other revisions to history (see case "REPORT_NEW")
        if (isset($serviceSegmentdata["LOGI_CONF2_ID"])) {
            $logiServiceSegments = $this->findBy(["serviceId" => $serviceDescriptorHash, "logiConf1Id" => $serviceSegmentdata["LOGI_CONF1_ID"], "logiConf2Id" => $serviceSegmentdata["LOG_CONF2_ID"]], ["serviceSegmentId" => "ASC"]);
        } else {
            $logiServiceSegments = $this->findBy(["serviceId" => $serviceDescriptorHash, "logiConf1Id" => $serviceSegmentdata["LOGI_CONF1_ID"]], ["serviceSegmentId" => "ASC"]);

        }
        if (count($logiServiceSegments) == 0) {

            //we are brand new segment,so create us with a fresh new physical Id
            return $this->createNewSegment($serviceDescriptorHash, $serviceSegmentdata, $ttrId, $userId, $assocEvenId, $impactedEventId, $impactedEventId, $designStatus);

        }

        //use segment info to create a new revision with new segment id but physical Id same as physical id of originator
        //but object id as newly allocated segmentId
        $logiServiceSegmentToCopyFrom = $logiServiceSegments[0];

        $logiServiceSegment = clone $logiServiceSegmentToCopyFrom;

        $headSegmentId = $logiServiceSegmentToCopyFrom->getServiceSegmentId();
        //set segment Id to null to force new assignment when we push it back again to repo and use its identifier as object id
        $logiServiceSegment->setSegmentServiceId($logiServiceSegmentToCopyFrom->getSegmentServiceId());
        $logiServiceSegment->setServiceSegmentId(null);

        //for some reason,binary_double values are not fetched throgh doctrine.putting it explicitly for new segment again
        $logiServiceSegment->setSegmentServiceId($serviceDescriptorHash);

        $logiServiceSegment->setSegmentPhysicalId($logiServiceSegmentToCopyFrom->getSegmentPhysicalId());

        //set physicalId of this new revision same as its head counterpart
        $this->_em->persist($logiServiceSegment);
        $this->_em->flush();//do flush to get the id

        //now get the id of the newly inserted segment and assume it as segmentObjectId
        $newSegmentId = $logiServiceSegment->getServiceSegmentId();
        $logiServiceSegment->setSegmentObjectId($newSegmentId);
        $logiServiceSegment->setAssociateEventId($assocEvenId);
        $logiServiceSegment->setImpactedEventId($impactedEventId);
        $logiServiceSegment->setProposedDecommission(0);
        $logiServiceSegment->setSourceId(1);
        //$logiServiceSegment>setIsUndone(false);

        $now = new \DateTime();
        $logiServiceSegment->setSegmentCreated($now);
        $logiServiceSegment->setLastChanged($now);
        $logiServiceSegment->setLastUpdated($now);

        if (!$this->applyDesignStatusToSegments($designStatus, $logiServiceSegment, $logiServiceSegments)) {
            return;//if we are report new or report decom,we are done, so pls return;
        };

        $this->objectifySegment($logiServiceSegment);

        return $logiServiceSegment;

    }

    /*
     * @param $logiServiceSegment LogiServiceSegment
     *
     * @return integer $allocatedSegmentId
     */
    function objectifySegment(&$logiServiceSegment)
    {
        $this->_em->persist($logiServiceSegment);
        $this->_em->flush();

        //Objectification
        //now that we are substantiated,also assign id of us as our object id
        $allocatedSegmentId = $logiServiceSegment->getServiceSegmentId();
        $logiServiceSegment->setSegmentObjectId($allocatedSegmentId);
        $now = new \DateTime();
        $logiServiceSegment->setLastChanged($now);
        $logiServiceSegment->setLastUpdated($now);

        $this->_em->persist($logiServiceSegment);
        $this->_em->flush();

        return $allocatedSegmentId;

    }


    /*
     * @return true if caller needs to exit ,false otherwise
     *
     */
    function applyDesignStatusToSegments($designStatus, $logiServiceSegment, $logiServiceSegments)
    {

        if ($logiServiceSegments == null) {
            $logiServiceSegments = [];
            $logiServiceSegments[] = $logiServiceSegment;
        }
        switch ($designStatus) {
            case "REPORT_NEW_DIRECTLY":
                $logiServiceSegment->setAsIs(1);
                break;
            case "REPORT_NEW":  //propose and execute(set as is and move prev track to history)
                //Executing a segment:
                /* $this->moveAllExceptFirstToHistory($logiServiceSegments);
                 $logiServiceSegment->setAsIs(1);
                  break;*/
                $logiServiceSegment->setAsIs(1);
                $this->objectifySegment($logiServiceSegment);
                $this->moveAllToHistory($logiServiceSegments, null);
                return false;
                break;
            case "REPORT_DECOM_DIRECTLY"://this is processed prior to all of this and wont happen here
                break;
            case "REPORT_DECOM":  //propose and execute and delete(set as is and move full track to history)
                //Executing a segment:
                $logiServiceSegment->setProposedDecommission(1);
                $logiServiceSegment->setAsIs(1);
                $this->objectifySegment($logiServiceSegment);
                $this->moveAllToHistory($logiServiceSegments, $logiServiceSegment);

                return false;
                break;
            case "PROPOSE_NEW":
                break;
            case "PROPOSE_DECOM":
                $logiServiceSegment->setProposedDecommission(1);
                break;
        }

        return true;
    }


    /*
    * @param double $serviceDescriptorHash,
    * @param [key=value,...] $serviceSegmentdata
    * @param integr $ttrId
    * @param integer $userId
    * @param integer $assocEvenIds
    * @param integer $impactedEventIds
    * @param integer $segmentsNewDDocStatus
    * @param integer $designStatus
    *
    * @return LogiServiceSegment
    */
    public function createOrUpdateSegmentRevision($serviceDescriptorHash, $serviceSegmentdata, $ttrId = null, $userId = null, $assocEvenId = null, $impactedEventId = null, $designStatus = null)
    {

        //first find segment having same log conf Ids and assume its physicalId(or logical id) as our physical (or logical) Id
        if (isset($serviceSegmentdata["LOGI_CONF2_ID"])) {
            if (isset($serviceSegmentdata["SEGMENT_PHYS_CON_ID"])) {
                $logiServiceSegment = $this->findOneBy(["serviceId" => $serviceDescriptorHash, "logiConf1Id" => $serviceSegmentdata["LOGI_CONF1_ID"], "logiConf2Id" => $serviceSegmentdata["LOGI_CONF2_ID"], "logConId" => null], ["serviceSegmentId" => "ASC"]);
                if ($logiServiceSegment == null) { //also check if reverse segment present and reuse it if existing
                    $logiServiceSegment = $this->findOneBy(["serviceId" => $serviceDescriptorHash, "logiConf2Id" => $serviceSegmentdata["LOGI_CONF1_ID"], "logiConf1Id" => $serviceSegmentdata["LOGI_CONF2_ID"], "logConId" => null], ["serviceSegmentId" => "ASC"]);
                }
            } else {

                if (isset($serviceSegmentdata["SERVICE_ID"])) {//if this is set,we are forming a subservice placeholder on upstream

                    $serviceDescriptorHash = $serviceSegmentdata["SERVICE_ID"];
                }
                $logConId = $serviceSegmentdata["SEGMENT_LOG_CON_ID"];

                $logiServiceSegment = $this->findOneBy(["serviceId" => $serviceDescriptorHash, "logiConf1Id" => $serviceSegmentdata["LOGI_CONF1_ID"], "logiConf2Id" => $serviceSegmentdata["LOGI_CONF2_ID"], "physConId" => null, "logConId" => $logConId], ["serviceSegmentId" => "ASC"]);
                if ($logiServiceSegment == null) { //also check if reverse segment present and reuse it if existing
                    $logiServiceSegment = $this->findOneBy(["serviceId" => $serviceDescriptorHash, "logiConf2Id" => $serviceSegmentdata["LOGI_CONF1_ID"], "logiConf1Id" => $serviceSegmentdata["LOGI_CONF2_ID"], "physConId" => null, "logConId" => $logConId], ["serviceSegmentId" => "ASC"]);
                }
            }

            //remove orphan segments as we have two end points by now
            $this->removeOrphanSegmentsPassingThroughAnyOfLogConfs($serviceSegmentdata["LOGI_CONF1_ID"], $serviceSegmentdata["LOGI_CONF2_ID"]);
        } else {
            $logiServiceSegment = $this->findOneBy(["serviceId" => $serviceDescriptorHash, "logiConf1Id" => $serviceSegmentdata["LOGI_CONF1_ID"]], ["serviceSegmentId" => "ASC"]);
            if ($logiServiceSegment == null) { //also check if reverse segment present and reuse it if existing
                $logiServiceSegment = $this->findOneBy(["serviceId" => $serviceDescriptorHash, "logiConf2Id" => $serviceSegmentdata["LOGI_CONF1_ID"]], ["serviceSegmentId" => "ASC"]);
            }
        }

        if ($logiServiceSegment == null) {

            //we are brand new segment,so create us with a fresh new physical Id
            return $this->createNewSegment($serviceDescriptorHash, $serviceSegmentdata, $ttrId, $userId, $assocEvenId, $impactedEventId, $impactedEventId, $designStatus);

        }

        //$logiServiceSegment=clone $logiServiceSegmentToCopyFrom;
        //$logiServiceSegment=$logiServiceSegmentToCopyFrom;

        $headSegmentId = $logiServiceSegment->getServiceSegmentId();
        //set segment Id to null to force new assignment when we push it back again to repo and use its identifier as object id
        $logiServiceSegment->setSegmentServiceId($logiServiceSegment->getSegmentServiceId());
        //@@@ trying to overwrite prev object
        //$logiServiceSegment->setServiceSegmentId(null);

        //for some reason,binary_double values are not fetched through doctrine.putting it explicitly for new segment again
        $logiServiceSegment->setSegmentServiceId($serviceDescriptorHash);

        $logiServiceSegment->setSegmentPhysicalId($logiServiceSegment->getSegmentPhysicalId());


        //now get the id of the newly inserted segment and assume it as segmentObjectId
        //$newSegmentId=$logiServiceSegment->getServiceSegmentId();
        //$logiServiceSegment->setSegmentObjectId($newSegmentId);
        $logiServiceSegment->setAssociateEventId($assocEvenId);
        $logiServiceSegment->setImpactedEventId($impactedEventId);
        $logiServiceSegment->setProposedDecommission(0);
        $logiServiceSegment->setSourceId(1);
        //$logiServiceSegment>setIsUndone(false);

        $now = new \DateTime();
        $logiServiceSegment->setSegmentCreated($now);
        $logiServiceSegment->setLastChanged($now);
        $logiServiceSegment->setLastUpdated($now);


        if (!$this->applyDesignStatusToSegments($designStatus, $logiServiceSegment, null)) {
            //set physicalId of this new revision same as its head counterpart
            $this->_em->persist($logiServiceSegment);
            $this->_em->flush();//do flush to get the id

            return $logiServiceSegment;//if we are report new or report decom,we are done, so pls return;
        };

        //Objectification
        //now that we are substantiated,also assign id of us as our object id
        $allocatedSegmentId = $logiServiceSegment->getServiceSegmentId();
        $logiServiceSegment->setSegmentObjectId($allocatedSegmentId);
        $now = new \DateTime();
        $logiServiceSegment->setLastChanged($now);
        $logiServiceSegment->setLastUpdated($now);

        $this->_em->persist($logiServiceSegment);
        $this->_em->flush();


        return $logiServiceSegment;

    }


    private function removeOrphanSegmentsPassingThroughAnyOfLogConfs($logConfId1, $logConfId2)
    {
        $sql = "delete from logi_service_segment where
      (segment_logi_conf1_id=$logConfId1 and segment_logi_conf2_id is null)  or
      (segment_logi_conf2_id=$logConfId1 and segment_logi_conf1_id is null)  or
      (segment_logi_conf1_id=$logConfId2 and segment_logi_conf2_id is null) or
      (segment_logi_conf2_id=$logConfId2 and segment_logi_conf1_id is null)";

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();
    }


    /**
     * @param [key=>val,...] $serviceSegmentdata
     * @param [LogiServiceSegment] $logiServiceSegments
     *
     * @return LogiServiceSegment $logiServiceSegment
     */
    private function getMatchedSegmentByPhysicalConnectionOrLogicalOne($serviceSegmentdata, $logiServiceSegments)
    {

        $matchedServiceSegment = null;
        foreach ($logiServiceSegments as $logiServiceSegment) {
            if (isset($serviceSegmentdata["SEGMENT_LOG_CON_ID"])) {
                $logConId = $logiServiceSegment->getSegmentLogConId();
                if ($logConId == $serviceSegmentdata["SEGMENT_LOG_CON_ID"]) {
                    $matchedServiceSegment = $logiServiceSegment;
                    break;
                }

            } elseif (isset($serviceSegmentdata["CONNECTION_ID"])) {
                $physConId = $logiServiceSegment->getSegmentPhysConId();
                if ($physConId == $serviceSegmentdata["CONNECTION_ID"]) {
                    $matchedServiceSegment = $logiServiceSegment;
                    break;
                }

            } else {
                $matchedServiceSegment = $logiServiceSegment;
                break;
            }
        }

        return $matchedServiceSegment;
    }


    /*
     * @param [LogiServiceSegment] $logiServiceSegments
     */
    public function moveAllToHistory($logiServiceSegments, $logiServiceSegment)
    {

        $logiServiceSegmentHistoryrepository = new LogiServiceSegmentHistoryRepository($this->_em);

        foreach ($logiServiceSegments as $logiServiceSegmentItem) {

            //1)insert into history table
            //todo: enhance of architecture by moving this history repo outside the main repo for SOLID
            $logiServiceSegmentHistoryrepository->addServiceSegmentToHistory($logiServiceSegmentItem);

            //2)delete from main table
            $this->_em->remove($logiServiceSegmentItem);

        }
        //also remove temporarily created one from db(for the purpose of propose decom before delete)

        if ($logiServiceSegment != null) {
            $logiServiceSegmentHistoryrepository->addServiceSegmentToHistory($logiServiceSegment);
            $this->_em->remove($logiServiceSegment);
        }
        $this->_em->flush();
    }

    /*
     * @param [LogiServiceSegment] $logiServiceSegments
     */
    public function moveSegmentToHistory($logiServiceSegment, $undo)
    {

        $logiServiceSegmentHistoryrepository = new LogiServiceSegmentHistoryRepository($this->_em);

        //1)insert into history table
        //todo: enhance of architecture by moving this history repo outside the main repo for SOLID
        $logiServiceSegmentHistoryrepository->addServiceSegmentToHistory($logiServiceSegment);

        //2)delete from main table
        $this->_em->remove($logiServiceSegment);

        $this->_em->flush();
    }


    /* //todo: move this to netDataProvider to make this SOLID (non dependent on other repo)
     * @param integer $logConId
     * @return string $subserviceName
     */
    public function resolveLogicalConnectionName($logConId)
    {

        /* @var $logiServiceRepo LogiServiceRepository */
        $logiServiceRepo = new LogiServiceRepository($this->_em);
        //now save subservice place holders' service

        $subserviceName = $logiServiceRepo->getServiceName($subServiceId = $logConId);
        return $subserviceName;
    }


    private function getBasicSqlSelectForMainNodes($configIds, $serviceRole, $serviceName, $serviceId)
    {

        $lookupTable = " log_conf_indexed_val lciv ";
        $lookupTableJoin = " lc.log_conf_id=lciv.log_conf_object_id ";

        $configIdsStr = implode(",", $configIds);//AND lc2.parent_physical_id=cd2.card_object_id


        $basicSelectSqlCardLevel = " SELECT
                       lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       lc2.logical_entity_id as service_config_type,
                       '$serviceRole' as service_role,
                       '$serviceName' as service_name,
                       '$serviceId' as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name || '/' ||  slm.slot_acronym ||'/' || cdm.card_acronym   as EQ1_FULL_NAME,
                       eq.equipment_name || '/' ||  slm.slot_acronym ||'/' || cdm.card_acronym || '/' || im.interface_acronym  as INTF1_FULL_NAME,
                       eq2.equipment_name || '/' ||  slm2.slot_acronym ||'/' || cdm2.card_acronym || '/' || im2.interface_acronym  as INTF2_FULL_NAME,
                       eq2.equipment_name || '/' ||  slm2.slot_acronym ||'/' || cdm2.card_acronym   as EQ2_FULL_NAME,

                        eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          slm.slot_acronym as SLOT_ACR1,
                          cdm.card_acronym as CARD_ACR1,
                          cd.card_id as CARD_ID1,
                            im.interface_acronym as INTERFACE_ACR1,
                            con.interface_object_id1 as INTERFACE_OBJECT_ID1,
                              con.connection_id as CONNECTION_ID,
                            con.interface_object_id2 as INTERFACE_OBJECT_ID2,
                            im2.interface_acronym as INTERFACE_ACR2,
                          cd2.card_id as CARD_ID2,
                          cdm2.card_acronym as CARD_ACR2,
                          slm2.slot_acronym as SLOT_ACR2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2

                       FROM  $lookupTable
                       INNER JOIN logical_configuration lc  on ($lookupTableJoin)
                       INNER JOIN logical_entity le on (le.logical_entity_id=lc.logical_entity_id)
                       INNER JOIN card cd on (cd.card_object_id=lc.parent_physical_id)
                       INNER JOIN card_model cdm on (cdm.card_model_id=cd.card_model_id)
                       INNER JOIN slot sl on (sl.slot_id=cd.slot_id )
                       INNER JOIN slot_model slm on (slm.slot_model_id=sl.slot_model_id )
                       INNER JOIN equipment eq on (eq.equipment_id =sl.equipment_id)
                       INNER JOIN inv_site st on (st.SITE_ID=eq.SITE_ID)
                       INNER JOIN interface_connection_state ics on (ics.card_physical_id=cd.card_id)
                       INNER JOIN interface_model im on (im.interface_model_id=ics.interface_model_id)
                       INNER JOIN connection con on (con.interface_object_id1=ics.INTERFACE_OBJECT_ID)
                       INNER JOIN interface_connection_state ics2 on (ics2.INTERFACE_OBJECT_ID=con.interface_object_id2)
                       INNER JOIN interface_model im2 on (im2.interface_model_id=ics2.interface_model_id)
                       INNER JOIN card cd2 on (cd2.card_id=ics2.card_physical_id)
                       INNER JOIN card_model cdm2 on (cdm2.card_model_id=cd2.card_model_id)
                       INNER JOIN slot sl2 on (sl2.slot_id=cd2.slot_id )
                       INNER JOIN slot_model slm2 on (slm2.slot_model_id=sl2.slot_model_id )
                       INNER JOIN equipment eq2 on (eq2.equipment_id =sl2.equipment_id)
                       INNER JOIN inv_site st2 on (st2.SITE_ID=eq2.SITE_ID)
                       INNER JOIN logical_configuration lc2 on (lc2.LOGICAL_ENTITY_ID=lc.LOGICAL_ENTITY_ID)
                       INNER JOIN logical_entity le2 on (le2.logical_entity_id=lc2.logical_entity_id)
                       WHERE
                         lc.log_conf_id in ($configIdsStr) AND
                         lc.log_conf_id<>lc2.log_conf_id

                      ";


        //todo: correct overflow which happens in this sql result (model tables make some issue):

        $basicSelectSqlEqLevel = "
       select
                       lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       lc.logical_entity_id as service_config_type,
                       '$serviceRole' as service_role,
                       '$serviceName' as service_name,
                       '$serviceId' as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,

                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          cd.card_id as CARD_ID1,
                            con.interface_object_id1 as INTERFACE_OBJECT_ID1,
                              con.connection_id as CONNECTION_ID,
                            con.interface_object_id2 as INTERFACE_OBJECT_ID2,
                          cd2.card_id as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                       logical_entity le,
                        logical_configuration lc,
                         equipment eq,
                          inv_site st,
                          slot   sl,
                           card   cd,
                             interface_connection_state ics,
                              connection con,
                             interface_connection_state ics2,
                           card   cd2,
                          slot   sl2,
                          inv_site st2,
                        equipment eq2,
                       logical_configuration lc2,
                      logical_entity le2

                           where
                        lc.log_conf_id in ($configIdsStr) AND
                          lc2.log_conf_id in ($configIdsStr) AND
                          le.logical_entity_id=lc.logical_entity_id AND
                          eq.equipment_id=lc.parent_physical_id  AND
                            st.SITE_ID=eq.SITE_ID AND
                            sl.equipment_id=eq.equipment_id AND
                             cd.slot_id=sl.slot_id AND
                              ics.card_physical_id=cd.card_id AND
                               con.interface_object_id1=ics.interface_object_id AND
                                ics2.interface_object_id=con.interface_object_id2 AND
                                 cd2.card_id=ics2.card_physical_id AND
                                  sl2.slot_id=cd2.slot_id  AND
                                  eq2.equipment_id=sl2.equipment_id AND
                                   st2.SITE_ID=eq2.SITE_ID AND
                                   lc2.parent_physical_id=eq2.equipment_id  AND
                                    le2.logical_entity_id=lc2.logical_entity_id
                               ";


        //return $basicSelectSqlCardLevel;
        return $basicSelectSqlEqLevel;

    }


    private function getBasicSqlSelectForUnconnectedMainNodes($configIds, $serviceRole, $serviceName, $serviceId)
    {

        $logConfIdsStr = implode(",", $configIds);
        $basicSelectSql = "
     select
                       lc.log_conf_id as LOGI_CONF1_ID,
                       null as LOGI_CONF2_ID,
                       null as service_config_type,
                       '$serviceRole' as service_role,
                       '$serviceName' as service_name,
                       '$serviceId' as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null  as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       null  as EQ2_FULL_NAME,

                        eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          null as SLOT_ACR1,
                          null as CARD_ACR1,
                          null as CARD_ID1,
                             null as INTERFACE_ACR1,
                            null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                            null as INTERFACE_ACR2,
                          null as CARD_ID2,
                          null CARD_ACR2,
                          null as SLOT_ACR2,
                        null as EQUIPMENT_ID2,
                        null as EQUIPMENT_NAME2,
                       null as SITE2
                  from
                       logical_entity le,
                        logical_configuration lc,
                         equipment eq,
                          inv_site st
                          /*,
                          slot   sl,
                           slot_model slm ,
                           card   cd,
                            card_model cdm
                        */
                           where
                        lc.log_conf_id in ($logConfIdsStr) AND
                          le.logical_entity_id=lc.logical_entity_id AND
                          eq.equipment_id=lc.parent_physical_id  AND
                            st.SITE_ID=eq.SITE_ID

                            /* AND
                            sl.equipment_id=eq.equipment_id AND
                             slm.slot_model_id=sl.slot_model_id AND
                             cd.slot_id=sl.slot_id AND
                              cdm.card_model_id=cd.card_model_id
                           */";

        return $basicSelectSql;
    }


    private function getBasicSqlSelectForSubserviceNodes($configIds, $serviceRole, $serviceName, $serviceId, $subServiceId, $subServiceTypeId)
    {

        $startConfig = $configIds[0];
        $endConfig = $configIds[1];


        $basicInterfaceLevelSelectSql = "SELECT
                       lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       '$serviceRole' as service_role,
                       '$serviceName' as service_name,
                       '$serviceId' as service_id,
                       '$subServiceId' as SEGMENT_LOG_CON_ID,
                       st.site_name as SITE1,
                        eq.equipment_name as EQUIPMENT_NAME1,
                        eq.equipment_id as EQUIPMENT_ID1,
                         cd.card_id as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                         cd2.card_id as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2,
                       eq.equipment_name || '/' ||  slm.slot_acronym ||'/' || cdm.card_acronym   as EQ1_FULL_NAME,
                       null  as INTF1_FULL_NAME,
                       eq2.equipment_name || '/' ||  slm2.slot_acronym ||'/' || cdm2.card_acronym EQ2_FULL_NAME,
                       null   as INTF2_FULL_NAME

                       FROM
                       logical_configuration lc
                       ,logical_configuration lc2
                       ,logical_entity le
                       ,card cd
                       ,card_model cdm
                       ,slot sl
                       ,slot_model slm
                       , equipment eq
                       , inv_site st
                       ,card cd2
                       ,card_model cdm2
                       ,slot sl2
                       ,slot_model slm2
                       , equipment eq2
                       , inv_site st2
                       , logical_entity le2

                       WHERE
                         lc.log_conf_id=$startConfig AND
                        lc2.log_conf_id=$endConfig AND
                        le.logical_entity_id=lc.logical_entity_id AND
                        cd.card_id=lc.parent_physical_id  AND
                        cdm.card_model_id=cd.card_model_id AND
                        sl.slot_id=cd.slot_id AND
                        slm.slot_model_id=sl.slot_model_id AND
                        eq.equipment_id=sl.equipment_id AND
                        st.SITE_ID=eq.SITE_ID AND
                        cd2.card_id=lc2.parent_physical_id  AND
                        le2.logical_entity_id=lc2.logical_entity_id AND
                        cdm2.card_model_id=cd2.card_model_id AND
                        sl2.slot_id=cd2.slot_id AND
                        slm2.slot_model_id=sl2.slot_model_id AND
                        eq2.equipment_id=sl2.equipment_id AND
                        st2.SITE_ID=eq2.SITE_ID
                        ";
        /*
         *   ls.service_name as SEGMENT_LOG_CON_NAME, ls.service_id=$subServiceId AND ,logi_service ls
         */

        $basicEquipmentLevelSelectSql = "SELECT
                       lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       '$serviceRole' as service_role,
                       '$serviceName' as service_name,
                       '$serviceId' as service_id,
                       '$subServiceId' as SEGMENT_LOG_CON_ID,
                       st.site_name as SITE1,
                        eq.equipment_name as EQUIPMENT_NAME1,
                        eq.equipment_id as EQUIPMENT_ID1,
                         null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                         null as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2,
                       eq.equipment_name    as EQ1_FULL_NAME,
                       null  as INTF1_FULL_NAME,
                       eq2.equipment_name  EQ2_FULL_NAME,
                       null   as INTF2_FULL_NAME

                       FROM
                       logical_configuration lc
                       ,logical_configuration lc2
                       ,logical_entity le
                        , equipment eq
                       , inv_site st
                       , equipment eq2
                       , inv_site st2


                       WHERE
                         lc.log_conf_id=$startConfig AND
                        lc2.log_conf_id=$endConfig AND
                        le.logical_entity_id=lc.logical_entity_id AND
                        eq.equipment_id=lc.parent_physical_id AND
                        st.SITE_ID=eq.SITE_ID AND
                        eq2.equipment_id=lc2.parent_physical_id AND
                        st2.SITE_ID=eq2.SITE_ID
                        ";

        return $basicEquipmentLevelSelectSql;
    }


    /* //todo: add $serviceDiscoveryParams to filter configs on not only service type ,but also service values
     *
     * @param [[key=>value],...] $serviceDiscoveryParams discovery criteria on which service is recognised example: [[vlan_id=>'vlan123'],[subnet=>12345678],...]
     */
    private function getSqlForFindRadiatingMainSegmentsFromConfigIdWithExplicitDownstreams($logConfId, $lookupJoinTablesPartialSqlForServiceParams, $lookupWhereClausePartialSqlForServiceParams)
    {
        //@@@ todo: pass service id to here
        $serviceId = "";

        $sqlForEqOnlyConfs = "
    select         lc.log_conf_id as LOGI_CONF1_ID,
                   lc2.log_conf_id   as LOGI_CONF2_ID,
                       'main' as service_role,
                       le.entity_name as service_name,
                       '$serviceId' as service_id,
                       null as SEGMENT_LOG_CON_ID,
                       null as SITE1,
                        eq.equipment_name as EQUIPMENT_NAME1,
                        eq.equipment_id as EQUIPMENT_ID1,
                         null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              cn.connection_id  as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                         null as CARD_ID2,
                       eq.equipment_id as EQUIPMENT_ID2,
                       eq.equipment_name as EQUIPMENT_NAME2,
                       null as SITE2,
                       eq.equipment_name    as EQ1_FULL_NAME,
                       null  as INTF1_FULL_NAME,
                       eq2.equipment_name  EQ2_FULL_NAME,
                       null   as INTF2_FULL_NAME,
                       cn.connection_id as SEGMENT_PHYS_CON_ID
                 from
                       logical_entity le,
                        logical_configuration lc,
                         equipment eq,
                          slot   sl,
                           card   cd,
                            interface_connection_state ics,
                             connection cn,
                            interface_connection_state ics2,
                           card cd2,
                          slot sl2,
                         equipment eq2,
                        logical_configuration lc2,
                        logical_entity le2
                        $lookupJoinTablesPartialSqlForServiceParams
                            where
                        lc.log_conf_id=$logConfId AND
                          lc.parent_event_entity_id=1 AND
                          le.logical_entity_id=lc.logical_entity_id AND
                          eq.equipment_id=lc.parent_physical_id  AND
                            sl.equipment_id=eq.equipment_id AND
                             cd.slot_id=sl.slot_id  AND
                              ics.card_physical_id=cd.card_id AND
                                   ((cn.interface_object_id1=ics.interface_object_id AND ics2.interface_object_id= cn.interface_object_id2 AND cd2.card_id=ics2.card_physical_id) OR
                                 (cn.interface_object_id2=ics.interface_object_id AND ics2.interface_object_id= cn.interface_object_id1 AND cd2.card_id=ics2.card_physical_id)) AND
                            sl2.slot_id=cd2.slot_id AND
                           eq2.equipment_id=sl2.equipment_id AND
                           lc2.parent_event_entity_id=1 AND
                           lc2.parent_physical_id=eq2.equipment_id AND
                           lc2.logical_entity_id=lc.logical_entity_id AND
                            le2.logical_entity_id=lc2.logical_entity_id AND
                            lciv1.log_conf_object_id=$logConfId
                            $lookupWhereClausePartialSqlForServiceParams
                            AND lc.log_conf_id!=lc2.log_conf_id
    ";

        //todo: make a proper switch between these two cases of config on equipmens and configs on cards
        //todo: also add discriptor criteria to filter on same entity types but those who has the criteria

        return $sqlForEqOnlyConfs;
    }


    /*
    *
    * @param [[key=>value],...] $serviceDiscoveryParams discovery criteria on which service is recognised example: [[vlan_id=>'vlan123'],[subnet=>12345678],...]
    */
    private function getSqlForfindRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForThisUpstream($logConfId, $lookupJoinTablesPartialSqlForServiceParams, $lookupWhereClausePartialSqlForServiceParams, $serviceHashAsSubserviceId)
    {

        //todo: fill this properly to reflect main service names
        $serviceName = "";
        $serviceId = "";
        $subServiceId = $serviceHashAsSubserviceId;


        $newerSqlEq = "select
                       $logConfId as LOGI_CONF1_ID,
                       lcivd1s.log_conf_object_id as LOGI_CONF1_ID_S,
                       lciv1.log_conf_object_id  as LOGI_CONF2_ID,
                       lcivd2s.log_conf_object_id  as LOGI_CONF2_ID_S,
                       'subservice' as service_role,
                       '$serviceName' as service_name,
                       '$serviceId' as service_id,
                       '$subServiceId' as SEGMENT_LOG_CON_ID,
                       st1.site_name as SITE1,
                        d1eq.equipment_name as EQUIPMENT_NAME1,
                        d1eq.equipment_id as EQUIPMENT_ID1,
                         null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                         null as CARD_ID2,
                       d2eq.equipment_id as EQUIPMENT_ID2,
                       d2eq.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2,
                       null    as EQ1_FULL_NAME,
                       null  as INTF1_FULL_NAME,
                       null  EQ2_FULL_NAME,
                       null   as INTF2_FULL_NAME

                    from
                       logical_configuration d1lcm
                       ,logical_configuration d2lcm
                       ,equipment d1eq
                       ,inv_site st1
                       ,equipment d2eq
                       ,inv_site st2
                       $lookupJoinTablesPartialSqlForServiceParams
                       ,log_conf_indexed_val lcivd1s
                       ,log_conf_indexed_val lcivd1sp
                       ,log_conf_indexed_val lcivd2s
                       ,log_conf_indexed_val lcivd2sp


                      where
                      d1lcm.log_conf_id=$logConfId
                      AND  d1lcm.parent_event_entity_id=1
                      AND  d1eq.equipment_id=d1lcm.PARENT_PHYSICAL_ID
                      AND  st1.site_id=d1eq.site_id
                      $lookupWhereClausePartialSqlForServiceParams
                      AND lciv1.log_conf_object_id!=$logConfId
                      AND lcivd2s.param_name='hand_over_config' AND lcivd2s.int_val= lciv1.log_conf_object_id
                      AND lcivd1s.param_name='hand_over_config' AND lcivd1s.int_val= $logConfId
                      AND lcivd1sp.log_conf_object_id=lcivd1s.log_conf_object_id
                      AND lcivd2sp.log_conf_object_id=lcivd2s.log_conf_object_id
                      AND lcivd2sp.param_name=lcivd1sp.param_name AND lcivd2sp.string_val=lcivd1sp.string_val
                      AND d2lcm.log_conf_object_id=lciv1.log_conf_object_id
                      AND d2eq.equipment_id=d2lcm.parent_physical_id
                      AND  st2.site_id=d2eq.site_id";

        return $newerSqlEq;
    }

    /*
     *
     * @param [[key=>value],...] $serviceDiscoveryParams discovery criteria on which service is recognised example: [[vlan_id=>'vlan123'],[subnet=>12345678],...]
     */
    private function getSqlForfindRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForUpstreamOfThisService($logConfId, $lookupJoinTablesPartialSqlForServiceParams, $lookupWhereClausePartialSqlForServiceParams, $serviceHashAsSubserviceId)
    {

        //todo: fill this properly to reflect main service names
        $serviceName = "";
        $serviceId = "";
        $subServiceId = $serviceHashAsSubserviceId;

        $newerSqlEq = "select
                       $logConfId as LOGI_CONF1_ID_M,
                       d1lcu.log_conf_id as LOGI_CONF1_ID,
                       d2lcmiv.log_conf_object_id  as LOGI_CONF2_ID_M,
                       d2lcu.log_conf_id  as LOGI_CONF2_ID,

                       'subservice' as service_role,
                       '$serviceName' as service_name,
                       '$serviceId' as service_id,
                       '$subServiceId' as SEGMENT_LOG_CON_ID,
                       st1.site_name as SITE1,
                        d1eq.equipment_name as EQUIPMENT_NAME1,
                        d1eq.equipment_id as EQUIPMENT_ID1,
                         null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                         null as CARD_ID2,
                       d2eq.equipment_id as EQUIPMENT_ID2,
                       d2eq.equipment_name  as EQUIPMENT_NAME2,
                       st2.site_name as SITE2,
                       null    as EQ1_FULL_NAME,
                       null  as INTF1_FULL_NAME,
                       null  EQ2_FULL_NAME,
                       null   as INTF2_FULL_NAME

                    from
                       logical_configuration d1lcm
                       ,log_conf_indexed_val d1lcmiv
                       ,logical_configuration d1lcu
                       ,logical_configuration d2lcm
                       ,log_conf_indexed_val d2lcmiv
                       ,logical_configuration d2lcu
                       ,equipment d1eq
                       ,inv_site st1
                       ,equipment d2eq
                       ,inv_site st2
                       $lookupJoinTablesPartialSqlForServiceParams


                      where
                      d1lcm.log_conf_id=$logConfId
                      AND  d1lcm.parent_event_entity_id=1
                      AND  d1eq.equipment_id=d1lcm.PARENT_PHYSICAL_ID
                      AND  st1.site_id=d1eq.site_id
                      $lookupWhereClausePartialSqlForServiceParams
                      AND d1lcmiv.log_conf_object_id=$logConfId  AND d1lcmiv.param_name='hand_over_config' AND d1lcu.log_conf_id=d1lcmiv.int_val
                      AND d2lcmiv.log_conf_object_id=lciv1.log_conf_object_id AND d2lcmiv.log_conf_object_id!=$logConfId AND d2lcmiv.param_name='hand_over_config' AND d2lcu.log_conf_id=d2lcmiv.int_val
                      AND d2lcm.log_conf_id=lciv1.log_conf_object_id
                      AND d1eq.equipment_id=d1lcm.parent_physical_id
                      AND d2eq.equipment_id=d2lcm.parent_physical_id
                      AND  st1.site_id=d1eq.site_id
                      AND  st2.site_id=d2eq.site_id
                      ";

        return $newerSqlEq;
    }

    /*
     *  this will finds adjacent main segments running from focus device
     * @param integer  logical configuration id of the device on focus
     * @param integer $serviceDiscoveryParams
     */
    public function findRadiatingMainSegmentsFromConfigIdWithExplicitDownstreams($logConfId, $lookupJoinTablesPartialSql, $lookupWhereClausePartialSql)
    {

        //1) find other connected devices to this device

        //2) check confs of this device having same config type of this one (only search for config types of this service)
        //and then elaborate on corresponding values of $serviceDiscoveryParamsFromServiceType looked up from indexed_val

        $sql = $this->getSqlForFindRadiatingMainSegmentsFromConfigIdWithExplicitDownstreams($logConfId, $lookupJoinTablesPartialSql, $lookupWhereClausePartialSql);

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $mainServiceSegment = $stmt->fetchAll();

        return $mainServiceSegment;
    }

    /*
       *  this will find subservice placeholders that this config Id creates in this upstream service due to some subservices  which hand over to this
       * @param integer  logical configuration id of the device on focus
       * @param integer $serviceDiscoveryParams
       */
    public function findRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForThisUpstream($logConfId, $lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $serviceHashAsSubserviceId)
    {

        //1) find other connected devices to this device (which are connected through a subservice)

        //2) check confs of this device having same config type of this one (only search for config types of this service)
        //and then elaborate on corresponding values of $serviceDiscoveryParamsFromServiceType looked up from indexed_val

        $sql = $this->getSqlForfindRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForThisUpstream($logConfId, $lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $serviceHashAsSubserviceId);

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $subserviceSegment = $stmt->fetchAll();

        return $subserviceSegment;
    }


    /*
     *  this will find subservice placeholders that this config Id creates in the upstream of this service due to this service handing over to an upstream
     * @param integer  logical configuration id of the device on focus
     * @param integer $serviceDiscoveryParams
     */
    public function findRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForUpstreamOfThisService($logConfId, $lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $serviceHashAsSubserviceId)
    {

        //1) find other connected devices to this device (which are connected through a subservice)

        //2) check confs of this device having same config type of this one (only search for config types of this service)
        //and then elaborate on corresponding values of $serviceDiscoveryParamsFromServiceType looked up from indexed_val

        $sql = $this->getSqlForfindRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForUpstreamOfThisService($logConfId, $lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $serviceHashAsSubserviceId);

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $subserviceSegment = $stmt->fetchAll();

        return $subserviceSegment;
    }

    public function saveServiceSegments($parentServiceId, $serviceSegmentsAssoc, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {
        foreach ($serviceSegmentsAssoc as $serviceSegmentAssoc) {
            $serviceSegment = new LogiServiceSegment;
            $serviceSegment->setSegmentServiceId($parentServiceId);
            $serviceSegment->setSegmentLogiConf1Id($serviceSegmentAssoc["SEGMENT_LOGI_CONF1_ID"]);
            $serviceSegment->setSegmentLogiConf2Id($serviceSegmentAssoc["SEGMENT_LOGI_CONF2_ID"]);
            $serviceSegment->setSegmentPhysConId($serviceSegmentAssoc["SEGMENT_PHYS_CON_ID"]);
            $serviceSegment->setAssociateEventId($assocEventId);
            $serviceSegment->setImpactedEventId($impactedEventId);
            $this->add($serviceSegment);
        }
        $this->_em->flush();
    }

    public function resolveAllSegmentsFullInfoFromLogiSegments($serviceId)
    {

        $mainSegments = $this->resolveMainSegmentsFullInfoFromLogiSegments($serviceId);
        $mainPatchPaneledSegments = $this->resolvePatchPanelsAsMainSegmentsFullInfoFromLogiSegments($serviceId);
        $passiveConnectionMainSegments = $this->resolvePatchPanelsAsSubserviceMainSegmentsFullInfoFromLogiSegments($serviceId);

        $subserviceSegments = $this->resolveSubserviceSegmentsFullInfoFromLogiSegments($serviceId);
        $mainSegments = array_merge($mainPatchPaneledSegments, $mainSegments, $passiveConnectionMainSegments);//consider patch paneled segments also as main segments
        $connnectedNodesSegments = array_merge($subserviceSegments, $mainSegments);
        $rightNodeOrphanSegments = $this->resolveRightNodeOrphanSegmentsFullInfoFromLogiSegments($serviceId);

        $this->removeRedundancy($rightNodeOrphanSegments, $connnectedNodesSegments);
        $allsegments = array_merge($connnectedNodesSegments, $rightNodeOrphanSegments);

        return $allsegments;
    }

    public function resolveMW_OrLL_HumanReadableNamesForSegments(&$mainPatchPaneledSegments)
    {
        foreach ($mainPatchPaneledSegments as $key => $mainPatchPaneledSegment) {
            if (isset($mainPatchPaneledSegment["SEGMENT_VIA"])) {
                $conName = $this->resolveMW_OrLL_HumanReadableName($mainPatchPaneledSegment["SEGMENT_VIA"]);
                if ($conName != null) {
                    $conName = str_replace("\t", "", $conName);
                    $conName = str_replace("\n", "", $conName);
                    $mainPatchPaneledSegment["SERVICE_NAME"] = $conName;
                    $mainPatchPaneledSegment["SEGMENT_LOG_CON_NAME"] = $conName;
                    $mainPatchPaneledSegments[$key] = $mainPatchPaneledSegment;
                }
            }
        }
    }

    private function resolveMW_OrLL_HumanReadableName($viaStr)
    {

        if ($viaStr == null) {
            return null;
        }
        $viaStr = rtrim($viaStr, ",");
        $viaStrParts = explode(",", $viaStr);

        $nOfViaParts = count($viaStrParts);
        if ($nOfViaParts & 1) {//if odd number
            $partCountMiddle = ($nOfViaParts - 1) / 2;

            $centerConnectionId = $viaStrParts[$partCountMiddle];
            $connectionTypeId = $this->getConnectionType($centerConnectionId);

            $sql = $this->getSqlForResolveMW_OrLL_HumanReadableName($centerConnectionId, $connectionTypeId);
            $linkNameRec = $this->fetcthAll($sql);

            if (count($linkNameRec) > 0) {
                return $linkNameRec[0]["LINK_NAME"];
            }
        }
        return null;

    }

    private function getSqlForResolveMW_OrLL_HumanReadableName($linkConnectionId, $connectionTypeId)
    {
        switch ($connectionTypeId) {
            case 3://MW
                $sql = "select concat(mwcn.m_link_no,concat(' ACMA_',mwcn.m_acma_license))  as link_name
               from
                mw_connection mwcn,
                connection  cn
               where
                 cn.connection_id=$linkConnectionId
                 and mwcn.connection_object_id=cn.connection_id
            ";
                break;
            case 2://LL
                $sql = "select concat('LL_',lls.external_service_id) as link_name
                from
                    ll_link_interface llli,
                    ll_link lll,
                    ll_bearer_link llbl,
                    ll_service lls
                where
                  llli.connection_object_id=$linkConnectionId
                  and lll.link_id=llli.link_id
                  and llbl.link_id=lll.link_id
                  and  lls.b_end_bearer_id=llbl.bearer_id or lls.a_end_bearer_id=llbl.bearer_id
              "; //lli.connection_object_id,
                $sql = "select sa.service_id ||'-'|| sa.external_service_id as link_name from ll_link_interface lli left join
              ll_bearer_link lbl on lli.link_id=lbl.link_id
              left join ll_service sa on sa.b_end_bearer_id = lbl.bearer_id where lli.connection_object_id=$linkConnectionId";
                break;
        }
        return $sql;
    }

    private function getConnectionType($linkConnectionId)
    {
        $sql = "select connection_type_id from connection where connection_id=$linkConnectionId";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();
        $connectionTypeRecord = $stmt->fetchAll();

        if (count($connectionTypeRecord) > 0) {
            return $connectionTypeRecord[0]["CONNECTION_TYPE_ID"];
        }

        return null;

    }

    private function resolveMainSegmentsFullInfoFromLogiSegments($serviceId)
    {
        return $this->resolveSegmentsFullInfoFromLogiSegments($serviceId, $this->getSqlForResolveMainSegmentsFullInfoFromLogiSegments($serviceId));
    }

    private function resolvePatchPanelsAsMainSegmentsFullInfoFromLogiSegments($serviceId)
    {
        return $this->resolveSegmentsFullInfoFromLogiSegments($serviceId, $this->getSqlForResolvePatchPanelsAsMainSegmentsFullInfoFromLogiSegments($serviceId));
    }


    private function resolvePatchPanelsAsSubserviceMainSegmentsFullInfoFromLogiSegments($serviceId)
    {
        return $this->resolveSegmentsFullInfoFromLogiSegments($serviceId, $this->getSqlForResolvePatchPanelsAsSubserviceMainSegmentsFullInfoFromLogiSegments($serviceId));
    }


    private function resolveSubserviceSegmentsFullInfoFromLogiSegments($serviceId)
    {
        return $this->resolveSegmentsFullInfoFromLogiSegments($serviceId, $this->getSqlForResolveSubserviceSegmentsFullInfoFromLogiSegments($serviceId));
    }


    private function resolveRightNodeOrphanSegmentsFullInfoFromLogiSegments($serviceId)
    {
        return $this->resolveSegmentsFullInfoFromLogiSegments($serviceId, $this->getSqlForResolveRightNodeOrphanSegmentsFullInfoFromLogiSegments($serviceId));
    }

    private function resolveSegmentsFullInfoFromLogiSegments($serviceId, $sql)
    {
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

        $segments = $stmt->fetchAll();

        return $segments;

    }


    private function getSqlForResolveMainSegmentsFullInfoFromLogiSegments($serviceId)
    {

        $sql = "select
                       lss.segment_id lssid,
                       lss.segment_as_is asis,
                       lss.segment_proposed_decommission propdec,
                       lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       lc.logical_entity_id as service_config_type,
                       'main' as service_role,
                        ls.service_name as service_name,
                       '$serviceId' as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,
                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          cd.card_id as CARD_ID1,
                            con.interface_object_id1 as INTERFACE_OBJECT_ID1,
                              con.connection_id as CONNECTION_ID,
                            con.interface_object_id2 as INTERFACE_OBJECT_ID2,
                          cd2.card_id as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                       logi_service ls,
                       logi_service_segment lss,
                       logical_entity le,
                        logical_configuration lc,
                         equipment eq,
                          inv_site st,
                          slot   sl,
                           card   cd,
                             interface_connection_state ics,
                              connection con,
                             interface_connection_state ics2,
                           card   cd2,
                          slot   sl2,
                          inv_site st2,
                        equipment eq2,
                       logical_configuration lc2,
                      logical_entity le2
                           where
                        ls.service_id=$serviceId AND
                        lss.segment_service_id=$serviceId AND
                        lc.log_conf_id=lss.segment_logi_conf1_id AND
                         le.logical_entity_id=lc.logical_entity_id AND
                          eq.equipment_id=lc.parent_physical_id  AND
                           st.SITE_ID=eq.SITE_ID AND
                            sl.equipment_id=eq.equipment_id AND
                             cd.slot_id=sl.slot_id AND
                             (ics.card_physical_id=cd.card_id OR ics.card_physical_id=cd2.card_id)AND
                        lc2.log_conf_id=lss.segment_logi_conf2_id AND
                         le2.logical_entity_id=lc2.logical_entity_id AND
                          eq2.equipment_id=lc2.parent_physical_id  AND
                           st2.SITE_ID=eq2.SITE_ID AND
                            sl2.equipment_id=eq2.equipment_id  AND
                              cd2.slot_id=sl2.slot_id AND
                               (ics2.card_physical_id=cd2.card_id OR ics2.card_physical_id=cd.card_id) AND
                        con.connection_id=lss.segment_phys_con_id AND
                         con.interface_object_id1=ics.interface_object_id AND
                          con.interface_object_id2=ics2.interface_object_id
          ";

        return $sql;
    }


    private function getSqlForResolvePatchPanelsAsSubserviceMainSegmentsFullInfoFromLogiSegments($serviceId)
    {

        $sql = "select
                       lss.segment_id lssid,
                       null as LOGI_CONF1_ID,
                       null as LOGI_CONF2_ID,
                       null as service_config_type,
                       'main' as service_role,
                        lss.segment_log_con_name as service_name,
                        lss.segment_log_con_name as SEGMENT_LOG_CON_NAME,
                        lss.segment_via as segment_via,
                       lss.segment_log_con_id as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,
                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                           cd.card_id as CARD_ID1,
                           ics.interface_object_id as INTERFACE_OBJECT_ID1,
                              con.connection_id as CONNECTION_ID,
                           ics2.interface_object_id as INTERFACE_OBJECT_ID2,
                          cd2.card_id as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                       logi_service ls,
                        logi_service_segment lss,
                         equipment eq,
                          inv_site st,
                           card cd,
                           slot sl,
                           interface_connection_state ics ,
                            connection con,
                           interface_connection_state ics2 ,
                           card cd2,
                           slot sl2,
                          inv_site st2,
                         equipment eq2

                           where
                        ls.service_id=$serviceId
                        AND lss.segment_service_id=$serviceId
                        AND con.connection_id=lss.segment_phys_con_id
                         AND ics.interface_object_id=con.interface_object_id1
                          AND cd.card_id=ics.card_physical_id
                          AND sl.slot_id=cd.slot_id
                           AND eq.equipment_id=sl.equipment_id

                            AND st.SITE_ID=eq.SITE_ID
                         AND ics2.interface_object_id=con.interface_object_id2
                          AND cd2.card_id=ics2.card_physical_id
                           AND sl2.slot_id=cd2.slot_id
                           AND eq2.equipment_id=sl2.equipment_id
                            AND  st2.SITE_ID=eq2.SITE_ID

          ";

        return $sql;
    }


    private function getSqlForResolvePatchPanelsAsMainSegmentsFullInfoFromLogiSegments($serviceId)
    {


        $sql = "select
                       lss.segment_id lssid,
                       lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       lc.logical_entity_id as service_config_type,
                       'subservice' as service_role,
                        lss.segment_log_con_name as service_name,
                        lss.segment_log_con_name as SEGMENT_LOG_CON_NAME,
                        lss.segment_via as segment_via,
                       lss.segment_log_con_id as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,
                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                           null as CARD_ID1,
                           null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                           null as INTERFACE_OBJECT_ID2,
                          null as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                       logi_service ls,
                       logi_service_segment lss,
                       logical_entity le,
                        logical_configuration lc,
                         equipment eq,
                          inv_site st,
                          inv_site st2,
                        equipment eq2,
                       logical_configuration lc2,
                      logical_entity le2
                           where
                        ls.service_id=$serviceId AND
                        lss.segment_service_id=$serviceId AND
                        lss.segment_log_con_id is not null AND
                        lc.log_conf_id=lss.segment_logi_conf1_id AND
                         le.logical_entity_id=lc.logical_entity_id AND
                          eq.equipment_id=lc.parent_physical_id  AND
                           st.SITE_ID=eq.SITE_ID AND
                        lc2.log_conf_id=lss.segment_logi_conf2_id AND
                         le2.logical_entity_id=lc2.logical_entity_id AND
                          eq2.equipment_id=lc2.parent_physical_id  AND
                           st2.SITE_ID=eq2.SITE_ID

          ";

        return $sql;
    }

    private function getSqlForResolvePatchPaneledMainSegmentsFullInfoFromLogiSegments($serviceId)
    {
        $sql = " select
                       null as LOGI_CONF1_ID,
                       null as LOGI_CONF2_ID,
                       null as service_config_type,
                       'main' as service_role,
                        'ppaneled'  as service_complexity,
                        ls.service_name as service_name,
                       '$serviceId' as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,

                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          cd.card_id as CARD_ID1,
                            con.interface_object_id1 as INTERFACE_OBJECT_ID1,
                              con.connection_id as CONNECTION_ID,
                            con.interface_object_id2 as INTERFACE_OBJECT_ID2,
                          cd2.card_id as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                       logi_service ls,
                       logi_service_segment lss,
                         equipment eq,
                          inv_site st,
                          slot   sl,
                           card   cd,
                             interface_connection_state ics,
                              connection con,
                             interface_connection_state ics2,
                           card   cd2,
                          slot   sl2,
                          inv_site st2,
                        equipment eq2
                           where
                         ls.service_id=$serviceId AND
                         lss.segment_service_id=$serviceId AND
                         con.connection_id=lss.segment_phys_con_id AND
                         ics.interface_object_id=con.interface_object_id1 AND
                         ics2.interface_object_id=con.interface_object_id2 AND
                          cd.card_id=ics.card_physical_id AND
                           sl.slot_id=cd.slot_id AND
                            eq.equipment_id=sl.equipment_id AND
                             st.SITE_ID=eq.SITE_ID AND
                             cd2.card_id=ics2.card_physical_id AND
                             sl2.slot_id=cd2.slot_id  AND
                            eq2.equipment_id=sl2.equipment_id AND
                           st2.SITE_ID=eq2.SITE_ID

          ";

        return $sql;
    }

    public function resolveServiceSegmentFullInfoFromNetworkNode($networkNode)
    {

        if ($networkNode->leftConnectionId == null) {
            return null;
        }
        if ($networkNode->leftId == null) {
            return null;
        }

        if ($networkNode->leftConnectionType == "physical") {
            $physConId = $networkNode->leftConnectionId;
            $sql = " select
                       null as LOGI_CONF1_ID,
                       null as LOGI_CONF2_ID,
                       null as service_config_type,
                       'main' as service_role,
                        'ppaneled'  as service_complexity,
                        null as service_name,
                       null as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,

                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          cd.card_id as CARD_ID1,
                            con.interface_object_id1 as INTERFACE_OBJECT_ID1,
                              con.connection_id as CONNECTION_ID,
                              null as SEGMENT_LOG_CON_ID,
                              null as SEGMENT_LOG_CON_NAME,
                            con.interface_object_id2 as INTERFACE_OBJECT_ID2,
                          cd2.card_id as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                         equipment eq,
                          inv_site st,
                          slot   sl,
                           card   cd,
                             interface_connection_state ics,
                              connection con,
                             interface_connection_state ics2,
                           card   cd2,
                          slot   sl2,
                          inv_site st2,
                        equipment eq2
                           where
                         con.connection_id=$physConId AND
                         ics.interface_object_id=con.interface_object_id1 AND
                         ics2.interface_object_id=con.interface_object_id2 AND
                          cd.card_id=ics.card_physical_id AND
                           sl.slot_id=cd.slot_id AND
                            eq.equipment_id=sl.equipment_id AND
                             st.SITE_ID=eq.SITE_ID AND
                             cd2.card_id=ics2.card_physical_id AND
                             sl2.slot_id=cd2.slot_id  AND
                            eq2.equipment_id=sl2.equipment_id AND
                           st2.SITE_ID=eq2.SITE_ID

          ";
        } else { //physical connection is null but logical exists
            $logConId = $networkNode->leftConnectionId;
            $logConName = $networkNode->leftConnectionName;
            $eqId = $networkNode->id;
            $eqId2 = $networkNode->leftId;
            $viaStr = $networkNode->viaStr;
            $sql = " select
                       null as LOGI_CONF1_ID,
                       null as LOGI_CONF2_ID,
                       null as service_config_type,
                       'subservice' as service_role,
                        'ppaneled'  as service_complexity,
                        null as service_name,
                       null as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,
                         eq.equipment_name as EQUIPMENT_NAME1,
                          $eqId as EQUIPMENT_ID1,
                          null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                               '$logConId' as SEGMENT_LOG_CON_ID,
                               '$logConName' as SEGMENT_LOG_CON_NAME,
                               '$viaStr' as SEGMENT_VIA,
                               null as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                         null as CARD_ID2,
                         $eqId2 as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                         equipment eq,
                          inv_site st,
                          inv_site st2,
                        equipment eq2
                           where

                            eq.equipment_id=$eqId AND
                             st.SITE_ID=eq.SITE_ID AND
                            eq2.equipment_id=$eqId2 AND
                           st2.SITE_ID=eq2.SITE_ID

          ";
        }


        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

        $segments = $stmt->fetchAll();


        return $segments;

    }


    private function getSqlForResolveSubserviceSegmentsFullInfoFromLogiSegments($serviceId)
    {

        $sql = " select
                       lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       lc.logical_entity_id as service_config_type,
                       'subservice' as service_role,
                        ls.service_name as service_name,
                       '$serviceId' as service_id,
                       NVL(ls_sub.service_name,lss.segment_log_con_name) as SEGMENT_LOG_CON_NAME,
                       lss.segment_log_con_id as SEGMENT_LOG_CON_ID,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       eq2.equipment_name as EQ2_FULL_NAME,
                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                           null as INTERFACE_OBJECT_ID2,
                          null as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2
                  from
                       logi_service ls,
                       logi_service ls_sub,
                       logi_service_segment lss,
                       logical_entity le,
                        logical_configuration lc,
                         equipment eq,
                          inv_site st,
                          inv_site st2,
                         equipment eq2,
                        logical_configuration lc2,
                       logical_entity le2
                           where
                        ls.service_id=$serviceId AND
                        lss.segment_service_id=$serviceId AND
                         ls_sub.service_id=lss.segment_log_con_id AND
                         lc.log_conf_id=lss.segment_logi_conf1_id AND
                         lc2.log_conf_id=lss.segment_logi_conf2_id AND
                           le.logical_entity_id=lc.logical_entity_id AND
                           eq.equipment_id=lc.parent_physical_id  AND
                            st.SITE_ID=eq.SITE_ID AND
                           eq2.equipment_id=lc2.parent_physical_id AND
                             st2.SITE_ID=eq2.SITE_ID AND
                             le2.logical_entity_id=lc2.logical_entity_id

          ";

        return $sql;
    }


    private function getSqlForResolveRightNodeOrphanSegmentsFullInfoFromLogiSegments($serviceId)
    {

        $sql = " select
                       lc.log_conf_id as LOGI_CONF1_ID,
                        null as LOGI_CONF2_ID,
                       lc.logical_entity_id as service_config_type,
                       'main' as service_role,
                        ls.service_name as service_name,
                       '$serviceId' as service_id,
                       st.site_name as SITE1,
                       eq.equipment_name   as EQ1_FULL_NAME,
                       null   as INTF1_FULL_NAME,
                       null  as INTF2_FULL_NAME,
                       null as EQ2_FULL_NAME,
                         eq.equipment_name as EQUIPMENT_NAME1,
                          eq.equipment_id as EQUIPMENT_ID1,
                          null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              null CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                          null as CARD_ID2,
                        null as EQUIPMENT_ID2,
                        null as EQUIPMENT_NAME2,
                       null as SITE2
                  from
                       logi_service ls,
                       logi_service_segment lss,
                       logical_entity le,
                        logical_configuration lc,
                         equipment eq,
                          inv_site st
                  where
                        ls.service_id=$serviceId AND
                        lss.segment_service_id=$serviceId AND
                        lc.log_conf_id=lss.segment_logi_conf1_id AND
                           le.logical_entity_id=lc.logical_entity_id AND
                          eq.equipment_id=lc.parent_physical_id  AND
                            st.SITE_ID=eq.SITE_ID

          ";

        return $sql;
    }

    /* removes items from non connected segments these who are connected
     * @var [[key=>val],...] &$nonConnectedServiceSegments
     * @var [[key=>val],...] &$connectedServiceSegments
     */
    private function removeRedundancy(&$nonConnectedServiceSegments, &$connectedServiceSegments)
    {

        $existingIndexes = [];
        foreach ($nonConnectedServiceSegments as $index => $nonConnectedServiceSegment) {
            foreach ($connectedServiceSegments as $segment) {

                if (
                    ($segment["LOGI_CONF1_ID"] == $nonConnectedServiceSegment["LOGI_CONF1_ID"]) ||
                    ($segment["LOGI_CONF2_ID"] == $nonConnectedServiceSegment["LOGI_CONF1_ID"]) ||
                    ($segment["LOGI_CONF1_ID"] == $nonConnectedServiceSegment["LOGI_CONF2_ID"]) ||
                    ($segment["LOGI_CONF2_ID"] == $nonConnectedServiceSegment["LOGI_CONF2_ID"])
                ) {
                    $existingIndexes[$index] = $index;
                }
            }
        }

        foreach ($existingIndexes as $existingIndex) {
            unset($nonConnectedServiceSegments[$existingIndex]);
        }
    }

    public function removeAllByServiceId($serviceId)
    {
        $sql = "delete from logi_service_segment where segment_service_id=$serviceId";
        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

    }

    public function removeAllByConfigId($logConfId)
    {
        $sql = "delete from logi_service_segment where (segment_logi_conf1_id=$logConfId) OR (segment_logi_conf2_id=$logConfId) ";
        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

    }

    public function removeByLogConId($logConId)
    {
        $sql = "delete from logi_service_segment where segment_log_con_id=$logConId ";
        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

    }


    /*
    *  creates segments of type subservice which are also running on the same set of devices discovered in this service
    * @var $configIds configuration already discovered on this service
    * @$serviceEntityId integer service type
    * @param [integer] $configIds
    * @return ServiceSegmentsInfo $serviceSegmentsInfo which will contain these segments
    */
    public function discoverImplicitHandoverConfigsAndResolveImplicitSubServiceSegments($deviceConfigIds, $mainServiceTypeId, $serviceSegmentsInfo, $mainServiceId)
    {
        $sql = $this->getSqlForDiscoverImplicitHandoverConfigsAndResolveImplicitSubServiceSegments($deviceConfigIds, $mainServiceTypeId, $serviceSegmentsInfo, $mainServiceId);
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

        $segmentsInOneGo = $stmt->fetchAll();

        if (count($segmentsInOneGo) > 0) {
            $segments[0] = [
                "service_type1" => $segmentsInOneGo[0]["SERVICE_TYPE1"],
                "log_conf1_id" => $segmentsInOneGo[0]["LOG_CONF1_ID"],
                "site_name" => $segmentsInOneGo[0]["SITE_NAME"],
                "eq_name" => $segmentsInOneGo[0]["EQ_NAME"],
                "slot_id" => $segmentsInOneGo[0]["SLOT_ID"],
                "card_id" => $segmentsInOneGo[0]["CARD_ID"],
                "interface_object_id" => $segmentsInOneGo[0]["INTERFACE_OBJECT_ID"],
                "con_id" => $segmentsInOneGo[0]["CON_ID"],
                "interface_object_id2" => $segmentsInOneGo[0]["INTERFACE_OBJECT_ID2"],
                "card2_id" => $segmentsInOneGo[0]["CARD2_ID"],
                "slot2_id" => $segmentsInOneGo[0]["SLOT2_ID"],
                "eq2_name" => $segmentsInOneGo[0]["EQ2_NAME"],
                "site2_name" => $segmentsInOneGo[0]["SITE2_NAME"],
                "log_conf2_id" => null,
                "service_type2" => null,

            ];
            $segments[1] = [
                "service_type1" => null,
                "log_conf1_id" => null,
                "site_name" => $segmentsInOneGo[0]["SITE2_NAME"],
                "eq_name" => $segmentsInOneGo[0]["EQ2_NAME"],
                "slot_id" => $segmentsInOneGo[0]["SLOT2_ID"],
                "card_id" => $segmentsInOneGo[0]["CARD2_ID"],
                "interface_object_id" => $segmentsInOneGo[0]["INTERFACE_OBJECT_ID2"],
                "con_id" => $segmentsInOneGo[0]["LEASEDORMW_ID"],
                "interface_object_id2" => $segmentsInOneGo[0]["INTERFACE_OBJECT_ID3"],
                "card2_id" => $segmentsInOneGo[0]["CARD3_ID"],
                "slot2_id" => $segmentsInOneGo[0]["SLOT3_ID"],
                "eq2_name" => $segmentsInOneGo[0]["EQ3_NAME"],
                "site2_name" => $segmentsInOneGo[0]["SITE3_NAME"],
                "log_conf2_id" => null,
                "service_type2" => null,
            ];

            $segments[2] = [
                "service_type1" => null,
                "log_conf1_id" => null,
                "site_name" => $segmentsInOneGo[0]["SITE3_NAME"],
                "eq_name" => $segmentsInOneGo[0]["EQ3_NAME"],
                "slot_id" => $segmentsInOneGo[0]["SLOT3_ID"],
                "card_id" => $segmentsInOneGo[0]["CARD3_ID"],
                "interface_object_id" => $segmentsInOneGo[0]["INTERFACE_OBJECT_ID3"],
                "con_id" => $segmentsInOneGo[0]["CON2_ID"],
                "interface_object_id2" => $segmentsInOneGo[0]["INTERFACE_OBJECT_ID4"],
                "card2_id" => $segmentsInOneGo[0]["CARD4_ID"],
                "slot2_id" => $segmentsInOneGo[0]["SLOT4_ID"],
                "eq2_name" => $segmentsInOneGo[0]["EQ4_NAME"],
                "site2_name" => $segmentsInOneGo[0]["SITE4_NAME"],
                "log_conf2_id" => $segmentsInOneGo[0]["LOG_CONF4_ID"],
                "service_type2" => $segmentsInOneGo[0]["SERVICE_TYPE4"],

            ];
        } else {
            $segments = [];
        }

        $m = 0;

        /*
            $serviceSegmentsInfo=new ServiceSegmentsInfo();
            $serviceSegmentsInfo->setSegments($segments);*/
        $serviceSegmentsInfo = [];
        return $serviceSegmentsInfo;

    }

    /*
     *  creates segments of type subservice which are also running on the same set of devices discovered in this service
     * @var $configIds configuration already discovered on this service
     * @$serviceEntityId integer service type
     * @param [integer] $configIds
     * @return ServiceSegmentsInfo $serviceSegmentsInfo which will contain these segments
     */
    public function getSqlForDiscoverImplicitHandoverConfigsAndResolveImplicitSubServiceSegments($deviceConfigIds, $mainServiceTypeId, $serviceSegmentsInfo, $mainServiceId)
    {

        $configIdsStr = implode(",", $deviceConfigIds);//AND lc2.parent_physical_id=cd2.card_object_id


        $eqBasedSql = "
    select
	                  le.entity_name as service_type1,
                       lc.log_conf_object_id as log_conf1_id,
						st.site_name as site_name,
						 eq.equipment_name as eq_name,
                          sl.slot_id as slot_id,

                            cd.card_id as card_id,
                              ics.interface_object_id as interface_object_id,
                               con.connection_id as con_id,
                              ics2.interface_object_id as interface_object_id2,
                            cd2.card_id as card2_id,
                          sl2.slot_id as slot2_id,
                         st2.site_name as site2_name,
                          eq2.equipment_name as eq2_name,
						   bps.equipment_bypass_id as bps1_id,
						    ics2p.interface_object_id as interface_object_id2p,
								                  					  leasedOrMW.connection_id as leasedOrMW_Id,
						    ics3p.interface_object_id as interface_object_id3p,
						   bps.equipment_bypass_id as bps2_id,
                         st3.site_name as site3_name,
                          eq3.equipment_name as eq3_name,
                           sl3.slot_id as slot3_id,
                            cd3.card_id as card3_id,
                             ics3.interface_object_id as interface_object_id3,
                              con2.connection_id as con2_id,
                             ics4.interface_object_id as interface_object_id4,

                            cd4.card_id as card4_id,
                           sl4.slot_id as slot4_id,
                         st4.site_name as site4_name,
                          eq4.equipment_name as eq4_name,
                        lc4.log_conf_id as log_conf4_id,
                       le4.entity_name as service_type4
                  from
                      logical_entity le,
                       logical_configuration lc,
					              equipment eq,
                       inv_site st,
                          slot   sl,
                           card   cd,
                             interface_connection_state ics,
                                                 connection con,
                             interface_connection_state ics2,
                           card   cd2,
                          slot   sl2,
                          inv_site st2,
                        equipment eq2,
						     equipment_bypass bps,
						        interface_connection_state ics2p,
                           					  connection leasedOrMW,
								    interface_connection_state ics3p,
							equipment_bypass bps2,
                       inv_site st3,
						equipment eq3,
						  slot   sl3,
                           card   cd3,
                             interface_connection_state ics3,
                                                 connection con2,
                             interface_connection_state ics4,
                           card   cd4,
                          slot   sl4,
                       inv_site st4,
						equipment eq4,
                       logical_configuration lc4,
                      logical_entity le4

                       where
                        lc.log_conf_id in (460906,460905,460903,460904)
                        AND lc4.log_conf_id in (460906,460905,460903,460904)
					    AND lc.log_conf_id<>lc4.log_conf_id
					      AND le.logical_entity_id=lc.logical_entity_id
                         AND eq.equipment_id=lc.parent_physical_id
                        AND st.SITE_ID=eq.SITE_ID
                          AND sl.equipment_id=eq.equipment_id
                           AND cd.slot_id=sl.slot_id
                            AND ics.card_physical_id=cd.card_id
                             AND con.interface_object_id1=ics.interface_object_id
                            AND ics2.interface_object_id=con.interface_object_id2
                           AND cd2.card_id=ics2.card_physical_id
                          AND sl2.slot_id=cd2.slot_id
						   AND eq2.equipment_id=sl2.equipment_id
						    AND st2.SITE_ID=eq2.SITE_ID
						     AND bps.card_model_id=cd2.card_model_id AND  bps.interface1_model=ics2.interface_model_id
						      AND ics2p.card_physical_id=cd2.card_id  AND  ics2p.interface_model_id=bps.interface2_model
							   AND leasedOrMW.interface_object_id1=ics2p.interface_object_id
						      AND ics3p.interface_object_id=leasedOrMW.interface_object_id2
						     AND bps2.interface1_model=ics3p.interface_model_id
                            AND  cd3.card_id=ics3p.card_physical_id AND  cd3.card_model_id=bps2.card_model_id
					       AND sl3.slot_id=cd3.slot_id
                          AND eq3.equipment_id=sl3.equipment_id
					     AND st3.SITE_ID=eq3.SITE_ID
						   AND ics3.card_physical_id=cd3.card_id AND ics3.interface_model_id=bps2.interface2_model
                            AND con2.interface_object_id2=ics3.interface_object_id
                           AND ics4.interface_object_id=con2.interface_object_id1
                         AND cd4.card_id=ics4.card_physical_id
                        AND sl4.slot_id=cd4.slot_id
					   AND eq4.equipment_id=sl4.equipment_id
					  AND st4.SITE_ID=eq4.SITE_ID
					 AND lc4.parent_physical_id=eq4.equipment_id
					AND le4.logical_entity_id=lc4.logical_entity_id
					          ";

        $tmpForReturn = "    lc.log_conf_id as LOGI_CONF1_ID,
                       lc2.log_conf_id as LOGI_CONF2_ID,
                       'serviceRole' as service_role,
                       'serviceName' as service_name,
                       'serviceId' as service_id,
                       'subServiceId' as SEGMENT_LOG_CON_ID,
                       st.site_name as SITE1,
                        eq.equipment_name as EQUIPMENT_NAME1,
                        eq.equipment_id as EQUIPMENT_ID1,
                         null as CARD_ID1,
                            null as INTERFACE_OBJECT_ID1,
                              null as CONNECTION_ID,
                            null as INTERFACE_OBJECT_ID2,
                         null as CARD_ID2,
                        eq2.equipment_id as EQUIPMENT_ID2,
                        eq2.equipment_name as EQUIPMENT_NAME2,
                       st2.site_name as SITE2,
                       eq.equipment_name    as EQ1_FULL_NAME,
                       null  as INTF1_FULL_NAME,
                       eq2.equipment_name  EQ2_FULL_NAME,
                       null   as INTF2_FULL_NAME
                        ";
        return $eqBasedSql;
    }

    /*
     *  To make leased line discovery sql easier ,we make equipment bypass relation of interface1 and interface 2
     *  agnostic to the order of these interfaces in this table,by duplicating each row by its reveresed order interfaces(int2,int1) as well as (int1,int2)
     */
    function helperAddReverseInterfacesInEquipmentBypassRelation()
    {

        $sql = "select * from equipment_bypass";
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

        $int1int2s = $stmt->fetchAll();

        $int2int1s = [];
        foreach ($int1int2s as $int1int2) {

            $int2int1 = $int1int2;
            $int2int1["EQUIPMENT_BYPASS_ID"] = null;
            $int2int1["INTERFACE2_MODEL"] = $int1int2["INTERFACE1_MODEL"];
            $int2int1["INTERFACE1_MODEL"] = $int1int2["INTERFACE2_MODEL"];
            $int2int1s[] = $int2int1;
        }

        $m = 0;
        $id = 668;
        foreach ($int2int1s as $int2int1) {
            try {
                $sql = "insert into equipment_bypass (equipment_bypass_id,card_model_id,interface1_model,interface2_model) values($id,{$int2int1['CARD_MODEL_ID']},{$int2int1['INTERFACE1_MODEL']},{$int2int1['INTERFACE2_MODEL']})";

                $id++;
                $stmt = $this->_em->getConnection()->prepare($sql);
                $stmt->execute();
            } catch (\Exception $e) {
                $message = $e->getMessage();
                $m = 0;
                break;
            }
        }
    }

    function getEquipmentIds($logConfIds)
    {

        $logConfIdsStr = implode(",", $logConfIds);
        //eq ids using "in" shuffles order therefore is not  usefull
        $sql = "select equipment.equipment_id from equipment,logical_configuration where equipment.equipment_id=logical_configuration.parent_physical_id and logical_configuration.log_conf_id in ($logConfIdsStr) ";

        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

        $results = $stmt->fetchAll();

        $eqIds = [];
        foreach ($results as $result) {
            $eqIds[] = $result["EQUIPMENT_ID"];
        }
        return $eqIds;
    }

    function getEquipmentIdsMock($logConfIds)
    {
        //return [214521,214520,214519,214518];
        return [214518, 214519, 214520, 214521];

    }


    function getNodeIdsOfNetworkNodes($equipmentNodes)
    {
        $eqqIds = [];
        foreach ($equipmentNodes as $equipmentNode) {

            $eqIds[] = $equipmentNode->id;
        }
        return $eqIds;
    }


    function getNextConnectedEqipmentsToEquipment($nodeId, $eqNodeIds)
    {

        //keep this for detached from db debugging (bypassing next paragraph)
        // $next=[1=>[2,"con1","physical"],2=>[3,"con2","physical"],/*3=>[4,"con3","physical"],*/4=>[5,"con4","physical"],5=>[1,"con5"],7=>[8,"con6","physical"],8=>[9,"con7","physical"],11=>[12,"con_8","physical"],17=>[18,"con9","physical"],/*18=>[19,"con_10","physical"],*/19=>[20,"con11","physical"],20=>[17,"con12","physical"]];

        $sql = $this->getSqlForGetNextConnectedEqipmentsToEquipment($nodeId, $eqNodeIds);
        $nextEquipmentsRecords = $this->fetcthAll($sql);
        return $nextEquipmentsRecords;

    }


    function getSqlForGetNextConnectedEqipmentsToEquipment($nodeId, $eqNodeIds)
    {

        if ($nodeId != null) {
            $sql = "select
              nextEq.equipment_id as next_eq_id
              ,con.connection_id as con_id
              ,con.connection_type_id as con_type
            from
              slot sl
              ,card cd
              ,interface_connection_state ics
              ,connection con
              ,interface_connection_state ics2
              ,card cd2
              ,slot sl2
              ,equipment nextEq

            where
               sl.equipment_id=$nodeId
               AND cd.slot_id=sl.slot_id
               AND ics.card_physical_id=cd.card_id
               AND ((con.interface_object_id1=ics.interface_object_id AND ics2.interface_object_id=con.interface_object_id2)
               OR   (con.interface_object_id2=ics.interface_object_id AND ics2.interface_object_id=con.interface_object_id1))
               AND cd2.card_id=ics2.card_physical_id
               AND sl2.slot_id=cd2.slot_id
               AND nextEq.equipment_id=sl2.equipment_id

           ";

            return $sql;
        }


    }


    function getNextConnectedPatchPanelsFromEquipment($nodeId)
    {
        $sql = $this->getSqlForGetNextConnectedPatchPanelsFromEquipment($nodeId);
        $nextPatchPanels = $this->fetcthAll($sql);
        return $nextPatchPanels;

    }


    function getNextConnectedPatchPanelLandingOnAnEquipment($ppCardId, $ppOutInterfaceModelId)
    {
        $sql = $this->getSqlForGetNextConnectedPatchPanelLandingOnAnEquipment($ppCardId, $ppOutInterfaceModelId);
        if ($sql == null) {
            return null;
        }
        $nextPatchPanels = $this->fetcthAll($sql);
        if (count($nextPatchPanels) > 0) {
            return $nextPatchPanels[0];
        } else {
            return null;
        }
    }


    function getNextConnectedPatchPanelToPatchPanel($LeftPP_EqId, $lefPP_InInterfaceModel)
    {
        $sql = $this->getSqlForGetNextConnectedPatchPanelToPatchPanel($LeftPP_EqId, $lefPP_InInterfaceModel);
        $nextPatchPanels = $this->fetcthAll($sql);
        if (isset($nextPatchPanels[0])) {
            return $nextPatchPanels[0];
        } else {
            return null;
        }
    }

    function getSqlForGetNextConnectedPatchPanelsFromEquipment($nodeId)
    {


        if ($nodeId != null) {
            $sql = "select
              ppsl.equipment_id as next_eq_id
              ,nextPP.equipment_bypass_id as ppanel_id
              ,nextPP.card_model_id as ppanel_card_model_id
              ,ics2.card_physical_id as ppanel_card_id
              ,ics2.interface_model_id as ppanel_int1_model_id
              ,ics2.interface_object_id as ppanel_int1_id
              ,nextPP.interface2_model as ppanel_int2_model_id
              ,null as ppanel_int2_id
              ,con.connection_id as con_id
              ,'EQ_PP' as con_type

            from
              slot sl
              ,card cd
              ,interface_connection_state ics1
              ,connection con
              ,interface_connection_state ics2
              ,equipment_bypass nextPP
              ,card   ppcd
              ,slot ppsl
            where
              sl.equipment_id=$nodeId
              AND cd.slot_id=sl.slot_id
              AND ics1.card_physical_id=cd.card_id
              AND ((con.interface_object_id1=ics1.interface_connection_state_id AND ics2.interface_object_id=con.interface_object_id2)
              OR   (con.interface_object_id2=ics1.interface_connection_state_id AND ics2.interface_object_id=con.interface_object_id1))
              AND (nextPP.interface1_model=ics2.interface_model_id OR nextPP.interface2_model=ics2.interface_model_id)
              AND ppcd.card_id=ics2.card_physical_id
              AND ppsl.slot_id=ppcd.slot_id
           ";

            return $sql;
        } else {
            return null;
        }
    }


    /*
     *
     */
    function getSqlForGetNextConnectedPatchPanelLandingOnAnEquipment($ppCardId, $ppOutInterfaceModelId)
    {


        if ($ppCardId != null) {

            $sql = "select
              nextEq.equipment_id   as next_eq_id
              ,null as ppanel_id
              ,$ppCardId as ppanel_card_model_id
              ,null as ppanel_card_id
              ,null as ppanel_int1_model_id
              ,null as ppanel_int1_id
              ,$ppOutInterfaceModelId as ppanel_int2_model_id
              ,ics2.interface_object_id as ppanel_int2_id
              ,con.connection_id as con_id
              ,'PP_EQ' as con_type

            from
              interface_connection_state ics2
              ,connection con
              ,interface_connection_state ics
              ,card cd
              ,slot sl
              ,equipment nextEq
            where
               ics2.card_physical_id=$ppCardId
               AND ics2.interface_model_id=$ppOutInterfaceModelId
               AND ((con.interface_object_id1=ics2.interface_object_id AND ics.interface_object_id=con.interface_object_id2)
               OR   (con.interface_object_id2=ics2.interface_object_id AND ics.interface_object_id=con.interface_object_id1))
               AND cd.card_id=ics.card_physical_id
               AND sl.slot_id=cd.slot_id
               AND nextEq.equipment_id=sl.equipment_id
           ";

            return $sql;
        }
    }


    function getSqlForGetNextConnectedPatchPanelToPatchPanel($LeftPP_EqId, $lefPP_InInterfaceModel)
    {

        if ($LeftPP_EqId != null) {

            $sql = "select
              pp2sl.equipment_id   as next_eq_id
              ,nextPP.equipment_bypass_id as ppanel_id
              ,nextPP.card_model_id as ppanel_card_model_id
              ,ics2.card_physical_id as ppanel_card_id
              ,ics2.interface_model_id as ppanel_int1_model_id
              ,ics2.interface_object_id as ppanel_int1_id
              ,nextPP.interface2_model as ppanel_int2_model_id
              ,null as ppanel_int2_id
              ,con.connection_id as con_id
              ,'PP_PP' as con_type
            from
              equipment_bypass leftBPS
              ,slot ppsl
              ,card ppcd
              ,interface_connection_state ics
              ,connection con
              ,interface_connection_state ics2
              ,equipment_bypass  nextPP
              ,card   pp2cd
              ,slot pp2sl
            where
              leftBPS.interface1_model=$lefPP_InInterfaceModel
              AND ppsl.equipment_id=$LeftPP_EqId
              AND ppcd.slot_id=ppsl.slot_id
              AND ics.card_physical_id=ppcd.card_id
              AND ics.interface_model_id=leftBPS.interface2_model
              AND (  (con.interface_object_id1=ics.interface_object_id AND ics2.interface_object_id=con.interface_object_id2)
              OR (con.interface_object_id2=ics.interface_object_id AND ics2.interface_object_id=con.interface_object_id1))
              AND nextPP.interface1_model=ics2.interface_model_id
              AND pp2cd.card_id=ics2.card_physical_id
              AND pp2sl.slot_id=pp2cd.slot_id
           ";

            return $sql;
        }
    }


    private function fetcthAll($sql)
    {
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();

        $results = $stmt->fetchAll();

        return $results;
    }

    /*
     * Note : we only have used $logConfId.Others are passed here for possible future code revisions
     */
    public function removeSegmentsHavingLogicalConfiguration($logConfId, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        if (($designStatus == null) OR ($designStatus == "REPORT_DECOM_DIRECTLY")) {

            //Depreciated: before deletion,move to history
            /*$sql = "insert into logi_service_segment_history  * from  logi_service_segment where segment_logi_conf1_id=$logConfId OR segment_logi_conf2_id=$logConfId";
            $stmt = $this->_em->getConnection()->prepare($sql);
            $stmt->execute();*/

            //do'nt move to history if we are in rush
            $sql = "delete from logi_service_segment where segment_logi_conf1_id=$logConfId OR segment_logi_conf2_id=$logConfId";
            $stmt = $this->_em->getConnection()->prepare($sql);
            $stmt->execute();
        }


    }


    public function findDistinctServiceIdsPassingThroughLogConfId($logConfId)
    {
        $sql = "select distinct segment_service_id  from logi_service_segment where segment_logi_conf1_id=$logConfId or  segment_logi_conf2_id=$logConfId";
        $servicIds = $this->fetcthAll($sql);
        return $servicIds;
    }

    public function getAllSegmentsPassingThroughLogConfId($logConfId)
    {
        $sql = "select * from logi_service_segment where segment_logi_conf1_id=$logConfId or  segment_logi_conf2_id=$logConfId";
        $segments = $this->fetcthAll($sql);
        return $segments;
    }

    public function deleteAllSegmentsPassingThroughLogConfId($logConfId)
    {
        $sql = "delete from logi_service_segment where segment_logi_conf1_id=$logConfId or  segment_logi_conf2_id=$logConfId";
        $this->execute($sql);
    }

}
