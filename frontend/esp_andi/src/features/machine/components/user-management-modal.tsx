'use client';

import React, { useState, useEffect } from 'react';
import { Users, Search, UserCheck } from 'lucide-react';
import { fetchOperators } from '@/services/machine-api';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface UserManagementModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function UserManagementModal({ isOpen, onClose }: UserManagementModalProps) {
  const [operators, setOperators] = useState<string[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    let isCancelled = false;
    if (!isOpen) return;

    fetchOperators()
      .then((data) => {
        if (!isCancelled && data) {
          setOperators(data);
        }
      })
      .finally(() => {
        if (!isCancelled) {
          setLoading(false);
        }
      });

    return () => {
      isCancelled = true;
    };
  }, [isOpen]);

  const filtered = operators.filter((op) =>
    op.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-lg p-0 overflow-hidden flex flex-col max-h-[85vh] bg-white dark:bg-zinc-900 border border-slate-300 dark:border-zinc-700 shadow-2xl">
        <DialogHeader className="p-4 border-b border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800">
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-lg bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300">
              <Users className="size-5" />
            </div>
            <div className="flex flex-col gap-0.5">
              <DialogTitle className="text-sm font-bold text-slate-900 dark:text-zinc-100">
                User Management
              </DialogTitle>
              <DialogDescription className="text-[11px] font-mono text-slate-500 dark:text-zinc-400">
                Daftar Operator & Teknisi Aktif Terdaftar
              </DialogDescription>
            </div>
            <Badge variant="outline" className="ml-auto font-mono text-xs bg-slate-100 dark:bg-zinc-800 border-slate-300 text-slate-800 dark:text-zinc-200 font-bold">
              {operators.length} Operator
            </Badge>
          </div>
        </DialogHeader>

        {/* Search Input */}
        <div className="p-3 border-b border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
          <div className="relative">
            <Search className="size-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <Input
              type="text"
              placeholder="Cari nama operator / teknisi..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9 h-9 text-xs bg-slate-50 dark:bg-zinc-800 border-slate-300 dark:border-zinc-700 text-slate-900 dark:text-zinc-100"
            />
          </div>
        </div>

        {/* Operator Cards Grid */}
        <div className="p-4 overflow-y-auto flex-1 bg-white dark:bg-zinc-900">
          {loading ? (
            <div className="p-8 text-center text-xs text-slate-500 font-mono">
              Memuat data operator...
            </div>
          ) : filtered.length === 0 ? (
            <div className="p-8 text-center text-xs text-slate-500 font-mono">
              Tidak ada operator ditemukan.
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
              {filtered.map((op, idx) => (
                <div
                  key={`${op}-${idx}`}
                  className="p-2.5 rounded-lg border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 flex items-center gap-2.5 text-xs hover:border-sky-500 transition"
                >
                  <div className="size-7 rounded-full bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 flex items-center justify-center text-[10px] font-bold shrink-0">
                    <UserCheck className="size-3.5" />
                  </div>
                  <span className="font-semibold text-slate-800 dark:text-zinc-200 truncate">
                    {op}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="p-3 border-t border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 flex justify-end">
          <Button variant="outline" size="sm" onClick={onClose} className="text-xs border-slate-300 dark:border-zinc-700 text-slate-800 dark:text-zinc-200 bg-white dark:bg-zinc-900">
            Tutup
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
