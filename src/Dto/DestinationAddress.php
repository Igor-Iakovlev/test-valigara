<?php

namespace App\Dto;

use PrinsFrank\Standards\Country\CountryAlpha2;
use Symfony\Component\Validator\Constraints as Assert;

class DestinationAddress implements DtoInterface
{
    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $addressLine1;

    #[Assert\Type('string')]
    public ?string $addressLine2;

    #[Assert\Type('string')]
    public ?string $addressLine3;

    #[Assert\Type('string')]
    public ?string $city;

    #[Assert\Type('string')]
    public ?string $districtOrCountry;

    #[Assert\Type('string')]
    public ?string $stateOrRegion;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $postalCode;

    #[Assert\NotNull]
    #[Assert\Type(CountryAlpha2::class)]
    public CountryAlpha2 $countryCode;

    #[Assert\Type('string')]
    public ?string $phone;
}
