<?php

namespace PayPlugModule\EventListener;

use PayPlugModule\PayPlugModule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Thelia\Model\ModuleConfigQuery;

class ConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'module.config' => [
                'onModuleConfig', 128
                ],
        ];
    }

    public function onModuleConfig(GenericEvent $event): void
    {
        $subject = $event->getSubject();

        if ($subject !== "HealthStatus") {
            throw new \RuntimeException('Event subject does not match expected value');
        }

        $configModule = ModuleConfigQuery::create()
            ->filterByModuleId(PayPlugModule::getModuleId())
            ->filterByName(['live_api_key', 'test_api_key', 'payment_page_type', 'api_mode', 'payment_enabled'])
            ->find();

        $moduleConfig = [];
        $moduleConfig['module'] = PayPlugModule::getModuleCode();

        $paymentEnabled = false;
        $liveApiKey = null;
        $testApiKey = null;

        foreach ($configModule as $config) {
            $name = $config->getName();
            $value = $config->getValue();

            if ($name === 'payment_enabled') {
                $paymentEnabled = $value == 1;
            }

            if ($name === 'live_api_key') {
                $liveApiKey = $value;
            }

            if ($name === 'test_api_key') {
                $testApiKey = $value;
            }

            $moduleConfig[$name] = $value;
        }

        if (($liveApiKey !== null || $testApiKey !== null) && $paymentEnabled) {
            $moduleConfig['completed'] = true;
        } else {
            $moduleConfig['completed'] = false;
        }

        $moduleConfig['payment_enabled'] = $paymentEnabled;

        $event->setArgument('payplug.module.config', $moduleConfig);
    }

}
