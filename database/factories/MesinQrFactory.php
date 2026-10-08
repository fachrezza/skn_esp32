<?php

namespace Database\Factories;

use App\Enums\StatusMesin;
use App\Models\MesinQr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MesinQr>
 */
class MesinQrFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_mesin' => fake()->unique()->numerify('### ').fake()->randomLetter(),
            'nama_mesin' => fake()->randomElement(['Haitian', 'Weltech', 'Shin Well', 'Enafive']),
            'note_mesin' => null,
            'pin_mesin' => '123',
            'status_mesin' => StatusMesin::Ready,
            'running_jo' => null,
            'uid_esp' => null,
            'user_at' => 'dashboard',
        ];
    }

    public function running(string $runningJo = 'JO1QA00001'): static
    {
        return $this->state(fn () => [
            'status_mesin' => StatusMesin::Running,
            'running_jo' => $runningJo,
        ]);
    }

    public function withEsp(?string $uid = null): static
    {
        return $this->state(fn () => [
            'uid_esp' => $uid ?? strtoupper(fake()->unique()->regexify('[0-9A-F]{12}')),
        ]);
    }
}
