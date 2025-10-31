<?php

namespace App\Enum;

enum FeatureFulfillmentPolicy: string
{
    case Required = 'Required';
    case NotRequired = 'NotRequired';
}
