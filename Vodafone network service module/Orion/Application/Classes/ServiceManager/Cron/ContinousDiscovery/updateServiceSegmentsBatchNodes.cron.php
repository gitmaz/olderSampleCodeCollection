<?php
/*
 *
 *  * This should be used to when all segments to do can be processed at the same time (no user is asking for new segments update meanwhile)

 */
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


$networkServiceQueueProcessor = $container->get("inv.service_management.network_service_queue_processor");
try {
    $networkServiceQueueProcessor->processServiceNodeUpdateQueueAtOnce("network_nodes_todo_list");
} catch (\Exception $ex) {
    Echo "An error Occured while updating segments from log: " . $ex->getMessage();
}

echo "\n\nFinished updating segments.";