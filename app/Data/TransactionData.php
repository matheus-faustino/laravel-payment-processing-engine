<?php

namespace App\Data;

use App\Enums\TransactionType;
use Spatie\LaravelData\Data;

class TransactionData extends Data
{
    public function __construct(
        public ?string $sourceAccountId,
        public ?string $destinationAccountId,
        public int $amount,
        public TransactionType $type,
    ) {}
}
