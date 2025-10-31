<?php

namespace App\Enum;

enum ShippingSpeedCategory: string
{
    case Standard = 'Standard';
    case Expedited = 'Expedited';
    case Priority = 'Priority';
    case ScheduledDelivery = 'ScheduledDelivery';
}
