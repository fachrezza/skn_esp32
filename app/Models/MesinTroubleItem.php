<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Master item masalah/defect (tabel mesin_troublle_item; kolom hapusnya bernama deteled_at).
 */
class MesinTroubleItem extends Model
{
    protected $table = 'mesin_troublle_item';

    protected $primaryKey = 'kditem';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->where('stat', 'ENABLE')->whereNull('deteled_at');
    }
}
