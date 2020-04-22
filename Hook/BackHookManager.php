<?php


namespace PayPlugModule\Hook;


use PayPlugModule\Model\OrderPayPlugData;
use PayPlugModule\Model\OrderPayPlugDataQuery;
use PayPlugModule\Model\OrderPayPlugMultiPaymentQuery;
use PayPlugModule\PayPlugModule;
use Propel\Runtime\Map\TableMap;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;

class BackHookManager extends BaseHook
{
    /**
     * @param HookRenderEvent $event
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function onOrderEditPaymentModuleBottom(HookRenderEvent $event)
    {
        $order = OrderQuery::create()
            ->filterByPaymentModuleId(PayPlugModule::getModuleId())
            ->filterById($event->getArgument('order_id'))
            ->findOne();

        if (null === $order) {
            return;
        }

        /** @var OrderPayPlugData $orderPayPlugData */
        $orderPayPlugData = OrderPayPlugDataQuery::create()
            ->findOneById($order->getId());

        if (null === $orderPayPlugData) {
            return;
        }

        $orderPayPlugMultiPayments = OrderPayPlugMultiPaymentQuery::create()
            ->filterByOrderId($order->getId())
            ->find()
            ->toArray(null, false,TableMap::TYPE_CAMELNAME);

        $isPaid = !in_array($order->getOrderStatus()->getCode(), [OrderStatus::CODE_NOT_PAID, OrderStatus::CODE_CANCELED]);
        $event->add(
            $this->render(
                'PayPlugModule/order_pay_plug.html',
                array_merge(
                    $event->getArguments(),
                    [
                        'isPaid' => $isPaid,
                        'currency' => $order->getCurrency()->getSymbol()
                    ],
                    $orderPayPlugData->toArray(TableMap::TYPE_CAMELNAME),
                    [
                        'multiPayments' => $orderPayPlugMultiPayments
                    ]
                )
            )
        );
    }
}