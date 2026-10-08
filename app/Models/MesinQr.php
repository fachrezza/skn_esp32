<?php

namespace App\Models;

use App\Enums\StatusMesin;
use App\Enums\StatusProses;
use Database\Factories\MesinQrFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master mesin (tabel mesin_qr). ESP32 dipetakan ke mesin lewat kolom uid_esp.
 */
class MesinQr extends Model
{
    /** @use HasFactory<MesinQrFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'mesin_qr';

    protected $primaryKey = 'no_mesin';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'no_mesin',
        'nama_mesin',
        'image_qr',
        'note_mesin',
        'pin_mesin',
        'status_mesin',
        'running_jo',
        'uid_esp',
        'user_at',
    ];

    protected $hidden = ['pin_mesin'];

    protected function casts(): array
    {
        return [
            'status_mesin' => StatusMesin::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MesinHistory::class, 'no_mesin', 'no_mesin');
    }

    /**
     * Riwayat produksi yang sedang berjalan (belum di-stop).
     */
    public function activeHistory(): HasOne
    {
        return $this->hasOne(MesinHistory::class, 'no_mesin', 'no_mesin')
            ->ofMany(['id_history' => 'max'], function ($query) {
                $query->where('status_proses', StatusProses::Running->value)->whereNull('mesin_stop');
            });
    }

    public function mappingJo(): HasMany
    {
        return $this->hasMany(MesinMappingJo::class, 'no_mesin', 'no_mesin');
    }

    public function isRunning(): bool
    {
        return $this->status_mesin === StatusMesin::Running;
    }

    /**
     * @return list<string>
     */
    public function runningJoList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->running_jo))));
    }

    public function namaUntukTampilan(): string
    {
        return trim("{$this->no_mesin} {$this->nama_mesin}");
    }

    /**
     * Lama (detik) mesin berada di status sekarang: dihitung dari mesin_start riwayat aktif
     * bila sedang RUNNING, selain itu dari updated_at terakhir.
     */
    public function durasiStatus(): int
    {
        $sejak = $this->isRunning() ? $this->activeHistory?->mesin_start : null;
        $sejak ??= $this->updated_at;

        return $sejak ? (int) $sejak->diffInSeconds(now(), true) : 0;
    }
}
