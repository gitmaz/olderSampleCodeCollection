<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 22/04/2016
 * Time: 10:03 AM
 */
//todo: move this to test folder for unit testing

use Orion\Controller\Service\Request;
//include constants
require_once('../../../constants.php');
//include core functions
require_once('../../../bootstrap.php');

//todo :This is put here for later complete resoloution of interface ids instead of interface types for
//all existing patch panels and putting them here

$container = Request::getContainer();

/* @var $netDataProvider NetworkSetupFromInventory */
$netDataProvider = $container->get("inv.service_management.network_setup_from_inventory");
