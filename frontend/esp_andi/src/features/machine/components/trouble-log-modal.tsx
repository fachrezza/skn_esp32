'use client';

import React, { useState } from 'react';
import { AlertTriangle, Wrench, CheckCircle } from 'lucide-react';
import { MachineCardDTO } from '@/types/machine';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';

interface TroubleLogModalProps {
  isOpen: boolean;
  onClose: () => void;
  machine: MachineCardDTO | null;
}

export function TroubleLogModal({ isOpen, onClose, machine }: TroubleLogModalProps) {
  const [downtimeType, setDowntimeType] = useState('ELECTRICAL');
  const [keterangan, setKeterangan] = useState('');
  const [isSubmitted, setIsSubmitted] = useState(false);

  if (!machine) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitted(true);
    setTimeout(() => {
      setIsSubmitted(false);
      onClose();
    }, 1200);
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-lg p-0 overflow-hidden flex flex-col bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 shadow-2xl">
        {/* Header */}
        <DialogHeader className="p-4 border-b border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 flex flex-row items-center gap-2.5">
          <div className="p-2 rounded-lg bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
            <AlertTriangle className="size-5" />
          </div>
          <div className="flex flex-col gap-0.5 text-left">
            <DialogTitle className="text-sm font-bold text-slate-900 dark:text-zinc-100">
              Log Trouble & Downtime
            </DialogTitle>
            <DialogDescription className="text-[11px] font-mono text-slate-500 dark:text-zinc-400">
              Unit {machine.no_mesin} • {machine.nama_mesin}
            </DialogDescription>
          </div>
        </DialogHeader>

        {/* Content */}
        {isSubmitted ? (
          <div className="p-8 text-center flex flex-col items-center gap-2 bg-white dark:bg-zinc-900">
            <CheckCircle className="size-12 text-emerald-500 animate-bounce" />
            <h4 className="text-sm font-bold text-slate-900 dark:text-zinc-100">
              Trouble Log Berhasil Dicatat!
            </h4>
            <p className="text-xs text-slate-500 dark:text-zinc-400">
              Notifikasi telah dikirim ke tim Maintenance & Spv Shift.
            </p>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="p-5 flex flex-col gap-4 bg-white dark:bg-zinc-900">
            {/* Machine summary row */}
            <div className="p-3 bg-slate-50 dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 flex items-center justify-between text-xs font-mono">
              <div>
                <span className="text-slate-500 dark:text-zinc-400 text-[10px] font-semibold block">RUNNING JO</span>
                <span className="font-bold text-sky-700 dark:text-sky-400">
                  {machine.running_jo?.no_jo || '-'}
                </span>
              </div>
              <div>
                <span className="text-slate-500 dark:text-zinc-400 text-[10px] font-semibold block">ESP32 UID</span>
                <span className="font-bold text-slate-800 dark:text-zinc-200">
                  {machine.uid_esp}
                </span>
              </div>
              <div>
                <span className="text-slate-500 dark:text-zinc-400 text-[10px] font-semibold block">STATUS</span>
                <Badge variant="destructive" className="font-mono text-[10px] font-bold bg-rose-600 text-white">
                  {machine.status}
                </Badge>
              </div>
            </div>

            {/* Category selection */}
            <div className="flex flex-col gap-1.5">
              <label className="text-xs font-bold text-slate-800 dark:text-zinc-200">
                Kategori Masalah
              </label>
              <div className="grid grid-cols-3 gap-2">
                {['ELECTRICAL', 'MECHANICAL', 'MOLD_TOOLING'].map((cat) => (
                  <Button
                    key={cat}
                    type="button"
                    variant={downtimeType === cat ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => setDowntimeType(cat)}
                    className={`font-mono font-bold text-[11px] h-8 ${
                      downtimeType === cat
                        ? 'bg-rose-600 text-white hover:bg-rose-700'
                        : 'bg-white dark:bg-zinc-800 border-slate-300 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 hover:bg-slate-100'
                    }`}
                  >
                    {cat}
                  </Button>
                ))}
              </div>
            </div>

            {/* Keterangan */}
            <div className="flex flex-col gap-1.5">
              <label className="text-xs font-bold text-slate-800 dark:text-zinc-200">
                Keterangan / Diagnosa Kerusakan
              </label>
              <Textarea
                required
                rows={3}
                value={keterangan}
                onChange={(e) => setKeterangan(e.target.value)}
                placeholder="Contoh: Nozzle heater thermocouple error, temperatur melonjak ke 280°C..."
                className="text-xs font-mono bg-white dark:bg-zinc-800 border-slate-300 dark:border-zinc-700 text-slate-800 dark:text-zinc-200"
              />
            </div>

            {/* Modal Actions */}
            <div className="pt-2 flex items-center justify-end gap-2 border-t border-slate-200 dark:border-zinc-700">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={onClose}
                className="text-xs border-slate-300 dark:border-zinc-700 text-slate-800 dark:text-zinc-200 bg-white dark:bg-zinc-800"
              >
                Batal
              </Button>
              <Button
                type="submit"
                variant="destructive"
                size="sm"
                className="text-xs font-bold gap-1.5 bg-rose-600 hover:bg-rose-700 text-white"
              >
                <Wrench data-icon="inline-start" />
                <span>Simpan Log Downtime</span>
              </Button>
            </div>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
}
