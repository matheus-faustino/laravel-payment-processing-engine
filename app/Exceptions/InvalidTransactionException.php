<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTransactionException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => 'INVALID_TRANSACTION',
            'message' => $this->message,
        ], $this->code);
    }
}
