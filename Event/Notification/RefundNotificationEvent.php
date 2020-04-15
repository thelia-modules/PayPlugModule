<?php

namespace PayPlugModule\Event\Notification;

use Payplug\Resource\Refund;
use Thelia\Core\Event\ActionEvent;

class RefundNotificationEvent extends ActionEvent
{
    const REFUND_NOTIFICATION_EVENT = "payplugmodule_refund_notification_event";

    /** @var Refund */
    protected $resource;

    public function __construct(Refund $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return Refund
     */
    public function getResource(): Refund
    {
        return $this->resource;
    }

    /**
     * @param Refund $resource
     * @return RefundNotificationEvent
     */
    public function setResource(Refund $resource): RefundNotificationEvent
    {
        $this->resource = $resource;
        return $this;
    }
}