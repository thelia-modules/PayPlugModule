<?php

namespace PayPlugModule\Controller;

use PayPlugModule\Event\Notification\UnknownNotificationEvent;
use PayPlugModule\Service\PaymentService;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Log\Tlog;

class NotificationController extends BaseFrontController
{
    public function entryPoint(Request $request)
    {
        /** @var PaymentService $paymentService */
        $paymentService = $this->container->get('payplugmodule_payment_service');
        Tlog::getInstance()->addAlert('Notification received');
        Tlog::getInstance()->addAlert($request->getContent());

        $notificationResource = $paymentService->getNotificationResource($request);

        $notificationEvent = new UnknownNotificationEvent($notificationResource);
        $this->dispatch(UnknownNotificationEvent::UNKNOWN_NOTIFICATION_EVENT, $notificationEvent);

        return new Response();
    }
}