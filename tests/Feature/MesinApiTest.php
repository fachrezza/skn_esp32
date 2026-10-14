<?php

namespace Tests\Feature;

use App\Enums\StatusMesin;
use App\Models\MesinHistory;
use App\Models\MesinMappingJo;
use App\Models\MesinQr;
use App\Models\MesinTroubleItem;
use App\Models\MesinTroubleLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesinApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_mesin_and_filters_by_status(): void
    {
        MesinQr::factory()->running()->create(['no_mesin' => '100 T']);
        MesinQr::factory()->create(['no_mesin' => '200 A']);

        $this->getJson('/api/mesin')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('total', 2)
            ->assertJsonPath('running', 1);

        $this->getJson('/api/mesin?status=running')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.no_mesin', '100 T');
    }

    public function test_show_uses_no_mesin_with_space_and_includes_active_history(): void
    {
        MesinQr::factory()->running()->create(['no_mesin' => '100 T']);
        $history = MesinHistory::factory()->running()->create(['no_mesin' => '100 T']);

        $this->getJson('/api/mesin/100%20T')
            ->assertOk()
            ->assertJsonPath('data.no_mesin', '100 T')
            ->assertJsonPath('data.active_history.id_history', $history->id_history)
            ->assertJsonMissingPath('data.pin_mesin');

        $this->getJson('/api/mesin/TIDAKADA')->assertNotFound();
    }

    public function test_on_off_and_toggle_change_status_mesin(): void
    {
        MesinQr::factory()->create(['no_mesin' => '300']);
        MesinHistory::factory()->running()->create(['no_mesin' => '300']);

        $this->postJson('/api/mesin/300/on')->assertOk()->assertJsonPath('mesin.status_mesin', 'RUNNING');
        $this->postJson('/api/mesin/300/on')->assertStatus(409);
        $this->postJson('/api/mesin/300/off')->assertOk()->assertJsonPath('mesin.status_mesin', 'OFF');
        $this->postJson('/api/mesin/300/toggle')->assertOk()->assertJsonPath('mesin.status_mesin', 'RUNNING');
    }

    public function test_running_is_rejected_without_operator(): void
    {
        MesinQr::factory()->create(['no_mesin' => '300']);

        $this->postJson('/api/mesin/300/on')->assertStatus(409);
        $this->putJson('/api/mesin/300/status', ['status' => 'RUNNING'])->assertStatus(409);

        $this->assertSame(StatusMesin::Ready, MesinQr::find('300')->status_mesin);
    }

    public function test_update_status_validates_enum(): void
    {
        MesinQr::factory()->create(['no_mesin' => '300']);

        $this->putJson('/api/mesin/300/status', ['status' => 'RUSAK'])->assertOk();
        $this->assertSame(StatusMesin::Rusak, MesinQr::find('300')->status_mesin);

        $this->putJson('/api/mesin/300/status', ['status' => 'SALAH'])->assertJsonValidationErrors('status');
    }

    public function test_assign_esp_must_be_unique_and_can_be_cleared(): void
    {
        MesinQr::factory()->withEsp('AABBCCDDEEFF')->create(['no_mesin' => '100 T']);
        MesinQr::factory()->create(['no_mesin' => '300']);

        $this->putJson('/api/mesin/300/esp', ['uid_esp' => 'AABBCCDDEEFF'])->assertJsonValidationErrors('uid_esp');

        $this->putJson('/api/mesin/300/esp', ['uid_esp' => '112233445566'])
            ->assertOk()
            ->assertJsonPath('mesin.uid_esp', '112233445566');

        $this->putJson('/api/mesin/300/esp', ['uid_esp' => null])
            ->assertOk()
            ->assertJsonPath('mesin.uid_esp', null);
    }

    public function test_jo_and_history_of_mesin(): void
    {
        MesinQr::factory()->create(['no_mesin' => '300']);
        MesinMappingJo::create(['no_jo' => 'JO1', 'no_mesin' => '300', 'prioritas' => 2, 'status_jo' => 'OPEN']);
        MesinMappingJo::create(['no_jo' => 'JO2', 'no_mesin' => '300', 'prioritas' => 1, 'status_jo' => 'FINISH']);
        MesinHistory::factory()->count(3)->create(['no_mesin' => '300']);

        $this->getJson('/api/mesin/300/jo')
            ->assertOk()
            ->assertJsonPath('data.0.no_jo', 'JO2');

        $this->getJson('/api/mesin/300/jo?status=open')->assertJsonCount(1, 'data');

        $this->getJson('/api/mesin/300/history?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_history_detail_includes_jo_details_and_trouble_logs(): void
    {
        $history = MesinHistory::factory()->create(['ITEM3' => 2]);
        $history->joDetails()->create(['no_mesin' => $history->no_mesin, 'no_jo' => 'JO1', 'good' => 10]);
        MesinTroubleLog::create(['id_history' => $history->id_history, 'kditem' => 'OPE0', 'nmitem' => 'TIDAK ADA MASALAH', 'jenis' => 'OPERATOR']);

        $this->getJson("/api/history/{$history->id_history}")
            ->assertOk()
            ->assertJsonPath('data.defects.ITEM3', 2)
            ->assertJsonCount(1, 'data.jo_details')
            ->assertJsonCount(1, 'data.trouble_logs')
            ->assertJsonPath('data.mesin.no_mesin', $history->no_mesin);

        $this->getJson("/api/history?no_mesin={$history->no_mesin}&status_proses=FINISH")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_trouble_items_logs_and_dashboard(): void
    {
        MesinTroubleItem::create(['kditem' => 'MOL0', 'nmitem' => 'TIDAK ADA MASALAH', 'jenis' => 'MOULD']);
        MesinTroubleItem::create(['kditem' => 'MOL9', 'nmitem' => 'LAMA', 'jenis' => 'MOULD', 'stat' => 'DISABLE']);
        MesinTroubleLog::create(['id_history' => 1, 'kditem' => 'MOL0', 'jenis' => 'MOULD']);
        MesinQr::factory()->running()->create();
        MesinQr::factory()->create();

        $this->getJson('/api/trouble-items?jenis=mould')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/trouble-logs?id_history=1')->assertOk()->assertJsonPath('total', 1);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('mesin.RUNNING', 1)
            ->assertJsonPath('mesin.READY', 1)
            ->assertJsonPath('mesin.TOTAL', 2);
    }
}
