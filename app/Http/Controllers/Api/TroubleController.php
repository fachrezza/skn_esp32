<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MesinTroubleItem;
use App\Models\MesinTroubleLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TroubleController extends Controller
{
    /**
     * GET /api/trouble-items   (?jenis=MASALAH | MOULD | OPERATOR | SETTING)
     */
    public function items(Request $request): JsonResponse
    {
        $items = MesinTroubleItem::aktif()
            ->when($request->filled('jenis'), fn ($query) => $query->where('jenis', strtoupper((string) $request->input('jenis'))))
            ->orderBy('jenis')
            ->orderBy('kditem')
            ->get(['kditem', 'nmitem', 'jenis']);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * GET /api/trouble-logs   (?id_history=1169&kditem=MOL1&jenis=MOULD&per_page=20)
     */
    public function logs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_history' => ['nullable', 'integer'],
            'kditem' => ['nullable', 'string'],
            'jenis' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = MesinTroubleLog::query()
            ->when($validated['id_history'] ?? null, fn ($query, $v) => $query->where('id_history', $v))
            ->when($validated['kditem'] ?? null, fn ($query, $v) => $query->where('kditem', $v))
            ->when($validated['jenis'] ?? null, fn ($query, $v) => $query->where('jenis', strtoupper($v)))
            ->orderByDesc('id_log')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json(['success' => true] + $logs->toArray());
    }
}
