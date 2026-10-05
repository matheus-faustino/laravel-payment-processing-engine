<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AccountData extends Data
{
    public function __construct(
        public int $balance,
        public ?string $currency,
    ) {}
}
