import { useMemo } from 'react';
import { MachineCardDTO, MachineStatus } from '@/types/machine';

export interface MachineCardVisualTokens {
  badgeBg: string;
  badgeBorder: string;
  badgeText: string;
  cardBorder: string;
  accentBarColor: string;
}

const STATUS_TOKENS: Record<MachineStatus, MachineCardVisualTokens> = {
  RUNNING: {
    badgeBg: 'bg-emerald-100 dark:bg-emerald-950',
    badgeBorder: 'border-emerald-300 dark:border-emerald-700',
    badgeText: 'text-emerald-900 dark:text-emerald-200',
    cardBorder: 'border-slate-200 dark:border-zinc-800 hover:border-slate-300',
    accentBarColor: 'bg-emerald-500',
  },
  READY: {
    badgeBg: 'bg-sky-100 dark:bg-sky-950',
    badgeBorder: 'border-sky-300 dark:border-sky-700',
    badgeText: 'text-sky-900 dark:text-sky-200',
    cardBorder: 'border-slate-200 dark:border-zinc-800 hover:border-slate-300',
    accentBarColor: 'bg-sky-500',
  },
  MAINTANCE: {
    badgeBg: 'bg-amber-100 dark:bg-amber-950',
    badgeBorder: 'border-amber-300 dark:border-amber-700',
    badgeText: 'text-amber-900 dark:text-amber-200',
    cardBorder: 'border-amber-300 dark:border-amber-800',
    accentBarColor: 'bg-amber-500',
  },
  RUSAK: {
    badgeBg: 'bg-rose-100 dark:bg-rose-950',
    badgeBorder: 'border-rose-300 dark:border-rose-700',
    badgeText: 'text-rose-900 dark:text-rose-200',
    cardBorder: 'border-rose-300 dark:border-rose-700',
    accentBarColor: 'bg-rose-500',
  },
  OFF: {
    badgeBg: 'bg-slate-200 dark:bg-zinc-800',
    badgeBorder: 'border-slate-300 dark:border-zinc-700',
    badgeText: 'text-slate-800 dark:text-zinc-200',
    cardBorder: 'border-slate-200 dark:border-zinc-800',
    accentBarColor: 'bg-slate-400',
  },
};

/**
 * Custom hook to prepare machine card view model
 * Isolates math, formatting, and semantic styling from dumb visual components
 */
export function useMachineCard(machine: MachineCardDTO) {
  const visualTokens = useMemo(() => {
    return STATUS_TOKENS[machine.status] ?? STATUS_TOKENS.OFF;
  }, [machine.status]);

  const joMetrics = useMemo(() => {
    if (!machine.running_jo) {
      return {
        hasJO: false,
        progressPercent: 0,
        formattedGood: '0',
        formattedNg: '0',
        formattedTarget: '0',
        formattedSisa: '0',
        progressBarWidth: '0%',
      };
    }

    const { target_qty, qty_good, qty_ng, sisa_jo, progress_percent } = machine.running_jo;
    const progress = Math.min(100, Math.max(0, progress_percent));

    return {
      hasJO: true,
      progressPercent: progress,
      formattedGood: qty_good.toLocaleString('id-ID'),
      formattedNg: qty_ng.toLocaleString('id-ID'),
      formattedTarget: target_qty.toLocaleString('id-ID'),
      formattedSisa: sisa_jo.toLocaleString('id-ID'),
      progressBarWidth: `${progress}%`,
    };
  }, [machine.running_jo]);

  const formattedCycleTime = useMemo(() => {
    return machine.cycle_time > 0 ? `${machine.cycle_time.toFixed(1)}s` : '-';
  }, [machine.cycle_time]);

  return {
    visualTokens,
    joMetrics,
    formattedCycleTime,
  };
}
