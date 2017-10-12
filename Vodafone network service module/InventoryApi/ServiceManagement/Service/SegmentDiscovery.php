<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 5/06/2016
 * Time: 10:28 AM
 */

namespace InventoryApi\ServiceManagement\Service;

class SegmentDiscovery
{
    /**
     * @var NetworkSetupFromInventory $netDataProvider
     */
    private $netDataProvider;

    function __construct(
        NetworkSetupFromInventory $netDataProvider
    )
    {
        $this->netDataProvider = $netDataProvider;
    }

    /*
     *@param integer $logConfId
     *@return  ServiceSegmentsInfo $serviceSegmentsInfo
     */
    public function discoverSegmentsRadiatingFrom($logConfId)
    {

        try {
            $serviceSegmentsInfo = $this->netDataProvider->resolveNeighborhoodServiceSegments($logConfId);
        } catch (\Exception $ex) {
            echo "Error in resolveNeighborhoodServiceSegments :" . $ex->getMessage();
        }
        return $serviceSegmentsInfo;
    }

    public function getLogicalConfiguration($logConfId)
    {
        return $this->netDataProvider->getLogicalConfiguration($logConfId);
    }

    public function getLogicalConfigurationFromHistory($logConfId)
    {
        return $this->netDataProvider->getLogicalConfigurationFromHistory($logConfId);
    }

    /*
     * @return [] segmentsData
     */
    public function resolveNeighborhoodServiceSegmentsFromDb($logConfId)
    {
        return $this->netDataProvider->resolveNeighborhoodServiceSegmentsFromDb($logConfId);
    }
}