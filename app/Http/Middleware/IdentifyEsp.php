<?php

namespace App\Http\Middleware;

use App\Models\MesinQr;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencari mesin milik ESP32 berdasarkan header X-ESP-ID (atau ?id_esp=) yang dicocokkan
 * dengan kolom mesin_qr.uid_esp, lalu menyimpannya di atribut request "esp".
 */
class IdentifyEsp
{
    public function handle(Request $request, Closure $next): Response
    {
        $uid = trim((string) ($request->header('X-ESP-ID') ?? $request->input('id_esp')));

        $mesin = $uid !== ''
            ? MesinQr::with(['activeHistory', 'pendingHistory'])->where('uid_esp', $uid)->first()
            : null;

        if (! $mesin) {
            Log::warning('ESP belum terdaftar di mesin_qr.uid_esp', [
                'uid_esp' => $uid !== '' ? $uid : null,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
        }

        $request->attributes->set('esp', $mesin);

        return $next($request);
    }
}
