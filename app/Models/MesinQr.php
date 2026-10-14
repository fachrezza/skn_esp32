<?php

namespace App\Models;

use App\Enums\StatusMesin;
use App\Enums\StatusProses;
use Carbon\CarbonInterface;
use Database\Factories\MesinQrFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    /**
     * Persiapan produksi: operator & JO sudah diinput dari dashboard (status_proses PENDING,
     * mesin_start masih kosong) dan menunggu tombol RUNNING di ESP32.
     */
    public function pendingHistory(): HasOne
    {
        return $this->hasOne(MesinHistory::class, 'no_mesin', 'no_mesin')
            ->ofMany(['id_history' => 'max'], function ($query) {
                $query->where('status_proses', StatusProses::Pending->value)->whereNull('mesin_stop');
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
     * Menghapus persiapan (PENDING) mesin ini. Mengembalikan jumlah baris yang dihapus.
     */
    public function hapusPersiapan(): int
    {
        return MesinHistory::where('no_mesin', $this->no_mesin)
            ->where('status_proses', StatusProses::Pending->value)
            ->whereNull('mesin_start')
            ->whereNull('mesin_stop')
            ->delete();
    }

    /**
     * READY dan operator sudah diinput, tinggal ditekan RUNNING.
     */
    public function isSiapRunning(): bool
    {
        return $this->status_mesin === StatusMesin::Ready && $this->pendingHistory !== null;
    }

    /**
     * Status seperti yang ditampilkan ESP32 & dashboard: PRODUKSI / MOULD / SETTER selama sesi berjalan,
     * MAINTANCE / RUSAK / OFF apa adanya, selain itu READY.
     */
    public function statusTampilan(): string
    {
        if (in_array($this->status_mesin, [StatusMesin::Maintance, StatusMesin::Rusak, StatusMesin::Off], true)) {
            return $this->status_mesin->value;
        }

        if ($this->activeHistory) {
            return $this->activeHistory->statusEsp();
        }

        return $this->status_mesin?->value ?? StatusMesin::Ready->value;
    }

    /**
     * Mesin hanya boleh RUNNING bila sudah ada operator: persiapan (PENDING) atau sesi yang
     * sedang berjalan (mis. kembali RUNNING setelah RUSAK).
     */
    public function punyaOperator(): bool
    {
        return $this->activeHistory !== null || $this->pendingHistory !== null;
    }

    /**
     * Nama operator/teknisi/setup dari sesi berjalan, atau dari persiapan bila belum running.
     */
    public function namaOperator(): ?string
    {
        $history = $this->activeHistory ?? $this->pendingHistory;

        return $history?->operator1 ?? $history?->nama_teksini ?? $history?->nama_setup;
    }

    /**
     * Memulai produksi dari persiapan (PENDING), atau melanjutkan sesi yang sudah berjalan.
     * Mengembalikan false bila belum ada operator.
     */
    public function jalankan(string $userAt): bool
    {
        if ($this->activeHistory) {
            $statusSesi = self::statusSesi($this->activeHistory->jenis_operator);

            if ($this->status_mesin !== $statusSesi) {
                $this->update(['status_mesin' => $statusSesi, 'user_at' => $userAt]);
            }

            return true;
        }

        if (! $this->pendingHistory) {
            return false;
        }

        DB::transaction(fn () => $this->mulaiProduksi($this->pendingHistory, $userAt));

        $this->unsetRelation('pendingHistory')->load('activeHistory');

        return true;
    }

    /**
     * status_mesin selama sesi berjalan: OPERATOR (produksi) RUNNING, TEKNISI (mold) dan SETUP (setter) READY.
     */
    public static function statusSesi(?string $jenisOperator): StatusMesin
    {
        return $jenisOperator === 'OPERATOR' ? StatusMesin::Running : StatusMesin::Ready;
    }

    /**
     * Mengubah riwayat menjadi RUNNING (mesin_start & start_produksi/mold/setup sesuai jenis), mengisi
     * mesin_qr.running_jo, dan menandai JO-nya RUNNING. Status mesin mengikuti statusSesi().
     */
    public function mulaiProduksi(MesinHistory $history, string $userAt): void
    {
        $sekarang = now();

        $history->update([
            'tgl_running' => $sekarang->toDateString(),
            'mesin_start' => $sekarang,
            'start_'.$history->tahap() => $sekarang->format('H:i:s'),
            'status_proses' => StatusProses::Running,
            'user_at' => $userAt,
        ]);

        $this->update([
            'status_mesin' => self::statusSesi($history->jenis_operator),
            'running_jo' => $history->no_jo,
            'user_at' => $userAt,
        ]);

        MesinMappingJo::where('no_mesin', $this->no_mesin)
            ->whereIn('no_jo', $history->joList())
            ->update(['status_jo' => StatusProses::Running->value, 'user_updated' => $userAt]);
    }

    /**
     * Menutup sesi produksi yang berjalan: mesin_history FINISH dengan mesin_stop & durasi, mesin kembali
     * ke $statusAkhir (READY), running_jo dikosongkan. JO di $joSelesai menjadi FINISH, sisanya kembali OPEN.
     *
     * @param  array{good?: float|int, ng?: float|int, sisa_jo?: float|int, remark?: string}  $hasil
     * @param  list<string>  $joSelesai
     */
    public function hentikanProduksi(
        string $userAt,
        array $hasil = [],
        array $joSelesai = [],
        StatusMesin $statusAkhir = StatusMesin::Ready,
    ): ?MesinHistory {
        $history = $this->activeHistory()->first();
        $sekarang = now();
        $daftarJo = $history?->joList() ?? $this->runningJoList();
        $joSelesai = array_values(array_intersect($daftarJo, $joSelesai));

        if ($history) {
            $this->tutupHistory($history, $sekarang, $hasil);
        }

        $this->update([
            'status_mesin' => $statusAkhir,
            'running_jo' => null,
            'user_at' => $userAt,
        ]);

        $this->kembalikanJoKeOpen(array_diff($daftarJo, $joSelesai), $userAt);

        if ($joSelesai) {
            MesinMappingJo::where('no_mesin', $this->no_mesin)
                ->whereIn('no_jo', $joSelesai)
                ->update(['status_jo' => StatusProses::Finish->value, 'user_updated' => $userAt]);
        }

        $this->unsetRelation('activeHistory');

        return $history;
    }

    /**
     * Mengganti orang yang memegang mesin (satu orang aktif). Status tetap diubah dari ESP32:
     * - sesi berjalan dengan jenis yang sama (mis. ganti shift): sesi lama ditutup, sesi baru langsung lanjut
     *   (status jeda MAINTANCE / RUSAK dipertahankan);
     * - sesi berjalan dengan jenis berbeda: sesi lama dihentikan, orang baru disimpan sebagai persiapan (PENDING);
     * - belum ada sesi: persiapan lama diganti persiapan baru.
     *
     * @param  array{jenis_operator: string, shift: string, operator1: ?string, operator2: ?string, nama_teksini: ?string, nama_setup: ?string, no_jo: ?string}  $data
     */
    public function gantiOperator(array $data, string $userAt): MesinHistory
    {
        $lama = $this->activeHistory()->first();

        if ($lama && $lama->jenis_operator === $data['jenis_operator']) {
            $statusJeda = in_array($this->status_mesin, [StatusMesin::Maintance, StatusMesin::Rusak], true)
                ? $this->status_mesin
                : null;

            $this->tutupHistory($lama, now());

            $baru = MesinHistory::create($data + ['no_mesin' => $this->no_mesin, 'user_at' => $userAt]);
            $this->mulaiProduksi($baru, $userAt);
            $this->kembalikanJoKeOpen(array_diff($lama->joList(), $baru->joList()), $userAt);

            if ($statusJeda) {
                $this->update(['status_mesin' => $statusJeda]);
            }

            $this->unsetRelation('activeHistory');

            return $baru;
        }

        if ($lama) {
            $this->hentikanProduksi($userAt);
        }

        $this->hapusPersiapan();

        $baru = MesinHistory::create($data + [
            'no_mesin' => $this->no_mesin,
            'tgl_running' => now()->toDateString(),
            'status_proses' => StatusProses::Pending,
            'user_at' => $userAt,
        ]);

        $this->unsetRelation('pendingHistory');

        return $baru;
    }

    /**
     * JO yang masih RUNNING di mesin ini dikembalikan ke OPEN.
     *
     * @param  array<int, string>  $daftarJo
     */
    private function kembalikanJoKeOpen(array $daftarJo, string $userAt): void
    {
        if (! $daftarJo) {
            return;
        }

        MesinMappingJo::where('no_mesin', $this->no_mesin)
            ->whereIn('no_jo', $daftarJo)
            ->where('status_jo', StatusProses::Running->value)
            ->update(['status_jo' => StatusProses::Open->value, 'user_updated' => $userAt]);
    }

    /**
     * Menandai satu sesi selesai: mesin_stop, stop_produksi/mold/setup, durasi, dan status FINISH.
     *
     * @param  array{good?: float|int, ng?: float|int, sisa_jo?: float|int, remark?: string}  $hasil
     */
    private function tutupHistory(MesinHistory $history, CarbonInterface $sekarang, array $hasil = []): void
    {
        $detik = (int) $history->mesin_start->diffInSeconds($sekarang, true);

        $history->update($hasil + [
            'mesin_stop' => $sekarang,
            'stop_'.$history->tahap() => $sekarang->format('H:i:s'),
            'mesin_durasi' => sprintf('%02d:%02d:%02d', intdiv($detik, 3600), intdiv($detik % 3600, 60), $detik % 60),
            'status_proses' => StatusProses::Finish,
        ]);
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
     * bila status sesuai sesinya (RUNNING produksi, READY mold/setter), selain itu dari updated_at terakhir.
     */
    public function durasiStatus(): int
    {
        $history = $this->activeHistory;
        $sejak = $history && $this->status_mesin === self::statusSesi($history->jenis_operator) ? $history->mesin_start : null;
        $sejak ??= $this->updated_at;

        return $sejak ? (int) $sejak->diffInSeconds(now(), true) : 0;
    }
}
