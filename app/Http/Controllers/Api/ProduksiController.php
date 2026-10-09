<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusMesin;
use App\Enums\StatusProses;
use App\Http\Controllers\Controller;
use App\Http\Resources\HistoryResource;
use App\Http\Resources\MesinResource;
use App\Models\MesinHistory;
use App\Models\MesinMappingJo;
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
        $validated = $request->validate([
            'jenis_operator' => ['required', Rule::in(['OPERATOR', 'TEKNISI', 'SETUP'])],
            'operator1' => ['required', 'string', 'max:117'],
            'operator2' => ['nullable', 'string', 'max:117'],
            'shift' => ['required', Rule::in(['1', '2', '3'])],
            'no_jo' => ['required', 'array', 'min:1'],
            'no_jo.*' => ['required', 'string', 'distinct'],
        ]);

        $daftarJo = array_values(array_map('trim', $validated['no_jo']));
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

        return DB::transaction(function () use ($mesin, $validated, $daftarJo, $gabunganJo) {
            $mesin = MesinQr::whereKey($mesin->no_mesin)->lockForUpdate()->firstOrFail();

            if ($mesin->activeHistory()->exists()) {
                return $this->error("Mesin {$mesin->no_mesin} masih berjalan, hentikan dulu.", 409, $mesin);
            }

            $jenis = $validated['jenis_operator'];
            $sekarang = now();

            $history = MesinHistory::create([
                'no_mesin' => $mesin->no_mesin,
                'tgl_running' => $sekarang->toDateString(),
                'jenis_operator' => $jenis,
                'shift' => $validated['shift'],
                'operator1' => $jenis === 'OPERATOR' ? $validated['operator1'] : null,
                'operator2' => $jenis === 'OPERATOR' ? ($validated['operator2'] ?? null) : null,
                'nama_teksini' => $jenis === 'TEKNISI' ? $validated['operator1'] : null,
                'nama_setup' => $jenis === 'SETUP' ? $validated['operator1'] : null,
                'no_jo' => $gabunganJo,
                'mesin_start' => $sekarang,
                'status_proses' => StatusProses::Running,
                'user_at' => 'api',
            ]);

            $mesin->update([
                'status_mesin' => $jenis === 'OPERATOR' ? StatusMesin::Running : StatusMesin::Maintance,
                'running_jo' => $gabunganJo,
                'user_at' => 'api',
            ]);

            MesinMappingJo::where('no_mesin', $mesin->no_mesin)
                ->whereIn('no_jo', $daftarJo)
                ->update(['status_jo' => StatusProses::Running->value, 'user_updated' => 'api']);

            return response()->json([
                'success' => true,
                'message' => "{$mesin->namaUntukTampilan()} dijalankan oleh {$validated['operator1']}",
                'history' => new HistoryResource($history),
                'mesin' => new MesinResource($mesin->load('activeHistory')),
            ], 201);
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

            $sekarang = now();
            $daftarJo = $history?->joList() ?? $mesin->runningJoList();
            $joSelesai = array_values(array_intersect($daftarJo, $validated['jo_selesai'] ?? []));

            if ($history) {
                $history->update([
                    'mesin_stop' => $sekarang,
                    'mesin_durasi' => $this->formatDurasi((int) $history->mesin_start->diffInSeconds($sekarang, true)),
                    'good' => $validated['good'] ?? $history->good,
                    'ng' => $validated['ng'] ?? $history->ng,
                    'sisa_jo' => $validated['sisa_jo'] ?? $history->sisa_jo,
                    'remark' => $validated['remark'] ?? $history->remark,
                    'status_proses' => StatusProses::Finish,
                ]);
            }

            $mesin->update([
                'status_mesin' => StatusMesin::Ready,
                'running_jo' => null,
                'user_at' => 'api',
            ]);

            if ($daftarJo) {
                MesinMappingJo::where('no_mesin', $mesin->no_mesin)
                    ->whereIn('no_jo', array_diff($daftarJo, $joSelesai))
                    ->where('status_jo', StatusProses::Running->value)
                    ->update(['status_jo' => StatusProses::Open->value, 'user_updated' => 'api']);

                MesinMappingJo::where('no_mesin', $mesin->no_mesin)
                    ->whereIn('no_jo', $joSelesai)
                    ->update(['status_jo' => StatusProses::Finish->value, 'user_updated' => 'api']);
            }

            return response()->json([
                'success' => true,
                'message' => "{$mesin->namaUntukTampilan()} dihentikan",
                'history' => $history ? new HistoryResource($history) : null,
                'mesin' => new MesinResource($mesin->load('activeHistory')),
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

    private function formatDurasi(int $detik): string
    {
        return sprintf('%02d:%02d:%02d', intdiv($detik, 3600), intdiv($detik % 3600, 60), $detik % 60);
    }

    private function error(string $message, int $code, MesinQr $mesin): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'mesin' => new MesinResource($mesin->load('activeHistory')),
        ], $code);
    }
}
