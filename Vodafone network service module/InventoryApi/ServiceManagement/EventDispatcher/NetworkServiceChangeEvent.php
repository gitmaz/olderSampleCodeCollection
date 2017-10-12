<?php

namespace InventoryApi\ServiceManagement\EventDispatcher;

final class NetworkServiceChangeEvent
{
    /**
     * Event is thrown each time a new Service is proposed.
     *
     * The event listener receives an
     * InventoryApi\ServiceManagement\EventDispatcher\NetworkServiceChangeEventData instance.
     *
     * @var string
     */
    const NETWORK_SERVICE_PROPOSE_NEW_AFTER = 'network_service.propose_new_after';

    /**
     * Event is thrown after a network service is proposed to be decommissioned.
     *
     * The event listener receives an
     * InventoryApi\ServiceManagement\EventDispatcher\NetworkServiceChangeEventData instance.
     *
     * @var string
     */
    const NETWORK_SERVICE_PROPOSE_DECOM_AFTER = 'network_service.propose_decom_after';

    /**
     * Event is thrown each time a new network service is reported directly.
     *
     * The event listener receives an
     * InventoryApi\ServiceManagement\EventDispatcher\NetworkServiceChangeEventData instance.
     *
     * @var string
     */
    const NETWORK_SERVICE_REPORT_NEW_DIRECTLY_AFTER = 'network_service.report_new_directly_after';

    /**
     * Event is thrown each time a network service decommission is reported
     *
     * The event listener receives an
     * InventoryApi\ServiceManagement\EventDispatcher\NetworkServiceChangeEventData instance.
     *
     * @var string
     */
    const NETWORK_SERVICE_REPORT_DECOM_DIRECTLY_AFTER = 'network_service.report_decom_directly_after';
}