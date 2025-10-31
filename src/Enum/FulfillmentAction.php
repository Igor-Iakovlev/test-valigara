<?php

namespace App\Enum;

enum FulfillmentAction: string
{
    case Ship = 'Ship';
    case Hold = 'Hold';
}
