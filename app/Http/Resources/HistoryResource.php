<?php

namespace App\Http\Resources;

use App\Models\MesinHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MesinHistory
 */
class HistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defects = [];
        foreach (MesinHistory::DEFECT_COLUMNS as $kolom) {
            if ($this->{$kolom} !== null) {
                $defects[$kolom] = (float) $this->{$kolom};
            }
        }

        return [
            'id_history' => $this->id_history,
            'no_mesin' => $this->no_mesin,
            'tgl_running' => $this->tgl_running?->toDateString(),
            'jenis_operator' => $this->jenis_operator,
            'shift' => $this->shift,
            'operator1' => $this->operator1,
            'operator2' => $this->operator2,
            'nama_teknisi' => $this->nama_teksini,
            'nama_setup' => $this->nama_setup,
            'no_jo' => $this->joList(),
            'mesin_start' => $this->mesin_start?->toDateTimeString(),
            'mesin_stop' => $this->mesin_stop?->toDateTimeString(),
            'mesin_durasi' => $this->mesin_durasi,
            'durasi_berjalan_sec' => $this->mesin_start && ! $this->mesin_stop
                ? (int) $this->mesin_start->diffInSeconds(now(), true)
                : null,
            'produksi' => ['start' => $this->start_produksi, 'stop' => $this->stop_produksi],
            'mold' => ['start' => $this->start_mold, 'stop' => $this->stop_mold],
            'setup' => ['start' => $this->start_setup, 'stop' => $this->stop_setup],
            'good' => $this->good,
            'ng' => $this->ng,
            'sisa_jo' => $this->sisa_jo,
            'runner' => $this->runner,
            'defects' => $defects,
            'actual_cycletime' => $this->actual_cycletime,
            'actual_berat' => $this->actual_berat,
            'remark' => $this->remark,
            'status_proses' => $this->status_proses?->value,
            'user_at' => $this->user_at,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'mesin' => new MesinResource($this->whenLoaded('mesin')),
            'jo_details' => $this->whenLoaded('joDetails'),
            'trouble_logs' => $this->whenLoaded('troubleLogs'),
        ];
    }
}
