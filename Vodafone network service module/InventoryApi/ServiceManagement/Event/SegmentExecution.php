<?php

namespace InventoryApi\ServiceManagement\Event;

use CommonConstant;
use EntityNames;
use ExceptionDbLogger;
use InventoryApi\DependencyInjection\Service\ContainerProvider;
use InventoryApi\ServiceManagement\Service\SegmentManager;


/**
 * Created by PhpStorm.
 * User: 61077789
 * Date: 7/06/2016
 * Time: 8:34 AM
 */
class SegmentExecution
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SegmentManager
     */
    private $segmentManager;

    private $container;

    /**
     */
    function __construct()
    {
        $this->container = ContainerProvider::getContainer();
        $this->em = $this->container->get("entity_manager_inv");//\Orm_Conf::getEntityManager();
        $this->segmentManager = $this->container->get("inv.service_management.segment_manager");
    }


    public function executeDecommisionedEvent($event)
    {
        // delete and move to history
        $segmentObjectId = $event->getTargetObjectId();
        $segmentToDecommision = $this->segmentManager->getSegmentByObjectId($segmentObjectId);

        //this is because report decom propose part is not yet commited in transaction to set the flag.we are setting it here
        //$segmentToDecommision->setProposedDecommission(1);

        $this->segmentManager->moveSegmentToHistory($segmentToDecommision, 0);
        $this->removeSegment($segmentToDecommision);
        $this->em->flush();
    }

    private function executeProposedEvent($event)
    {

        $segmentObjectId = $event->getSourceObjectId();
        $segmentToMakeAsIs = $this->segmentManager->getSegmentByObjectId($segmentObjectId);

        $this->segmentManager->addSegmentAsIs($segmentToMakeAsIs);

        $this->em->flush();
    }

    private function executeModifiedEvent($event)
    {
        // modify, delete the old one and make the target as is.

        $segmentSourceObjectId = $event->getSourceObjectId();
        $segmentSource = $this->segmentManager->getSegmentByObjectId($segmentSourceObjectId);

        $segmentTargetObjectId = $event->getTargetObjectId();
        $segmentTarget = $this->segmentManager->getSegmentByObjectId($segmentSourceObjectId);

        $this->segmentManager->moveSegmentToHistory($segmentTarget, 0);
        $this->copySegment($segmentSource, $segmentTarget);
        $this->segmentManager->addSegmentAsIs($segmentTarget);
        $sourceSegmentIdentifier = $segmentSource->getSegmentObjectId();
        $this->removeSegment($segmentSource);
        $this->em->flush();
        $segmentTarget->setSegmentObjectId($sourceSegmentIdentifier);
        $this->em->flush();
    }

    /**
     * This function is to execute the EquipmentEvent.
     * It checks whether event is proposed, modified or decommisioned and
     * then execute accordingly
     *
     * @param type $event
     */
    public function executeEvent($event)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if (!$event->getSourceObjectId()) {
                $this->executeDecommisionedEvent($event);
            } elseif (!$event->getTargetObjectId()) {
                $this->executeProposedEvent($event);
            } elseif ($event->getSourceObjectId() && $event->getTargetObjectId()) {
                $this->executeModifiedEvent($event);
            }
            $eventManager = new \EventManager();
            $eventManager->moveEventToHistory($event, 0);
            $eventDepManager = new \EventDependencyManager();
            $eventDepManager->freeDependenciesOnEvent($event);
            $eventManager->removeEvent($event);
            $this->em->flush();
            $this->em->getConnection()->commit();
            return array(
                CommonConstant::IS_SUCCESS => true
            );
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            ExceptionDbLogger::logException($e, $this->className . "::executeEvent", $event->getEventId());
            return array(
                CommonConstant::IS_SUCCESS => false,
                CommonConstant::ERROR_MESSAGE => $this->className . "::executeEvent, check the exception logs for event id: " . $event->getEventId()
            );
        }
    }

    public function executeEventVirtually($event)
    {
        if (!$event->getSourceObjectId()) {
            // delete and move to history
            $segmentToDecommision = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getTargetObjectId()
            ));
            return $segmentToDecommision;
        } elseif (!$event->getTargetObjectId()) {
            // add and make target as is
            $segmentToMakeAsIs = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getSourceObjectId()
            ));
            return $segmentToMakeAsIs;
        } elseif ($event->getSourceObjectId() && $event->getTargetObjectId()) {
            // modify, delete the old one and make the target as is.
            $segmentSource = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getSourceObjectId()
            ));
            $segmentTarget = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getTargetObjectId()
            ));
            $this->copySegment($segmentSource, $segmentTarget);
            $sourceSegmentIdentifier = $segmentSource->getSegmentObjectId();
            $segmentTarget->setSegmentObjectId($sourceSegmentIdentifier);
            return $segmentTarget;
        }
    }

    private function undoDecommisionedEvent($event)
    {
        $segmentWasToDecommision = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
            'segmentObjectId' => $event->getTargetObjectId()
        ));
        $segmentWasToDecommision->setProposedDecommission(0);
        $segmentWasToDecommision->setImpactedEvent(NULL);
        $this->em->flush();
    }

    private function undoProposedEvent($event)
    {
        // add and make target as is
        $segmentToAdd = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
            'segmentObjectId' => $event->getSourceObjectId()
        ));
        $this->segmentManager->moveSegmentToHistory($segmentToAdd, 1);
        $this->removeSegment($segmentToAdd);
        $this->em->flush();
    }

    private function undoModifiedEvent($event)
    {
        // modify, delete the old one and make the target as is.
        $segmentSource = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
            'segmentObjectId' => $event->getSourceObjectId()
        ));
        $this->segmentManager->moveSegmentToHistory($segmentSource, 1);
        $this->removeSegment($segmentSource);
        $segmentTarget = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
            'segmentObjectId' => $event->getTargetObjectId()
        ));
        $segmentTarget->setImpactedEvent(NULL);
    }

    public function undoEvent($event)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            if (!$event->getSourceObjectId()) {
                $this->undoDecommisionedEvent($event);
            } elseif (!$event->getTargetObjectId()) {
                $this->undoProposedEvent($event);
            } elseif ($event->getSourceObjectId() && $event->getTargetObjectId()) {
                $this->undoModifiedEvent($event);
            }
            $eventManager = new \EventManager();
            $eventManager->moveEventToHistory($event, 1);
            $eventDepManager = new \EventDependencyManager();
            $eventDepManager->removeEventReferencesAsBeingRemoved($event);
            $eventManager->removeEvent($event);
            $this->em->getConnection()->commit();
            return array(
                CommonConstant::IS_SUCCESS => true
            );
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            ExceptionDbLogger::logException($e, $this->className . "::undoEvent", $event->getEventId());
            return array(
                CommonConstant::IS_SUCCESS => false,
                CommonConstant::ERROR_MESSAGE => $this->className . "::undoEvent, check the exception logs for event id: " . $event->getEventId()
            );
        }
    }


    public function copySegment($segmentSource, $segmentTarget)
    {
        $segmentTarget->setAsIs($segmentSource->getAsIs());
        $now = new \DateTime();
        $now->setTimestamp(time());
        $segmentTarget->setSegmentName($segmentSource->getSegmentName());
        $segmentTarget->setCabinType($segmentSource->getCabinType());
        $segmentTarget->setBatchId($segmentSource->getBatchId());
        $segmentTarget->setHuaweiId($segmentSource->getHuaweiId());
        $segmentTarget->setJvId($segmentSource->getJvId());
        $segmentTarget->setLat($segmentSource->getLat());
        $segmentTarget->setLocation($segmentSource->getLocation());
        $segmentTarget->setLon($segmentSource->getLon());
        $segmentTarget->setLong($segmentSource->getLong());
        $segmentTarget->setM2000Id($segmentSource->getM2000Id());
        $segmentTarget->setMslSegmentId($segmentSource->getMslSegmentId());
        $segmentTarget->setImpactedEvent($segmentSource->getImpactedEvent());
        $segmentTarget->setAssociateEvent($segmentSource->getAssociateEvent());
        $segmentTarget->setLastChanged($segmentSource->getLastChanged());
        $segmentTarget->setLastUpdated($segmentSource->getLastUpdated());
        $segmentTarget->setProposedDecommission($segmentSource->getProposedDecommission());

        if ($segmentSource->getSource()) {
            $segmentTarget->setSource($segmentSource->getSource());
        }
    }

    public function removeSegment($segment)
    {
        $this->em->remove($segment);
    }


    public function getEventStatus($event)
    {
        /*
        if (! $event) {
            $evStatus = array(
                EquipConstants::EQ_STATUS_ID => CommonConstant::ASIS_ID,
                EquipConstants::EQ_STATUS => CommonConstant::ASIS_STATUS,
                CommonConstant::EVENT_DESCRIPTION => "AS_IS"
            );

            return $evStatus;
        } elseif (! $event->getSourceObjectId()) {
            $segmentSource = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getTargetObjectId()
            ));
            $evStatus = array(
                EquipConstants::EQ_STATUS_ID => CommonConstant::PROP_DEL_ID,
                EquipConstants::EQ_STATUS => CommonConstant::PROP_DEL_STATUS,
                CommonConstant::EVENT_DESCRIPTION => $segmentSource->getSegmentName()
            );

            return $evStatus;
        } elseif (! $event->getTargetObjectId()) {
            // add and make target as is
            $segmentTarget = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getSourceObjectId()
            ));
            $evStatus = array(
                EquipConstants::EQ_STATUS_ID => CommonConstant::PROP_ADD_ID,
                EquipConstants::EQ_STATUS => CommonConstant::PROP_ADD_STATUS,
                CommonConstant::EVENT_DESCRIPTION => $segmentTarget->getSegmentName()
            );
            return $evStatus;
        } elseif ($event->getSourceObjectId() && $event->getTargetObjectId()) {
            // modify, delete the old one and make the target as is.

            $segmentSource = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getSourceObjectId()
            ));

            $segmentTarget = $this->em->getRepository(EntityNames::LOGI_SERVICE_SEGMENT)->findOneBy(array(
                'segmentObjectId' => $event->getTargetObjectId()
            ));

            $evStatus = array(
                EquipConstants::EQ_STATUS_ID => CommonConstant::PROP_MOD_ID,
                EquipConstants::EQ_STATUS => CommonConstant::PROP_MOD_STATUS,
                CommonConstant::EVENT_DESCRIPTION => $segmentSource->getSegmentName() . " to " . $segmentTarget->getSegmentName()
            );

            return $evStatus;
        }
        */
    }
}

