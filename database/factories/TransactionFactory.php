<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_account_id' => Account::factory(),
            'destination_account_id' => Account::factory(),
            'amount' => fake()->numberBetween(1, 5000),
            'type' => TransactionType::TRANSFER,
            'status' => TransactionStatus::PENDING,
            'idempotency_key' => fake()->uuid(),
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_account_id' => null,
            'destination_account_id' => Account::factory(),
            'type' => TransactionType::DEPOSIT,
        ]);
    }

    public function withdraw(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_account_id' => Account::factory(),
            'destination_account_id' => null,
            'type' => TransactionType::WITHDRAW,
        ]);
    }
}
