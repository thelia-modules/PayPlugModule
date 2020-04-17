<?php


namespace PayPlugModule\Model;


use PayPlugModule\PayPlugModule;

class PayPlugConfigValue
{
    const OFFER = "offer";
    const PAYMENT_ENABLED = "payment_enabled";
    const API_MODE = "api_mode";
    const LIVE_API_KEY = "live_api_key";
    const TEST_API_KEY = "test_api_key";
    const PAYMENT_PAGE_TYPE = "payment_page_type";
    const ONE_CLICK_PAYMENT_ENABLED = "one_click_payment_enabled";
    const MULTI_PAYMENT_ENABLED = "multi_payment_enabled";
    const MULTI_PAYMENT_TIMES = "multi_payment_times";
    const MULTI_PAYMENT_MINIMUM = "multi_payment_minimum";
    const MULTI_PAYMENT_MAXIMUM = "multi_payment_maximum";
    const DIFFERED_PAYMENT_ENABLED = "differed_payment_enabled";
    const DIFFERED_PAYMENT_AUTHORIZED_CAPTURE_STATUS = "differed_payment_authorized_capture_status";
    const DIFFERED_PAYMENT_TRIGGER_CAPTURE_STATUS = "differed_payment_trigger_capture_status";
    const DIFFERED_PAYMENT_CAPTURE_EXPIRED_STATUS = "differed_payment_capture_expired_status";
    const SEND_CONFIRMATION_MESSAGE_ONLY_IF_PAID = "send_confirmation_message_only_if_paid";

    public static function getConfigKeys()
    {
        return [
            self::OFFER,
            self::PAYMENT_ENABLED,
            self::API_MODE,
            self::LIVE_API_KEY,
            self::TEST_API_KEY,
            self::PAYMENT_PAGE_TYPE,
            self::ONE_CLICK_PAYMENT_ENABLED,
            self::MULTI_PAYMENT_ENABLED,
            self::MULTI_PAYMENT_TIMES,
            self::MULTI_PAYMENT_MINIMUM,
            self::MULTI_PAYMENT_MAXIMUM,
            self::DIFFERED_PAYMENT_ENABLED,
            self::DIFFERED_PAYMENT_TRIGGER_CAPTURE_STATUS,
            self::DIFFERED_PAYMENT_AUTHORIZED_CAPTURE_STATUS,
            self::DIFFERED_PAYMENT_CAPTURE_EXPIRED_STATUS,
            self::SEND_CONFIRMATION_MESSAGE_ONLY_IF_PAID
        ];
    }

    public static function getApiKey()
    {
        if (PayPlugModule::getConfigValue(self::API_MODE, 'test') === 'live') {
            return PayPlugModule::getConfigValue(self::LIVE_API_KEY);
        }

        return PayPlugModule::getConfigValue(self::TEST_API_KEY);
    }
}
