<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    /**
     * An open stay. tenant_id is copied from the room, and the child belongs to that same tenant.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'tenant_id' => function (array $attributes) {
                return Room::query()->findOrFail($attributes['room_id'])->tenant_id;
            },
            'child_id' => function (array $attributes) {
                return Child::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                ])->id;
            },
            'checked_in_at' => now(),
            'checked_out_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_out_at' => now(),
        ]);
    }
}
