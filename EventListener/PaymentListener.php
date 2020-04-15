<?php

namespace PayPlugModule\EventListener;

use Payplug\Exception\PayplugException;
use Payplug\Payment;
use PayPlugModule\Event\PayPlugPaymentEvent;
use PayPlugModule\Model\OrderPayPlugData;
use PayPlugModule\Model\OrderPayPlugMultiPaymentQuery;
use PayPlugModule\Service\OrderStatusService;
use PayPlugModule\Service\PaymentService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;

class PaymentListener extends PaymentService implements EventSubscriberInterface
{
    /** @var OrderStatusService */
    protected $orderStatusService;

    public function __construct(EventDispatcherInterface $dispatcher, OrderStatusService $orderStatusService)
    {
        parent::__construct($dispatcher);
        $this->orderStatusService = $orderStatusService;
    }

    /**
     * Send refund to PayPlug, set payment url and id to event
     *
     * @param PayPlugPaymentEvent $paymentEvent
     * @throws \Payplug\Exception\ConfigurationNotSetException
     */
    public function createPayment(PayPlugPaymentEvent $paymentEvent)
    {
        try {
            $parameters = $paymentEvent->getFormattedPaymentParameters();
            $payPlugPayment = Payment::create($parameters);

            $paymentEvent->setPaymentId($payPlugPayment->id)
                ->setIsPaid($payPlugPayment->is_paid)
                ->setPaymentUrl($payPlugPayment->hosted_payment->payment_url);

        } catch (PayplugException $exception) {
            $response = json_decode($exception->getHttpResponse(), true);
            throw new \Exception($response['message']);
        }
    }

    /**
     * Send refund to PayPlug
     *
     * @param PayPlugPaymentEvent $paymentEvent
     * @throws \Payplug\Exception\ConfigurationNotSetException
     * @throws \Payplug\Exception\InvalidPaymentException
     */
    public function createRefund(PayPlugPaymentEvent $paymentEvent)
    {
        try {
            $payPlugPayment = Payment::retrieve($paymentEvent->getPaymentId());
            $data = null;
            if ($paymentEvent->getAmount()) {
                $data = ['amount' => $paymentEvent->getAmount()];
            }
            $payPlugPayment->refund($data);
        } catch (PayplugException $exception) {
            $response = json_decode($exception->getHttpResponse(), true);
            throw new \Exception($response['message']);
        }
    }

    /**
     * Send capture to PayPlug
     *
     * @param PayPlugPaymentEvent $paymentEvent
     * @throws \Payplug\Exception\ConfigurationNotSetException
     */
    public function createCapture(PayPlugPaymentEvent $paymentEvent)
    {
        try {
            $payPlugPayment = Payment::retrieve($paymentEvent->getPaymentId());

            $paymentCapture = $payPlugPayment->capture();
        } catch (PayplugException $exception) {
            $response = json_decode($exception->getHttpResponse(), true);
            throw new \Exception($response['message']);
        }
    }

    /**
     * Dispatch create payment for an order and set payment id to order transaction ref
     *
     * @param PayPlugPaymentEvent $paymentEvent
     */
    public function orderPayment(PayPlugPaymentEvent $paymentEvent)
    {
        $this->dispatcher->dispatch(PayPlugPaymentEvent::CREATE_PAYMENT_EVENT, $paymentEvent);

        $order = $paymentEvent->getOrder();

        $orderPayPlugData = (new OrderPayPlugData())
            ->setId($order->getId());

        if ($paymentEvent->isCapture()) {
            $orderPayPlugData->setNeedCapture(1);
        }

        $orderPayPlugData->save();

        $orderEvent = new OrderEvent($paymentEvent->getOrder());
        $orderEvent->setTransactionRef($paymentEvent->getPaymentId());
        $this->dispatcher->dispatch(TheliaEvents::ORDER_UPDATE_TRANSACTION_REF, $orderEvent);
    }

    /**
     * Dispatch refund for an order
     *
     * @param PayPlugPaymentEvent $paymentEvent
     * @throws \Payplug\Exception\ConfigurationNotSetException
     * @throws \Payplug\Exception\InvalidPaymentException
     */
    public function orderRefund(PayPlugPaymentEvent $paymentEvent)
    {
        $multiPayments = OrderPayPlugMultiPaymentQuery::create()
            ->findByOrderId($paymentEvent->getOrder()->getId());

        if (count($multiPayments) > 0) {
            foreach ($multiPayments as $multiPayment) {
                // If already refunded => do nothing
                if ($multiPayment->getRefundedAt() !== null) {
                    continue;
                }
                // If not paid => cancel it
                if ($multiPayment->getPaidAt() === null) {
                    $multiPayment->setPlannedAt(null)
                        ->save();
                    continue;
                }
                // Else refund it
                $refundPaymentEvent = clone $paymentEvent;
                $refundPaymentEvent->setPaymentId($multiPayment->getPaymentId())
                    ->setAmount($multiPayment->getAmount());
                $this->dispatcher->dispatch(PayPlugPaymentEvent::CREATE_REFUND_EVENT, $refundPaymentEvent);
            }
            return;
        }

        $this->dispatcher->dispatch(PayPlugPaymentEvent::CREATE_REFUND_EVENT, $paymentEvent);
    }

    /**
     * Dispatch capture for an order
     *
     * @param PayPlugPaymentEvent $paymentEvent
     * @throws \Payplug\Exception\ConfigurationNotSetException
     * @throws \Payplug\Exception\InvalidPaymentException
     */
    public function orderCapture(PayPlugPaymentEvent $paymentEvent)
    {
        $this->dispatcher->dispatch(PayPlugPaymentEvent::CREATE_CAPTURE_EVENT, $paymentEvent);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            PayPlugPaymentEvent::CREATE_PAYMENT_EVENT => ['createPayment', 128],
            PayPlugPaymentEvent::CREATE_REFUND_EVENT => ['createRefund', 128],
            PayPlugPaymentEvent::CREATE_CAPTURE_EVENT => ['createCapture', 128],
            PayPlugPaymentEvent::ORDER_PAYMENT_EVENT => ['orderPayment', 128],
            PayPlugPaymentEvent::ORDER_REFUND_EVENT => ['orderRefund', 128],
            PayPlugPaymentEvent::ORDER_CAPTURE_EVENT => ['orderCapture', 128]
        ];
    }
}