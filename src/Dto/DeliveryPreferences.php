<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DeliveryPreferences extends AbstractDto
{
    #[Assert\Type('string')]
    public ?string $deliveryInstructions;

    #[Assert\Type(DropOffLocation::class)]
    public ?DropOffLocation $dropOffLocation;
}
