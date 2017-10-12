<?php

/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:44 AM
 */

/*
 * singleton to switch between different ways of displaying routes
 *  these can be switched to:
 *   display graphs via plain print out
 *   display graphs via html
 *
 */
namespace Orion\ServiceManager\GraphPresentation;

class ServiceGraphDisplayStrategySelector
{
    public static $strategies = ["record_print" => "Orion\\ServiceManager\\GraphPresentation\\ServiceGraphDisplayRecordPrintStrategy",
        "record_html" => "Orion\\ServiceManager\\GraphPresentation\\ServiceGraphDisplayRecordHtmlStrategy",
        "visual_print" => "Orion\\ServiceManager\\GraphPresentation\\ServiceGraphDisplayPrintWithPositioningStrategy",
        "visual_html" => "Orion\\ServiceManager\\GraphPresentation\\ServiceGraphDisplayHtmlWithPositioningStrategy",
        "visual_html_for_save" => "Orion\\ServiceManager\\GraphPresentation\\ServiceGraphDisplayHtmlWithPositioningStrategyForSave",
        "table_html" => "Orion\\ServiceManager\\GraphPresentation\\ServiceGraphDisplayTableHtmlStrategy",
        "visual_mxgraph" => "Orion\\ServiceManager\\GraphPresentation\\ServiceGraphDisplayMXGraphStrategy"
    ];

    static function getStrategy($method)
    {

        $strategyClass = self::$strategies[$method];
        return new $strategyClass();
    }

}