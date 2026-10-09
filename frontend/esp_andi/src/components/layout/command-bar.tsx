'use client';

import React from 'react';
import { AlertCircle, RefreshCw, Search } from 'lucide-react';
import { MachineFilterType } from '@/types/machine';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface CommandBarProps {
  filter: MachineFilterType;
  onFilterChange: (f: MachineFilterType) => void;
  searchQuery: string;
  onSearchChange: (q: string) => void;
  counts: {
    all: number;
    running: number;
    alarm: number;
    readySetup: number;
  };
  onOpenTroubleModal?: () => void;
  onRefresh?: () => void;
}

export function CommandBar({
  filter,
  onFilterChange,
  searchQuery,
  onSearchChange,
  counts,
  onOpenTroubleModal,
  onRefresh,
}: CommandBarProps) {
  return (
    <Card className="p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
      {/* Left Column: Fleet Status & Gateway Indicator */}
      <div className="flex flex-col gap-1.5">
        <div className="flex items-center gap-3">
          <h2 className="text-lg font-bold text-slate-900 dark:text-zinc-100 tracking-tight">
            {counts.all} Mesin Terpantau
          </h2>

          <Badge variant="outline" className="font-mono text-[11px] font-bold bg-sky-100 text-sky-900 border-sky-300 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800">
            ESP-MESH #01 TERHUBUNG
          </Badge>
        </div>

        <div className="text-[11px] font-mono font-medium text-slate-500 dark:text-zinc-400">
          PEMANTAUAN LANTAI PRODUKSI • REFRESH OTOMATIS 5s • TELEMETRI API LARAVEL
        </div>
      </div>

      {/* Right Column: Search, Filters & Action Buttons */}
      <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
        {/* Search Input */}
        <div className="relative">
          <Search className="size-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
          <Input
            type="text"
            value={searchQuery}
            onChange={(e) => onSearchChange(e.target.value)}
            placeholder="Cari mesin / JO / produk..."
            className="pl-8 text-xs h-8 w-full sm:w-52 bg-slate-50 dark:bg-zinc-800 border-slate-300 dark:border-zinc-700 font-medium"
          />
        </div>

        {/* Filter Tabs */}
        <div className="flex items-center bg-slate-100 dark:bg-zinc-800 p-1 rounded-lg border border-slate-200 dark:border-zinc-700 text-xs font-semibold gap-1">
          <Button
            size="sm"
            variant={filter === 'ALL' ? 'default' : 'ghost'}
            onClick={() => onFilterChange('ALL')}
            className={`h-7 text-xs px-2.5 font-bold ${
              filter === 'ALL'
                ? 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-zinc-100 dark:text-zinc-900'
                : 'text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'
            }`}
          >
            SEMUA ({counts.all})
          </Button>

          <Button
            size="sm"
            variant={filter === 'RUNNING' ? 'default' : 'ghost'}
            onClick={() => onFilterChange('RUNNING')}
            className={`h-7 text-xs px-2.5 font-bold ${
              filter === 'RUNNING'
                ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                : 'text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'
            }`}
          >
            BERJALAN ({counts.running})
          </Button>

          <Button
            size="sm"
            variant={filter === 'ALARM' ? 'destructive' : 'ghost'}
            onClick={() => onFilterChange('ALARM')}
            className={`h-7 text-xs px-2.5 font-bold ${
              filter === 'ALARM'
                ? 'bg-rose-600 hover:bg-rose-700 text-white'
                : 'text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'
            }`}
          >
            RUSAK ({counts.alarm})
          </Button>

          <Button
            size="sm"
            variant={filter === 'READY_SETUP' ? 'secondary' : 'ghost'}
            onClick={() => onFilterChange('READY_SETUP')}
            className={`h-7 text-xs px-2.5 font-bold ${
              filter === 'READY_SETUP'
                ? 'bg-sky-600 hover:bg-sky-700 text-white'
                : 'text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'
            }`}
          >
            STANDBY ({counts.readySetup})
          </Button>
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-2">
          {onRefresh && (
            <Button
              size="sm"
              variant="outline"
              onClick={onRefresh}
              title="Refresh Telemetri"
              className="h-8 px-2.5 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800"
            >
              <RefreshCw className="size-3.5" />
            </Button>
          )}

          {onOpenTroubleModal && (
            <Button
              size="sm"
              variant="destructive"
              onClick={onOpenTroubleModal}
              className="h-8 text-xs font-bold tracking-wide uppercase gap-1.5 bg-rose-600 hover:bg-rose-700 text-white"
            >
              <AlertCircle data-icon="inline-start" />
              <span>Catat Kerusakan</span>
            </Button>
          )}
        </div>
      </div>
    </Card>
  );
}
