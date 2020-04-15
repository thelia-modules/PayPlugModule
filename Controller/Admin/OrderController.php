<?php

namespace PayPlugModule\Controller\Admin;

use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\PaymentService;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;

class OrderController extends BaseAdminController
{
    public function refundAction()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('payplugmodule_order_action_form');

        try {
            $data = $this->validateForm($form)->getData();
            $order = OrderQuery::create()
                ->findOneById($data['order_id']);

            /** @var PaymentService $paymentService */
            $paymentService = $this->container->get('payplugmodule_payment_service');
            $paymentService->doOrderRefund($order);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    PayPlugModule::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
        }

        return $this->generateSuccessRedirect($form);
    }

    public function captureAction()
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm('payplugmodule_order_action_form');

        try {
            $data = $this->validateForm($form)->getData();
            $order = OrderQuery::create()
                ->findOneById($data['order_id']);

            /** @var PaymentService $paymentService */
            $paymentService = $this->container->get('payplugmodule_payment_service');
            $paymentService->doOrderCapture($order);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    PayPlugModule::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
        }

        return $this->generateSuccessRedirect($form);
    }
}