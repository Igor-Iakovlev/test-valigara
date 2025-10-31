<?php

namespace App\Enum;

enum FulfillmentPolicy: string
{
    case FillOrKill = 'FillOrKill';
    case FillAll = 'FillAll';
    case FillAllAvailable = 'FillAllAvailable';
}
