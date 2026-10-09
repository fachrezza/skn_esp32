'use client';

import React from 'react';
import { Factory, Bell, CheckCircle2, ChevronDown, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface HeaderProps {
  plantName?: string;
  lineName?: string;
  userName?: string;
  userRole?: string;
  connectedNodes?: number;
  totalNodes?: number;
  isLiveApi?: boolean;
}

export function Header({
  plantName = 'PABRIK 1 - PENCETAKAN INJEKSI',
  lineName = 'JALUR A • HIDROLIK & ELEKTRIK',
  userName = 'Andi Pratama',
  userRole = 'KEPALA OPERATOR • SHIFT 1',
  connectedNodes = 12,
  totalNodes = 12,
  isLiveApi = false,
}: HeaderProps) {
  return (
    <header className="h-16 bg-white dark:bg-zinc-950 border-b border-slate-200 dark:border-zinc-800 px-6 flex items-center justify-between sticky top-0 z-10 shadow-2xs">
      {/* Left: Plant Selection & Gateway Pulse */}
      <div className="flex items-center gap-4">
        {/* Plant Selector Pill */}
        <div className="flex items-center gap-2.5 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800">
          <Factory className="size-4 text-sky-600 dark:text-sky-400 flex-shrink-0" />
          <div className="text-left">
            <p className="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
              {plantName}
            </p>
            <p className="text-xs font-bold text-slate-800 dark:text-zinc-200 leading-tight">
              {lineName}
            </p>
          </div>
          <ChevronDown className="size-3.5 text-slate-400 ml-1" />
        </div>

        {/* Live ESP32 Mesh Gateway / Backend Indicator */}
        <div className="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 text-xs">
          <Badge
            variant={isLiveApi ? 'outline' : 'secondary'}
            className={`text-[10px] font-mono font-bold tracking-wide ${
              isLiveApi
                ? 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300'
                : 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950 dark:text-amber-300'
            }`}
          >
            {isLiveApi ? 'LARAVEL API TERHUBUNG' : 'MODE DEMO / SIMULASI'}
          </Badge>
          <span className="text-[11px] font-mono font-semibold text-slate-500 dark:text-zinc-400">
            ({connectedNodes}/{totalNodes} NODE)
          </span>
        </div>
      </div>

      {/* Right: Notifications, System Status & Profile */}
      <div className="flex items-center gap-4">
        {/* Sync Status Badge */}
        <Badge
          variant="outline"
          className="hidden lg:flex items-center gap-1.5 px-3 py-1.5 text-xs text-slate-600 dark:text-zinc-400 border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 font-medium"
        >
          <CheckCircle2 className="size-3.5 text-emerald-500" />
          <span>Telemetri Realtime Tersinkron</span>
        </Badge>

        {/* Notification Bell with Badge */}
        <div className="relative">
          <Button
            variant="outline"
            size="icon"
            aria-label="Notifikasi"
            className="size-9 bg-slate-50 dark:bg-zinc-900 border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800"
          >
            <Bell className="size-4" />
          </Button>
          <span className="absolute -top-1 -right-1 size-4 rounded-full bg-rose-500 text-white text-[10px] font-mono font-bold flex items-center justify-center ring-2 ring-white dark:ring-zinc-950">
            1
          </span>
        </div>

        {/* User Profile */}
        <div className="flex items-center gap-3 pl-3 border-l border-slate-200 dark:border-zinc-800">
          <div className="size-8 rounded-full bg-sky-700 text-white flex items-center justify-center text-xs font-bold shadow-xs">
            <User className="size-4" />
          </div>
          <div className="text-left hidden sm:block">
            <p className="text-xs font-bold text-slate-800 dark:text-zinc-200 leading-tight">
              {userName}
            </p>
            <p className="text-[10px] font-mono font-bold text-sky-700 dark:text-sky-400 uppercase tracking-tight">
              {userRole}
            </p>
          </div>
        </div>
      </div>
    </header>
  );
}
