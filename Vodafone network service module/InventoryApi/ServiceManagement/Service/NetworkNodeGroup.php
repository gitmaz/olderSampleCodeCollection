<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 3/05/2016
 * Time: 3:23 AM
 */
namespace InventoryApi\ServiceManagement\Service;


class NetworkNodeGroup
{
    public $nodeIds = [];
    public $nodes;

    function __construct()
    {

    }

    function addNode($networkNode)
    {
        $this->nodeIds[] = $networkNode->id;
        $this->nodes[] = $networkNode;
    }
}