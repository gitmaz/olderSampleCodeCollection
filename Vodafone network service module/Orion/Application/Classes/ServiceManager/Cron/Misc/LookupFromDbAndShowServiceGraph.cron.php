<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 31/03/2016
 * Time: 9:06 AM
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
$netDataProvider = $container->get("inv.service_management.network_setup_from_inventory");

//2) also get previously recognised service info from db (it should be recognised incrementally on the time of service configuration assignments or I when AvalanchRecogniseAndSave was used)
//
//$serviceSegmentsInfo=new ServiceSegmentsInfo();
$serviceSegmentsInfo = $container->get("inv.service_management.service_segments_info");
$serviceSegmentsInfo->setFilledServiceDiscoveryParams($netDataProvider->getSampleSearchParamsForIncremental());
$serviceId = $serviceSegmentsInfo->getServiceId();
$serviceSegments = $netDataProvider->resolveSegmentsFullInfoFromDb($serviceId);


//3) recognise graph of service(nodes important in service and their connectivities)
//recognise graph of such devices with their interconnections
/* @var $serviceGraphRecogniser ServiceGraphRecognition */
//$serviceGraphRecogniser=new ServiceGraphRecognition($serviceSegments);
$serviceGraphRecogniser = $container->get("inv.service_management.service_graph_recognition");
$serviceGraphRecogniser->initialize($serviceSegments);

//4) display the recognised graph
//available print strategies: record_print (simple record print) ,visual_print (complete human readable tree printout of graph)
/* @var $displayStrategy ServiceGraphDisplayPrintWithPositioningStrategy */
$displayStrategy = Orion\ServiceManager\GraphPresentation\ServiceGraphDisplayStrategySelector::getStrategy("visual_print");
$displayCells = $serviceGraphRecogniser->getDisplayCells();
$displayStrategy->display($displayCells);

/* @var $displayStrategy ServiceGraphDisplayRecordHtmlStrategy */
$displayStrategy = Orion\ServiceManager\GraphPresentation\ServiceGraphDisplayStrategySelector::getStrategy("record_print");
$displayStrategy->display($serviceSegments);

