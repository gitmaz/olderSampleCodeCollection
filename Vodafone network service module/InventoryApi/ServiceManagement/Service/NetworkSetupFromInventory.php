<?php
/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 15/02/2016
 * Time: 1:56 PM
 *
 *  This is a Facade that does most interaction between Network Service discover modules and Inventory api through
 *  controlled repositories dedicated for service management(in Repository folder).
 */
namespace InventoryApi\ServiceManagement\Service;

use InventoryApi\DependencyInjection\Service\ContainerProvider;
use InventoryApi\LogicalConfiguration\Entity\LogicalConfiguration;
use InventoryApi\ServiceManagement\Repository;

//todo: IMPORTANT : consult tomas on how to access ccontainer without dependency on Orion namespace

class NetworkSetupFromInventory
{
    public $serviceLookupTableToUse = "LOG_CONF_INDEXED_VAL";//"LOGI_SERVICE_LOOKUP";
    public $em;
    public $serviceDescriptor;
    public $networkGrouper;
    public $discoveredNetworkNodes;
    public $container;

    function __construct($em)
    {
        //consult ORM to get service segments
        $this->em = $em;// OrmConfiguration::getEntityManager();
        $this->container = ContainerProvider::getContainer();
    }

    /*
     *
     *  preparing for more advanced service search not dependent on one service type or service id but more general $serviceParams
     *
     * @param array $serviceParams ["ServiceType"=>x,"ServiceId"=>x1 [,other service search parameters]]
     *  Note x and x1 can be null for more general searches spanning many services (not just one)
     *
     * @return [int] $confIds   ids of discovered configs
     */
    public function discoverDeviceConfigs($serviceParams)
    {
        //$method="LOGI_SERVICE_LOOKUP";

        switch ($this->serviceLookupTableToUse) {
            case "LOGI_SERVICE_LOOKUP":
                $confIds = $this->lookupConfigsFromLogiServiceLookupTable($serviceParams);
                break;
            case "LOG_CONF_INDEXED_VAL":
                $confIds = $this->lookupConfigsFromLogConfIndexedValTable($serviceParams);
                break;

        }
        return $confIds;

    }


    public function queryServices($serviceQueryStr)
    {

        $serviceQueryStr = trim($serviceQueryStr, ")");
        $serviceQueryStr = str_replace(['(', ')', ' ', '<strong>', '</strong>'], '', $serviceQueryStr);
        $rawServiceParams = explode("AND", $serviceQueryStr);
        $serviceParams = $this->getKeyValuePairs($rawServiceParams);


        return $this->resolveServiceSegmentsAvalanch($serviceParams);
    }

    private function getKeyValuePairs($rawServiceParams)
    {
        $serviceParams = [];
        foreach ($rawServiceParams as $rawServiceParam) {
            $eqSplits = explode("=", $rawServiceParam);
            if (count($eqSplits) > 1) {
                $serviceParams[$eqSplits[0]] = ["=" => $eqSplits[1]];
            }
            $ltSplits = explode("<", $rawServiceParam);
            if (count($ltSplits) > 1) {
                $serviceParams[$ltSplits[0]] = ["<" => $ltSplits[1]];
            }
            $neqSplits = explode("<>", $rawServiceParam);
            if (count($neqSplits) > 1) {
                $serviceParams[$neqSplits[0]] = ["<>" => $neqSplits[1]];
            }
            $gtSplits = explode(">", $rawServiceParam);
            if (count($gtSplits) > 1) {
                $serviceParams[$gtSplits[0]] = [">" => $gtSplits[1]];
            }
        }

        return $serviceParams;
    }

    /*
     *  this will find adjacent segments running from focus device
     * @param integer  logical configuration id of the device on focus
     *
     */
    public function findSegmentsRunningFromDeviceWithConfigId($logConfId)
    {

        //1) find other connected devices to this device

        //2) check confs of this device having same config type of this one (only search for config types of service)

        //3) consider newly discovered segments as new segments per service and add it to the same service

    }

    /* find service segment and subservice segments having the criteria passed in $serviceParams
     * @param [["parma_key"=>x,"param_value"=>xVal ],...]  $serviceParams
     * @return ServiceSegmentsInfo $serviceSegmentsInfo  where ->segment contains a service segment in key value assoc array format
     */
    public function resolveServiceSegmentsAvalanch($serviceParams)
    {

        $deviceConfigIds = $this->discoverDeviceConfigs($serviceParams);
        if ($deviceConfigIds == null) {
            return null;
        }

        if (count($deviceConfigIds) != 0) {
            //now find main segments

            //Note: this will find main service segments(nodes directly connected to eachother through physical connections /leased lines
            // and/or through passive nodes [patch panels or leased line nodes]-which in this case it flags their handover points to be connected through a subservice containing those nodes)

            $serviceSegmentsInfo = $this->resolveMainServiceSegments($deviceConfigIds, "main");
            $this->resolveSubserviceSegmentsAsMainServicePlaceholdersDiscoveredFromNetworkNodes($serviceSegmentsInfo);
            $serviceSegments = $serviceSegmentsInfo->getSegments();

            $mainServiceTypeId = $serviceSegmentsInfo->serviceTypeId;
            $mainServiceName = $serviceSegmentsInfo->serviceTypeName;
            $mainServiceDescId = $serviceSegmentsInfo->serviceDescId;
            $mainServiceId = $serviceSegmentsInfo->getServiceDescriptorHash();
            $serviceRole = "main";

            //and lastly find and add subservice segments

            //resolve subservices which we have explicitly defined via hand_over_config key in their logical configuration
            $explicitSubserviceConfigs = $this->discoverExplicitHandoverConfigs($deviceConfigIds, $mainServiceTypeId, $serviceSegmentsInfo);
            $explicitSubserviceSegments = $this->resolveExplicitSubServiceSegmentsHavingConfigs($explicitSubserviceConfigs, $mainServiceId);


            //also put placeholders for subservice segments on their dedicated configs when they are assumed as main services
            $this->resolveSubserviceSegmentsAsMainServicePlaceholders($explicitSubserviceConfigs, $serviceSegmentsInfo);

            //put main and sub services in one place
            $allSegments = array_merge($explicitSubserviceSegments, $serviceSegments);//help grapher to place nodes neater by pushinf subservices first

            //for cases with incomplete segments,try finding unconnected segments instead (segment with only one vertex filled)
            $nonConnectedServiceSegments = $this->resolveSegmentsForStandAloneMainServiceNodes($deviceConfigIds, $serviceRole, $mainServiceName, $mainServiceDescId);
            //merged with non orphan segments
            $this->removeRedundancy($nonConnectedServiceSegments, $allSegments);

            $allSegments = array_merge($allSegments, $nonConnectedServiceSegments);

        } else {
            $serviceSegmentsInfo = new ServiceSegmentsInfo();
            //$serviceSegmentsInfo=$this->container->get("inv.service_management.service_segments_info");
            $allSegments = [];
        }

        $serviceSegmentsInfo->setSegments($allSegments);
        return $serviceSegmentsInfo;
    }

    /* resolves subservices dedicated segments as placeholders of their role as main service
     * @param [[key=>value],...] $subserviceConfigs   structure containing configs dedicated to subservice
     * @param ServiceSegmentsInfo $serviceSegmentsInfo
     * @parm LogiServiceSegmentRepository $serviceSegmentRepo
     */
    function resolveSubserviceSegmentsAsMainServicePlaceholders($subserviceConfigs, $serviceSegmentsInfo)
    {

        $subserviceIndex = 0;
        foreach ($subserviceConfigs as $subserviceConfig) {

            $subserviceConfigsAsMainService = $subserviceConfig["hand_over_conf_ids"];
            $subServiceSegmentsInfo = $this->resolveMainServiceSegments($subserviceConfigsAsMainService, "main_place_holder", $subserviceIndex);

            $serviceSegmentsInfo->addSubserviceSegmentsInfo($subServiceSegmentsInfo);

            $subserviceIndex++;
        }

    }

    /* resolves subservices dedicated segments as placeholders of their role as main service
     * This is a variation of resolveSubserviceSegmentsAsMainServicePlaceholders but the former works for patch paneled connections as subservices
     * but the latter works for explicitly configured subservices through logical configuratiions and their hand_over_config attributes
     * @param [[key=>value],...] $subserviceConfigs   structure containing configs dedicated to subservice
     * @param ServiceSegmentsInfo $serviceSegmentsInfo
     * @parm LogiServiceSegmentRepository $serviceSegmentRepo
     */
    function resolveSubserviceSegmentsAsMainServicePlaceholdersDiscoveredFromNetworkNodes($serviceSegmentsInfo)
    {
        $networkNodes = $serviceSegmentsInfo->networkNodes;
        /* @var $networkNode NetworkNode */
        foreach ($networkNodes as $networkNode) {

            $subServiceSegmentsInfo = new ServiceSegmentsInfo();
            //$subServiceSegmentsInfo=$this->container->get("inv.service_management.service_segments_info");
            if ($networkNode->leftConnectionName == null) {
                continue;
            }
            $subServiceSegmentsInfo->setDescriptor($networkNode->leftConnectionName);
            $subServiceSegmentsForThisNetworkNode = $this->getSubServiceSegmentsBrief($networkNode);
            if ($subServiceSegmentsForThisNetworkNode != null) {
                $subServiceSegmentsInfo->setSegments($subServiceSegmentsForThisNetworkNode);

                $serviceSegmentsInfo->addSubserviceSegmentsInfo($subServiceSegmentsInfo);
            }
        }
    }

    function getSubServiceSegmentsBrief($networkNode)
    {
        if ($networkNode != null) {
            $serviceSegments = [];

            $childNodes = $networkNode->childNodes;
            if (count($childNodes) == 0) {
                return null;
            }
            foreach ($childNodes as $childNode) {
                $serviceSegmentForThisNetworkNode = $this->getServiceSegmentBrief($childNode);
                $serviceSegments[] = $serviceSegmentForThisNetworkNode;
            }
            return $serviceSegments;
        }

        return null;
    }

    function getServiceSegmentBrief($networkNode)
    {
        $serviceSegment = [];
        /* @var $networkNode NetworkNode */
        $serviceSegment["LOGI_CONF1_ID"] = null;
        $serviceSegment["LOGI_CONF2_ID"] = null;
        // if($networkNode->leftConnectionType!="ppService") {
        $serviceSegment["CONNECTION_ID"] = $networkNode->leftConnectionId;
        /*}
        else {
            $serviceSegment["SEGMENT_LOG_CON_ID"] = $networkNode->leftConnectionId;
        }*/

        return $serviceSegment;

    }

    /*
     * @param [integer] $deviceConfigIds
     * @param string $serviceRole  either "main" or "main_place_holder"
     * @param integer $subserviceIndex in case of subservices ,it highlights which segment from parent,this returning ServiceSegmentsInfo points to
     * @return ServiceSegmentsInfo $serviceSegmentsInfo which will contain these segments
     */
    function resolveMainServiceSegments($deviceConfigIds, $serviceRole, $subserviceIndex = null)
    {

        $networkNodes = null;

        //fetch first node's config descriptor as a means to create service id for all nodes of same service type and descriptor
        $firstDeviceConfigId = $deviceConfigIds[0];

        //Resolve service Id from service descriptor
        /* @var $serviceSegmentsInfo ServiceSegmentsInfo */
        $serviceSegmentsInfo = $this->resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($firstDeviceConfigId);

        $mainServiceTypeId = $serviceSegmentsInfo->serviceTypeId;
        $mainServiceName = $serviceSegmentsInfo->serviceTypeName;
        $mainServiceDescId = $serviceSegmentsInfo->serviceDescId;
        $mainServiceId = $serviceSegmentsInfo->getServiceDescriptorHash();

        if ($serviceRole == "main") {
            //Find main segments (thorough loading)


            $networkNodes = $this->resolveSegmentsConsideringPatchPanelsWithCascadeSelects($deviceConfigIds);
            $serviceSegments = $this->resolveToFullServiceSegments($networkNodes);


            //Depreciate single sql main segment resoloution in favor of above cascaded selects search method to accomodate for patch pannel connectivity
            /*
             $serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
             $serviceSegments = $serviceSegmentRepo->resolveServiceHavingConfigs($deviceConfigIds, $serviceRole, $mainServiceName, $mainServiceId, null, $mainServiceTypeId);
            */
        } else {
            //Find main segments as placeholders (lazy loading)
            //Note: placeholders does not show in middle nodes,they only show first and last nodes and therefore in disconnected way to be resolved or defined later

            $serviceSegments = $this->resolveSegmentsForStandAloneMainServiceNodes($deviceConfigIds, $serviceRole, $mainServiceName, $mainServiceDescId);

        }

        $serviceSegmentsInfo->setSegments($serviceSegments);

        $serviceSegmentsInfo->setParentSegmentIndex($subserviceIndex);
        $serviceSegmentsInfo->setNetworkNodes($networkNodes);
        return $serviceSegmentsInfo;
    }


    /*
     * @param $networkNodes service segments with minimal info ie only eq ids and connection ids plus connection types
     * @retrun $serviceSegmentsInFull same info but with full detailed resolved from related tables from db
     */
    public function resolveToFullServiceSegments($networkNodes)
    {
        $serviceSegmentsInFull = [];
        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        foreach ($networkNodes as $networkNode) {

            $serviceSegmentInFull = $this->resolveServiceSegmentFullInfo($networkNode, $serviceSegmentRepo);
            if ($serviceSegmentInFull != null) {
                $serviceSegmentsInFull = array_merge($serviceSegmentsInFull, $serviceSegmentInFull);
            }
        }

        return $serviceSegmentsInFull;
    }

    public function resolveServiceSegmentFullInfo($networkNode, $serviceSegmentRepo)
    {

        /* @var $serviceSegmentRepo LogiServiceSegmentRepository */
        $serviceSegmentInFull = $serviceSegmentRepo->resolveServiceSegmentFullInfoFromNetworkNode($networkNode);

        if ($serviceSegmentInFull == null) {
            return null;
        }
        $EquipmentInfo = new EquipmentInfo();//$this->container->get("inv.service_management.equipment_info");

        $serviceSegmentInFull[0]["LOGI_CONF1_ID"] = $EquipmentInfo->getLogConfOfEquip($serviceSegmentInFull[0]["EQUIPMENT_ID1"]);
        $serviceSegmentInFull[0]["LOGI_CONF2_ID"] = $EquipmentInfo->getLogConfOfEquip($serviceSegmentInFull[0]["EQUIPMENT_ID2"]);

        //also replace segments ids and names passing through MW ,and LL with their resolved names
        $this->resolveMW_OrLL_HumanReadableNamesForSegments($serviceSegmentRepo, $serviceSegmentInFull);

        return $serviceSegmentInFull;
    }


    /*
     * @param [integer] $deviceConfigIds
     * @return ServiceSegmentsInfo $serviceSegmentsInfo which will contain these segments
     */
    function resolveExplicitSubServiceSegmentsHavingConfigs($subserviceConfigs, $mainServiceId)
    {

        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $subserviceSegments = $serviceSegmentRepo->resolveExplicitSubserviceSegmentsHavingConfigs($subserviceConfigs, $mainServiceId);

        $this->suggestNameForSubserviceSegmentsName($subserviceConfigs, $subserviceSegments, $serviceSegmentRepo);
        return $subserviceSegments;
    }

    /*
     * creates duplicates with reverse order interfaces in EQuipment_bypass table for ease of search
     */
    function helperPrepareEquipmentBypassRelationForLeasedLineSubserviceDiscovery()
    {


        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $serviceSegmentRepo->helperAddReverseInterfacesInEquipmentBypassRelation();

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


        /* @var $logiServiceTypeRepo LogiServiceTypeRepository */

        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $handOverConfigsInfo = $serviceSegmentRepo->discoverImplicitHandoverConfigsAndResolveImplicitSubServiceSegments($deviceConfigIds, $mainServiceTypeId, $serviceSegmentsInfo, $mainServiceId);

        //$logiServiceTypeRepo=new LogiServiceTypeRepository($this->em);
        $logiServiceTypeRepo = $this->container->get("inv.service_management.logi_service_type_repository");
        $this->resolveHandoverServiceIdsFromTheirServiceType($handOverConfigsInfo, $serviceSegmentsInfo);

        return $handOverConfigsInfo;
    }


    function suggestNameForSubserviceSegmentsName($subserviceConfigs, &$subserviceSegments, $serviceSegmentRepo)
    {
        foreach ($subserviceSegments as $key => $subserviceSegment) {
            /*$logConId=$subserviceSegment["SEGMENT_LOG_CON_ID"];*/
            /* @var $serviceSegmentRepo LogiServiceSegmentRepository */
            //$logConName=$serviceSegmentRepo->resolveLogicalConnectionName($logConId);

            $logConName = $subserviceConfigs[$key]["descriptor"];
            $subserviceSegments[$key]["SEGMENT_LOG_CON_NAME"] = $logConName;
        }
    }


    function resolveSegmentsForStandAloneMainServiceNodes($deviceConfigIds, $serviceRole, $mainServiceName, $mainServiceDescId)
    {
        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $segments = $serviceSegmentRepo->resolveSegmentsForStandAloneMainServiceNodes($deviceConfigIds, $serviceRole, $mainServiceName, $mainServiceDescId);

        return $segments;
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

                if (!isset($segment["LOGI_CONF1_ID"])) {
                    if (
                        ($segment["EQUIPMENT_ID1"] == $nonConnectedServiceSegment["EQUIPMENT_ID1"]) ||
                        ($segment["EQUIPMENT_ID2"] == $nonConnectedServiceSegment["EQUIPMENT_ID1"]) ||
                        ($segment["EQUIPMENT_ID1"] == $nonConnectedServiceSegment["EQUIPMENT_ID2"]) ||
                        ($segment["EQUIPMENT_ID2"] == $nonConnectedServiceSegment["EQUIPMENT_ID2"])
                    ) {
                        $existingIndexes[$index] = $index;
                    }

                } else {
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
        }

        foreach ($existingIndexes as $existingIndex) {
            unset($nonConnectedServiceSegments[$existingIndex]);
        }
    }


    /*
     * @param integer $serviceConfigurationId :source of information config
     * @param ServiceSegmentsInfo $serviceSegmentsInfo : struct encapsulating output service type ,descriptor and segments (we fill first two here_
     * @return  string  $serviceDiscoveryDescriptor :criteria of discovey for other configs of same service in string format
     */
    private function fillServiceDiscoveryDescriptorAndServiceTypeForConfig($serviceConfigurationId, $serviceSegmentsInfo, $useHistoryTable = false)
    {

        if ($serviceSegmentsInfo == null) {
            return null;
        }

        //form $serviceDescriptor from information found in serviceType table
        /* @var $logiServiceType LogiServiceType */
        $logiServiceType = $this->getServiceTypeForLogicalConfiguration($serviceConfigurationId, $useHistoryTable);
        if ($logiServiceType == null) {
            //throw(new \Exception("logical configuration type for this logConfId is not of a type : service!"));
            return null;
        }

        $serviceSegmentsInfo->setServiceTypeId($logiServiceType->getServiceTypeId());

        $logiServiceTypeDescriptor = $logiServiceType->getServiceTypeDiscoveryKeys();
        $serviceTypeDescriptor = $logiServiceTypeDescriptor;
        $this->serviceDescriptor = $serviceTypeDescriptor;

        /* @var ServiceSegmentsInfo $serviceSegmentsInfo */
        $serviceSegmentsInfo->setTypeDescriptor($serviceTypeDescriptor);
        $serviceSegmentsInfo->setUnfilledServiceDiscoveryParamsFromDescriptor($serviceTypeDescriptor);


        return $serviceTypeDescriptor;

    }

    /*
     * @param ServiceSegmentsInfo $serviceSegmentsInfo
     * @param [[key=>val],...] $filledServiceDiscoveryParams
     */
    function fillDescriptorFromFilledParams($serviceSegmentsInfo, $filledServiceDiscoveryParams)
    {
        $seviceDescriptor = $this->translateParamsToDescriptor($filledServiceDiscoveryParams);
        $serviceSegmentsInfo->setDescriptor($seviceDescriptor);
    }

    /*
     * @var [[key=>val],...] $filledServiceParams
     *
     * @return string  filled string as descriptor
     */
    private function translateParamsToDescriptor($filledServiceParams)
    {

    }

    /*
    * @param integer $logConfId :logical configuration from which we are looking up the below keys
    * @param [[key1=>%1],[key2=>%2],...]  $serviceSegmentInfo->unfilledDicoveryParams :unfilled key value pairs as a source of lookup keys
    * @param ServiceSegmentInfo $serviceSegmentInfo
    * @return [[key=>value],...]  same $unfilledDicoveryParams array but with their values looked up and filled from Logical configuration data
    */
    public function fillServiceDiscoveryParamsFromConfigId($logConfId, $serviceSegmentInfo)
    {

        $unfilledDicoveryParams = $serviceSegmentInfo->unfilledServiceDiscoveryParams;
        //$serviceLookupRepo = new LogiServiceLookupRepository($this->em);
        $serviceLookupRepo = $this->container->get("inv.service_management.logi_service_lookup_repository");
        $filledServiceDiscoveryParams = $serviceLookupRepo->fillServiceDiscoveryParamsFromConfigId($logConfId, $unfilledDicoveryParams);
        /* @var ServiceSegmentsInfo $serviceSegmentInfo */
        $serviceSegmentInfo->setFilledServiceDiscoveryParams($filledServiceDiscoveryParams);
        return $filledServiceDiscoveryParams;

    }


    /*
    * same as above ,but using handover descriptor of the service type as a hint to resolution
    * @param integer $logConfId :logical configuration from which we are looking up the below keys
    * @param [[key1=>%1],[key2=>%2],...]  $serviceSegmentInfo->unfilledSubserviceDicoveryParams :unfilled key value pairs as a source of lookup keys
    * @param ServiceSegmentInfo $serviceSegmentInfo
    * @return [[key=>value],...]  same $unfilledSubserrviceDicoveryParams array but with their values looked up and filled from Logical configuration data
    */
    private function fillSubServiceDiscoveryParamsFromConfigId($logConfId, $serviceSegmentInfo)
    {

        $unfilledDicoveryParams = $serviceSegmentInfo->unfilledServiceDiscoveryParams;
        //$serviceLookupRepo = new LogiServiceLookupRepository($this->em);
        $serviceLookupRepo = $this->container->get("inv.service_management.logi_service_lookup_repository");
        $filledSubServiceDiscoveryParams = $serviceLookupRepo->fillServiceDiscoveryParamsFromConfigId($logConfId, $unfilledDicoveryParams);
        /* @var ServiceSegmentsInfo $serviceSegmentInfo */
        $serviceSegmentInfo->setFilledSubServiceDiscoveryParams($filledSubServiceDiscoveryParams);
        return $filledSubServiceDiscoveryParams;

    }

    /*
     * This will find service segments centered to $centerLogConfId and radiating from it having same service type equal to configuration entity id and also
     *  having specific keys defined in service type having values equal to values of the same keys in $centerLogConf
     *
      *  @param integer $centerLogConfId  :configId on which the search is centered to
     */
    public function resolveNeighborhoodServiceSegments($centerLogConfId)
    {

        $serviceSegmentsInfo = new ServiceSegmentsInfo();
        //$serviceSegmentsInfo=$this->container->get("inv.service_management.service_segments_info");

        $filledServiceDiscoveryParams = $this->getFilledServiceDiscoveryParamsForConfig($centerLogConfId, $serviceSegmentsInfo);
        if ($filledServiceDiscoveryParams == null) {
            return null;
        }

        //check if discovery params properly filled
        if (!$this->isDiscoveryKeysFine($filledServiceDiscoveryParams, $serviceSegmentsInfo)) {

            return $serviceSegmentsInfo;

        }

        //1) form service descriptor from config service type
        //build service discovery descriptor
        //$serviceLookupRepo = new LogiServiceLookupRepository($this->em);
        $serviceLookupRepo = $this->container->get("inv.service_management.logi_service_lookup_repository");
        $lookupJoinTablesPartialSqlFromServiceParams = "";
        $lookupWhereClausePartialSqlFromServiceParams = "";
        $serviceLookupRepo->getServiceDiscoveryDescriptorPartialSqlForConfig($centerLogConfId, $serviceSegmentsInfo->filledServiceDiscoveryParams, $lookupJoinTablesPartialSqlFromServiceParams, $lookupWhereClausePartialSqlFromServiceParams);


        //2) find segments
        //find service segments having descriptor
        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $explicitServiceSegments = $serviceSegmentRepo->findRadiatingMainSegmentsFromConfigIdWithExplicitDownstreams($centerLogConfId, $lookupJoinTablesPartialSqlFromServiceParams, $lookupWhereClausePartialSqlFromServiceParams);


        //find segment found through patch paneling
        $implicitServiceSegments = $this->findRadiatingMainSegmentsFromConfigIdWithImplicitDownstreams($centerLogConfId, $filledServiceDiscoveryParams, $serviceLookupRepo, $serviceSegmentRepo);
        $serviceSegments = array_merge($implicitServiceSegments, $explicitServiceSegments);

        //find subservice segments
        //and lastly find and add subservice segments

        //if this service acts as a subservice of another one,use its id as segment_log_con
        $serviceHashAsSubserviceId = $serviceSegmentsInfo->getServiceId();
        $subserviceSegmentsOnThisUpstreamService = $serviceSegmentRepo->findRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForThisUpstream($centerLogConfId, $lookupJoinTablesPartialSqlFromServiceParams, $lookupWhereClausePartialSqlFromServiceParams, $serviceHashAsSubserviceId);

        if (count($subserviceSegmentsOnThisUpstreamService) > 0) {

            $this->setNameAndIdForSubservicePlaceholderSegments($subserviceSegmentsOnThisUpstreamService, $isMainAnUpstream = true);
            $serviceSegments = array_merge($subserviceSegmentsOnThisUpstreamService, $serviceSegments);
        }
        //since we are incremental discovery,if subservice formation is done after service formation,trigger creation of placeholder in upstream if we are hand overing to upper service
        $subserviceSegmentsOnUpstreamOfThisService = $serviceSegmentRepo->findRadiatingSubserviceSegmentsFromConfigIdWithExplicitDownstreamsForUpstreamOfThisService($centerLogConfId, $lookupJoinTablesPartialSqlFromServiceParams, $lookupWhereClausePartialSqlFromServiceParams, $serviceHashAsSubserviceId);


        if (count($subserviceSegmentsOnUpstreamOfThisService) > 0) {

            $this->setNameAndIdForSubservicePlaceholderSegments($subserviceSegmentsOnUpstreamOfThisService, $isMainAnUpstream = false);
            $serviceSegments = array_merge($serviceSegments, $subserviceSegmentsOnUpstreamOfThisService);
        }

        if (count($serviceSegments) == 0) {
            //if no complete segment is found,add us as a partial segment(segment having null endpoint)
            $serviceSegments = [["LOGI_CONF1_ID" => $centerLogConfId, "LOGI_CONF2_ID" => null, "PHYS_CON_ID" => null, "LOG_CON_ID" => null, "CONNECTION_ID" => null]];
        }
        $serviceSegmentsInfo->setSegments($serviceSegments);

        return $serviceSegmentsInfo;
    }

    function findRadiatingMainSegmentsFromConfigIdWithImplicitDownstreams($centerLogConfId, $serviceParams, $serviceLookupRepo, $serviceSegmentRepo)
    {
        /* @var $serviceLookupRepository LogiServiceLookupRepository */
        $configIds = $serviceLookupRepo->resolveConfigsFromLogConfIndexedValTable($serviceParams);
        if (count($configIds) == 0) {
            return [];
        }
        sort($configIds);
        $eqIds = $serviceSegmentRepo->getEquipmentIds($configIds);

        $EquipmentInfo = new EquipmentInfo();//$this->container->get("inv.service_management.equipment_info");
        $EquipmentInfo->setLogConfOfEquips($eqIds, $configIds);

        //remove us from configIds to connect to
        $indexOfUs = array_search($centerLogConfId, $configIds);
        $centerEqId = $eqIds[$indexOfUs];
        unset($configIds[$indexOfUs]);
        unset($eqIds[$indexOfUs]);

        //now acceptable discover nodes should be a member of $configIds

        $eqIdsToCheckConnectivityWith = $eqIds;
        //check if patch paneled connection exists as another means of connection
        $centerEqNode = new NetworkNode($centerEqId, "Equip", null, null);
        $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels = $this->getNextPatchPaneledConnectedEquipmentsToEqipment($centerEqNode, $centerEqNode, $eqIdsToCheckConnectivityWith, $serviceSegmentRepo);

        if ($nextConnectedEquipmentNodesDiscoveredThroughPatchPanels != null) {
            $implicitServiceSegments = $this->resolveToFullServiceSegments($nextConnectedEquipmentNodesDiscoveredThroughPatchPanels);
        } else {
            $implicitServiceSegments = [];
        }

        return $implicitServiceSegments;
    }

    /**
     * @param [[key=>val],...] $subserviceSegments
     */
    function setNameAndIdForSubservicePlaceholderSegments(&$subserviceSegments, $isMainAnUpstream = true)
    {
        if ($isMainAnUpstream) {

            foreach ($subserviceSegments as $key => $subserviceSegment) {

                $firstLogConfId = $subserviceSegment["LOGI_CONF1_ID_S"];
                $subServiceSegmentsInfo = $this->resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($firstLogConfId);
                $name = $subServiceSegmentsInfo->getServiceDescriptor();
                $id = $subServiceSegmentsInfo->getServiceDescriptorHash();

                //$subserviceSegment["SERVICE_ID"]=$id;
                //$subserviceSegment["SERVICE_NAME"]=$name;
                $subserviceSegment["SEGMENT_LOG_CON_NAME"] = $name;
                $subserviceSegment["SEGMENT_LOG_CON_ID"] = $id;
                $subserviceSegments[$key] = $subserviceSegment;
            }
        } else {
            $firstLogConfId = $subserviceSegments[0]["LOGI_CONF1_ID_M"];
            $subServiceSegmentsInfo = $this->resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($firstLogConfId);
            $name = $subServiceSegmentsInfo->getServiceDescriptor();
            $id = $subServiceSegmentsInfo->getServiceDescriptorHash();
            $upstreamFirstLogCofId = $subserviceSegments[0]["LOGI_CONF1_ID"];
            $upstreamServiceSegmentsInfo = $this->resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($upstreamFirstLogCofId);
            $upstreamName = $upstreamServiceSegmentsInfo->getServiceDescriptor();
            $upstreamId = $upstreamServiceSegmentsInfo->getServiceDescriptorHash();
            foreach ($subserviceSegments as $key => $subserviceSegment) {

                $subserviceSegment["SERVICE_ID"] = $upstreamId;
                //$subserviceSegment["SERVICE_NAME"]=$name;
                $subserviceSegment["SEGMENT_LOG_CON_NAME"] = $name;
                $subserviceSegment["SEGMENT_LOG_CON_ID"] = $id;
                $subserviceSegments[$key] = $subserviceSegment;
            }
        }


    }

    function resolveMW_OrLL_HumanReadableNamesForSegments($serviceSegmentRepo, &$serviceSegments)
    {
        $serviceSegmentRepo->resolveMW_OrLL_HumanReadableNamesForSegments($serviceSegments);

        $serviceSegmentsInfo = new ServiceSegmentsInfo();
        foreach ($serviceSegments as $key => $serviceSegment) {
            //for segment with odd number of ids in their segment_via recreate the id to match newly resolve name
            if (isset($serviceSegment["SEGMENT_LOG_CON_NAME"])) {

                if (isset($serviceSegment["SERVICE_NAME"])) {
                    if (strpos($serviceSegment["SERVICE_NAME"], "_to_") === false) {
                        //$serviceSegmentsInfo=$this->container->get("inv.service_management.service_segments_info");
                        $serviceSegmentsInfo->setDescriptor($serviceSegment["SERVICE_NAME"]);
                        $serviceSegment["SEGMENT_LOG_CON_ID"] = $serviceSegmentsInfo->getServiceId();
                        $serviceSegments[$key] = $serviceSegment;
                    }
                }
            }
        }
    }

    function resolveUpstramServiceForSubserviceSegments($serviceSegments)
    {
        $sampleConf = $serviceSegments[0]["LOGI_CONF1_ID"];
        $serviceSegmentsInfo = $this->resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($sampleConf);
        return $serviceSegmentsInfo;

    }


    function removeSegmentsRadiatingFrom($logConfIdOfCenterNode, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {
        //find all segments with log_conf1 or log_conf2 equal to given log conf and delete them
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $serviceSegmentRepo->removeSegmentsHavingLogicalConfiguration($logConfIdOfCenterNode, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);
    }


    /*
     *
     * @param integer $logCofId
     * @param ServiceSegmentsInfo $serviceSegmentsInfo //will get filled
     *
     * @param [[key=>val],...] $filledServiceDiscoveryParams
     */
    public function getFilledServiceDiscoveryParamsForConfig($logCofId, $serviceSegmentsInfo, $useHistoryTable = false)
    {
        //$serviceSegmentsInfo=new ServiceSegmentsInfo();

        try {

            $serviceTypeDiscoveryDescriptor = $this->fillServiceDiscoveryDescriptorAndServiceTypeForConfig($logCofId, $serviceSegmentsInfo, $useHistoryTable);
            if ($serviceTypeDiscoveryDescriptor == null) {
                return null;
            }
            $filledServiceDiscoveryParams = $this->fillServiceDiscoveryParamsFromConfigId($logCofId, $serviceSegmentsInfo);
        } catch (\Exception $ex) {
            echo "\n error in getFilledServiceDiscoveryParamsForConfig" . $ex->getMessage();
        }
        return $filledServiceDiscoveryParams;
    }

    /*
     * @param [[key=>value],...]
     * @param ServiceSegmentInfo $serviceSegmentInfo
     *
     * @return ServiceSegmentInfo  with status "not found" if no discovery keys are not fully filled
     */
    private function isDiscoveryKeysFine($filledServiceDiscoveryParams, $serviceSegmentsInfo)
    {
        $foundMismatch = false;
        foreach ($filledServiceDiscoveryParams as $paramKey => $paramValue) {
            //if service descriptor is not fully filled,it means we have not found any service
            if (strpos($paramValue, "%") !== false) {


                $serviceSegmentsInfo->mismatchKeys .= $paramKey . " , ";

                $foundMismatch = true;
            }
        }
        if ($foundMismatch) {
            $serviceSegmentsInfo->setStatus("not found");
            return false;
        }
        return true;
    }

    /*
     *@param integer $existingServiceId
     * @param [] $serviceSegments
     */
    function saveServiceSegmentsToExistingService($existingServiceId, $serviceSegments, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $serviceSegmentRepo->saveServiceSegments($existingServiceId, $serviceSegments);
    }

    /*
     * Allocates a new service in LOGI_SERVICE table and adds $serviceSegments as children of it to LOGI_SERVICE_SEGMENT
     *
     * @param ServiceSegmentsInfo $serviceSegmentsInfo  contains segments ,their service type and descriptor which formed them
     */
    function saveServiceSegmentsToNewService($serviceSegmentsInfo, $newServiceName, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        //$serviceRepo = new LogiServiceRepository($this->em);
        $serviceRepo = $this->container->get("inv.service_management.logi_service_repository");

        //create brand new service to parent these service segments

        //todo: doctroine entity method has an error.fix and depreciate sql only version
        $newServiceId = $serviceRepo->allocateNewService($serviceSegmentsInfo->serviceTypeId, $newServiceName, $serviceSegmentsInfo->descriptor);

        //use this serviceId to register service segments.
        //$serviceSegmentRepo = new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $serviceSegmentRepo->saveServiceSegments($newServiceId, $serviceSegmentsInfo->segments);

    }

    /*
     *
     * @return LogiServiceType service type
     */
    public function getServiceTypeForLogicalConfiguration($serviceConfigurationId, $useHistoryTable = false)
    {
        //$serviceTypeRepo = new LogiServiceTypeRepository($this->em);
        $serviceTypeRepo = $this->container->get("inv.service_management.logi_service_type_repository");
        $logiServiceType = $serviceTypeRepo->getServiceTypeFromLogicalConfiguration($serviceConfigurationId, $useHistoryTable);

        return $logiServiceType;


    }

    /*
     * @var [["parma_key"=>x,"param_value"=>xVal ],...]  $serviceParams
     * @return [segment] where segment contains a service segment in key value assoc array format
     */
    function discoverServiceSegments($serviceParams)
    {
        //$serviceSegmentRepo=new LogiServiceSegmentRepository($this->em);
        $serviceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $serviceSegments = $serviceSegmentRepo->resolveService($serviceParams["service_type"], $serviceParams["service_id"]);

        return $serviceSegments;
    }


    /*
     *  creates segments of type subservice which are also running on the same set of devices discovered in this service
     * @var $configIds configuration already discovered on this service
     * @$serviceEntityId integer service type
     * @return [["id"=>x ,"config_type"=>x_new_conf_type_Id ],...]
     */
    public function discoverExplicitHandoverConfigs($configIds, $serviceEntityId, $serviceSegmentsInfo)
    {

        //$logiServiceLookupRepository=new LogiServiceLookupRepository($this->em);
        $logiServiceLookupRepository = $this->container->get("inv.service_management.logi_service_lookup_repository");
        $handOverConfigsInfo = $logiServiceLookupRepository->filterConfIdsOfServiceForExplicitHandOverNodes($configIds, $serviceEntityId);

        //Prepare subservive configs by resolving its handover descriptor per config and then find service ids from it
        /* @var $logiServiceTypeRepo LogiServiceTypeRepository */
        //$logiServiceTypeRepo=new LogiServiceTypeRepository($this->em);
        $logiServiceTypeRepo = $this->container->get("inv.service_management.logi_service_type_repository");

        $this->resolveHandoverServiceIdsFromTheirServiceType($handOverConfigsInfo, $serviceSegmentsInfo);


        return $handOverConfigsInfo;
    }


    /*
     * @param  [[key=>value],...] where value=[[key=>value],...] &$handOverConfigsInfo
     */
    function resolveHandoverServiceIdsFromTheirServiceType(&$handOverConfigsInfo, $serviceSegmentsInfo)
    {


        foreach ($handOverConfigsInfo as $key => $deviceSubservicesConfig) {

            $firstLogConfId = $deviceSubservicesConfig["hand_over_conf_ids"][0];
            $subServiceTypeId = $deviceSubservicesConfig["service_type_id"];

            //first,lookup handover service typedescriptor and from it ,find keys ,then resolve them to find service descriptor,
            // then,fill the keys and then form service id from formed descriptor

            /* @var $subServiceSegmentsInfo ServiceSegmentsInfo */
            $subServiceSegmentsInfo = $this->resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($firstLogConfId);
            $subServiceTypeName = $subServiceSegmentsInfo->serviceTypeName; //like : vlan
            $serviceDescId = $subServiceSegmentsInfo->serviceDescId;        //like : vlan123
            $descriptor = $subServiceSegmentsInfo->descriptor;


            $serviceId = $subServiceSegmentsInfo->getServiceDescriptorHash();

            //this as subservice id when viewing from main service and will show as SEGMENT_LOG_CON_ID
            $handOverConfigsInfo[$key]["service_id"] = $serviceId;
            $handOverConfigsInfo[$key]["service_name"] = $serviceDescId;
            $handOverConfigsInfo[$key]["descriptor"] = $descriptor;
            $handOverConfigsInfo[$key]["subservice_type_name"] = $subServiceTypeName;


        }

    }


    /*
     *  This is alternative way of getting config Ids using Tomas' created lookup table LOG_CONF_INDEXED_VAL
     *
     * @return [int] array of logical configuration ids having certain criteria
     */
    public function lookupConfigsFromLogConfIndexedValTable($serviceParams)
    {
        //$logiServiceLookupRepository=new LogiServiceLookupRepository($this->em);
        $logiServiceLookupRepository = $this->container->get("inv.service_management.logi_service_lookup_repository");
        $configIds = $logiServiceLookupRepository->resolveConfigsFromLogConfIndexedValTable($serviceParams);


        return $configIds;
    }


    /*  Moving to new way of figuring out service name and Id from LogiServiceType table descriptor column hint
     *
     * @param integer $configId
     * @param &integer $serviceTypeId (output)
     * @param &string $serviceTypeName (output)
     * @param &string $serviceDescId  (output)
     *
     * @return ServiceSegmentsInfo $serviceSegmentsInfo //same output info in this structure
     */
    public function resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($configId)
    {

        $serviceSegmentsInfo = new ServiceSegmentsInfo();
        //$serviceSegmentsInfo=$this->container->get("inv.service_management.service_segments_info");
        //service name is same as logical configuration logical entity name
        $serviceParams = $this->getFilledServiceDiscoveryParamsForConfig($configId, $serviceSegmentsInfo);

        $serviceTypeName = null;
        $serviceDescId = null;

        $this->resolveServiceIdFromFilledParams($serviceParams, $serviceTypeName, $serviceDescId);

        $serviceSegmentsInfo->setServiceTypeName($serviceTypeName);
        $serviceSegmentsInfo->setServiceDescId($serviceDescId);
        return $serviceSegmentsInfo;
    }

    function resolveServiceIdFromFilledParams($serviceParams, &$serviceTypeName, &$serviceDescId)
    {
        if ($serviceParams == null) {
            return null;
        }

        //by convention,first key is servicetype_id and contains service type name and id is its value
        reset($serviceParams);
        $first_key = key($serviceParams);
        //trim _id from $first_key
        $serviceTypeName = rtrim($first_key, "_id");
        $serviceDescId = $serviceParams[$first_key];

        return $serviceDescId;
    }

    /*
     *  This is alternative way of getting config Ids using Maziar's created lookup table LOGI_CONFIG_LOOKUP
     *
     * @return [int] array of logical configuration ids having certain criteria
     */
    public function lookupConfigsFromLogiServiceLookupTable($serviceParams)
    {
        //$logiServiceLookupRepository=new LogiServiceLookupRepository($this->em);
        $logiServiceLookupRepository = $this->container->get("inv.service_management.logi_service_lookup_repository");
        $configIds = $logiServiceLookupRepository->resolveConfigsFromLogiServiceLookupTable($serviceParams);
        return $configIds;
    }


    /*
     * use case where we are searching in one vlan
     */
    public function getSampleSearchParamsForIncremental()
    {

        //return [ "vlan_maz_id" => "1114"];
        //return [ "vlan_x_id" => "123"];
        return ["opt_id" => "1213"];
    }

    /*
     * @param ServiceSegmentsInfo  $serviceSegmentsInfo containing  [[key=>value],...] segments
     */
    public function saveServiceSegments($serviceSegmentsInfo, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        //$logiServiceSegmentRepository=new LogiServiceSegmentRepository($this->em);
        $logiServiceSegmentRepository = $this->container->get("inv.service_management.logi_service_segment_repository");
        $logiServiceSegmentRepository->saveSegments($serviceSegmentsInfo, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);

        //now save subservice (unconnected nodes as orphan segments) this makes main service refrences to subservices meaningful
        $subserviceInfos = $serviceSegmentsInfo->subserviceSegmentsInfos;

        foreach ((array)$subserviceInfos as $subserviceInfo) {
            $logiServiceSegmentRepository->saveSegments($subserviceInfo, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);

        }

    }


    /*
     * removes previous record corresponding to these segments which are already in db to make space for new ones
     */
    public function clearDbForSegments($serviceSegmentsInfo)
    {

        $serviceId = $serviceSegmentsInfo->getServiceId();
        //$logiServiceRepository=new LogiServiceRepository($this->em);
        $logiServiceRepository = $this->container->get("inv.service_management.logi_service_repository");
        //todo: do this in a transaction
        $logiServiceRepository->removeService($serviceId);

    }

    /* save theLogiService elements into db
     * note: this also resolves connection names of type logical (which are subservice service names
     * @param ServiceSegmentsInfo $serviceSegmentsInfo
     */
    public function saveServiceHeaderRecord($serviceSegmentsInfo)
    {

        /* @var $serviceSegmentsInfo ServiceSegmentsInfo */
        $serviceDescriptorHash = $serviceSegmentsInfo->getServiceDescriptorHash();
        if ($serviceDescriptorHash == null) {
            throw(new Exception("Descriptor not defined and hash can not be generated in saveServiceHeaderRecord."));
        }
        $serviceDescriptor = $serviceSegmentsInfo->getServiceDescriptor();
        //we  put service name as service descriptor
        $serviceName = $serviceDescriptor;
        $serviceTypeId = $serviceSegmentsInfo->getServiceTypeId();
        //$logiServiceRepository=new LogiServiceRepository($this->em);
        $logiServiceRepository = $this->container->get("inv.service_management.logi_service_repository");
        $logiServiceRepository->saveServiceRecord($serviceDescriptorHash, $serviceTypeId, $serviceDescriptor, $serviceName);

        //now save subservice place holders' service
        $subserviceInfos = $serviceSegmentsInfo->subserviceSegmentsInfos;

        foreach ((array)$subserviceInfos as $subserviceInfo) {
            $this->saveServiceHeaderRecord($subserviceInfo);
        }

    }

    public function saveService($serviceSegmentsInfo, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId, $designStatus = null)
    {
        $this->saveServiceHeaderRecord($serviceSegmentsInfo);
        $this->saveServiceSegments($serviceSegmentsInfo, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);

    }

    public function calculatePrevToModLogicalConfigurationId($logConfIdOfNode)
    {
        //get impacted event id and search a logical configuration having assoc event id as this impacted one
        //$logiConfRepo=new LogicalConfigurationRepository($this->em);
        $logiConfRepo = $this->container->get("inv.log_conf.log_conf_repository");
        /* @var $logicalConfiguration LogicalConfiguration */
        $logicalConfiguration = $logiConfRepo->findBy(["logConfObjectId" => $logConfIdOfNode]);

        if (count($logicalConfiguration) == 0) {
            return null;
        }
        $logicalConfigurationdata = $logicalConfiguration[0]->toArray();
        $impactedEventId = $logicalConfigurationdata['impactedEvent'];

        $logConfPhysId = $logicalConfigurationdata['logConfPhysicalId'];

        $prevToModlogicalConfiguration = $logiConfRepo->findBy(["logConfPhysicalId" => $logConfPhysId, "associateEvent" => $impactedEventId]);
        $prevToModLogicalConfigurationdata = $prevToModlogicalConfiguration[0]->toArray();
        $prevToModObjectId = $prevToModLogicalConfigurationdata['logConfObjectId'];
        return $prevToModObjectId;
    }


    /*
     *  find a service with matching $descriptor
     *
     * @param  string $discriptor :string which is used to describe a service
     * @return integer id of the service found or null otherwise
     */
    public function findMatchingService($descriptor)
    {
        if ($descriptor == null) {
            return null;
        }
        //$logiServiceRepository=new LogiServiceRepository($this->em);
        $logiServiceRepository = $this->container->get("inv.service_management.logi_service_repository");
        $serviceId = $logiServiceRepository->findMatchingService($descriptor);
        return $serviceId;
    }


    public function getAllServiceKeyParameters()
    {
        //$logiServiceTypeRepository=new LogiServiceTypeRepository($this->em);
        $logiServiceTypeRepository = $this->container->get("inv.service_management.logi_service_type_repository");
        $allServiceKeyParameters = $logiServiceTypeRepository->getAllServiceKeyParameters();
        return $allServiceKeyParameters;
    }

    public function getAllServiceKeyParametersForJs()
    {
        $allServiceKeyParameters = $this->getAllServiceKeyParameters();

        $strParameterListForJs = "[";
        foreach ($allServiceKeyParameters as $allServiceKeyParameter) {

            $strParameterListForJs .= "{name:'$allServiceKeyParameter'},";
        }
        $strParameterListForJs .= "]";

        return $strParameterListForJs;

    }

    /* given service Id,this will retrive human readable information of segments in the service (names instead of ids )
       * and prepare this info to be displayed later via display strategies
       */
    function resolveSegmentsFullInfoFromDb($serviceId)
    {

        //firstly,fetch records from db corresponding this service
        //$logiServiceSegmentRepository=new LogiServiceSegmentRepository($this->em);
        $logiServiceSegmentRepository = $this->container->get("inv.service_management.logi_service_segment_repository");
        $segments = $logiServiceSegmentRepository->resolveAllSegmentsFullInfoFromLogiSegments($serviceId);
        return $segments;

    }

    function removeService($serviceId)
    {
        //$logiServiceSegmentRepository=new LogiServiceSegmentRepository($this->em);
        $logiServiceSegmentRepository = $this->container->get("inv.service_management.logi_service_segment_repository");
        $logiServiceSegmentRepository->removeAllByServiceId($serviceId);

        //$logiServiceRepository=new LogiServiceRepository($this->em);
        $logiServiceRepository = $this->container->get("inv.service_management.logi_service_repository");
        $logiServiceRepository->removeService($serviceId);
    }

    function deleteConfigFromSegments($serviceConfigurationId)
    {
        //$logiServiceSegmentRepository=new LogiServiceSegmentRepository($this->em);
        $logiServiceSegmentRepository = $this->container->get("inv.service_management.logi_service_segment_repository");
        $logiServiceSegmentRepository->removeAllByConfigId($serviceConfigurationId);
    }

    function deleteSubserviceMadeThroughMainConfig($serviceConfigurationId)
    {
        $serviceSegmentsInfo = $this->resolveServiceTypeAndTypeNameAndDescriptorIdForConfig($serviceConfigurationId);
        $subserviceId = $serviceSegmentsInfo->getServiceId();
        //$logiServiceSegmentRepository=new LogiServiceSegmentRepository($this->em);
        $logiServiceSegmentRepository = $this->container->get("inv.service_management.logi_service_segment_repository");
        $logiServiceSegmentRepository->removeByLogConId($subserviceId);
    }

    public function AddParameterToAvailableParameters($parameterName, $parameterType)
    {
        //$logiServiceTypeRepository=new LogiServiceTypeRepository($this->em);
        $logiServiceTypeRepository = $this->container->get("inv.service_management.logi_service_type_repository");
        $logiServiceTypeRepository->AddParameterToAvailableParameters($parameterName, $parameterType);
    }


    public function resolveSegmentsConsideringPatchPanelsWithCascadeSelects($logConfIds)
    {

        $this->discoveredNetworkNodes = [];

        //$logiServiceSegmentRepo=new LogiServiceSegmentRepository($this->em);
        $logiServiceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");


        if (count($logConfIds) == 0) {
            return;
        }

        //$eqIds = $logiServiceSegmentRepo->getEquipmentIdsMock($logConfIds);
        sort($configIds);
        $eqIds = $logiServiceSegmentRepo->getEquipmentIds($logConfIds);
        //$eqIds=array_unique ($eqIds);

        $EquipmentInfo = new EquipmentInfo();//$this->container->get("inv.service_management.equipment_info");
        $EquipmentInfo->setLogConfOfEquips($eqIds, $logConfIds);

        $firstEqId = $eqIds[0];

        //$networkNodeGrouper=new NetworkNodeGrouper($eqIds);
        $networkNodeGrouper = $this->container->get("inv.service_management.network_node_grouper");
        $networkNodeGrouper->initialize($eqIds);

        $this->networkGrouper = $networkNodeGrouper;

        $eqToProcessId = $firstEqId;
        $prevNodeId = null;
        $prevPPanelOrEquip = "Equip";
        $startEqNode = null;

        while ($eqToProcessId != null) {

            $currentNode = new NetworkNode($eqToProcessId, "Equip", $prevNodeId, $prevPPanelOrEquip);
            //$currentNode=$this->container->get("inv.service_management.network_node");
            //$currentNode->initialize($eqToProcessId,"Equip",$prevNodeId,$prevPPanelOrEquip);

            if ($networkNodeGrouper->currentGroupId == null) {//if start of a group
                if ($prevNodeId == null) {//if we are the very first node,also add us to discovered nodes
                    $this->addToDiscoveredNodes($currentNode);
                    $startEqNode = $currentNode;
                }


                $networkNodeGrouper->setAsTraversed($currentNode);
                $prevNodeId = $currentNode->id;
                $prevPPanelOrEquip = $currentNode->PPanelOrEquip;
            }

            //first process simple connections
            $nextEquipmentNodes = $this->getNextConnectedEqipmentsToEquipment($currentNode, $networkNodeGrouper->getCurrentGroupNodes(), $startEqNode, $networkNodeGrouper->existingEquipmentIds, $logiServiceSegmentRepo);
            foreach ($nextEquipmentNodes as $nextEquipmentNode) {
                //todo: note: this still does not work for cloud.Make it work that way also
                $eqToProcessId = $networkNodeGrouper->setAsTraversed($nextEquipmentNode);
            }

            //if a discontinuity encountered,check if we are connected through patch panels first,and if not ,try a fresh start
            if (count($nextEquipmentNodes) == 0) {

                //check if patch paneled connection exists as another means of connection
                $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels = $this->getNextPatchPaneledConnectedEquipmentsToEqipment($currentNode, $startEqNode, $networkNodeGrouper->existingEquipmentIds, $logiServiceSegmentRepo);

                if ($nextConnectedEquipmentNodesDiscoveredThroughPatchPanels != null) {

                    //todo: make this work when many equipments found as next patch paneled nodes (below foreach loop)
                    //IMPORTANT: we might miss some eqs this way

                    $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels = $this->getNodeWithMinId($nextConnectedEquipmentNodesDiscoveredThroughPatchPanels);
                    //foreach($nextConnectedEquipmentNodesDiscoveredThroughPatchPanels as $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels) {
                    if ($nextConnectedEquipmentNodeDiscoveredThroughPatchPanels->id == $startEqNode->id) {//if a loop discovered, continue afresh
                        $networkNodeGrouper->currentGroupId = null;
                        $prevNodeId = null;
                        $prevPPanelOrEquip = null;
                        $eqToProcessId = $networkNodeGrouper->getFreshEqId();
                        continue;
                    }
                    $this->addToDiscoveredNodes($nextConnectedEquipmentNodeDiscoveredThroughPatchPanels);
                    $prevNodeId = $eqToProcessId;
                    $eqToProcessId = $networkNodeGrouper->setAsTraversed($nextConnectedEquipmentNodeDiscoveredThroughPatchPanels);
                    //}

                } else {
                    //although there is a path to an equipment via patch panels,but useless as equipment is not a member of eqIds!

                    $networkNodeGrouper->currentGroupId = null;
                    $prevNodeId = null;
                    $prevPPanelOrEquip = null;
                    $eqToProcessId = $networkNodeGrouper->getFreshEqId();
                    continue;

                }
            }
        }

        $m = 0;
        return $this->discoveredNetworkNodes;
    }


    function getNodeWithMinId($nodes)
    {
        $nodeInd = 0;
        $minId = 999999999999;
        $minInd = -1;
        foreach ($nodes as $node) {
            if ($node->id < $minId) {
                $minId = $node->id;
                $minInd = $nodeInd;
            }
            $nodeInd++;

        }

        return $nodes[$minInd];
    }

    function addToDiscoveredNodes($newNetworkNode)
    {
        $this->discoveredNetworkNodes[] = $newNetworkNode;
    }


    function getNextConnectedEqipmentsToEquipment($currentNode, $eqNodes, $startEqNode, $eqIds, $logiServiceSegmentRepo)
    {

        //keep this for detached from db debugging (bypassing next paragraph)
        // $next=[1=>[2,"con1","physical"],2=>[3,"con2","physical"],/*3=>[4,"con3","physical"],*/4=>[5,"con4","physical"],5=>[1,"con5"],7=>[8,"con6","physical"],8=>[9,"con7","physical"],11=>[12,"con_8","physical"],17=>[18,"con9","physical"],/*18=>[19,"con_10","physical"],*/19=>[20,"con11","physical"],20=>[17,"con12","physical"]];

        $nodeId = $currentNode->id;
        $nextEquipmentsRecords = $logiServiceSegmentRepo->getNextConnectedEqipmentsToEquipment($nodeId, $eqNodes);

        //todo: make this work with multiple connections To this equipment
        foreach ($nextEquipmentsRecords as $nextEquipmentsRecord) {

            $nexEqId = $nextEquipmentsRecord["NEXT_EQ_ID"];
            if (in_array($nexEqId, $eqIds)) {//only accept us if we are in same criteria equipments (this is given by $eqIds from outside)
                $next[$currentNode->id][0] = $nexEqId;
                $next[$currentNode->id][1] = $nextEquipmentsRecord["CON_ID"];
                $next[$currentNode->id][2] = $nextEquipmentsRecord["CON_TYPE"];
            }
        }

        if (isset($next[$currentNode->id])) {
            $newId = $next[$currentNode->id][0];
            $startEqId = $startEqNode->id;
            if ($newId == $startEqId) {
                //now  that we have looped back,we know which connection land on starting node.so lets set it
                $eqNode = $eqNodes[0];
                $eqNode->setConnectionDetails($next[$currentNode->id][1], $next[$currentNode->id][2]);
                $startEqNode->setConnectionDetails($next[$currentNode->id][1], $next[$currentNode->id][2]);
            } else {
                $eqNode = new NetworkNode($newId, "Equip", $currentNode->id, $currentNode->PPanelOrEquip);
                //$eqNode = $this->container->get("inv.service_management.network_node");
                //$eqNode->initialize($newId, "Equip",$currentNode->id,$currentNode->PPanelOrEquip);

                $eqNode->setConnectionDetails($next[$currentNode->id][1], $next[$currentNode->id][1], $next[$currentNode->id][2]);

            }
            return [$eqNode];
        } else {
            return [];
        }
    }

    function getNextPatchPaneledConnectedEquipmentsToEqipment($currentNode, $startEqNode, $eqIds, $logiServiceSegmentRepo)
    {

        $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels = $this->getNextPatchPanelledEqNode($eqIds, $currentNode, $startEqNode, $logiServiceSegmentRepo);
        return $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels;
    }

    function getNextPatchPaneledEquipmentMock($currentNode, $eqIds)
    {
        $startEqId = $currentNode->id;

        //conid ,int2,eqId
        $consToPp = ["i1" => ["con1", "i2", null]];
        $consFromPp = ["i2p" => ["ppcon1", "i3", null], "i3p" => ["ppcon2", "i4", 5]];
        $pPanels = ["i2" => "i2p", "i3" => "i3p"];

        $conIntIn = "i1";
        $conId = $consToPp["i1"][0];
        $conIntOut = $consToPp["i1"][1];

        //add first patch pannel to $nextPatchPaneledNodes  array

        $nextPatchPaneledNode = new NetworkNode($conIntIn, "PPanel", null, null);
        //$nextPatchPaneledNode=$this->container->get("inv.service_management.network_node");
        //$nextPatchPaneledNode->initialize($conIntIn,"PPanel",null,null);

        $nextPatchPaneledNode->setConnectionDetails($conId, $conId, "physical_pp");
        $nextPatchPaneledNode->setPatchPanelDetails(null, null, null, $conIntIn, null, $conIntOut, null);
        $nextPatchPaneledNodes[] = $nextPatchPaneledNode;

        $lastEquipId = null;
        $nextPatchPaneledNode = null;
        $nextPatchPaneledNodes = [];
        do {

            if (isset($pPanels[$conIntOut])) {

                $pPanelIntOut = $pPanels[$conIntOut];
                if (!isset($consFromPp[$pPanelIntOut])) {
                    $lastEquipId = $consFromPp[$pPanelIntOut][2];
                    break;
                }

                $conIntIn = $pPanelIntOut;
                $conId = $consFromPp[$pPanelIntOut][0];
                $conIntOut = $consFromPp[$pPanelIntOut][1];
                $endEquipOrNull = $consFromPp[$pPanelIntOut][2];

                //segment: [$conIntIn,$conId,$conIntOut,$endEquipOrNull];

                //since patch panels does not have id,we put their input interface id as their id
                //patch paneled nodes either end to a patch panel or an equipment(last destination) in case of latter,we put eqId as node id
                $nodeId = ($endEquipOrNull == null ? $conIntOut : $endEquipOrNull);
                $nextPatchPaneledNode = new NetworkNode($nodeId, "PPanel", null, null);
                //$nextPatchPaneledNode=$this->container->get("inv.service_management.network_node");
                //$nextPatchPaneledNode->initialize(nodeId,"PPanel",null,null);

                $nextPatchPaneledNode->setConnectionDetails($conId, $conId, "physical_pp");

                $nextPatchPaneledNode->setPatchPanelDetails(null, null, null, $conIntIn, null, $conIntOut, $endEquipOrNull);
                $nextPatchPaneledNodes[] = $nextPatchPaneledNode;
            } else {
                break;
            }
        } while (true);


        return $nextPatchPaneledNodes;
    }


    function getNextPatchPanelledEqNode($eqIds, $currentNode, $startEqNode, $logiServiceSegmentRepo)
    {

        $firstNode = $currentNode;

        $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels = null;
        $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels = [];
        $firstPatchPanels = $this->getNextConnectedPatchPanelsFromEquipment($currentNode, $logiServiceSegmentRepo);
        if (count($firstPatchPanels) > 0) {
            foreach ($firstPatchPanels as $firstPatchPanel) {

                $safetyTooManyPatchPannelCounter = 0;
                $firstPatchPanelOrEqNode = $this->createPatchPanelNodeFromPatchPanelRecord($firstPatchPanel, $currentNode);
                $chainedNodes[] = $firstPatchPanelOrEqNode;
                $currentNode = $firstPatchPanelOrEqNode;
                do {
                    $nextPatchPanelLandingOnAnother = $this->getNextConnectedPatchPanelToPatchPanel($currentNode, $logiServiceSegmentRepo);

                    if ($nextPatchPanelLandingOnAnother != null) {
                        $chainedNode = $this->createPatchPanelNodeFromPatchPanelRecord($nextPatchPanelLandingOnAnother, $currentNode);
                        $chainedNodes[] = $chainedNode;
                        $currentNode = $chainedNode;

                    } else {
                        break;
                    }
                    $safetyTooManyPatchPannelCounter++;
                } while (true && ($safetyTooManyPatchPannelCounter < 4));

                $nextPatchPanelNodeLandingOnAnEquipment = $this->getNextConnectedPatchPanelLandingOnAnEquipment($currentNode, $logiServiceSegmentRepo);

                if ($nextPatchPanelNodeLandingOnAnEquipment != null) {
                    if ($nextPatchPanelNodeLandingOnAnEquipment["NEXT_EQ_ID"] == $currentNode->leftId) {
                        return null;
                    }
                    $destinationEqNode = $this->createPatchPanelNodeFromPatchPanelRecord($nextPatchPanelNodeLandingOnAnEquipment, $currentNode);
                    $startNodeId = $startEqNode->id;
                    $destinationEqNodeId = $destinationEqNode->id;
                    if ($destinationEqNodeId == $startNodeId) {//if destination is start node
                        if ($firstNode->leftId != $startNodeId) {//if we are not already next node after start
                            $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels = $this->makeEqNodeFromPatchPaneledNodes($chainedNodes, $startEqNode);
                            $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels[] = $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels;
                        }
                    } elseif (in_array($destinationEqNodeId, $eqIds)) {
                        $chainedNodes[] = $destinationEqNode;

                        $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels = $this->makeEqNodeFromPatchPaneledNodes($chainedNodes, null);
                        $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels[] = $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels;
                    }
                } else {
                    //throw(new Exception("No equip or patch panel connected to patch pannel after eq Or ppEq with id {$currentNode->id} "));
                    return null;
                }
            }
        }


        return $nextConnectedEquipmentNodesDiscoveredThroughPatchPanels;
    }


    function makeEqNodeFromPatchPaneledNodes($nextPatchPaneledNodes, $startEqNode)
    {
        $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels = NetworkNode::makeEqNodeFromPatchPaneledNodes($nextPatchPaneledNodes, $startEqNode);
        return $nextConnectedEquipmentNodeDiscoveredThroughPatchPanels;
    }

    function createPatchPanelNodeFromPatchPanelRecord($patchPanelRecord, $currentNode)
    {
        $conType = $patchPanelRecord["CON_TYPE"];
        $equipOrPPanel = ["PP_PP" => "PPanel", "EQ_PP" => "PPanel", "PP_EQ" => "Equip"];
        $pPanelCardId = $patchPanelRecord["PPANEL_CARD_ID"];
        $pPanelCardModelId = $patchPanelRecord["PPANEL_CARD_MODEL_ID"];
        $pPanelIntIn = $patchPanelRecord["PPANEL_INT1_ID"];
        $pPanelIntInModel = $patchPanelRecord["PPANEL_INT1_MODEL_ID"];
        $pPanelIntOut = $patchPanelRecord["PPANEL_INT2_ID"];
        $pPanelIntOutModel = $patchPanelRecord["PPANEL_INT2_MODEL_ID"];
        $endEquipOrNull = $patchPanelRecord["NEXT_EQ_ID"];
        $conId = $patchPanelRecord["CON_ID"];
        $nodeId = $endEquipOrNull;// ($endEquipOrNull == null ? $pPanelIntIn : $endEquipOrNull);
        $patchPanelOrEqNode = new NetworkNode($nodeId, $equipOrPPanel[$conType], $currentNode->id, $currentNode->PPanelOrEquip);
        //$patchPanelOrEqNode = $this->container->get("inv.service_management.network_node");
        //$patchPanelOrEqNode->initialize($nodeId, $equipOrPPanel[$conType], $currentNode->id, $currentNode->PPanelOrEquip);

        $patchPanelOrEqNode->setConnectionDetails($conId, $conId, "physical");
        $patchPanelOrEqNode->setPatchPanelDetails($pPanelCardModelId, $pPanelCardId, $pPanelIntInModel, $pPanelIntIn, $pPanelIntOutModel, $pPanelIntOut, $endEquipOrNull);

        return $patchPanelOrEqNode;
    }

    function getNextConnectedPatchPanelsFromEquipment($currentNode, $logiServiceSegmentRepo)
    {
        $nodeId = $currentNode->id;
        /* @var $logiServiceSegmentRepo LogiServiceSegmentRepository */
        return $logiServiceSegmentRepo->getNextConnectedPatchPanelsFromEquipment($nodeId);

    }


    function getNextConnectedPatchPanelLandingOnAnEquipment($currentNode, $logiServiceSegmentRepo)
    {
        $ppOutInterfaceModelId = $currentNode->ppOutInterfaceModel;
        $ppCardId = $currentNode->ppCardId;

        /* @var LogiServiceSegmentRepository $logiServiceSegmentRepo */
        return $logiServiceSegmentRepo->getNextConnectedPatchPanelLandingOnAnEquipment($ppCardId, $ppOutInterfaceModelId);

    }

    function getNextConnectedPatchPanelToPatchPanel($currentNode, $logiServiceSegmentRepo)
    {
        $lefPP_InInterfaceModel = $currentNode->ppInInterfaceModel;
        $LeftPP_EqId = $currentNode->id;
        /* @var   $logiServiceSegmentRepo LogiServiceSegmentRepository */
        return $logiServiceSegmentRepo->getNextConnectedPatchPanelToPatchPanel($LeftPP_EqId, $lefPP_InInterfaceModel);

    }

//Note: kept here for possible future moving to add dedicated event to each segment instead of
//todo: move this segment creation to a Factory class (see EventDispatchingFinal branch)
    /*
    * @param double $serviceDescriptorHash
    * @param [] $serviceSegment [key=>value,...]
    */
    public function createNewSegment($serviceDescriptorHash, $serviceSegment, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        $logiServiceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $newSegId = $logiServiceSegmentRepo->createNewSegment($serviceDescriptorHash, $serviceSegment, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);
        return $newSegId;
    }

    /*
     * @param integer $assocEvenIds
     * @param integer $impactedEventIds
     * @param integer $segmentsNewDDocStatus
     * @param integer $designStatus
     */
    public function createNewSegmentRevision($segmentId, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        $logiServiceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $logiServiceSegmentRepo->createNewSegmentRevision($segmentId, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);
    }


    public function getLogicalConfiguration($logConfId)
    {
        $logicalCongigurationRepo = $this->container->get("inv.log_conf.log_conf_repository");

        $logicalConfiguration = $logicalCongigurationRepo->findOneBy(["logConfId" => $logConfId]);
        return $logicalConfiguration;
    }

    public function getLogicalConfigurationFromHistory($logConfId)
    {
        $logicalCongigurationHistoryRepo = $this->container->get("inv.log_conf.log_conf_history_repository");

        $logicalConfiguration = $logicalCongigurationHistoryRepo->findOneBy(["logConfId" => $logConfId]);
        return $logicalConfiguration;
    }


    /*
     *  sam as resolveNeighborhoodServiceSegments but used when $centerLogConfId  is no longer in logical configuration table(moved to history due to decom execution)
     *  Therefore the search algorithm will not use logical configuration info ,but uses segments table for fetching those segments
     *
     *  @param integer $centerLogConfId  :configId on which the search is centered to
     *  @return []
     */
    public function resolveNeighborhoodServiceSegmentsFromDb($centerLogConfId)
    {


        $serviceSegmentsInfo = new ServiceSegmentsInfo();

        //todo: IMPORTANT: this fails as tomas does not keep indexed values of decom logical configuration in index table,so no way to fill our params
        //we dont use this in caller instead for now
        $filledServiceDiscoveryParams = null;
        /*$filledServiceDiscoveryParams=$this->getFilledServiceDiscoveryParamsForConfig($centerLogConfId,$serviceSegmentsInfo,$useHistoryTable=true);
        if($filledServiceDiscoveryParams==null){
            return null;
        }

        //check if discovery params properly filled
        if(!$this->isDiscoveryKeysFine($filledServiceDiscoveryParams,$serviceSegmentsInfo)){

            return $serviceSegmentsInfo;

        }*/

        //1) form service descriptor from config service type
        //build service discovery descriptor

        //2) find segments
        $logiServiceSegmentRepo = $this->container->get("inv.service_management.logi_service_segment_repository");
        $serviceSegments = $logiServiceSegmentRepo->getAllSegmentsPassingThroughLogConfId($centerLogConfId);


        $serviceSegmentsInfo->setSegments($serviceSegments);

        return $serviceSegmentsInfo;

    }


}

