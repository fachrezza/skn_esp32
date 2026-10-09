/**
 * Domain types & legacy database contracts for Sanken Industrial IIoT Shop Floor
 * Conforms to database table: mesin_qr, mesin_mapping_jo, mesin_troublle_log
 */

export type MachineStatus = 'RUNNING' | 'READY' | 'MAINTANCE' | 'RUSAK' | 'OFF';

export type MachineFilterType = 'ALL' | 'RUNNING' | 'ALARM' | 'READY_SETUP';

export interface MesinQrRaw {
  no_mesin: string;
  nama_mesin?: string | null;
  image_qr?: string | null;
  note_mesin?: string | null;
  pin_mesin?: string | null;
  status_mesin: MachineStatus;
  created_at?: string | null;
  user_at?: string | null;
  updated_at?: string | null;
  deleted_at?: string | null;
  running_jo?: string | null;
  uid_esp?: string | null;
}

export interface MesinMappingJoRaw {
  no_jo: string;
  prioritas?: number | null;
  no_mesin: string;
  tgl_jo?: string | null;
  boms?: string | null;
  produk?: string | null;
  qtyjo?: number | null;
  status_jo?: 'OPEN' | 'RUNNING' | 'FINISH' | 'HOLD' | 'PENDING';
  qty_good?: number | null;
  qty_ng?: number | null;
  sisa_jo?: number | null;
  runner?: number | null;
  user_updated?: string | null;
  updated_at?: string | null;
}

export interface MesinTroubleLogRaw {
  id_log: number;
  id_history?: number | null;
  kduser?: string | null;
  kditem?: string | null;
  nmitem?: string | null;
  jenis?: string | null;
  keterangan?: string | null;
  created_at?: string | null;
}

/**
 * Normalized UI Presentational DTO
 * Cleaned, non-nullable where appropriate, ready for UI display
 */
export interface MachineCardDTO {
  no_mesin: string;
  nama_mesin: string;
  tonnage: string;
  mold_code: string;
  status: MachineStatus;
  status_label: string;
  uid_esp: string;
  cycle_time: number; // in seconds
  is_selected?: boolean;

  // Active Job Order
  running_jo?: {
    no_jo: string;
    prioritas: number;
    produk: string;
    target_qty: number;
    qty_good: number;
    qty_ng: number;
    sisa_jo: number;
    progress_percent: number;
  } | null;

  // Alert / Downtime / Maintenance details
  alert_message?: string | null;
  downtime_minutes?: number | null;
  est_ready?: string | null;
  setup_progress?: number | null;
  notes?: string | null;
}

export interface MachineKpiDTO {
  total_machines: number;
  running_count: number;
  ready_count: number;
  maintance_count: number;
  breakdown_count: number;
  off_count: number;

  shift_good_qty: number;
  shift_ng_qty: number;
  defect_rate_percent: number;
  hourly_rate: number;
  trend_vs_target: number;

  active_jo_count: number;
  on_schedule_count: number;
  critical_jo_count: number;
  total_sisa_qty: number;

  active_alarm_count: number;
  critical_downtime_label: string;
  critical_downtime_duration: string;
}

/**
 * Exact payload contracts from Laravel Backend API
 */
export interface LaravelDashboardResponse {
  success: boolean;
  mesin: {
    READY: number;
    RUNNING: number;
    MAINTANCE: number;
    OFF: number;
    RUSAK: number;
    TOTAL: number;
  };
  jo: Record<string, number>;
  produksi_hari_ini: {
    tanggal: string;
    sesi: number;
    good: number;
    ng: number;
  };
}

export interface LaravelMesinResource {
  no_mesin: string;
  nama_mesin: string | null;
  nama_tampilan: string;
  note_mesin: string | null;
  image_qr: string | null;
  uid_esp: string | null;
  status_mesin: MachineStatus;
  is_running: boolean;
  running_jo: string[];
  timer_sec: number;
  updated_at: string | null;
  active_history?: {
    id_history: number;
    no_mesin?: string;
    tgl_running?: string | null;
    jenis_operator?: string | null;
    shift?: string | null;
    operator1?: string | null;
    operator2?: string | null;
    nama_teknisi?: string | null;
    nama_setup?: string | null;
    no_jo?: string[] | string | null;
    mesin_start?: string | null;
    mesin_stop?: string | null;
    mesin_durasi?: string | null;
    durasi_berjalan_sec?: number | null;
    good?: number;
    ng?: number;
    sisa_jo?: number;
    runner?: number | null;
    actual_cycletime?: number | null;
    actual_berat?: number | null;
    remark?: string | null;
    status_proses?: string;
  } | null;
}

export interface LaravelMesinCollectionResponse {
  data: LaravelMesinResource[];
  success: boolean;
  total: number;
  running: number;
}

export interface LaravelMappingJoItem {
  no_jo: string;
  prioritas: number | null;
  no_mesin: string;
  tgl_jo: string | null;
  boms: string | null;
  produk: string | null;
  qtyjo: number | null;
  status_jo: string;
  qty_good: number | null;
  qty_ng: number | null;
  sisa_jo: number | null;
  runner: number | null;
}

export interface LaravelTroubleItem {
  kditem: string;
  nmitem: string;
  jenis: string;
}

export interface LaravelTroubleLog {
  id_log: number;
  id_history: number | null;
  kduser: string | null;
  kditem: string | null;
  nmitem: string | null;
  jenis: string | null;
  keterangan: string | null;
  created_at: string | null;
}

export interface LaravelMesinHistoryItem {
  id_history: number;
  no_mesin: string;
  tgl_running: string | null;
  jenis_operator: string | null;
  shift: string | null;
  operator1: string | null;
  operator2: string | null;
  nama_teknisi: string | null;
  nama_setup: string | null;
  no_jo: string[] | string | null;
  mesin_start: string | null;
  mesin_stop: string | null;
  mesin_durasi: string | null;
  durasi_berjalan_sec: number | null;
  good: number;
  ng: number;
  sisa_jo: number;
  runner: number | null;
  actual_cycletime: number | null;
  actual_berat: number | null;
  remark: string | null;
  status_proses: string;
}

