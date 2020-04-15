<?php

namespace PayPlugModule\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\OrderStatus\OrderStatusCreateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\OrderStatusQuery;

class OrderStatusService
{
    const REFUND_PENDING_ORDER_STATUS_CODE = "refund_pending";

    const AUTHORIZED_CAPTURE_ORDER_STATUS_CODE = "authorized_capture";
    const EXPIRED_CAPTURE_ORDER_STATUS_CODE = "expired_capture";

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function initAllStatuses()
    {
        $this->findOrCreateRefundPendingOrderStatus();
        $this->findOrCreateAuthorizedCaptureOrderStatus();
        $this->findOrCreateExpiredCaptureOrderStatus();
    }

    /**
     * @return \Thelia\Model\OrderStatus
     */
    public function findOrCreateRefundPendingOrderStatus()
    {
        $refundPendingOrderStatus = OrderStatusQuery::create()
            ->findOneByCode($this::REFUND_PENDING_ORDER_STATUS_CODE);

        if (null !== $refundPendingOrderStatus) {
            return $refundPendingOrderStatus;
        }

        $refundPendingOrderStatusEvent = (new OrderStatusCreateEvent())
            ->setCode(self::REFUND_PENDING_ORDER_STATUS_CODE)
            ->setColor("#A7A7A7")
            ->setLocale('en_US')
            ->setTitle('Refund pending');

        $this->dispatcher->dispatch(TheliaEvents::ORDER_STATUS_CREATE, $refundPendingOrderStatusEvent);

        return $refundPendingOrderStatusEvent->getOrderStatus();
    }

    /**
     * @return \Thelia\Model\OrderStatus
     */
    public function findOrCreateAuthorizedCaptureOrderStatus()
    {
        $authorizedCaptureOrderStatus = OrderStatusQuery::create()
            ->findOneByCode($this::AUTHORIZED_CAPTURE_ORDER_STATUS_CODE);

        if (null !== $authorizedCaptureOrderStatus) {
            return $authorizedCaptureOrderStatus;
        }

        $authorizedCaptureOrderStatus = (new OrderStatusCreateEvent())
            ->setCode(self::AUTHORIZED_CAPTURE_ORDER_STATUS_CODE)
            ->setColor("#71ED71")
            ->setLocale('en_US')
            ->setTitle('Authorized capture');

        $this->dispatcher->dispatch(TheliaEvents::ORDER_STATUS_CREATE, $authorizedCaptureOrderStatus);

        return $authorizedCaptureOrderStatus->getOrderStatus();
    }

    /**
     * @return \Thelia\Model\OrderStatus
     */
    public function findOrCreateExpiredCaptureOrderStatus()
    {
        $expiredCaptureOrderStatus = OrderStatusQuery::create()
            ->findOneByCode($this::EXPIRED_CAPTURE_ORDER_STATUS_CODE);

        if (null !== $expiredCaptureOrderStatus) {
            return $expiredCaptureOrderStatus;
        }

        $expiredCaptureOrderStatus = (new OrderStatusCreateEvent())
            ->setCode(self::EXPIRED_CAPTURE_ORDER_STATUS_CODE)
            ->setColor("#4B4B4B")
            ->setLocale('en_US')
            ->setTitle('Expired capture');

        $this->dispatcher->dispatch(TheliaEvents::ORDER_STATUS_CREATE, $expiredCaptureOrderStatus);

        return $expiredCaptureOrderStatus->getOrderStatus();
    }

}