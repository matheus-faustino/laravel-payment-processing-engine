<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('source_account_id', 'destination_account_id', 'amount', 'type', 'status', 'idempotency_key')]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }
}
