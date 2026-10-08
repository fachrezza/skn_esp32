<?php

namespace App\Models;

use App\Enums\StatusProses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Antrian JO per mesin (tabel mesin_mapping_jo).
 * Primary key aslinya komposit (no_jo, no_mesin), jadi model ini hanya dipakai untuk membaca.
 */
class MesinMappingJo extends Model
{
    protected $table = 'mesin_mapping_jo';

    protected $primaryKey = 'no_jo';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tgl_jo' => 'date:Y-m-d',
            'status_jo' => StatusProses::class,
            'qtyjo' => 'float',
            'qty_good' => 'float',
            'qty_ng' => 'float',
            'sisa_jo' => 'float',
            'runner' => 'float',
        ];
    }

    public function mesin(): BelongsTo
    {
        return $this->belongsTo(MesinQr::class, 'no_mesin', 'no_mesin');
    }
}
