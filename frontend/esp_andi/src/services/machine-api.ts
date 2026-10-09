import apiClient, { API_BASE_URL } from '@/lib/axios';
import {
  LaravelDashboardResponse,
  LaravelMesinCollectionResponse,
  LaravelMesinResource,
  LaravelMappingJoItem,
  LaravelMesinHistoryItem,
  LaravelTroubleItem,
  MachineCardDTO,
  MachineKpiDTO,
  MachineStatus,
} from '@/types/machine';
import {
  transformLaravelMesinToCardDTO,
  transformLaravelDashboardToKpiDTO,
  calculateMachineKpi,
} from './machine-transformer';
import { INITIAL_MACHINES } from './machine-service';

export { API_BASE_URL };

/**
 * GET /api/dashboard
 * Mengambil ringkasan status mesin, JO, dan output shift hari ini
 */
export async function fetchDashboardSummary(): Promise<LaravelDashboardResponse | null> {
  try {
    const response = await apiClient.get<LaravelDashboardResponse>('/dashboard');
    return response.data;
  } catch (error) {
    console.warn('[API] Gagal mengambil summary dashboard Laravel via Axios:', error);
    return null;
  }
}

/**
 * GET /api/mesin (?status=RUNNING, ?q=Haitian)
 * Mengambil daftar armada mesin injection beserta history dan status ESP32
 */
export async function fetchMesinList(
  status?: string,
  q?: string
): Promise<LaravelMesinResource[] | null> {
  try {
    const response = await apiClient.get<LaravelMesinCollectionResponse>('/mesin', {
      params: {
        ...(status ? { status } : {}),
        ...(q ? { q } : {}),
      },
    });
    return response.data?.data || null;
  } catch (error) {
    console.warn('[API] Gagal mengambil daftar mesin dari backend Laravel via Axios:', error);
    return null;
  }
}

/**
 * GET /api/mesin/{no_mesin}/jo
 * Mengambil daftar Job Order yang dimapping ke mesin tertentu (tabel mesin_mapping_jo)
 */
export async function fetchMesinJo(noMesin: string): Promise<LaravelMappingJoItem[] | null> {
  try {
    const response = await apiClient.get<{ success: boolean; data: LaravelMappingJoItem[] }>(
      `/mesin/${encodeURIComponent(noMesin)}/jo`
    );
    return response.data?.data || null;
  } catch {
    return null;
  }
}

/**
 * GET /api/mesin/{no_mesin}/history
 * Mengambil riwayat produksi mesin dari tabel mesin_history
 */
export async function fetchMesinHistory(noMesin: string): Promise<LaravelMesinHistoryItem[] | null> {
  try {
    const response = await apiClient.get<{ success: boolean; data: LaravelMesinHistoryItem[] }>(
      `/mesin/${encodeURIComponent(noMesin)}/history`
    );
    return response.data?.data || null;
  } catch (err) {
    console.warn(`[API] Gagal mengambil riwayat mesin ${noMesin}:`, err);
    return null;
  }
}

/**
 * POST /api/mesin/{no_mesin}/jo
 * Tambah JO baru ke mesin (tabel mesin_mapping_jo)
 */
export async function createMesinJo(
  noMesin: string,
  payload: {
    no_jo: string;
    qtyjo: number;
    produk?: string;
    boms?: string;
    prioritas?: number;
    tgl_jo?: string;
  }
): Promise<{ success: boolean; message: string; data?: LaravelMappingJoItem }> {
  try {
    const response = await apiClient.post<{
      success: boolean;
      message: string;
      data?: LaravelMappingJoItem;
    }>(`/mesin/${encodeURIComponent(noMesin)}/jo`, payload);
    return response.data;
  } catch (err: unknown) {
    const errorMsg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'Gagal menambahkan Job Order';
    return { success: false, message: errorMsg };
  }
}

/**
 * PUT /api/mesin/{no_mesin}/jo/{no_jo}
 * Update status JO (OPEN, HOLD, PENDING, FINISH)
 */
export async function updateMesinJoStatus(
  noMesin: string,
  noJo: string,
  status_jo: string
): Promise<{ success: boolean; message: string }> {
  try {
    const response = await apiClient.put<{ success: boolean; message: string }>(
      `/mesin/${encodeURIComponent(noMesin)}/jo/${encodeURIComponent(noJo)}`,
      { status_jo }
    );
    return response.data;
  } catch (err: unknown) {
    const errorMsg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'Gagal memperbarui status JO';
    return { success: false, message: errorMsg };
  }
}

/**
 * DELETE /api/mesin/{no_mesin}/jo/{no_jo}
 * Hapus mapping JO dari mesin
 */
export async function deleteMesinJo(
  noMesin: string,
  noJo: string
): Promise<{ success: boolean; message: string }> {
  try {
    const response = await apiClient.delete<{ success: boolean; message: string }>(
      `/mesin/${encodeURIComponent(noMesin)}/jo/${encodeURIComponent(noJo)}`
    );
    return response.data;
  } catch (err: unknown) {
    const errorMsg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'Gagal menghapus Job Order';
    return { success: false, message: errorMsg };
  }
}

/**
 * GET /api/mesin/{no_mesin}
 * Mengambil detail lengkap mesin dari tabel mesin_qr beserta activeHistory
 */
export async function fetchMesinDetail(
  noMesin: string
): Promise<LaravelMesinResource | null> {
  try {
    const response = await apiClient.get<{ data: LaravelMesinResource; success: boolean }>(
      `/mesin/${encodeURIComponent(noMesin)}`
    );
    return response.data?.data || null;
  } catch {
    return null;
  }
}

/**
 * POST /api/mesin/{no_mesin}/start
 * Menjalankan mesin produksi (mengisi mesin_history & status RUNNING)
 */
export async function startMesinProduksi(
  noMesin: string,
  payload: {
    jenis_operator: string;
    operator1: string;
    operator2?: string;
    shift: string;
    no_jo: string[];
  }
): Promise<{ success: boolean; message: string }> {
  try {
    const response = await apiClient.post<{ success: boolean; message: string }>(
      `/mesin/${encodeURIComponent(noMesin)}/start`,
      payload
    );
    return response.data;
  } catch (err: unknown) {
    const errorMsg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'Gagal menjalankan mesin';
    return { success: false, message: errorMsg };
  }
}

/**
 * POST /api/mesin/{no_mesin}/stop
 * Menghentikan mesin produksi (menutup mesin_history & update hasil good/ng)
 */
export async function stopMesinProduksi(
  noMesin: string,
  payload: {
    good?: number;
    ng?: number;
    sisa_jo?: number;
    remark?: string;
    jo_selesai?: string[];
  }
): Promise<{ success: boolean; message: string }> {
  try {
    const response = await apiClient.post<{ success: boolean; message: string }>(
      `/mesin/${encodeURIComponent(noMesin)}/stop`,
      payload
    );
    return response.data;
  } catch (err: unknown) {
    const errorMsg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message ||
      'Gagal menghentikan mesin';
    return { success: false, message: errorMsg };
  }
}

/**
 * GET /api/operators
 * Mengambil daftar nama operator/teknisi/setup yang tercatat di sistem
 */
export async function fetchOperators(): Promise<string[] | null> {
  try {
    const response = await apiClient.get<{ success: boolean; data: string[] }>('/operators');
    return response.data?.data || null;
  } catch {
    return null;
  }
}

/**
 * PUT /api/mesin/{no_mesin}/status
 * Memperbarui status operasi mesin (misal ke RUNNING, READY, MAINTANCE, RUSAK)
 */
export async function updateMesinStatus(
  noMesin: string,
  status: MachineStatus
): Promise<{ success: boolean; message?: string }> {
  try {
    const response = await apiClient.put<{ success: boolean; message: string }>(
      `/mesin/${encodeURIComponent(noMesin)}/status`,
      { status }
    );
    return response.data;
  } catch (error) {
    console.error('[API] Gagal memperbarui status mesin via Axios:', error);
    return { success: false, message: String(error) };
  }
}

/**
 * POST /api/mesin/{no_mesin}/toggle
 * Menghidupkan/mematikan mesin secara remote
 */
export async function toggleMesin(noMesin: string): Promise<{ success: boolean; message?: string }> {
  try {
    const response = await apiClient.post<{ success: boolean; message: string }>(
      `/mesin/${encodeURIComponent(noMesin)}/toggle`
    );
    return response.data;
  } catch (error) {
    return { success: false, message: String(error) };
  }
}

/**
 * GET /api/trouble-items
 * Mengambil daftar katalog trouble/downtime
 */
export async function fetchTroubleItems(): Promise<LaravelTroubleItem[] | null> {
  try {
    const response = await apiClient.get<{ success: boolean; data: LaravelTroubleItem[] }>(
      '/trouble-items'
    );
    return response.data?.data || null;
  } catch {
    return null;
  }
}

/**
 * Master data fetcher untuk shop floor:
 * Mengambil data live dari API Laravel menggunakan Axios.
 * Jika backend offline / belum terhubung, otomatis fallback ke data lokal tanpa error.
 */
export async function fetchShopFloorData(): Promise<{
  machines: MachineCardDTO[];
  kpi: MachineKpiDTO;
  isLive: boolean;
}> {
  try {
    const [mesinRawList, dashboardRaw] = await Promise.all([
      fetchMesinList(),
      fetchDashboardSummary(),
    ]);

    if (!mesinRawList || mesinRawList.length === 0) {
      // Backend offline atau belum ada data mesin, gunakan data simulasi
      return {
        machines: INITIAL_MACHINES,
        kpi: calculateMachineKpi(INITIAL_MACHINES),
        isLive: false,
      };
    }

    // Transformasi data mesin dari Laravel Resource
    const machines = mesinRawList.map((m) => transformLaravelMesinToCardDTO(m));

    // Hitung KPI ribbon teragregasi
    const kpi = dashboardRaw
      ? transformLaravelDashboardToKpiDTO(dashboardRaw, machines)
      : calculateMachineKpi(machines);

    return {
      machines,
      kpi,
      isLive: true,
    };
  } catch (err) {
    console.warn('[API] Gagal sinkronisasi data shop floor, menggunakan fallback:', err);
    return {
      machines: INITIAL_MACHINES,
      kpi: calculateMachineKpi(INITIAL_MACHINES),
      isLive: false,
    };
  }
}
