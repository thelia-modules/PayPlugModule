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

        $event->add(
            $this->render(
                'PayPlugModule/order_pay_plug.html',
                array_merge(
                    $event->getArguments(),
                    [
                        'isPaid' => $order->isPaid(false),
                        'currency' => $order->getCurrency()->getSymbol()
                    ],
                    $orderPayPlugData->toArray(TableMap::TYPE_CAMELNAME),
                    [
                        'multiPayments' => $orderPayPlugMultiPayments
                    ]
                )
            )
        );

//        if ($order->isPaid()) {
//            $event->add(
//                $this->render(
//                    'PayPlugModule/order_refund_form.html',
//                    $event->getArguments()
//                )
//            );
//        }
//        if ($orderPayPlugData->getNeedCapture()) {
//            $event->add(
//                $this->render(
//                    'PayPlugModule/order_capture_data.html',
//                    array_merge($event->getArguments(), $orderPayPlugData->toArray(TableMap::TYPE_CAMELNAME))
//                )
//            );
//        }
    }
}