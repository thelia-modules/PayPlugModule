<?php

namespace PayPlugModule\EventListener;

use PayPlugModule\Model\OrderPayPlugData;
use PayPlugModule\Model\OrderPayPlugDataQuery;
use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\PaymentService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;

class OrderListener implements EventSubscriberInterface
{
    /** @var PaymentService  */
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function onOrderUpdateStatus(OrderEvent $event)
    {
        $order = $event->getOrder();

        $orderPayPlugData = OrderPayPlugDataQuery::create()
            ->findOneById($order->getId());

        if (null === $orderPayPlugData || false == $orderPayPlugData->getNeedCapture()) {
            return;
        }

        $this->handleCapture($event, $orderPayPlugData);
    }

    protected function handleCapture(OrderEvent $event, OrderPayPlugData $orderPayPlugData)
    {
        // If already captured do nothing
        if (null !== $orderPayPlugData->getCapturedAt()) {
            return;
        }

        // If new status is not trigger status do nothing
        if (PayPlugModule::getConfigValue(PayPlugConfigValue::DIFFERED_PAYMENT_TRIGGER_CAPTURE_STATUS) != $event->getStatus()) {
            return;
        }

        $this->paymentService->doOrderCapture($event->getOrder());
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::ORDER_UPDATE_STATUS => ['onOrderUpdateStatus', 64]
        ];
    }
}