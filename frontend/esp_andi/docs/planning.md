# Master Implementation Plan: Shop Floor & Job Order Monitor

Rencana aksi bertahap untuk membangun dashboard monitoring mesin dan antrean Job Order dengan Clean Code, pemisahan mutlak antara UI dan Business Logic, serta integrasi React Compiler.

---

## Fase 1: Domain Modeling & Normalisasi Skema Legacy

- [ ] Definisikan kontrak tipe TypeScript di `src/types/machine.ts`:
  - Normalisasi enum status mesin (`READY`, `RUNNING`, `MAINTANCE`, `OFF`, `RUSAK`).
  - Interface untuk tabel `mesin_qr`, `mesin_mapping_jo`, dan `mesin_troublle_log`.
- [ ] Buat data transformation layer (DTO) di `src/services/machine-transformer.ts`:
  - Bersihkan string typo warisan backend (seperti penanganan status `MAINTANCE`).
  - Gabungkan relasi antara mesin aktif dengan detail JO yang sedang berjalan (`running_jo` dengan `mesin_mapping_jo`).

---

## Fase 2: Service Layer & Database Query (Data Access)

- [ ] Bangun layer akses data di `src/services/machine-service.ts`:
  - Query fetching data kartu mesin beserta target dan akumulasi output barang jadi.
  - Query log kendala mesin dari `mesin_troublle_log`.
- [ ] Buat Next.js Route Handler atau Server Actions di `src/app/api/machines/route.ts` untuk menyediakan endpoint siap pakai bagi client.

---

## Fase 3: Business Logic & Custom Hooks Layer

- [ ] Buat custom hook `src/features/machine/hooks/use-machine-card.ts`:
  - Menangani kalkulasi persentase penyelesaian target Job Order:
    $$\text{Progress (\%)} = \left(\frac{\text{qty\_good}}{\text{qtyjo}}\right) \times 100$$
  - Format angka ribuan untuk output produksi.
  - Memetakan styling badge berdasarkan status mesin secara terisolasi.
- [ ] Buat custom hook `src/features/machine/hooks/use-machine-telemetry.ts`:
  - Mengelola polling data otomatis atau koneksi SSE/WebSocket dari gateway ESP32 (`uid_esp`).
  - Menangani state loading, error, dan pembersihan interval saat unmount.

---

## Fase 4: Presentational UI & Integrasi Reactbits

- [ ] Pasang komponen visual murni di `src/components/reactbits/`:
  - `spotlight-card.tsx`: Kontainer kartu dengan aksen sorot mouse interaktif.
  - `animated-counter.tsx`: Transisi angka bergulir untuk metrik kuantitas.
- [ ] Bangun komponen presentasional di `src/features/machine/components/`:
  - `machine-card.tsx`: Komponen murni tanpa fetch atau kalkulasi rumit, hanya menerima hasil olahan hook.
  - `machine-status-badge.tsx`: Komponen indikator status mesin dengan efek animasi ping.
  - `machine-grid.tsx`: Grid layout untuk menyusun seluruh unit mesin pabrik.
  - `trouble-log-modal.tsx`: Dialog ringkas untuk menampilkan histori downtime mesin.

---

## Fase 5: Halaman Utama & Server Component Integration

- [ ] Susun `src/app/page.tsx` memanfaatkan React Server Components (RSC):
  - Tarik data awal mesin langsung di server untuk kecepatan First Contentful Paint (FCP).
  - Teruskan data awal ke client component container sebagai initial state.
- [ ] Pasang panel ringkasan KPI di bagian atas dashboard (total mesin running, rasio good vs reject, mesin breakdown).

---

## Fase 6: Validasi Kualitas & Audit Performa

- [ ] Verifikasi optimasi React Compiler:
  - Buka React DevTools Profiler, pastikan komponen tidak mengalami re-render massal yang tidak perlu.
  - Pastikan tidak ada peringatan ESLint terkait pelanggaran *Rules of React*.
- [ ] Uji responsivitas layout pada layar display resolusi 1920x1080 (standar monitor kontrol pabrik).