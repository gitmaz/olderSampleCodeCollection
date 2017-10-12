<?php

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 22/02/2016
 * Time: 12:04 PM
 *
 *  This  is used to represent each node in a graph either they are device,logical and physical connection (cable or service)
 */
namespace InventoryApi\ServiceManagement\Service;

//todo: IMPORTANT : consult tomas on how to access ccontainer without dependency on Orion namespace
use InventoryApi\DependencyInjection\Service\ContainerProvider;

class ServiceNode
{
    /*
     * @var string
     */
    public $type;// device subservice or connection

    /*
     * @var string
     */
    public $id;//either device id,subservice id or connection id
    public $in;//interface or device id  connected to this node
    public $isInInterface;//in can be interface or device
    public $out;//interface or device id  connected to this node
    public $isOutInterface;//out can be interface or device
    public $site;
    //additional data which can be saved in service node for later retrieval from UI
    public $tag;
    public $tag2;
    public $tag3;
    public $tag4;
    public $tag5;
    /*
     * @var ServiceNode $leftSiblingNode
     *  node directly after from left
     */
    public $leftSiblingNode;

    /*
    * @var ServiceNode $rightSiblingNode
    *  node directly after from right
    */
    public $rightSiblingNode;

    /*
     * @var array $position ["x"=>xxx,"y"=>yyy]
     */
    public $position = [];

    public $nextCellIndent;//ofset by which the node should be drawn if in two dimentional display

    public $keyValuePairs;
    private $container;

    function __construct($in, $id, $out, $type, $isInInterface, $isOutInterface, $site, $keyValuePairs, $tag = null, $tag2 = null, $tag3 = null, $tag4 = null, $tag5 = null)
    {
        $this->container = ContainerProvider::getContainer();
        $this->initialize($in, $id, $out, $type, $isInInterface, $isOutInterface, $site, $keyValuePairs, $tag, $tag2, $tag3, $tag4, $tag5);
    }


    function initialize($in, $id, $out, $type, $isInInterface, $isOutInterface, $site, $keyValuePairs, $tag = null, $tag2 = null, $tag3 = null, $tag4 = null, $tag5 = null)
    {
        $this->in = $in;
        $this->out = $out;
        $this->isInInterface = $isInInterface;
        $this->isOutInterface = $isOutInterface;
        $this->type = $type;
        $this->id = $id;
        $this->site = $site;
        $this->tag = $tag;
        $this->tag2 = $tag2;
        $this->tag3 = $tag3;
        $this->tag4 = $tag4;
        $this->tag5 = $tag5;
        $this->keyValuePairs = $keyValuePairs;
    }

    /*
     * @var ServiceNode $serviceNode
     */
    public function setRightSibling($siblingNode)
    {
        $this->rightSiblingNode = $siblingNode;
    }


    /*
     * @var ServiceNode $serviceNode
     */
    public function setLeftSibling($siblingNode)
    {
        $this->leftSiblingNode = $siblingNode;
    }

    /*
     * @var GraphNodePositioner $positioner
     *
     * called only from a connection node
     */
    public function position($positioner, $nodeCount = 1)
    {


        $leftDeviceNode = $this->leftSiblingNode;
        $rightDeviceNode = $this->rightSiblingNode;

        if ($rightDeviceNode->id != "/") {
            //if this is the first node,dont swap
            if ($nodeCount == 1) {
                $this->positionAsSegmentWithLeftNodeAtLeft($positioner, $leftDeviceNode, $rightDeviceNode);
            } else if ($positioner->checkNodeWithSameIdExistInMiddle($leftDeviceNode)) {

                $y = $this->positionAsSegmentWithLeftNodeAtLeft($positioner, $leftDeviceNode, $rightDeviceNode);
                //also if we got inserted before a segment,re adjust that other segment to get attached to us if necessay
                //$this->readjustStartAndEndNodesFromThisY_toEndY($positioner,$y+1);

            } else {
                $this->positionAsSegmentWithLeftNodeAtRight($positioner, $leftDeviceNode, $rightDeviceNode);
            }
        } else {//in case the connection id is null and right hand is "/" it means we are a dummy connection to hold a single node ($leftDeviceNode)
            $positioner->positionAsStartNode($leftDeviceNode);
        }

    }


    /*
	   * @var GraphNodePositioner $positioner
	   *
	   * called only from a connection node
	   */
    public function positionWithDedicatedColumnPerDevice($positioner, $nodeCount = 1)
    {


        $leftDeviceNode = $this->leftSiblingNode;
        $rightDeviceNode = $this->rightSiblingNode;

        if ($rightDeviceNode->id != "/") {
            //if this is the first node,dont swap
            if ($nodeCount == 1) {
                $this->positionAsSegmentWithLeftNodeAtLeftWithDedicatedColumnPerDevice($positioner, $leftDeviceNode, $rightDeviceNode);
            } else if ($positioner->checkNodeWithSameIdExistInMiddle($leftDeviceNode)) {

                $y = $this->positionAsSegmentWithLeftNodeAtLeftWithDedicatedColumnPerDevice($positioner, $leftDeviceNode, $rightDeviceNode);
                //also if we got inserted before a segment,re adjust that other segment to get attached to us if necessay
                //$this->readjustStartAndEndNodesFromThisY_toEndY($positioner,$y+1);

            } else {
                $this->positionAsSegmentWithLeftNodeAtRightWithDedicatedColumnPerDevice($positioner, $leftDeviceNode, $rightDeviceNode);
            }
        } else {//in case the connection id is null and right hand is "/" it means we are a dummy connection to hold a single node ($leftDeviceNode)
            $positioner->positionAsStartNode($leftDeviceNode);
        }

    }


    function reserveColumnForEndPointNodes($positioner, $nodeCount = 1)
    {
        $leftDeviceNode = $this->leftSiblingNode;
        $rightDeviceNode = $this->rightSiblingNode;
        $positioner->reserveNextAvailColumnForDevice($leftDeviceNode);//we put devices only in odd columns
        $positioner->reserveNextAvailColumnForDevice($rightDeviceNode);//we put devices only in odd columns
    }

    private function readjustStartAndEndNodesFromThisY_toEndY($positioner, $y)
    {
        $endY = $positioner->getFirstFreeY();

        $leftDeviceNode = null;
        $connectionNode = null;
        $rightDeviceNode = null;
        for ($Y = $y; $Y < $endY; $Y++) {
            //get each row as segment and position it again,as it might get a better positioning
            $positioner->popSegmentHavingY($Y, $leftDeviceNode, $connectionNode, $rightDeviceNode);
            if ($connectionNode != null) {
                $connectionNode->position($positioner, 2);
            }
        }

    }

    private function positionAsSegmentWithLeftNodeAtLeft($positioner, $leftDeviceNode, $rightDeviceNode)
    {
        //add $leftDeviceNode as device starting node (under a device with same name or otherwise at very left side of screen)
        /* @var $positioner GraphNodePositioner */
        $y = $positioner->positionAsStartNode($leftDeviceNode);
        //add $this as connection node
        $positioner->positionAsConnectionNode($this);
        //add $rightDeviceNode as device ending node
        $positioner->positionAsEndNode($rightDeviceNode);

        return $y;
    }

    private function positionAsSegmentWithLeftNodeAtLeftWithDedicatedColumnPerDevice($positioner, $leftDeviceNode, $rightDeviceNode)
    {
        //add $leftDeviceNode as device starting node (under a device with same name or otherwise at very left side of screen)
        /* @var $positioner GraphNodePositioner */

        $y = $positioner->positionNodeOnItsDedicatedColumn($leftDeviceNode);
        //add $this as connection node
        $positioner->positionAsConnectionNodeBetweenDedicatedColumns($this);
        //add $rightDeviceNode as device ending node
        $positioner->positionNodeOnItsDedicatedColumn($rightDeviceNode);

        return $y;
    }

    private function positionAsSegmentWithLeftNodeAtRight($positioner, $leftDeviceNode, $rightDeviceNode)
    {

        $rightDeviceNode->swapSiblings();
        $this->swapSiblings();
        $leftDeviceNode->swapSiblings();

        //add $leftDeviceNode as device starting node (under a device with same name or otherwise at very left side of screen)
        $positioner->positionAsStartNode($rightDeviceNode);
        //add $this as connection node
        $positioner->positionAsConnectionNode($this);
        //add $rightDeviceNode as device ending node
        $positioner->positionAsEndNode($leftDeviceNode);
    }


    private function positionAsSegmentWithLeftNodeAtRightWithDedicatedColumnPerDevice($positioner, $leftDeviceNode, $rightDeviceNode)
    {

        $rightDeviceNode->swapSiblings();
        $this->swapSiblings();
        $leftDeviceNode->swapSiblings();

        $this->positionAsSegmentWithLeftNodeAtLeftWithDedicatedColumnPerDevice($positioner, $leftDeviceNode, $rightDeviceNode);
    }

    public function swapSiblings()
    {
        $leftDeviceNode = $this->leftSiblingNode;
        $rightDeviceNode = $this->rightSiblingNode;
        $this->leftSiblingNode = $rightDeviceNode;
        $this->rightSiblingNode = $leftDeviceNode;
    }


}
