<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusMesin;
use App\Http\Controllers\Controller;
use App\Models\MesinQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EspController extends Controller
{
    /**
     * POST /api/esp/ping   (header X-ESP-ID: 9C12B8F23A08)
     * Dipanggil ESP32 saat baru terhubung / sebagai heartbeat. Mengembalikan mesin yang dipetakan
     * lewat mesin_qr.uid_esp.
     */
    public function ping(Request $request): JsonResponse
    {
        $mesin = $this->mesinDariRequest($request);

        if (! $mesin) {
            return $this->belumTerdaftar();
        }

        return response()->json([
            'success' => true,
            'message' => "Terhubung sebagai {$mesin->namaUntukTampilan()}",
            'esp' => $this->infoEsp($mesin),
        ]);
    }

    /**
     * GET /api/esp/state   (header X-ESP-ID, atau ?id_esp=9C12B8F23A08)
     * Dipakai ESP32 untuk polling: nama mesin, status_mesin, JO berjalan, dan timer_sec.
     */
    public function state(Request $request): JsonResponse
    {
        $mesin = $this->mesinDariRequest($request);

        if (! $mesin) {
            return $this->belumTerdaftar();
        }

        return response()->json([
            'success' => true,
            'esp' => $this->infoEsp($mesin),
        ]);
    }

    /**
     * POST /api/esp/status   (header X-ESP-ID, body: {"status": "READY" | "RUNNING" | "MAINTANCE" | "OFF" | "RUSAK"})
     * Dipanggil saat tombol status di ESP32 diklik; mengubah mesin_qr.status_mesin.
     */
    public function status(Request $request): JsonResponse
    {
        $mesin = $this->mesinDariRequest($request);

        if (! $mesin) {
            return $this->belumTerdaftar();
        }

        $request->merge(['status' => strtoupper((string) $request->input('status'))]);
        $validated = $request->validate([
            'status' => ['required', Rule::enum(StatusMesin::class)],
        ]);

        $statusBaru = StatusMesin::from($validated['status']);

        // Klik tombol yang sama berulang kali tidak me-reset timer
        if ($mesin->status_mesin !== $statusBaru) {
            $mesin->update([
                'status_mesin' => $statusBaru,
                'user_at' => 'esp32',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "{$mesin->namaUntukTampilan()} sekarang {$statusBaru->value}",
            'esp' => $this->infoEsp($mesin),
        ]);
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
            'status' => $mesin->status_mesin?->value,
            'running_jo' => $mesin->runningJoList(),
            'operator' => $mesin->activeHistory?->operator1,
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
