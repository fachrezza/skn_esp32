import { INITIAL_MACHINES } from '@/services/machine-service';
import { ShopFloorDashboard } from '@/features/machine/components/shop-floor-dashboard';

export const metadata = {
  title: 'PT Sanken • Pemantauan Lantai Produksi & Job Order',
  description: 'Dashboard Realtime Telemetri IIoT Mesin Injection Molding PT Sanken',
};

export default function Page() {
  // Initial machines for instant hydration, client hook polls live Laravel backend
  return <ShopFloorDashboard initialMachines={INITIAL_MACHINES} />;
}
