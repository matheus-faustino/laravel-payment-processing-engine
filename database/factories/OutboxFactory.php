<?php

namespace Database\Factories;

use App\Enums\OutboxStatus;
use App\Models\Outbox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outbox>
 */
class OutboxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type' => 'testing',
            'payload' => [
                'test' => 'testing',
            ],
            'status' => $status = fake()->randomElement(OutboxStatus::cases()),
            'retry_count' => 1,
            'processed_at' => $status == OutboxStatus::PROCESSED ? now() : null,
        ];
    }
}
