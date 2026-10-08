<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusProses;
use App\Http\Controllers\Controller;
use App\Http\Resources\HistoryResource;
use App\Models\MesinHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class HistoryController extends Controller
{
    /**
     * GET /api/history
     * Filter: ?no_mesin=1400&status_proses=RUNNING&jenis_operator=OPERATOR&tgl_dari=2026-10-01&tgl_sampai=2026-10-08&no_jo=JO1QA00101&per_page=20
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'no_mesin' => ['nullable', 'string'],
            'no_jo' => ['nullable', 'string'],
            'status_proses' => ['nullable', Rule::enum(StatusProses::class)],
            'jenis_operator' => ['nullable', Rule::in(['OPERATOR', 'TEKNISI', 'SETUP'])],
            'tgl_dari' => ['nullable', 'date'],
            'tgl_sampai' => ['nullable', 'date', 'after_or_equal:tgl_dari'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $history = MesinHistory::query()
            ->when($validated['no_mesin'] ?? null, fn ($query, $v) => $query->where('no_mesin', $v))
            ->when($validated['no_jo'] ?? null, fn ($query, $v) => $query->where('no_jo', 'like', "%$v%"))
            ->when($validated['status_proses'] ?? null, fn ($query, $v) => $query->where('status_proses', $v))
            ->when($validated['jenis_operator'] ?? null, fn ($query, $v) => $query->where('jenis_operator', $v))
            ->when($validated['tgl_dari'] ?? null, fn ($query, $v) => $query->whereDate('tgl_running', '>=', $v))
            ->when($validated['tgl_sampai'] ?? null, fn ($query, $v) => $query->whereDate('tgl_running', '<=', $v))
            ->orderByDesc('id_history')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return HistoryResource::collection($history)->additional(['success' => true]);
    }

    /**
     * GET /api/history/{id_history}
     * Detail riwayat beserta hasil per JO (mesin_history_jo) dan log masalah (mesin_troublle_log).
     */
    public function show(MesinHistory $history): HistoryResource
    {
        return (new HistoryResource($history->load(['mesin', 'joDetails', 'troubleLogs'])))
            ->additional(['success' => true]);
    }
}
