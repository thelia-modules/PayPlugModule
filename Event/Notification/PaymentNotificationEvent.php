<?php

namespace PayPlugModule\Event\Notification;

use Payplug\Resource\Payment;
use Thelia\Core\Event\ActionEvent;

class PaymentNotificationEvent extends ActionEvent
{
    const PAYMENT_NOTIFICATION_EVENT = "payplugmodule_payment_notification_event";

    /** @var Payment */
    protected $resource;

    public function __construct(Payment $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return Payment
     */
    public function getResource(): Payment
    {
        return $this->resource;
    }

    /**
     * @param Payment $resource
     * @return PaymentNotificationEvent
     */
    public function setResource(Payment $resource): PaymentNotificationEvent
    {
        $this->resource = $resource;
        return $this;
    }
}