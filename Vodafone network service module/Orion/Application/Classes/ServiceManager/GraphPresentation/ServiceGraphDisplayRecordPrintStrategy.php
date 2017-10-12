<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:45 AM
 */
namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayRecordPrintStrategy
{
    private $route;

    function __construct()
    {

    }

    /* prints out $route as an array of subsequent routeNodes in a route
     *  @VAR array $serviceSegments example: [["SITE1"=>"mosman","SITE2"=>"mosman","EQUIPMENT_NAME1"=>"B","EQUIPMENT_NAME2"=>"C","EQUIPMENT_ID1"=>"B","EQUIPMENT_ID2"=>"C","CONNECTION_ID"=>"con2","INTERFACE_OBJECT_ID1"=>"b2","INTERFACE_OBJECT_ID2"=>"c1"],...]
     */
    public function display($serviceSegments)
    {

        echo "\n\n\nGRAPH PRINT \n\n";

        $nodeCount = 0;

        foreach ($serviceSegments as $serviceSegment) {
            $serviceSegmentStr = "SERVICE => {$serviceSegment["SERVICE_NAME"]}\n,
                                 SITE1 => {$serviceSegment["SITE1"]}\n,
                                 EQUIPMENT_NAME1 =>{$serviceSegment["EQUIPMENT_NAME1"]}\n,
                                 EQUIPMENT_ID1 => {$serviceSegment["EQUIPMENT_ID1"]}\n,
                                  CONF_ID1 =>{$serviceSegment["LOGI_CONF1_ID"]}\n,
                                 CARD_ID1 => {$serviceSegment["CARD_ID1"]}\n,
                                 INTERFACE_OBJECT_ID1 => {$serviceSegment["INTERFACE_OBJECT_ID1"]}\n,
                                 CONNECTION_ID => {$serviceSegment["CONNECTION_ID"]}\n,
                                 INTERFACE_OBJECT_ID2 =>{$serviceSegment["INTERFACE_OBJECT_ID2"]}\n,
                                 CARD_ID2=>{$serviceSegment["CARD_ID2"]}\n,
                                 CONF_ID2 =>{$serviceSegment["LOGI_CONF2_ID"]}\n,
                                 EQUIPMENT_ID2 => {$serviceSegment["EQUIPMENT_ID2"]}\n,
                                 EQUIPMENT_NAME2 => {$serviceSegment["EQUIPMENT_NAME2"]}\n,
                                 SITE2 => {$serviceSegment["SITE2"]}\n\n\n";
            echo $serviceSegmentStr;
        }
        echo "\n";
    }
}