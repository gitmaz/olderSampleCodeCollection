<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 10/02/2016
 * Time: 4:10 PM
 *
 *   Purpose: given ServiceSegments ,it will build up a connectivity graph
 *   of contributing devices,connections and subservices of an specific service
 *
 *  Usage:instantiate by passing discovered devices it fills $serviceExtensionNodes which have all informatin about
 *  graph including the relative positioning of its nodes in $serviceExtensionNodes->positioner->displayCells
 */
namespace InventoryApi\ServiceManagement\Service;

//todo: IMPORTANT : consult tomas on how to access ccontainer without dependency on Orion namespace
use InventoryApi\DependencyInjection\Service\ContainerProvider;

class ServiceGraphRecognition
{
    //input
    private $serviceSegments;

    //output (valid after an instance is of this class is created)
    public $serviceNodes = [];//a node is either a device or a connection having  ( in entity out)  where entity is either device or connection
    public $serviceDeviceNodes = [];//for devices having a service
    public $serviceSubServiceDeviceNodes = [];//for sub service of a service on a device having the service

    //main output ,which will internally contain displayCells relative positioning of the nodes through $positioner
    public $serviceExtensionNodes = [];//for physical and service connections

    /*
     * @var GraphNodePositioner $positioner
     *
     */
    private $positioner;
    private $container;

    function __construct($serviceSegments)
    {
        $this->container = ContainerProvider::getContainer();
        //$this->initialize($serviceSegments);
    }

    function initialize($serviceSegments)
    {

        $this->serviceSegments = $serviceSegments;
        $this->buildServiceNodes();
        $this->recogniseGraph();
        $this->organizeGraph();
    }

    /*
    *  A service node is a node on which the service is operating.It can be a device( equipment or card)
    *  or it can be a physical connection connecting connecting two of those devices ,or can be another service connecting
    *
    */
    public function buildServiceNodes()
    {


        $this->serviceNodes = new ServiceNodes();
        $this->serviceExtensionNodes = new ServiceNodes();
        $this->serviceDeviceNodes = new ServiceNodes();

        foreach ($this->serviceSegments as $serviceSegment) {

            //sample segment
            // A--con1--B

            $isInInterface = false;
            $isOutInterface = false;
            $conInInterface = $conOutInterface = null;

            $card1 = null;
            $card2 = null;
            $interface1 = null;
            $interface2 = null;
            if (!isset($serviceSegment["INTERFACE_OBJECT_ID1"])) {//if interface not set,use card as a substitude
                $isInInterface = false;
                if (isset($serviceSegment["CARD_ID1"])) {
                    $conInInterface = $serviceSegment["CARD_ID1"];
                    $card1 = $serviceSegment["CARD_ID1"];
                } else {
                    $conInInterface = $serviceSegment["EQUIPMENT_ID1"];
                    $equipment1 = $serviceSegment["EQUIPMENT_ID1"];
                }
            } else {
                $conInInterface = $serviceSegment["INTERFACE_OBJECT_ID1"];
                $interface1 = $serviceSegment["INTERFACE_OBJECT_ID1"];

                $card1 = $serviceSegment["CARD_ID1"];
                $equipment1 = $serviceSegment["EQUIPMENT_ID1"];

            }

            if (!isset($serviceSegment["INTERFACE_OBJECT_ID2"])) {//if interface not set,use card as a substitude

                $isOutInterface = false;
                if (isset($serviceSegment["CARD_ID2"])) {
                    $conOutInterface = $serviceSegment["CARD_ID2"];
                    $card2 = $serviceSegment["CARD_ID2"];
                } else {//if card not set,use equipment
                    if (isset($serviceSegment["EQUIPMENT_ID2"])) {
                        $conOutInterface = $serviceSegment["EQUIPMENT_ID2"];
                    } else {
                        $conOutInterface = null;
                    }
                }
            } else {
                $conOutInterface = $serviceSegment["INTERFACE_OBJECT_ID2"];
                $interface2 = $serviceSegment["INTERFACE_OBJECT_ID2"];

                $card2 = $serviceSegment["CARD_ID2"];
                $equipment2 = $serviceSegment["EQUIPMENT_ID2"];
            }

            $connection = $serviceSegment["CONNECTION_ID"];
            //$deviceStart=$serviceSegment["EQUIPMENT_ID1"];
            $deviceStart = $serviceSegment["EQUIPMENT_NAME1"];//."/".$serviceSegment["CARD_ID1"];

            //$deviceEnd=$serviceSegment["EQUIPMENT_ID2"];
            $deviceEnd = $serviceSegment["EQUIPMENT_NAME2"];//."/".$serviceSegment["CARD_ID2"];
            $site1 = $serviceSegment["SITE1"];
            $site2 = $serviceSegment["SITE2"];
            $eq1FullName = $serviceSegment["EQ1_FULL_NAME"];
            $eq2FullName = $serviceSegment["EQ2_FULL_NAME"];
            $intf1FullName = isset($serviceSegment["INTF1_FULL_NAME"]) ? $serviceSegment["INTF1_FULL_NAME"] : $conInInterface;
            $intf2FullName = isset($serviceSegment["INTF2_FULL_NAME"]) ? $serviceSegment["INTF2_FULL_NAME"] : $conOutInterface;


            $serviceName = $serviceSegment["SERVICE_NAME"];
            $serviceId = $serviceSegment["SERVICE_ID"];
            $serviceRole = $serviceSegment["SERVICE_ROLE"];
            $service_config_type = null;//$serviceSegment["SERVICE_CONFIG_TYPE"];


            if (isset($serviceSegment["SEGMENT_LOG_CON_NAME"])) {//$serviceRole!="main"  ){
                //override connection id from null to subservice name,as we are a subservice
                $connection = $serviceSegment["SEGMENT_LOG_CON_NAME"];//$serviceId
            }

            $asis = null;
            if (isset($serviceSegment["ASIS"])) {//$serviceRole!="main"  ){
                $asis = $serviceSegment["ASIS"];
            }
            if (isset($serviceSegment["PROPDEC"])) {//$serviceRole!="main"  ){
                $propdec = $serviceSegment["PROPDEC"];
            }

            $segmentDesignStatus = null;

            if ($asis != null) {
                if ($asis == false) {
                    $segmentDesignStatus = "proposednew";

                    if ($propdec) {
                        $segmentDesignStatus = "proposedec";
                    }
                } else {
                    $segmentDesignStatus = "asis";
                }
            } else {
                $segmentDesignStatus = "asis";
            }

            $startLogConf = $serviceSegment["LOGI_CONF1_ID"];
            $endLogConf = $serviceSegment["LOGI_CONF2_ID"];
            $keyValuePairs = ["equipment1" => $equipment1
                , "equipment2" => $equipment2
                , "card1" => $card1
                , "card2" => $card2
                , "interface1" => $interface1
                , "interface2" => $interface2
                , "designStatus" => $segmentDesignStatus];

            //first start equipment or card
            $serviceStartDeviceNode = new ServiceNode(null, $deviceStart, $conInInterface, "device", null, $isInInterface, $site1, $keyValuePairs, $eq1FullName, $intf1FullName, $startLogConf);

            $this->serviceDeviceNodes->addNode($serviceStartDeviceNode);
            $this->serviceNodes->addNode($serviceStartDeviceNode);

            //middle connection
            $serviceExtensionNode = new ServiceNode($conInInterface, $connection, $conOutInterface, "connection", $isInInterface, $isOutInterface, null, $keyValuePairs, $serviceRole, $serviceName, $serviceId, $service_config_type);
            $this->serviceExtensionNodes->addDoubleParentNode($serviceExtensionNode);
            $this->serviceNodes->addNode($serviceExtensionNode);
            //set relation of device and this connection:
            $serviceStartDeviceNode->setRightSibling($serviceExtensionNode);


            //second end equipment or card
            $serviceEndDeviceNode = new ServiceNode($conOutInterface, $deviceEnd, null, "device", $isOutInterface, null, $site2, $keyValuePairs, $eq2FullName, $intf2FullName, $endLogConf);
            $this->serviceDeviceNodes->addNode($serviceEndDeviceNode);
            $this->serviceNodes->addNode($serviceEndDeviceNode);
            //set relation of device and connection:
            $serviceEndDeviceNode->setLeftSibling($serviceExtensionNode);

            //set relation of connection and two devices
            $serviceExtensionNode->setLeftSibling($serviceStartDeviceNode);
            $serviceExtensionNode->setRightSibling($serviceEndDeviceNode);
        }


    }


    public function recogniseGraph()
    {
        $viewForDebug = true;

        //first we put nodes in displayCells through GraphNodePositioner which will take care of correct relative positioning of them
        $positioner = new GraphNodePositioner();
        $positioner->createBlankDisplayCells(50, 50);

        $nodeCount = 0;
        //a device node to start from
        $startVertex = null;
        $endVertex = null;
        $edge = null;

        /* foreach((array)$this->serviceExtensionNodes->nodes as $interface=>$serviceExtensionNode) {
         
             $nodeCount++;
           
             $serviceExtensionNode->reserveColumnForEndPointNodes($positioner);
         }*/


        $nodeCount = 0;
        foreach ((array)$this->serviceExtensionNodes->nodes as $interface => $serviceExtensionNode) {

            $nodeCount++;
            /* @var $serviceExtensionNode ServiceNode */
            $serviceExtensionNode->position($positioner, $nodeCount);
            //$serviceExtensionNode->positionWithDedicatedColumnPerDevice($positioner,$nodeCount);
        }

        //keep positioner relative graph node position info for later display purposes
        $this->positioner = $positioner;
    }

    public function organizeGraph()
    {
        $lastY = $this->positioner->getFirstFreeY();
        for ($y = 0; $y < $lastY - 3; $y++) {
            for ($x = 0; $x < 50; $x++) {
                $colVal1 = $this->positioner->displayCells[$y][$x];
                $colVal2 = $this->positioner->displayCells[$y + 2][$x];

                if (($colVal1 != null) && ($colVal2 != null)) {
                    if ($colVal1->type == "device") {
                        if ($colVal1->id == $colVal2->id) {
                            //swap col
                            $row2 = $this->positioner->displayCells[$y + 1];
                            $this->positioner->displayCells[$y + 1] = $this->positioner->displayCells[$y + 2];
                            $this->positioner->displayCells[$y + 2] = $row2;
                            break;
                        }
                    }
                }
            }
        }

    }

    public function getDisplayCells()
    {
        return $this->positioner->displayCells;
    }

    /*
     * return maximum height the segments are occupying from screen
     */
    public function getDisplayHeight()
    {
        $displayHeight = $this->positioner->getFirstFreeY();
        return $displayHeight;
    }


}