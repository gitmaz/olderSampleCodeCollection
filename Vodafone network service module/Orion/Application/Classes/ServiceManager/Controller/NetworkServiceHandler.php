<?php

/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 28/04/2016
 * Time: 8:54 AM
 */
namespace Orion\ServiceManager\Controller;

use CommonConstant;
use InventoryApi\ServiceManagement\Service;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Orion\Controller\Service\AbstractHandler;
use Orion\ServiceManager\GraphPresentation;
use Orion\Template\Service\Template;

// todo: consult Tomas on how to put this in Autoloader
require_once APP_PATH_CLASSES . '/ServiceManager/GraphPresentation/ServiceGraphDisplayStrategySelector.php';
require_once APP_PATH_CLASSES . '/ServiceManager/GraphPresentation/ServiceGraphDisplayHtmlWithPositioningStrategyForSave.php';
require_once APP_PATH_CLASSES . '/ServiceManager/GraphPresentation/ServiceGraphDisplayTableHtmlStrategy.php';
require_once APP_PATH_CLASSES . '/ServiceManager/GraphPresentation/ServiceGraphDisplayRecordHtmlStrategy.php';
require_once APP_PATH_CLASSES . '/ServiceManager/GraphPresentation/ServiceGraphDisplayMXGraphStrategy.php';
require_once APP_PATH_CLASSES . '/ServiceManager/GraphPresentation/MultiViewDisplayJqHtmlTabsStrategy.php';

class NetworkServiceHandler extends AbstractHandler
{
    private $netDataProvider;

    public function execute()
    {
        // keep this as sample data recovery of document ids
        // $param['ddid'] = Config::get('ddId');
        // Config::add('param', $param);
        /* @var $netDataProvider NetworkSetupFromInventory */
        $netDataProvider = $this->container->get("inv.service_management.network_setup_from_inventory");
        $this->netDataProvider = $netDataProvider;
        $tpl = Template::getInstance();
        $tpl->assign("netDataProvider", $netDataProvider);
        $tpl->loadTemplate('QueryService', $this->templatePath . '/QueryService.tpl.php');
        echo $tpl->getTemplateContent('QueryService');
    }

    public function queryAction()
    {
        $html = $this->handleServiceQuery();
        echo $html;
    }

    public function queryAffectedServicesAction()
    {
        $html = $this->handleAffectedServicesQuery();
        echo $html;
    }

    /*
     * @param bool $isAsynchProcessInPlace indicates if the update is done in an external async process (updateServiceSegments.cron.php)
     */
    private function updateServiceNodesFromLog($isAsynchProcessInPlace = true)
    {
        // if a process is in place,we do'nt need to do updates here.It will be done on that process in parallel which is
        // beneficial for user experience (use updateServiceSegments.cron.php instead)
        if ($isAsynchProcessInPlace) {
            return;
        }
        $networkServiceQueueProcessor = $this->container->get("inv.service_management.network_service_queue_processor");
        try {
            $networkServiceQueueProcessor->processServiceNodeUpdateQueueAtOnce("network_nodes_todo_list");
        } catch (\Exception $ex) {
            Echo "An error Occured while updating segments from log: " . $ex->getMessage();
        }
    }

    private function handleServiceQuery()
    {

        // Note: pass false and call this from a separate cron
        //       pass true,if there is another cron running that takes care of incremental updates.
        // if true passed use updateServiceSegmentsSingleNode.cron.php as a background cron job if concurrent insertion to log might happen
        $this->updateServiceNodesFromLog($isAsynchProcessInPlace = true);

        $bypasDbForDebug = false;
        if (!$bypasDbForDebug) {

            // 1) get a network configuration (physical and logical)
            /* @var $netDataProvider NetworkSetupFromInventory */
            $container = $this->container;
            $netDataProvider = $container->get("inv.service_management.network_setup_from_inventory");

            // 2) discover device configs for specific service criteria
            // note:keep this for hint of console only debugging

            $queryStr = $_POST["service_query_str"];

            if ($queryStr == "") {
                echo "\n<br>\nNo query str is set!";
                return;
            }
            // also get previously recognised service info from db (it should be recognised incrementally on the time of service configuration assignments or I when AvalanchRecogniseAndSave was used)
            $serviceSegmentsInfo = new \InventoryApi\ServiceManagement\Service\ServiceSegmentsInfo();
            $queryStr = $serviceSegmentsInfo->refineQueryStr($queryStr);
            $serviceSegmentsInfo->setDescriptor($queryStr);
            $serviceId = $serviceSegmentsInfo->getServiceId();
            $serviceSegments = $netDataProvider->resolveSegmentsFullInfoFromDb($serviceId);

            // 3) recognise graph of service(nodes important in service and their connectivities)
            // recognise graph of such devices with their interconnections
            /* @var $serviceGraphRecogniser ServiceGraphRecognition */
            // $serviceGraphRecogniser = new ServiceGraphRecognition($serviceSegments);
            $serviceGraphRecogniser = $container->get("inv.service_management.service_graph_recognition");

            $serviceGraphRecogniser->initialize($serviceSegments);
            $displayCells = $serviceGraphRecogniser->getDisplayCells();
            $displayHeight = $serviceGraphRecogniser->getDisplayHeight();
            // keep this: for creating sample data useful for bypassing databas (as the ones in SampleDbData folder)
            $str = serialize($displayCells);
            file_put_contents(TEMP_PATH . "/last_serialized_segments.txt", $str);
        } else {
            $displayCellsTxt = file_get_contents(APP_PATH_CLASSES . '/ServiceManager/Samples/SampleJsonData/doubleConnectionComplexity.txt', true);
            //keep this for debug:  $displayCellsTxt = file_get_contents(TEMP_PATH."/last_serialized_segments.txt", true);
            $displayCells = unserialize($displayCellsTxt);
            $displayHeight = 50;
            $serviceSegments = [];
        }

        // 4) display the recognised graph
        /* @var $displayStrategy ServiceGraphDisplayMxGraphStrategy */
        $displayStrategy = \Orion\ServiceManager\GraphPresentation\ServiceGraphDisplayStrategySelector::getStrategy("visual_mxgraph");
        $mxGraphHtml = $displayStrategy->display($displayCells, false);
        /* @var $displayStrategy ServiceGraphDisplayHtmlWithPositioningStrategy */
        $displayStrategy = \Orion\ServiceManager\GraphPresentation\ServiceGraphDisplayStrategySelector::getStrategy("visual_html");
        $visualHtml = $displayStrategy->display($displayCells, $displayHeight, false);

        /* @var $displayStrategy ServiceGraphDisplayTableHtmlStrategy */
        $displayStrategy = \Orion\ServiceManager\GraphPresentation\ServiceGraphDisplayStrategySelector::getStrategy("table_html");
        $tabularHtml = $displayStrategy->display($serviceSegments, false);

        /* @var $displayStrategy ServiceGraphDisplayRecordHtmlStrategy */
        $displayStrategy = \Orion\ServiceManager\GraphPresentation\ServiceGraphDisplayStrategySelector::getStrategy("record_html");
        $recordsHtml = $displayStrategy->display($serviceSegments, false);

        /* @var $multiViewDisplayStrategy MultiViewDisplayJqHtmlTabsStrategy */
        $activeTab = $_POST["active_tab"];
        $multiViewDisplayStrategy = \Orion\ServiceManager\GraphPresentation\MultiViewDisplayStrategySelector::getStrategy("jquery_tabs");
        $multiViewDisplayStrategy->display($mxGraphHtml, "Graphical View", $visualHtml, "Textual Graph View", $tabularHtml, "Tabular View", $recordsHtml, "Detail View", $activeTab);

    }

    function handleAffectedServicesQuery()
    {
        $networkServiceManager = $this->container->get("inv.service_management.network_service_manager");
        $logConfToDecomId = $_GET["logConfObjectId"];
        $servicesNames = $networkServiceManager->getAllServiceNamesAffectedByThisLogConfDecommission($logConfToDecomId);

        /* $servicesNames=["service1","service2"];*/


        $result = new \stdClass();
        $result->success = true;
        $result->affectedServiceNames = $servicesNames;
        $result->error = null;


        $servicesNamesInJson = json_encode($result);
        return $servicesNamesInJson;
    }
}

