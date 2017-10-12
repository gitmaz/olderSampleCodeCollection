<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CsvMapping extends Model
{
    function getExistingMappingNames()
    {
        $csvMapping = new CsvMapping();
        $mappingsBrief = $csvMapping->select(["name as label", "id as value"])
            ->get()->toArray();

        return $mappingsBrief;

    }

    function getMappingArrayStrById($mappingId)
    {
        $csvMapping = new CsvMapping();
        $mapping = $csvMapping->where("id", $mappingId)
            ->select("mapping", "id")
            ->first();
        if ($mapping != null) {
            return json_encode(["mappingStr" => $mapping->mapping]);
        }

        return null;
    }

    function saveMappingArrayStr($mappingId, $newMappingName, $mappingArrayStr)
    {
        $csvMapping = new CsvMapping();
        if ($mappingId != -1) {
            $mapping = $csvMapping->where("id", $mappingId)
                ->first();
            $mapping->mapping = $mappingArrayStr;
            $mapping->save();
        } else {
            $csvMapping->mapping = $mappingArrayStr;
            $csvMapping->name = $newMappingName;
            $csvMapping->save();
        }
        return json_encode(["result" => true, "id" => $csvMapping->id]);
    }

    function deleteMapping($mappingId)
    {
        $mapping = CsvMapping::where("id", $mappingId)
            ->first();
        $mapping->delete();
        return json_encode(["result" => true, "id" => $mappingId]);
    }
}

