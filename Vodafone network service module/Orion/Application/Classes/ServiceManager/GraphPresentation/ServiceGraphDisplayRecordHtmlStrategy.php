<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:45 AM
 */
namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayRecordHtmlStrategy
{

    private $route;

    function __construct()
    {

    }

    /* prints out $route as an array of subsequent routeNodes in a route
     *  @VAR array $serviceSegments example: [["SITE1"=>"mosman","SITE2"=>"mosman","EQUIPMENT_NAME1"=>"B","EQUIPMENT_NAME2"=>"C","EQUIPMENT_ID1"=>"B","EQUIPMENT_ID2"=>"C","CONNECTION_ID"=>"con2","INTERFACE_OBJECT_ID1"=>"b2","INTERFACE_OBJECT_ID2"=>"c1"],...]
     */
    public function display($serviceSegments, $shouldEcho = true)
    {

        // echo "<br><div><b>SEGMENT PRINTOUT</b></div><br>";

        $nodeCount = 0;

        $this->html = "";
        foreach ($serviceSegments as $serviceSegment) {
            $serviceSegmentStr = "<table class='table-bordered table-striped thick-border-table'><tr ><td class='caption_span highlighted_row'>SERVICE</td><td class='value_span highlighted_row'>{$serviceSegment["SERVICE_NAME"]}</td></tr>
                                 <tr><td class='caption_span'>SITE1</td><td class='value_span'>{$serviceSegment["SITE1"]}</td></tr>
                                 <tr><td class='caption_span'>CONF_ID1</td><td class='value_span'>{$serviceSegment["LOGI_CONF1_ID"]}</td></tr>
                                 <tr><td class='caption_span'>EQUIPMENT_NAME1</td><td class='value_span'>{$serviceSegment["EQUIPMENT_NAME1"]}</td></tr>
                                 <tr><td class='caption_span'>EQUIPMENT_ID1</td><td class='value_span'>{$serviceSegment["EQUIPMENT_ID1"]}</td></tr>
                                 <tr><td class='caption_span'>CARD_ID1</td><td class='value_span'>{$serviceSegment["CARD_ID1"]}</td></tr>
                                 <tr><td class='caption_span'>INTERFACE_OBJECT_ID1</td><td class='value_span'>{$serviceSegment["INTERFACE_OBJECT_ID1"]}</td></tr>
                                 <tr><td class='caption_span'>CONNECTION_ID</td><td class='value_span'>{$serviceSegment["CONNECTION_ID"]}</td></tr>
                                 <tr><td class='caption_span'>INTERFACE_OBJECT_ID2</td><td class='value_span'>{$serviceSegment["INTERFACE_OBJECT_ID2"]}</td></tr>
                                 <tr><td class='caption_span'>CARD_ID2</td><td class='value_span'>{$serviceSegment["CARD_ID2"]}</td></tr>
                                 <tr><td class='caption_span'>CONF_ID2</td><td class='value_span'>{$serviceSegment["LOGI_CONF2_ID"]}</td></tr>
                                 <tr><td class='caption_span'>EQUIPMENT_ID2</td><td class='value_span'>{$serviceSegment["EQUIPMENT_ID2"]}</td></tr>
                                 <tr><td class='caption_span'>EQUIPMENT_NAME2</td><td class='value_span'>{$serviceSegment["EQUIPMENT_NAME2"]}</td></tr>
                                 <tr><td class='caption_span'>SITE2</td><td class='value_span'>{$serviceSegment["SITE2"]}</td></tr></table>";
            $this->html .= $serviceSegmentStr;
            $this->html .= "<br><br>";
        }

        if ($shouldEcho) {
            echo $this->html;
        } else {
            return $this->html;
        }
    }
}