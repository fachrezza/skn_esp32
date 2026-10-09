'use client';

import React from 'react';
import Image from 'next/image';
import {
  LayoutDashboard,
  Users,
  AlertTriangle,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface SidebarProps {
  activeTab?: string;
  onTabChange?: (tab: string) => void;
}

export function Sidebar({ activeTab = 'mesin-management', onTabChange }: SidebarProps) {
  const navItems = [
    { id: 'mesin-management', label: 'Mesin Management', icon: LayoutDashboard },
    { id: 'user-management', label: 'User Management', icon: Users },
    { id: 'trouble-logs', label: 'Riwayat Masalah Mesin', icon: AlertTriangle },
  ];

  return (
    <aside className="w-64 flex-shrink-0 bg-white dark:bg-zinc-950 border-r border-slate-200 dark:border-zinc-800 flex flex-col justify-between h-screen sticky top-0 z-20">
      {/* Top Brand & Navigation */}
      <div className="flex flex-col">
        {/* Brand Header */}
        <div className="h-16 px-4 flex items-center gap-3 border-b border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900">
          <div className="size-9 rounded-lg overflow-hidden flex items-center justify-center shadow-xs flex-shrink-0 border border-slate-200 dark:border-zinc-700 bg-red-600">
            <Image
              src="/images/logo1.jpg"
              alt="Sanken Logo"
              width={36}
              height={36}
              className="size-full object-cover"
              priority
            />
          </div>
          <div className="overflow-hidden">
            <h1 className="text-base font-bold tracking-tight text-slate-900 dark:text-zinc-100 uppercase truncate">
              Sanken Test
            </h1>
          </div>
        </div>

        {/* Section Header */}
        <div className="px-5 pt-4 pb-2">
          <span className="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
            PEMANTAUAN OPERASIONAL
          </span>
        </div>

        {/* Nav Links */}
        <nav className="px-2 flex flex-col gap-1">
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = activeTab === item.id;

            return (
              <button
                key={item.id}
                onClick={() => onTabChange?.(item.id)}
                className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs transition-colors duration-150 text-left ${
                  isActive
                    ? 'bg-sky-50 dark:bg-sky-950/50 text-sky-800 dark:text-sky-300 font-bold border-l-4 border-sky-600 shadow-2xs'
                    : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-900 hover:text-slate-900 dark:hover:text-zinc-200 font-medium'
                }`}
              >
                <Icon className={`size-4 flex-shrink-0 ${isActive ? 'text-sky-600 dark:text-sky-400' : 'text-slate-400 dark:text-zinc-500'}`} />
                <span className="truncate">{item.label}</span>
              </button>
            );
          })}
        </nav>
      </div>

      {/* Bottom Shift Progress Ribbon */}
      <div className="p-3 border-t border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 flex flex-col gap-2">
        <div className="flex items-center justify-between text-[11px] font-mono font-bold">
          <span className="text-slate-500 dark:text-zinc-400 uppercase tracking-wider">PROGRES SHIFT 1</span>
          <Badge variant="outline" className="bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 text-[10px] px-1.5 py-0 font-bold">
            87.5%
          </Badge>
        </div>

        {/* Progress Bar */}
        <div className="w-full h-2 bg-slate-200 dark:bg-zinc-800 rounded-full overflow-hidden border border-slate-200 dark:border-zinc-700">
          <div className="h-full bg-emerald-500 rounded-full transition-all duration-500" style={{ width: '87.5%' }} />
        </div>

        <div className="flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400">
          <span className="text-[11px] font-medium">Target OEE</span>
          <span className="font-mono font-bold text-slate-800 dark:text-zinc-200 text-[11px]">85.0%</span>
        </div>
      </div>
    </aside>
  );
}
