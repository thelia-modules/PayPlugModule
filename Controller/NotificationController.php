<?php

namespace PayPlugModule\Controller;

use PayPlugModule\Event\Notification\UnknownNotificationEvent;
use PayPlugModule\Service\PaymentService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Log\Tlog;

class NotificationController extends BaseFrontController
{
    public function entryPoint(Request $request, EventDispatcherInterface $eventDispatcher, PaymentService $paymentService)
    {
        Tlog::getInstance()->addAlert('Notification received');
        Tlog::getInstance()->addAlert($request->getContent());

        $notificationResource = $paymentService->getNotificationResource($request);

        $notificationEvent = new UnknownNotificationEvent($notificationResource);
        $eventDispatcher->dispatch($notificationEvent, UnknownNotificationEvent::UNKNOWN_NOTIFICATION_EVENT);

        return new Response();
    }
}