<?php

namespace InventoryApi\ServiceManagement\EventDispatcher;

use Event;
use InventoryApi\Event\Entity\TimeMachineObjectInterface;
use Source;
use Symfony\Component\EventDispatcher\Event as EventDispatcherEvent;

class NetworkServiceChangeEventData extends EventDispatcherEvent
{

    /**
     * @var TimeMachineObjectInterface
     */
    private $object;

    /**
     * @var integer
     */
    private $ttrId;

    /**
     * @var Source
     */
    private $source;

    /**
     * @var Event
     */
    private $familyEvent;

    /**
     * @var integer
     */
    private $userId;

    /**
     * Constructor
     *
     * @param TimeMachineObjectInterface $object
     */
    public function __construct(
        TimeMachineObjectInterface $object,
        $ttrId,
        Source $source = NULL,
        Event $familyEvent = NULL,
        $userId
    )
    {
        $this->object = $object;
        $this->ttrId = (int)$ttrId;
        $this->source = $source;
        $this->familyEvent = $familyEvent;
        $this->userId = (int)$userId;
    }

    /**
     * Get Object.
     *
     * @return TimeMachineObjectInterface
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Get UserId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Get TtrId
     *
     * @return integer
     */
    public function getTtrId()
    {
        return $this->ttrId;
    }

    /**
     * Get Source
     *
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get Family Event.
     *
     * @return Event
     */
    public function getFamilyEvent()
    {
        return $this->familyEvent;
    }
}