<?php

namespace PayPlugModule\Event;

use PayPlugModule\Model\PayPlugModuleDeliveryType;
use PayPlugModule\PayPlugModule;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Base\OrderProduct;
use Thelia\Model\Base\OrderProductTax;
use Thelia\Model\ConfigQuery;

class PayPlugProduct
{
    /** @var string */
    protected $brand;

    /** @var string */
    protected $expectedDeliveryDate;

    /** @var string */
    protected $deliveryLabel;

    /** @var string */
    protected $deliveryType;

    /** @var string */
    protected $merchantItemId;

    /** @var string */
    protected $name;

    /** @var integer */
    protected $price;

    /** @var integer */
    protected $quantity;

    /** @var integer */
    protected $totalAmount;

    public function buildFromOrderProduct(OrderProduct $orderProduct, PayPlugModuleDeliveryType $payPlugModuleDeliveryType = null)
    {
        $storeName = ConfigQuery::read(
            'store_name',
            Translator::getInstance()->trans(
                'Unknown',
                [],
                PayPlugModule::DOMAIN_NAME
            )
        );

        $deliveryType = $payPlugModuleDeliveryType !== null ? $payPlugModuleDeliveryType->getDeliveryType() : 'carrier';
        // Brand can't be find from order product but it's required so set store name as brand or "Unknown"
        $this->setBrand($storeName);
        $this->setExpectedDeliveryDate(date('Y-m-d'));
        $this->setDeliveryLabel($storeName);
        $this->setDeliveryType($deliveryType);
        $this->setMerchantItemId($orderProduct->getId());
        $this->setName($orderProduct->getTitle());

        $orderProductTaxes = $orderProduct->getOrderProductTaxes();
        $tax = array_reduce(
            iterator_to_array($orderProductTaxes),
            function ($accumulator, OrderProductTax $orderProductTax) {
                return $accumulator + $orderProductTax->getAmount();
            },
            0
        );
        $promoTax = array_reduce(
            iterator_to_array($orderProductTaxes),
            function ($accumulator, OrderProductTax $orderProductTax) {
                return $accumulator + $orderProductTax->getPromoAmount();
            },
            0
        );

        $taxedPrice = round((float) $orderProduct->getPrice() + $tax, 2);
        $taxedPromoPrice = round((float) $orderProduct->getPromoPrice() + $promoTax, 2);

        $price = $orderProduct->getWasInPromo() ? $taxedPromoPrice : $taxedPrice;
        $this->setPrice($price * 100);
        $this->setQuantity($orderProduct->getQuantity());
        $this->setTotalAmount($price * $orderProduct->getQuantity() * 100);

        return $this;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     * @return PayPlugProduct
     */
    public function setBrand(string $brand): PayPlugProduct
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpectedDeliveryDate()
    {
        return $this->expectedDeliveryDate;
    }

    /**
     * @param string $expectedDeliveryDate
     * @return PayPlugProduct
     */
    public function setExpectedDeliveryDate(string $expectedDeliveryDate): PayPlugProduct
    {
        $this->expectedDeliveryDate = $expectedDeliveryDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryLabel()
    {
        return $this->deliveryLabel;
    }

    /**
     * @param string $deliveryLabel
     * @return PayPlugProduct
     */
    public function setDeliveryLabel(string $deliveryLabel): PayPlugProduct
    {
        $this->deliveryLabel = $deliveryLabel;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryType()
    {
        return $this->deliveryType;
    }

    /**
     * @param string $deliveryType
     * @return PayPlugProduct
     */
    public function setDeliveryType(string $deliveryType): PayPlugProduct
    {
        $this->deliveryType = $deliveryType;
        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantItemId()
    {
        return $this->merchantItemId;
    }

    /**
     * @param string $merchantItemId
     * @return PayPlugProduct
     */
    public function setMerchantItemId(string $merchantItemId): PayPlugProduct
    {
        $this->merchantItemId = $merchantItemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PayPlugProduct
     */
    public function setName(string $name): PayPlugProduct
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int $price
     * @return PayPlugProduct
     */
    public function setPrice(int $price): PayPlugProduct
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return PayPlugProduct
     */
    public function setQuantity(int $quantity): PayPlugProduct
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param int $totalAmount
     * @return PayPlugProduct
     */
    public function setTotalAmount(int $totalAmount): PayPlugProduct
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
}