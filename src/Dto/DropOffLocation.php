<?php

namespace App\Dto;

use App\Enum\DropOffLocationType;
use Symfony\Component\Validator\Constraints as Assert;

class DropOffLocation implements DtoInterface
{
    #[Assert\Type(DropOffLocationType::class)]
    public DropOffLocationType $type;

    #[Assert\Type('array')]
    public ?array $attributes;
}
