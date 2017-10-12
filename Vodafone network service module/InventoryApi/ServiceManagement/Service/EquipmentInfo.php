<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 11/05/2016
 * Time: 10:24 AM
 */
namespace InventoryApi\ServiceManagement\Service;

class EquipmentInfo
{

    public static $logConfOfEquip = [];

    public function getLogConfOfEquip($eqId)
    {
        return self::$logConfOfEquip[$eqId];
    }

    public function setLogConfOfEquip($eqId, $logConfId)
    {
        self::$logConfOfEquip[$eqId] = $logConfId;
    }

    public function setLogConfOfEquips($eqIds, $logConfIds)
    {
        foreach ($eqIds as $ind => $eqId) {
            $this->setLogConfOfEquip($eqId, $logConfIds[$ind]);
        }
    }


}