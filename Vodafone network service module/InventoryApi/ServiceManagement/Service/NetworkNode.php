<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 3/05/2016
 * Time: 3:22 AM
 */
namespace InventoryApi\ServiceManagement\Service;

//todo: IMPORTANT : consult tomas on how to access ccontainer without dependency on Orion namespace
use InventoryApi\DependencyInjection\Service\ContainerProvider;

class NetworkNode
{
    /* can have values "PPanel" or "Equip" */
    public $id;
    public $PPanelOrEquip;
    public $leftConnectionId;
    public $leftConnectionName;
    public $leftConnectionType;//physical or ppService
    public $viaStr;
    public $leftPPanelOrEquip;
    public $leftId;

    //Patch panel details if we are one
    public $ppCardModel;
    public $ppCardId;
    public $ppInInterfaceModel;
    public $ppInInterfaceId;
    public $ppOutInterfaceModel;
    public $ppOutInterfaceId;
    public $ppEndEquipOrNull;
    private $container;

    public $childNodes;

    function __construct($id, $PPanelOrEquip, $leftId, $leftPPanelOrEquip)
    {
        $this->container = ContainerProvider::getContainer();
        $this->initialize($id, $PPanelOrEquip, $leftId, $leftPPanelOrEquip);
    }

    function initialize($id, $PPanelOrEquip, $leftId, $leftPPanelOrEquip)
    {
        $this->id = $id;
        $this->PPanelOrEquip = $PPanelOrEquip;

        $this->leftId = $leftId;
        $this->leftPPanelOrEquip = $leftPPanelOrEquip;
    }

    function setPatchPanelDetails($ppCardModel,
                                  $ppCardId,
                                  $ppInInterfaceModel,
                                  $ppInInterfaceId,
                                  $ppOutInterfaceModel,
                                  $ppOutInterfaceId,
                                  $ppEndEquipOrNull)
    {

        $this->ppCardModel = $ppCardModel;
        $this->ppCardId = $ppCardId;
        $this->ppInInterfaceModel = $ppInInterfaceModel;
        $this->ppInInterfaceId = $ppInInterfaceId;
        $this->ppOutInterfaceModel = $ppOutInterfaceModel;
        $this->ppOutInterfaceId = $ppOutInterfaceId;
        $this->ppEndEquipOrNull = $ppEndEquipOrNull;

    }

    function setConnectionDetails($connectionId, $connectionName, $connectionType, $viaStr = null)
    {
        if ($connectionId != null) {
            $this->leftConnectionId = $connectionId;
        } else {
            $serviceSegmentsInfo = new ServiceSegmentsInfo();
            //$serviceSegmentsInfo=$this->container->get("inv.service_management.service_segments_info");
            $serviceSegmentsInfo->setDescriptor($connectionName);
            $this->leftConnectionId = $serviceSegmentsInfo->getServiceId();
        }
        $this->leftConnectionName = $connectionName;
        $this->viaStr = $viaStr;
        $this->leftConnectionType = $connectionType;

    }

    function getNodeIdIfEquipment()
    {
        if ($this->PPanelOrEquip == "Equip") {
            return $this->id;
        } else {
            return null;
        }
    }

    function getNodeIdIfPatchPanel()
    {
        if ($this->PPanelOrEquip == "PPanel") {
            return $this->id;
        } else {
            return null;
        }
    }

    //nodes that this network node represents
    function represent($networkNodesThisNodeIsComposedOf)
    {
        $this->childNodes = $networkNodesThisNodeIsComposedOf;
        $this->suggestConnectionIdFromConnections($networkNodesThisNodeIsComposedOf);

    }

    private function suggestConnectionIdFromConnections($networkNodesThisNodeIsComposedOf)
    {
        $connectionIdStr = "";

        $viaStr = "";
        foreach ($networkNodesThisNodeIsComposedOf as $node) {
            $viaStr .= "{$node->leftConnectionId},";
        }

        //make a brief of connection as they get too long and not nice to show on UI
        $lastNode = end($networkNodesThisNodeIsComposedOf);
        $firstNode = reset($networkNodesThisNodeIsComposedOf);
        $connectionIdStr = $lastNode->leftConnectionId . "_to_" . $firstNode->leftConnectionId;


        $this->setConnectionDetails(null, $connectionIdStr, "ppService", $viaStr);
    }

    /*
     *  this creates linkable subservice to have details of patch pannel connection
     */
    public static function makeEqNodeFromPatchPaneledNodes($nextPatchPaneledNodes, $nodeToFillUp)
    {
        $lastNode = end($nextPatchPaneledNodes);
        $firstNode = reset($nextPatchPaneledNodes);
        if ($nodeToFillUp == null) {
            $newNode = new NetworkNode($lastNode->id, "Equip", $firstNode->leftId, "Equip");
            $newNode->represent($nextPatchPaneledNodes);

            return $newNode;
        } else {
            $nodeToFillUp->leftId = $firstNode->leftId;
            $nodeToFillUp->leftPPanelOrEquip = "Equip";
            $nodeToFillUp->represent($nextPatchPaneledNodes);
            return $nodeToFillUp;//used when node is the first node in a loop
        }
    }
}

