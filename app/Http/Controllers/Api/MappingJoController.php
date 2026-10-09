<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusProses;
use App\Http\Controllers\Controller;
use App\Models\MesinMappingJo;
use App\Models\MesinQr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kelola antrian JO per mesin (mesin_mapping_jo). Primary key tabelnya komposit (no_jo, no_mesin),
 * jadi update/hapus selalu memakai query dengan kedua kolom, bukan save() pada model.
 */
class MappingJoController extends Controller
{
    /**
     * POST /api/mesin/{no_mesin}/jo
     * Body: {"no_jo": "JO1QA00101", "produk": "ZLTUBWT30004", "boms": "...", "qtyjo": 2100, "prioritas": 1, "tgl_jo": "2026-10-01"}
     */
    public function store(Request $request, MesinQr $mesin): JsonResponse
    {
        $request->merge(['no_jo' => strtoupper(trim((string) $request->input('no_jo')))]);

        $validated = $request->validate([
            'no_jo' => [
                'required',
                'string',
                'max:71',
                'not_regex:/,/',
                Rule::unique('mesin_mapping_jo', 'no_jo')->where('no_mesin', $mesin->no_mesin),
            ],
            'produk' => ['nullable', 'string', 'max:111'],
            'boms' => ['nullable', 'string', 'max:111'],
            'qtyjo' => ['required', 'numeric', 'min:0'],
            'prioritas' => ['nullable', 'integer', 'min:0'],
            'tgl_jo' => ['nullable', 'date'],
        ], [
            'no_jo.unique' => 'JO ini sudah ada di mesin tersebut.',
        ]);

        MesinMappingJo::create($validated + [
            'no_mesin' => $mesin->no_mesin,
            'tgl_jo' => $validated['tgl_jo'] ?? now()->toDateString(),
            'status_jo' => StatusProses::Open,
            'user_at' => 'api',
        ]);

        return response()->json([
            'success' => true,
            'message' => "JO {$validated['no_jo']} ditambahkan ke mesin {$mesin->no_mesin}",
            'data' => $this->cari($mesin, $validated['no_jo'])->first(),
        ], 201);
    }

    /**
     * PUT /api/mesin/{no_mesin}/jo/{no_jo}
     * Body (semua opsional): {"produk", "boms", "qtyjo", "prioritas", "tgl_jo", "status_jo": "OPEN" | "HOLD" | "PENDING" | "FINISH"}
     */
    public function update(Request $request, MesinQr $mesin, string $noJo): JsonResponse
    {
        $jo = $this->cari($mesin, $noJo)->firstOrFail();

        if ($request->filled('status_jo')) {
            $request->merge(['status_jo' => strtoupper((string) $request->input('status_jo'))]);
        }

        $validated = $request->validate([
            'produk' => ['sometimes', 'nullable', 'string', 'max:111'],
            'boms' => ['sometimes', 'nullable', 'string', 'max:111'],
            'qtyjo' => ['sometimes', 'required', 'numeric', 'min:0'],
            'prioritas' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'tgl_jo' => ['sometimes', 'nullable', 'date'],
            'status_jo' => ['sometimes', Rule::in(['OPEN', 'HOLD', 'PENDING', 'FINISH'])],
        ]);

        if (isset($validated['status_jo']) && $jo->status_jo === StatusProses::Running) {
            return $this->error("JO {$noJo} sedang berjalan, hentikan mesin dulu untuk mengubah statusnya.", 409);
        }

        if ($validated) {
            $this->cari($mesin, $noJo)->update($validated + ['user_updated' => 'api']);
        }

        return response()->json([
            'success' => true,
            'message' => "JO {$noJo} diperbarui",
            'data' => $this->cari($mesin, $noJo)->first(),
        ]);
    }

    /**
     * DELETE /api/mesin/{no_mesin}/jo/{no_jo}
     */
    public function destroy(MesinQr $mesin, string $noJo): JsonResponse
    {
        $jo = $this->cari($mesin, $noJo)->firstOrFail();

        if ($jo->status_jo === StatusProses::Running) {
            return $this->error("JO {$noJo} sedang berjalan dan tidak bisa dihapus.", 409);
        }

        $this->cari($mesin, $noJo)->delete();

        return response()->json([
            'success' => true,
            'message' => "JO {$noJo} dihapus dari mesin {$mesin->no_mesin}",
        ]);
    }

    /**
     * @return Builder<MesinMappingJo>
     */
    private function cari(MesinQr $mesin, string $noJo): Builder
    {
        return MesinMappingJo::where('no_mesin', $mesin->no_mesin)->where('no_jo', $noJo);
    }

    private function error(string $message, int $code): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }
}
