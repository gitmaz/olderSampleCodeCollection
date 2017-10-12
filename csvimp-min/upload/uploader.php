<?php
/**
 * Created by PhpStorm.
 * User: maziar
 * Date: 29/09/2017
 * Time: 10:54 PM
 */

$fileName = $_FILES["uploadedFile"]["name"];
$fileContent = file_get_contents($_FILES['uploadedFile']['tmp_name']);
file_put_contents(__DIR__ . "/files/csv.txt", $fileContent);
echo $fileContent;