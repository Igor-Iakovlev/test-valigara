<?php

namespace App\Dto;

use PrinsFrank\Standards\Currency\CurrencyAlpha3;
use Symfony\Component\Validator\Constraints as Assert;

class CurrencyValue implements DtoInterface
{
    #[Assert\NotNull]
    #[Assert\Type(CurrencyAlpha3::class)]
    public CurrencyAlpha3 $currencyCode;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $value;
}
