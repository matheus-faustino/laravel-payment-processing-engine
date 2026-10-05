<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InsufficientBalanceException extends Exception
{
    public function __construct(
        string $message = 'Insufficient balance for this transaction',
        int $code = 422,
        ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => 'INSUFFICIENT_BALANCE',
            'message' => $this->getMessage(),
        ], $this->getCode());
    }
}
