<?php

/**
 * Created by PhpStorm.
 * Copyright: maziar Navabi
 * Date: 6/08/2016
 * Time: 12:26 PM
 *
 * Utility class for saving instances of key values (as records in a database table) in plain json format
 * -the json will be saved as a plain text file
 * -the index of records will be preserved in the json file and used later for retrievals and updates
 *
 * sample usage:
 *
 * //insert      (first value of the record is the index)
 * $jsonTable=new JsonFileTable("table.txt");
 * $record=[0,1,1,0,1]; //first value is index of the records
 * $jsonTable->writeRecord($record);
 *
 * //update
 * $updateIndice=[1,3];
 * $updates=[1,9,10];
 * $jsonTable->overwriteRecord($updateIndice,$updates);
 *
 * //delete
 * $jsonTable=new JsonFileTable("table.txt");
 * $jsonTable->removeRecord(2);
 *
 * // select (find) a person named Joe Smith in users table
 * $jsonTable=new JsonFileTable("users.txt");
 * $record=$jsonTable->findWhere('name', 'Joe', 'surname', 'Smith');
 */
class JsonFileTable
{
    private $fileName = "";
    public $records;

    function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    function readRecord($rowId)
    {
        $record = [];
        $this->readRecords();
        $record = $this->records[$rowId];
        return $record;
    }

    /**
     * @return array of records each of which is an array with first element as id of the record
     */
    function readRecords()
    {
        $json = file_get_contents($this->fileName);
        $jsonDecoded = json_decode($json, true);
        $this->records = $jsonDecoded["data"];
        return $this->records;
    }

    /**
     * @param $record  an array with first element as id of the record
     */
    function writeRecord($record)
    {
        $rowId = $record[0];
        $this->readRecords();
        $this->records[$rowId] = $record;
        $json = json_encode(["data" => $this->records], JSON_PRETTY_PRINT);
        file_put_contents($this->fileName, $json);

    }

    /**
     * @param $record an array with first element as id of the record
     */
    function addRecord($record)
    {
        $this->writeRecord($record);
        return "added";
    }

    /**
     * @return int number of records in the file
     */
    function getRecordsCount()
    {
        $this->readRecords();
        return count($this->records);
    }

    /**
     * @param int $updateIndice index of record we want to update
     * @param array $updates array of records each of which is an array with first element as id of the record
     */
    function overwriteRecord($updateIndice, $updates)
    {

        //first column is always row id
        $rowId = $updates[0];
        $oldRecord = $this->readRecord($rowId);

        if ($oldRecord == null) {
            $oldRecord[0] = $rowId;
        }


        foreach ($updateIndice as $updateIndex) {
            $oldRecord[$updateIndex] = $updates[$updateIndex];

        }

        $this->writeRecord($oldRecord);

    }

    /**
     * @param int $rowId index of record we want to delete
     */
    function removeRecord($rowId)
    {
        $this->readRecords();
        unset($this->records[$rowId]);

        $json = json_encode(["data" => $this->records], JSON_PRETTY_PRINT);
        file_put_contents($this->fileName, $json);
        return "deleted";
    }

    function getNextAvailRowId()
    {
        $this->readRecords();

        $maxRowId = -1;
        foreach ($this->records as $record) {
            $maxRowId = max($maxRowId, $record[0]);
        }

        return $maxRowId;
    }

    function findWhere($key1, $value1, $key2 = null, $value2 = null)
    {
        $this->readRecords();
        foreach ($this->records as $record) {
            if (!isset($record[$key1])) {
                return null;
            }
            if ($record[$key1] == $value1) {
                if ($key2 != null) {
                    if (!isset($record[$key2])) {
                        return null;
                    }

                    if ($record[$key2] == $value2) {
                        return $record;
                    }
                } else {
                    return $record;
                }
            }
        }

        return null;
    }
}



