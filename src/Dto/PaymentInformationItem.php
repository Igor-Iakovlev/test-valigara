<?php

namespace App\Dto;

use DateTimeInterface;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentInformationItem implements DtoInterface
{
    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $paymentTransactionId;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $paymentMode;

    #[Assert\NotNull]
    #[Assert\Type(DateTimeInterface::class)]
    public DateTimeInterface $paymentDate;
}
