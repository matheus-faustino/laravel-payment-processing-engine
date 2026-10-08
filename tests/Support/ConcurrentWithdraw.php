<?php

namespace Tests\Support;

use App\Contracts\TransactionServiceInterface;
use App\Data\TransactionData;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Str;

/**
 * Concurrency tasks are serialized and executed in an isolated PHP process.
 * The task closure must be defined inside an autoloadable class (not inside
 * the Pest-generated test class, which does not exist in the child process).
 */
final class ConcurrentWithdraw
{
    public static function task(string $accountId, int $amount, ?string $idempotencyKey = null): \Closure
    {
        return function () use ($accountId, $amount, $idempotencyKey): string {
            return rescue(function () use ($accountId, $amount, $idempotencyKey): string {
                app(TransactionServiceInterface::class)->execute(
                    TransactionData::from(Transaction::factory()->make([
                        'amount' => $amount,
                        'type' => TransactionType::WITHDRAW,
                        'source_account_id' => $accountId,
                    ])),
                    $idempotencyKey ?? (string) Str::uuid(),
                );

                return 'processed';
            }, 'failed', false);
        };
    }
}
