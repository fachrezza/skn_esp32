'use client';

import React from 'react';
import { LaravelMesinHistoryItem } from '@/types/machine';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';

interface MachineDetailHistoryTabProps {
  historyList: LaravelMesinHistoryItem[];
}

export function MachineDetailHistoryTab({ historyList }: MachineDetailHistoryTabProps) {
  if (historyList.length === 0) {
    return (
      <div className="p-8 text-center border border-dashed border-slate-200 dark:border-zinc-700 rounded-xl text-xs text-slate-500 dark:text-zinc-400 bg-slate-50 dark:bg-zinc-800/40">
        Belum ada riwayat produksi tercatat untuk mesin ini.
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-3">
      <div className="flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400 font-medium">
        <span>Total {historyList.length} riwayat operasional tercatat</span>
      </div>

      <div className="border border-slate-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 overflow-hidden shadow-xs max-h-[420px] overflow-y-auto">
        <Table>
          <TableHeader className="sticky top-0 bg-slate-100 dark:bg-zinc-800 z-10 border-b border-slate-200 dark:border-zinc-700">
            <TableRow>
              <TableHead className="w-[140px] font-bold text-slate-800 dark:text-zinc-200 text-xs">Tanggal / Shift</TableHead>
              <TableHead className="font-bold text-slate-800 dark:text-zinc-200 text-xs">Operator</TableHead>
              <TableHead className="font-bold text-slate-800 dark:text-zinc-200 text-xs">No JO</TableHead>
              <TableHead className="text-right font-bold text-slate-800 dark:text-zinc-200 text-xs">Good / NG</TableHead>
              <TableHead className="text-center w-[90px] font-bold text-slate-800 dark:text-zinc-200 text-xs">Cycle Time</TableHead>
              <TableHead className="text-center w-[90px] font-bold text-slate-800 dark:text-zinc-200 text-xs">Status</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody className="font-mono text-xs">
            {historyList.map((item) => {
              const joCodes = Array.isArray(item.no_jo) ? item.no_jo.join(', ') : item.no_jo || '-';
              return (
                <TableRow key={item.id_history} className="hover:bg-slate-50 dark:hover:bg-zinc-800/60 border-b border-slate-100 dark:border-zinc-800">
                  <TableCell>
                    <div className="font-bold text-slate-900 dark:text-zinc-100">{item.tgl_running || '-'}</div>
                    <div className="text-[10px] text-slate-500 dark:text-zinc-400">Shift {item.shift || '1'}</div>
                  </TableCell>
                  <TableCell className="font-sans font-semibold text-slate-800 dark:text-zinc-200">
                    {item.operator1 || '-'}
                  </TableCell>
                  <TableCell className="text-sky-700 dark:text-sky-400 font-bold">
                    {joCodes}
                  </TableCell>
                  <TableCell className="text-right">
                    <span className="text-emerald-700 dark:text-emerald-400 font-bold">{item.good}</span>
                    <span className="text-slate-400 mx-1">/</span>
                    <span className="text-rose-600 dark:text-rose-400 font-bold">{item.ng}</span>
                  </TableCell>
                  <TableCell className="text-center font-sans font-semibold text-slate-700 dark:text-zinc-300">
                    {item.actual_cycletime ? `${item.actual_cycletime}s` : '-'}
                  </TableCell>
                  <TableCell className="text-center">
                    <Badge
                      variant="outline"
                      className={`text-[10px] font-mono uppercase font-bold ${
                        item.status_proses === 'RUNNING'
                          ? 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300'
                          : 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-zinc-800 dark:text-zinc-300'
                      }`}
                    >
                      {item.status_proses}
                    </Badge>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}
