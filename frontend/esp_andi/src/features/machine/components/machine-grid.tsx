'use client';

import React from 'react';
import { MachineCardDTO } from '@/types/machine';
import { MachineCard } from './machine-card';
import { Inbox } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

interface MachineGridProps {
  machines: MachineCardDTO[];
  selectedCode?: string | null;
  onSelectMachine?: (no_mesin: string) => void;
  onActionClick?: (machine: MachineCardDTO) => void;
}

export function MachineGrid({
  machines,
  selectedCode,
  onSelectMachine,
  onActionClick,
}: MachineGridProps) {
  if (machines.length === 0) {
    return (
      <Card className="border border-border p-8 text-center bg-card">
        <CardContent className="flex flex-col items-center justify-center p-0 gap-2">
          <Inbox className="size-10 text-muted-foreground/50 mb-1" />
          <h3 className="text-sm font-semibold text-foreground">
            Tidak Ada Mesin yang Sesuai Filter
          </h3>
          <p className="text-xs text-muted-foreground">
            Coba ganti filter status atau bersihkan kata kunci pencarian.
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      {machines.map((machine) => (
        <MachineCard
          key={machine.no_mesin}
          machine={machine}
          isSelected={machine.no_mesin === selectedCode}
          onSelect={onSelectMachine}
          onActionClick={onActionClick}
        />
      ))}
    </div>
  );
}
