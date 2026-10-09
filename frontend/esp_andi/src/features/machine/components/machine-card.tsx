'use client';

import React from 'react';
import { Clock, Cpu, AlertTriangle, ArrowRight } from 'lucide-react';
import { MachineCardDTO } from '@/types/machine';
import { useMachineCard } from '../hooks/use-machine-card';
import { MachineStatusBadge } from './machine-status-badge';
import { AnimatedCounter } from '@/components/reactbits/animated-counter';
import { Card, CardHeader, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface MachineCardProps {
  machine: MachineCardDTO;
  isSelected?: boolean;
  onSelect?: (no_mesin: string) => void;
  onActionClick?: (machine: MachineCardDTO) => void;
}

export function MachineCard({
  machine,
  isSelected = false,
  onSelect,
  onActionClick,
}: MachineCardProps) {
  const { joMetrics, formattedCycleTime } = useMachineCard(machine);

  const cardBorderClass = isSelected
    ? 'ring-2 ring-sky-500 border-sky-500 shadow-md'
    : machine.status === 'RUSAK'
    ? 'border-rose-300 dark:border-rose-700 ring-1 ring-rose-200 dark:ring-rose-900/50 hover:border-rose-400'
    : 'border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700';

  return (
    <Card
      onClick={() => onSelect?.(machine.no_mesin)}
      className={`p-4 bg-white dark:bg-zinc-900 shadow-xs transition-all duration-150 cursor-pointer flex flex-col justify-between ${cardBorderClass}`}
    >
      <div className="flex flex-col gap-3">
        {/* Card Header: Code, Tonnage, Mold & Status Badge */}
        <CardHeader className="p-0 pb-2.5 border-b border-slate-100 dark:border-zinc-800 flex flex-row items-start justify-between">
          <div className="flex flex-col gap-0.5">
            <div className="flex items-center gap-1.5">
              <span className="text-base font-extrabold font-mono text-slate-900 dark:text-zinc-100 tracking-tight">
                {machine.no_mesin}
              </span>
              <Badge variant="outline" className="text-[10px] font-mono font-bold px-1.5 py-0 h-4.5 bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 border-slate-200 dark:border-zinc-700">
                {machine.tonnage}
              </Badge>
            </div>
            <p className="text-[11px] font-semibold text-slate-500 dark:text-zinc-400">
              {machine.nama_mesin} • <span className="font-mono text-[10px] text-slate-600 dark:text-zinc-300">{machine.mold_code}</span>
            </p>
          </div>

          <MachineStatusBadge status={machine.status} label={machine.status_label} />
        </CardHeader>

        {/* Card Body: Active JO Details */}
        <CardContent className="p-0 flex flex-col gap-3">
          {machine.running_jo ? (
            <div className="bg-slate-50 dark:bg-zinc-800/70 rounded-lg p-2.5 border border-slate-200 dark:border-zinc-700 flex flex-col gap-1">
              <div className="flex items-center justify-between text-[11px] font-mono">
                <span className="font-bold text-sky-700 dark:text-sky-400">
                  {machine.running_jo.no_jo}
                </span>
                <span className="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                  PRIORITAS {machine.running_jo.prioritas}
                </span>
              </div>
              <p className="text-xs font-bold text-slate-800 dark:text-zinc-200 truncate">
                {machine.running_jo.produk}
              </p>
            </div>
          ) : (
            <div className="bg-slate-50 dark:bg-zinc-800/50 rounded-lg p-2.5 border border-slate-200 dark:border-zinc-700 text-xs text-slate-500 dark:text-zinc-400 italic">
              {machine.notes || 'Siaga • Belum ada antrean Job Order'}
            </div>
          )}

          {/* Breakdown Alert Banner */}
          {machine.status === 'RUSAK' && machine.alert_message && (
            <div className="bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-800 rounded-lg p-2 flex items-center gap-2 text-rose-800 dark:text-rose-300 text-[11px] font-mono font-bold">
              <AlertTriangle className="size-4 shrink-0 text-rose-600 dark:text-rose-400" />
              <span className="truncate">{machine.alert_message}</span>
            </div>
          )}

          {/* Telemetry Good vs NG Counters (SOLID & HIGH CONTRAST) */}
          <div className="grid grid-cols-2 gap-2">
            <div className="bg-emerald-50/90 dark:bg-emerald-950/50 rounded-lg p-2.5 border border-emerald-200 dark:border-emerald-800">
              <span className="text-[9px] font-mono font-bold uppercase text-emerald-800 dark:text-emerald-300 block">
                PRODUK BAIK
              </span>
              <div className="text-xl font-mono font-extrabold text-emerald-700 dark:text-emerald-300 mt-0.5">
                <AnimatedCounter value={machine.running_jo?.qty_good ?? 0} />
                <span className="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 ml-1">PCS</span>
              </div>
            </div>

            <div className="bg-rose-50/90 dark:bg-rose-950/50 rounded-lg p-2.5 border border-rose-200 dark:border-rose-800">
              <span className="text-[9px] font-mono font-bold uppercase text-rose-800 dark:text-rose-300 block">
                DEFEK (NG)
              </span>
              <div className="text-xl font-mono font-extrabold text-rose-700 dark:text-rose-300 mt-0.5">
                <AnimatedCounter value={machine.running_jo?.qty_ng ?? 0} />
                <span className="text-[10px] font-bold text-rose-600 dark:text-rose-400 ml-1">PCS</span>
              </div>
            </div>
          </div>

          {/* Target Progress Bar */}
          <div className="flex flex-col gap-1">
            <div className="flex items-center justify-between text-[11px] font-mono font-semibold">
              <span className="text-slate-500 dark:text-zinc-400">
                TARGET: {joMetrics.formattedTarget} PCS
              </span>
              <span className="font-bold text-slate-800 dark:text-zinc-200">
                {joMetrics.progressPercent}%
              </span>
            </div>
            <div className="w-full h-2 bg-slate-100 dark:bg-zinc-800 rounded-full overflow-hidden border border-slate-200 dark:border-zinc-700">
              <div
                className="h-full rounded-full transition-all duration-300 bg-sky-600 dark:bg-sky-500"
                style={{ width: joMetrics.progressBarWidth }}
              />
            </div>
          </div>
        </CardContent>
      </div>

      {/* Card Footer: Metadata & Action Button */}
      <CardFooter className="p-0 pt-3 mt-2 border-t border-slate-100 dark:border-zinc-800 flex flex-col gap-2">
        <div className="flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-zinc-400 w-full">
          <span className="flex items-center gap-1 font-medium">
            <Clock className="size-3 text-slate-400" />
            SIKLUS: <strong className="text-slate-800 dark:text-zinc-200">{formattedCycleTime}</strong>
          </span>
          <span className="flex items-center gap-1 font-medium">
            <Cpu className="size-3 text-slate-400" />
            <span className="truncate max-w-[80px]">{machine.uid_esp}</span>
          </span>
        </div>

        <Button
          type="button"
          size="sm"
          variant={machine.status === 'RUSAK' ? 'destructive' : 'outline'}
          onClick={(e) => {
            e.stopPropagation();
            onActionClick?.(machine);
          }}
          className={`w-full font-bold text-xs tracking-wide ${
            machine.status === 'RUSAK'
              ? 'bg-rose-600 hover:bg-rose-700 text-white'
              : 'bg-white hover:bg-slate-50 dark:bg-zinc-800 dark:hover:bg-zinc-700 border-slate-300 dark:border-zinc-700 text-slate-800 dark:text-zinc-200'
          }`}
        >
          <span>{machine.status === 'RUSAK' ? 'CATAT KERUSAKAN' : 'DETAIL TELEMETRI'}</span>
          <ArrowRight data-icon="inline-end" />
        </Button>
      </CardFooter>
    </Card>
  );
}
