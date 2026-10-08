<?php

use App\Contracts\TransactionServiceInterface;
use App\Data\TransactionData;
use App\Enums\OutboxStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransactionException;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->transactionService = app(TransactionServiceInterface::class);
});

test('cant execute transactions with amount lower or equals to zero', function () {
    $account = Account::factory()->create([
        'balance' => 10000, // 100.00
    ]);

    $transaction = Transaction::factory()->make([
        'amount' => 0, // 50.00
        'type' => TransactionType::DEPOSIT,
        'destination_account_id' => $account->id,
    ]);

    $transactionData = TransactionData::from($transaction);

    expect(fn () => ($this->transactionService->execute($transactionData, Str::uuid())))->toThrow(InvalidTransactionException::class);

    $this->assertDatabaseMissing('transactions', [
        'amount' => 0,
        'type' => TransactionType::DEPOSIT,
        'destination_account_id' => $account->id,
    ]);

    $this->assertDatabaseCount('outboxes', 0);
});

test('cant execute transactions between same account', function () {
    $sourceAccount = Account::factory()->create([
        'balance' => 10000, // 100.00
    ]);

    $transaction = Transaction::factory()->make([
        'amount' => 5000, // 50.00
        'type' => TransactionType::TRANSFER,
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $sourceAccount->id,
    ]);

    $transactionData = TransactionData::from($transaction);

    expect(fn () => $this->transactionService->execute($transactionData, Str::uuid()))->toThrow(InvalidTransactionException::class);

    $this->assertDatabaseMissing('transactions', [
        'amount' => 5000,
        'type' => TransactionType::TRANSFER,
        'status' => TransactionStatus::PROCESSED,
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $sourceAccount->id,
    ]);

    $this->assertDatabaseCount('outboxes', 0);
});

test('can withdraw from an account', function () {

    $account = Account::factory()->create([
        'balance' => 10000, // 100.00
    ]);

    $transaction = Transaction::factory()->make([
        'amount' => 5000, // 50.00
        'type' => TransactionType::WITHDRAW,
        'source_account_id' => $account->id,
    ]);

    $transactionData = TransactionData::from($transaction);

    $this->transactionService->execute($transactionData, Str::uuid());

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'balance' => 5000,
    ]);

    $this->assertDatabaseHas('transactions', [
        'amount' => 5000,
        'source_account_id' => $account->id,
    ]);

    $this->assertDatabaseHas('outboxes', [
        'event_type' => 'transaction.processed',
        'payload->transaction->amount' => 5000,
        'payload->transaction->type' => TransactionType::WITHDRAW,
        'payload->transaction->status' => TransactionStatus::PROCESSED,
        'payload->transaction->source_account_id' => $account->id,
        'status' => OutboxStatus::PENDING,
        'retry_count' => 0,
    ]);
});

test('withdraw without sufficient balance throws exception', function () {

    $account = Account::factory()->create([
        'balance' => 10000, // 100.00
    ]);

    $transaction = Transaction::factory()->make([
        'amount' => 15000, // 150.00
        'type' => TransactionType::WITHDRAW,
        'source_account_id' => $account->id,
    ]);

    $transactionData = TransactionData::from($transaction);

    expect(fn () => $this->transactionService->execute($transactionData, Str::uuid()))->toThrow(InsufficientBalanceException::class);

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'balance' => 10000,
    ]);

    $this->assertDatabaseHas('transactions', [
        'amount' => 15000,
        'type' => TransactionType::WITHDRAW,
        'status' => TransactionStatus::FAILED,
        'source_account_id' => $account->id,
    ]);

    $this->assertDatabaseHas('outboxes', [
        'event_type' => 'transaction.failed',
        'payload->transaction->amount' => 15000,
        'payload->transaction->type' => TransactionType::WITHDRAW,
        'payload->transaction->status' => TransactionStatus::FAILED,
        'payload->transaction->source_account_id' => $account->id,
        'payload->error' => (new InsufficientBalanceException)->getMessage(),
        'status' => OutboxStatus::PENDING,
        'retry_count' => 0,
    ]);
});

test('can deposit into an account', function () {

    $account = Account::factory()->create([
        'balance' => 0,
    ]);

    $transaction = Transaction::factory()->make([
        'amount' => 5000, // 50.00
        'type' => TransactionType::DEPOSIT,
        'destination_account_id' => $account->id,
    ]);

    $transactionData = TransactionData::from($transaction);

    $this->transactionService->execute($transactionData, Str::uuid());

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'balance' => 5000, // 50.00
    ]);

    $this->assertDatabaseHas('transactions', [
        'amount' => 5000,
        'type' => TransactionType::DEPOSIT,
        'status' => TransactionStatus::PROCESSED,
        'destination_account_id' => $account->id,
    ]);

    $this->assertDatabaseHas('outboxes', [
        'event_type' => 'transaction.processed',
        'payload->transaction->amount' => 5000,
        'payload->transaction->type' => TransactionType::DEPOSIT,
        'payload->transaction->destination_account_id' => $account->id,
        'status' => OutboxStatus::PENDING,
        'retry_count' => 0,
    ]);
});

test('can create transactions between accounts', function () {

    $sourceAccount = Account::factory()->create([
        'balance' => 10000, // 100.00
    ]);

    $destinationAccount = Account::factory()->create([
        'balance' => 0,
    ]);

    $transaction = Transaction::factory()->make([
        'amount' => 5000, // 50.00
        'type' => TransactionType::TRANSFER,
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $destinationAccount->id,
    ]);

    $transactionData = TransactionData::from($transaction);

    $this->transactionService->execute($transactionData, Str::uuid());

    $this->assertDatabaseHas('accounts', [
        'id' => $sourceAccount->id,
        'balance' => 5000, // 50.00
    ]);

    $this->assertDatabaseHas('accounts', [
        'id' => $destinationAccount->id,
        'balance' => 5000, // 50.00
    ]);

    $this->assertDatabaseHas('transactions', [
        'amount' => 5000,
        'type' => TransactionType::TRANSFER,
        'status' => TransactionStatus::PROCESSED,
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $destinationAccount->id,
    ]);

    $this->assertDatabaseHas('outboxes', [
        'event_type' => 'transaction.processed',
        'payload->transaction->amount' => 5000,
        'payload->transaction->type' => TransactionType::TRANSFER,
        'payload->transaction->source_account_id' => $sourceAccount->id,
        'payload->transaction->destination_account_id' => $destinationAccount->id,
        'status' => OutboxStatus::PENDING,
        'retry_count' => 0,
    ]);
});

test('transaction without sufficient balance throws exception', function () {

    $sourceAccount = Account::factory()->create([
        'balance' => 10000, // 100.00
    ]);

    $destinationAccount = Account::factory()->create([
        'balance' => 0,
    ]);

    $transaction = Transaction::factory()->make([
        'amount' => 15000, // 150.00
        'type' => TransactionType::TRANSFER,
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $destinationAccount->id,
    ]);

    $transactionData = TransactionData::from($transaction);

    expect(fn () => $this->transactionService->execute($transactionData, Str::uuid()))->toThrow(InsufficientBalanceException::class);

    $this->assertDatabaseHas('accounts', [
        'id' => $sourceAccount->id,
        'balance' => 10000, // 100.00
    ]);

    $this->assertDatabaseHas('accounts', [
        'id' => $destinationAccount->id,
        'balance' => 0,
    ]);

    $this->assertDatabaseHas('transactions', [
        'amount' => 15000,
        'type' => TransactionType::TRANSFER,
        'status' => TransactionStatus::FAILED,
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $destinationAccount->id,
    ]);

    $this->assertDatabaseHas('outboxes', [
        'event_type' => 'transaction.failed',
        'payload->transaction->amount' => 15000,
        'payload->transaction->type' => TransactionType::TRANSFER,
        'payload->transaction->status' => TransactionStatus::FAILED,
        'payload->transaction->source_account_id' => $sourceAccount->id,
        'payload->transaction->destination_account_id' => $destinationAccount->id,
        'payload->error' => (new InsufficientBalanceException)->getMessage(),
        'status' => OutboxStatus::PENDING,
        'retry_count' => 0,
    ]);
});
