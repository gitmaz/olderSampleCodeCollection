<?php
require_once("../../storage/JsonFileTable.php");
$jsonTable = new JsonFileTable("../../records/birthdays.txt");

$records = $jsonTable->readRecords();
echo asHtmlTable(["author", "editor", "narrator"], $records);

function asHtmlTable($columnNames, $records)
{

    $columnCount = count($columnNames);

    $html = "";

    $html .= "<div style='width:1200px'>\n
               \t<table id='records_table' class='thick-border-table'>\n";
    $html .= "\t\t<thead >\n
                \t\t\t<tr class='highlighted_row'>\n";
    foreach ($columnNames as $columnName) {
        $html .= "\t\t\t\t<th>$columnName</th>\n";
    }
    $html .= "\t\t\t</tr>
                \t\t</thead>\t";
    foreach ($records as $index => $record) {
        $tableRow =
            "\n\t\t\t\t<tr>\n";
        for ($i = 1; $i <= $columnCount; $i++) {
            $tableRow .= "\t\t\t\t\t<td>{$record[$i]}</td>\n";
        }
        $tableRow .=
            "\t\t\t\t</tr>";
        $html .= $tableRow;
    }

    $html .= "\n\t</table>
            </div>";

    return $html;
}


