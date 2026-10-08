<?php

namespace App\Enums;

/**
 * Nilai kolom mesin_history.status_proses dan mesin_mapping_jo.status_jo.
 */
enum StatusProses: string
{
    case Open = 'OPEN';
    case Running = 'RUNNING';
    case Finish = 'FINISH';
    case Pending = 'PENDING';
    case Hold = 'HOLD';
}
