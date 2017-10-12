<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 23/03/2016
 * Time: 8:59 AM
 *
 * This class encapsulates raw information of prospective or consolidated segments which alltogethe can make a service
 */
namespace InventoryApi\ServiceManagement\Service;

//todo: IMPORTANT : consult tomas on how to access ccontainer without dependency on Orion namespace
use InventoryApi\DependencyInjection\Service\ContainerProvider;

class ServiceSegmentsInfo
{
    public $serviceTypeId;
    public $serviceId;
    public $serviceTypeName;
    public $serviceName;
    public $segments;
    public $descriptor;
    public $serviceDescId;
    public $typeDescriptor;
    public $unfilledServiceDiscoveryParams; //array of key values with values to be filled to specific service case
    public $filledServiceDiscoveryParams; //array of key values with values filled to specific service case
    public $filledSubServiceDiscoveryParams; //array of key values with values filled to specific subservice case
    public $status;
    public $mismatchKeys = "";
    public $serviceDescriptorHash;
    public $segmentCount;
    public $subserviceSegmentsInfos;//array of same class object pointing to subservice of this service segments information
    public $indexInParentService;//denotes which index of segments from parent ServiceSegmentsInfo service resolves to this child ServiceSegmentsInfo
    public $upstreamServiceSegmentsInfo;//points to same structure object where this object acts as a subservice for
    public $networkNodes;//segments data but in the form of what PatchPanel bypass algorithm fills up
    private $container;

    function __construct()
    {
        $this->container = ContainerProvider::getContainer();
    }

    function initialize()
    {

    }

    /*
     * @param string $status : "found" ,"not found"
     */
    function setStatus($status)
    {
        $this->status = $status;
    }

    function setServiceTypeId($serviceTypeId)
    {
        $this->serviceTypeId = $serviceTypeId;
    }

    function setTypeDescriptor($typeDescriptor)
    {
        $this->typeDescriptor = $typeDescriptor;
    }

    function setDescriptor($descriptor)
    {
        $this->descriptor = $descriptor;
    }


    /*
     * @param [[key=>val],...]
     */
    public function setFilledServiceDiscoveryParams($filledServiceDiscoveryParams)
    {
        $this->filledServiceDiscoveryParams = $filledServiceDiscoveryParams;

        //also set the newly formed descriptor
        $this->formDescriptorFromParams($filledServiceDiscoveryParams);
    }

    function formDescriptorFromParams($filledServiceDiscoveryParams)
    {
        $descriptor = "";

        $counter = 0;
        foreach ((array)$filledServiceDiscoveryParams as $key => $value) {
            $andOrNothing = ($counter > 0 ? " AND " : "");
            $descriptor .= "{$andOrNothing}$key=$value";
            $counter++;
        }
        $this->descriptor = $descriptor;
    }

    /*
    * @param [[key=>val],...]
    */
    private function setUnfilledServiceDiscoveryParams($unfilledServiceDiscoveryParams)
    {
        $this->unfilledServiceDiscoveryParams = $unfilledServiceDiscoveryParams;
    }


    /*
     * @param [[key=>val],...]
     */
    function setUnfilledServiceDiscoveryParamsFromDescriptor($serviceTypeDescriptor)
    {
        $unfilledServiceDiscoveryParams = $this->translateToKeyValParams($serviceTypeDescriptor);
        $this->setUnfilledServiceDiscoveryParams($unfilledServiceDiscoveryParams);
    }


    function setSegments($segments)
    {
        $this->segments = $segments;
        $this->segmentCount = count($segments);
    }

    function setEvent($event)
    {
        $this->event = $event;
    }

    function setSegmentsEvent($segmentIndex, $event)
    {
        $this->event = $event;//event object containing timemachine properties of this segment
        $this->segments[$segmentIndex]["event"] = $event;
    }


    /*
     * @return ServiceSegmentsInfo
     */
    function getSegments()
    {
        return $this->segments;

    }

    /*
     * @param string $serviceDescriptor criteria of discovey for other configs of same service in string format
     * @return  [[key=>value],...] $serviceParams criteria of discovey for other configs of same service (service discovery rules).
     */
    private function translateToKeyValParams($serviceDescriptor)
    {

        if ($serviceDescriptor != null) {
            //check if $serviceDescriptor
            $serviceParams = $this->parseServiceDescriptor($serviceDescriptor);
            return $serviceParams;
        } else {
            echo "service description not found!";
            return null;
        }


    }

    /* todo: add more complex cases other than AND
     *  parses some string like vlan_id=vlan123 AND subnet=12345678 and returns [[vlan_id=>vlan123],[subnet=>12345678]]
     */
    function parseServiceDescriptor($serviceDescriptor)
    {
        $discoveryParams = [];
        $serviceDescriptorAndParts = explode("AND", $serviceDescriptor);
        foreach ($serviceDescriptorAndParts as $serviceDescriptorAndPart) {
            $keyValueParts = explode("=", $serviceDescriptorAndPart);
            $discoveryParams[$keyValueParts[0]] = $keyValueParts[1];

        }

        return $discoveryParams;
    }

    function getServiceDescriptorHash()
    {
        if ($this->descriptor == null) {
            return null;
        }
        if ($this->serviceDescriptorHash == null) {
            $this->serviceDescriptorHash = hexdec(md5($this->descriptor));
        }
        return $this->serviceDescriptorHash;
    }

    /*
     *  human readable service type such as vlan
     */
    function setServiceTypeName($serviceTypeName)
    {
        $this->serviceTypeName = $serviceTypeName;
    }

    /*  human readable id for service such as vlan123
     *
     */
    function setServiceDescId($serviceDescId)
    {
        $this->serviceDescId = $serviceDescId;
    }


    /*
     * @param ServiceSegmentsInfo $subserviceSegmentsInfo
     */
    function addSubserviceSegmentsInfo($subserviceSegmentsInfo)
    {
        $this->subserviceSegmentsInfos[] = $subserviceSegmentsInfo;
    }


    function getServiceDescriptor()
    {

        return $this->descriptor;
    }

    function getServiceTypeId()
    {
        return $this->serviceTypeId;
    }

    function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }

    function getServiceId()
    {
        $serviceDescriptorHash = $this->getServiceDescriptorHash();
        $this->serviceId = $serviceDescriptorHash;
        return $this->serviceId;
    }

    function getServiceName()
    {
        return $this->serviceName;
    }

    function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
    }

    function setParentSegmentIndex($indexInParentService)
    {
        $this->indexInParentService = $indexInParentService;
    }

    function refineQueryStr($serviceQueryStr)
    {
        $serviceQueryStr = trim($serviceQueryStr, ")");
        $serviceQueryStr = ltrim($serviceQueryStr, " ");
        $serviceQueryStr = trim($serviceQueryStr, " ");
        $serviceQueryStr = str_replace(['(', ')', '<strong>', '</strong>'], '', $serviceQueryStr);
        $serviceQueryStr = str_replace(" = ", '=', $serviceQueryStr);


        return $serviceQueryStr;
    }

    function setUpstream($serviceSegmentsInfo)
    {
        $this->upstreamServiceSegmentsInfo = $serviceSegmentsInfo;
    }

    function getUpstream()
    {
        return $this->upstreamServiceSegmentsInfo;
    }

    function setNetworkNodes($networkNodes)
    {
        $this->networkNodes = $networkNodes;
    }

    /*
     * @return [key=>null,...]
     */
    function getUnfilledServiceDiscoveryParams()
    {
        return $this->unfilledServiceDiscoveryParams;
    }

}