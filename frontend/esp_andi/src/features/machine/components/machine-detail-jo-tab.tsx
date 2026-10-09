'use client';

import React from 'react';
import { LaravelMappingJoItem } from '@/types/machine';
import { Trash2, PlusCircle, CheckCircle2, AlertCircle } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface MachineDetailJoTabProps {
  joList: LaravelMappingJoItem[];
  formData: {
    no_jo: string;
    qtyjo: string;
    produk: string;
    boms: string;
    prioritas: number;
    tgl_jo: string;
  };
  isSubmitting: boolean;
  feedback: { type: 'success' | 'error'; message: string } | null;
  onInputChange: (field: string, value: string | number) => void;
  onAddJo: (e: React.FormEvent) => void;
  onStatusChange: (noJo: string, newStatus: string) => void;
  onDelete: (noJo: string) => void;
}

export function MachineDetailJoTab({
  joList,
  formData,
  isSubmitting,
  feedback,
  onInputChange,
  onAddJo,
  onStatusChange,
  onDelete,
}: MachineDetailJoTabProps) {
  return (
    <div className="flex flex-col gap-6">
      {/* Alert feedback */}
      {feedback && (
        <Alert
          variant={feedback.type === 'error' ? 'destructive' : 'default'}
          className={
            feedback.type === 'success'
              ? 'bg-emerald-50 text-emerald-900 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-200'
              : 'bg-rose-50 text-rose-900 border-rose-300 dark:bg-rose-950 dark:text-rose-200'
          }
        >
          {feedback.type === 'success' ? (
            <CheckCircle2 data-icon="inline-start" className="text-emerald-600 dark:text-emerald-400" />
          ) : (
            <AlertCircle data-icon="inline-start" className="text-rose-600 dark:text-rose-400" />
          )}
          <AlertDescription className="font-semibold">{feedback.message}</AlertDescription>
        </Alert>
      )}

      {/* 1. Tabel JO Mesin */}
      <div className="flex flex-col gap-2.5">
        <div className="flex items-center justify-between">
          <h4 className="text-sm font-bold text-slate-900 dark:text-zinc-100 tracking-tight">
            Daftar JO Mesin ({joList.length})
          </h4>
        </div>

        {joList.length === 0 ? (
          <div className="p-8 text-center border border-dashed border-slate-200 dark:border-zinc-700 rounded-xl text-xs text-slate-500 dark:text-zinc-400 bg-slate-50 dark:bg-zinc-800/40">
            Belum ada Job Order yang dimapping ke mesin ini.
          </div>
        ) : (
          <div className="border border-slate-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 overflow-hidden shadow-xs">
            <Table>
              <TableHeader>
                <TableRow className="bg-slate-100 dark:bg-zinc-800 border-b border-slate-200 dark:border-zinc-700">
                  <TableHead className="w-[120px] font-bold text-slate-800 dark:text-zinc-200 text-xs">No JO</TableHead>
                  <TableHead className="font-bold text-slate-800 dark:text-zinc-200 text-xs">Produk</TableHead>
                  <TableHead className="text-right font-bold text-slate-800 dark:text-zinc-200 text-xs">Qty JO</TableHead>
                  <TableHead className="text-center w-[70px] font-bold text-slate-800 dark:text-zinc-200 text-xs">Prio</TableHead>
                  <TableHead className="w-[110px] font-bold text-slate-800 dark:text-zinc-200 text-xs">Status</TableHead>
                  <TableHead className="text-center w-[60px] font-bold text-slate-800 dark:text-zinc-200 text-xs">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {joList.map((item) => (
                  <TableRow key={item.no_jo} className="hover:bg-slate-50 dark:hover:bg-zinc-800/60 border-b border-slate-100 dark:border-zinc-800">
                    <TableCell className="font-mono">
                      <div className="font-bold text-sky-700 dark:text-sky-400">{item.no_jo}</div>
                      <div className="text-[10px] text-slate-500 dark:text-zinc-400">{item.tgl_jo || '-'}</div>
                    </TableCell>
                    <TableCell>
                      <div className="font-semibold text-xs text-slate-800 dark:text-zinc-200">{item.produk || '-'}</div>
                      {item.boms && <div className="text-[10px] text-slate-500 dark:text-zinc-400 font-mono">{item.boms}</div>}
                    </TableCell>
                    <TableCell className="text-right font-mono font-bold text-slate-900 dark:text-zinc-100">
                      {item.qtyjo?.toLocaleString('id-ID') ?? 0}
                    </TableCell>
                    <TableCell className="text-center font-mono font-bold text-amber-700 dark:text-amber-400">
                      {item.prioritas ?? 1}
                    </TableCell>
                    <TableCell>
                      <select
                        aria-label={`Status JO ${item.no_jo}`}
                        value={item.status_jo}
                        onChange={(e) => onStatusChange(item.no_jo, e.target.value)}
                        className={`text-[11px] font-bold px-2 py-1 rounded border cursor-pointer ${
                          item.status_jo === 'RUNNING'
                            ? 'text-emerald-900 bg-emerald-100 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-700'
                            : item.status_jo === 'FINISH'
                            ? 'text-slate-700 bg-slate-200 border-slate-300 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700'
                            : 'text-sky-900 bg-sky-100 border-sky-300 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-700'
                        }`}
                      >
                        <option value="OPEN">OPEN</option>
                        <option value="RUNNING">RUNNING</option>
                        <option value="PENDING">PENDING</option>
                        <option value="HOLD">HOLD</option>
                        <option value="FINISH">FINISH</option>
                      </select>
                    </TableCell>
                    <TableCell className="text-center">
                      <Button
                        variant="ghost"
                        size="icon-xs"
                        onClick={() => onDelete(item.no_jo)}
                        title="Hapus JO"
                        className="text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                      >
                        <Trash2 className="size-3.5" />
                        <span className="sr-only">Hapus JO</span>
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </div>

      {/* 2. Form Tambah JO (SOLID CARD) */}
      <Card className="border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800/80 shadow-xs">
        <CardHeader className="pb-3 border-b border-slate-200 dark:border-zinc-700">
          <CardTitle className="text-sm font-bold text-slate-900 dark:text-zinc-100 tracking-tight flex items-center gap-1.5">
            <PlusCircle className="size-4 text-sky-600 dark:text-sky-400" />
            <span>Tambah Job Order Baru</span>
          </CardTitle>
        </CardHeader>

        <CardContent className="flex flex-col gap-4 text-xs pt-4">
          <form onSubmit={onAddJo} className="flex flex-col gap-3">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div className="flex flex-col gap-1.5">
                <label htmlFor="input-nojo" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                  No JO <span className="text-rose-500">*</span>
                </label>
                <Input
                  id="input-nojo"
                  required
                  type="text"
                  placeholder="Contoh: JO1QA00101"
                  value={formData.no_jo}
                  onChange={(e) => onInputChange('no_jo', e.target.value)}
                  className="font-mono bg-white dark:bg-zinc-900 border-slate-300 dark:border-zinc-700"
                />
              </div>

              <div className="flex flex-col gap-1.5">
                <label htmlFor="input-qtyjo" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                  Qty JO <span className="text-rose-500">*</span>
                </label>
                <Input
                  id="input-qtyjo"
                  required
                  type="number"
                  min="1"
                  placeholder="Contoh: 2100"
                  value={formData.qtyjo}
                  onChange={(e) => onInputChange('qtyjo', e.target.value)}
                  className="font-mono bg-white dark:bg-zinc-900 border-slate-300 dark:border-zinc-700"
                />
              </div>

              <div className="flex flex-col gap-1.5">
                <label htmlFor="input-produk" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                  Produk
                </label>
                <Input
                  id="input-produk"
                  type="text"
                  placeholder="Contoh: ZLTUBWT30004"
                  value={formData.produk}
                  onChange={(e) => onInputChange('produk', e.target.value)}
                  className="bg-white dark:bg-zinc-900 border-slate-300 dark:border-zinc-700"
                />
              </div>

              <div className="flex flex-col gap-1.5">
                <label htmlFor="input-boms" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                  BOMS
                </label>
                <Input
                  id="input-boms"
                  type="text"
                  placeholder="Contoh: ZLTUBWT30004.V01"
                  value={formData.boms}
                  onChange={(e) => onInputChange('boms', e.target.value)}
                  className="font-mono bg-white dark:bg-zinc-900 border-slate-300 dark:border-zinc-700"
                />
              </div>

              <div className="flex flex-col gap-1.5">
                <label htmlFor="input-prioritas" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                  Prioritas (1 = Paling Prioritas)
                </label>
                <Input
                  id="input-prioritas"
                  type="number"
                  min="1"
                  value={formData.prioritas}
                  onChange={(e) => onInputChange('prioritas', e.target.value)}
                  className="font-mono bg-white dark:bg-zinc-900 border-slate-300 dark:border-zinc-700"
                />
              </div>

              <div className="flex flex-col gap-1.5">
                <label htmlFor="input-tgljo" className="text-slate-600 dark:text-zinc-300 font-semibold text-[11px]">
                  Tanggal JO
                </label>
                <Input
                  id="input-tgljo"
                  type="date"
                  value={formData.tgl_jo}
                  onChange={(e) => onInputChange('tgl_jo', e.target.value)}
                  className="font-mono bg-white dark:bg-zinc-900 border-slate-300 dark:border-zinc-700"
                />
              </div>
            </div>

            <div className="flex justify-end pt-2">
              <Button
                type="submit"
                disabled={isSubmitting}
                className="bg-sky-600 hover:bg-sky-700 text-white font-bold"
              >
                <PlusCircle data-icon="inline-start" />
                {isSubmitting ? 'Menyimpan...' : 'Simpan Job Order'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
