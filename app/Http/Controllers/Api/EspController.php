<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusMesin;
use App\Http\Controllers\Controller;
use App\Models\MesinHistory;
use App\Models\MesinQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EspController extends Controller
{
    /**
     * POST /api/esp/ping   (header X-ESP-ID: 9C12B8F23A08)
     * Dipanggil ESP32 sekali saat berhasil terhubung (bukan heartbeat). Mesin yang OFF / belum
     * punya status langsung menjadi READY; status RUNNING, MAINTANCE, dan RUSAK tidak diubah agar
     * ESP yang restart di tengah produksi tidak mereset timer. Mengembalikan mesin yang dipetakan
     * lewat mesin_qr.uid_esp.
     */
    public function ping(Request $request): JsonResponse
    {
        $mesin = $this->mesinDariRequest($request);

        if (! $mesin) {
            return $this->belumTerdaftar();
        }

        if ($mesin->status_mesin === null || $mesin->status_mesin === StatusMesin::Off) {
            $mesin->update([
                'status_mesin' => StatusMesin::Ready,
                'user_at' => 'esp32',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Terhubung sebagai {$mesin->namaUntukTampilan()}",
            'esp' => $this->infoEsp($mesin),
        ]);
    }

    /**
     * GET /api/esp/state   (header X-ESP-ID, atau ?id_esp=9C12B8F23A08)
     * Dipanggil ESP32 hanya saat menyala dan saat menu "Refresh Data" (tidak ada ping/polling berkala),
     * jadi sekaligus menandai ESP terhubung: mesin OFF menjadi READY.
     */
    public function state(Request $request): JsonResponse
    {
        $mesin = $this->mesinDariRequest($request);

        if (! $mesin) {
            return $this->belumTerdaftar();
        }

        if ($mesin->status_mesin === null || $mesin->status_mesin === StatusMesin::Off) {
            $mesin->update([
                'status_mesin' => StatusMesin::Ready,
                'user_at' => 'esp32',
            ]);
        }

        return response()->json([
            'success' => true,
            'esp' => $this->infoEsp($mesin),
        ]);
    }

    /**
     * POST /api/esp/status   (header X-ESP-ID, body: {"status": "PRODUKSI" | "MOULD" | "SETTER" | "READY"})
     * Dipanggil saat tombol status di ESP32 diklik.
     * PRODUKSI / MOULD / SETTER memulai (atau melanjutkan) sesi operator yang disiapkan di dashboard; hanya boleh
     * oleh jenis operator yang sesuai (OPERATOR / TEKNISI / SETUP). 409 bila belum ada operator / jenis tidak sesuai.
     * READY / OFF saat sesi berjalan = STOP: mesin_history ditutup.
     * Nilai lama RUNNING / MAINTANCE / RUSAK / OFF tetap diterima.
     */
    public function status(Request $request): JsonResponse
    {
        $mesin = $this->mesinDariRequest($request);

        if (! $mesin) {
            return $this->belumTerdaftar();
        }

        $request->merge(['status' => strtoupper(trim((string) $request->input('status')))]);
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                ...array_values(MesinHistory::STATUS_ESP),
                ...array_column(StatusMesin::cases(), 'value'),
            ])],
        ]);

        $jenisDiminta = array_search($validated['status'], MesinHistory::STATUS_ESP, true);

        if ($jenisDiminta !== false || $validated['status'] === StatusMesin::Running->value) {
            $sesi = $mesin->activeHistory ?? $mesin->pendingHistory;

            if (! $sesi) {
                return $this->ditolak($mesin, 'Belum ada operator');
            }

            if ($jenisDiminta !== false && $sesi->jenis_operator !== $jenisDiminta) {
                return $this->ditolak($mesin, "{$validated['status']} khusus {$jenisDiminta}");
            }

            $mesin->jalankan('esp32');

            return $this->berhasil($mesin);
        }

        $statusBaru = StatusMesin::from($validated['status']);

        if ($mesin->activeHistory && in_array($statusBaru, [StatusMesin::Ready, StatusMesin::Off], true)) {
            // STOP dari ESP: sesi produksi ditutup, JO yang berjalan kembali OPEN
            DB::transaction(fn () => $mesin->hentikanProduksi('esp32', statusAkhir: $statusBaru));
        } elseif ($mesin->status_mesin !== $statusBaru) {
            // Klik tombol yang sama berulang kali tidak me-reset timer
            $mesin->update([
                'status_mesin' => $statusBaru,
                'user_at' => 'esp32',
            ]);
        }

        return $this->berhasil($mesin);
    }

    private function berhasil(MesinQr $mesin): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => "{$mesin->namaUntukTampilan()} sekarang {$mesin->statusTampilan()}",
            'esp' => $this->infoEsp($mesin),
        ]);
    }

    /**
     * Pesan dibuat pendek karena LCD ESP32 hanya menampilkan 25 karakter.
     */
    private function ditolak(MesinQr $mesin, string $pesan): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $pesan,
            'esp' => $this->infoEsp($mesin),
        ], 409);
    }

    private function mesinDariRequest(Request $request): ?MesinQr
    {
        return $request->attributes->get('esp');
    }

    /**
     * @return array<string, mixed>
     */
    private function infoEsp(MesinQr $mesin): array
    {
        return [
            'id_esp' => $mesin->uid_esp,
            'no_mesin' => $mesin->no_mesin,
            'nama_mesin' => $mesin->namaUntukTampilan(),
            'status' => $mesin->statusTampilan(),
            'status_mesin' => $mesin->status_mesin?->value,
            'running_jo' => $mesin->runningJoList(),
            'siap_running' => $mesin->isSiapRunning(),
            'sesi_berjalan' => $mesin->activeHistory !== null,
            'jenis_operator' => ($mesin->activeHistory ?? $mesin->pendingHistory)?->jenis_operator,
            'operator' => $mesin->namaOperator(),
            'timer_sec' => $mesin->durasiStatus(),
        ];
    }

    private function belumTerdaftar(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'ESP belum terdaftar di mesin_qr.uid_esp atau header X-ESP-ID tidak dikirim',
        ], 404);
    }
}
