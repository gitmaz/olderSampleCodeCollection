<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 7/06/2016
 * Time: 10:06 AM
 */

use EntityNames;
use InventoryApi\DependencyInjection\Service\ContainerProvider;
use InventoryApi\Event\Factory\EntityExecutionFactory;
use InventoryAPi\ServiceManagement\Event\SegmentExecution;

//include constants
require_once('../../../constants.php');
//include core functions
require_once('../../../bootstrap.php');

//require_once 'C:\xampp\htdocs\InventoryAPI\InventoryApi\ServiceManagement\Event\SegmentExecution.php';

//1) get a network configuration (physical and logical)
$container = ContainerProvider::getContainer();
$em = $container->get("entity_manager_inv");

$eventToExecute = $em->getRepository(EntityNames::EVENT)
    ->findOneBy(array('eventId' => 1516886));

$entityExecution = EntityExecutionFactory::getEntityExecution($eventToExecute);
$retData = $entityExecution->executeEvent($eventToExecute);

/*$ex=new SegmentExecution();
$ex->executeDecommisionedEvent(NULL);*/
if (isset($retData[CommonConstant::IS_SUCCESS]) && $retData[CommonConstant::IS_SUCCESS]) {

}