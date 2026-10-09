import React from 'react';
import { MachineStatus } from '@/types/machine';
import { Badge } from '@/components/ui/badge';

interface MachineStatusBadgeProps {
  status: MachineStatus;
  label?: string;
  className?: string;
}

const BADGE_CONFIG: Record<
  MachineStatus,
  {
    variant: 'default' | 'secondary' | 'destructive' | 'outline';
    className: string;
  }
> = {
  RUNNING: {
    variant: 'outline',
    className: 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-200 dark:border-emerald-700 font-bold',
  },
  READY: {
    variant: 'outline',
    className: 'bg-sky-100 text-sky-900 border-sky-300 dark:bg-sky-950 dark:text-sky-200 dark:border-sky-700 font-bold',
  },
  MAINTANCE: {
    variant: 'outline',
    className: 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950 dark:text-amber-200 dark:border-amber-700 font-bold',
  },
  RUSAK: {
    variant: 'destructive',
    className: 'bg-rose-100 text-rose-900 border-rose-300 dark:bg-rose-950 dark:text-rose-200 dark:border-rose-700 font-bold',
  },
  OFF: {
    variant: 'secondary',
    className: 'bg-slate-200 text-slate-800 border-slate-300 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 font-bold',
  },
};

export function MachineStatusBadge({ status, label, className = '' }: MachineStatusBadgeProps) {
  const config = BADGE_CONFIG[status] ?? BADGE_CONFIG.OFF;
  const displayLabel = label ?? (status === 'MAINTANCE' ? 'SETUP' : status === 'RUSAK' ? 'BREAKDOWN' : status);

  return (
    <Badge
      variant={config.variant}
      className={`font-mono uppercase tracking-wider text-[10px] font-bold px-2 py-0.5 ${config.className} ${className}`}
    >
      {displayLabel}
    </Badge>
  );
}
