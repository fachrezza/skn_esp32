<?php

namespace App\Enums;

/**
 * Nilai kolom mesin_qr.status_mesin (ejaan "MAINTANCE" mengikuti enum di database).
 */
enum StatusMesin: string
{
    case Ready = 'READY';
    case Running = 'RUNNING';
    case Maintance = 'MAINTANCE';
    case Off = 'OFF';
    case Rusak = 'RUSAK';
}
