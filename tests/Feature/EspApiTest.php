<?php

namespace Tests\Feature;

use App\Enums\StatusMesin;
use App\Enums\StatusProses;
use App\Models\MesinHistory;
use App\Models\MesinMappingJo;
use App\Models\MesinQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kontrak API yang dipakai test.ino: GET /api/esp/state (saat menyala & "Refresh Data") dan
 * POST /api/esp/status dengan status PRODUKSI / MOULD / SETTER / READY.
 */
class EspApiTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['X-ESP-ID' => 'AABBCCDDEEFF'];

    private function siapkan(string $jenis, string $nama = 'JOKO'): void
    {
        MesinMappingJo::create(['no_jo' => 'JO1', 'no_mesin' => '260', 'qtyjo' => 100, 'status_jo' => 'OPEN']);
        $this->postJson('/api/mesin/260/siapkan', [
            'jenis_operator' => $jenis, 'operator1' => $nama, 'shift' => '1', 'no_jo' => ['JO1'],
        ])->assertCreated();
    }

    public function test_state_returns_mesin_mapped_by_uid_esp_with_display_status(): void
    {
        $mesin = MesinQr::factory()->running('JO1,JO2')->withEsp('9C12B8F23A08')->create(['no_mesin' => '160 A']);
        MesinHistory::factory()->running()->create(['no_mesin' => '160 A', 'operator1' => 'JOKO']);

        $this->getJson('/api/esp/state', ['X-ESP-ID' => '9C12B8F23A08'])
            ->assertOk()
            ->assertJsonPath('esp.no_mesin', $mesin->no_mesin)
            ->assertJsonPath('esp.status', 'PRODUKSI')
            ->assertJsonPath('esp.status_mesin', 'RUNNING')
            ->assertJsonPath('esp.jenis_operator', 'OPERATOR')
            ->assertJsonPath('esp.running_jo', ['JO1', 'JO2'])
            ->assertJsonPath('esp.operator', 'JOKO');
    }

    public function test_state_on_boot_marks_off_mesin_as_ready(): void
    {
        $mesin = MesinQr::factory()->withEsp('AABBCCDDEEFF')->create(['status_mesin' => StatusMesin::Off]);

        $this->getJson('/api/esp/state', self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'READY');

        $mesin->refresh();
        $this->assertSame(StatusMesin::Ready, $mesin->status_mesin);
        $this->assertSame('esp32', $mesin->user_at);
    }

    public function test_state_accepts_id_esp_query_parameter(): void
    {
        MesinQr::factory()->withEsp('AABBCCDDEEFF')->create();

        $this->getJson('/api/esp/state?id_esp=AABBCCDDEEFF')
            ->assertOk()
            ->assertJsonPath('esp.id_esp', 'AABBCCDDEEFF')
            ->assertJsonPath('esp.status', 'READY')
            ->assertJsonPath('esp.operator', null);
    }

    public function test_ping_marks_off_mesin_as_ready(): void
    {
        $mesin = MesinQr::factory()->withEsp('AABBCCDDEEFF')->create(['status_mesin' => StatusMesin::Off]);

        $this->postJson('/api/esp/ping', [], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'READY');

        $this->assertSame(StatusMesin::Ready, $mesin->refresh()->status_mesin);
    }

    public function test_unknown_esp_returns_404(): void
    {
        $this->getJson('/api/esp/state', ['X-ESP-ID' => 'TIDAKADA'])
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->getJson('/api/esp/state')->assertNotFound();
    }

    public function test_produksi_without_operator_is_rejected_with_short_message(): void
    {
        $mesin = MesinQr::factory()->withEsp('AABBCCDDEEFF')->create();

        $this->postJson('/api/esp/status', ['status' => 'PRODUKSI'], self::HEADER)
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Belum ada operator')
            ->assertJsonPath('esp.status', 'READY');

        $this->assertSame(StatusMesin::Ready, $mesin->refresh()->status_mesin);
    }

    public function test_produksi_starts_prepared_operator(): void
    {
        $mesin = MesinQr::factory()->withEsp('AABBCCDDEEFF')->create(['no_mesin' => '260']);
        $this->siapkan('OPERATOR');

        $this->getJson('/api/esp/state', self::HEADER)
            ->assertJsonPath('esp.status', 'READY')
            ->assertJsonPath('esp.jenis_operator', 'OPERATOR')
            ->assertJsonPath('esp.operator', 'JOKO');

        $this->postJson('/api/esp/status', ['status' => 'PRODUKSI'], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'PRODUKSI')
            ->assertJsonPath('esp.operator', 'JOKO')
            ->assertJsonPath('esp.running_jo', ['JO1']);

        $history = MesinHistory::sole();
        $this->assertSame(StatusProses::Running, $history->status_proses);
        $this->assertNotNull($history->start_produksi);
        $this->assertSame('RUNNING', MesinMappingJo::where('no_jo', 'JO1')->value('status_jo')->value);
        $this->assertSame(StatusMesin::Running, $mesin->refresh()->status_mesin);
    }

    public function test_status_only_allowed_for_matching_jenis_operator(): void
    {
        MesinQr::factory()->withEsp('AABBCCDDEEFF')->create(['no_mesin' => '260']);
        $this->siapkan('OPERATOR');

        $this->postJson('/api/esp/status', ['status' => 'mould'], self::HEADER)
            ->assertStatus(409)
            ->assertJsonPath('message', 'MOULD khusus TEKNISI');
        $this->postJson('/api/esp/status', ['status' => 'SETTER'], self::HEADER)
            ->assertStatus(409)
            ->assertJsonPath('message', 'SETTER khusus SETUP');

        $this->assertSame(StatusProses::Pending, MesinHistory::sole()->status_proses);
    }

    public function test_mould_session_keeps_status_mesin_ready_and_selecting_ready_stops_it(): void
    {
        $mesin = MesinQr::factory()->withEsp('AABBCCDDEEFF')->create(['no_mesin' => '260']);
        $this->siapkan('TEKNISI', 'BUDI');

        $this->postJson('/api/esp/status', ['status' => 'MOULD'], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'MOULD')
            ->assertJsonPath('esp.status_mesin', 'READY')
            ->assertJsonPath('esp.jenis_operator', 'TEKNISI')
            ->assertJsonPath('esp.operator', 'BUDI');
        $this->assertNotNull(MesinHistory::sole()->start_mold);

        $this->postJson('/api/esp/status', ['status' => 'READY'], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'READY')
            ->assertJsonPath('esp.running_jo', []);

        $history = MesinHistory::sole();
        $this->assertSame(StatusProses::Finish, $history->status_proses);
        $this->assertNotNull($history->stop_mold);
        $this->assertSame('OPEN', MesinMappingJo::where('no_jo', 'JO1')->value('status_jo')->value);
        $this->assertSame(StatusMesin::Ready, $mesin->refresh()->status_mesin);
    }

    public function test_setter_session_runs_with_status_setter(): void
    {
        MesinQr::factory()->withEsp('AABBCCDDEEFF')->create(['no_mesin' => '260']);
        $this->siapkan('SETUP', 'SETTER1');

        $this->postJson('/api/esp/status', ['status' => 'SETTER'], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'SETTER')
            ->assertJsonPath('esp.status_mesin', 'READY');

        $this->assertNotNull(MesinHistory::sole()->start_setup);
    }

    public function test_ready_from_esp_stops_produksi_session(): void
    {
        $mesin = MesinQr::factory()->running('JO1')->withEsp('AABBCCDDEEFF')->create(['no_mesin' => '260']);
        MesinMappingJo::create(['no_jo' => 'JO1', 'no_mesin' => '260', 'qtyjo' => 100, 'status_jo' => 'RUNNING']);
        $history = MesinHistory::factory()->running()->create(['no_mesin' => '260', 'no_jo' => 'JO1']);

        $this->postJson('/api/esp/status', ['status' => 'READY'], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'READY')
            ->assertJsonPath('esp.running_jo', []);

        $history->refresh();
        $this->assertSame(StatusProses::Finish, $history->status_proses);
        $this->assertSame('00:10:00', $history->mesin_durasi);
        $this->assertSame('esp32', $mesin->refresh()->user_at);
    }

    public function test_rusak_pauses_session_and_produksi_resumes_it(): void
    {
        $mesin = MesinQr::factory()->running('JO1')->withEsp('AABBCCDDEEFF')->create(['no_mesin' => '260']);
        $history = MesinHistory::factory()->running()->create(['no_mesin' => '260', 'no_jo' => 'JO1']);

        $this->postJson('/api/esp/status', ['status' => 'RUSAK'], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'RUSAK');
        $this->postJson('/api/esp/status', ['status' => 'PRODUKSI'], self::HEADER)
            ->assertOk()
            ->assertJsonPath('esp.status', 'PRODUKSI');

        $this->assertSame(StatusMesin::Running, $mesin->refresh()->status_mesin);
        $this->assertSame(StatusProses::Running, $history->refresh()->status_proses);
    }

    public function test_status_rejects_unknown_value(): void
    {
        MesinQr::factory()->withEsp('AABBCCDDEEFF')->create();

        $this->postJson('/api/esp/status', ['status' => 'TERBANG'], self::HEADER)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
