<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 18/03/2016
 * Time: 4:22 PM
 *
 *
 *  Incremental approach
 *
 *  Only service segments of neighboring logical configs are discovered centered to given logical configuration
 *  This is used per manual defenition of logical configuration (service type ones) and discovered segments are
 *  accumulated in service segments table per user interaction
 */

//todo: add unit tests


use Orion\Controller\Service\Request;

//include constants
require_once('../../../../constants.php');
//include core functions
require_once('../../../../bootstrap.php');

//1) get a network configuration (physical and logical)

$container = Request::getContainer();

/* @var $netDataProvider NetworkSetupFromInventory */
//$netDataProvider = $container->get("inv.service_management.network_setup_from_inventory");

//2) discover device configs for specific service criteria
//Note:service params not yet used

//$serviceSegments=$netDataProvider->resolveNeighborhoodServiceSegments($netDataProvider->getSampleSearchParamsPlus(),460783);
//$networkServiceManager=new  InventoryApi\ServiceManagement\Service\NetworkServiceManager($netDataProvider);
$networkServiceManager = $container->get("inv.service_management.network_service_manager");
//sample test 2265_ALCP2E/01 configured for vlan_maz service type with vlan_maz_id=vlan_maz123 gaining conf id of 460783
//$networkServiceManager->registerOrUpdateNode(460783);

//a leasedline_maz1 node added which  will cause to connect vlan_maz1 nodes(460789,460881) virtually (through this subservice)
$networkServiceManager->registerOrUpdateServiceNode(460884);