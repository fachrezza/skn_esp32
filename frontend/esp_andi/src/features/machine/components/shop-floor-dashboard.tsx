'use client';

import React, { useState } from 'react';
import { MachineCardDTO } from '@/types/machine';
import { useMachineTelemetry } from '../hooks/use-machine-telemetry';
import { Sidebar } from '@/components/layout/sidebar';
import { Header } from '@/components/layout/header';
import { CommandBar } from '@/components/layout/command-bar';
import { MachineKpiRibbon } from './machine-kpi-ribbon';
import { MachineGrid } from './machine-grid';
import { TroubleLogModal } from './trouble-log-modal';
import { MachineDetailModal } from './machine-detail-modal';
import { UserManagementModal } from './user-management-modal';

interface ShopFloorDashboardProps {
  initialMachines: MachineCardDTO[];
}

export function ShopFloorDashboard({ initialMachines }: ShopFloorDashboardProps) {
  const [activeTab, setActiveTab] = useState('mesin-management');
  const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
  const [activeDetailMachine, setActiveDetailMachine] = useState<MachineCardDTO | null>(null);
  const [isUserModalOpen, setIsUserModalOpen] = useState(false);

  const {
    machines,
    filteredMachines,
    kpiData,
    filter,
    setFilter,
    searchQuery,
    setSearchQuery,
    selectedMachineCode,
    selectMachine,
    isTroubleModalOpen,
    activeTroubleMachine,
    openTroubleModal,
    closeTroubleModal,
    isLiveApi,
    refreshTelemetri,
  } = useMachineTelemetry(initialMachines);

  const filterCounts = {
    all: machines.length,
    running: kpiData.running_count,
    alarm: kpiData.breakdown_count,
    readySetup: kpiData.ready_count + kpiData.maintance_count,
  };

  return (
    <div className="min-h-screen bg-slate-100 dark:bg-zinc-950 flex flex-row font-sans text-slate-900 dark:text-zinc-100">
      {/* 1. Left Fixed Sidebar */}
      <Sidebar
        activeTab={activeTab}
        onTabChange={(tab) => {
          setActiveTab(tab);
          if (tab === 'user-management') {
            setIsUserModalOpen(true);
          } else if (tab === 'trouble-logs') {
            setFilter('ALARM');
            const alarmMachine = machines.find((m) => m.status === 'RUSAK') || machines[0];
            if (alarmMachine) openTroubleModal(alarmMachine);
          } else if (tab === 'mesin-management') {
            setFilter('ALL');
          }
        }}
      />

      {/* 2. Main Content Viewport */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Top Header */}
        <Header isLiveApi={isLiveApi} />

        {/* Scrollable Dashboard Body */}
        <main className="flex-1 p-4 md:p-6 flex flex-col gap-4 max-w-[1440px] w-full mx-auto">
          {/* Command & Quick Filter Bar */}
          <CommandBar
            filter={filter}
            onFilterChange={setFilter}
            searchQuery={searchQuery}
            onSearchChange={setSearchQuery}
            counts={filterCounts}
            onRefresh={refreshTelemetri}
            onOpenTroubleModal={() => {
              const firstAlarmMachine = machines.find((m) => m.status === 'RUSAK') || machines[0];
              openTroubleModal(firstAlarmMachine);
            }}
          />

          {/* Section 1: Top KPI Metric Summary Ribbon (4 Cards) */}
          <MachineKpiRibbon kpi={kpiData} />

          {/* Section 2: Machine Floor Grid Container */}
          <section className="flex flex-col gap-3 pt-2">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <h3 className="text-xs font-mono font-bold uppercase tracking-wider text-muted-foreground">
                  ARMADA MESIN LANTAI PRODUKSI ({filteredMachines.length})
                </h3>
              </div>
              <span className="text-[11px] font-mono text-muted-foreground">
                Lantai Produksi A • Unit Mesin Cetak Injeksi
              </span>
            </div>

            <MachineGrid
              machines={filteredMachines}
              selectedCode={selectedMachineCode}
              onSelectMachine={selectMachine}
              onActionClick={(m) => {
                if (m.status === 'RUSAK') {
                  openTroubleModal(m);
                } else {
                  selectMachine(m.no_mesin);
                  setActiveDetailMachine(m);
                  setIsDetailModalOpen(true);
                }
              }}
            />
          </section>
        </main>
      </div>

      {/* Detail Telemetri Modal (mesin_qr, mesin_mapping_jo, mesin_history) */}
      <MachineDetailModal
        isOpen={isDetailModalOpen}
        onClose={() => {
          setIsDetailModalOpen(false);
          setActiveDetailMachine(null);
        }}
        machine={activeDetailMachine}
      />

      {/* User Management Modal */}
      <UserManagementModal
        isOpen={isUserModalOpen}
        onClose={() => {
          setIsUserModalOpen(false);
          setActiveTab('mesin-management');
        }}
      />

      {/* Trouble Log Modal */}
      <TroubleLogModal
        isOpen={isTroubleModalOpen}
        onClose={closeTroubleModal}
        machine={activeTroubleMachine}
      />
    </div>
  );
}
