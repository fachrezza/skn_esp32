<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusMesin;
use App\Http\Controllers\Controller;
use App\Http\Resources\HistoryResource;
use App\Http\Resources\MesinResource;
use App\Models\MesinQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class KontrolMesinController extends Controller
{
    /**
     * GET /api/mesin   (?status=RUNNING, ?q=Haitian)
     * Daftar semua mesin dari mesin_qr (dipakai aplikasi Flutter untuk polling).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->merge(['status' => $request->filled('status') ? strtoupper((string) $request->input('status')) : null]);
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(StatusMesin::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $mesin = MesinQr::with('activeHistory')
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status_mesin', $status))
            ->when($validated['q'] ?? null, fn ($query, $q) => $query->where(function ($query) use ($q) {
                $query->where('no_mesin', 'like', "%$q%")->orWhere('nama_mesin', 'like', "%$q%");
            }))
            ->orderBy('no_mesin')
            ->get();

        return MesinResource::collection($mesin)->additional([
            'success' => true,
            'total' => $mesin->count(),
            'running' => $mesin->filter(fn (MesinQr $m) => $m->isRunning())->count(),
        ]);
    }

    /**
     * GET /api/mesin/{no_mesin}
     */
    public function show(MesinQr $mesin): MesinResource
    {
        return (new MesinResource($mesin->load('activeHistory')))->additional(['success' => true]);
    }

    /**
     * PUT /api/mesin/{no_mesin}/status   (body: {"status": "READY" | "RUNNING" | "MAINTANCE" | "OFF" | "RUSAK"})
     */
    public function updateStatus(Request $request, MesinQr $mesin): JsonResponse
    {
        $request->merge(['status' => strtoupper((string) $request->input('status'))]);
        $validated = $request->validate([
            'status' => ['required', Rule::enum(StatusMesin::class)],
        ]);

        return $this->ubahStatus($mesin, StatusMesin::from($validated['status']));
    }

    /**
     * POST /api/mesin/{no_mesin}/on   — status_mesin menjadi RUNNING.
     */
    public function on(MesinQr $mesin): JsonResponse
    {
        if ($mesin->isRunning()) {
            return $this->error("Mesin {$mesin->no_mesin} sudah menyala", 409, $mesin);
        }

        return $this->ubahStatus($mesin, StatusMesin::Running);
    }

    /**
     * POST /api/mesin/{no_mesin}/off  — status_mesin menjadi OFF.
     */
    public function off(MesinQr $mesin): JsonResponse
    {
        if (! $mesin->isRunning()) {
            return $this->error("Mesin {$mesin->no_mesin} sudah mati", 409, $mesin);
        }

        return $this->ubahStatus($mesin, StatusMesin::Off);
    }

    /**
     * POST /api/mesin/{no_mesin}/toggle — ON/OFF bergantian.
     */
    public function toggle(MesinQr $mesin): JsonResponse
    {
        return $mesin->isRunning() ? $this->off($mesin) : $this->on($mesin);
    }

    /**
     * PUT /api/mesin/{no_mesin}/esp   (body: {"uid_esp": "9C12B8F23A08"} atau {"uid_esp": null} untuk melepas)
     * Memetakan ESP32 ke mesin lewat kolom mesin_qr.uid_esp.
     */
    public function assignEsp(Request $request, MesinQr $mesin): JsonResponse
    {
        $validated = $request->validate([
            'uid_esp' => [
                'present',
                'nullable',
                'string',
                'max:117',
                Rule::unique('mesin_qr', 'uid_esp')->ignore($mesin->no_mesin, 'no_mesin'),
            ],
        ]);

        $mesin->update(['uid_esp' => $validated['uid_esp'] ?: null]);

        return response()->json([
            'success' => true,
            'message' => $mesin->uid_esp
                ? "ESP {$mesin->uid_esp} dipetakan ke {$mesin->namaUntukTampilan()}"
                : "ESP dilepas dari {$mesin->namaUntukTampilan()}",
            'mesin' => new MesinResource($mesin->load('activeHistory')),
        ]);
    }

    /**
     * GET /api/mesin/{no_mesin}/jo   (?status=OPEN)
     * Antrian JO mesin dari mesin_mapping_jo.
     */
    public function jo(Request $request, MesinQr $mesin): JsonResponse
    {
        $jo = $mesin->mappingJo()
            ->when($request->filled('status'), fn ($query) => $query->where('status_jo', strtoupper((string) $request->input('status'))))
            ->orderBy('prioritas')
            ->orderBy('tgl_jo')
            ->get();

        return response()->json([
            'success' => true,
            'no_mesin' => $mesin->no_mesin,
            'data' => $jo,
        ]);
    }

    /**
     * GET /api/mesin/{no_mesin}/history   (?per_page=20)
     */
    public function history(Request $request, MesinQr $mesin): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return HistoryResource::collection(
            $mesin->histories()->orderByDesc('id_history')->paginate($perPage)
        )->additional(['success' => true]);
    }

    private function ubahStatus(MesinQr $mesin, StatusMesin $statusBaru): JsonResponse
    {
        if ($mesin->status_mesin !== $statusBaru) {
            $mesin->update([
                'status_mesin' => $statusBaru,
                'user_at' => 'api',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "{$mesin->namaUntukTampilan()} sekarang {$statusBaru->value}",
            'mesin' => new MesinResource($mesin->load('activeHistory')),
        ]);
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
