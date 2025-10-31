<?php

namespace App\Dto;

use DateTimeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DeliveryWindow extends AbstractDto
{
    #[Assert\NotNull]
    #[Assert\Type(DateTimeInterface::class)]
    public DateTimeInterface $startDate;

    #[Assert\NotNull]
    #[Assert\Type(DateTimeInterface::class)]
    public DateTimeInterface $endDate;
}
