<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 5/06/2016
 * Time: 8:18 PM
 *
 * This class is specialised to process queues of propose new node -report new node  ,and propose decom  node and report
 * decom jobs queued through similar events happening on logical configurations
 *
 *  Note: This is an extra utility function (synchronous updates will be done by above functions)
 * This can be use asynchronous worker cron if only LogicalConfigurationManager loggs the new and decom nodes in
 * network_nodes_todo_list.txt
 *
 *  to make this work we need to have hard coded this codesnippet in reportNewLogicalConfiguration() and reportDeleteLogicalConfiguration():
 *
 *  temp asynchronous worker cron approach
 *  $myId=$logConf->getLogConfId();
 *  file_put_contents(TEMP_PATH . '/network_nodes_todo_list.txt', "ins $myId\n", FILE_APPEND);
 */

namespace InventoryApi\ServiceManagement\Service;

use Source;

class NetworkServiceQueueProcessor
{

    /*
     * @var NetworkServiceManager $networkServiceManager
     */
    private $networkServiceManager;

    /**
     * @param NetworkServiceManager $networkServiceManager
     */
    function __construct(
        NetworkServiceManager $networkServiceManager
    )
    {
        $this->networkServiceManager = $networkServiceManager;
    }


    /**
     *
     */
    public function processServiceNodeUpdateQueueAtOnce($queueFileName)
    {
        $bypassLogConfUIForDebug = false;

        //every x minute applies the network_nodes_todo_list and flush it(segment discovery on the center node and segment deletes).
        $toDoFilePath = APP_PATH . "/../temp/$queueFileName.txt";
        $toDoBackupFilePath = APP_PATH . "/../temp/{$queueFileName}_backup.txt";
        $actions = $this->parseActionsAllAtOnce($toDoFilePath, $toDoBackupFilePath);


        try {
            $insCount = 0;
            $delCount = 0;
            $prevActionIsRnew = false;
            $prevActionIsRdec = false;

            foreach ($actions as $action) {
                if ($action == "") {
                    continue;
                }
                $actionName = $action[0];
                $configId = $action[1];
                $ttrId = $action[2];
                $assocEventId = $action[3];
                $userId = $action[4];
                if ($actionName == "") {
                    continue;
                }


                if ($actionName == "rnew") { //if rnew or rdecom ,since logcof is proposing and then executing,we get information of log conf id from immediate next action
                    $prevActionIsRnew = true;
                    continue;
                }
                if ($actionName == "rdec") {
                    $prevActionIsRdec = true;
                    continue;
                }
                if ($prevActionIsRnew) {
                    $actionName = "rnew";//overwrite this pnew to rnew(immediate next action of any rnew in log is pnew which provides logconId)
                    $prevActionIsRnew = false;
                }
                if ($prevActionIsRdec) {
                    $actionName = "rdec";//overwrite this pdec to rdec(immediate next action of any rdecin log is pdec which provides logconId)
                    $prevActionIsRdec = false;
                }

                //Note: keep this as a  debug facility for bypassing user logical configuration assignment to equipments( list of equipments should be put in logged_equipments.txt)
                if ($bypassLogConfUIForDebug) {

                    require_once(ROOT_PATH . "../../InventoryAPI/InventoryApi/ServiceManagement/Util/LogicalConfToLoggedEquipmentsAssigment.php");
                    $logicalConfToLoggedEquipmentsAssignment = new LogicalConfToLoggedEquipmentsAssignment();
                    $logicalConfToLoggedEquipmentsAssignment->assignLogicalConfToNextEquipment($configId, $this->networkServiceManager->em);
                }

                $this->executeAction($actionName, $configId, $ttrId, $assocEventId, $userId, $insCount, $delCount);
            }
        } catch (\Exception $ex) {
            //if unsuccessfull in one of many todos,try to do the whole lot next time (bypass last truncate)

            copy($toDoBackupFilePath, $toDoFilePath);

            echo "An error happened while performing log action $actionName, on  logConf $configId: " . $ex->getMessage();
            exit(0);
        }


    }

    /**
     *
     */
    public function processServiceNodeUpdateQueueOneAtATime($queueFileName)
    {
        //every x minute applies the network_nodes_todo_list and flush it(segment discovery on the center node and segment deletes).
        $toDoFilePath = APP_PATH . "/../temp/$queueFileName.txt";
        $toDoBackupFilePath = APP_PATH . "/../temp/{$queueFileName}_backup.txt";
        $action = $this->parseActionsOneAtATime($toDoFilePath, $toDoBackupFilePath);

        if ($action == "") {
            return "action list is empty";
        }

        try {
            $insCount = 0;
            $delCount = 0;
            $prevActionIsRnew = false;
            $prevActionIsRdec = false;


            $actionName = $action[0];
            $configId = $action[1];
            $ttrId = $action[2];
            $assocEventId = $action[3];
            $userId = $action[4];


            if ($actionName == "rnew") { //if rnew or rdecom ,since logcof is proposing and then executing,we get information of log conf id from immediate next action
                $prevActionIsRnew = true;//overwrite this pnew to rnew(immediate next action of any rnew in log is pnew which provides logconId)
                $action = $this->parseActionsOneAtATime($toDoFilePath, $toDoBackupFilePath);//parse next action to get log conf id
                $actionName = "rnew";
                $configId = $action[1];
                $ttrId = $action[2];
                $assocEventId = $action[3];
                $userId = $action[4];

            }
            if ($actionName == "rdec") {
                $prevActionIsRdec = true;
                $action = $this->parseActionsOneAtATime($toDoFilePath, $toDoBackupFilePath);//parse next action to get log conf id
                $actionName = "rdec";//overwrite this pdec to rdec(immediate next action of any rdecin log is pdec which provides logconId)
                $configId = $action[1];
                $ttrId = $action[2];
                $assocEventId = $action[3];
                $userId = $action[4];


            }

            $this->executeAction($actionName, $configId, $ttrId, $assocEventId, $userId, $insCount, $delCount);

        } catch (\Exception $ex) {
            //if unsuccessfull in one of many todos,try to do the whole lot next time (bypass last truncate)

            copy($toDoBackupFilePath, $toDoFilePath);

            echo "An error happened while performing log action $actionName, on  logConf $configId: " . $ex->getMessage();
            exit(0);
        }


    }


    public function processQueuedActions($queueFileName)
    {
        //every x minute applies the network_nodes_todo_list and flush it(segment discovery on the center node and segment deletes).
        $queueFilePath = APP_PATH . "/../temp/$queueFileName.txt";
        $queueBackupFilePath = APP_PATH . "/../temp/{$queueFileName}_backup.txt";
        $action = $this->popRawActionFromQueue($queueFilePath, $queueBackupFilePath);

        if ($action != null) {

            $actionName = $action[0];
            $configId = $action[1];
            $ttrId = $action[2];
            $assocEventId = $action[3];
            $userId = $action[4];

            $insCount = 0;
            $delCount = 0;

            $this->executeAction($actionName, $configId, $ttrId, $assocEventId, $userId, $insCount, $delCount);
        } else {
            echo "everything is uptodate!";
        }


    }

    function executeAction($actionName, $configId, $ttrId, $assocEventId, $userId, &$insCount, &$delCount)
    {
        switch ($actionName) {
            case "pnew":
                $insCount++;
                $this->registerOrUpdateServiceNode($configId, $ttrId, $userId, $assocEventId, null, $designStatus = "PROPOSE_NEW");
                break;
            case "rnew":
                $insCount++;
                $this->registerOrUpdateServiceNode($configId, $ttrId, $userId, $assocEventId, null, $designStatus = "REPORT_NEW");
                break;
            case "rnewd"://specific for first time discovery of all inventory nodes
                $insCount++;
                $this->registerOrUpdateServiceNode($configId, $ttrId, $userId, $assocEventId, null, $designStatus = "REPORT_NEW_DIRECTLY");
                break;
            case "pmod":
                $delCount++;
                $insCount++;
                $this->registerOrUpdateServiceNode($configId, $ttrId, $userId, $assocEventId, null, $designStatus = "PROPOSE_MOD");
                break;

            case "pdec":
                $delCount++;
                $this->registerOrUpdateServiceNode($configId, $ttrId, $userId, $assocEventId, null, $designStatus = "PROPOSE_DECOM");
                break;
            case "rdec":
                $delCount++;
                $this->registerOrUpdateServiceNode($configId, $ttrId, $userId, $assocEventId, null, $designStatus = "REPORT_DECOM");
                break;
            case "rmod":
                $delCount++;
                $insCount++;
                $this->registerOrUpdateServiceNode($configId, $ttrId, $userId, $assocEventId, null, $designStatus = "REPORT_MOD");
                break;
        }

    }


    /*
     * @param string $queueFilePath
     * @param string $queueBackupFilePath
     * @return [assoc] $actions
     */
    private function parseActionsAllAtOnce($queueFilePath, $queueBackupFilePath)
    {

        //since our processing may take time,copy the content and flush it immediately for new incoming todos ,but make a backup in case we get exceptions
        $networkNodesQueue = file_get_contents($queueFilePath);

        //todo: think of any missed hit from newly created todo messages from LogicalConfigurationManager
        $hTodoFile = @fopen($queueFilePath, "r+");
        //@ftruncate($hTodoFile, 0);

        if ($networkNodesQueue != "") {//just do backup on data,not on nothing
            file_put_contents($queueBackupFilePath, $networkNodesQueue);
        }

        if ($networkNodesQueue == "") {
            echo "Everything is up to date.\n";
            return;
        }
        $queueItems = explode("\r\n", $networkNodesQueue);
        if (count($queueItems) == 1) {
            $queueItems = explode("\n", $networkNodesQueue);//if we have only \n
        }
        $actions = [];
        foreach ($queueItems as $queueItem) {

            $actionItem = $this->parseAction($queueItem);

            $actions[] = $actionItem;
        }

        return $actions;
    }


    /*
     * @param string $queueFilePath
     * @param string $queueBackupFilePath
     * @return [key=>val,...] $action
     */
    public function parseActionsOneAtATime($queueFilePath, $queueBackupFilePath)
    {

        //since our processing may take time,copy the content and flush it immediately for new incoming todos ,but make a backup in case we get exceptions
        $networkNodesQueue = file_get_contents($queueFilePath);

        //todo: think of any missed hit from newly created todo messages from LogicalConfigurationManager
        $hTodoFile = @fopen($queueFilePath, "r+");
        //@ftruncate($hTodoFile, 0);

        if ($networkNodesQueue != "") {//just do backup on data,not on nothing
            file_put_contents($queueBackupFilePath, $networkNodesQueue);
        }

        if ($networkNodesQueue == "") {
            echo "Everything is up to date.\n";
            return;
        }
        $queueItems = explode("\r\n", $networkNodesQueue);
        if (count($queueItems) == 1) {
            $queueItems = explode("\n", $networkNodesQueue);//if we have only \n
        }

        if (($key = array_search("", $queueItems)) !== false) {
            unset($queueItems[$key]);
        }
        $topMostActionStr = array_shift($queueItems);

        $reducedQueueItems = implode("\n", $queueItems);
        file_put_contents($queueFilePath, $reducedQueueItems);

        $topMostAction = explode(" ", $topMostActionStr);
        return $topMostAction;
    }


    private function parseAction($queueItem)
    {
        $queueItem = str_replace("\n", "", $queueItem);
        $queueItem = str_replace("\r", "", $queueItem);
        $queueItemParts = explode(' ', $queueItem);
        $action = $queueItemParts[0];
        $logConfId = $queueItemParts[1];
        $ttrIdTitle = (isset($queueItemParts[2]) ? $queueItemParts[2] : null);
        $ttrId = (isset($queueItemParts[3]) ? $queueItemParts[3] : null);
        $assocEventIdTitle = (isset($queueItemParts[4]) ? $queueItemParts[4] : null);
        $assocEventId = (isset($queueItemParts[5]) ? $queueItemParts[5] : null);
        $userIdTitle = (isset($queueItemParts[6]) ? $queueItemParts[6] : null);
        $userId = (isset($queueItemParts[7]) ? $queueItemParts[7] : null);

        $actionItem = [$action, $logConfId, $ttrId, $assocEventId, $userId];

        return $actionItem;
    }

    private function popRawActionFromQueue($queueFilePath, $queueBackupFilePath)
    {
        //since our processing may take time,copy the content and flush it immediately for new incoming todos ,but make a backup in case we get exceptions
        $networkNodesQueue = file_get_contents($queueFilePath);


        if ($networkNodesQueue != "") {//just do backup on data,not on nothing
            file_put_contents($queueBackupFilePath, $networkNodesQueue);
        } else {
            return null;
        }

        if ($networkNodesQueue == "") {
            echo "Everything is up to date.\n";
            return null;
        }
        $queueItems = explode("\r\n", $networkNodesQueue);
        if (count($queueItems) == 1) {
            $queueItems = explode("\n", $networkNodesQueue);//if we have only \n
        }

        $firstTodoLine = array_shift($queueItems);
        $newNetworkNodesQueue = implode("\n", $queueItems);
        file_put_contents($queueFilePath, $newNetworkNodesQueue);


        $actionItem = $this->parseAction($firstTodoLine);

        return $actionItem;
    }





    //Consultation points to underlying service discovery module

    /*
     * This will check a descriptor of a service to see if it matches previously known
     *  services in LOGI_SERVICE table ,if so it uses the previous allocated service id for
     *  master id for new service segment (SERVICE_SEGMENT_ID) ,otherwise it allocates new service
     *  in LOGI_SERVICE table and uses the new id for service segment.
     *
     *
     * @param integer $logConfIdOfNewNode : configuration which is triggering this addition
     * @param integer $ttrId
     * @param integer $userId
     * @param integer $assocEventId
     * @param integer $impactedEventId
     * @param string $designStatus  one of ("REPORT_NEW","PROPOSE_NEW","REPORT_DECOM","PROPOSE_DECOM") valuses
     */
    function registerOrUpdateServiceNode($logConfIdOfNode, $ttrId = null, $userId = null, $assocEventId = null, $impactedEventId = null, $designStatus = null)
    {

        if ($designStatus == "REPORT_DECOM_DIRECTLY") { //note :  case ($designStatus=="REPORT_DECOM") is special case of Propose Decom combined with delete

            //in case of report_decom, we need design doc info (such as eventIds) to be registered and then putting it to history,therefore propagating this
            $this->dismissServiceNode($logConfIdOfNode, $ttrId, $userId, $assocEventId, $impactedEventId, $designStatus);
            return null;
        }

        // Find prospective main service segments running on this config to other neighbor configs .

        //first, update segment on current state of given logical configuration
        $logConfObjectIdOfNode = $logConfIdOfNode;

        if ($designStatus != "REPORT_DECOM") {
            $logConfObj = $this->networkServiceManager->getLogicalConfiguration($logConfObjectIdOfNode);
        } else {
            $logConfObj = $this->networkServiceManager->getLogicalConfigurationFromHistory($logConfObjectIdOfNode);

        }

        $source = new Source();
        //todo: create event object from eventId intead of below hack
        $event = NULL;//$logConfObj->getParentEventEntity();//NULL
        //do a decom on the previous segments(ie segments found with previous logical configuration object Id value)
        switch ($designStatus) {
            case "PROPOSE_NEW":
                $this->networkServiceManager->proposeNewNode($logConfObj, $ttrId, $source, $userId, $event);
                break;
            case "PROPOSE_DECOM":
                $this->networkServiceManager->proposeDecommissionNode($logConfObj, $ttrId, $source, $userId, $event);
                break;
            case "PROPOSE_MOD":
                $this->networkServiceManager->proposeModificationToNode($logConfObj, $ttrId, $source, $userId, $event);
                break;

            case "REPORT_NEW":
                $this->networkServiceManager->reportNewNode($logConfObj, $ttrId, $source, $userId, $event);
                break;
            case "REPORT_NEW_DIRECTLY":
                $this->networkServiceManager->reportNewNodeDirectly($logConfObj);
                break;
            case "REPORT_DECOM":
                $this->networkServiceManager->reportDecomNode($logConfObj, $ttrId, $source, $userId, $event);
                break;
            case "REPORT_MOD":
                $this->networkServiceManager->reportModificationToNode($logConfObj, $ttrId, $source, $userId, $event);
                break;

        }

    }


}