<?php
/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 5/06/2016
 * Time: 11:39 AM
 */

namespace InventoryApi\ServiceManagement\Service;

use InventoryApi\ServiceManagement\Repository\LogiServiceRepository;

class SegmentHeaderFactory
{
    /*
     * @var LogiServiceRepository
     */
    private $logiServiceRepository;

    function __construct(
        LogiServiceRepository $logiServiceRepository
    )
    {
        $this->logiServiceRepository = $logiServiceRepository;
    }

    /*
     * Saves new header in database(or overwrites existing one
     */
    function create($numericHashAsId, $serviceTypeId, $serviceDescriptor, $serviceName)
    {
        $this->logiServiceRepository->saveServiceRecord($numericHashAsId, $serviceTypeId, $serviceDescriptor, $serviceName);

    }

    /**
     * @param [] $serviceIds
     */
    function resolveServiceNamesFromIds($serviceIds)
    {
        $serviceNames = [];
        foreach ($serviceIds as $serviceId) {
            $serviceNames[] = $this->logiServiceRepository->getServiceName($serviceId);
        }

        return $serviceNames;
    }

}