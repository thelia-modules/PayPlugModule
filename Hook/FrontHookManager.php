<?php

namespace PayPlugModule\Hook;

use PayPlugModule\Model\PayPlugCardQuery;
use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\Country;
use Thelia\TaxEngine\TaxEngine;

class FrontHookManager extends BaseHook
{
    /** @var TaxEngine */
    protected $taxEngine;

    public function __construct(TaxEngine $taxEngine)
    {
        $this->taxEngine = $taxEngine;
    }

    public function onOrderInvoiceAfterJsInclude(HookRenderEvent $event)
    {
        $payPlugModuleId = PayPlugModule::getModuleId();
        if (PayPlugModule::getConfigValue(PayPlugConfigValue::PAYMENT_PAGE_TYPE) === "lightbox") {
            $event->add($this->render(
                'PayPlugModule/order-invoice-after-js-include.html',
                compact('payPlugModuleId')
            ));
        }
    }

    public function onOrderInvoicePaymentExtra(HookRenderEvent $event)
    {
        if ((int)$event->getArgument('module') !== PayPlugModule::getModuleId()) {
            return;
        }

        $this->displayOneClickPayment($event);
        $this->displayMultiPayment($event);
    }

    protected function displayMultiPayment(HookRenderEvent $event)
    {
        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_ENABLED)) {
            return;
        }

        $nTimes = PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_TIMES);
        $minimumAmount = PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MINIMUM);
        $maximumAmount = PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MAXIMUM);

        /** @var Country $country */
        $country = $this->taxEngine->getDeliveryCountry();

        $cart = $this->getSession()->getSessionCart();
        $cartAmount = $cart->getTaxedAmount($country);
        if ($cartAmount <= $minimumAmount || $cartAmount >= $maximumAmount) {
            return;
        }

        $event->add(
            $this->render(
                'PayPlugModule/multi-payment.html',
                compact("nTimes")
            )
        );
    }

    protected function displayOneClickPayment(HookRenderEvent $event)
    {
        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::ONE_CLICK_PAYMENT_ENABLED)) {
            return;
        }

        $customerId = $this->getSession()->getCustomerUser()->getId();

        $payPlugCard = PayPlugCardQuery::create()
            ->findOneByCustomerId($customerId);

        if (null === $payPlugCard) {
            return;
        }

        $event->add(
            $this->render(
                'PayPlugModule/one-click-payment.html',
                [
                    'last4' => $payPlugCard->getLast4()
                ]
            )
        );
    }
}