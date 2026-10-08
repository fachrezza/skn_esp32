<?php

namespace App\Http\Resources;

use App\Models\MesinQr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MesinQr
 */
class MesinResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'no_mesin' => $this->no_mesin,
            'nama_mesin' => $this->nama_mesin,
            'nama_tampilan' => $this->namaUntukTampilan(),
            'note_mesin' => $this->note_mesin,
            'image_qr' => $this->image_qr,
            'uid_esp' => $this->uid_esp,
            'status_mesin' => $this->status_mesin?->value,
            'is_running' => $this->isRunning(),
            'running_jo' => $this->runningJoList(),
            'timer_sec' => $this->durasiStatus(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'active_history' => $this->whenLoaded(
                'activeHistory',
                fn () => $this->activeHistory ? new HistoryResource($this->activeHistory) : null,
            ),
        ];
    }
}
