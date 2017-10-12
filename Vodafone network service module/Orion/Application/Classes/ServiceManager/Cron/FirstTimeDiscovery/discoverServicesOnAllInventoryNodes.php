<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 14/06/2016
 * Time: 9:16 AM
 */

use InventoryAPi\LogicalConfiguration\Repository\LogConfIndexedValueRepository;
use InventoryApi\ServiceManagement\Service;
use Orion\Controller\Service\Request;

//include constants
require_once('../../../../constants.php');
//include core functions
require_once('../../../../bootstrap.php');

$container = Request::getContainer();

/* @var $netDataProvider NetworkSetupFromInventory */
$networkServiceManager = $container->get("inv.service_management.network_service_manager");

$logger = $container->get("inv.service_management.loggit");
$logger->log("Calling update service segments cron.");

$logiServiceTypeRepo = $container->get("inv.service_management.logi_service_type_repository");
$logIndexedValRepo = $container->get("inv.log_conf.log_conf_index_value_repository");
$networkServiceQueueProcessor = $container->get("inv.service_management.network_service_queue_processor");

//1) iterate through defined service types

$allDefinedLogiServiceTypes = $logiServiceTypeRepo->findAll();

foreach ($allDefinedLogiServiceTypes as $serviceType) {

    $serviceIdName = $serviceType->getServiceTypeMainKeyName();

    if ($serviceIdName != "vlan_y_id") {
        continue;
    }
//2) for each service type, find the prospective configuration Ids that can match them ,and add those to action queue

    /* @var $logConfRepo LogConfIndexedValueRepository */
    $logIndexedVals = $logIndexedValRepo->findBy(["paramName" => $serviceIdName]);

    //put configuration of those nodes into the service queue to be processed below
    foreach ($logIndexedVals as $logIndexedVal) {
        $logConfPhysId = $logIndexedVal->getLogConfPhysicalId();

        //put in action queue (ask for service discovery)
        logForServiceManager("rnewd", $logConfPhysId, -1, -1, -1);
//3)process action queue (discover the service)
        try {
            $networkServiceQueueProcessor->processServiceNodeUpdateQueueOneAtATime("network_nodes_todo_list");
        } catch (\Exception $ex) {
            Echo "An error Occured while updating segments from log: " . $ex->getMessage();
        }
    }

    $nodesNrProcessed = count($logIndexedVals);
    echo "\n\nFinished discovering segments for service: $serviceIdName ($nodesNrProcessed nodes).\n ";
}

echo "\n\nFINISHED  UPDATING ALL SERVICES.";


function logForServiceManager($actionAbr, $logicalConfId, $ttrId, $eventId, $userId)
{

    try {//$logicalConfId
        $logStr = "\n$actionAbr $logicalConfId ttr $ttrId event $eventId user $userId";
        file_put_contents(TEMP_PATH . '/network_nodes_todo_list.txt', $logStr, FILE_APPEND);

    } catch (\Exception $e) {
        \ExceptionDbLogger::logException($e, get_class($this) . "::logForServiceManager action $logStr ");
        throw $e;
    }


}