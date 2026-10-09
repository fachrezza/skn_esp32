'use client';

import { useState, useMemo, useEffect, useCallback } from 'react';
import { MachineCardDTO, MachineFilterType, MachineKpiDTO, MachineStatus } from '@/types/machine';
import { calculateMachineKpi } from '@/services/machine-transformer';
import { fetchShopFloorData, updateMesinStatus, toggleMesin } from '@/services/machine-api';

export function useMachineTelemetry(initialMachines: MachineCardDTO[]) {
  const [machines, setMachines] = useState<MachineCardDTO[]>(initialMachines);
  const [filter, setFilter] = useState<MachineFilterType>('ALL');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [selectedMachineCode, setSelectedMachineCode] = useState<string | null>('MC-INJ-01');
  const [isTroubleModalOpen, setIsTroubleModalOpen] = useState<boolean>(false);
  const [activeTroubleMachine, setActiveTroubleMachine] = useState<MachineCardDTO | null>(null);
  const [isLiveApi, setIsLiveApi] = useState<boolean>(false);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

  // Function to pull latest data from Laravel API
  const refreshTelemetri = useCallback(async () => {
    setIsRefreshing(true);
    try {
      const result = await fetchShopFloorData();
      if (result.isLive) {
        setMachines(result.machines);
        setIsLiveApi(true);
      } else {
        setIsLiveApi(false);
      }
    } catch (err) {
      console.warn('[Telemetry] Error saat polling backend:', err);
      setIsLiveApi(false);
    } finally {
      setIsRefreshing(false);
    }
  }, []);

  // Periodic polling to Laravel backend (every 5 seconds)
  useEffect(() => {
    let isMounted = true;

    const fetchLiveUpdates = async () => {
      try {
        const result = await fetchShopFloorData();
        if (isMounted) {
          if (result.isLive) {
            setMachines(result.machines);
            setIsLiveApi(true);
          } else {
            setIsLiveApi(false);
          }
        }
      } catch (err) {
        if (isMounted) {
          console.warn('[Telemetry] Error saat polling backend:', err);
          setIsLiveApi(false);
        }
      }
    };

    fetchLiveUpdates();
    const pollingInterval = setInterval(fetchLiveUpdates, 5000);

    return () => {
      isMounted = false;
      clearInterval(pollingInterval);
    };
  }, []);

  // Fallback sensor shot simulator: if Laravel backend is not running yet,
  // increment counts periodically so the dashboard remains interactive for demo/testing!
  useEffect(() => {
    if (isLiveApi) return; // If connected to real backend, skip mock simulator

    const timer = setInterval(() => {
      setMachines((prev) =>
        prev.map((m) => {
          if (m.status !== 'RUNNING' || !m.running_jo) return m;

          const increment = Math.random() > 0.4 ? 1 : 0;
          if (increment === 0) return m;

          const newGood = m.running_jo.qty_good + increment;
          const target = m.running_jo.target_qty;
          const newSisa = Math.max(0, target - newGood);
          const newProgress = Math.min(100, Math.round((newGood / target) * 1000) / 10);

          return {
            ...m,
            running_jo: {
              ...m.running_jo,
              qty_good: newGood,
              sisa_jo: newSisa,
              progress_percent: newProgress,
            },
          };
        })
      );
    }, 3000);

    return () => clearInterval(timer);
  }, [isLiveApi]);

  // Filtered machines
  const filteredMachines = useMemo(() => {
    return machines.filter((m) => {
      // 1. Category Filter
      if (filter === 'RUNNING' && m.status !== 'RUNNING') return false;
      if (filter === 'ALARM' && m.status !== 'RUSAK') return false;
      if (filter === 'READY_SETUP' && m.status !== 'READY' && m.status !== 'MAINTANCE') return false;

      // 2. Search query filter
      if (searchQuery.trim()) {
        const query = searchQuery.toLowerCase();
        const matchesCode = m.no_mesin.toLowerCase().includes(query);
        const matchesName = m.nama_mesin.toLowerCase().includes(query);
        const matchesProduct = m.running_jo?.produk.toLowerCase().includes(query) ?? false;
        const matchesJo = m.running_jo?.no_jo.toLowerCase().includes(query) ?? false;
        if (!matchesCode && !matchesName && !matchesProduct && !matchesJo) return false;
      }

      return true;
    });
  }, [machines, filter, searchQuery]);

  // Aggregate KPI stats
  const kpiData: MachineKpiDTO = useMemo(() => {
    return calculateMachineKpi(machines);
  }, [machines]);

  const selectMachine = useCallback((no_mesin: string) => {
    setSelectedMachineCode(no_mesin);
  }, []);

  const openTroubleModal = useCallback((machine: MachineCardDTO) => {
    setActiveTroubleMachine(machine);
    setIsTroubleModalOpen(true);
  }, []);

  const closeTroubleModal = useCallback(() => {
    setIsTroubleModalOpen(false);
    setActiveTroubleMachine(null);
  }, []);

  // Action handlers connected to Laravel backend
  const handleToggleMesin = useCallback(async (noMesin: string) => {
    try {
      await toggleMesin(noMesin);
      await refreshTelemetri();
    } catch (err) {
      console.error('[Action] Gagal toggle status mesin:', err);
    }
  }, [refreshTelemetri]);

  const handleUpdateStatus = useCallback(async (noMesin: string, newStatus: MachineStatus) => {
    try {
      await updateMesinStatus(noMesin, newStatus);
      await refreshTelemetri();
    } catch (err) {
      console.error('[Action] Gagal update status mesin:', err);
    }
  }, [refreshTelemetri]);

  return {
    machines,
    filteredMachines,
    kpiData,
    filter,
    setFilter,
    searchQuery,
    setSearchQuery,
    selectedMachineCode,
    selectMachine,
    isTroubleModalOpen,
    activeTroubleMachine,
    openTroubleModal,
    closeTroubleModal,
    isLiveApi,
    isRefreshing,
    refreshTelemetri,
    handleToggleMesin,
    handleUpdateStatus,
  };
}
