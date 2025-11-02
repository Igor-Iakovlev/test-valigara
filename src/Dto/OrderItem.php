<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class OrderItem implements DtoInterface
{
    #[Assert\NotNull]
    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 50)]
    public string $sellerSku;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 50)]
    public string $sellerFulfillmentOrderItemId;

    #[Assert\NotNull]
    #[Assert\Type('int')]
    public int $quantity;

    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 512)]
    public ?string $giftMessage;

    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 250)]
    public ?string $displayableComment;

    #[Assert\Type('string')]
    public ?string $fulfillmentNetworkSku;

    #[Assert\Type(CurrencyValue::class)]
    #[Assert\Valid]
    public ?CurrencyValue $perUnitDeclaredValue;

    #[Assert\Type(CurrencyValue::class)]
    #[Assert\Valid]
    public ?CurrencyValue $perUnitPrice;

    #[Assert\Type(CurrencyValue::class)]
    #[Assert\Valid]
    public ?CurrencyValue $perUnitTax;
}
