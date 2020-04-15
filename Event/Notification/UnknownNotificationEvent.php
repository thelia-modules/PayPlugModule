<?php

namespace PayPlugModule\Event\Notification;

use Payplug\Resource\IVerifiableAPIResource;
use Thelia\Core\Event\ActionEvent;

class UnknownNotificationEvent extends ActionEvent
{
    const UNKNOWN_NOTIFICATION_EVENT = "payplugmodule_unknown_notification_event";

    /** @var IVerifiableAPIResource */
    protected $resource;

    public function __construct(IVerifiableAPIResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return IVerifiableAPIResource
     */
    public function getResource(): IVerifiableAPIResource
    {
        return $this->resource;
    }

    /**
     * @param IVerifiableAPIResource $resource
     * @return UnknownNotificationEvent
     */
    public function setResource(IVerifiableAPIResource $resource): UnknownNotificationEvent
    {
        $this->resource = $resource;
        return $this;
    }
}