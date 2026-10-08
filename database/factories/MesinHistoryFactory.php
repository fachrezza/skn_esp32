<?php

namespace Database\Factories;

use App\Enums\StatusProses;
use App\Models\MesinHistory;
use App\Models\MesinQr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MesinHistory>
 */
class MesinHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->subHours(fake()->numberBetween(2, 48));

        return [
            'no_mesin' => MesinQr::factory(),
            'tgl_running' => $start->toDateString(),
            'jenis_operator' => 'OPERATOR',
            'shift' => '1',
            'operator1' => strtoupper(fake()->firstName()),
            'no_jo' => 'JO1QA'.fake()->numerify('#####'),
            'mesin_start' => $start,
            'mesin_stop' => $start->copy()->addHour(),
            'mesin_durasi' => '01:00:00',
            'good' => fake()->numberBetween(0, 500),
            'ng' => fake()->numberBetween(0, 20),
            'sisa_jo' => 0,
            'status_proses' => StatusProses::Finish,
            'user_at' => 'dashboard',
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'mesin_start' => now()->subMinutes(10),
            'mesin_stop' => null,
            'mesin_durasi' => null,
            'status_proses' => StatusProses::Running,
        ]);
    }
}
