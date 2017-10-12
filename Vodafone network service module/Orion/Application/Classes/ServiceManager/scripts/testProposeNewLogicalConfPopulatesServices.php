<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 14/05/2016
 * Time: 1:15 PM
 */
use InventoryApi\LogicalConfiguration\Entity\LogicalConfiguration;
use InventoryApi\LogicalConfiguration\Service\LogicalConfigurationManager;
use InventoryApi\ServiceManagement\Service;
use Orion\Controller\Service\Request;


//include constants
require_once('../../../constants.php');
//include core functions
require_once('../../../bootstrap.php');

//1) get network configuration (physical and logical)

$container = Request::getContainer();

$modelStructureXsd = \file_get_contents('../Samples/SampleXML/serviceStructure.xsd');
$renderStructureXml = \file_get_contents('../Samples/SampleXML/serviceStructure.xml');
$valueXml = \file_get_contents('../Samples/SampleXML/serviceValue.xml');

$logicalConfiguration = new LogicalConfiguration();
$logicalConfiguration->setLogConfObjectId(11)
    ->setLogConfPhysicalId(22)
    ->setRenderStructureXml($renderStructureXml)
    ->setValueXml($valueXml);


$configurationManager = $container->get("inv.log_conf.log_conf_manager");

$logEntityRepo = $container->get("inv.log_conf.logical_entity_repository");
$logEntity = $logEntityRepo->find(3);

Doctrine\Common\Util\Debug::dump($logEntity);

$siteRepository = $container->get("inv.site.inv_site_repository");
$parentSite = $siteRepository->find(23471);

Doctrine\Common\Util\Debug::dump($parentSite);

$sourceRepo = $container->get("inv.event.source_repository");
$source = $sourceRepo->getChangedByDesigner();

Doctrine\Common\Util\Debug::dump($source);

$values = [];

try {
    $result = $configurationManager->proposeNewLogicalConfiguration($values, $logEntity, $parentSite, 1, $source, 1);

    Doctrine\Common\Util\Debug::dump($result);
} catch (Expection $e) {
    var_dump($e);
}