<?php
/** ajahHandler.php :
 *  Works as a facade to handlle various ajax requests fired from front end
 */

require_once("storage/JsonFileTable.php");
$jsonTable = new JsonFileTable("records/birthdays.txt");

$action = null;

if (isset($_POST['action'])) {
    $action = $_POST['action'];
}

// resolve request object from  POST data
$postdata = file_get_contents("php://input");
$request = json_decode($postdata);
$action = $request->action;

if ($action == null) {
    echo wrongJob();
    exit(0);
}

//handle request based on requested action
switch ($action) {
    case "save_as_json":
        //add currently calculated birth record in storage/records/birthdays.txt json file
        $newRecord = $request->birth_record;
        echo saveBirthRecord($newRecord, $jsonTable);
        break;
    case "get_users_birth_records":
        //retrieve already saved birth records from storage/records/birthdays.txt json file
        $records = $jsonTable->readRecords();
        $outData = ["status" => "success", "birth_records" => $records, "message" => ""];
        echo json_encode($outData);
        break;
    default:
        echo wrongAction();
        break;
}
/**
 * @return string
 */
function wrongAction()
{
    $outData = ["status" => "failure", "message" => "no correct action specified!"];
    $outDataInJson = json_encode($outData);
    return $outDataInJson;
}

/**
 * @return string  returns a json string with status attribute of success or failure ,message attribute of why it failed
 */
function saveBirthRecord($birthRecord, $jsonTable)
{

    try {
        $birthRecordAsArray = [
            $jsonTable->getRecordsCount(),
            $birthRecord->user_name,
            $birthRecord->birth_day,
            $birthRecord->elapsed,
        ];

        $jsonTable->addRecord($birthRecordAsArray);
        $outData = [
            "status" => "success",
            "message" => ""
        ];
    } catch (\Exception $e) {

        $message = "Cannot save birth record: " . $e->getMessage();
        $outData = [
            "status" => "failure",
            "message" => $message
        ];
    }

    $outDataInJson = json_encode($outData);
    return $outDataInJson;
}
