<?php

declare(strict_types=1);

namespace PayPlugModule\EventListener;

use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Thelia\Api\Bridge\Propel\Event\PaymentModuleOptionEvent;
use Thelia\Api\Resource\PaymentModuleOption;
use Thelia\Api\Resource\PaymentModuleOptionGroup;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Domain\Taxation\TaxEngine\TaxEngine;

/**
 * Offers the payment in instalments as an option of the PayPlug payment method.
 *
 * The theme shows the option under the method and keeps the buyer's answer in the
 * session, under SESSION_KEY, as a list of {group, values} entries; pay() reads it back
 * with isMultiPaymentChosen().
 */
final readonly class PaymentOptionListener implements EventSubscriberInterface
{
    public const SESSION_KEY = 'payment_module_option_choices';
    public const GROUP_CODE = 'payplug_multi_payment';
    public const MULTI_PAYMENT_OPTION_CODE = 'multi_payment';

    public function __construct(private TaxEngine $taxEngine)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::MODULE_PAYMENT_GET_OPTIONS => ['addMultiPaymentOption', 128],
        ];
    }

    public function addMultiPaymentOption(PaymentModuleOptionEvent $event): void
    {
        if ($event->getModule()->getId() !== PayPlugModule::getModuleId()) {
            return;
        }

        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_ENABLED)) {
            return;
        }

        $cart = $event->getCart();

        if (null === $cart) {
            return;
        }

        $country = $event->getCountry() ?? $this->taxEngine->getDeliveryCountry();
        $cartAmount = $cart->getTaxedAmount($country);

        if ($cartAmount <= (float) PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MINIMUM)
            || $cartAmount >= (float) PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MAXIMUM)) {
            return;
        }

        $translator = Translator::getInstance();
        $domain = PayPlugModule::DOMAIN_NAME.'.fo.default';

        $option = (new PaymentModuleOption())
            ->setCode(self::MULTI_PAYMENT_OPTION_CODE)
            ->setTitle($translator->trans(
                'Pay in %n times without fees.',
                ['%n' => PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_TIMES)],
                $domain,
            ))
            ->setDescription($translator->trans('You will be asked to save your payment card data.', [], $domain));

        $group = (new PaymentModuleOptionGroup())
            ->setCode(self::GROUP_CODE)
            ->setTitle($translator->trans('Payment in instalments', [], $domain))
            ->setMinimumSelectedOptions(0)
            ->setMaximumSelectedOptions(1)
            ->appendPaymentModuleOption($option);

        $event->appendPaymentModuleOptionGroups($group);
    }

    /**
     * Whether the buyer ticked the instalment option, while the shop still offers it.
     */
    public static function isMultiPaymentChosen(SessionInterface $session): bool
    {
        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_ENABLED)) {
            return false;
        }

        $choices = $session->get(self::SESSION_KEY, []);

        if (!\is_array($choices)) {
            return false;
        }

        foreach ($choices as $choice) {
            if (\is_array($choice)
                && self::GROUP_CODE === ($choice['group'] ?? null)
                && \is_array($choice['values'] ?? null)
                && \in_array(self::MULTI_PAYMENT_OPTION_CODE, $choice['values'], true)) {
                return true;
            }
        }

        return false;
    }
}
