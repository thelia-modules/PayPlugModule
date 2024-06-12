<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace PayPlugModule;

use PayPlugModule\EventListener\FormExtend\OrderFormListener;
use PayPlugModule\Model\PayPlugConfigValue;
use PayPlugModule\Service\PaymentService;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Install\Database;
use Thelia\Log\Tlog;
use Thelia\Model\Order;
use Thelia\Module\AbstractPaymentModule;
use Thelia\Tools\URL;

class PayPlugModule extends AbstractPaymentModule
{
    /** @var string */
    const DOMAIN_NAME = 'payplugmodule';


    public function postActivation(ConnectionInterface $con = null): void
    {
        if (!$this->getConfigValue('is_initialized', false)) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/thelia.sql"]);
        }
    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in(__DIR__ . DS . 'Config' . DS . 'update');

        $database = new Database($con);

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    public function isValidPayment()
    {
        if ($this->getCurrentOrderTotalAmount() < 1) {
            return false;
        }

        try {
            /** @var PaymentService $paymentService */
            $paymentService = $this->container->get('payplugmodule_payment_service');

            return $paymentService->isPayPlugAvailable();
        } catch (\Exception $e) {
            Tlog::getInstance()->addError("Error during Payplug validation check : ".$e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function pay(Order $order)
    {
        try {

            /** @var PaymentService $paymentService */
            $paymentService = $this->container->get('payplugmodule_payment_service');

            $slice = 1;

            $isMultiPayment = $this->getRequest()->getSession()->get(OrderFormListener::PAY_PLUG_MULTI_PAYMENT_FIELD_NAME, 0);
            $orderTotalAmount = $this->getOrderPayTotalAmount($order);

            if ($isMultiPayment) {
                $minAmount = PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MINIMUM);
                $maxAmount = PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_MAXIMUM);

                if ($minAmount <= $orderTotalAmount && $maxAmount >= $orderTotalAmount) {
                    $slice = PayPlugModule::getConfigValue(PayPlugConfigValue::MULTI_PAYMENT_TIMES);
                }
            }

            $payment = $paymentService->sendOrderPayment(
                $order,
                PayPlugModule::getConfigValue(PayPlugConfigValue::DIFFERED_PAYMENT_ENABLED, false),
                PayPlugModule::getConfigValue(PayPlugConfigValue::ONE_CLICK_PAYMENT_ENABLED, false),
                $slice,
                $orderTotalAmount
            );

            $forceRedirect = false;
            if (true === $payment['isPaid']) {
                $forceRedirect = true;
                $payment['url'] = URL::getInstance()->absoluteUrl('/order/placed/'.$order->getId());
            }

            if ($this->getRequest()->isXmlHttpRequest()) {
                return new JsonResponse(
                    [
                        'paymentUrl' => $payment['url'],
                        'forceRedirect' => $forceRedirect
                    ]
                );
            }
        } catch (\Exception $exception) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                return new JsonResponse(['error' => $exception->getMessage()], 400);
            }
            Tlog::getInstance()->addError(
                'Error PayPlugModule::pay() : ' . $exception->getMessage()
            );
            return new RedirectResponse(URL::getInstance()->absoluteUrl('error'));
        }

        return new RedirectResponse($payment['url']);
    }

    public function getHooks()
    {
        return [
            [
                "type" => TemplateDefinition::BACK_OFFICE,
                "code" => "payplugmodule.configuration.bottom",
                "title" => [
                    "en_US" => "Bottom of PayPlug configuration page",
                    "fr_FR" => "Bas de la page de configuration PayPlug",
                ],
                "block" => false,
                "active" => true,
            ]
        ];
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
