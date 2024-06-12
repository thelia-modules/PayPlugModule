<?php

namespace PayPlugModule\Service;

use Payplug\Notification;
use Payplug\Payplug;
use PayPlugModule\Event\PayPlugPaymentEvent;
use PayPlugModule\Model\OrderPayPlugMultiPayment;
use PayPlugModule\Model\PayPlugCardQuery;
use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\Order;

class PaymentService
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        self::initAuth();
    }

    public function isPayPlugAvailable()
    {
        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::PAYMENT_ENABLED, false)) {
            return false;
        }

        return true;
    }

    /**
     * @param Order $order
     * @return array
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function sendOrderPayment(
        Order $order,
        bool $capture = false,
        bool $allowSaveCard = false,
        int $paymentSlice = 1,
        float $totalOrder
    ) {
        $paymentEvent = (new PayPlugPaymentEvent())
            ->buildFromOrder($order,$totalOrder)
            ->setCapture($capture)
            ->setAllowSaveCard($allowSaveCard);

        if (null !== $card = PayPlugCardQuery::create()->findOneByCustomerId($order->getCustomerId())) {
            $paymentEvent->setPaymentMethod($card->getUuid())
                ->setInitiator('PAYER')
                ->setAllowSaveCard(false);
        }

        $firstPayment = null;
        if ($paymentSlice > 1) {
            $totalAmount = $paymentEvent->getAmount();
            $firstAmount = round($totalAmount / $paymentSlice) + $totalAmount % $paymentSlice;
            $paymentEvent->setForceSaveCard(true)
                ->setAllowSaveCard(false)
                ->setPaymentMethod(null)
                ->setAmount($firstAmount);
            $today = (new \DateTime())->setTime(0,0,0,0);

            $firstPayment = (new OrderPayPlugMultiPayment())
                ->setAmount($paymentEvent->getAmount())
                ->setOrder($order)
                ->setPlannedAt($today)
                ->setPaymentId($paymentEvent->getPaymentId())
                ->setIsFirstPayment(true);
            $firstPayment->save();

            for ($paymentCount = 1; $paymentCount < $paymentSlice ; $paymentCount++) {
                $paymentDay = (clone $today)->add((new \DateInterval('P'.intval($paymentCount * 30).'D')));
                $multiPayment = (new OrderPayPlugMultiPayment())
                    ->setAmount(round($totalAmount / $paymentSlice))
                    ->setOrder($order)
                    ->setPlannedAt($paymentDay);
                $multiPayment->save();
            }
        }

        $this->dispatcher->dispatch($paymentEvent, PayPlugPaymentEvent::ORDER_PAYMENT_EVENT);

        if (null !== $firstPayment) {
            $firstPayment->setPaymentId($paymentEvent->getPaymentId())
                ->save();

        }

        $isPaid = $paymentEvent->isPaid();

        // If one click payment consider it as isPaid (redirect to order/placed)
        if (!$isPaid && $paymentEvent->isCapture() && null !== $paymentEvent->getPaymentMethod()) {
            $isPaid = true;
        }

        return [
            'id' => $paymentEvent->getPaymentId(),
            'url' => $paymentEvent->getPaymentUrl(),
            'isPaid' => $isPaid
        ];
    }

    public function doOrderCapture(Order $order)
    {
        $paymentEvent = (new PayPlugPaymentEvent())
            ->buildFromOrder($order);

        $this->dispatcher->dispatch($paymentEvent, PayPlugPaymentEvent::ORDER_CAPTURE_EVENT);
    }

    public function doOrderRefund(Order $order, int $amountRefund = null)
    {
        $paymentEvent = (new PayPlugPaymentEvent())
            ->buildFromOrder($order);

        if (null !== $amountRefund) {
            $paymentEvent->setAmount($amountRefund);
        }

        $this->dispatcher->dispatch($paymentEvent, PayPlugPaymentEvent::ORDER_REFUND_EVENT);
    }

    public function getNotificationResource(Request $request)
    {
        return Notification::treat($request->getContent());
    }

    public function initAuth()
    {
        if (null === PayPlugConfigValue::getApiKey()) {
            throw new \Exception("PayPlug API key is not set");
        }

        return Payplug::init(
            [
                'secretKey' => PayPlugConfigValue::getApiKey(),
                'apiVersion' => '2019-08-06'
            ]
        );
    }
}