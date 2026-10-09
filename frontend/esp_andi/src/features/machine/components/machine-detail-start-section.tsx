'use client';

import React, { useState } from 'react';
import { LaravelMappingJoItem } from '@/types/machine';
import { Play } from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface MachineDetailStartSectionProps {
  availableJos: LaravelMappingJoItem[];
  isSubmitting: boolean;
  onStartMesin: (payload: {
    jenis_operator: string;
    operator1: string;
    operator2?: string;
    shift: string;
    no_jo: string[];
  }) => Promise<void>;
}

export function MachineDetailStartSection({
  availableJos,
  isSubmitting,
  onStartMesin,
}: MachineDetailStartSectionProps) {
  const [jenis, setJenis] = useState<'OPERATOR' | 'TEKNISI' | 'SETUP'>('OPERATOR');
  const [operator1, setOperator1] = useState<string>('');
  const [operator2, setOperator2] = useState<string>('');
  const [shift, setShift] = useState<string>('1');
  const [selectedJos, setSelectedJos] = useState<string[]>([]);

  const openJos = availableJos.filter((j) => j.status_jo !== 'FINISH');

  const handleToggleJo = (noJo: string) => {
    setSelectedJos((prev) =>
      prev.includes(noJo) ? prev.filter((j) => j !== noJo) : [...prev, noJo]
    );
  };

  const handleSubmitStart = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!operator1.trim() || selectedJos.length === 0) return;

    await onStartMesin({
      jenis_operator: jenis,
      operator1: operator1.trim(),
      operator2: operator2.trim() || undefined,
      shift,
      no_jo: selectedJos,
    });
  };

  return (
    <Card className="border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
      <CardHeader className="pb-3 border-b border-slate-100 dark:border-zinc-800">
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-bold tracking-tight text-slate-900 dark:text-zinc-100">
            Mulai Operasional Mesin
          </CardTitle>
          <Badge variant="outline" className="bg-sky-100 text-sky-900 border-sky-300 dark:bg-sky-950 dark:text-sky-300 font-mono text-[11px] font-bold">
            Siap Dijalankan
          </Badge>
        </div>
      </CardHeader>

      <CardContent className="flex flex-col gap-4 text-xs pt-4">
        <form onSubmit={handleSubmitStart} className="flex flex-col gap-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div className="flex flex-col gap-1.5">
              <label htmlFor="select-jenis" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                Jenis Operator
              </label>
              <select
                id="select-jenis"
                value={jenis}
                onChange={(e) => setJenis(e.target.value as 'OPERATOR' | 'TEKNISI' | 'SETUP')}
                className="h-9 px-3 rounded-md border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-zinc-100 text-xs font-semibold focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-sky-500"
              >
                <option value="OPERATOR">OPERATOR</option>
                <option value="TEKNISI">TEKNISI</option>
                <option value="SETUP">SETUP</option>
              </select>
            </div>

            <div className="flex flex-col gap-1.5">
              <label htmlFor="select-shift" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                Shift Kerja
              </label>
              <select
                id="select-shift"
                value={shift}
                onChange={(e) => setShift(e.target.value)}
                className="h-9 px-3 rounded-md border border-slate-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-zinc-100 text-xs font-semibold focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-sky-500"
              >
                <option value="1">Shift 1</option>
                <option value="2">Shift 2</option>
                <option value="3">Shift 3</option>
              </select>
            </div>

            <div className="flex flex-col gap-1.5">
              <label htmlFor="input-operator1" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                Operator 1 <span className="text-rose-500">*</span>
              </label>
              <Input
                id="input-operator1"
                required
                type="text"
                placeholder="Nama Operator..."
                value={operator1}
                onChange={(e) => setOperator1(e.target.value)}
                className="bg-white dark:bg-zinc-800 border-slate-300 dark:border-zinc-700"
              />
            </div>

            <div className="flex flex-col gap-1.5">
              <label htmlFor="input-operator2" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                Operator 2 (Opsional)
              </label>
              <Input
                id="input-operator2"
                type="text"
                placeholder="Operator pendamping..."
                value={operator2}
                onChange={(e) => setOperator2(e.target.value)}
                className="bg-white dark:bg-zinc-800 border-slate-300 dark:border-zinc-700"
              />
            </div>
          </div>

          {/* Pilih JO yang akan dijalankan */}
          <div className="flex flex-col gap-1.5">
            <span className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
              Pilih Job Order <span className="text-rose-500">*</span>
            </span>
            {openJos.length === 0 ? (
              <p className="text-slate-400 italic text-[11px] p-3 rounded-lg border border-dashed border-slate-200 dark:border-zinc-700 text-center">
                Belum ada JO yang siap. Tambah JO terlebih dahulu di tab JO Mesin di bawah.
              </p>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-36 overflow-y-auto pr-1">
                {openJos.map((j) => (
                  <label
                    key={j.no_jo}
                    className="flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 cursor-pointer font-mono hover:bg-slate-100 dark:hover:bg-zinc-700/60 transition"
                  >
                    <input
                      type="checkbox"
                      checked={selectedJos.includes(j.no_jo)}
                      onChange={() => handleToggleJo(j.no_jo)}
                      className="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                    />
                    <div className="truncate">
                      <span className="font-bold text-sky-700 dark:text-sky-400 block">{j.no_jo}</span>
                      <span className="text-[10px] text-slate-500 dark:text-zinc-400 truncate block">{j.produk || '-'}</span>
                    </div>
                  </label>
                ))}
              </div>
            )}
          </div>

          <div className="pt-2 flex justify-start">
            <Button
              type="submit"
              disabled={isSubmitting || selectedJos.length === 0 || !operator1.trim()}
              className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold"
            >
              <Play data-icon="inline-start" className="fill-current" />
              {isSubmitting ? 'Memulai...' : 'Jalankan Mesin'}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
