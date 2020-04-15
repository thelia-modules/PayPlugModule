<?php

namespace PayPlugModule\EventListener\FormExtend;

use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\PaymentService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\HttpFoundation\Request;

class OrderFormListener implements EventSubscriberInterface
{
    const THELIA_CUSTOMER_ORDER_PAYMENT_FROM_NAME = 'thelia_order_payment';

    const PAY_PLUG_MULTI_PAYMENT_FIELD_NAME = 'pay_plug_multi_payment';

    /** @var Request  */
    protected $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function addMultiPaymentField(TheliaFormEvent $event)
    {
        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_ENABLED)) {
            return;
        }

        $event->getForm()->getFormBuilder()
            ->add(
                self::PAY_PLUG_MULTI_PAYMENT_FIELD_NAME,
                CheckboxType::class
            );

    }

    public function checkMultiPaymentSelected(OrderEvent $event)
    {
        $this->request->getSession()->set(self::PAY_PLUG_MULTI_PAYMENT_FIELD_NAME, 0);
        $formData = $this->request->get(self::THELIA_CUSTOMER_ORDER_PAYMENT_FROM_NAME);

        if (!isset($formData[self::PAY_PLUG_MULTI_PAYMENT_FIELD_NAME]) || 0 == $formData[self::PAY_PLUG_MULTI_PAYMENT_FIELD_NAME]) {
            return;
        }

        $this->request->getSession()->set(self::PAY_PLUG_MULTI_PAYMENT_FIELD_NAME, 1);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::FORM_AFTER_BUILD.'.'.self::THELIA_CUSTOMER_ORDER_PAYMENT_FROM_NAME => array('addMultiPaymentField', 64),
            TheliaEvents::ORDER_SET_PAYMENT_MODULE => array('checkMultiPaymentSelected', 64)
        );
    }
}