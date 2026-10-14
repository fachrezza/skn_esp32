<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusMesin;
use App\Enums\StatusProses;
use App\Http\Controllers\Controller;
use App\Http\Resources\HistoryResource;
use App\Http\Resources\MesinResource;
use App\Models\MesinHistory;
use App\Models\MesinQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProduksiController extends Controller
{
    /**
     * POST /api/mesin/{no_mesin}/start
     * Body: {"jenis_operator": "OPERATOR" | "TEKNISI" | "SETUP", "operator1": "JOKO", "operator2": null, "shift": "1", "no_jo": ["JO1QA00101"]}
     * Membuat mesin_history RUNNING, mengisi mesin_qr.running_jo, dan menandai JO di mesin_mapping_jo sebagai RUNNING.
     */
    public function start(Request $request, MesinQr $mesin): JsonResponse
    {
        $data = $this->validasiPersiapan($request, $mesin);

        return DB::transaction(function () use ($mesin, $data) {
            $mesin = MesinQr::whereKey($mesin->no_mesin)->lockForUpdate()->firstOrFail();

            if ($mesin->activeHistory()->exists()) {
                return $this->error("Mesin {$mesin->no_mesin} masih berjalan, hentikan dulu.", 409, $mesin);
            }

            $mesin->hapusPersiapan();

            $history = MesinHistory::create($data + ['user_at' => 'api']);
            $mesin->mulaiProduksi($history, 'api');
            $mesin->load(['activeHistory', 'pendingHistory']);

            return response()->json([
                'success' => true,
                'message' => "{$mesin->namaUntukTampilan()} dijalankan oleh {$mesin->namaOperator()}",
                'history' => new HistoryResource($history),
                'mesin' => new MesinResource($mesin),
            ], 201);
        });
    }

    /**
     * POST /api/mesin/{no_mesin}/siapkan
     * Body sama dengan /start. Menyimpan operator & JO sebagai mesin_history PENDING tanpa menjalankan mesin;
     * mesin tetap READY ("siap running") sampai tombol RUNNING di ESP32 ditekan. Persiapan lama diganti.
     */
    public function siapkan(Request $request, MesinQr $mesin): JsonResponse
    {
        $data = $this->validasiPersiapan($request, $mesin);

        return DB::transaction(function () use ($mesin, $data) {
            $mesin = MesinQr::whereKey($mesin->no_mesin)->lockForUpdate()->firstOrFail();

            if ($mesin->activeHistory()->exists()) {
                return $this->error("Mesin {$mesin->no_mesin} masih berjalan, hentikan dulu.", 409, $mesin);
            }

            $mesin->hapusPersiapan();

            $history = MesinHistory::create($data + [
                'tgl_running' => now()->toDateString(),
                'status_proses' => StatusProses::Pending,
                'user_at' => 'api',
            ]);

            $mesin->load(['activeHistory', 'pendingHistory']);

            return response()->json([
                'success' => true,
                'message' => "{$mesin->namaUntukTampilan()} siap running oleh {$mesin->namaOperator()}",
                'history' => new HistoryResource($history),
                'mesin' => new MesinResource($mesin),
            ], 201);
        });
    }

    /**
     * DELETE /api/mesin/{no_mesin}/siapkan
     * Membatalkan persiapan (operator dilepas), mesin kembali READY tanpa operator.
     */
    public function batalSiapkan(MesinQr $mesin): JsonResponse
    {
        if (! $mesin->hapusPersiapan()) {
            return $this->error("Mesin {$mesin->no_mesin} belum disiapkan", 409, $mesin);
        }

        return response()->json([
            'success' => true,
            'message' => "Operator {$mesin->namaUntukTampilan()} dibatalkan",
            'mesin' => new MesinResource($mesin->load(['activeHistory', 'pendingHistory'])),
        ]);
    }

    /**
     * POST /api/mesin/{no_mesin}/ganti-operator
     * Body: {"jenis_operator": "OPERATOR" | "TEKNISI" | "SETUP", "operator1": "BUDI", "operator2": null, "shift": "2", "no_jo": ["JO1"]}
     * Satu-satunya pengaturan dari dashboard: siapa yang memegang mesin. Status diubah dari ESP32.
     * JO hanya untuk OPERATOR (produksi); bila tidak dikirim, JO operator sebelumnya dipakai lagi.
     * - Belum ada / masih persiapan: disimpan sebagai persiapan (PENDING) baru.
     * - Sesi berjalan, jenis sama (mis. ganti shift): sesi lama ditutup, sesi baru langsung lanjut.
     * - Sesi berjalan, jenis beda: sesi lama dihentikan, orang baru menunggu dipilih statusnya di ESP32.
     */
    public function gantiOperator(Request $request, MesinQr $mesin): JsonResponse
    {
        $operator = $this->validasiOperator($request);
        $jo = $this->validasiJo($request, $mesin);

        return DB::transaction(function () use ($mesin, $operator, $jo) {
            $mesin = MesinQr::whereKey($mesin->no_mesin)->lockForUpdate()->firstOrFail();

            if ($operator['jenis_operator'] === 'OPERATOR') {
                $sebelumnya = $mesin->activeHistory ?? $mesin->pendingHistory;
                $jo ??= $sebelumnya?->jenis_operator === 'OPERATOR' ? $sebelumnya->no_jo : null;

                if (! $jo) {
                    throw ValidationException::withMessages(['no_jo' => 'Pilih JO untuk produksi.']);
                }
            }

            $history = $mesin->gantiOperator($operator + [
                'no_jo' => $operator['jenis_operator'] === 'OPERATOR' ? $jo : null,
            ], 'api');

            $mesin->load(['activeHistory', 'pendingHistory']);

            return response()->json([
                'success' => true,
                'message' => "Operator {$mesin->namaUntukTampilan()} diganti menjadi {$mesin->namaOperator()}",
                'history' => new HistoryResource($history),
                'mesin' => new MesinResource($mesin),
            ]);
        });
    }

    /**
     * POST /api/mesin/{no_mesin}/stop
     * Body: {"good": 100, "ng": 2, "sisa_jo": 0, "remark": "...", "jo_selesai": ["JO1QA00101"]}
     * Menutup mesin_history yang berjalan, mesin kembali READY. JO di jo_selesai menjadi FINISH, sisanya kembali OPEN.
     */
    public function stop(Request $request, MesinQr $mesin): JsonResponse
    {
        $validated = $request->validate([
            'good' => ['nullable', 'numeric', 'min:0'],
            'ng' => ['nullable', 'numeric', 'min:0'],
            'sisa_jo' => ['nullable', 'numeric', 'min:0'],
            'remark' => ['nullable', 'string', 'max:2000'],
            'jo_selesai' => ['nullable', 'array'],
            'jo_selesai.*' => ['string'],
        ]);

        return DB::transaction(function () use ($mesin, $validated) {
            $mesin = MesinQr::whereKey($mesin->no_mesin)->lockForUpdate()->firstOrFail();
            $history = $mesin->activeHistory()->lockForUpdate()->first();

            if (! $history && ! $mesin->isRunning() && $mesin->status_mesin !== StatusMesin::Maintance) {
                return $this->error("Mesin {$mesin->no_mesin} tidak sedang berjalan", 409, $mesin);
            }

            $history = $mesin->hentikanProduksi(
                'api',
                array_filter([
                    'good' => $validated['good'] ?? null,
                    'ng' => $validated['ng'] ?? null,
                    'sisa_jo' => $validated['sisa_jo'] ?? null,
                    'remark' => $validated['remark'] ?? null,
                ], fn ($nilai) => $nilai !== null),
                $validated['jo_selesai'] ?? [],
            );

            return response()->json([
                'success' => true,
                'message' => "{$mesin->namaUntukTampilan()} dihentikan",
                'history' => $history ? new HistoryResource($history) : null,
                'mesin' => new MesinResource($mesin->load(['activeHistory', 'pendingHistory'])),
            ]);
        });
    }

    /**
     * GET /api/operators
     * Nama operator/teknisi/setup yang pernah tercatat 90 hari terakhir (untuk saran input).
     */
    public function operators(): JsonResponse
    {
        $batas = now()->subDays(90)->toDateString();

        $nama = collect(['operator1', 'operator2', 'nama_teksini', 'nama_setup'])
            ->flatMap(fn (string $kolom) => MesinHistory::where('tgl_running', '>=', $batas)
                ->whereNotNull($kolom)
                ->distinct()
                ->pluck($kolom))
            ->map(fn (string $n) => strtoupper(trim($n)))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $nama,
        ]);
    }

    /**
     * Validasi operator & JO untuk /start dan /siapkan, dikembalikan sebagai kolom mesin_history.
     *
     * @return array<string, string|null>
     */
    private function validasiPersiapan(Request $request, MesinQr $mesin): array
    {
        $operator = $this->validasiOperator($request);
        $produksi = $operator['jenis_operator'] === 'OPERATOR';
        $jo = $this->validasiJo($request, $mesin);

        if ($produksi && ! $jo) {
            throw ValidationException::withMessages(['no_jo' => 'Pilih JO untuk produksi.']);
        }

        return ['no_mesin' => $mesin->no_mesin] + $operator + ['no_jo' => $produksi ? $jo : null];
    }

    /**
     * Validasi JO opsional (wajibnya hanya untuk produksi, dicek pemanggil). Mengembalikan JO yang digabung koma,
     * atau null bila tidak ada JO yang dikirim.
     */
    private function validasiJo(Request $request, MesinQr $mesin): ?string
    {
        $validated = $request->validate([
            'no_jo' => ['nullable', 'array'],
            'no_jo.*' => ['required', 'string', 'distinct'],
        ]);

        $daftarJo = array_values(array_filter(array_map('trim', $validated['no_jo'] ?? [])));

        if (! $daftarJo) {
            return null;
        }
        $gabunganJo = implode(',', $daftarJo);

        if (strlen($gabunganJo) > 117) {
            throw ValidationException::withMessages(['no_jo' => 'Terlalu banyak JO dipilih sekaligus (maksimal 117 karakter digabung).']);
        }

        $joTersedia = $mesin->mappingJo()
            ->whereIn('no_jo', $daftarJo)
            ->where('status_jo', '!=', StatusProses::Finish->value)
            ->pluck('no_jo')
            ->all();

        $joTidakValid = array_diff($daftarJo, $joTersedia);
        if ($joTidakValid) {
            throw ValidationException::withMessages([
                'no_jo' => 'JO tidak terdaftar di mesin ini atau sudah FINISH: '.implode(', ', $joTidakValid),
            ]);
        }

        return $gabunganJo;
    }

    /**
     * Validasi jenis, shift & nama operator, dikembalikan sebagai kolom mesin_history
     * (nama masuk ke operator1 / nama_teksini / nama_setup sesuai jenis).
     *
     * @return array{jenis_operator: string, shift: string, operator1: ?string, operator2: ?string, nama_teksini: ?string, nama_setup: ?string}
     */
    private function validasiOperator(Request $request): array
    {
        $validated = $request->validate([
            'jenis_operator' => ['required', Rule::in(['OPERATOR', 'TEKNISI', 'SETUP'])],
            'operator1' => ['required', 'string', 'max:117'],
            'operator2' => ['nullable', 'string', 'max:117'],
            'shift' => ['required', Rule::in(['1', '2', '3'])],
        ]);

        $jenis = $validated['jenis_operator'];

        return [
            'jenis_operator' => $jenis,
            'shift' => $validated['shift'],
            'operator1' => $jenis === 'OPERATOR' ? $validated['operator1'] : null,
            'operator2' => $jenis === 'OPERATOR' ? ($validated['operator2'] ?? null) : null,
            'nama_teksini' => $jenis === 'TEKNISI' ? $validated['operator1'] : null,
            'nama_setup' => $jenis === 'SETUP' ? $validated['operator1'] : null,
        ];
    }

    private function error(string $message, int $code, MesinQr $mesin): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'mesin' => new MesinResource($mesin->load(['activeHistory', 'pendingHistory'])),
        ], $code);
    }
}
