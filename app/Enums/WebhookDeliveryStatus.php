<?php

namespace App\Enums;

enum WebhookDeliveryStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case RETRYING = 'retrying';
}
