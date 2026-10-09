'use client';

import React from 'react';
import { RefreshCw } from 'lucide-react';
import { MachineCardDTO } from '@/types/machine';
import { useMachineDetail } from '../hooks/use-machine-detail';
import { MachineDetailRunningSection } from './machine-detail-running-section';
import { MachineDetailStartSection } from './machine-detail-start-section';
import { MachineDetailJoTab } from './machine-detail-jo-tab';
import { MachineDetailHistoryTab } from './machine-detail-history-tab';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';

interface MachineDetailModalProps {
  isOpen: boolean;
  onClose: () => void;
  machine: MachineCardDTO | null;
}

export function MachineDetailModal({ isOpen, onClose, machine }: MachineDetailModalProps) {
  const {
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
  } = useMachineDetail(isOpen && machine ? machine.no_mesin : null);

  if (!machine) return null;

  const isMachineRunning =
    machine.status === 'RUNNING' || mesinResource?.is_running || Boolean(mesinResource?.active_history);

  const activeHistory =
    mesinResource?.active_history ||
    (isMachineRunning
      ? {
          id_history: 0,
          no_mesin: machine.no_mesin,
          tgl_running: null,
          jenis_operator: 'OPERATOR',
          shift: '1',
          operator1: machine.running_jo ? 'Operator Shift' : 'Operator',
          operator2: null,
          nama_teknisi: null,
          nama_setup: null,
          no_jo: machine.running_jo ? [machine.running_jo.no_jo] : [],
          mesin_start: null,
          mesin_stop: null,
          mesin_durasi: null,
          durasi_berjalan_sec: 0,
          good: machine.running_jo?.qty_good ?? 0,
          ng: machine.running_jo?.qty_ng ?? 0,
          sisa_jo: machine.running_jo?.sisa_jo ?? 0,
          runner: null,
          actual_cycletime: machine.cycle_time,
          actual_berat: null,
          remark: null,
          status_proses: 'RUNNING',
        }
      : null);

  return (
    <Dialog open={isOpen} onOpenChange={(open) => { if (!open) onClose(); }}>
      <DialogContent
        className="sm:max-w-3xl max-h-[90vh] flex flex-col p-0 overflow-hidden bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 shadow-2xl"
        showCloseButton={true}
      >
        {/* Header (SOLID, CRISP & CONTRAST) */}
        <DialogHeader className="px-6 pt-5 pb-4 border-b border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 flex-shrink-0">
          <div className="flex items-center justify-between pr-8">
            <div className="flex flex-col gap-1.5">
              <div className="flex items-center gap-3">
                <DialogTitle className="text-lg font-bold font-mono tracking-tight text-slate-900 dark:text-zinc-100">
                  Mesin {machine.no_mesin}
                </DialogTitle>
                <Badge
                  variant="outline"
                  className={`font-mono text-[10px] tracking-wider uppercase font-bold ${
                    machine.status === 'RUNNING'
                      ? 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300'
                      : machine.status === 'READY'
                      ? 'bg-sky-100 text-sky-900 border-sky-300 dark:bg-sky-950 dark:text-sky-300'
                      : 'bg-rose-100 text-rose-900 border-rose-300 dark:bg-rose-950 dark:text-rose-300'
                  }`}
                >
                  {machine.status}
                </Badge>
              </div>
              <DialogDescription className="text-xs text-slate-500 dark:text-zinc-400 flex flex-wrap items-center gap-2">
                <span className="font-semibold text-slate-800 dark:text-zinc-200">{machine.nama_mesin}</span>
                <span>•</span>
                <span className="font-mono">
                  {machine.uid_esp && machine.uid_esp !== 'TIDAK TERHUBUNG'
                    ? `UID: ${machine.uid_esp}`
                    : 'ESP belum terpasang'}
                </span>
              </DialogDescription>
            </div>

            <Button
              variant="outline"
              size="icon-sm"
              onClick={refreshData}
              title="Refresh Data"
              className="mr-2 border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-300"
            >
              <RefreshCw className={isLoading ? 'animate-spin' : ''} />
              <span className="sr-only">Refresh Data</span>
            </Button>
          </div>
        </DialogHeader>

        {/* Tabbed Content */}
        <Tabs
          value={activeTab}
          onValueChange={(val) => setActiveTab(val as 'jo' | 'history')}
          className="flex-1 flex flex-col overflow-hidden bg-white dark:bg-zinc-900"
        >
          <div className="px-6 pt-3 pb-2 border-b border-slate-200 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-800/40 flex-shrink-0">
            <TabsList variant="line">
              <TabsTrigger value="jo" className="font-bold text-xs">
                Operasional & JO Mesin ({joList.length})
              </TabsTrigger>
              <TabsTrigger value="history" className="font-bold text-xs">
                Riwayat Produksi ({historyList.length})
              </TabsTrigger>
            </TabsList>
          </div>

          <div className="p-6 overflow-y-auto flex-1 bg-white dark:bg-zinc-900">
            <TabsContent value="jo" className="flex flex-col gap-6 mt-0">
              {/* Section Sedang Berjalan (jika RUNNING) atau Form Jalankan Mesin (jika READY) */}
              {activeHistory ? (
                <MachineDetailRunningSection
                  activeHistory={activeHistory}
                  timerSec={mesinResource?.timer_sec}
                  isSubmitting={isSubmitting}
                  onStopMesin={handleStopMesin}
                />
              ) : (
                <MachineDetailStartSection
                  availableJos={joList}
                  isSubmitting={isSubmitting}
                  onStartMesin={handleStartMesin}
                />
              )}

              {/* Section JO Mesin & Form Tambah JO */}
              <MachineDetailJoTab
                joList={joList}
                formData={formData}
                isSubmitting={isSubmitting}
                feedback={feedback}
                onInputChange={handleInputChange}
                onAddJo={handleAddJo}
                onStatusChange={handleStatusChange}
                onDelete={handleDelete}
              />
            </TabsContent>

            <TabsContent value="history" className="mt-0">
              <MachineDetailHistoryTab historyList={historyList} />
            </TabsContent>
          </div>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
}
