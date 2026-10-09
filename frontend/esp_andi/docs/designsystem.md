
# Design System Specification: Industrial IIoT Shop Floor (Light Mode)

Version: 2.0 (Inspired by AURA MES Clean Industrial)

Dokumen ini adalah acuan baku styling, token warna, tipografi, dan micro-interaction Reactbits untuk dashboard pabrik berbasis High-Contrast Clean Light Mode.

---

## 1. Filosofi Desain

- **Ergonomi Lantai Produksi**: Menggunakan latar *off-white/light-slate* untuk meminimalkan pantulan cahaya lampu pabrik tanpa membuat mata silau.
- **Status-First Border Framing**: Kartu mesin tidak hanya mengandalkan badge kecil, melainkan dibingkai oleh border berwarna tebal sesuai status operasionalnya agar deteksi anomali bersifat instan.
- **Tipografi Tabular & Monospace**: Angka output, waktu siklus (CT), dan kode mesin wajib menggunakan font tabular agar layout tidak bergeser (*zero layout shift*) saat telemetri realtime masuk.

---

## 2. Palet Warna & Semantik Token

### Background & Surface

- **Canvas Base**: `#F1F5F9` (`slate-100`) - Latar belakang utama dashboard.
- **Card Surface**: `#FFFFFF` (`white`) - Permukaan kartu mesin & widget KPI.
- **Panel Header / Sidebar**: `#F8FAFC` (`slate-50`) dengan border `#E2E8F0` (`slate-200`).
- **Sub-box Background**: `#F8FAFC` (`slate-50`) - Kontainer internal untuk info JO, counter Good/NG, dan tele-control.

### Semantik Status Mesin (`mesin_qr.status_mesin`)

| Status Mesin                | Border Card            | Badge Pill (Kapsul)           | Text / Accent        | Status Dot / Pulse               |
| :-------------------------- | :--------------------- | :---------------------------- | :------------------- | :------------------------------- |
| **RUNNING**           | `border-emerald-500` | `bg-emerald-500 text-white` | `text-emerald-700` | `bg-emerald-400 animate-pulse` |
| **READY / STANDBY**   | `border-sky-400`     | `bg-sky-500 text-white`     | `text-sky-700`     | `bg-sky-400`                   |
| **MAINTANCE / SETUP** | `border-amber-400`   | `bg-amber-500 text-white`   | `text-amber-800`   | `bg-amber-400 animate-pulse`   |
| **RUSAK / BREAKDOWN** | `border-rose-500`    | `bg-rose-500 text-white`    | `text-rose-700`    | `bg-rose-500 animate-ping`     |
| **OFF / STOP**        | `border-slate-300`   | `bg-slate-400 text-white`   | `text-slate-600`   | `bg-slate-400`                 |

### Token Aksen & Output

- **Good Output**: `text-emerald-600` (`#059669`) dengan background counter `bg-emerald-50/50`.
- **Defect / NG Output**: `text-rose-600` (`#E11D48`) dengan background counter `bg-rose-50/50`.
- **Warning Alert Box** (Mesin Rusak): `bg-rose-50 border border-rose-200 text-rose-800`.
- **Action Button Urgent** (Page Maintenance): `bg-rose-600 hover:bg-rose-700 text-white`.
- **Standard Action Button**: `bg-white border border-slate-200 hover:bg-slate-50 text-slate-700`.

---

## 3. Tipografi & Hierarki Teks

- **UI Interface Font**: `Inter`, `Geist Sans`, atau sans-serif modern.
- **Telemetry & Metric Font**: `JetBrains Mono` atau `Geist Mono` (Wajib untuk: `no_mesin`, `no_jo`, `CT (Cycle Time)`, counter produksi, dan IP/ESP UID).
- **Skala Ukuran**:
  - **KPI Hero Value**: `text-2xl font-bold font-mono tracking-tight text-slate-900`
  - **Machine Code Header**: `text-base font-bold font-mono text-slate-900`
  - **Machine Subtitle / Brand**: `text-[11px] font-medium text-slate-500`
  - **Metric Counter**: `text-lg font-bold font-mono`
  - **Label / Subtext**: `text-[10px] uppercase font-semibold tracking-wider text-slate-400`

---

## 4. Komponen Reactbits (Adaptasi Light Theme)

Semua animasi disimpan di `src/components/reactbits/` dan dikonfigurasi untuk latar terang:

### A. Spotlight Card (Light Mode Version)

- **Trik**: Jangan pakai radial gradient putih di atas background putih, nggak bakal keliatan!
- **Styling**: Gunakan sorot bayangan atau tint aksen tipis: `radial-gradient(circle at ...px ...px, rgba(14, 165, 233, 0.08), transparent 80%)` agar kartu terasa hidup saat kursor melintas.

### B. Animated Counter / Rolling Number

- **Penerapan**: Digunakan pada counter `Good Qty`, `Defect NG`, dan kalkulasi persentase target pengerjaan JO.
- **Aturan**: Angka bergulir halus tanpa jeda frame (60 FPS) ketika websocket telemetri ESP32 memicu pembaruan data.

### C. Magnet Pill Button

- **Penerapan**: Tombol "Dispatch New JO", tombol filter line produksi, dan tombol drawer "Live Diagnostics".

---

## 5. Layout & Spacing Specification

- **App Shell**:
  - Sidebar ramping di sisi kiri (`w-56 bg-slate-50 border-r border-slate-200`).
  - Top Navigation Bar (`h-14 bg-white border-b border-slate-200 px-6 flex items-center justify-between`).
  - Main Area: Background `bg-slate-100/80 p-6`.
- **Top KPI Ribbon**: 4 kolom kartu metrik (`Fleet Status`, `Volume Output`, `JO Pipeline`, `Active Alarms`).
- **Main Machine Grid**: Responsive 3-to-4 column grid (`grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4`).
- **Right Tele-Control Drawer**: Panel detail mesin fleksibel (`w-96 bg-white border-l border-slate-200 shadow-xl`) yang menampilkan telemetri langsung (Mold Temp, Pressure, Clamp Force, Vibration RMS).
