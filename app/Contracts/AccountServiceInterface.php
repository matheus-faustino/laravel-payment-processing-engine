<?php

namespace App\Contracts;

use App\Data\AccountData;
use App\Models\Account;

interface AccountServiceInterface
{
    public function create(int $userId, AccountData $accountData): Account;
}
