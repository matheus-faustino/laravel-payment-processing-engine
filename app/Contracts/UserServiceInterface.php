<?php

namespace App\Contracts;

use App\Data\UserData;
use App\Models\User;

interface UserServiceInterface
{
    public function create(UserData $userData): User;
}
