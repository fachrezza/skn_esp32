<?php

namespace Tests\Feature;

use App\Enums\StatusMesin;
use App\Enums\StatusProses;
use App\Models\MesinHistory;
use App\Models\MesinMappingJo;
use App\Models\MesinQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduksiDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function buatJo(string $noMesin, string $noJo, string $status = 'OPEN'): void
    {
        MesinMappingJo::create(['no_jo' => $noJo, 'no_mesin' => $noMesin, 'qtyjo' => 100, 'status_jo' => $status]);
    }

    private function statusJo(string $noMesin, string $noJo): StatusProses
    {
        return MesinMappingJo::where('no_mesin', $noMesin)->where('no_jo', $noJo)->first()->status_jo;
    }

    public function test_dashboard_page_renders(): void
    {
        $this->get('/dashboard')->assertOk()->assertSee('Dashboard Mesin');
    }

    public function test_start_creates_running_history_and_marks_jo_running(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');
        $this->buatJo('260', 'JO2');

        $this->postJson('/api/mesin/260/start', [
            'jenis_operator' => 'OPERATOR',
            'operator1' => 'JOKO',
            'shift' => '1',
            'no_jo' => ['JO1', 'JO2'],
        ])
            ->assertCreated()
            ->assertJsonPath('mesin.status_mesin', 'RUNNING')
            ->assertJsonPath('mesin.running_jo', ['JO1', 'JO2'])
            ->assertJsonPath('mesin.active_history.operator1', 'JOKO');

        $history = MesinHistory::sole();
        $this->assertSame('JO1,JO2', $history->no_jo);
        $this->assertSame(StatusProses::Running, $history->status_proses);
        $this->assertNull($history->mesin_stop);
        $this->assertSame(StatusProses::Running, $this->statusJo('260', 'JO1'));
    }

    public function test_start_as_teknisi_runs_mold_session_with_status_ready(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');

        $this->postJson('/api/mesin/260/start', [
            'jenis_operator' => 'TEKNISI',
            'operator1' => 'BUDI',
            'shift' => '2',
            'no_jo' => ['JO1'],
        ])
            ->assertCreated()
            ->assertJsonPath('mesin.status_mesin', 'READY')
            ->assertJsonPath('mesin.active_history.jenis_operator', 'TEKNISI')
            ->assertJsonPath('mesin.operator', 'BUDI');

        $history = MesinHistory::sole();
        $this->assertSame('BUDI', $history->nama_teksini);
        $this->assertSame(StatusProses::Running, $history->status_proses);
        $this->assertNotNull($history->start_mold);
        $this->assertNull($history->start_produksi);
    }

    public function test_stop_setup_session_fills_stop_setup(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');
        $this->postJson('/api/mesin/260/start', [
            'jenis_operator' => 'SETUP', 'operator1' => 'SETTER', 'shift' => '1', 'no_jo' => ['JO1'],
        ])->assertCreated()->assertJsonPath('mesin.status_mesin', 'READY');

        $this->postJson('/api/mesin/260/stop')->assertOk()->assertJsonPath('mesin.status_mesin', 'READY');

        $history = MesinHistory::sole();
        $this->assertSame(StatusProses::Finish, $history->status_proses);
        $this->assertNotNull($history->start_setup);
        $this->assertNotNull($history->stop_setup);
    }

    public function test_start_rejects_jo_from_other_mesin_or_finished_and_when_already_running(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        MesinQr::factory()->create(['no_mesin' => '300']);
        $this->buatJo('300', 'JO-LAIN');
        $this->buatJo('260', 'JO-SELESAI', 'FINISH');
        $this->buatJo('260', 'JO1');

        $payload = ['jenis_operator' => 'OPERATOR', 'operator1' => 'JOKO', 'shift' => '1'];

        $this->postJson('/api/mesin/260/start', $payload + ['no_jo' => ['JO-LAIN']])->assertJsonValidationErrors('no_jo');
        $this->postJson('/api/mesin/260/start', $payload + ['no_jo' => ['JO-SELESAI']])->assertJsonValidationErrors('no_jo');
        $this->postJson('/api/mesin/260/start', $payload)->assertJsonValidationErrors('no_jo');

        $this->postJson('/api/mesin/260/start', $payload + ['no_jo' => ['JO1']])->assertCreated();
        $this->postJson('/api/mesin/260/start', $payload + ['no_jo' => ['JO1']])->assertStatus(409);
        $this->assertSame(1, MesinHistory::count());
    }

    public function test_siapkan_stores_operator_as_pending_without_running(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');

        $this->getJson('/api/mesin/260')
            ->assertJsonPath('data.siap_running', false)
            ->assertJsonPath('data.operator', null);

        $this->postJson('/api/mesin/260/siapkan', [
            'jenis_operator' => 'OPERATOR', 'operator1' => 'JOKO', 'shift' => '1', 'no_jo' => ['JO1'],
        ])
            ->assertCreated()
            ->assertJsonPath('mesin.status_mesin', 'READY')
            ->assertJsonPath('mesin.siap_running', true)
            ->assertJsonPath('mesin.operator', 'JOKO')
            ->assertJsonPath('mesin.pending_history.no_jo', ['JO1'])
            ->assertJsonPath('mesin.active_history', null);

        $history = MesinHistory::sole();
        $this->assertSame(StatusProses::Pending, $history->status_proses);
        $this->assertNull($history->mesin_start);
        $this->assertSame(StatusProses::Open, $this->statusJo('260', 'JO1'));
    }

    public function test_siapkan_replaces_previous_operator_and_can_be_cancelled(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');
        $payload = ['jenis_operator' => 'OPERATOR', 'shift' => '1', 'no_jo' => ['JO1']];

        $this->postJson('/api/mesin/260/siapkan', $payload + ['operator1' => 'JOKO'])->assertCreated();
        $this->postJson('/api/mesin/260/siapkan', $payload + ['operator1' => 'BUDI'])
            ->assertCreated()
            ->assertJsonPath('mesin.operator', 'BUDI');
        $this->assertSame('BUDI', MesinHistory::sole()->operator1);

        $this->deleteJson('/api/mesin/260/siapkan')
            ->assertOk()
            ->assertJsonPath('mesin.siap_running', false);
        $this->assertSame(0, MesinHistory::count());

        $this->deleteJson('/api/mesin/260/siapkan')->assertStatus(409);
    }

    public function test_ganti_operator_updates_pending_preparation(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');
        $this->postJson('/api/mesin/260/siapkan', [
            'jenis_operator' => 'OPERATOR', 'operator1' => 'JOKO', 'shift' => '1', 'no_jo' => ['JO1'],
        ])->assertCreated();

        $this->postJson('/api/mesin/260/ganti-operator', ['jenis_operator' => 'TEKNISI', 'operator1' => 'BUDI', 'shift' => '2'])
            ->assertOk()
            ->assertJsonPath('mesin.siap_running', true)
            ->assertJsonPath('mesin.operator', 'BUDI');

        $history = MesinHistory::sole();
        $this->assertSame(StatusProses::Pending, $history->status_proses);
        $this->assertSame('BUDI', $history->nama_teksini);
        $this->assertNull($history->operator1);
        $this->assertNull($history->no_jo, 'JO hanya untuk produksi');
    }

    public function test_ganti_operator_assigns_teknisi_or_setup_without_jo_but_operator_needs_jo(): void
    {
        MesinQr::factory()->create(['no_mesin' => '300']);
        $this->buatJo('300', 'JO1');

        $this->postJson('/api/mesin/300/ganti-operator', ['jenis_operator' => 'OPERATOR', 'operator1' => 'ADI', 'shift' => '1'])
            ->assertJsonValidationErrors('no_jo');

        $this->postJson('/api/mesin/300/ganti-operator', ['jenis_operator' => 'SETUP', 'operator1' => 'SETTER', 'shift' => '1'])
            ->assertOk()
            ->assertJsonPath('mesin.siap_running', true)
            ->assertJsonPath('mesin.status_mesin', 'READY')
            ->assertJsonPath('mesin.pending_history.jenis_operator', 'SETUP');

        $this->postJson('/api/mesin/300/ganti-operator', [
            'jenis_operator' => 'OPERATOR', 'operator1' => 'ADI', 'shift' => '1', 'no_jo' => ['JO1'],
        ])
            ->assertOk()
            ->assertJsonPath('mesin.operator', 'ADI')
            ->assertJsonPath('mesin.pending_history.no_jo', ['JO1']);
        $this->assertSame(1, MesinHistory::count());
    }

    public function test_ganti_operator_with_different_jenis_stops_session_and_waits_for_esp(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');
        $this->postJson('/api/mesin/260/start', [
            'jenis_operator' => 'OPERATOR', 'operator1' => 'JOKO', 'shift' => '1', 'no_jo' => ['JO1'],
        ])->assertCreated();

        $this->postJson('/api/mesin/260/ganti-operator', ['jenis_operator' => 'TEKNISI', 'operator1' => 'BUDI', 'shift' => '1'])
            ->assertOk()
            ->assertJsonPath('mesin.status_mesin', 'READY')
            ->assertJsonPath('mesin.status_tampilan', 'READY')
            ->assertJsonPath('mesin.siap_running', true)
            ->assertJsonPath('mesin.active_history', null)
            ->assertJsonPath('mesin.running_jo', []);

        [$lama, $baru] = MesinHistory::orderBy('id_history')->get()->all();
        $this->assertSame(StatusProses::Finish, $lama->status_proses);
        $this->assertSame(StatusProses::Pending, $baru->status_proses);
        $this->assertSame('BUDI', $baru->nama_teksini);
        $this->assertSame(StatusProses::Open, $this->statusJo('260', 'JO1'));
    }

    public function test_ganti_operator_while_running_closes_old_session_and_continues_with_same_jo(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');
        $this->postJson('/api/mesin/260/start', [
            'jenis_operator' => 'OPERATOR', 'operator1' => 'JOKO', 'shift' => '1', 'no_jo' => ['JO1'],
        ])->assertCreated();
        $this->travel(2)->hours();

        $this->postJson('/api/mesin/260/ganti-operator', ['jenis_operator' => 'OPERATOR', 'operator1' => 'ADI', 'shift' => '2'])
            ->assertOk()
            ->assertJsonPath('mesin.status_mesin', 'RUNNING')
            ->assertJsonPath('mesin.operator', 'ADI')
            ->assertJsonPath('mesin.running_jo', ['JO1'])
            ->assertJsonPath('mesin.active_history.shift', '2');

        [$lama, $baru] = MesinHistory::orderBy('id_history')->get()->all();
        $this->assertSame(StatusProses::Finish, $lama->status_proses);
        $this->assertSame('02:00:00', $lama->mesin_durasi);
        $this->assertNotNull($lama->stop_produksi);
        $this->assertSame(StatusProses::Running, $baru->status_proses);
        $this->assertSame('JO1', $baru->no_jo);
        $this->assertSame(StatusProses::Running, $this->statusJo('260', 'JO1'));
    }

    public function test_ganti_operator_same_jenis_keeps_paused_status(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260', 'status_mesin' => StatusMesin::Rusak]);
        MesinHistory::factory()->running()->create(['no_mesin' => '260', 'no_jo' => 'JO1']);

        $this->postJson('/api/mesin/260/ganti-operator', ['jenis_operator' => 'OPERATOR', 'operator1' => 'ADI', 'shift' => '2'])
            ->assertOk()
            ->assertJsonPath('mesin.status_mesin', 'RUSAK')
            ->assertJsonPath('mesin.operator', 'ADI')
            ->assertJsonPath('mesin.active_history.no_jo', ['JO1']);
    }

    public function test_siapkan_rejected_while_running(): void
    {
        MesinQr::factory()->running()->create(['no_mesin' => '260']);
        MesinHistory::factory()->running()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');

        $this->postJson('/api/mesin/260/siapkan', [
            'jenis_operator' => 'OPERATOR', 'operator1' => 'JOKO', 'shift' => '1', 'no_jo' => ['JO1'],
        ])->assertStatus(409);
    }

    public function test_stop_closes_history_and_updates_jo_status(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1');
        $this->buatJo('260', 'JO2');
        $this->postJson('/api/mesin/260/start', [
            'jenis_operator' => 'OPERATOR', 'operator1' => 'JOKO', 'shift' => '1', 'no_jo' => ['JO1', 'JO2'],
        ])->assertCreated();

        $this->travel(90)->minutes();

        $this->postJson('/api/mesin/260/stop', ['good' => 150, 'ng' => 3, 'jo_selesai' => ['JO1']])
            ->assertOk()
            ->assertJsonPath('mesin.status_mesin', 'READY')
            ->assertJsonPath('mesin.running_jo', []);

        $history = MesinHistory::sole();
        $this->assertSame(StatusProses::Finish, $history->status_proses);
        $this->assertSame('01:30:00', $history->mesin_durasi);
        $this->assertSame(150.0, $history->good);
        $this->assertSame(StatusProses::Finish, $this->statusJo('260', 'JO1'));
        $this->assertSame(StatusProses::Open, $this->statusJo('260', 'JO2'));

        $this->postJson('/api/mesin/260/stop')->assertStatus(409);
    }

    public function test_stop_works_for_mesin_running_without_history(): void
    {
        MesinQr::factory()->running('JO1')->create(['no_mesin' => '260']);

        $this->postJson('/api/mesin/260/stop')->assertOk();
        $this->assertSame(StatusMesin::Ready, MesinQr::find('260')->status_mesin);
    }

    public function test_mapping_jo_crud_only_touches_the_selected_mesin(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        MesinQr::factory()->create(['no_mesin' => '300']);
        $this->buatJo('300', 'JO1');

        $this->postJson('/api/mesin/260/jo', ['no_jo' => 'jo1', 'qtyjo' => 500, 'produk' => 'ZLTUB'])
            ->assertCreated()
            ->assertJsonPath('data.no_jo', 'JO1')
            ->assertJsonPath('data.status_jo', 'OPEN');
        $this->postJson('/api/mesin/260/jo', ['no_jo' => 'JO1', 'qtyjo' => 1])->assertJsonValidationErrors('no_jo');

        $this->putJson('/api/mesin/260/jo/JO1', ['status_jo' => 'hold', 'prioritas' => 3])
            ->assertOk()
            ->assertJsonPath('data.status_jo', 'HOLD')
            ->assertJsonPath('data.prioritas', 3);
        $this->assertSame(StatusProses::Open, $this->statusJo('300', 'JO1'));

        $this->deleteJson('/api/mesin/260/jo/JO1')->assertOk();
        $this->assertFalse(MesinMappingJo::where('no_mesin', '260')->exists());
        $this->assertTrue(MesinMappingJo::where('no_mesin', '300')->where('no_jo', 'JO1')->exists());

        $this->deleteJson('/api/mesin/260/jo/JO1')->assertNotFound();
    }

    public function test_running_jo_cannot_be_deleted_or_status_changed(): void
    {
        MesinQr::factory()->create(['no_mesin' => '260']);
        $this->buatJo('260', 'JO1', 'RUNNING');

        $this->deleteJson('/api/mesin/260/jo/JO1')->assertStatus(409);
        $this->putJson('/api/mesin/260/jo/JO1', ['status_jo' => 'FINISH'])->assertStatus(409);
        $this->putJson('/api/mesin/260/jo/JO1', ['qtyjo' => 900])->assertOk();
    }

    public function test_operators_returns_recent_unique_names(): void
    {
        MesinHistory::factory()->create(['operator1' => 'joko', 'tgl_running' => now()->toDateString()]);
        MesinHistory::factory()->create(['operator1' => 'JOKO', 'operator2' => 'ADI', 'tgl_running' => now()->toDateString()]);
        MesinHistory::factory()->create(['operator1' => 'LAMA', 'tgl_running' => now()->subYear()->toDateString()]);

        $this->getJson('/api/operators')->assertOk()->assertExactJson(['success' => true, 'data' => ['ADI', 'JOKO']]);
    }
}
