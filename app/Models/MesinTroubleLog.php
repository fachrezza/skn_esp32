<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan masalah per riwayat mesin (tabel mesin_troublle_log, tanpa kolom updated_at).
 */
class MesinTroubleLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'mesin_troublle_log';

    protected $primaryKey = 'id_log';

    protected $guarded = ['id_log'];

    public function history(): BelongsTo
    {
        return $this->belongsTo(MesinHistory::class, 'id_history', 'id_history');
    }
}
