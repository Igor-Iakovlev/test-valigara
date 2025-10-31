<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CodSettings extends AbstractDto
{
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    public bool $isCodRequired;

    #[Assert\Type(CurrencyValue::class)]
    #[Assert\Valid]
    public ?CurrencyValue $codCharge;

    #[Assert\Type(CurrencyValue::class)]
    #[Assert\Valid]
    public ?CurrencyValue $codChargeTax;

    #[Assert\Type(CurrencyValue::class)]
    #[Assert\Valid]
    public ?CurrencyValue $shippingCharge;

    #[Assert\Type(CurrencyValue::class)]
    #[Assert\Valid]
    public ?CurrencyValue $shippingChargeTax;
}
