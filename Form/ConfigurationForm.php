<?php

namespace PayPlugModule\Form;

use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\OrderStatusService;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\OrderStatusQuery;

class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {
        $orderStatuses = OrderStatusQuery::create()
            ->find();

        $orderStatusChoices = [];
        foreach ($orderStatuses as $orderStatus) {
            $orderStatusChoices[$orderStatus->getId()] = $orderStatus->getTitle();
        }

        /** @var OrderStatusService $orderStatusesService */
        $orderStatusesService = $this->container->get('payplugmodule_order_status_service');

        $this->formBuilder
            ->add(
                PayPlugConfigValue::OFFER,
                ChoiceType::class,
                [
                    'choices' => [
                        'starter' => 'Starter',
                        'pro' => 'Pro',
                        'premium' => 'Premium',
                    ],
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::OFFER),
                    "label"=> Translator::getInstance()->trans("Select your PayPlug offer", [], PayPlugModule::DOMAIN_NAME),
                    "required" => true
                ]
            )
            ->add(
                PayPlugConfigValue::PAYMENT_ENABLED,
                CheckboxType::class,
                [
                    "data" => !!PayPlugModule::getConfigValue(PayPlugConfigValue::PAYMENT_ENABLED, false),
                    "label"=> Translator::getInstance()->trans("Enable payment by PayPlug", [], PayPlugModule::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::API_MODE,
                ChoiceType::class,
                [
                    'choices' => [
                        'live' => 'Live',
                        'test' => 'Test',
                    ],
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::API_MODE),
                    "label"=> Translator::getInstance()->trans("Choose API mode", [], PayPlugModule::DOMAIN_NAME),
                    "required" => true
                ]
            )
            ->add(
                PayPlugConfigValue::LIVE_API_KEY,
                TextType::class,
                [
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::LIVE_API_KEY),
                    "label"=> Translator::getInstance()->trans("Live API secret key", [], PayPlugModule::DOMAIN_NAME),
                    "label_attr" => ['help' => Translator::getInstance()->trans("Look here %link", ['%link' => "<a target='_blank' href='https://portal.payplug.com/#/configuration/api'>Api configuration</a>"], PayPlugModule::DOMAIN_NAME)],
                    "required" => true
                ]
            )
            ->add(
                PayPlugConfigValue::TEST_API_KEY,
                TextType::class,
                [
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::TEST_API_KEY),
                    "label"=> Translator::getInstance()->trans("Test API secret key", [], PayPlugModule::DOMAIN_NAME),
                    "label_attr" => ['help' => Translator::getInstance()->trans("Look here %link", ['%link' => "<a target='_blank' href='https://portal.payplug.com/#/configuration/api'>Api configuration</a>"], PayPlugModule::DOMAIN_NAME)],
                    "required" => true
                ]
            )
            ->add(
                PayPlugConfigValue::PAYMENT_PAGE_TYPE,
                ChoiceType::class,
                [
                    'choices' => [
                        'hosted_page' => Translator::getInstance()->trans("Hosted page", [], PayPlugModule::DOMAIN_NAME),
                        'lightbox' => Translator::getInstance()->trans("Lightbox", [], PayPlugModule::DOMAIN_NAME),
                        // Todo implement payplug JS
                        //'payplug_js' => Translator::getInstance()->trans("Payplug.js", [], PayPlugModule::DOMAIN_NAME)
                    ],
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::PAYMENT_PAGE_TYPE),
                    "label"=> Translator::getInstance()->trans("Payment page type", [], PayPlugModule::DOMAIN_NAME),
                    "label_attr" => ['help' => Translator::getInstance()->trans("Hosted page will redirect your customer to a payment page / Lightbox will open a payment pop up in your website.", [], PayPlugModule::DOMAIN_NAME)],
                    "required" => true
                ]
            )
            ->add(
                PayPlugConfigValue::ONE_CLICK_PAYMENT_ENABLED,
                CheckboxType::class,
                [
                    "data" => !!PayPlugModule::getConfigValue(PayPlugConfigValue::ONE_CLICK_PAYMENT_ENABLED),
                    "label"=> Translator::getInstance()->trans("Enable one click payment", [], PayPlugModule::DOMAIN_NAME),
                    "label_attr" => ['help' => Translator::getInstance()->trans("This will allow your customer to save their card fo future order.", [], PayPlugModule::DOMAIN_NAME)],
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::MULTI_PAYMENT_ENABLED,
                CheckboxType::class,
                [
                    "data" => !!PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_ENABLED),
                    "label"=> Translator::getInstance()->trans("Enabled multi-payment", [], PayPlugModule::DOMAIN_NAME),
                    "label_attr" => ['help' => Translator::getInstance()->trans("Enable payment in 2,3 or 4 times", [], PayPlugModule::DOMAIN_NAME)],
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::MULTI_PAYMENT_TIMES,
                ChoiceType::class,
                [
                    'choices' => [
                        '2' => Translator::getInstance()->trans("2 times", [], PayPlugModule::DOMAIN_NAME),
                        '3' => Translator::getInstance()->trans("3 times", [], PayPlugModule::DOMAIN_NAME),
                        '4' => Translator::getInstance()->trans("4 times", [], PayPlugModule::DOMAIN_NAME)
                    ],
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_TIMES),
                    "label"=> Translator::getInstance()->trans("Payment in ", [], PayPlugModule::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::MULTI_PAYMENT_MINIMUM,
                TextType::class,
                [
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MINIMUM),
                    "label"=> Translator::getInstance()->trans("Minimum amount ", [], PayPlugModule::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::MULTI_PAYMENT_MAXIMUM,
                TextType::class,
                [
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MAXIMUM),
                    "label"=> Translator::getInstance()->trans("Maximum amount ", [], PayPlugModule::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::DIFFERED_PAYMENT_ENABLED,
                CheckboxType::class,
                [
                    "data" => !!PayPlugModule::getConfigValue(PayPlugConfigValue::DIFFERED_PAYMENT_ENABLED),
                    "label"=> Translator::getInstance()->trans("Enabled differed payment", [], PayPlugModule::DOMAIN_NAME),
                    "label_attr" => ['help' => Translator::getInstance()->trans("Trigger the payment on order status change (max : 7 days after)", [], PayPlugModule::DOMAIN_NAME)],
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::DIFFERED_PAYMENT_AUTHORIZED_CAPTURE_STATUS,
                ChoiceType::class,
                [
                    'choices' => $orderStatusChoices,
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::DIFFERED_PAYMENT_AUTHORIZED_CAPTURE_STATUS, $orderStatusesService->findOrCreateAuthorizedCaptureOrderStatus()->getId()),
                    "label"=> Translator::getInstance()->trans("Which status to set when a capture is authorized", [], PayPlugModule::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::DIFFERED_PAYMENT_TRIGGER_CAPTURE_STATUS,
                ChoiceType::class,
                [
                    'choices' => $orderStatusChoices,
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::DIFFERED_PAYMENT_TRIGGER_CAPTURE_STATUS),
                    "label"=> Translator::getInstance()->trans("Capture the payment after order get the status", [], PayPlugModule::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                PayPlugConfigValue::DIFFERED_PAYMENT_CAPTURE_EXPIRED_STATUS,
                ChoiceType::class,
                [
                    'choices' => $orderStatusChoices,
                    "data" => PayPlugModule::getConfigValue(PayPlugConfigValue::DIFFERED_PAYMENT_CAPTURE_EXPIRED_STATUS, $orderStatusesService->findOrCreateExpiredCaptureOrderStatus()->getId()),
                    "label"=> Translator::getInstance()->trans("What status to set on expired capture ", [], PayPlugModule::DOMAIN_NAME),
                    "required" => false
                ]
            )
        ;
    }

    public function getName()
    {
        return "payplugmodule_configuration_form";
    }
}
