<?php

namespace App\Services;

use App\Contracts\AccountServiceInterface;
use App\Data\AccountData;
use App\Models\Account;
use Override;

class AccountService implements AccountServiceInterface
{
    #[Override]
    public function create(int $userId, AccountData $accountData): Account
    {
        return Account::create(['user_id' => $userId, ...$accountData->toArray()]);
    }
}
