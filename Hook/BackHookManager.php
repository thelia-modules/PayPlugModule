<?php

namespace PayPlugModule\Hook;

use PayPlugModule\Form\ConfigurationForm;
use PayPlugModule\Model\OrderPayPlugData;
use PayPlugModule\Model\OrderPayPlugDataQuery;
use PayPlugModule\Model\OrderPayPlugMultiPaymentQuery;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\OrderStatusService;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Model\OrderQuery;
use Thelia\Model\OrderStatus;

class BackHookManager extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        private readonly OrderStatusService $orderStatusService,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfigure'],
            ],
            'order-edit.payment-module-bottom' => [
                ['type' => 'back', 'method' => 'onOrderEditPaymentModuleBottom'],
            ],
            'order.edit-js' => [
                ['type' => 'back', 'method' => 'onOrderEditJs'],
            ],
        ];
    }

    public function onModuleConfigure(HookRenderEvent $event): void
    {
        $this->orderStatusService->initAllStatuses();

        $form = $this->formFactory->createForm(ConfigurationForm::getName());

        $event->add(
            $this->render(
                'PayPlugModule/configuration.html.twig',
                array_merge(
                    $event->getArguments(),
                    [
                        'form' => $form->createView()->getView(),
                        'deliveryModuleFormFields' => ConfigurationForm::getDeliveryModuleFormFields(),
                    ]
                )
            )
        );
    }

    public function onOrderEditPaymentModuleBottom(HookRenderEvent $event): void
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
            ->toArray(null, false, TableMap::TYPE_CAMELNAME);

        $isPaid = !in_array($order->getOrderStatus()->getCode(), [OrderStatus::CODE_NOT_PAID, OrderStatus::CODE_CANCELED]);

        $tax = 0;
        $orderTotalAmount = $order->getTotalAmount($tax, true);
        $orderTotalAmountWithoutShipping = $order->getTotalAmount($tax, false);

        $orderProducts = [];
        foreach ($order->getOrderProducts() as $orderProduct) {
            $unitPrice = $orderProduct->getWasInPromo()
                ? (float) $orderProduct->getPromoPrice()
                : (float) $orderProduct->getPrice();

            $unitTax = 0.0;
            foreach ($orderProduct->getOrderProductTaxes() as $orderProductTax) {
                $unitTax += $orderProduct->getWasInPromo()
                    ? (float) $orderProductTax->getPromoAmount()
                    : (float) $orderProductTax->getAmount();
            }

            $orderProducts[] = [
                'ref' => $orderProduct->getProductSaleElementsRef(),
                'title' => $orderProduct->getTitle(),
                'realTotalTaxedPrice' => (round($unitPrice, 2) + round($unitTax, 2)) * $orderProduct->getQuantity(),
            ];
        }

        $event->add(
            $this->render(
                'PayPlugModule/order_pay_plug.html.twig',
                array_merge(
                    $event->getArguments(),
                    [
                        'isPaid' => $isPaid,
                        'currency' => $order->getCurrency()->getSymbol(),
                        'orderTotalAmount' => $orderTotalAmount,
                        'orderTotalAmountWithoutShipping' => $orderTotalAmountWithoutShipping,
                        'orderProducts' => $orderProducts,
                    ],
                    $orderPayPlugData->toArray(TableMap::TYPE_CAMELNAME),
                    [
                        'multiPayments' => $orderPayPlugMultiPayments,
                    ]
                )
            )
        );
    }

    public function onOrderEditJs(HookRenderEvent $event): void
    {
        $event->add(
            $this->render('PayPlugModule/order_pay_plug.js.html.twig', $event->getArguments())
        );
    }
}
