<?php

namespace Tests\Feature;

use App\Enums\StatusMesin;
use App\Models\MesinHistory;
use App\Models\MesinQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EspApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_mesin_mapped_by_uid_esp(): void
    {
        $mesin = MesinQr::factory()->running('JO1,JO2')->withEsp('9C12B8F23A08')->create(['no_mesin' => '160 A']);
        MesinHistory::factory()->running()->create(['no_mesin' => '160 A', 'operator1' => 'JOKO']);

        $this->postJson('/api/esp/ping', [], ['X-ESP-ID' => '9C12B8F23A08'])
            ->assertOk()
            ->assertJsonPath('esp.no_mesin', $mesin->no_mesin)
            ->assertJsonPath('esp.status', 'RUNNING')
            ->assertJsonPath('esp.running_jo', ['JO1', 'JO2'])
            ->assertJsonPath('esp.operator', 'JOKO');
    }

    public function test_state_accepts_id_esp_query_parameter(): void
    {
        MesinQr::factory()->withEsp('AABBCCDDEEFF')->create();

        $this->getJson('/api/esp/state?id_esp=AABBCCDDEEFF')
            ->assertOk()
            ->assertJsonPath('esp.id_esp', 'AABBCCDDEEFF')
            ->assertJsonPath('esp.status', 'READY');
    }

    public function test_unknown_esp_returns_404(): void
    {
        $this->getJson('/api/esp/state', ['X-ESP-ID' => 'TIDAKADA'])
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->getJson('/api/esp/state')->assertNotFound();
    }

    public function test_status_updates_status_mesin(): void
    {
        $mesin = MesinQr::factory()->withEsp('AABBCCDDEEFF')->create();

        $this->postJson('/api/esp/status', ['status' => 'maintance'], ['X-ESP-ID' => 'AABBCCDDEEFF'])
            ->assertOk()
            ->assertJsonPath('esp.status', 'MAINTANCE');

        $mesin->refresh();
        $this->assertSame(StatusMesin::Maintance, $mesin->status_mesin);
        $this->assertSame('esp32', $mesin->user_at);
    }

    public function test_status_rejects_value_outside_database_enum(): void
    {
        MesinQr::factory()->withEsp('AABBCCDDEEFF')->create();

        $this->postJson('/api/esp/status', ['status' => 'MOULD'], ['X-ESP-ID' => 'AABBCCDDEEFF'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
