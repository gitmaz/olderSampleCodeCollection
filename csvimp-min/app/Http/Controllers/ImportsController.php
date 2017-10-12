<?php
/**
 * Created by PhpStorm.
 * User: maziar
 * Date: 27/12/2016
 * Time: 3:28 PM
 */

namespace App\Http\Controllers;
define("UPLOADS_DIR", __DIR__ . "/../../../upload/files");
use Illuminate\Http\Request;
use App\Services\CsvImporter;
use App\CsvMapping;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Country;
use App\City;


class ImportsController extends Controller
{

    public function __construct()
    {
    }


    /**
     * facade to handle importing of different data types (csv implemented)
     */
    public function handle(Request $request, $dataType, $action)
    {


        if ($dataType == "csv") {
            switch ($action) {
                case "show_importer":
                    return view('csv_import');
                    break;

                case "upload_file":
                    $file = $request->file('uploadedFile');
                    //Display File Name
                    return 'File Name: ' . $file->getClientOriginalName();
                    break;

                case "ajax_get_existing_mapping_names":
                    return $this->getExistingMappingNames();
                    break;

                case "ajax_get_mapping_array_str_by_id":
                    $mappingId = $request->input("mappingId");
                    return $this->getMappingArrayStrById($mappingId);
                    break;

                case "ajax_save_mapping_array_str":
                    $mappingId = $request->input("mappingId");

                    $newMappingName = $request->input("newMappingName");
                    $mappingArrayStr = $request->input("mappingArrayStr");
                    return $this->saveMappingArrayStr($mappingId, $newMappingName, $mappingArrayStr);
                    break;

                case  "ajax_delete_mapping":
                    $mappingId = $request->input("mappingId");
                    return $this->deleteMapping($mappingId);
                    break;


                case "ajax_import_csv":
                    $mappingArrayStr = $request->input("mappingArrayStr");
                    //$csvFileName = $request->input("csvFileName");


                    return $this->importCsv($mappingArrayStr);
                    break;

                case "ajax_fetch_table_data":
                    $modelName = $request->input("modelName");
                    return $this->fetchTableData($modelName);
                    break;

                case "ajax_recognise_model_names_in_mapping":
                    $mappingArrayStr = $request->input("mappingArrayStr");
                    return $this->recogniseModelNamesInMapping($mappingArrayStr);
                    break;

            }
        }
        // implement other data type processing , here, which can pop up in the future.

        return null;
    }

    private function importCsv($mappingArrayStr)
    {
        //todo: only allow $mappingArrayStr to be a multidimensional array with proper format
        //warning: eval is a security risk unless above is implemented
        $mappingArray = "";
        eval('$mappingArray=' . $mappingArrayStr . ';');
        $result = [];
        if (is_array($mappingArray)) {

            $importer = new CsvImporter(UPLOADS_DIR . "/csv.txt", $mappingArray);

            $res = $importer->import();
            return $res;
        } else {
            return json_encode(false);
        }
    }

    private function getExistingMappingNames()
    {
        $csvMapping = new CsvMapping();
        return $csvMapping->getExistingMappingNames();
    }

    private function getMappingArrayStrById($mappingId)
    {
        $csvMapping = new CsvMapping();
        return $csvMapping->getMappingArrayStrById($mappingId);
    }

    private function saveMappingArrayStr($mappingId, $newMappingName, $mappingArrayStr)
    {
        $csvMapping = new CsvMapping();
        return $csvMapping->saveMappingArrayStr($mappingId, $newMappingName, $mappingArrayStr);
    }

    private function deleteMapping($mappingId)
    {
        $csvMapping = new CsvMapping();
        return $csvMapping->deleteMapping($mappingId);

    }

    private function fetchTableData($modelName)
    {
        //todo: blacklist forbiden models for security

        $modelName = "App\\$modelName";
        $modelObject = new $modelName();
        $res = $modelObject->fetchTableRecords();
        return $res;

    }

    private function recogniseModelNamesInMapping($mappingArrayStr)
    {
        $mappingArray = "";
        eval('$mappingArray=' . $mappingArrayStr . ';');
        $result = [];
        if (is_array($mappingArray)) {
            $modelNames = array_keys($mappingArray);

            foreach ($modelNames as $modelName) {
                $modelFullName = "App\\$modelName";
                $model = new $modelFullName();
                $tableName = $model->getTable();
                $result[] = ["label" => ucfirst($tableName), "value" => $modelName];
            }
            return json_encode($result);

        } else {
            return json_encode(false);
        }
    }


}
