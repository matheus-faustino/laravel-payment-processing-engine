<?php

use App\Contracts\TransactionServiceInterface;
use App\Data\TransactionData;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->transactionService = app(TransactionServiceInterface::class);
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

});
