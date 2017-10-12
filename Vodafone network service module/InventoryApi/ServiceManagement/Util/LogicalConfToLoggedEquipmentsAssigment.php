<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 28/05/2016
 * Time: 10:55 PM
 *
 * This is a debugging utility to simulate assigning logical configurations to selected equipments (predefined in logged_equipments.txt)
 */

namespace InventoryApi\ServiceManagement\Service;


use Symfony\Component\Config\Definition\Exception\Exception;

class LogicalConfToLoggedEquipmentsAssignment
{


    public function assignLogicalConfToNextEquipment($logConfId, $em)
    {

        $loggedEquipmentFilePath = TEMP_PATH . "/logged_equipments.txt";
        $nextEquipmentIdToAssignLogConfTo = $this->popTopLoggedEquipment($loggedEquipmentFilePath);

        if ($nextEquipmentIdToAssignLogConfTo == null) {
            throw(new Exception("logged_equipments.txt is empty"));
        }

        $this->removeExistingRecords($logConfId, $em);

        //log conf table
        $sql = "Insert into LOGICAL_CONFIGURATION (LOG_CONF_ID,AS_IS,LAST_UPDATED,LAST_CHANGED,LOG_CONF_OBJECT_ID,LOG_CONF_PHYSICAL_ID,PROPOSED_DECOMMISSION,LOGICAL_ENTITY_ID,PARENT_EVENT_ENTITY_ID,PARENT_PHYSICAL_ID,IMPACTED_EVENT_ID,ASSOCIATE_EVENT_ID,SOURCE_ID,PARENT_LOGICAL_ENTITY_ID,XML_OBJECT_VALUE_TEMP1,XML_OBJECT_VALUE,MODEL_STRUCTURE_XSD,RENDER_STRUCTURE_XML,VALUE_XML,IS_BINDED) values ($logConfId,1,to_date('10/NOV/15','DD/MON/RR'),to_date('12/NOV/14','DD/MON/RR'),$logConfId,$logConfId,null,221,1,$nextEquipmentIdToAssignLogConfTo,null,null,3,null,null,null,null,null,null,null)";
        $this->executeSql($sql, $em);

        //index table
        $sql = "Insert into LOG_CONF_INDEXED_VAL (LOG_CONF_OBJECT_ID,LOG_CONF_PHYSICAL_ID,CONF_OBJ_TYPE_ID,PARAM_TYPE_ID,INT_VAL,STRING_VAL,LONG_VAL,FLOAT_VAL,PARAM_NAME) values ($logConfId,$logConfId,221,2,null,'2000',null,null,'vlan_y_id')";
        $this->executeSql($sql, $em);

        //$sql="Insert into LOG_CONF_INDEXED_VAL (LOG_CONF_OBJECT_ID,LOG_CONF_PHYSICAL_ID,CONF_OBJ_TYPE_ID,PARAM_TYPE_ID,INT_VAL,STRING_VAL,LONG_VAL,FLOAT_VAL,PARAM_NAME) values ($logConfId,$logConfId,122,4,0,null,null,null,'hand_over_config')";
        //$this->executeSql($sql,$em);

    }


    /*
     * @param string $toDoFilePath
     * @param string $toDoBackupFilePath
     * @return [assoc] $actions
     */
    private function popTopLoggedEquipment($loggedEquipmentFilePath)
    {

        //since our processing may take time,copy the content and flush it immediately for new incoming todos ,but make a backup in case we get exceptions
        $loggeEquipments = file_get_contents($loggedEquipmentFilePath);


        if ($loggeEquipments == "") {
            echo "No equipment found in logged equipments list.\n";
            return;
        }
        $equipIds = explode("\r\n", $loggeEquipments);
        if (count($equipIds) == 1) {
            $equipIds = explode("\n", $loggeEquipments);//if we have only \n
        }

        $topMostEquipId = array_shift($equipIds);

        $loggeEquipments = implode("\n", $equipIds);
        file_put_contents($loggedEquipmentFilePath, $loggeEquipments);

        return $topMostEquipId;
    }


    private function removeExistingRecords($logConfId, $em)
    {

        $sql = "delete from LOGICAL_CONFIGURATION  where LOG_CONF_OBJECT_ID=$logConfId";
        $this->executeSql($sql, $em);

        //index table
        $sql = "delete from LOG_CONF_INDEXED_VAL where LOG_CONF_OBJECT_ID=$logConfId";
        $this->executeSql($sql, $em);

    }

    private function executeSql($sql, $em)
    {
        $stmt = $em->getConnection()->prepare($sql);

        $stmt->execute();

    }
}