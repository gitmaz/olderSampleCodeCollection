<?php

namespace InventoryApi\ServiceManagement\EventDispatcher;

use InventoryApi\LogicalConfiguration\EventDispatcher\HandleCompatibilityEvent;
use InventoryApi\LogicalConfiguration\EventDispatcher\LogConfEvent;
use InventoryApi\ServiceManagement\Service\NetworkServiceManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SegmentDiscoveryEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var NetworkServiceManager
     */
    private $networkServiceManager;

    /**
     * Get Subscribed Events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LogConfEvent::LOG_CONF_PROPOSE_NEW_AFTER => ['handleProposeNewLogicalConfigurationEvent', 20],
            LogConfEvent::LOG_CONF_PROPOSE_DECOM_AFTER => ['handleProposeDecomLogicalConfigurationEvent', 20],
            LogConfEvent::LOG_CONF_PROPSE_MOD_AFTER => ['handleReportModLogicalConfigurationEvent', 20],

            LogConfEvent::LOG_CONF_REPORT_NEW_AFTER => ['handleReportNewLogicalConfigurationEvent', 20],
            LogConfEvent::LOG_CONF_REPORT_DECOM_AFTER => ['handleReportDecomLogicalConfigurationEvent', 20],
            LogConfEvent::LOG_CONF_REPORT_MOD_AFTER => ['handleReportModLogicalConfigurationEvent', 20],

        ];
    }

    /**
     * Constructor
     *
     * @param NetworkServiceManager $networkServiceManager
     */
    public function __construct(
        NetworkServiceManager $networkServiceManager
    )
    {
        $this->networkServiceManager = $networkServiceManager;
    }


    /**
     * Handle Propose New LogConf
     *
     * @param LogConfEvent $event
     */
    public function handleProposeNewLogicalConfigurationEvent(HandleCompatibilityEvent $event)
    {
        // Look for new Segment that can be proposed together with new Logical Configuration
        /*  $this->networkServiceManager->proposeNewNode(
    
                                                $event->getObject(), // LogConf as parent
                                                $event->getTtrId(),  
                                                $event->getSource(), 
                                                $event->getUserId(),
                                                $event->getFamilyEvent()); // Family event to decom/execute within the same transaction*/

//     $logger=Request::getContainer()->get("inv.service_management.loggit");
//     $logger->log("got a propose_new_log_conf event in segmentDescoverySubscriber");

        $this->networkServiceManager->gotCalled("handleProposeNewLogicalConfigurationEvent");
    }

    /**
     * Handle Propose Decom LogConf
     *
     * @param LogConfEvent $event
     */
    public function handleProposeDecomLogicalConfigurationEvent(HandleCompatibilityEvent $event)
    {
        // Find all segments that are impacted by Logical Configuration to be decommissioned
        /* $this->networkServiceManager->proposeDecomissionNode(
                                                   $event->getObject(), // LogConf as parent
                                                   $event->getTtrId(),
                                                   $event->getUserId(),
                                                   $event->getFamilyEvent());*/
        $this->networkServiceManager->gotCalled("handleProposeDecomLogicalConfigurationEvent");
    }

    /**
     * Handle Propose Mod LogConf
     *
     * @param LogConfEvent $event
     */
    public function handleProposeModLogicalConfigurationEvent(HandleCompatibilityEvent $event)
    {
        // Find all segments that are impacted by Logical Configuration to be decommissioned
        /* $this->networkServiceManager->proposeDecomissionNode(
                                                   $event->getObject(), // LogConf as parent
                                                   $event->getTtrId(),
                                                   $event->getUserId(),
                                                   $event->getFamilyEvent());*/
        $this->networkServiceManager->gotCalled("handleProposeModLogicalConfigurationEvent");
    }


    /**
     * Handle Report New Log Conf
     *
     * @param LogConfEvent $event
     */
    public function handleReportNewLogicalConfigurationEvent(HandleCompatibilityEvent $event)
    {
        // Look for new Segment that can be reported for new Logical Configuration
        /*$this->networkServiceManager->reportNewNodeDirectly(
                                              $event->getObject(), // LogConf as parent
                                              $event->getSource(),
                                              $event->getUserId());*/
        // No Family Event & TTR ID here because Report New Directly does not go through TimeMachine Workflow

        $this->networkServiceManager->gotCalled("handleReportNewLogicalConfigurationEvent");
    }

    /**
     * Handle Report Decom  Log Conf
     *
     * @param LogConfEvent $event
     */
    public function handleReportDecomLogicalConfigurationEvent(HandleCompatibilityEvent $event)
    {
        // Look for new Segment that can be reported for new Logical Configuration
        /*$this->networkServiceManager->reportNewNodeDirectly(
                                                           $event->getObject(), // LogConf as parent
                                                           $event->getSource(),
                                                           $event->getUserId());*/
        // No Family Event & TTR ID here because Report New Directly does not go through TimeMachine Workflow

        $this->networkServiceManager->gotCalled("handleReportDecomLogicalConfigurationEvent");
    }

    /**
     * Handle Propose Mod LogConf
     *
     * @param LogConfEvent $event
     */
    public function handleReportModLogicalConfigurationEvent(HandleCompatibilityEvent $event)
    {
        // Find all segments that are impacted by Logical Configuration to be decommissioned
        /* $this->networkServiceManager->proposeDecomissionNode(
                                                   $event->getObject(), // LogConf as parent
                                                   $event->getTtrId(),
                                                   $event->getUserId(),
                                                   $event->getFamilyEvent());*/
        $this->networkServiceManager->gotCalled("handleReportModLogicalConfigurationEvent");
    }
}
