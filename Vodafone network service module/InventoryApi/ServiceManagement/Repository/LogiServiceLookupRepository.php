<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 2/03/2016
 * Time: 9:58 AM
 *
 * //todo: Move everything here to use existing LOG_CONF_INDEXED_VAL and PARAMETER_TYPE tables
 */

namespace InventoryApi\ServiceManagement\Repository;

use InventoryApi\Repository\AbstractEntityRepository;

class LogiServiceLookupRepository extends AbstractEntityRepository
{

    protected $entityClassName = \EntityNames::LOGI_SERVICE_LOOKUP;

    /*
   * @VAR \EntityManager $em
   */
    public function __construct($em)
    {
        $this->_em = $em;

        $this->class = $this->entityClassName;

        $this->_entityName = $this->entityClassName;
    }


    /*
   * Builds dynamic sql based on a very elastic number of parameters and values on log conf indexed val table
   *
   * @var [] $serviceParams
   */
    private function getGetDeviceConfigIdsHavingParamsSqlUsingLogConfIndexedValTable($serviceParams)
    {

        $lookupJoinTablesPartialSql = "Select lciv1.log_conf_object_id from \n ";
        $lookupWhereClausePartialSql = "";
        $paramCounter = 0;

        $serviceType = "";
        $onlyServiceTypeSupplied = false;
        foreach ($serviceParams as $paramKey => $paramValue) {
            if ($paramKey == "service_type") {
                $serviceType = $paramValue;
                $onlyServiceTypeSupplied = true;
                continue;
            }
            $onlyServiceTypeSupplied = false;
            $lookupKey = $paramKey;//$this->getServicLookupKeyIdForParamKeyForLogConfIndexedValTable($paramKey);
            $lookupKeyType = $this->getServiceLookupKeyValueTypeForParamKeyForLogConfIndexedValTable($lookupKey);
            $lookupValue = $paramValue;
            $paramCounter++;

            $this->addToSqlForQueryServiceLookupForLogConfIndexedValTable($lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $serviceType, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter);
        }

        $lookupSql = "$lookupJoinTablesPartialSql where $lookupWhereClausePartialSql";

        if ($onlyServiceTypeSupplied) {
            $lookupSql = "select  log_conf_object_id from log_conf_indexed_val where conf_obj_type_id={$serviceType['=']}";
        }

        return $lookupSql;
    }


    /*   ignoring serviceType as being special.treat .we dont need to include it at all (service_id is descriptive enough)
     * Builds dynamic sql based on a very elastic number of parameters and values on log conf indexed val table
     *
     * @var [] $serviceParams
     */
    private function getGetDeviceConfigIdsHavingParamsSqlUsingLogConfIndexedValTableNew($serviceParams)
    {

        $lookupJoinTablesPartialSql = " lciv1.log_conf_object_id from";
        $lookupWhereClausePartialSql = "";
        $paramCounter = 0;

        $serviceType = "";
        $onlyServiceTypeSupplied = false;
        foreach ($serviceParams as $paramKey => $paramValue) {

            $lookupKey = $paramKey;
            $lookupKeyType = $this->getServiceLookupKeyValueTypeForParamKeyForLogConfIndexedValTable($lookupKey);
            $lookupValue = $paramValue;
            $paramCounter++;

            $this->addToSqlForQueryServiceLookupForLogConfIndexedValTableNew($lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $serviceType, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter);
        }

        $lookupSql = "select $lookupJoinTablesPartialSql where $lookupWhereClausePartialSql";

        return $lookupSql;
    }


    /*
   * Builds dynamic sql from and where phrases based on a very elastic number of parameters and values on log conf indexed val table
   *  it is used to apply to
   *
   * @param [] $serviceParams
   * @param string &$lookupJoinTablesPartialSql : additional phrase for filtering on serviceParams as a table join from clause
   * @param string &$lookupWhereClausePartialSql : additional phrase for filtering on serviceParams as a where clause
   */
    private function getFilterOnParamsSqlPhrasesUsingLogConfIndexedValTable($serviceParams, &$lookupJoinTablesPartialSql, &$lookupWhereClausePartialSql)
    {
        $paramCounter = 0;
        foreach ($serviceParams as $paramKey => $paramValue) {

            $lookupKey = $paramKey;
            $lookupKeyType = $this->getServiceLookupKeyValueTypeForParamKeyForLogConfIndexedValTable($lookupKey);
            $lookupValue = $paramValue;
            $paramCounter++;

            $this->addToFilterOnParamsSqlPhrasesUsingLogConfIndexedValTable($lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter);
        }

    }


    /*
     * fills correct partial  sql tailored to this service configuration useful to find other similar configurations (those on the same service)
     *
     * @param integer $logConfId :logical configuration on which the service is based on
     * @param [[key=>val],...]  $filledServiceDiscoveryParams  :service descriptor     */
    public function getServiceDiscoveryDescriptorPartialSqlForConfig($logConfId, $filledServiceDiscoveryParams, &$lookupJoinTablesPartialSql, &$lookupWhereClausePartialSql)
    {

        $this->getFilterOnParamsSqlPhrasesUsingLogConfIndexedValTable($filledServiceDiscoveryParams, $lookupJoinTablesPartialSql, $lookupWhereClausePartialSql);
    }

    /*
   *
   * @param integer $logConfId :logical configuration from which we are looking up the below keys
   * @param [[key1=>%1],[key2=>%2],...]  $unfilledDicoveryParams :unfilled key value pairs as a source of lookup keys
   *
   * @return [[key=>value],...]  same $unfilledDicoveryParams array but with their values looked up and filled from Logical configuration data
   */
    public function fillServiceDiscoveryParamsFromConfigId($logConfId, $unfilledDicoveryParams)
    {

        try {
            foreach ((array)$unfilledDicoveryParams as $key => $value) {

                $lookedupValue = $this->getValueForServiceKeyOn($logConfId, $key);
                $unfilledDicoveryParams[$key] = $lookedupValue;
            }
        } catch (\Exception $ex) {
            echo "\n Error in fillServiceDiscoveryParamsFromConfigId : " . $ex->getMessage();
        }

        return $unfilledDicoveryParams;
    }


    /* //todo : depreciate this and substitute doctrine entity repo direct access after using service.xml
  * @param  integer $logConfId
  * @param  string  $serviceKey service key to lookup
  *
  * @return mix value for the key for this logical configuration
  */
    private function getValueForServiceKeyOn($logConfId, $serviceKey)
    {

        $keyDataType = $this->getKeyDataType($logConfId, $serviceKey);
        if ($keyDataType != null) {
            $returnColumn = null;
            switch ($keyDataType) {
                case 1:
                    $returnColumn = "int_val";
                    break;
                case 2:
                case 27:// a hack as data type is put wrongly on editing of xml tagging register index key
                    $returnColumn = "string_val";
                    break;
                case 3:
                    $returnColumn = "long_val";
                    break;
                case 4:
                    $returnColumn = "float_val";
                    break;
            }


            $sql = " select
                           $returnColumn as result
                       from
                          log_conf_indexed_val
                       where
                             log_conf_object_id=$logConfId
                             AND param_name='$serviceKey'
                        ";

            $stmt = $this->_em->getConnection()->prepare($sql);

            $stmt->execute(null);

            $keyValue = $stmt->fetchAll();

            return $keyValue[0]['RESULT'];
        } else {
            throw(new \Exception("datatype for $serviceKey is not set for logical configuration $logConfId "));
        }
    }


    /*
     * //todo : substitute with doctrine entity
     */
    private function getKeyDataType($logConfId, $serviceKey)
    {
        $sql = " select
               param_type_id
           from
              log_conf_indexed_val
                       WHERE
                             log_conf_object_id=$logConfId
                         AND param_name='$serviceKey'
                        ";

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute(null);
        $keyDataType = $stmt->fetchAll();

        if (isset($keyDataType[0]['PARAM_TYPE_ID'])) {
            return $keyDataType[0]['PARAM_TYPE_ID'];
        } else {
            return null;
        }
    }

    private function addToFilterOnParamsSqlPhrasesUsingLogConfIndexedValTable(&$lookupJoinTablesPartialSql, &$lookupWhereClausePartialSql, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter)
    {

        $commaOrNothing = ($paramCounter == 0 ? "" : ",");
        $ANDorNothing = ($paramCounter == 0 ? "" : " AND");
        $quotationOrNothing = ($lookupKeyType == "string" ? "'" : "");

        //decompose operation type and value
        //if we are dealing with values such a ["="=>value] or ["<"=>value] etc
        if (is_array($lookupValue)) {

            reset($lookupValue);
            $logicOperation = key($lookupValue);
            $lookupValue = $lookupValue[$logicOperation];
        } else { //simple case when params are key value pairs we consider the criteria operation to be equality
            $logicOperation = "=";
            $lookupValue = $lookupValue;//keep lookup value intact

        }

        $fieldToSearchOn = ["string" => "string_val", "integer" => "int_val", "long" => "long_val", "float" => "float_val"];
        if ($lookupKeyType == null) {
            $lookupKeyType = "string";
            if ($lookupValue == null) {//a hack not to create malformed sql if data is null
                $lookupValue = "'error_not_set'";
            }
        }
        $fieldTypeToSearchOnName = $fieldToSearchOn[$lookupKeyType];

        $tableAlias = "lciv$paramCounter";
        $lookupJoinTablesPartialSql .= " $commaOrNothing log_conf_indexed_val $tableAlias \n";
        $prevParamNumber = $paramCounter - 1;
        $confEqualityPhrase = ($paramCounter > 1 ? "AND lciv$paramCounter.log_conf_object_id=lciv$prevParamNumber.log_conf_object_id" : "");
        $lookupWhereClausePartialSql .= "$ANDorNothing  $tableAlias.param_name = '$lookupKey' AND $tableAlias.$fieldTypeToSearchOnName{$logicOperation}$quotationOrNothing{$lookupValue}$quotationOrNothing $confEqualityPhrase \n";

    }

    private function addToSqlForQueryServiceLookupForLogConfIndexedValTable(&$lookupJoinTablesPartialSql, &$lookupWhereClausePartialSql, $serviceType, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter)
    {

        $commaOrNothing = ($paramCounter == 1 ? "" : ",");
        $ANDorNothing = ($paramCounter == 1 ? "" : " AND");
        $quotationOrNothing = ($lookupKeyType == "string" ? "'" : "");

        //decompose operation type and value
        //if we are dealing with values such a ["="=>value] or ["<"=>value] etc
        if (is_array($lookupValue)) {

            reset($lookupValue);
            $logicOperation = key($lookupValue);
            $lookupValue = $lookupValue[$logicOperation];
        } else { //simple case when params are key value pairs we consider the criteria operation to be equality
            $logicOperation = "=";
            $lookupValue = $lookupValue;//keep lookup value intact
        }

        //we only allow equality for $serviceType if <> or < or > passed
        if (is_array($serviceType)) {
            reset($serviceType);
            $logicOperation = key($serviceType);
            $serviceType = $serviceType[$logicOperation];
        }


        $fieldToSearchOn = ["string" => "string_val", "integer" => "int_val", "long" => "long_val", "float" => "float_val"];

        $fieldTypeToSearchOnName = $fieldToSearchOn[$lookupKeyType];


        $tableAlias = "lciv$paramCounter";
        $lookupJoinTablesPartialSql .= " $commaOrNothing log_conf_indexed_val $tableAlias \n";
        $prevParamNumber = $paramCounter - 1;
        $confEqualityPhrase = ($paramCounter > 1 ? "AND lciv$paramCounter.log_conf_object_id=lciv$prevParamNumber.log_conf_object_id" : "");
        $lookupWhereClausePartialSql .= "$ANDorNothing $tableAlias.conf_obj_type_id=$serviceType AND $tableAlias.param_name = '$lookupKey' AND $tableAlias.$fieldTypeToSearchOnName{$logicOperation}$quotationOrNothing{$lookupValue}$quotationOrNothing $confEqualityPhrase \n";

    }


    private function addToSqlForQueryServiceLookupForLogConfIndexedValTableNew(&$lookupJoinTablesPartialSql, &$lookupWhereClausePartialSql, $serviceType, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter)
    {

        $commaOrNothing = ($paramCounter == 1 ? "" : ",");
        $ANDorNothing = ($paramCounter == 1 ? "" : " AND");
        $quotationOrNothing = ($lookupKeyType == "string" ? "'" : "");

        //decompose operation type and value
        //if we are dealing with values such a ["="=>value] or ["<"=>value] etc
        if (is_array($lookupValue)) {

            reset($lookupValue);
            $logicOperation = key($lookupValue);
            $lookupValue = $lookupValue[$logicOperation];
        } else { //simple case when params are key value pairs we consider the criteria operation to be equality
            $logicOperation = "=";
            $lookupValue = $lookupValue;//keep lookup value intact
        }

        //we only allow equality for $serviceType if <> or < or > passed
        if (is_array($serviceType)) {
            reset($serviceType);
            $logicOperation = key($serviceType);
            $serviceType = $serviceType[$logicOperation];
        }


        $fieldToSearchOn = ["string" => "string_val", "integer" => "int_val", "long" => "long_val", "float" => "float_val"];

        $fieldTypeToSearchOnName = $fieldToSearchOn[$lookupKeyType];


        $tableAlias = "lciv$paramCounter";
        $lookupJoinTablesPartialSql .= " $commaOrNothing log_conf_indexed_val $tableAlias \n";
        $prevParamNumber = $paramCounter - 1;
        $confEqualityPhrase = ($paramCounter > 1 ? "AND lciv$paramCounter.log_conf_object_id=lciv$prevParamNumber.log_conf_object_id" : "");
        $lookupWhereClausePartialSql .= "$ANDorNothing  $tableAlias.param_name = '$lookupKey' AND $tableAlias.$fieldTypeToSearchOnName{$logicOperation}$quotationOrNothing{$lookupValue}$quotationOrNothing $confEqualityPhrase \n";

    }


    /*
    *  gets the corresponding type for $paramKey (integer ,string etc )
    */
    private function getServiceLookupKeyValueTypeForParamKeyForLogConfIndexedValTable($paramKey)
    {

        return $this->getSearchFieldToApplySearchTo_ForLogConfIndexedValTable($paramKey);

    }

    private function getSearchFieldToApplySearchTo_ForLogConfIndexedValTable($paramKey)
    {

        $stmt = $this->_em->getConnection()->prepare("select final_type from parameter_type where parameter_type_name='$paramKey'");

        $stmt->execute();

        $finalType = $stmt->fetchAll();

        if (!isset($finalType[0]["FINAL_TYPE"])) {
            return null;
        }
        //todo: add exception handling if key not exists in db
        return $finalType[0]["FINAL_TYPE"];
    }

    /*
     * gets param str passed from UI in the form of (key1=val1 AND key2=val2 ...) and converts it to arrays of sql search phrases adapted
     *  for right lookup from table Log_Conf_Indexed_Val
     *
     * @return  ["key1=val1","key1>val2" ,"key2=val3' ,...] searchPhrases
     */
    public function parseParamString($paramStr)
    {
        $searchPhrases = explode("AND", $paramStr);
        return $searchPhrases;
    }


    /* find segments on nodes that have hand over functionality
     *
     *
     */
    public function filterConfIdsOfServiceForExplicitHandOverNodes($logConfIds, $serviceEntityId)
    {

        //1)find configurations that are repeated with other types (they are also registered in other services).

        // This will give us
        // configs of type handover (devices which hand over this service to other services)
        //sudo sql: select from cofs cof1, confs conf2 where conf1.conf_type=x  and conf2.entity_id=conf1.entityId conf2.conf_type_id!=conf1.conf_type_id  order by conf2.confType


        $configIdsStr = implode(",", $logConfIds);


        if (is_array($serviceEntityId)) {
            $serviceEntityId = $serviceEntityId["="];
        }


        $sql = "Select
                   distinct d1lc1.log_conf_id as main_log_conf_id,
                   d1lc2.log_conf_id as hand_over_conf_id,
                   d1lc2.logical_entity_id as service_type_id,
                   null as service_id
                 from
                   log_conf_indexed_val lciv1,
                   logical_configuration d1lc1,
                   logical_configuration d1lc2,
                   logical_configuration d2lc1,
                   logical_configuration d2lc2

                 where  d1lc1.log_conf_id in ($configIdsStr) AND
                        d1lc1.logical_entity_id=$serviceEntityId AND
                        d2lc1.logical_entity_id=$serviceEntityId AND
                        lciv1.log_conf_object_id=d1lc2.log_conf_id AND
                        lciv1.param_name='hand_over_config' AND
                        d1lc1.log_conf_id=lciv1.int_val AND
                        d1lc1.parent_physical_id=d1lc2.parent_physical_id AND
                        d2lc1.parent_physical_id=d2lc2.parent_physical_id AND
                        d1lc2.logical_entity_id!=$serviceEntityId AND
                        d2lc2.logical_entity_id=d1lc2.logical_entity_id
        ";


        $correctedSql = "Select
                   distinct d1lc1.log_conf_id as main_log_conf1_id,
                            d2lc1.log_conf_id as main_log_conf2_id,
                            d1lc2.log_conf_id as subservice_conf1_id,
                            d2lc2.logical_entity_id as subservice_conf2_id,
                            lciv1.int_val as INT_VAL,
                            $serviceEntityId as service_type_id,
                            d2lc2.logical_entity_id as subservice_type_id
                 from
                   log_conf_indexed_val lciv1,
                   logical_configuration d1lc1,
                   logical_configuration d1lc2,
                   logical_configuration d2lc1,
                   logical_configuration d2lc2,
                   logi_service_type lc2lst,
                   log_conf_indexed_val lciv2,
                   log_conf_indexed_val lciv3

                 where  d1lc1.log_conf_id in ($configIdsStr) AND
                        d2lc1.log_conf_id in ($configIdsStr) AND
                        d1lc1.log_conf_id<>d2lc1.log_conf_id AND
                        d1lc2.log_conf_id<>d2lc2.log_conf_id AND
                        d1lc1.logical_entity_id=$serviceEntityId AND
                        d2lc1.logical_entity_id=$serviceEntityId AND
                        lciv1.param_name='hand_over_config' AND
                        d1lc2.log_conf_id=lciv1.log_conf_object_id AND
                        d1lc1.log_conf_id=lciv1.int_val AND
                        d1lc2.parent_physical_id=d1lc1.parent_physical_id AND
                        d2lc2.parent_physical_id=d2lc1.parent_physical_id AND
                        d1lc2.logical_entity_id!=$serviceEntityId AND
                        d2lc2.logical_entity_id=d1lc2.logical_entity_id AND
                        lc2lst.service_type_id=d1lc2.logical_entity_id AND
                        lciv2.log_conf_object_id=d1lc2.log_conf_id AND
                        lciv2.param_name=lc2lst.service_type_main_key_name AND
                        lciv3.log_conf_object_id=d2lc2.log_conf_id AND
                        lciv3.param_name=lc2lst.service_type_main_key_name AND
                        lciv2.string_val=lciv3.string_val
                        ";//order by INT_VAL

        $stmt = $this->_em->getConnection()->prepare($correctedSql);

        $stmt->execute(null);

        $configIdsRaw = $stmt->fetchAll();

        $configIds = [];

        $configs = [];
        $handOverConfigIds = [];
        $isEven = false;
        $cell = [];
        foreach ($configIdsRaw as $configIdRaw) {
            $conf1 = $configIdRaw["MAIN_LOG_CONF1_ID"];
            $conf2 = $configIdRaw["MAIN_LOG_CONF2_ID"];
            if (isset($cell["{{$conf1}_$conf2"]) or isset($cell["{$conf2}_$conf1"])) {
                continue;
            }
            $cell["{$conf1}_$conf2"] = 1;
            $cell["{$conf2}_$conf1"] = 1;

            $configIds[0] = intval($conf1);
            $configIds[1] = intval($conf2);
            $handOverConfigIds[0] = intval($configIdRaw["SUBSERVICE_CONF1_ID"]);
            $handOverConfigIds[1] = intval($configIdRaw["SUBSERVICE_CONF2_ID"]);
            $serviceTypeId = $configIdRaw["SERVICE_TYPE_ID"];
            $serviceId = $serviceEntityId;//$configIdRaw["SERVICE_ID"];
            $configs[] = ["service_type_id" => $serviceTypeId, "service_id" => $serviceId, "main_conf_ids" => $configIds, "hand_over_conf_ids" => $handOverConfigIds];

        }

        return $configs;

    }


    private function getServiceDescriptorForlogicalconfiguration($servicelogicalconfigurationId)
    {

        //first,find descriptor descriptor type for logical configuration to know which keys to lookup
        //service type is same as logical Entity id
        $serviceTypeId = $this->getServiceTypeFromLogicalConfiguration($servicelogicalconfigurationId);
    }


//Queries based on LOG_CONF_INDEXED_VAL  table as lookup table


    /*
     *  returns config ids from LOGI_SERVICE_LOOKUP_TABLE
     *  @RETURN array configIds
     */
    public function resolveConfigsFromLogiServiceLookupTable($serviceParams)
    {

        return $this->getDeviceConfigIdsHavingParamsFromLogiServiceLookupTable($serviceParams);
    }

    private function getDeviceConfigIdsHavingParamsFromLogiServiceLookupTable($serviceParams)
    {
        $lookupSql = $this->getGetDeviceConfigIdsHavingParamsSqlUsingLogiServiceLookupTable($serviceParams);

        //complete end to end connection device resolution
        $stmt = $this->_em->getConnection()->prepare($lookupSql);

        $params = [];

        $stmt->execute($params);

        $configIdsRaw = $stmt->fetchAll();

        $configIds = [];
        foreach ($configIdsRaw as $configIdRaw) {
            $configIds[] = intval($configIdRaw["SERVICE_LOOKUP_VALUE"]);
        }

        return $configIds;

    }


    /*
    *  returns config ids from LOG_INDEXED_VAL table
    *  @RETURN array configIds
    */
    public function resolveConfigsFromLogConfIndexedValTable($serviceParams)
    {

        return $this->getDeviceConfigIdsHavingParamsLogConfIndexedValTable($serviceParams);
    }

    private function getDeviceConfigIdsHavingParamsLogConfIndexedValTable($serviceParams)
    {
        $lookupSql = $this->getGetDeviceConfigIdsHavingParamsSqlUsingLogConfIndexedValTableNew($serviceParams);


        //complete end to end connection device resolution
        $stmt = $this->_em->getConnection()->prepare($lookupSql);
        $stmt->execute(null);

        $configIdsRaw = $stmt->fetchAll();

        $configIds = [];
        foreach ($configIdsRaw as $configIdRaw) {
            $configIds[] = intval($configIdRaw["LOG_CONF_OBJECT_ID"]);
        }

        return $configIds;

    }


    /*
     * Builds dynamic sql based on a very elastic number of parameters and values on Logi_Service_Lookup table
     */
    private function getGetDeviceConfigIdsHavingParamsSqlUsingLogiServiceLookupTable($serviceParams)
    {

        $lookupJoinTablesPartialSql = "Select lsl1.service_lookup_value from \n ";
        $lookupWhereClausePartialSql = "";
        $paramCounter = 0;

        $serviceType = "";
        foreach ($serviceParams as $paramKey => $paramValue) {
            if ($paramKey == "service_type") {
                $serviceType = $paramValue;
                continue;
            }
            $lookupKey = $this->getServicLookupKeyIdForParamKeyForLogiServiceLookupTable($paramKey);
            $lookupKeyType = $this->getServicLookupKeyIdTypeForParamKeyIdForLogiServiceLookupTable($lookupKey);
            $lookupValue = $paramValue;
            $paramCounter++;

            $this->addToSqlForQueryServiceLookupForLogiServiceLookupTable($lookupJoinTablesPartialSql, $lookupWhereClausePartialSql, $serviceType, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter);
        }

        $lookupSql = "$lookupJoinTablesPartialSql where $lookupWhereClausePartialSql";

        return $lookupSql;
    }


    private function addToSqlForQueryServiceLookupForLogiServiceLookupTable(&$lookupJoinTablesPartialSql, &$lookupWhereClausePartialSql, $serviceType, $lookupKey, $lookupValue, $lookupKeyType, $paramCounter)
    {

        $commaOrNothing = ($paramCounter == 1 ? "" : ",");
        $ANDorNothing = ($paramCounter == 1 ? "" : " AND");
        $quotationOrNothing = ($lookupKeyType == "string" ? "'" : "");

        $tableAlias = "lsl$paramCounter";
        $lookupJoinTablesPartialSql .= " $commaOrNothing logi_service_lookup $tableAlias \n";
        $prevParamNumber = $paramCounter - 1;
        $confEqualityPhrase = ($paramCounter > 1 ? "AND lsl$paramCounter.service_lookup_value=lsl$prevParamNumber.service_lookup_value" : "");
        $lookupWhereClausePartialSql .= "$ANDorNothing $tableAlias.SERVICE_TYPE_ID=$serviceType AND $tableAlias.service_lookup_key_id = $lookupKey AND $tableAlias.service_lookup_key_value=$quotationOrNothing{$lookupValue}$quotationOrNothing $confEqualityPhrase \n";
    }

    /*
     *  gets the corresponding numeric key dedicated for this param type in our LogiServiceLookup table
     */
    private function getServicLookupKeyIdForParamKeyForLogiServiceLookupTable($paramKey)
    {

        $paramKeyArray = ["service_id" => 0, "subnet_mask" => 1];
        if (isset($paramKeyArray[$paramKey])) {
            return $paramKeyArray[$paramKey];
        }

        return null;
    }

    /*
    *  gets the corresponding type for IdKey (integer ,string etc )
    */
    private function getServicLookupKeyIdTypeForParamKeyIdForLogiServiceLookupTable($paramKeyId)
    {

        //$paramKeyIdType=[0=>"string",1=>"integer"];
        $paramKeyIdType = [0 => "integer", 1 => "integer"];
        if (isset($paramKeyIdType[$paramKeyId])) {
            return $paramKeyIdType[$paramKeyId];
        }

        return null;
    }


}