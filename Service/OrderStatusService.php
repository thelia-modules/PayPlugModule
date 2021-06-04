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
        $this->findOrCreateRefundPendingOrderStatus($this->dispatcher);
        $this->findOrCreateAuthorizedCaptureOrderStatus($this->dispatcher);
        $this->findOrCreateExpiredCaptureOrderStatus($this->dispatcher);
    }

    /**
     * @return \Thelia\Model\OrderStatus
     */
    public static function findOrCreateRefundPendingOrderStatus(EventDispatcherInterface $eventDispatcher)
    {
        $refundPendingOrderStatus = OrderStatusQuery::create()
            ->findOneByCode(self::REFUND_PENDING_ORDER_STATUS_CODE);

        if (null !== $refundPendingOrderStatus) {
            return $refundPendingOrderStatus;
        }

        $refundPendingOrderStatusEvent = (new OrderStatusCreateEvent())
            ->setCode(self::REFUND_PENDING_ORDER_STATUS_CODE)
            ->setColor("#A7A7A7")
            ->setLocale('en_US')
            ->setTitle('Refund pending');

        $eventDispatcher->dispatch($refundPendingOrderStatusEvent, TheliaEvents::ORDER_STATUS_CREATE);

        return $refundPendingOrderStatusEvent->getOrderStatus();
    }

    /**
     * @return \Thelia\Model\OrderStatus
     */
    public static function findOrCreateAuthorizedCaptureOrderStatus(EventDispatcherInterface $eventDispatcher)
    {
        $authorizedCaptureOrderStatus = OrderStatusQuery::create()
            ->findOneByCode(self::AUTHORIZED_CAPTURE_ORDER_STATUS_CODE);

        if (null !== $authorizedCaptureOrderStatus) {
            return $authorizedCaptureOrderStatus;
        }

        $authorizedCaptureOrderStatus = (new OrderStatusCreateEvent())
            ->setCode(self::AUTHORIZED_CAPTURE_ORDER_STATUS_CODE)
            ->setColor("#71ED71")
            ->setLocale('en_US')
            ->setTitle('Authorized capture');

        $eventDispatcher->dispatch($authorizedCaptureOrderStatus, TheliaEvents::ORDER_STATUS_CREATE);

        return $authorizedCaptureOrderStatus->getOrderStatus();
    }

    /**
     * @return \Thelia\Model\OrderStatus
     */
    public static function findOrCreateExpiredCaptureOrderStatus(EventDispatcherInterface $eventDispatcher)
    {
        $expiredCaptureOrderStatus = OrderStatusQuery::create()
            ->findOneByCode(self::EXPIRED_CAPTURE_ORDER_STATUS_CODE);

        if (null !== $expiredCaptureOrderStatus) {
            return $expiredCaptureOrderStatus;
        }

        $expiredCaptureOrderStatus = (new OrderStatusCreateEvent())
            ->setCode(self::EXPIRED_CAPTURE_ORDER_STATUS_CODE)
            ->setColor("#4B4B4B")
            ->setLocale('en_US')
            ->setTitle('Expired capture');

        $eventDispatcher->dispatch($expiredCaptureOrderStatus, TheliaEvents::ORDER_STATUS_CREATE);

        return $expiredCaptureOrderStatus->getOrderStatus();
    }

}