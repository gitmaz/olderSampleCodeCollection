<?php

  namespace App\Services;

  use App\WorkOrders;
  use App\WorkOperations;
  use App\Services\CsvImporter;

  /**
   *  This is real world usage for CSV importer which loads a work order list and its operations
   *   into WorkOrders and WorkOperations eloquent classes from a flat structure in found in csv,
   *   it automatically sets up the master detailrelationships of records by reusing a allocated id of master
   *   table record as the foreign key of detail table
   *
   *  It also makes use of pipes to apply pre-processing on csv field to form adaptive alues to be put in database
   *  fields
   *
   */
  class WorkOrderHelper {

    /**
     *  import csv file containing rows of orders and their corresponding operations to WorkOrder and WorkOperations eloquent entities
     * @param string $fullFileName full file name with path
     * @param bool $csvHasHeaderRow true if there is column titles as first row
     * @param bool $silent set to false if used in command line and echoing messages is required
     * @return mixed  returns array  ["success" => x, "details" => y ,"errorMessage" => z]  where x is true for success ,y is an html for process details and z is a string of what error if any
     */
    public function importCsv($fullFileName,$csvHasHeaderRow = true, $silent = true){


      if(!file_exists($fullFileName)){

        return "file does not exist";
      }

      date_default_timezone_set("Australia/Perth");

      // pipes for preprocessing csv values
      // pipe for WorkOperations item_name
      $workOperationItemNamePipe = function ($csvValue){

        $csvValue= str_replace("Â", "", $csvValue );
        return $csvValue;
      };

      // pipe for dates
      $datePipe = function ($csvValue){
        if (substr($csvValue, 3, 1) == " ") {
          $csvValue = substr($csvValue, 4, strlen($csvValue));
        }

        $csvValue = date("Y-m-d H:i:s", strtotime(str_replace("-17 ", "-2017 ", str_replace("/", "-", $csvValue))));

        return $csvValue;
      };


      //resource modifier matcher pipe
      $resourceModifierPipe = function($csvValue){
        preg_match("/([0-9])00%/", $csvValue, $matches);
        if (!isset($matches[1])) {
          $csvValue = 1;
        } else {
          $csvValue = $matches[1];
        }
        return $csvValue;
      };

      $estimatedTimePipe = function($csvValue){
        $csvValue=str_replace(" hr", "", str_replace(" hrs", "", $csvValue)) * 60;
        return $csvValue;
      };

      $randomHasherPipe = function(){
        return  rs(32);
      };

      $currentTimePipe = function(){
        return date("Y-m-d H:i:s");
      };

      $importer= new CsvImporter($fullFileName, ["WorkOrders"=>[   ["col_name" =>"order_id"  , "csv_index" => 2]
                                                                   ,["col_name" =>"order_code", "csv_index" => 4]
                                                                   ,["col_name" =>"order_desc", "csv_index" => 3]
                                                                   ,["col_name" =>"order_hash", "csv_pipe"  => $randomHasherPipe]
                                                                   ,["col_name" =>"project_id", "const"  =>165]
                                                                   ,["col_name" =>"created_by", "const"  =>3]
      ],
                                                 "WorkOperations"=>[    ["col_name" =>"unique_id" , "csv_index" => 1]
                                                                        ,["col_name" =>"order_id"  , "csv_index" => 2 ,"lookup_master_model" =>"WorkOrders"]
                                                                        ,["col_name" =>"sequence"  , "csv_index" => 5]
                                                                        ,["col_name" =>"item_name" , "csv_index" => 6, "csv_pipe" => $workOperationItemNamePipe]
                                                                        ,["col_name" =>"start_date", "csv_index" => 7, "csv_pipe" => $datePipe]
                                                                        ,["col_name" =>"end_date"  , "csv_index" => 8, "csv_pipe" => $datePipe]
                                                                        ,["col_name" =>"recipient" , "csv_index" => 15, "csv_pipe" => " to be defined later" ]
                                                                        ,["col_name" =>"resource_modifier", "csv_index" =>11, "csv_pipe" =>$resourceModifierPipe ]
                                                                        ,["col_name" =>"estimated_time"   , "csv_index" =>10, "csv_pipe" =>$estimatedTimePipe]
                                                                        ,["col_name" =>"operation_hash"   , "csv_pipe" =>$randomHasherPipe]
                                                                        ,["col_name" =>"created_by", "const" =>3]
                                                                        ,["col_name" =>"updated_at", "csv_pipe" =>$currentTimePipe]
                                                 ]
      ],$csvHasHeaderRow, $silent);

      // Finally do the import
      $importer->import();

      WorkOrders::updateOrderStartAndEndTimesFromOperations();

      // and get a report of applied process
      if($silent) {
        $details = $importer->getMessageBuffer();
        return ["success" => true, "details" => $details, "errorMessage" => null];

      } else {
        return "Import process completed";
      }



    }


  }