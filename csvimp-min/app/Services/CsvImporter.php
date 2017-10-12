<?php
/**
 *  @Copyright Maziar Navabi all rights reserved
 *  This is a utility class which loads a csv file to configurable columns of multiple tables, given
 *  their Eloquent model names and the mapping of destination columns and their corresponding column index in CSV file
 *  It is capable of constructing a master detail relationship of two entities from records defined in a combined(flattened) fashion in a csv file
 */

namespace App\Services;

class CsvImporter
{
    private $sourceFileName;
    private $dstModelColumnAssignments;
    private $fileContentRows;
    private $shouldIgnoreHeaderRow;
    private $modelAffectedKeys;
    private $modelExternalToInternalKeyMap;
    private $masterModelName;
    private $messageBuffer;
    private $hasFatalError = false;

    /**
     *  constructs an importer object by loading the provided fileName and mappings array.this object will do the importing by calling its ->import() function
     * @param string $sourceFileName
     * @param array $dstModelColumnAssignments
     * <pre>
     *      it contains model names and their mapping.
     *      it lists model names as keys and model column name vs index in csv as values. first mapping is always key column.
     *       for ex. if we want to map first and third column of csv to WorkOrders.id and WorkOrders.col2  and first and second columns to model2.id and model3.col3, we use:
     *                                                                                     [ "WorkOrders" => [["col_name" => "id, "csv_index" =>1] , ["col_name" => "col2", "csv_index" =>3]],
     *                                                                                       "model2"     => [["col_name" => "id, "csv_index" =>1] , ["col_name" => "col3", "csv_index" =>2]] ];
     *
     *  if we want to pre process the col3 from model 2 with a pipe function $pipeProcess we first define the $pipeProcess closure :
     *    $pipeProcess= function($csvValue){
     *        $csvValue*=2; //as an example
     *        return $csvValue;
     *    };
     *
     *    then we use :
     *
     *    [ "WorkOrders" => [["col_name" => "id, "cdv_index" =>1] , ["col_name" => "col2", "csv_index" =>3]],
     *      "model2"     => [["col_name" => "id, "cdv_index" =>1] , ["col_name" => "col3", "csv_index" =>2 ,"csv_pipe" => $pipeProcess]] ];
     *
     *  if we want to set the column to a constant value x , dont supply  "csv_index", instead add "const" => x to the mapping
     *
     *    [ "WorkOrders" => [["col_name" => "id, "csv_index" =>1] , ["col_name" => "col2", "const" =>564]],
     *
     *  if a table is detail of another one, this master detail relation can be setup by adding  "lookup_master_model" => MasterModelName phrase in the place of the foreign key for example:
     *
     *    [ "WorkOrders" => [["col_name" =>"order_id"  , "csv_index" => 2] , [...] , ... , [...]],
     *      "WorkOperations"=>[["col_name" =>"unique_id" , "csv_index" => 1],["col_name" =>"order_id"  , "csv_index" => 2 ,"lookup_master_table" =>"WorkOrders"] ,...,[...]]
     *
     *     makes column order_id of WorkOperations a foreign key column with real key valuse as primary key of WorkOrders
     *
     *  Note: to temporary bypass a column set its csv_index to -1
     * </pre>
     * @param bool $shouldIgnoreHeaderRow default true, put this to false if csv file does not have a column headers row
     * @param bool $silent default false, put to true to get rid of $this->echoOrBuffering and directly echo to stdout
     * @return void
     */
    function __construct($sourceFileName, $dstModelColumnAssignments, $shouldIgnoreHeaderRow = true, $silent = false)
    {

        $this->sourceFileName = $sourceFileName;
        $this->dstModelColumnAssignments = $dstModelColumnAssignments;
        $this->shouldIgnoreHeaderRow = $shouldIgnoreHeaderRow;
        $this->silent = $silent;

        // fetch file contents
        try {

            $this->parseColumnValues($sourceFileName);
        } catch (Exception $ex) {
            $this->echoOrBuffer("error loading $sourceFileName : " . $ex . message . "\n");
        }

    }

    /**
     *  imports csv file to its configured destination models as described in $this->dstModelColumnAssignments mappings
     * @return void
     */
    public function import()
    {

        if ($this->hasFatalError) {

            $this->echoOrBuffer("Finished without importing due to above error\n");
            return;
        }

        $totalCsvRowCount = count($this->fileContentRows);
        // iterate through each destination table
        $this->echoOrBuffer("\n\n-----------------------------------Import started ---------------------------------------\n\n");
        $this->echoOrBuffer("\npath: {$this->sourceFileName}\n");
        $this->echoOrBuffer("row count: $totalCsvRowCount \n");
        $this->echoOrBuffer("models:" . implode(",", array_keys($this->dstModelColumnAssignments)) . "\n\n");


        //pre processing lookup_master_model directive tags
        $this->preprocessLookupMasterModelDirectives();


        foreach ($this->dstModelColumnAssignments as $dstModelName => $dstModelColumns) {
            $this->mismatchCount[$dstModelName] = 0;
            $this->echoOrBuffer("----- $dstModelName ------------------\n\n");
            $columnTypesInDb = $this->getModelColumnTypes($dstModelName);

            // find this table key column and other columns info
            $modelKeyColumnName = null;
            $modelKeyColumnIndex = null;
            $modelNonKeyColumnNames = [];
            $modelNonKeyColumnPipes = [];
            $modelNonKeyColumnIndexesInCsv = [];

            $this->getColumnMappings($dstModelColumns,
                                     $modelKeyColumnName,
                                     $modelKeyColumnIndex,
                                     $modelNonKeyColumnNames,
                                     $modelNonKeyColumnIndexesInCsv ,
                                     $modelNonKeyColumnPipes);

            // update values corresponding to the destination table into this table
            $counter = 0;
            foreach ($this->fileContentRows as $csvRowInd => $fileContentRow) {

                //select columns from csv which relates to this table
                $modelKeyColumnValue = trim($fileContentRow[$modelKeyColumnIndex - 1]);

                // for later distinct key count
                $this->modelAffectedKeys[$dstModelName][$modelKeyColumnValue] = $modelKeyColumnValue;
                $attributesToSet = [];

                //fetch value from csv and type match with columns in db then apply Pipe
                foreach ($modelNonKeyColumnIndexesInCsv as $recordInd => $modelNonKeyColumnIndexInCsv) {

                    if ($modelNonKeyColumnIndexInCsv == -1) { //a means of temporarily bypassing a column by setting its index to -1
                        continue;
                    }

                    $hasMismatch = false;
                    $this->columnTypeMatchAndApplyPipe($fileContentRow,
                                                        $recordInd,
                                                        $dstModelName,
                                                        $modelNonKeyColumnIndexInCsv,
                                                        $modelNonKeyColumnNames,
                                                        $modelNonKeyColumnPipes,
                                                        $columnTypesInDb,
                                                        $columnNameInDb,
                                                        $columnTypeInDb,
                                                        $hasMismatch,
                                                        $pipeSuccessful,
                                                        $columnValueFromCsv,
                                                        $attributesToSet);

                    if ($hasMismatch) {
                        break;
                    }

                }

                try {

                    if ($hasMismatch) {
                        $problem = ($pipeSuccessful ? "expects $columnTypeInDb" : "not found");
                        $this->echoOrBuffer("\nrejected row# {$csvRowInd}, $columnNameInDb $problem: " . json_encode($attributesToSet) . "\n");
                        continue;
                    }

                    $attributesToSet[$modelKeyColumnName] = $modelKeyColumnValue;// fix for problem of update not working ,it requires to have the update key in $attributesToSet as well
                    $res = call_user_func("App\\$dstModelName::updateOrCreate", [$modelKeyColumnName => $modelKeyColumnValue], $attributesToSet);

                    // for later foreign key resoloution (a "lookup_master_model" present)
                    $this->modelExternalToInternalKeyMap[$dstModelName][$modelKeyColumnValue] = $res->id;
                    $counter++;

                } catch (Exception $ex) {
                    $message = $ex->getMessage();
                    $this->echoOrBuffer("error while importing csv file. $counter/$totalCsvRowCount records imported. message: ($message) .\n");
                }
            }

            $rejected = $totalCsvRowCount - $counter;
            $rejectedStr = ($rejected ? "($rejected rejected)" : "");
            $affectedCount = count($this->modelAffectedKeys[$dstModelName]);
            $affectedStr = "total {$affectedCount} rows affected in table";
            $this->echoOrBuffer("\n\nimported $counter/$totalCsvRowCount records{$rejectedStr}, $affectedStr.\n\n");
        }

        $this->echoOrBuffer("\n-----------------------------------Import finished ---------------------------------------\n\n");
    }

    /**
     *  prepares master detail relationships if directed by lookup_master_model tag in mapping defenitions
     */
    private function preprocessLookupMasterModelDirectives(){

        foreach ($this->dstModelColumnAssignments as $dstModelName => $dstModelColumns) {
            $res = call_user_func("App\\$dstModelName::truncate");

            foreach ($dstModelColumns as $recordInd => $dstModelColumn) {

                if (isset($dstModelColumn["lookup_master_model"])) {
                    $columnNameInDb = $dstModelColumn["col_name"];
                    $this->masterModelName[$dstModelName][$columnNameInDb] = $dstModelColumn["lookup_master_model"];
                }
            }
        }
    }


    /*
     *  figures out key column and non-key column names and their corresponding column index in csv from mapping
     * @param array $dstModelColumns
     * @param &string $modelKeyColumnName
     * @param &integer $modelKeyColumnIndex
     * @param &array $modelNonKeyColumnNames
     * @param &array $modelNonKeyColumnIndexesInCsv
     * @param &function $modelNonKeyColumnPipes
     * @return void
     */
    private function getColumnMappings($dstModelColumns,
                                       &$modelKeyColumnName,
                                       &$modelKeyColumnIndex,
                                       &$modelNonKeyColumnNames,
                                       &$modelNonKeyColumnIndexesInCsv ,
                                       &$modelNonKeyColumnPipes){

        foreach ($dstModelColumns as $recordInd => $dstModelColumn) {
            if ($recordInd == 0) {

                $modelKeyColumnName = $dstModelColumn["col_name"];
                $modelKeyColumnIndex = $dstModelColumn["csv_index"];
            } else {
                $modelNonKeyColumnNames[] = $dstModelColumn["col_name"];
                $modelNonKeyColumnIndexesInCsv[] = (isset($dstModelColumn["csv_index"]) ? $dstModelColumn["csv_index"] : null);

                //constants
                //get the constant value to be used for this column if any
                if (isset($dstModelColumn["const"])) {
                    $const = $dstModelColumn["const"];
                    $modelNonKeyColumnPipes[] = function ($csvValue) use ($const) { // function as our pipe for constants
                        return $const;
                    };
                } else {

                    // pipes
                    //get the defined pipe by which csv value should be passed through
                    if (isset($dstModelColumn["csv_pipe"])) {
                        $modelNonKeyColumnPipes[] = $dstModelColumn["csv_pipe"];
                    } else {
                        $modelNonKeyColumnPipes[] = null;
                    }
                }

            }
        }
    }

    /**
     *  finds any mismatch of values in csv and their corresponding types in db,
     *   also applies pipe if defined on a column on current row column value (and puts it back to $columnValueFromCsv)
     * @param array $fileContentRow
     * @param integer $recordInd
     * @param string $dstModelName
     * @param integer $modelNonKeyColumnIndexInCsv
     * @param array $modelNonKeyColumnNames
     * @param array $columnTypesInDb
     * @param array $modelNonKeyColumnPipes
     * @param &string $columnNameInDb
     * @param &string $columnTypeInDb
     * @param &bool $hasMismatch
     * @param &bool $pipeSuccessful
     * @param &mix $columnValueFromCsv
     * @param &array $attributesToSet
     */
    private function columnTypeMatchAndApplyPipe($fileContentRow,
                                                 $recordInd,
                                                 $dstModelName,
                                                 $modelNonKeyColumnIndexInCsv,
                                                 $modelNonKeyColumnNames,
                                                 $columnTypesInDb,
                                                 $modelNonKeyColumnPipes,
                                                 &$columnNameInDb,
                                                 &$columnTypeInDb,
                                                 &$hasMismatch,
                                                 &$pipeSuccessful,
                                                 &$columnValueFromCsv,
                                                 &$attributesToSet){

        $columnNameInDb = $modelNonKeyColumnNames[$recordInd];

        if ($modelNonKeyColumnIndexInCsv == null) { //if index in csv not defined
            $columnValueFromCsv = null;
        } else {
            $columnValueFromCsv = trim($fileContentRow[$modelNonKeyColumnIndexInCsv - 1]);
        }

        $pipeSuccessful = true;
        //apply pipe before type matching
        if ($modelNonKeyColumnPipes[$recordInd] == null) {

            // if we have a foreign key resolution instruction (a "lookup_master_model" present ) , lookit up
            if (isset($this->masterModelName[$dstModelName][$columnNameInDb])) {
                $masterModelName = $this->masterModelName[$dstModelName][$columnNameInDb];
                // look it up in master table to find true (internally used) key
                $columnValueFromCsv = $this->modelExternalToInternalKeyMap[$masterModelName][$columnValueFromCsv];
            }

            $pipedValue = null;
        } else { // if a pipe is set for column

            // apply the pipe by which csv value should be passed through
            $pipedValue = $modelNonKeyColumnPipes[$recordInd]($columnValueFromCsv);
            if (is_array($pipedValue)) { // if return value is an array, pipe was not successfull
                $pipeSuccessful = false;
            } else {
                $columnValueFromCsv = $pipedValue;
            }

        }

        //type matching
        $columnTypeInDb = $columnTypesInDb[$columnNameInDb];

        $this->findCsvVersusColumnTypeMismatch($columnTypeInDb, $columnValueFromCsv, $dstModelName, $pipeSuccessful, $hasMismatch);
        $attributesToSet[$modelNonKeyColumnNames[$recordInd]] = $columnValueFromCsv;

    }

    /**
     * check type of value from csv to its corresponding type in db and returns false if mismatched (in $hasMismatch)
     * @param string $columnTypeInDb
     * @param mix $columnValueFromCsv
     * @param string $dstModelName
     * @param bool $pipeSuccessful
     * @param &bool $hasMismatch
     */
    private function findCsvVersusColumnTypeMismatch($columnTypeInDb,
                                                     $columnValueFromCsv,
                                                     $dstModelName,
                                                     $pipeSuccessful,
                                                     &$hasMismatch){
        switch ($columnTypeInDb) {
            case "integer":

                $strVal = "$columnValueFromCsv";
                if (!ctype_digit($strVal)) {
                    $hasMismatch = true;
                    $this->mismatchCount[$dstModelName]++;
                    break;
                } elseif (!$pipeSuccessful) {
                    $hasMismatch = true;
                    $this->mismatchCount[$dstModelName]++;
                    break;
                }
                break;
            case "text":
                if (false) { //we accept all type characters here
                    $hasMismatch = true;
                    $this->mismatchCount[$dstModelName]++;
                    break;
                } elseif (!$pipeSuccessful) {
                    $hasMismatch = true;
                    $this->mismatchCount[$dstModelName]++;
                    break;
                }
                break;
        }

    }

    /** gets the number of records which have mismatch values with their corresponding column types in db
     * @return string comma separated
     */
    private function getMismatchCount()
    {
        return implode(",", $this->mismatchCount);
    }



    /**
     *  helper to parses comma separated values in file $sourceFileName to array of columns
     * @param string $sourceFileName path to the file
     * @return void
     */
    private function parseColumnValues($sourceFileName)
    {
        $fileRawContentRows = [];

        //print_r($sourceFileName);
        $orderfp = fopen($sourceFileName, "r");
        if ($orderfp) {
            while (!feof($orderfp)) {
                $line = fgetcsv($orderfp);
                if ($line === false) { //bypass empty lines
                    continue;
                }
                $fileRawContentRows[] = $line;
            }
            fclose($orderfp);
        } else {
            $this->echoOrBuffer("Error happend wile opening csv file, please check if the path is correct.");
            $this->hasFatalError = true;
            return;
        }

        if ($this->shouldIgnoreHeaderRow) {
            array_shift($fileRawContentRows);
        }
        $this->fileContentRows = $fileRawContentRows;

    }

    /**
     * gets the column types a model has in database table
     * @param string $modelName
     * @return array  key value pairs with key as column names and value as column type
     */
    private function getModelColumnTypes($modelName)
    {
        $className = "App\\$modelName";
        $model = new $className;
        $builder = $model->getConnection()->getSchemaBuilder();
        $modelTable = $model->getTable();
        $columns = $builder->getConnection()->select("SHOW COLUMNS FROM $modelTable");
        $columnTypes = [];
        $thisColuntType = "";

        foreach ($columns as $column) {
            if (strpos($column->Type, "int(") !== false) {
                $thisColuntType = "integer";
            } elseif (strpos($column->Type, "varchar(") !== false) {
                $thisColuntType = "text";
            } elseif (strpos($column->Type, "text") !== false) {
                $thisColuntType = "text";
            } else {
                $thisColuntType = $column->Type;
            }
            $columnTypes[$column->Field] = $thisColuntType;
        }

        return $columnTypes;
    }

    /**
     * decides whether to echo out the message or buffer it in case of silent operation, getMessageBuffer is used to fetch the buffered messages
     * @param string $message
     * @param void
     */
    private function echoOrBuffer($message)
    {
        if (!$this->silent) {
            echo $message;
        } else {
            $this->messageBuffer[] = $message;
        }
    }

    /**
     * gets the buffered messages when $silent is passed as true to constructor
     * @param string $breakStr default "<br>" string to used to separate the lines visually ( use "<br>" or "\n" in web or cli)
     * @return string
     */
    function getMessageBuffer($breakStr = "<br>")
    {
        return implode($breakStr, $this->messageBuffer);
    }

    /**
     *  gets the distinct values of a column in csv
     * @param int $columnIndex index of column in csv file
     * @return array of distinct values in csv of that specific column
     */
    function getDistinctColumnValuesInCsv($columnIndex)
    {
        $distinctVals = [];
        $columnIndex -= 1;// 0 based
        foreach ($this->fileContentRows as $csvRowInd => $fileContentRow) {
            $colValue = $fileContentRow[$columnIndex];
            $distinctVals[$colValue] = $colValue;
        }

        return $distinctVals;
    }

    /**
     *  sets the pipe for specific mapping after constructor is called and before import is called
     * @param string $modelName
     * @param int $mappingRowIndex
     * @param closure $pipeProcess
     * @return void
     */
    function assignPipe($modelName, $mappingRowIndex, $pipeProcess)
    {
        $this->dstModelColumnAssignments[$modelName][$mappingRowIndex]["csv_pipe"] = $pipeProcess;
    }

}