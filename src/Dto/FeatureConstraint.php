<?php

namespace App\Dto;

use App\Enum\FeatureFulfillmentPolicy;
use App\Enum\FeatureName;
use Symfony\Component\Validator\Constraints as Assert;

class FeatureConstraint implements DtoInterface
{
    #[Assert\Type(FeatureName::class)]
    public ?FeatureName $featureName;

    #[Assert\Type(FeatureFulfillmentPolicy::class)]
    public ?FeatureFulfillmentPolicy $featureFulfillmentPolicy;
}
