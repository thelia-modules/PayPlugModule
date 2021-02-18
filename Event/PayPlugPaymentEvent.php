<?php

namespace PayPlugModule\Event;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PayPlugModule\Model\PayPlugModuleDeliveryTypeQuery;
use PayPlugModule\PayPlugModule;
use Thelia\Core\Event\ActionEvent;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Order;
use Thelia\Tools\URL;

class PayPlugPaymentEvent extends ActionEvent
{
    const CREATE_PAYMENT_EVENT = "payplugmodule_create_payment_event";
    const CREATE_REFUND_EVENT = "payplugmodule_create_refund_event";
    const CREATE_CAPTURE_EVENT = "payplugmodule_create_capture_event";

    const ORDER_PAYMENT_EVENT = "payplugmodule_order_payment_event";
    const ORDER_REFUND_EVENT = "payplugmodule_order_refund_event";
    const ORDER_CAPTURE_EVENT = "payplugmodule_order_capture_event";

    const PARAMETER_DEFINITIONS = [
        'amount' => [
            'type' => 'integer',
            'access' => 'getFormattedAmount',
            'accessParameters' => [
                'amount'
            ]
        ],
        'authorized_amount' => [
            'type' => 'integer',
            'access' => 'getFormattedAmount',
            'accessParameters' => [
                'authorized_amount'
            ]
        ],
        'auto_capture' => [
            'type' => 'boolean',
            'required' => false,
            'access' => 'autoCapture'
        ],
        'allow_save_card' => [
            'type' => 'boolean',
            'access' => 'allowSaveCard'
        ],
        'payment_method' => [
            'type' => 'string',
            'access' => 'paymentMethod'
        ],
        'initiator' => [
            'type' => 'string',
        ],
        'save_card' => [
            'type' => 'boolean',
            'access' => 'forceSaveCard'
        ],
        'currency' => [
            'type' => 'string',
            'required' => true
        ],
        'billing' => [
            'type' => 'nested',
            'required' => true,
            'parameters' => [
                'title' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'billingTitle'
                ],
                'first_name' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'billingFirstName'
                ],
                'last_name' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'billingLastName'
                ],
                'email' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'billingEmail'
                ],
                'mobile_phone_number' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'billingMobilePhone'
                ],
                'landline_phone_number' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'billingLandLinePhone'
                ],
                'address1' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'billingAddress1'
                ],
                'address2' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'billingAddress2'
                ],
                'postcode' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'billingPostcode'
                ],
                'company_name' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'billingCompany'
                ],
                'city' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'billingCity'
                ],
                'state' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'billingState'
                ],
                'country' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'billingCountry'
                ],
                'language' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'billingLanguage'
                ]
            ]
        ],
        'shipping' => [
            'type' => 'nested',
            'required' => true,
            'parameters' => [
                'title' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'shippingTitle'
                ],
                'first_name' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'shippingFirstName'
                ],
                'last_name' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'shippingLastName'
                ],
                'email' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'shippingEmail'
                ],
                'mobile_phone_number' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'shippingMobilePhone'
                ],
                'landline_phone_number' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'shippingLandLinePhone'
                ],
                'address1' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'shippingAddress1'
                ],
                'address2' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'shippingAddress2'
                ],
                'postcode' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'shippingPostcode'
                ],
                'company_name' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'shippingCompany'
                ],
                'city' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'shippingCity'
                ],
                'state' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'shippingState'
                ],
                'country' => [
                    'type' => 'string',
                    'required' => true,
                    'access' => 'shippingCountry'
                ],
                'language' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'shippingLanguage'
                ],
                'delivery_type' => [
                    'type' => 'choice',
                    'required' => true,
                    'access' => 'shippingDeliveryType',
                    'options' => [
                        'BILLING',
                        'VERIFIED',
                        'NEW',
                        'SHIP_TO_STORE',
                        'DIGITAL_GOODS',
                        'TRAVEL_OR_EVENT',
                        'OTHER'
                    ]
                ]
            ]
        ],
        'hosted_payment' => [
            'type' => 'nested',
            'parameters' => [
                'return_url' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'hostedPaymentReturnUrl'
                ],
                'cancel_url' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'hostedPaymentCancelUrl'
                ],
                'sent_by' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'hostedPaymentSentBy'
                ]
            ]
        ],
        'notification_url' => [
            'type' => 'string',
            'required' => false,
            'access' => 'notificationUrl'
        ],
        'payment_context' => [
            'type' => 'nested',
            'required' => false,
            'parameters' => [
                'cart' => [
                    'type' => 'array',
                    'access' => 'products',
                    'item_definition' => [
                        'brand' => [
                            'type' => "string",
                            'required' => true,
                            'access' => 'getBrand'
                        ],
                        'expected_delivery_date' => [
                            'type' => "string",
                            'required' => true,
                            'access' => "getExpectedDeliveryDate"
                        ],
                        'delivery_label' => [
                            'type' => "string",
                            'required' => true,
                            'access' => "getDeliveryLabel"
                        ],
                        'delivery_type' => [
                            'type' => "string",
                            'required' => true,
                            'access' => "getDeliveryType"
                        ],
                        'merchant_item_id' => [
                            'type' => "string",
                            'required' => true,
                            'access' => "getMerchantItemId"
                        ],
                        'name' => [
                            'type' => "string",
                            'required' => true,
                            'access' => "getName"
                        ],
                        'price' => [
                            'type' => "integer",
                            'required' => true,
                            'access' => "getPrice"
                        ],
                        'quantity' => [
                            'type' => "integer",
                            'required' => true,
                            'access' => "getQuantity"
                        ],
                        'total_amount' => [
                            'type' => "integer",
                            'required' => true,
                            'access' => "getTotalAmount"
                        ]
                    ]
                ]
            ]
        ],
        'metadata' => [
            'type' => 'nested',
            'parameters' => [
                'customer_id' => [
                    'type' => 'string',
                    'required' => false,
                    'access' => 'customerId'
                ]
            ]
        ],
    ];

    /**
     * @var string
     */
    protected $paymentId;

    /**
     * @var string
     */
    protected $paymentUrl;

    /**
     * @var boolean
     */
    protected $isPaid;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var integer
     */
    protected $amount;

    /**
     * @var boolean
     */
    protected $capture = false;

    /**
     * @var boolean
     */
    protected $autoCapture = false;

    /**
     * @var boolean
     */
    protected $allowSaveCard = false;

    /**
     * @var boolean
     */
    protected $forceSaveCard = false;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var string
     */
    protected $initiator;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @var string
     */
    protected $billingPersonTitle;

    /**
     * @var string
     */
    protected $billingFirstName;

    /**
     * @var string
     */
    protected $billingLastName;

    /**
     * @var string
     */
    protected $billingMobilePhone;

    /**
     * @var string
     */
    protected $billingLandLinePhone;

    /**
     * @var string
     */
    protected $billingEmail;

    /**
     * @var string
     */
    protected $billingAddress1;

    /**
     * @var string
     */
    protected $billingAddress2;

    /**
     * @var string
     */
    protected $billingPostcode;

    /**
     * @var string
     */
    protected $billingCompany;

    /**
     * @var string
     */
    protected $billingCity;

    /**
     * @var string
     */
    protected $billingState;

    /**
     * @var string
     */
    protected $billingCountry;

    /**
     * @var string
     */
    protected $billingLanguage;

    /**
     * @var string
     */
    protected $shippingPersonTitle;

    /**
     * @var string
     */
    protected $shippingFirstName;

    /**
     * @var string
     */
    protected $shippingLastName;

    /**
     * @var string
     */
    protected $shippingMobilePhone;

    /**
     * @var string
     */
    protected $shippingLandLinePhone;

    /**
     * @var string
     */
    protected $shippingEmail;

    /**
     * @var string
     */
    protected $shippingAddress1;

    /**
     * @var string
     */
    protected $shippingAddress2;

    /**
     * @var string
     */
    protected $shippingPostcode;

    /**
     * @var string
     */
    protected $shippingCompany;

    /**
     * @var string
     */
    protected $shippingCity;

    /**
     * @var string
     */
    protected $shippingState;

    /**
     * @var string
     */
    protected $shippingCountry;

    /**
     * @var string
     */
    protected $shippingLanguage;

    /**
     * @var string
     */
    protected $shippingDeliveryType = 'BILLING';

    /**
     * @var string
     */
    protected $hostedPaymentReturnUrl;

    /**
     * @var string
     */
    protected $hostedPaymentCancelUrl;

    /**
     * @var string
     */
    protected $hostedPaymentSentBy;

    /**
     * @var PayPlugProduct[]
     */
    protected $products;

    /**
     * @var string
     */
    protected $notificationUrl;

    public function buildFromOrder(Order $order)
    {
        $this->order = $order;

        if (null !== $customer = $order->getCustomer()) {
            $this->setCustomerId($customer->getId())
                ->setBillingEmail($customer->getEmail())
                ->setShippingEmail($customer->getEmail());
        }

        if (null !== $order) {
            // Avoid php bad int cast https://www.php.net/manual/fr/function.intval.php#60793
            $orderAmount = $order->getTotalAmount() * 100;
            $this->setAmount(intval("$orderAmount"))
                ->setCurrency($order->getCurrency()->getCode());

            $this->setPayPlugAddress($order->getOrderAddressRelatedByInvoiceOrderAddressId(), 'Billing');
            $this->setPayPlugAddress($order->getOrderAddressRelatedByDeliveryOrderAddressId(), 'Shipping');

            $langCode = $order->getLang()->getCode();
            $this->setBillingLanguage($langCode)
                ->setShippingLanguage($langCode);

            $this->setHostedPaymentReturnUrl(URL::getInstance()->absoluteUrl('/order/placed/'.$order->getId()))
                ->setHostedPaymentCancelUrl(URL::getInstance()->absoluteUrl('/order/failed/'.$order->getId().'/-'))
                ->setNotificationUrl(URL::getInstance()->absoluteUrl('/payplug/notification'));

            // If payment is done retrieve his id on transaction ref
            $this->setPaymentId($order->getTransactionRef());

            $payPlugDeliveryType = PayPlugModuleDeliveryTypeQuery::create()
                ->filterByModuleId($order->getDeliveryModuleId())
                ->findOne();

            foreach ($order->getOrderProducts() as $orderProduct) {
                $this->addProduct((new PayPlugProduct())->buildFromOrderProduct($orderProduct, $payPlugDeliveryType));
            }
        }

        return $this;
    }

    public function getFormattedPaymentParameters()
    {
        $this->checkParametersReadyForPayment($this::PARAMETER_DEFINITIONS);
        $formattedParameters = [];
        $this->buildArrayParameters($this::PARAMETER_DEFINITIONS, $formattedParameters);

        return $formattedParameters;
    }

    protected function buildArrayParameters($parameterDefinitions, &$array, $parentKey = null, $target = null)
    {
        if (null === $target) {
            $target = $this;
        }

        foreach ($parameterDefinitions as $key => $parameterDefinition) {
            $access = isset($parameterDefinition['access']) ? $parameterDefinition['access'] : $key;

            if ($parameterDefinition['type'] === 'array') {
                $targetArray = isset($array[$parentKey]) ? $array[$parentKey] : $array;
                $targetArray[$key] = [];

                foreach ($target->{$access} as $item) {
                    $childArray = [];
                    $this->buildArrayParameters($parameterDefinition['item_definition'], $childArray, $key, $item);
                    $targetArray[$key][] = $childArray;
                }

                if (!empty($targetArray[$key])) {
                    $array = $targetArray;
                }
                continue;
            }

            if ($parameterDefinition['type'] === 'nested') {
                $targetArray = isset($array[$parentKey]) ? $array[$parentKey] : $array;
                $targetArray[$key] = [];

                $this->buildArrayParameters($parameterDefinition['parameters'], $targetArray[$key], $key);
                if (!empty($targetArray[$key])) {
                    $array = $targetArray;
                }
                continue;
            }

            $value = null;
            $value = property_exists($target, $access) ? $target->{$access} :null;
            if (null === $value && method_exists($target, $access)) {
                $parameters = isset($parameterDefinition['accessParameters']) ? $parameterDefinition['accessParameters'] : [];
                $value = call_user_func([$target, $access], ...$parameters);
            }

            // If still null or empty no need to fill this parameter
            if (null == $value) {
                continue;
            }

            $array[$key] = $value;
        }
    }

    protected function checkParametersReadyForPayment(array $parameterDefinitions, $target = null)
    {
        if (null === $target) {
            $target = $this;
        }

        foreach ($parameterDefinitions as $key => $parameterDefinition) {
            $access = isset($parameterDefinition['access']) ? $parameterDefinition['access'] : $key;

            if ($parameterDefinition['type'] === 'array') {
                foreach ($target->{$access} as $item) {
                    $this->checkParametersReadyForPayment($parameterDefinition['item_definition'], $item);
                }
                continue;
            }

            if ($parameterDefinition['type'] === 'nested') {
                $this->checkParametersReadyForPayment($parameterDefinition['parameters'], $target);
                continue;
            }

            $value = property_exists($target, $access) ? $target->{$access} :null;
            if (null === $value && method_exists($target, $access)) {
                $parameters = isset($parameterDefinition['accessParameters']) ? $parameterDefinition['accessParameters'] : [];
                $value = call_user_func([$target, $access], ...$parameters);
            }

            if (isset($parameterDefinition['required']) && $parameterDefinition['required'] && $value == null) {
                throw new \Exception(Translator::getInstance()->trans("Invalid payment parameter, %parameter should not be null or empty.", ['%parameter' => $key], PayPlugModule::DOMAIN_NAME));
            }
        }
    }

    public function setPayPlugAddress($addressData, $addressType)
    {
        $this
            ->{'set'.$addressType.'FirstName'}($addressData->getFirstname())
            ->{'set'.$addressType.'LastName'}($addressData->getLastname())
            ->{'set'.$addressType.'Address1'}($addressData->getAddress1())
            ->{'set'.$addressType.'Postcode'}($addressData->getZipcode())
            ->{'set'.$addressType.'City'}($addressData->getCity())
            ->{'set'.$addressType.'Country'}($addressData->getCountry()->getIsoalpha2());

        if (null !== $addressData->getAddress2()) {
            $this->{'set'.$addressType.'Address2'}($addressData->getAddress2());
        }

        if (null !== $state = $addressData->getState()) {
            $this->{'set'.$addressType.'State'}($state->getIsocode());
        }

        if (null !== $addressData->getCellphone()) {
            $internationalPhoneNumber = $this->formatPhoneNumber($addressData->getCellphone(), $addressData->getCountry()->getIsoalpha2());
            $this->{'set'.$addressType.'MobilePhone'}($internationalPhoneNumber);
        }

        if (null !== $addressData->getPhone()) {
            $internationalPhoneNumber = $this->formatPhoneNumber($addressData->getPhone(), $addressData->getCountry()->getIsoalpha2());
            $this->{'set'.$addressType.'LandLinePhone'}($internationalPhoneNumber);
        }

        if (null !== $addressData->getCompany()) {
            $this->{'set'.$addressType.'Company'}($addressData->getCompany());
        }
    }

    protected function getFormattedAmount($key)
    {
        if ((!$this->capture && $key === "amount") || ($this->capture && $key === "authorized_amount")) {
            return $this->amount;
        }

        return null;
    }

    protected function formatPhoneNumber($phoneNumber, $countryCode)
    {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $phoneNumber = $phoneUtil->parse($phoneNumber, $countryCode);

            return $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     * @return PayPlugPaymentEvent
     */
    public function setPaymentId(string $paymentId = null): PayPlugPaymentEvent
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * @param string $paymentUrl
     * @return PayPlugPaymentEvent
     */
    public function setPaymentUrl(string $paymentUrl): PayPlugPaymentEvent
    {
        $this->paymentUrl = $paymentUrl;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPaid()
    {
        return $this->isPaid;
    }

    /**
     * @param bool $isPaid
     * @return PayPlugPaymentEvent
     */
    public function setIsPaid(bool $isPaid): PayPlugPaymentEvent
    {
        $this->isPaid = $isPaid;
        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @param Order $order
     * @return PayPlugPaymentEvent
     */
    public function setOrder(Order $order): PayPlugPaymentEvent
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return PayPlugPaymentEvent
     */
    public function setAmount(int $amount): PayPlugPaymentEvent
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCapture()
    {
        return $this->capture;
    }

    /**
     * @param bool $capture
     * @return PayPlugPaymentEvent
     */
    public function setCapture(bool $capture): PayPlugPaymentEvent
    {
        $this->capture = $capture;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoCapture()
    {
        return $this->autoCapture;
    }

    /**
     * @param bool $autoCapture
     * @return PayPlugPaymentEvent
     */
    public function setAutoCapture(bool $autoCapture): PayPlugPaymentEvent
    {
        $this->autoCapture = $autoCapture;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowSaveCard()
    {
        return $this->allowSaveCard;
    }

    /**
     * @param bool $allowSaveCard
     * @return PayPlugPaymentEvent
     */
    public function setAllowSaveCard(bool $allowSaveCard): PayPlugPaymentEvent
    {
        $this->allowSaveCard = $allowSaveCard;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForceSaveCard()
    {
        return $this->forceSaveCard;
    }

    /**
     * @param bool $forceSaveCard
     * @return PayPlugPaymentEvent
     */
    public function setForceSaveCard(bool $forceSaveCard): PayPlugPaymentEvent
    {
        $this->forceSaveCard = $forceSaveCard;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return PayPlugPaymentEvent
     */
    public function setPaymentMethod(string $paymentMethod = null): PayPlugPaymentEvent
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    /**
     * @param string $initiator
     * @return PayPlugPaymentEvent
     */
    public function setInitiator(string $initiator): PayPlugPaymentEvent
    {
        $this->initiator = $initiator;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return PayPlugPaymentEvent
     */
    public function setCurrency(string $currency): PayPlugPaymentEvent
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param string $customerId
     * @return PayPlugPaymentEvent
     */
    public function setCustomerId(string $customerId): PayPlugPaymentEvent
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingPersonTitle()
    {
        return $this->billingPersonTitle;
    }

    /**
     * @param string $billingPersonTitle
     * @return PayPlugPaymentEvent
     */
    public function setBillingPersonTitle(string $billingPersonTitle): PayPlugPaymentEvent
    {
        $this->billingPersonTitle = $billingPersonTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingFirstName()
    {
        return $this->billingFirstName;
    }

    /**
     * @param string $billingFirstName
     * @return PayPlugPaymentEvent
     */
    public function setBillingFirstName(string $billingFirstName): PayPlugPaymentEvent
    {
        $this->billingFirstName = $billingFirstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingLastName()
    {
        return $this->billingLastName;
    }

    /**
     * @param string $billingLastName
     * @return PayPlugPaymentEvent
     */
    public function setBillingLastName(string $billingLastName): PayPlugPaymentEvent
    {
        $this->billingLastName = $billingLastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingMobilePhone()
    {
        return $this->billingMobilePhone;
    }

    /**
     * @param string $billingMobilePhone
     * @return PayPlugPaymentEvent
     */
    public function setBillingMobilePhone(string $billingMobilePhone): PayPlugPaymentEvent
    {
        $this->billingMobilePhone = $billingMobilePhone;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingLandLinePhone()
    {
        return $this->billingLandLinePhone;
    }

    /**
     * @param string $billingLandLinePhone
     * @return PayPlugPaymentEvent
     */
    public function setBillingLandLinePhone(string $billingLandLinePhone): PayPlugPaymentEvent
    {
        $this->billingLandLinePhone = $billingLandLinePhone;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingEmail()
    {
        return $this->billingEmail;
    }

    /**
     * @param string $billingEmail
     * @return PayPlugPaymentEvent
     */
    public function setBillingEmail(string $billingEmail): PayPlugPaymentEvent
    {
        $this->billingEmail = $billingEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingAddress1()
    {
        return $this->billingAddress1;
    }

    /**
     * @param string $billingAddress1
     * @return PayPlugPaymentEvent
     */
    public function setBillingAddress1(string $billingAddress1): PayPlugPaymentEvent
    {
        $this->billingAddress1 = $billingAddress1;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingAddress2()
    {
        return $this->billingAddress2;
    }

    /**
     * @param string $billingAddress2
     * @return PayPlugPaymentEvent
     */
    public function setBillingAddress2(string $billingAddress2): PayPlugPaymentEvent
    {
        $this->billingAddress2 = $billingAddress2;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingPostcode()
    {
        return $this->billingPostcode;
    }

    /**
     * @param string $billingPostcode
     * @return PayPlugPaymentEvent
     */
    public function setBillingPostcode(string $billingPostcode): PayPlugPaymentEvent
    {
        $this->billingPostcode = $billingPostcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBillingCompany()
    {
        return $this->billingCompany;
    }

    /**
     * @param string $billingCompany
     * @return PayPlugPaymentEvent
     */
    public function setBillingCompany(string $billingCompany): PayPlugPaymentEvent
    {
        $this->billingCompany = $billingCompany;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingCity()
    {
        return $this->billingCity;
    }

    /**
     * @param string $billingCity
     * @return PayPlugPaymentEvent
     */
    public function setBillingCity(string $billingCity): PayPlugPaymentEvent
    {
        $this->billingCity = $billingCity;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingState()
    {
        return $this->billingState;
    }

    /**
     * @param string $billingState
     * @return PayPlugPaymentEvent
     */
    public function setBillingState(string $billingState): PayPlugPaymentEvent
    {
        $this->billingState = $billingState;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingCountry()
    {
        return $this->billingCountry;
    }

    /**
     * @param string $billingCountry
     * @return PayPlugPaymentEvent
     */
    public function setBillingCountry(string $billingCountry): PayPlugPaymentEvent
    {
        $this->billingCountry = $billingCountry;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillingLanguage()
    {
        return $this->billingLanguage;
    }

    /**
     * @param string $billingLanguage
     * @return PayPlugPaymentEvent
     */
    public function setBillingLanguage(string $billingLanguage): PayPlugPaymentEvent
    {
        $this->billingLanguage = $billingLanguage;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingPersonTitle()
    {
        return $this->shippingPersonTitle;
    }

    /**
     * @param string $shippingPersonTitle
     * @return PayPlugPaymentEvent
     */
    public function setShippingPersonTitle(string $shippingPersonTitle): PayPlugPaymentEvent
    {
        $this->shippingPersonTitle = $shippingPersonTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingFirstName()
    {
        return $this->shippingFirstName;
    }

    /**
     * @param string $shippingFirstName
     * @return PayPlugPaymentEvent
     */
    public function setShippingFirstName(string $shippingFirstName): PayPlugPaymentEvent
    {
        $this->shippingFirstName = $shippingFirstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingLastName()
    {
        return $this->shippingLastName;
    }

    /**
     * @param string $shippingLastName
     * @return PayPlugPaymentEvent
     */
    public function setShippingLastName(string $shippingLastName): PayPlugPaymentEvent
    {
        $this->shippingLastName = $shippingLastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingMobilePhone()
    {
        return $this->shippingMobilePhone;
    }

    /**
     * @param string $shippingMobilePhone
     * @return PayPlugPaymentEvent
     */
    public function setShippingMobilePhone(string $shippingMobilePhone): PayPlugPaymentEvent
    {
        $this->shippingMobilePhone = $shippingMobilePhone;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingLandLinePhone()
    {
        return $this->shippingLandLinePhone;
    }

    /**
     * @param string $shippingLandLinePhone
     * @return PayPlugPaymentEvent
     */
    public function setShippingLandLinePhone(string $shippingLandLinePhone): PayPlugPaymentEvent
    {
        $this->shippingLandLinePhone = $shippingLandLinePhone;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingEmail()
    {
        return $this->shippingEmail;
    }

    /**
     * @param string $shippingEmail
     * @return PayPlugPaymentEvent
     */
    public function setShippingEmail(string $shippingEmail): PayPlugPaymentEvent
    {
        $this->shippingEmail = $shippingEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingAddress1()
    {
        return $this->shippingAddress1;
    }

    /**
     * @param string $shippingAddress1
     * @return PayPlugPaymentEvent
     */
    public function setShippingAddress1(string $shippingAddress1): PayPlugPaymentEvent
    {
        $this->shippingAddress1 = $shippingAddress1;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingAddress2()
    {
        return $this->shippingAddress2;
    }

    /**
     * @param string $shippingAddress2
     * @return PayPlugPaymentEvent
     */
    public function setShippingAddress2(string $shippingAddress2): PayPlugPaymentEvent
    {
        $this->shippingAddress2 = $shippingAddress2;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingPostcode()
    {
        return $this->shippingPostcode;
    }

    /**
     * @param string $shippingPostcode
     * @return PayPlugPaymentEvent
     */
    public function setShippingPostcode(string $shippingPostcode): PayPlugPaymentEvent
    {
        $this->shippingPostcode = $shippingPostcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShippingCompany()
    {
        return $this->shippingCompany;
    }

    /**
     * @param string $shippingCompany
     * @return PayPlugPaymentEvent
     */
    public function setShippingCompany(string $shippingCompany): PayPlugPaymentEvent
    {
        $this->shippingCompany = $shippingCompany;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingCity()
    {
        return $this->shippingCity;
    }

    /**
     * @param string $shippingCity
     * @return PayPlugPaymentEvent
     */
    public function setShippingCity(string $shippingCity): PayPlugPaymentEvent
    {
        $this->shippingCity = $shippingCity;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingState()
    {
        return $this->shippingState;
    }

    /**
     * @param string $shippingState
     * @return PayPlugPaymentEvent
     */
    public function setShippingState(string $shippingState): PayPlugPaymentEvent
    {
        $this->shippingState = $shippingState;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingCountry()
    {
        return $this->shippingCountry;
    }

    /**
     * @param string $shippingCountry
     * @return PayPlugPaymentEvent
     */
    public function setShippingCountry(string $shippingCountry): PayPlugPaymentEvent
    {
        $this->shippingCountry = $shippingCountry;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingLanguage()
    {
        return $this->shippingLanguage;
    }

    /**
     * @param string $shippingLanguage
     * @return PayPlugPaymentEvent
     */
    public function setShippingLanguage(string $shippingLanguage): PayPlugPaymentEvent
    {
        $this->shippingLanguage = $shippingLanguage;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingDeliveryType()
    {
        return $this->shippingDeliveryType;
    }

    /**
     * @param string $shippingDeliveryType
     * @return PayPlugPaymentEvent
     */
    public function setShippingDeliveryType(string $shippingDeliveryType): PayPlugPaymentEvent
    {
        $this->shippingDeliveryType = $shippingDeliveryType;
        return $this;
    }

    /**
     * @return string
     */
    public function getHostedPaymentReturnUrl()
    {
        return $this->hostedPaymentReturnUrl;
    }

    /**
     * @param string $hostedPaymentReturnUrl
     * @return PayPlugPaymentEvent
     */
    public function setHostedPaymentReturnUrl(string $hostedPaymentReturnUrl = null): PayPlugPaymentEvent
    {
        $this->hostedPaymentReturnUrl = $hostedPaymentReturnUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getHostedPaymentCancelUrl()
    {
        return $this->hostedPaymentCancelUrl;
    }

    /**
     * @param string $hostedPaymentCancelUrl
     * @return PayPlugPaymentEvent
     */
    public function setHostedPaymentCancelUrl(string $hostedPaymentCancelUrl = null): PayPlugPaymentEvent
    {
        $this->hostedPaymentCancelUrl = $hostedPaymentCancelUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getHostedPaymentSentBy()
    {
        return $this->hostedPaymentSentBy;
    }

    /**
     * @param string $hostedPaymentSentBy
     * @return PayPlugPaymentEvent
     */
    public function setHostedPaymentSentBy(string $hostedPaymentSentBy): PayPlugPaymentEvent
    {
        $this->hostedPaymentSentBy = $hostedPaymentSentBy;
        return $this;
    }

    /**
     * @return PayPlugProduct[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param PayPlugProduct[] $products
     * @return PayPlugPaymentEvent
     */
    public function setProducts(array $products): PayPlugPaymentEvent
    {
        $this->products = $products;
        return $this;
    }

    /**
     * @param PayPlugProduct $product
     * @return PayPlugPaymentEvent
     */
    public function addProduct(PayPlugProduct $product): PayPlugPaymentEvent
    {
        $this->products[] = $product;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotificationUrl()
    {
        return $this->notificationUrl;
    }

    /**
     * @param string $notificationUrl
     * @return PayPlugPaymentEvent
     */
    public function setNotificationUrl(string $notificationUrl): PayPlugPaymentEvent
    {
        $this->notificationUrl = $notificationUrl;
        return $this;
    }
}
