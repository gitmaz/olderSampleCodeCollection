<?php
/*
 *   Avalanch discovery approach
 *   point of contact for discovery of service segments in dynamic memory only version in one go
 *   Note: main job of discovery is done in $netDataProvider->resolveServiceSegments
 */
//todo: add unit tests


use Orion\Controller\Service\Request;

//include constants
require_once('../../../../constants.php');
//include core functions
require_once('../../../../bootstrap.php');

//1) get network configuration (physical and logical)
$container = Request::getContainer();

/* @var $netDataProvider NetworkSetupFromInventory */
$netDataProvider = $container->get("inv.service_management.network_setup_from_inventory");

//2) discover device configs for specific service criteria

//2.1) finding nodes having same service and other attributes
try {
    $serviceSegmentsInfo = $netDataProvider->resolveServiceSegmentsAvalanch($netDataProvider->getSampleSearchParamsForIncremental());
} catch (Exception $ex) {
    Echo "An Error occured: " . $ex->getMessage();
    exit(0);
}

//2.2) save discovery into db

if ($serviceSegmentsInfo == null) {
    echo "\n\nAn Error occurred after calling resolveServiceSegmentsAvalanch";
    exit(0);
}

try {
    $netDataProvider->saveServiceHeaderRecord($serviceSegmentsInfo);
    $netDataProvider->saveServiceSegments($serviceSegmentsInfo);
} catch (Exception $ex) {
    Echo "An Error occured: " . $ex->getMessage();
    exit(0);
}

echo "\n\n Saved {$serviceSegmentsInfo->segmentCount} segments for {$serviceSegmentsInfo->descriptor} in LogiServiceSegments table\n\n";


$serviceSegments = $serviceSegmentsInfo->getSegments();

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
















