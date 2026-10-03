<?php

namespace App\Models;

use App\Enums\OutboxStatus;
use Database\Factories\OutboxFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('event_type', 'payload', 'status', 'retry_count', 'processed_at')]
class Outbox extends Model
{
    /** @use HasFactory<OutboxFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => OutboxStatus::class,
            'retry_count' => 'integer',
            'processed_at' => 'datetime',
        ];
    }
}
