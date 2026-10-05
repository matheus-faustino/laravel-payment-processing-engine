<?php

namespace App\Services;

use App\Contracts\UserServiceInterface;
use App\Data\UserData;
use App\Models\User;
use Override;

class UserService implements UserServiceInterface
{
    #[Override]
    public function create(UserData $userData): User
    {
        return User::create($userData->toArray());
    }
}
