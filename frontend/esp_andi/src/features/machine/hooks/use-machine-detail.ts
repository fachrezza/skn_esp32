'use client';

import { useState, useEffect, useCallback } from 'react';
import {
  LaravelMappingJoItem,
  LaravelMesinHistoryItem,
  LaravelMesinResource,
} from '@/types/machine';
import {
  fetchMesinJo,
  fetchMesinHistory,
  fetchMesinDetail,
  createMesinJo,
  updateMesinJoStatus,
  deleteMesinJo,
  startMesinProduksi,
  stopMesinProduksi,
} from '@/services/machine-api';

export function useMachineDetail(noMesin: string | null) {
  const [activeTab, setActiveTab] = useState<'jo' | 'history'>('jo');
  const [mesinResource, setMesinResource] = useState<LaravelMesinResource | null>(null);
  const [joList, setJoList] = useState<LaravelMappingJoItem[]>([]);
  const [historyList, setHistoryList] = useState<LaravelMesinHistoryItem[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  // Form state untuk Tambah JO
  const [formData, setFormData] = useState({
    no_jo: '',
    qtyjo: '',
    produk: '',
    boms: '',
    prioritas: 1,
    tgl_jo: '',
  });

  const refreshData = useCallback(async () => {
    if (!noMesin) return;
    setIsLoading(true);
    try {
      const [mesin, jos, histories] = await Promise.all([
        fetchMesinDetail(noMesin),
        fetchMesinJo(noMesin),
        fetchMesinHistory(noMesin),
      ]);
      if (mesin) setMesinResource(mesin);
      if (jos) setJoList(jos);
      if (histories) setHistoryList(histories);
    } catch (err) {
      console.warn('[MachineDetail] Gagal refresh data mesin:', err);
    } finally {
      setIsLoading(false);
    }
  }, [noMesin]);

  useEffect(() => {
    let isCancelled = false;

    if (!noMesin) {
      return;
    }

    const fetchData = async () => {
      try {
        const [mesin, jos, histories] = await Promise.all([
          fetchMesinDetail(noMesin),
          fetchMesinJo(noMesin),
          fetchMesinHistory(noMesin),
        ]);
        if (!isCancelled) {
          if (mesin) setMesinResource(mesin);
          if (jos) setJoList(jos);
          if (histories) setHistoryList(histories);
        }
      } catch (err) {
        if (!isCancelled) {
          console.warn('[MachineDetail] Gagal load data mesin:', err);
        }
      }
    };

    fetchData();
    const pollInterval = setInterval(fetchData, 5000);

    return () => {
      isCancelled = true;
      clearInterval(pollInterval);
    };
  }, [noMesin]);

  const handleInputChange = (field: string, value: string | number) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleAddJo = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!noMesin || !formData.no_jo.trim() || !formData.qtyjo) return;

    setIsSubmitting(true);
    setFeedback(null);

    const res = await createMesinJo(noMesin, {
      no_jo: formData.no_jo.trim(),
      qtyjo: Number(formData.qtyjo),
      produk: formData.produk.trim() || undefined,
      boms: formData.boms.trim() || undefined,
      prioritas: Number(formData.prioritas) || 1,
      tgl_jo: formData.tgl_jo || undefined,
    });

    setIsSubmitting(false);

    if (res.success) {
      setFeedback({ type: 'success', message: res.message || 'Job Order berhasil ditambahkan!' });
      setFormData({
        no_jo: '',
        qtyjo: '',
        produk: '',
        boms: '',
        prioritas: 1,
        tgl_jo: '',
      });
      refreshData();
    } else {
      setFeedback({ type: 'error', message: res.message || 'Gagal menambahkan Job Order.' });
    }
  };

  const handleStatusChange = async (noJo: string, newStatus: string) => {
    if (!noMesin) return;
    const res = await updateMesinJoStatus(noMesin, noJo, newStatus);
    if (res.success) {
      setFeedback({ type: 'success', message: `Status ${noJo} diubah ke ${newStatus}` });
      refreshData();
    } else {
      setFeedback({ type: 'error', message: res.message || 'Gagal mengubah status JO.' });
    }
  };

  const handleDelete = async (noJo: string) => {
    if (!noMesin) return;
    const confirmDelete = window.confirm(`Apakah Anda yakin ingin menghapus ${noJo}?`);
    if (!confirmDelete) return;

    const res = await deleteMesinJo(noMesin, noJo);
    if (res.success) {
      setFeedback({ type: 'success', message: `Job Order ${noJo} berhasil dihapus.` });
      refreshData();
    } else {
      setFeedback({ type: 'error', message: res.message || 'Gagal menghapus JO.' });
    }
  };

  const handleStartMesin = async (payload: {
    jenis_operator: string;
    operator1: string;
    operator2?: string;
    shift: string;
    no_jo: string[];
  }) => {
    if (!noMesin) return;
    setIsSubmitting(true);
    setFeedback(null);
    const res = await startMesinProduksi(noMesin, payload);
    setIsSubmitting(false);

    if (res.success) {
      setFeedback({ type: 'success', message: res.message || 'Mesin berhasil dijalankan!' });
      refreshData();
    } else {
      setFeedback({ type: 'error', message: res.message || 'Gagal menjalankan mesin.' });
    }
  };

  const handleStopMesin = async (payload: {
    good: number;
    ng: number;
    remark: string;
    jo_selesai: string[];
  }) => {
    if (!noMesin) return;
    setIsSubmitting(true);
    setFeedback(null);
    const res = await stopMesinProduksi(noMesin, payload);
    setIsSubmitting(false);

    if (res.success) {
      setFeedback({ type: 'success', message: res.message || 'Mesin berhasil dihentikan!' });
      refreshData();
    } else {
      setFeedback({ type: 'error', message: res.message || 'Gagal menghentikan mesin.' });
    }
  };

  return {
    activeTab,
    setActiveTab,
    mesinResource,
    joList,
    historyList,
    isLoading,
    isSubmitting,
    feedback,
    formData,
    handleInputChange,
    handleAddJo,
    handleStatusChange,
    handleDelete,
    handleStartMesin,
    handleStopMesin,
    refreshData,
  };
}
