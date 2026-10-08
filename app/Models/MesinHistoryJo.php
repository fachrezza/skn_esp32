<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil produksi per JO untuk satu riwayat mesin (tabel mesin_history_jo).
 */
class MesinHistoryJo extends Model
{
    protected $table = 'mesin_history_jo';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'qtyjo' => 'float',
            'good' => 'float',
            'ng' => 'float',
            'sisa_jo' => 'float',
            'runner' => 'float',
            'converted_good' => 'float',
            'converted_ng' => 'float',
        ];
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(MesinHistory::class, 'id_history', 'id_history');
    }
}
