'use client';

import React from 'react';
import { AlertOctagon } from 'lucide-react';
import { MachineKpiDTO } from '@/types/machine';
import { AnimatedCounter } from '@/components/reactbits/animated-counter';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface MachineKpiRibbonProps {
  kpi: MachineKpiDTO;
}

export function MachineKpiRibbon({ kpi }: MachineKpiRibbonProps) {
  const total = kpi.total_machines || 1;
  const runningPct = (kpi.running_count / total) * 100;
  const readyPct = (kpi.ready_count / total) * 100;
  const maintancePct = (kpi.maintance_count / total) * 100;
  const breakdownPct = (kpi.breakdown_count / total) * 100;

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      {/* KPI 1: Active Fleet Telemetry */}
      <Card className="relative overflow-hidden justify-between border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <div className="absolute top-0 left-0 right-0 h-1 bg-emerald-500" />
        <CardHeader className="p-4 pb-2">
          <div className="flex items-center justify-between">
            <CardTitle className="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
              STATUS ARMADA MESIN
            </CardTitle>
            <Badge variant="outline" className="text-[10px] font-mono font-bold bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800">
              {Math.round((kpi.running_count / total) * 100)}% BEROPERASI
            </Badge>
          </div>
        </CardHeader>

        <CardContent className="p-4 pt-0 flex flex-col gap-2.5">
          <div className="flex items-baseline gap-2">
            <span className="text-2xl lg:text-3xl font-extrabold font-mono text-slate-900 dark:text-zinc-100 tracking-tight">
              {kpi.running_count} / {kpi.total_machines}
            </span>
            <span className="text-xs font-bold text-slate-500 dark:text-zinc-400">UNIT</span>
          </div>

          {/* Segmented Status Bar */}
          <div className="w-full h-2.5 bg-slate-100 dark:bg-zinc-800 rounded-full flex overflow-hidden p-0.5 gap-0.5 border border-slate-200 dark:border-zinc-700">
            <div className="bg-emerald-500 rounded-xs transition-all" style={{ width: `${runningPct}%` }} title="Berjalan" />
            <div className="bg-sky-500 rounded-xs transition-all" style={{ width: `${readyPct}%` }} title="Siap" />
            <div className="bg-amber-500 rounded-xs transition-all" style={{ width: `${maintancePct}%` }} title="Setup" />
            <div className="bg-rose-500 rounded-xs transition-all" style={{ width: `${breakdownPct}%` }} title="Rusak" />
          </div>

          {/* Status Breakdown Legend */}
          <div className="flex items-center justify-between text-[11px] font-mono font-bold pt-0.5">
            <span className="text-emerald-700 dark:text-emerald-400">
              Jalan: {kpi.running_count}
            </span>
            <span className="text-sky-700 dark:text-sky-400">
              Siap: {kpi.ready_count}
            </span>
            <span className="text-amber-700 dark:text-amber-400">
              Setup: {kpi.maintance_count}
            </span>
            <span className="text-rose-700 dark:text-rose-400">
              Rusak: {kpi.breakdown_count}
            </span>
          </div>
        </CardContent>
      </Card>

      {/* KPI 2: Shift Cumulative Output */}
      <Card className="relative overflow-hidden justify-between border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <div className="absolute top-0 left-0 right-0 h-1 bg-sky-500" />
        <CardHeader className="p-4 pb-2">
          <div className="flex items-center justify-between">
            <CardTitle className="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
              OUTPUT KUMULATIF SHIFT
            </CardTitle>
            <Badge variant="destructive" className="text-[10px] font-mono font-bold bg-rose-100 text-rose-900 border-rose-300 dark:bg-rose-950 dark:text-rose-300 dark:border-rose-800">
              DEFEK {kpi.shift_ng_qty} PCS
            </Badge>
          </div>
        </CardHeader>

        <CardContent className="p-4 pt-0 flex flex-col gap-2">
          <div className="flex items-baseline gap-2">
            <span className="text-2xl lg:text-3xl font-extrabold font-mono text-emerald-700 dark:text-emerald-400 tracking-tight">
              <AnimatedCounter value={kpi.shift_good_qty} />
            </span>
            <span className="text-xs font-bold text-slate-500 dark:text-zinc-400">PRODUK BAIK</span>
          </div>

          <div className="bg-slate-50 dark:bg-zinc-800/80 rounded-lg p-2.5 border border-slate-200 dark:border-zinc-700 flex items-center justify-between text-xs mt-1">
            <div>
              <p className="text-[10px] font-mono font-semibold text-slate-500 dark:text-zinc-400 uppercase">KECEPATAN PRODUKSI</p>
              <p className="font-mono font-extrabold text-slate-800 dark:text-zinc-200 text-[11px]">
                {kpi.hourly_rate.toLocaleString('id-ID')} PCS/JAM
              </p>
            </div>
            <div className="text-right">
              <p className="text-[10px] font-mono font-semibold text-slate-500 dark:text-zinc-400 uppercase">RASIO DEFEK (NG)</p>
              <p className="font-mono font-extrabold text-rose-600 dark:text-rose-400 text-[11px]">
                {kpi.defect_rate_percent}%
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* KPI 3: Job Order (JO) Execution */}
      <Card className="relative overflow-hidden justify-between border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
        <div className="absolute top-0 left-0 right-0 h-1 bg-blue-600" />
        <CardHeader className="p-4 pb-2">
          <div className="flex items-center justify-between">
            <CardTitle className="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
              ANTREAN JOB ORDER (JO)
            </CardTitle>
            <Badge variant="secondary" className="text-[10px] font-mono font-bold bg-sky-100 text-sky-900 border-sky-300 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800">
              {kpi.on_schedule_count} SESUAI JADWAL
            </Badge>
          </div>
        </CardHeader>

        <CardContent className="p-4 pt-0 flex flex-col gap-2">
          <div className="flex items-baseline gap-2">
            <span className="text-2xl lg:text-3xl font-extrabold font-mono text-slate-900 dark:text-zinc-100 tracking-tight">
              {kpi.active_jo_count}
            </span>
            <span className="text-xs font-bold text-slate-500 dark:text-zinc-400">PESANAN AKTIF</span>
          </div>

          <div className="border-t border-slate-100 dark:border-zinc-800 pt-2.5 mt-1 flex items-center justify-between text-[11px] font-mono">
            <div>
              <span className="text-slate-500 dark:text-zinc-400 block text-[10px] font-semibold">TOTAL SISA TARGET</span>
              <span className="font-extrabold text-sky-700 dark:text-sky-400">
                {kpi.total_sisa_qty.toLocaleString('id-ID')} PCS
              </span>
            </div>
            <div className="text-right">
              <span className="text-slate-500 dark:text-zinc-400 block text-[10px] font-semibold">TERTUNDA</span>
              <span className="font-extrabold text-rose-600 dark:text-rose-400">{kpi.critical_jo_count} JO</span>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* KPI 4: Critical Downtime Alert */}
      <Card className="relative overflow-hidden justify-between border-rose-300 dark:border-rose-800 bg-rose-50/70 dark:bg-rose-950/40 shadow-xs">
        <div className="absolute top-0 left-0 right-0 h-1 bg-rose-600" />
        <CardHeader className="p-4 pb-2">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-1.5 text-rose-800 dark:text-rose-300 font-mono font-bold text-[11px] uppercase tracking-wider">
              <AlertOctagon className="size-3.5 text-rose-600 dark:text-rose-400" />
              <span>PERINGATAN DOWNTIME</span>
            </div>
            <Badge variant="destructive" className="text-[10px] font-mono font-bold bg-rose-600 text-white">
              PERLU TINDAKAN
            </Badge>
          </div>
        </CardHeader>

        <CardContent className="p-4 pt-0 flex flex-col gap-2">
          <div className="flex items-baseline gap-2">
            <span className="text-2xl lg:text-3xl font-extrabold font-mono text-rose-700 dark:text-rose-300 tracking-tight">
              {kpi.active_alarm_count}
            </span>
            <span className="text-xs font-bold text-rose-800 dark:text-rose-300">UNIT BERHENTI</span>
          </div>

          <div className="bg-white dark:bg-zinc-900 rounded-lg p-2.5 border border-rose-200 dark:border-rose-900 mt-1 shadow-2xs">
            <div className="flex items-center justify-between text-[11px] font-mono">
              <span className="font-bold text-rose-800 dark:text-rose-300 truncate max-w-[140px]">
                {kpi.critical_downtime_label}
              </span>
              <Badge variant="destructive" className="text-[10px] font-mono font-bold bg-rose-100 text-rose-900 border-rose-300 dark:bg-rose-950 dark:text-rose-200">
                {kpi.critical_downtime_duration}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
