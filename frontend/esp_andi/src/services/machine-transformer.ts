import { MachineCardDTO, MachineKpiDTO, MachineStatus, MesinMappingJoRaw, MesinQrRaw } from '@/types/machine';

/**
 * Sanitizes status string legacy.
 * Database enum contains 'MAINTANCE' which represents Maintenance/Setup.
 */
export function normalizeMachineStatus(rawStatus?: string | null): MachineStatus {
  if (!rawStatus) return 'OFF';
  const upper = rawStatus.trim().toUpperCase();
  if (upper === 'RUNNING') return 'RUNNING';
  if (upper === 'READY' || upper === 'IDLE') return 'READY';
  if (upper === 'MAINTANCE' || upper === 'MAINTENANCE' || upper === 'SETUP') return 'MAINTANCE';
  if (upper === 'RUSAK' || upper === 'BREAKDOWN' || upper === 'ALARM') return 'RUSAK';
  return 'OFF';
}

/**
 * Transforms raw legacy tables into clean MachineCardDTO
 */
export function transformToMachineCardDTO(
  mesin: MesinQrRaw,
  jo?: MesinMappingJoRaw | null,
  extra?: Partial<MachineCardDTO>
): MachineCardDTO {
  const status = normalizeMachineStatus(mesin.status_mesin);

  let runningJoDTO: MachineCardDTO['running_jo'] = null;

  if (jo) {
    const target = Number(jo.qtyjo ?? 0);
    const good = Number(jo.qty_good ?? 0);
    const ng = Number(jo.qty_ng ?? 0);
    const sisa = Number(jo.sisa_jo ?? Math.max(0, target - good));
    const progress = target > 0 ? Math.min(100, Math.round((good / target) * 1000) / 10) : 0;

    runningJoDTO = {
      no_jo: jo.no_jo,
      prioritas: jo.prioritas ?? 1,
      produk: jo.produk ?? 'Produk Standar Sanken',
      target_qty: target,
      qty_good: good,
      qty_ng: ng,
      sisa_jo: sisa,
      progress_percent: progress,
    };
  }

  return {
    no_mesin: mesin.no_mesin,
    nama_mesin: mesin.nama_mesin ?? `Injection Unit ${mesin.no_mesin}`,
    tonnage: extra?.tonnage ?? '250T',
    mold_code: extra?.mold_code ?? 'MOLD-STD',
    status,
    status_label:
      status === 'RUNNING'
        ? 'BERJALAN'
        : status === 'READY'
        ? 'SIAP'
        : status === 'MAINTANCE'
        ? 'SETUP'
        : status === 'RUSAK'
        ? 'RUSAK'
        : 'MATI',
    uid_esp: mesin.uid_esp ?? 'ESP-8266-XX',
    cycle_time: extra?.cycle_time ?? 22.5,
    is_selected: extra?.is_selected ?? false,
    running_jo: runningJoDTO,
    alert_message: extra?.alert_message ?? null,
    downtime_minutes: extra?.downtime_minutes ?? null,
    est_ready: extra?.est_ready ?? null,
    setup_progress: extra?.setup_progress ?? null,
    notes: mesin.note_mesin ?? extra?.notes ?? null,
  };
}

/**
 * Computes live aggregated KPIs for the top KPI ribbon
 */
export function calculateMachineKpi(machines: MachineCardDTO[]): MachineKpiDTO {
  let running = 0;
  let ready = 0;
  let maintance = 0;
  let breakdown = 0;
  let off = 0;

  let totalGood = 0;
  let totalNg = 0;
  let totalSisa = 0;
  let activeJOs = 0;

  let criticalAlarmLabel = 'TIDAK ADA ALARM KRITIS';
  let criticalDowntimeDuration = '0m';

  for (const m of machines) {
    switch (m.status) {
      case 'RUNNING':
        running++;
        break;
      case 'READY':
        ready++;
        break;
      case 'MAINTANCE':
        maintance++;
        break;
      case 'RUSAK':
        breakdown++;
        if (m.alert_message) {
          criticalAlarmLabel = `${m.no_mesin}: ${m.alert_message}`;
          criticalDowntimeDuration = m.downtime_minutes ? `${m.downtime_minutes}m DOWNTIME` : '42m DOWNTIME';
        }
        break;
      case 'OFF':
        off++;
        break;
    }

    if (m.running_jo) {
      activeJOs++;
      totalGood += m.running_jo.qty_good;
      totalNg += m.running_jo.qty_ng;
      totalSisa += m.running_jo.sisa_jo;
    }
  }

  const totalOutput = totalGood + totalNg;
  const defectRate = totalOutput > 0 ? (totalNg / totalOutput) * 100 : 0;

  return {
    total_machines: machines.length,
    running_count: running,
    ready_count: ready,
    maintance_count: maintance,
    breakdown_count: breakdown,
    off_count: off,
    shift_good_qty: totalGood,
    shift_ng_qty: totalNg,
    defect_rate_percent: Math.round(defectRate * 100) / 100,
    hourly_rate: 2306,
    trend_vs_target: 4.2,
    active_jo_count: activeJOs,
    on_schedule_count: Math.max(0, activeJOs - breakdown),
    critical_jo_count: breakdown,
    total_sisa_qty: totalSisa,
    active_alarm_count: breakdown,
    critical_downtime_label: criticalAlarmLabel,
    critical_downtime_duration: criticalDowntimeDuration,
  };
}

/**
 * Transforms Laravel MesinResource into MachineCardDTO
 */
export function transformLaravelMesinToCardDTO(
  resource: import('@/types/machine').LaravelMesinResource,
  mappingJo?: import('@/types/machine').LaravelMappingJoItem | null
): MachineCardDTO {
  const status = normalizeMachineStatus(resource.status_mesin);
  const statusLabel =
    status === 'RUNNING'
      ? 'BERJALAN'
      : status === 'READY'
      ? 'SIAP'
      : status === 'MAINTANCE'
      ? 'SETUP'
      : status === 'RUSAK'
      ? 'RUSAK'
      : 'MATI';

  let runningJoDTO: MachineCardDTO['running_jo'] = null;

  if (mappingJo) {
    const target = Number(mappingJo.qtyjo ?? 0);
    const good = Number(mappingJo.qty_good ?? 0);
    const ng = Number(mappingJo.qty_ng ?? 0);
    const sisa = Number(mappingJo.sisa_jo ?? Math.max(0, target - good));
    const progress = target > 0 ? Math.min(100, Math.round((good / target) * 1000) / 10) : 0;

    runningJoDTO = {
      no_jo: mappingJo.no_jo,
      prioritas: mappingJo.prioritas ?? 1,
      produk: mappingJo.produk ?? 'Produk Sanken',
      target_qty: target,
      qty_good: good,
      qty_ng: ng,
      sisa_jo: sisa,
      progress_percent: progress,
    };
  } else if (resource.active_history) {
    const good = Number(resource.active_history.good ?? 0);
    const ng = Number(resource.active_history.ng ?? 0);
    const sisa = Number(resource.active_history.sisa_jo ?? 0);
    const target = good + sisa > 0 ? good + sisa : 1000;
    const progress = target > 0 ? Math.min(100, Math.round((good / target) * 1000) / 10) : 0;
    
    const rawJo = resource.active_history.no_jo;
    const joCode =
      Array.isArray(rawJo) && rawJo.length > 0
        ? rawJo[0]
        : typeof rawJo === 'string' && rawJo
        ? rawJo
        : Array.isArray(resource.running_jo) && resource.running_jo.length > 0
        ? resource.running_jo[0]
        : 'JO-AKTIF';

    const operatorName = resource.active_history.operator1;
    const productLabel = operatorName
      ? `Op: ${operatorName}`
      : `Produksi ${resource.nama_mesin || resource.no_mesin}`;

    runningJoDTO = {
      no_jo: joCode,
      prioritas: 1,
      produk: productLabel,
      target_qty: target,
      qty_good: good,
      qty_ng: ng,
      sisa_jo: sisa,
      progress_percent: progress,
    };
  } else if (Array.isArray(resource.running_jo) && resource.running_jo.length > 0) {
    const joCode = resource.running_jo[0];
    runningJoDTO = {
      no_jo: joCode,
      prioritas: 1,
      produk: `Produksi ${resource.nama_mesin || resource.no_mesin}`,
      target_qty: 1000,
      qty_good: 0,
      qty_ng: 0,
      sisa_jo: 1000,
      progress_percent: 0,
    };
  }

  const downtimeMinutes = status === 'RUSAK' && resource.timer_sec > 0
    ? Math.max(1, Math.round(resource.timer_sec / 60))
    : null;

  // Smart tonnage extractor (e.g. '600 B' -> '600T')
  const matchedTonnage = resource.no_mesin.match(/\d+/)?.[0];
  const tonnage = matchedTonnage ? `${matchedTonnage}T` : '250T';

  return {
    no_mesin: resource.no_mesin,
    nama_mesin: resource.nama_tampilan || resource.nama_mesin || `Unit Injeksi ${resource.no_mesin}`,
    tonnage,
    mold_code: 'MOLD-SANKEN',
    status,
    status_label: statusLabel,
    uid_esp: resource.uid_esp || 'TIDAK TERHUBUNG',
    cycle_time: Number(resource.active_history?.actual_cycletime ?? 0),
    running_jo: runningJoDTO,
    alert_message: status === 'RUSAK' ? resource.note_mesin || 'PERINGATAN DOWNTIME TERDETEKSI' : null,
    downtime_minutes: downtimeMinutes,
    notes: resource.note_mesin,
    timer_sec: resource.timer_sec ?? (resource.active_history?.durasi_berjalan_sec ?? 0),
    started_at: resource.active_history?.mesin_start || resource.updated_at || null,
  };
}

/**
 * Transforms Laravel Dashboard Response into MachineKpiDTO
 */
export function transformLaravelDashboardToKpiDTO(
  dash: import('@/types/machine').LaravelDashboardResponse,
  machines: MachineCardDTO[]
): MachineKpiDTO {
  const goodToday = Number(dash.produksi_hari_ini?.good ?? 0);
  const ngToday = Number(dash.produksi_hari_ini?.ng ?? 0);
  const totalProduced = goodToday + ngToday;
  const defectRate = totalProduced > 0 ? (ngToday / totalProduced) * 100 : 0;

  const runningCount = dash.mesin.RUNNING ?? 0;
  const readyCount = dash.mesin.READY ?? 0;
  const maintanceCount = dash.mesin.MAINTANCE ?? 0;
  const breakdownCount = dash.mesin.RUSAK ?? 0;
  const offCount = dash.mesin.OFF ?? 0;
  const totalMachines = dash.mesin.TOTAL || (runningCount + readyCount + maintanceCount + breakdownCount + offCount);

  // Calculate sisa JO from active machines
  let totalSisa = 0;
  let activeJOs = 0;
  let criticalDowntimeLabel = 'TIDAK ADA ALARM KRITIS';
  let criticalDowntimeDuration = '0m';

  for (const m of machines) {
    if (m.running_jo) {
      activeJOs++;
      totalSisa += m.running_jo.sisa_jo;
    }
    if (m.status === 'RUSAK' && m.alert_message) {
      criticalDowntimeLabel = `${m.no_mesin}: ${m.alert_message}`;
      criticalDowntimeDuration = m.downtime_minutes ? `${m.downtime_minutes}m DOWNTIME` : '42m DOWNTIME';
    }
  }

  return {
    total_machines: totalMachines,
    running_count: runningCount,
    ready_count: readyCount,
    maintance_count: maintanceCount,
    breakdown_count: breakdownCount,
    off_count: offCount,
    shift_good_qty: goodToday,
    shift_ng_qty: ngToday,
    defect_rate_percent: Math.round(defectRate * 100) / 100,
    hourly_rate: 2306,
    trend_vs_target: 4.2,
    active_jo_count: activeJOs || Number(dash.jo?.RUNNING ?? 0) + Number(dash.jo?.OPEN ?? 0),
    on_schedule_count: Math.max(0, activeJOs - breakdownCount),
    critical_jo_count: breakdownCount,
    total_sisa_qty: totalSisa,
    active_alarm_count: breakdownCount,
    critical_downtime_label: criticalDowntimeLabel,
    critical_downtime_duration: criticalDowntimeDuration,
  };
}
