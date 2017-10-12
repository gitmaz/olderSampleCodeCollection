<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 3/05/2016
 * Time: 3:24 AM
 */
namespace InventoryApi\ServiceManagement\Service;

//todo: IMPORTANT : consult tomas on how to access ccontainer without dependency on Orion namespace
use InventoryApi\DependencyInjection\Service\ContainerProvider;

class NetworkNodeGrouper
{
    public $existingEquipmentIds = [];
    public $traversedEquipmentIds = [];

    public $nodeGroup = [];
    public $currentGroupId = null;
    private $container;

    function __construct()
    {

        $this->currentGroupId = null;

        $this->container = ContainerProvider::getContainer();
    }

    function initialize($equipmentIds)
    {
        $this->existingEquipmentIds = $equipmentIds;
    }

    /*
    * returns nextId to start from
    */
    function setAsTraversed($equipmentNode)
    {
        $equipmentId = $equipmentNode->id;

        $isLooped = false;
        if ($this->checkForLoopBack($equipmentId)) {
            $this->currentGroupId = null;
            $isLooped = true;
            if (count($this->existingEquipmentIds) > 0) {
                return $this->getFreshEqId();
            } else {
                return null;
            }

        }
        if ($this->currentGroupId == null) {
            $this->currentGroupId = $equipmentId;
            $this->nodeGroup[$this->currentGroupId] = new NetworkNodeGroup();
            //$this->nodeGroup[$this->currentGroupId]=$this->container->get("inv.service_management.network_node_group");
        }
        $this->moveToTraversedArray($equipmentId);
        $this->nodeGroup[$this->currentGroupId]->addNode($equipmentNode);

        //return next non processed as the next node to work on
        return $equipmentId;

    }

    function getFreshEqId()
    {
        return array_shift($this->existingEquipmentIds);
    }

    function moveToTraversedArray($equipmentId)
    {
        /*if($equipmentId==$this->currentGroupId){
          return;
        }*/
        $eqIdInd = array_search($equipmentId, $this->existingEquipmentIds);
        unset($this->existingEquipmentIds[$eqIdInd]);
        $this->traversedEquipmentIds[] = $equipmentId;
    }

    function checkForLoopBack($equipmentId)
    {
        if (in_array($equipmentId, $this->traversedEquipmentIds)) {
            return true;//mark group as its header equipment
        }
        return false;

    }

    function getCurrentGroupNodes()
    {
        return $this->nodeGroup[$this->currentGroupId]->nodes;
    }
}
