<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusMesin;
use App\Http\Controllers\Controller;
use App\Models\MesinHistory;
use App\Models\MesinMappingJo;
use App\Models\MesinQr;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Ringkasan monitoring: jumlah mesin per status, JO per status, dan produksi hari ini.
     */
    public function index(): JsonResponse
    {
        $statusMesin = MesinQr::selectRaw('status_mesin, count(*) as jumlah')
            ->groupBy('status_mesin')
            ->pluck('jumlah', 'status_mesin');

        $statusJo = MesinMappingJo::selectRaw('status_jo, count(*) as jumlah')
            ->groupBy('status_jo')
            ->pluck('jumlah', 'status_jo');

        $hariIni = MesinHistory::whereDate('tgl_running', today())
            ->selectRaw('count(*) as sesi, coalesce(sum(good), 0) as good, coalesce(sum(ng), 0) as ng')
            ->first();

        return response()->json([
            'success' => true,
            'mesin' => collect(StatusMesin::cases())
                ->mapWithKeys(fn (StatusMesin $s) => [$s->value => (int) ($statusMesin[$s->value] ?? 0)])
                ->put('TOTAL', (int) $statusMesin->sum()),
            'jo' => $statusJo->map(fn ($jumlah) => (int) $jumlah),
            'produksi_hari_ini' => [
                'tanggal' => today()->toDateString(),
                'sesi' => (int) $hariIni->sesi,
                'good' => (float) $hariIni->good,
                'ng' => (float) $hariIni->ng,
            ],
        ]);
    }
}
