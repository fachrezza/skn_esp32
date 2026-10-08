<?php

namespace App\Http\Middleware;

use App\Models\MesinQr;
use Closure;
use Illuminate\Http\Request;
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
            ? MesinQr::with('activeHistory')->where('uid_esp', $uid)->first()
            : null;

        $request->attributes->set('esp', $mesin);

        return $next($request);
    }
}
