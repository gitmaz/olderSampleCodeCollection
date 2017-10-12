<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 14/05/2016
 * Time: 9:45 AM
 */

namespace InventoryApi\ServiceManagement\Service;

class Loggit
{
    function log($msg)
    {
        file_put_contents(TEMP_PATH . '/service_management_log.txt', date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        file_put_contents(TEMP_PATH . '/service_management_log.txt', "$msg \n", FILE_APPEND);
    }
}