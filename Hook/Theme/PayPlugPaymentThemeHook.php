<?php

declare(strict_types=1);

namespace PayPlugModule\Hook\Theme;

use PayPlugModule\Model\PayPlugCardQuery;
use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\PayPlugModule;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Thelia\Core\Hook\Theme\ThemeHookInterface;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Translation\Translator;
use Twig\Environment;

/**
 * What PayPlug adds to the payment step of the theme.
 *
 * Under the PayPlug method, once chosen: the saved card that will be charged (one click
 * payment) and, in lightbox mode, the marker the lightbox script looks for. In the head
 * of the payment pages, in lightbox mode: the PayPlug form library and the script that
 * opens it instead of leaving the shop. The instalment payment is an option of the
 * method, see PaymentOptionListener.
 */
final readonly class PayPlugPaymentThemeHook implements ThemeHookInterface
{
    private const PAYMENT_EXTRA = 'checkout.payment.extra';

    private const SCRIPT_HOOKS = [
        'layout.head.checkout-payment',
        'layout.head.checkout-onepage',
    ];

    private const PAY_ROUTE = 'checkout_pay';

    private const TEMPLATE_DIR = '@PayPlugModuleModule/frontOffice/default/PayPlugModule/';

    public function __construct(
        private Environment $twig,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(string $hookName): bool
    {
        return self::PAYMENT_EXTRA === $hookName || \in_array($hookName, self::SCRIPT_HOOKS, true);
    }

    public function render(string $hookName, array $parameters): string
    {
        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::PAYMENT_ENABLED)) {
            return '';
        }

        if (self::PAYMENT_EXTRA !== $hookName) {
            return $this->isLightbox()
                ? $this->twig->render(self::TEMPLATE_DIR.'lightbox-script.html.twig')
                : '';
        }

        if ((int) ($parameters['module'] ?? 0) !== PayPlugModule::getModuleId()) {
            return '';
        }

        $last4 = $this->savedCardLast4();
        $payUrl = $this->isLightbox() ? $this->payUrl() : null;

        if (null === $last4 && null === $payUrl) {
            return '';
        }

        // Translated here: the module's front strings are read by the Thelia translator,
        // the Twig one of the theme does not load them.
        $translator = Translator::getInstance();
        $domain = PayPlugModule::DOMAIN_NAME.'.fo.default';

        return $this->twig->render(self::TEMPLATE_DIR.'payment-extra.html.twig', [
            'payUrl' => $payUrl,
            'savedCard' => null === $last4 ? null : [
                'title' => $translator->trans('One click payment is available !', [], $domain),
                'notice' => $translator->trans(
                    'A payment card ending in %last4% is saved on your account: it will be charged as soon as you place the order.',
                    ['%last4%' => $last4],
                    $domain,
                ),
                'deleteLabel' => $translator->trans('Delete this card', [], $domain),
                'deleteUrl' => $this->urlGenerator->generate('payplugmodule_delete_card'),
            ],
        ]);
    }

    private function isLightbox(): bool
    {
        return 'lightbox' === PayPlugModule::getConfigValue(PayPlugConfigValue::PAYMENT_PAGE_TYPE);
    }

    /**
     * The address the order button of the theme leads to, which the lightbox script
     * takes over. A theme without that route keeps the hosted payment page.
     */
    private function payUrl(): ?string
    {
        try {
            return $this->urlGenerator->generate(self::PAY_ROUTE);
        } catch (RouteNotFoundException) {
            return null;
        }
    }

    /**
     * The last digits of the card PaymentService will charge, under the same conditions:
     * one click payment on, a card saved by the customer, not expired.
     */
    private function savedCardLast4(): ?string
    {
        if (!PayPlugModule::getConfigValue(PayPlugConfigValue::ONE_CLICK_PAYMENT_ENABLED)) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->hasSession()) {
            return null;
        }

        $session = $request->getSession();

        if (!$session instanceof Session) {
            return null;
        }

        $customer = $session->getCustomerUser();

        if (null === $customer) {
            return null;
        }

        $card = PayPlugCardQuery::create()->findOneByCustomerId($customer->getId());

        if (null === $card) {
            return null;
        }

        $expireDate = date('Y-m', strtotime($card->getExpireYear().'-'.$card->getExpireMonth()));

        if ($expireDate <= date('Y-m')) {
            return null;
        }

        return (string) $card->getLast4();
    }
}
