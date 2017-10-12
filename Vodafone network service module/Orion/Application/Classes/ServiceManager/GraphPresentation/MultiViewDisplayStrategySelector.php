<?php
/**
 * Created by PhpStorm.
 * User: maziar navabi
 * Date: 12/02/2016
 * Time: 9:44 AM
 */

/*
 * singleton to switch between different ways of displaying multiple page information
 *  these can be switched to:
 *   display pages via jqueryUI tabs ,
 *
 */
namespace Orion\ServiceManager\GraphPresentation;

class MultiViewDisplayStrategySelector
{
    public static $strategies = ["jquery_tabs" => '\Orion\ServiceManager\GraphPresentation\MultiViewDisplayJqHtmlTabsStrategy',
    ];

    static function getStrategy($method)
    {

        $strategyClass = self::$strategies[$method];
        return new $strategyClass();
    }


}