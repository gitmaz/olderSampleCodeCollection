<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 3/04/2016
 * Time: 6:25 AM
 */

use Orion\Controller\Service\Request;

//include constants
require_once('../../../../constants.php');
//include core functions
require_once('../../../../bootstrap.php');

$container = Request::getContainer();

$descriptor = "PP_ID=123456";
//$serviceSegmentsInfo=new ServiceSegmentsInfo();
$serviceSegmentsInfo = $container->get("inv.service_management.service_segments_info");
$serviceSegmentsInfo->setDescriptor($descriptor);
$serviceId = $serviceSegmentsInfo->getServiceId();

echo $serviceId;