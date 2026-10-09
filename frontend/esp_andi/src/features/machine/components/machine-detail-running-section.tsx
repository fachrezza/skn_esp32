'use client';

import React, { useState } from 'react';
import { LaravelMesinResource } from '@/types/machine';
import { Square } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface MachineDetailRunningSectionProps {
  activeHistory: NonNullable<LaravelMesinResource['active_history']>;
  timerSec?: number;
  isSubmitting: boolean;
  onStopMesin: (payload: {
    good: number;
    ng: number;
    remark: string;
    jo_selesai: string[];
  }) => Promise<void>;
}

export function MachineDetailRunningSection({
  activeHistory,
  timerSec,
  isSubmitting,
  onStopMesin,
}: MachineDetailRunningSectionProps) {
  const [good, setGood] = useState<number>(activeHistory.good ?? 0);
  const [ng, setNg] = useState<number>(activeHistory.ng ?? 0);
  const [remark, setRemark] = useState<string>('');
  const [selectedFinishJos, setSelectedFinishJos] = useState<string[]>([]);

  // Format durasi HH:mm:ss
  const totalSeconds = activeHistory.durasi_berjalan_sec ?? timerSec ?? 0;
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = Math.floor(totalSeconds % 60);
  const formattedDuration = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

  // Format tanggal mulai
  const formatMulai = (rawDate?: string | null) => {
    if (!rawDate) return '-';
    try {
      const d = new Date(rawDate);
      const day = String(d.getDate()).padStart(2, '0');
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const year = String(d.getFullYear()).slice(-2);
      const hours = String(d.getHours()).padStart(2, '0');
      const mins = String(d.getMinutes()).padStart(2, '0');
      return `${day}/${month}/${year}, ${hours}.${mins}`;
    } catch {
      return rawDate;
    }
  };

  const activeJos: string[] = Array.isArray(activeHistory.no_jo)
    ? activeHistory.no_jo
    : typeof activeHistory.no_jo === 'string' && activeHistory.no_jo
    ? activeHistory.no_jo.split(',').map((j) => j.trim())
    : [];

  const operatorList = [activeHistory.operator1, activeHistory.operator2]
    .filter(Boolean)
    .join(', ');

  const handleToggleFinishJo = (noJo: string) => {
    setSelectedFinishJos((prev) =>
      prev.includes(noJo) ? prev.filter((j) => j !== noJo) : [...prev, noJo]
    );
  };

  const handleSubmitStop = async (e: React.FormEvent) => {
    e.preventDefault();
    await onStopMesin({
      good: Number(good) || 0,
      ng: Number(ng) || 0,
      remark: remark.trim(),
      jo_selesai: selectedFinishJos,
    });
  };

  return (
    <Card className="border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
      <CardHeader className="pb-3 border-b border-slate-100 dark:border-zinc-800">
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-bold tracking-tight text-slate-900 dark:text-zinc-100">
            Sedang Berjalan
          </CardTitle>
          <Badge variant="outline" className="bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 font-mono text-[11px] font-bold">
            {activeHistory.status_proses || 'RUNNING'}
          </Badge>
        </div>
      </CardHeader>

      <CardContent className="flex flex-col gap-4 text-xs pt-4">
        {/* Grid Informasi Operasional (SOLID & CRISP) */}
        <div className="grid grid-cols-2 gap-y-2.5 font-mono bg-slate-50 dark:bg-zinc-800/80 p-3.5 rounded-lg border border-slate-200 dark:border-zinc-700 text-xs">
          <div className="text-slate-500 dark:text-zinc-400 font-medium">Jenis Operator</div>
          <div className="font-bold text-slate-900 dark:text-zinc-100 uppercase">
            {activeHistory.jenis_operator || 'OPERATOR'}
          </div>

          <div className="text-slate-500 dark:text-zinc-400 font-medium">Operator</div>
          <div className="font-bold text-slate-900 dark:text-zinc-100 font-sans">
            {operatorList || '-'}
          </div>

          <div className="text-slate-500 dark:text-zinc-400 font-medium">Shift</div>
          <div className="font-bold text-slate-900 dark:text-zinc-100">
            {activeHistory.shift || '1'}
          </div>

          <div className="text-slate-500 dark:text-zinc-400 font-medium">Job Order</div>
          <div className="font-bold text-sky-700 dark:text-sky-400">
            {activeJos.join(', ') || '-'}
          </div>

          <div className="text-slate-500 dark:text-zinc-400 font-medium">Mulai</div>
          <div className="font-bold text-slate-900 dark:text-zinc-100">
            {formatMulai(activeHistory.mesin_start)}
          </div>

          <div className="text-slate-500 dark:text-zinc-400 font-medium">Durasi</div>
          <div className="font-bold text-slate-900 dark:text-zinc-100">
            {formattedDuration}
          </div>
        </div>

        {/* Form Input Good & NG dan Hentikan Mesin */}
        <form onSubmit={handleSubmitStop} className="flex flex-col gap-3.5 pt-2 border-t border-slate-100 dark:border-zinc-800">
          <div className="grid grid-cols-2 gap-3 text-xs">
            <div className="flex flex-col gap-1.5">
              <label htmlFor="input-good" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                Good (Pcs)
              </label>
              <Input
                id="input-good"
                type="number"
                min="0"
                value={good}
                onChange={(e) => setGood(Number(e.target.value))}
                className="font-mono font-bold text-emerald-700 dark:text-emerald-300 bg-white dark:bg-zinc-800 border-slate-300 dark:border-zinc-700"
              />
            </div>

            <div className="flex flex-col gap-1.5">
              <label htmlFor="input-ng" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                NG / Reject (Pcs)
              </label>
              <Input
                id="input-ng"
                type="number"
                min="0"
                value={ng}
                onChange={(e) => setNg(Number(e.target.value))}
                className="font-mono font-bold text-rose-700 dark:text-rose-300 bg-white dark:bg-zinc-800 border-slate-300 dark:border-zinc-700"
              />
            </div>
          </div>

          <div className="flex flex-col gap-1.5">
            <label htmlFor="input-remark" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
              Remark / Catatan Selesai
            </label>
            <Textarea
              id="input-remark"
              rows={2}
              value={remark}
              onChange={(e) => setRemark(e.target.value)}
              placeholder="Catatan kendala, hasil produksi, atau alasan berhenti..."
              className="bg-white dark:bg-zinc-800 border-slate-300 dark:border-zinc-700 text-slate-800 dark:text-zinc-200"
            />
          </div>

          {/* Tandai JO selesai (FINISH) */}
          {activeJos.length > 0 && (
            <div className="flex flex-col gap-1.5">
              <span className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                Tandai JO Selesai (FINISH)
              </span>
              <div className="flex flex-col gap-1">
                {activeJos.map((noJo) => (
                  <label
                    key={noJo}
                    className="flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 cursor-pointer text-xs font-mono hover:bg-slate-100 dark:hover:bg-zinc-700/60 transition"
                  >
                    <input
                      type="checkbox"
                      checked={selectedFinishJos.includes(noJo)}
                      onChange={() => handleToggleFinishJo(noJo)}
                      className="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                    />
                    <span className="font-bold text-slate-800 dark:text-zinc-200">{noJo}</span>
                  </label>
                ))}
              </div>
            </div>
          )}

          <div className="pt-1 flex justify-start">
            <Button
              type="submit"
              variant="destructive"
              disabled={isSubmitting}
              className="bg-rose-600 hover:bg-rose-700 text-white font-bold"
            >
              <Square data-icon="inline-start" className="fill-current" />
              {isSubmitting ? 'Menghentikan...' : 'Hentikan Mesin'}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
