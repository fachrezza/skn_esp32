<?php

namespace App\Models;

use App\Enums\StatusProses;
use Database\Factories\MesinHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Riwayat proses mesin per sesi operator/teknisi/setup (tabel mesin_history).
 */
class MesinHistory extends Model
{
    /** @use HasFactory<MesinHistoryFactory> */
    use HasFactory;

    /** Kolom jumlah defect, nama kolomnya sama dengan mesin_troublle_item.kditem. */
    public const DEFECT_COLUMNS = ['ITEM1', 'ITEM2', 'ITEM3', 'ITEM4', 'ITEM5', 'ITEM6', 'ITEM7', 'ITEM8', 'ITEM9', 'ITEM10'];

    protected $table = 'mesin_history';

    protected $primaryKey = 'id_history';

    protected $guarded = ['id_history'];

    protected function casts(): array
    {
        return [
            'tgl_running' => 'date:Y-m-d',
            'mesin_start' => 'datetime',
            'mesin_stop' => 'datetime',
            'status_proses' => StatusProses::class,
            'good' => 'float',
            'ng' => 'float',
            'sisa_jo' => 'float',
            'runner' => 'float',
            'actual_cycletime' => 'float',
            'actual_berat' => 'float',
        ];
    }

    public function mesin(): BelongsTo
    {
        return $this->belongsTo(MesinQr::class, 'no_mesin', 'no_mesin');
    }

    public function joDetails(): HasMany
    {
        return $this->hasMany(MesinHistoryJo::class, 'id_history', 'id_history');
    }

    public function troubleLogs(): HasMany
    {
        return $this->hasMany(MesinTroubleLog::class, 'id_history', 'id_history');
    }

    /**
     * @return list<string>
     */
    public function joList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->no_jo))));
    }
}
