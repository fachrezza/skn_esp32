import { MachineCardDTO, MachineKpiDTO } from '@/types/machine';
import { calculateMachineKpi } from './machine-transformer';

/**
 * Default shop floor dataset based on the industrial production floor
 * Represents the 8 units specified in the shop floor layout design.
 */
export const INITIAL_MACHINES: MachineCardDTO[] = [
  {
    no_mesin: 'MC-INJ-01',
    nama_mesin: 'Unit Injeksi 01',
    tonnage: '250T',
    mold_code: 'MOLD-A12',
    status: 'RUNNING',
    status_label: 'BERJALAN',
    uid_esp: 'ESP-8266-A1',
    cycle_time: 24.5,
    is_selected: true,
    running_jo: {
      no_jo: 'JO-2026-0891',
      prioritas: 1,
      produk: 'Casing Housing A12 - ABS Black',
      target_qty: 4000,
      qty_good: 3420,
      qty_ng: 48,
      sisa_jo: 580,
      progress_percent: 85.5,
    },
    alert_message: null,
    downtime_minutes: null,
  },
  {
    no_mesin: 'MC-INJ-02',
    nama_mesin: 'Unit Injeksi 02',
    tonnage: '180T',
    mold_code: 'MOLD-B04',
    status: 'RUNNING',
    status_label: 'BERJALAN',
    uid_esp: 'ESP-8266-A2',
    cycle_time: 18.2,
    running_jo: {
      no_jo: 'JO-2026-0894',
      prioritas: 2,
      produk: 'Gearbox Bevel Ring - POM',
      target_qty: 2250,
      qty_good: 2150,
      qty_ng: 12,
      sisa_jo: 100,
      progress_percent: 95.6,
    },
  },
  {
    no_mesin: 'MC-INJ-03',
    nama_mesin: 'Unit Injeksi 03',
    tonnage: '350T',
    mold_code: 'MOLD-C01',
    status: 'READY',
    status_label: 'SIAP',
    uid_esp: 'ESP-8266-A3',
    cycle_time: 0,
    running_jo: {
      no_jo: 'JO-2026-0899',
      prioritas: 1,
      produk: 'Connector Cap 8-Pin - PBT',
      target_qty: 1500,
      qty_good: 0,
      qty_ng: 0,
      sisa_jo: 1500,
      progress_percent: 0,
    },
    notes: 'Siaga • Siap Mulai Produksi Shift 1',
  },
  {
    no_mesin: 'MC-INJ-04',
    nama_mesin: 'Unit Injeksi 04',
    tonnage: '250T',
    mold_code: 'MOLD-D08',
    status: 'RUSAK',
    status_label: 'RUSAK',
    uid_esp: 'ESP-8266-A4',
    cycle_time: 0,
    alert_message: 'SUHU SENSOR NOZZLE OVERHEAT',
    downtime_minutes: 42,
    running_jo: {
      no_jo: 'JO-2026-0887',
      prioritas: 1,
      produk: 'Automotive Dashboard Bracket',
      target_qty: 4000,
      qty_good: 1420,
      qty_ng: 85,
      sisa_jo: 2580,
      progress_percent: 35.5,
    },
    notes: 'Teknisi sedang inspeksi thermocouple barrel 2',
  },
  {
    no_mesin: 'MC-INJ-05',
    nama_mesin: 'Unit Injeksi 05',
    tonnage: '180T',
    mold_code: 'MOLD-E19',
    status: 'MAINTANCE',
    status_label: 'SETUP',
    uid_esp: 'ESP-8266-A5',
    cycle_time: 0,
    est_ready: '14:30 WIB',
    setup_progress: 75.0,
    running_jo: {
      no_jo: 'JO-2026-0895',
      prioritas: 2,
      produk: 'Battery Pack Retainer Shell',
      target_qty: 1200,
      qty_good: 0,
      qty_ng: 0,
      sisa_jo: 1200,
      progress_percent: 0,
    },
    notes: 'Pergantian Mold & Pengaturan Clamping Force',
  },
  {
    no_mesin: 'MC-INJ-06',
    nama_mesin: 'Unit Injeksi 06',
    tonnage: '200T',
    mold_code: 'MOLD-F02',
    status: 'RUNNING',
    status_label: 'BERJALAN',
    uid_esp: 'ESP-8266-A6',
    cycle_time: 21.4,
    running_jo: {
      no_jo: 'JO-2026-0902',
      prioritas: 2,
      produk: 'Lens Optical Diffuser - PC Clear',
      target_qty: 5440,
      qty_good: 4890,
      qty_ng: 32,
      sisa_jo: 550,
      progress_percent: 89.8,
    },
  },
  {
    no_mesin: 'MC-INJ-07',
    nama_mesin: 'Unit Injeksi 07',
    tonnage: '150T',
    mold_code: 'MOLD-G11',
    status: 'RUNNING',
    status_label: 'BERJALAN',
    uid_esp: 'ESP-8266-A7',
    cycle_time: 12.1,
    running_jo: {
      no_jo: 'JO-2026-0905',
      prioritas: 1,
      produk: 'Medical Syringe Plunger 20ml',
      target_qty: 10000,
      qty_good: 8240,
      qty_ng: 18,
      sisa_jo: 1760,
      progress_percent: 82.4,
    },
    notes: 'Operasi Ruang Bersih (Cleanroom ISO Class 7)',
  },
  {
    no_mesin: 'MC-INJ-08',
    nama_mesin: 'Unit Injeksi 08',
    tonnage: '300T',
    mold_code: 'MOLD-H00',
    status: 'OFF',
    status_label: 'MATI',
    uid_esp: 'ESP-8266-A8',
    cycle_time: 0,
    running_jo: null,
    notes: 'Siaga untuk Mulai Shift 3',
  },
];

export async function getMachines(): Promise<MachineCardDTO[]> {
  try {
    const { fetchShopFloorData } = await import('./machine-api');
    const { machines } = await fetchShopFloorData();
    return machines;
  } catch {
    return INITIAL_MACHINES;
  }
}

export async function getMachineKpi(): Promise<MachineKpiDTO> {
  try {
    const { fetchShopFloorData } = await import('./machine-api');
    const { kpi } = await fetchShopFloorData();
    return kpi;
  } catch {
    return calculateMachineKpi(INITIAL_MACHINES);
  }
}
