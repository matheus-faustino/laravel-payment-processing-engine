<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->url(),
            'secret' => Str::random(32),
            'is_active' => true,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
