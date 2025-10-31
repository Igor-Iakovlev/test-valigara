<?php

namespace App\Dto;

use App\Data\BuyerInterface;
use App\Enum\FulfillmentAction;
use App\Enum\FulfillmentPolicy;
use App\Enum\ShippingSpeedCategory;
use DateTime;
use DateTimeInterface;
use PrinsFrank\Standards\Country\CountryAlpha2;
use PrinsFrank\Standards\Currency\CurrencyAlpha3;
use Symfony\Component\Validator\Constraints as Assert;

class CreateFullfillmentOrderParameters extends AbstractDto
{
    public ?string $marketplaceId;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 40)]
    public string $sellerFulfillmentOrderId;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 40)]
    public string $displayableOrderId;

    #[Assert\NotNull]
    #[Assert\Type(DateTimeInterface::class)]
    public DateTimeInterface $displayableOrderDate;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 750)]
    public string $displayableOrderComment;

    #[Assert\NotNull]
    #[Assert\Type(ShippingSpeedCategory::class)]
    public ShippingSpeedCategory $shippingSpeedCategory = ShippingSpeedCategory::Standard;

    #[Assert\Type(DeliveryWindow::class)]
    #[Assert\Valid]
    public ?DeliveryWindow $deliveryWindow;

    #[Assert\NotNull]
    #[Assert\Type(DestinationAddress::class)]
    #[Assert\Valid]
    public DestinationAddress $destinationAddress;

    #[Assert\Type(DeliveryPreferences::class)]
    #[Assert\Valid]
    public ?DeliveryPreferences $deliveryPreferences;

    #[Assert\Type(FulfillmentAction::class)]
    public ?FulfillmentAction $fulfillmentAction;

    #[Assert\Type(FulfillmentPolicy::class)]
    public ?FulfillmentPolicy $fulfillmentPolicy;

    #[Assert\Type(CodSettings::class)]
    #[Assert\Valid]
    public ?CodSettings $codSettings;

    #[Assert\Type(CountryAlpha2::class)]
    public ?CountryAlpha2 $shipFromCountryCode;

    #[Assert\Type('array')]
    #[Assert\All([new Assert\Email()])]
    public ?array $notificationEmails;

    /**
     * @var FeatureConstraint[]|null
     */
    #[Assert\Type('array')]
    #[Assert\All([new Assert\Type(FeatureConstraint::class)])]
    #[Assert\Valid]
    public ?array $featureConstraints;

    /**
     * @var OrderItem[]
     */
    #[Assert\NotNull]
    #[Assert\Type('array')]
    #[Assert\All([new Assert\Type(OrderItem::class)])]
    #[Assert\Valid]
    public array $items;

    /**
     * @var PaymentInformationItem[]|null
     */
    #[Assert\Type('array')]
    #[Assert\All([new Assert\Type(PaymentInformationItem::class)])]
    #[Assert\Valid]
    public ?array $paymentInformation;

    /**
     * @param array $orderData
     * @return self
     */
    public function fillFromOrderData(array $orderData): self
    {
        $this->sellerFulfillmentOrderId = $orderData['order_unique'] ?? null;
        $this->displayableOrderId = $orderData['order_id'] ?? null;
        $this->displayableOrderDate = DateTime::createFromFormat('Y-m-d', $orderData['order_date']);
        $this->displayableOrderComment = $orderData['comments'] ?? null;
        $this->destinationAddress = new DestinationAddress();
        $this->destinationAddress->name = $orderData['buyer_name'] ?? null;
        $this->destinationAddress->addressLine1 = $orderData['shipping_street'] ?? null;
        $this->destinationAddress->city = $orderData['shipping_city'] ?? null;
        $this->destinationAddress->countryCode = CountryAlpha2::tryFrom($orderData['shipping_country'] ?? null);
        $this->destinationAddress->stateOrRegion = $orderData['shipping_state'] ?? null;
        $this->destinationAddress->postalCode = $orderData['shipping_zip'] ?? null;
        $items = [];
        foreach ($orderData['products'] as $product) {
            $currency = CurrencyAlpha3::tryFrom($orderData['currency']);

            $item = new OrderItem();
            $item->sellerSku = $product['sku'] ?? null;
            $item->sellerFulfillmentOrderItemId = $product['order_product_id'] ?? null;
            $item->quantity = (int) ($product['ammount'] ?? 0);
            if ($product['original_price']) {
                $item->perUnitDeclaredValue = new CurrencyValue();
                $item->perUnitDeclaredValue->value = $product['original_price'];
                $item->perUnitDeclaredValue->currencyCode = $currency;
            }
            if ($product['buying_price']) {
                $item->perUnitPrice = new CurrencyValue();
                $item->perUnitPrice->value = $product['buying_price'];
                $item->perUnitPrice->currencyCode = $currency;
            }
            $items[] = $item;
        }
        $this->items = $items;

        return $this;
    }

    /**
     * @param BuyerInterface $buyer
     * @return $this
     */
    public function fillFromBuyer(BuyerInterface $buyer): self
    {
        $this->notificationEmails = isset($buyer['email']) ? [$buyer['email']] : null;
        $this->destinationAddress->phone = $buyer['phone'];
        return $this;
    }
}
