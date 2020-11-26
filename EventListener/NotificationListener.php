<?php

namespace PayPlugModule\EventListener;

use Payplug\Resource\Refund;
use Payplug\Resource\Payment;
use PayPlugModule\Event\Notification\UnknownNotificationEvent;
use PayPlugModule\Event\Notification\PaymentNotificationEvent;
use PayPlugModule\Event\Notification\RefundNotificationEvent;
use PayPlugModule\Model\OrderPayPlugData;
use PayPlugModule\Model\OrderPayPlugDataQuery;
use PayPlugModule\Model\OrderPayPlugMultiPayment;
use PayPlugModule\Model\OrderPayPlugMultiPaymentQuery;
use PayPlugModule\Model\PayPlugCard;
use PayPlugModule\Model\PayPlugCardQuery;
use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\OrderStatusService;
use Propel\Runtime\Collection\Collection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Order;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatusQuery;

class NotificationListener implements EventSubscriberInterface
{
    /** @var OrderStatusService  */
    protected $orderStatusService;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher, OrderStatusService $orderStatusService)
    {
        $this->dispatcher = $dispatcher;
        $this->orderStatusService = $orderStatusService;
    }

    public function handleUnknownNotification(UnknownNotificationEvent $event)
    {
        $resource = $event->getResource();
        switch(true) {
            case $resource instanceof Payment:
                $paymentNotificationEvent = new PaymentNotificationEvent($resource);
                $this->dispatcher->dispatch(PaymentNotificationEvent::PAYMENT_NOTIFICATION_EVENT, $paymentNotificationEvent);
                break;
            case $resource instanceof Refund:
                $refundNotificationEvent = new RefundNotificationEvent($resource);
                $this->dispatcher->dispatch(RefundNotificationEvent::REFUND_NOTIFICATION_EVENT, $refundNotificationEvent);
                break;
        }
    }

    public function handlePaymentNotification(PaymentNotificationEvent $event)
    {
        $transactionRef = $event->getResource()->id;
        if (!$transactionRef) {
            return null;
        }

        $order = OrderQuery::create()
            ->filterByPaymentModuleId(PayPlugModule::getModuleId())
            ->filterByTransactionRef($transactionRef)
            ->findOne();

        if (null === $order) {
            return;
        }

        $orderPayPlugData = OrderPayPlugDataQuery::create()
            ->findOneById($order->getId());

        if (null === $orderPayPlugData) {
            return;
        }

        $paymentResource = $event->getResource();

        $orderStatusId = OrderStatusQuery::getCancelledStatus()->getId();

        $orderPayPlugMultiPayment = OrderPayPlugMultiPaymentQuery::create()
            ->findByOrderId($order->getId());

        // Multi payment is really different
        if ($orderPayPlugMultiPayment->count() > 0) {
            $this->handleMultiPaymentNotification($paymentResource, $order, $orderPayPlugData, $orderPayPlugMultiPayment);
            return;
        }

        if ($orderPayPlugData->getNeedCapture()) {
            // Handle differed payment
            if ($paymentResource->is_paid) {
                $orderPayPlugData->setCapturedAt((new \DateTime()))
                    ->save();
                // Don't update status on capture
                $orderStatusId = null;
            } elseif ($paymentResource->authorization->authorized_at) {
                $orderStatusId = PayPlugModule::getConfigValue(PayPlugConfigValue::DIFFERED_PAYMENT_AUTHORIZED_CAPTURE_STATUS);
                $orderPayPlugData->setCaptureExpireAt($paymentResource->authorization->expires_at)
                    ->save();
            }
        } elseif ($paymentResource->is_paid) {
            // Handle classic payment
            $orderStatusId = OrderStatusQuery::getPaidStatus()->getId();
        }

        if (null !== $orderStatusId) {
            $event = (new OrderEvent($order))
                ->setStatus($orderStatusId);
            $this->dispatcher->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
        }

        if (null !== $paymentResource->card->id) {
            $cardData = $paymentResource->card;
            $cardExist = PayPlugCardQuery::create()
                ->filterByCustomerId($order->getCustomerId())
                ->filterByUuid($cardData->id)
                ->findOne();

            if (null === $cardExist) {
                (new PayPlugCard())
                    ->setUuid($cardData->id)
                    ->setCustomerId($order->getCustomerId())
                    ->setBrand($cardData->brand)
                    ->setLast4($cardData->last4)
                    ->setExpireMonth($cardData->exp_month)
                    ->setExpireYear($cardData->exp_year)
                    ->save();
            }
        }
    }

    protected function handleMultiPaymentNotification($paymentResource, Order $order, OrderPayPlugData $orderPayPlugData, Collection $orderMultiPayments)
    {
        /** @var OrderPayPlugMultiPayment $orderMultiPayment */
        foreach ($orderMultiPayments as $orderMultiPayment) {
            $orderMultiPayment->setPaymentMethod($paymentResource->card->id);

            if ($paymentResource->id === $orderMultiPayment->getPaymentId()) {
                $orderStatusId = OrderStatusQuery::getCancelledStatus()->getId();

                if ($paymentResource->is_paid) {
                    $orderMultiPayment->setPaidAt(new \DateTime());
                    $orderStatusId = OrderStatusQuery::getPaidStatus()->getId();
                }

                // Update order status only for first payment
                if ($orderMultiPayment->getIsFirstPayment()) {
                    $event = (new OrderEvent($order))
                        ->setStatus($orderStatusId);
                    $this->dispatcher->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
                }
            }

            $orderMultiPayment->save();
        }
    }

    public function handleRefundNotification(RefundNotificationEvent $event)
    {
        $transactionRef = $event->getResource()->payment_id;
        if (!$transactionRef) {
            return;
        }

        $order =  OrderQuery::create()
            ->filterByPaymentModuleId(PayPlugModule::getModuleId())
            ->filterByTransactionRef($transactionRef)
            ->findOne();

        $multiPayment = OrderPayPlugMultiPaymentQuery::create()
            ->findOneByPaymentId($transactionRef);

        if (null !== $multiPayment) {
            $multiPayment->setAmountRefunded((int)$multiPayment->getAmountRefunded() + $event->getResource()->amount)
                ->save();
            $order = $multiPayment->getOrder();
        }

        if (null === $order) {
            return;
        }

        $orderPayPlugData = OrderPayPlugDataQuery::create()
            ->findOneById($order->getId());

        $orderPayPlugData->setAmountRefunded((int)$orderPayPlugData->getAmountRefunded() + $event->getResource()->amount)
            ->save();

        $event = (new OrderEvent($order))
            ->setStatus(OrderStatusQuery::getRefundedStatus()->getId());
        $this->dispatcher->dispatch(TheliaEvents::ORDER_UPDATE_STATUS, $event);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            UnknownNotificationEvent::UNKNOWN_NOTIFICATION_EVENT => ['handleUnknownNotification', 128],
            PaymentNotificationEvent::PAYMENT_NOTIFICATION_EVENT => ['handlePaymentNotification', 128],
            RefundNotificationEvent::REFUND_NOTIFICATION_EVENT => ['handleRefundNotification', 128]
        ];
    }
}