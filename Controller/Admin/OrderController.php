<?php

namespace PayPlugModule\Controller\Admin;

use PayPlugModule\Form\OrderActionForm;
use PayPlugModule\Form\OrderRefundForm;
use PayPlugModule\PayPlugModule;
use PayPlugModule\Service\PaymentService;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Model\OrderQuery;

class OrderController extends BaseAdminController
{
    public function refundAction(PaymentService $paymentService)
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(OrderRefundForm::getName());

        try {
            $data = $this->validateForm($form)->getData();
            $order = OrderQuery::create()
                ->findOneById($data['order_id']);

            $amountToRefund = (int)($data['refund_amount'] * 100);

            $paymentService->doOrderRefund($order, $amountToRefund);
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

        // Sleep to let time for PayPlug to send validation
        sleep(2);
        $url = $this->retrieveSuccessUrl($form);
        return $this->generateRedirect($url.'#orderPayPlug');
    }

    public function captureAction(PaymentService $paymentService)
    {
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), 'PayPlugModule', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(OrderActionForm::getName());

        try {
            $data = $this->validateForm($form)->getData();
            $order = OrderQuery::create()
                ->findOneById($data['order_id']);

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

        // Sleep to let time for PayPlug to send validation
        sleep(2);
        $url = $this->retrieveSuccessUrl($form);
        return $this->generateRedirect($url.'#orderPayPlug');
    }
}