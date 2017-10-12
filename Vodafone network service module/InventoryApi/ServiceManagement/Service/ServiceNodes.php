<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 4/03/2016
 * Time: 11:07 AM
 */
namespace InventoryApi\ServiceManagement\Service;

//todo: IMPORTANT : consult tomas on how to access ccontainer without dependency on Orion namespace
use InventoryApi\DependencyInjection\Service\ContainerProvider;

class ServiceNodes
{
    /*
     * @var array of ServiceNode
     */
    public $nodes;
    private $container;

    function __construct()
    {
        $this->container = ContainerProvider::getContainer();

    }

    /*
     * @var int $inId  input interface or device of a node
     */
    public function getNodeByInterfaceOrDevice($inId)
    {
        if (isset($this->nodes[$inId])) {
            return $this->nodes[$inId];
        } else {
            return null;
        }

    }

    /*
     * @var ServiceNode $serviceNode
     */
    public function addNode(&$serviceNode)
    {
        if ($serviceNode->in != null) {
            if (!isset($this->nodes["_{$serviceNode->in}"])) {
                $this->nodes["_{$serviceNode->in}"] = $serviceNode;
            } else {
                $this->nodes["__{$serviceNode->in}"] = $serviceNode;
            }

        } elseif ($serviceNode->out != null) {//this (elseif instead of if) prevents connection nodes t o appear twice
            $this->nodes["_{$serviceNode->out}"] = $serviceNode;
        }
    }

    /*
    * @var ServiceNode $serviceNode
    */
    public function addDoubleParentNode(&$serviceNode)
    {
        if (!isset($this->nodes["{$serviceNode->in}_{$serviceNode->out}"])) {
            $this->nodes["{$serviceNode->in}_{$serviceNode->out}"] = $serviceNode;
        }
    }

}
