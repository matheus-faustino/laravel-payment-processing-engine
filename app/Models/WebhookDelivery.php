<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Database\Factories\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('webhook_endpoint_id', 'event_type', 'payload', 'response_status', 'response_body', 'attempt', 'status')]
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_status' => 'integer',
            'attempt' => 'integer',
            'status' => WebhookDeliveryStatus::class,
        ];
    }

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }
}
