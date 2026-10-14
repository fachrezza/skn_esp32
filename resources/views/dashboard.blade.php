<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Mesin</title>
    <style>
        :root {
            --bg: #f3f4f6; --panel: #ffffff; --text: #111827; --muted: #6b7280; --border: #e5e7eb;
            --accent: #2563eb; --run: #16a34a; --run-bg: #dcfce7; --ready: #2563eb; --ready-bg: #dbeafe;
            --maint: #d97706; --maint-bg: #fef3c7; --off: #6b7280; --off-bg: #f3f4f6; --rusak: #dc2626; --rusak-bg: #fee2e2;
            --setter: #9333ea; --setter-bg: #f3e8ff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a; --panel: #1e293b; --text: #f1f5f9; --muted: #94a3b8; --border: #334155;
                --accent: #60a5fa; --run: #4ade80; --run-bg: #14532d; --ready: #93c5fd; --ready-bg: #1e3a8a;
                --maint: #fbbf24; --maint-bg: #78350f; --off: #cbd5e1; --off-bg: #334155; --rusak: #fca5a5; --rusak-bg: #7f1d1d;
                --setter: #d8b4fe; --setter-bg: #581c87;
            }
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); }
        header { padding: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: var(--muted); font-size: 13px; }
        main { padding: 0 16px 32px; max-width: 1400px; margin: 0 auto; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .stat { background: var(--panel); border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
        .stat b { display: block; font-size: 26px; margin-top: 4px; font-variant-numeric: tabular-nums; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; align-items: center; }
        .tabs { display: flex; gap: 4px; background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 3px; }
        .tabs button { border: 0; background: transparent; color: var(--text); padding: 6px 12px; border-radius: 6px; cursor: pointer; font: inherit; }
        .tabs button.on { background: var(--accent); color: #fff; }
        input, select, textarea, button { font: inherit; }
        input, select, textarea { width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; background: var(--panel); color: var(--text); }
        .toolbar input { max-width: 260px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .card { background: var(--panel); border: 1px solid var(--border); border-left: 5px solid var(--off); border-radius: 10px; padding: 12px; cursor: pointer; text-align: left; color: var(--text); width: 100%; }
        .card:hover { border-color: var(--accent); }
        .card.s-RUNNING { border-left-color: var(--run); }
        .card.s-READY { border-left-color: var(--ready); }
        .card.s-PRODUKSI { border-left-color: var(--run); }
        .card.s-MOULD { border-left-color: var(--maint); }
        .card.s-SETTER { border-left-color: var(--setter); }
        .card.s-SIAP { border-left-color: var(--run); border-left-style: dashed; }
        .card.s-MAINTANCE { border-left-color: var(--maint); }
        .card.s-RUSAK { border-left-color: var(--rusak); }
        .card-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .card h3 { margin: 0; font-size: 18px; }
        .card .line { font-size: 13px; margin-top: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .timer { font-variant-numeric: tabular-nums; font-weight: 600; }
        .badge { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; white-space: nowrap; }
        .b-RUNNING { background: var(--run-bg); color: var(--run); }
        .b-READY { background: var(--ready-bg); color: var(--ready); }
        .b-PRODUKSI { background: var(--run-bg); color: var(--run); }
        .b-MOULD { background: var(--maint-bg); color: var(--maint); }
        .b-SETTER { background: var(--setter-bg); color: var(--setter); }
        .b-SIAP { background: var(--run-bg); color: var(--run); outline: 1px dashed var(--run); }
        .b-MAINTANCE { background: var(--maint-bg); color: var(--maint); }
        .b-OFF { background: var(--off-bg); color: var(--off); }
        .b-RUSAK { background: var(--rusak-bg); color: var(--rusak); }
        .b-OPEN { background: var(--ready-bg); color: var(--ready); }
        .b-FINISH { background: var(--off-bg); color: var(--off); }
        .b-HOLD, .b-PENDING { background: var(--maint-bg); color: var(--maint); }
        dialog { border: 0; padding: 0; margin: 0 0 0 auto; height: 100vh; max-height: 100vh; width: min(560px, 100vw); background: var(--panel); color: var(--text); }
        dialog::backdrop { background: rgba(0, 0, 0, .45); }
        .drawer-head { position: sticky; top: 0; background: var(--panel); border-bottom: 1px solid var(--border); padding: 16px; display: flex; justify-content: space-between; gap: 8px; align-items: flex-start; z-index: 1; }
        .drawer-head h2 { margin: 0 0 4px; font-size: 20px; }
        section { padding: 16px; border-bottom: 1px solid var(--border); }
        section h4 { margin: 0 0 12px; font-size: 15px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-grid .full { grid-column: 1 / -1; }
        label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 4px; }
        .btn { border: 1px solid var(--border); background: var(--panel); color: var(--text); padding: 8px 14px; border-radius: 6px; cursor: pointer; }
        .btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn-run { background: var(--run); border-color: var(--run); color: #fff; }
        .btn-stop { background: var(--rusak); border-color: var(--rusak); color: #fff; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .checks { display: grid; gap: 6px; max-height: 180px; overflow: auto; border: 1px solid var(--border); border-radius: 6px; padding: 8px; }
        .checks label { display: flex; gap: 8px; align-items: center; color: var(--text); font-size: 13px; margin: 0; }
        .checks input { width: auto; }
        .kv { display: grid; grid-template-columns: 110px 1fr; gap: 6px 10px; font-size: 14px; margin-bottom: 12px; }
        .kv dt { color: var(--muted); }
        .kv dd { margin: 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 6px 4px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: middle; }
        td select { padding: 3px 4px; font-size: 12px; }
        .msg { font-size: 13px; margin-top: 10px; padding: 8px 10px; border-radius: 6px; display: none; }
        .msg.err { display: block; background: var(--rusak-bg); color: var(--rusak); }
        .msg.ok { display: block; background: var(--run-bg); color: var(--run); }
        .hint { font-size: 13px; padding: 8px 10px; border-radius: 6px; background: var(--ready-bg); color: var(--ready); margin-bottom: 12px; }
        .empty { padding: 32px; text-align: center; color: var(--muted); }
        @media (max-width: 480px) { .form-grid { grid-template-columns: 1fr; } .toolbar input { max-width: none; } }
    </style>
</head>
<body>
<header>
    <div>
        <h1>Dashboard Mesin</h1>
        <div class="muted" id="last-update">Memuat data…</div>
    </div>
    <button class="btn" type="button" id="btn-refresh">Muat ulang</button>
</header>

<main>
    <div class="summary" id="summary"></div>

    <div class="toolbar">
        <div class="tabs" id="tabs">
            <button type="button" data-filter="semua" class="on">Semua</button>
            <button type="button" data-filter="nyala">Nyala</button>
            <button type="button" data-filter="mati">Tidak nyala</button>
        </div>
        <input type="search" id="cari" placeholder="Cari no / nama mesin…">
    </div>

    <div class="grid" id="grid"></div>
</main>

<dialog id="drawer">
    <div class="drawer-head">
        <div id="drawer-title"></div>
        <button class="btn" type="button" id="btn-close">Tutup</button>
    </div>
    <section id="sec-run"></section>
    <section id="sec-jo"></section>
</dialog>

<datalist id="dl-operator"></datalist>

<script>
    const API = @json(url('/api'));
    // Sama dengan listStatus di test.ino: PRODUKSI (OPERATOR), MOULD (TEKNISI), SETTER (SETUP)
    const STATUS_SESI = ['PRODUKSI', 'MOULD', 'SETTER'];
    const sedangNyala = (m) => STATUS_SESI.includes(m.status_tampilan);
    const state = { mesin: [], fetchedAt: Date.now(), filter: 'semua', cari: '', aktif: null, jo: [] };

    const $ = (sel) => document.querySelector(sel);
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const enc = encodeURIComponent;
    const durasi = (s) => {
        s = Math.max(0, Math.floor(s));
        return [Math.floor(s / 3600), Math.floor(s % 3600 / 60), s % 60].map((n) => String(n).padStart(2, '0')).join(':');
    };
    const jamLokal = (utc) => utc ? new Date(utc.replace(' ', 'T') + 'Z').toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) : '-';
    const operatorDari = (h) => h ? [h.operator1, h.operator2, h.nama_teknisi, h.nama_setup].filter(Boolean).join(', ') : '';
    // READY tanpa operator tidak bisa RUNNING; READY + operator disiapkan = "SIAP RUNNING"
    // OPERATOR = produksi (RUNNING); TEKNISI = mold dan SETUP = setter berjalan dengan status READY
    const TAHAP = { OPERATOR: 'PRODUKSI', TEKNISI: 'MOULD', SETUP: 'SETTER' };
    const kodeStatus = (m) => m.siap_running ? 'SIAP' : m.status_tampilan;
    const labelStatus = (m) => m.siap_running ? `READY · siap ${TAHAP[m.pending_history?.jenis_operator] || 'PRODUKSI'}` : m.status_tampilan;

    async function api(path, method = 'GET', body = null) {
        const res = await fetch(API + path, {
            method,
            headers: { 'Accept': 'application/json', ...(body ? { 'Content-Type': 'application/json' } : {}) },
            body: body ? JSON.stringify(body) : null,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : null;
            throw new Error(errors || data.message || `Gagal (HTTP ${res.status})`);
        }
        return data;
    }

    function tampilPesan(el, teks, ok = false) {
        el.textContent = teks;
        el.className = 'msg ' + (ok ? 'ok' : 'err');
    }

    async function muatMesin() {
        try {
            const res = await api('/mesin');
            state.mesin = res.data;
            state.fetchedAt = Date.now();
            $('#last-update').textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID') + ' · otomatis tiap 10 detik';
            render();
            if (state.aktif && $('#drawer').open) {
                renderJudul();
                // Mis. RUNNING ditekan di ESP: ganti panel tanpa menghapus isian form bila tidak berubah
                if (mesinAktif() && kunciPanel(mesinAktif()) !== state.kunciPanel) {
                    renderRun();
                }
            }
        } catch (e) {
            $('#last-update').textContent = 'Gagal memuat: ' + e.message;
        }
    }

    function mesinAktif() {
        return state.mesin.find((m) => m.no_mesin === state.aktif);
    }

    function render() {
        const hitung = (s) => state.mesin.filter((m) => m.status_tampilan === s).length;
        const nyala = state.mesin.filter(sedangNyala).length;
        $('#summary').innerHTML = [
            ['Total mesin', state.mesin.length],
            ['Produksi', hitung('PRODUKSI')],
            ['Mould', hitung('MOULD')],
            ['Setter', hitung('SETTER')],
            ['Ready', hitung('READY')],
            ['Siap running', state.mesin.filter((m) => m.siap_running).length],
            ['Maintenance', hitung('MAINTANCE')],
            ['Off / Rusak', hitung('OFF') + hitung('RUSAK')],
        ].map(([k, v]) => `<div class="stat"><span class="muted">${k}</span><b>${v}</b></div>`).join('');

        const q = state.cari.toLowerCase();
        const list = state.mesin.filter((m) => {
            if (state.filter === 'nyala' && !sedangNyala(m)) return false;
            if (state.filter === 'mati' && sedangNyala(m)) return false;
            return !q || m.no_mesin.toLowerCase().includes(q) || (m.nama_mesin || '').toLowerCase().includes(q);
        });

        $('#grid').innerHTML = list.length ? list.map((m) => {
            const h = m.active_history || m.pending_history;
            const jo = m.running_jo.length ? m.running_jo : (m.pending_history?.no_jo || []);
            return `<button type="button" class="card s-${esc(kodeStatus(m))}" data-no="${esc(m.no_mesin)}">
                <div class="card-top"><h3>${esc(m.no_mesin)}</h3><span class="badge b-${esc(kodeStatus(m))}">${esc(labelStatus(m))}</span></div>
                <div class="line muted">${esc(m.nama_mesin)}${m.uid_esp ? ' · ESP ' + esc(m.uid_esp) : ''}</div>
                <div class="line">👤 ${esc(operatorDari(h) || 'Belum ada operator')}</div>
                <div class="line">📄 ${esc(jo.join(', ') || '-')}</div>
                <div class="line muted">${sedangNyala(m) ? 'Berjalan' : 'Status selama'} <span class="timer" data-sejak="${Date.now() / 1000 - m.timer_sec}">${durasi(m.timer_sec)}</span></div>
            </button>`;
        }).join('') : '<div class="empty">Tidak ada mesin yang cocok.</div>';
    }

    function tickTimer() {
        document.querySelectorAll("[data-sejak]").forEach((el) => {
            el.textContent = durasi(Date.now() / 1000 - Number(el.dataset.sejak));
        });
    }

    async function bukaDrawer(no) {
        state.aktif = no;
        state.jo = [];
        renderJudul();
        renderRun();
        $('#sec-jo').innerHTML = '<p class="muted">Memuat JO…</p>';
        $('#drawer').showModal();
        await muatJo();
    }

    function renderJudul() {
        const m = mesinAktif();
        if (!m) return;
        $('#drawer-title').innerHTML = `<h2>Mesin ${esc(m.no_mesin)}</h2>
            <span class="badge b-${esc(kodeStatus(m))}">${esc(labelStatus(m))}</span>
            <span class="muted"> ${esc(m.nama_mesin)}${m.uid_esp ? ' · ESP ' + esc(m.uid_esp) : ' · ESP belum terpasang'}</span>`;
    }

    async function muatJo() {
        try {
            const res = await api(`/mesin/${enc(state.aktif)}/jo`);
            state.jo = res.data;
        } catch (e) {
            state.jo = [];
        }
        renderRun();
        renderJo();
    }

    function shiftSekarang() {
        const jam = new Date().getHours();
        return jam >= 7 && jam < 15 ? '1' : (jam >= 15 && jam < 23 ? '2' : '3');
    }

    const PILIHAN_JENIS = [
        ['OPERATOR', 'Operator (PRODUKSI)'],
        ['TEKNISI', 'Teknisi (MOULD)'],
        ['SETUP', 'Setup (SETTER)'],
    ];

    function opsiJenis(terpilih = 'OPERATOR') {
        return PILIHAN_JENIS.map(([v, t]) => `<option value="${v}" ${v === terpilih ? 'selected' : ''}>${t}</option>`).join('');
    }

    function opsiShift(terpilih = shiftSekarang()) {
        return ['1', '2', '3'].map((s) => `<option ${s === terpilih ? 'selected' : ''}>${s}</option>`).join('');
    }

    // Dashboard hanya mengatur SIAPA yang memegang mesin (satu orang aktif). Status dipilih dari ESP32.
    // JO hanya untuk OPERATOR (produksi); TEKNISI (mould) & SETUP (setter) tanpa JO.
    function formOperator(m) {
        const sekarang = m.active_history || m.pending_history;
        const jenis = sekarang?.jenis_operator || 'OPERATOR';
        const joSekarang = sekarang?.jenis_operator === 'OPERATOR' ? sekarang.no_jo : [];
        const joBisa = state.jo.filter((j) => j.status_jo !== 'FINISH' && (j.status_jo !== 'RUNNING' || joSekarang.includes(j.no_jo)));
        const keterangan = m.active_history
            ? 'Jenis sama (mis. ganti shift): sesi berlanjut dengan orang baru. Jenis beda: sesi sekarang dihentikan, orang baru menunggu dipilih statusnya di ESP.'
            : 'Orang ini menunggu dipilih statusnya di ESP (PRODUKSI / MOULD / SETTER sesuai jenis).';

        return `<h4 style="margin-top:16px">${sekarang ? 'Ganti operator' : 'Atur operator'}</h4>
            <form id="form-operator" class="form-grid">
                <div class="full hint" style="margin:0">${keterangan} Setelah disimpan, pilih <b>[ Refresh Data ]</b> di ESP.</div>
                <div><label>Jenis</label><select name="jenis_operator" id="pilih-jenis">${opsiJenis(jenis)}</select></div>
                <div><label>Shift</label><select name="shift">${opsiShift()}</select></div>
                <div><label>Nama *</label><input name="operator1" list="dl-operator" required autocomplete="off"></div>
                <div class="khusus-produksi"><label>Operator 2 (opsional)</label><input name="operator2" list="dl-operator" autocomplete="off"></div>
                <div class="full khusus-produksi"><label>JO produksi *</label>
                    ${joBisa.length ? `<div class="checks">${joBisa.map((j) => `<label><input type="checkbox" name="no_jo" value="${esc(j.no_jo)}" ${joSekarang.includes(j.no_jo) ? 'checked' : ''}>
                        <span><b>${esc(j.no_jo)}</b> · ${esc(j.produk || '-')} · qty ${esc(j.qtyjo ?? 0)} <span class="badge b-${esc(j.status_jo)}">${esc(j.status_jo)}</span></span></label>`).join('')}</div>`
                    : '<p class="muted">Belum ada JO. Tambahkan JO di bagian bawah.</p>'}
                </div>
                <div class="full"><button class="btn btn-primary" type="submit">Simpan operator</button></div>
            </form>`;
    }

    function aturKhususProduksi() {
        const produksi = $('#pilih-jenis').value === 'OPERATOR';
        document.querySelectorAll('#form-operator .khusus-produksi').forEach((el) => { el.style.display = produksi ? '' : 'none'; });
    }

    function kunciPanel(m) {
        return [m.status_tampilan, m.active_history?.id_history, m.pending_history?.id_history].join('|');
    }

    function renderRun() {
        const m = mesinAktif();
        if (!m) return;
        state.kunciPanel = kunciPanel(m);
        const h = m.active_history;
        const p = m.pending_history;
        let info;

        if (h) {
            info = `<h4>Operator mesin · ${esc(m.status_tampilan)}</h4>
                <dl class="kv">
                    <dt>Jenis</dt><dd>${esc(h.jenis_operator || '-')}</dd>
                    <dt>Nama</dt><dd>${esc(operatorDari(h) || '-')}</dd>
                    <dt>Shift</dt><dd>${esc(h.shift || '-')}</dd>
                    <dt>JO</dt><dd>${esc(h.no_jo.join(', ') || '-')}</dd>
                    <dt>Mulai</dt><dd>${jamLokal(h.mesin_start)}</dd>
                    <dt>Durasi</dt><dd class="timer" data-sejak="${Date.now() / 1000 - m.timer_sec}">${durasi(m.timer_sec)}</dd>
                </dl>
                <div class="hint">Status diubah dari ESP. STOP: pilih lagi <b>${esc(TAHAP[h.jenis_operator] || 'PRODUKSI')}</b> yang sedang [AKTIF] (kembali READY).</div>`;
        } else if (p) {
            info = `<h4>Operator mesin · READY</h4>
                <dl class="kv">
                    <dt>Jenis</dt><dd>${esc(p.jenis_operator || '-')}</dd>
                    <dt>Nama</dt><dd>${esc(operatorDari(p) || '-')}</dd>
                    <dt>Shift</dt><dd>${esc(p.shift || '-')}</dd>
                    <dt>JO</dt><dd>${esc(p.no_jo.join(', ') || '-')}</dd>
                </dl>
                <div class="hint">Di ESP: pilih <b>[ Refresh Data ]</b>, lalu <b>${esc(TAHAP[p.jenis_operator] || 'PRODUKSI')}</b> untuk mulai.</div>
                <button class="btn btn-stop" type="button" id="btn-batal-siap">Lepas operator</button>`;
        } else {
            info = `<h4>Operator mesin · ${esc(m.status_tampilan)}</h4>
                <div class="hint">Belum ada operator. ESP menampilkan "Ready belum running" dan semua status terkunci (NO OP).</div>`;
        }

        $('#sec-run').innerHTML = info + formOperator(m) + '<div class="msg" id="msg-run"></div>';
        $('#btn-batal-siap')?.addEventListener('click', batalSiapkan);
        $('#form-operator').addEventListener('submit', submitOperator);
        $('#pilih-jenis').addEventListener('change', aturKhususProduksi);
        aturKhususProduksi();
    }

    function renderJo() {
        $('#sec-jo').innerHTML = `<h4>JO mesin</h4>
            ${state.jo.length ? `<table><thead><tr><th>No JO</th><th>Produk</th><th>Qty</th><th>Prio</th><th>Status</th><th></th></tr></thead><tbody>
            ${state.jo.map((j) => `<tr>
                <td><b>${esc(j.no_jo)}</b><div class="muted">${esc(j.tgl_jo || '')}</div></td>
                <td>${esc(j.produk || '-')}</td>
                <td>${esc(j.qtyjo ?? 0)}</td>
                <td><input class="jo-prio" data-jo="${esc(j.no_jo)}" type="number" min="0" value="${esc(j.prioritas ?? '')}" style="width:56px;padding:3px 4px"></td>
                <td>${j.status_jo === 'RUNNING' ? '<span class="badge b-RUNNING">RUNNING</span>'
                    : `<select class="jo-status" data-jo="${esc(j.no_jo)}">${['OPEN', 'HOLD', 'PENDING', 'FINISH'].map((s) => `<option ${s === j.status_jo ? 'selected' : ''}>${s}</option>`).join('')}</select>`}</td>
                <td>${j.status_jo === 'RUNNING' ? '' : `<button class="btn btn-sm jo-hapus" type="button" data-jo="${esc(j.no_jo)}">Hapus</button>`}</td>
            </tr>`).join('')}</tbody></table>` : '<p class="muted">Belum ada JO untuk mesin ini.</p>'}
            <h4 style="margin-top:16px">Tambah JO</h4>
            <form id="form-jo" class="form-grid">
                <div><label>No JO *</label><input name="no_jo" required placeholder="JO1QA00101"></div>
                <div><label>Qty JO *</label><input name="qtyjo" type="number" min="0" step="any" required></div>
                <div><label>Produk</label><input name="produk"></div>
                <div><label>BOMS</label><input name="boms"></div>
                <div><label>Prioritas</label><input name="prioritas" type="number" min="0"></div>
                <div><label>Tanggal JO</label><input name="tgl_jo" type="date"></div>
                <div class="full"><button class="btn btn-primary" type="submit">Tambah JO</button></div>
            </form>
            <div class="msg" id="msg-jo"></div>`;

        $('#form-jo').addEventListener('submit', submitJo);
        document.querySelectorAll('.jo-status').forEach((el) => el.addEventListener('change', () => ubahJo(el.dataset.jo, { status_jo: el.value })));
        document.querySelectorAll('.jo-prio').forEach((el) => el.addEventListener('change', () => ubahJo(el.dataset.jo, { prioritas: el.value === '' ? null : Number(el.value) })));
        document.querySelectorAll('.jo-hapus').forEach((el) => el.addEventListener('click', () => hapusJo(el.dataset.jo)));
    }

    async function submitOperator(e) {
        e.preventDefault();
        const f = new FormData(e.target);
        const produksi = f.get('jenis_operator') === 'OPERATOR';
        const body = {
            jenis_operator: f.get('jenis_operator'),
            shift: f.get('shift'),
            operator1: f.get('operator1').trim(),
            operator2: produksi ? (f.get('operator2') || '').trim() || null : null,
            no_jo: produksi ? f.getAll('no_jo') : [],
        };
        if (produksi && !body.no_jo.length) {
            return tampilPesan($('#msg-run'), 'Pilih JO untuk produksi.');
        }
        await kirim(e.target, '#msg-run', () => api(`/mesin/${enc(state.aktif)}/ganti-operator`, 'POST', body));
    }

    async function batalSiapkan(e) {
        if (!confirm(`Batalkan operator mesin ${state.aktif}?`)) return;
        e.target.disabled = true;
        try {
            const res = await api(`/mesin/${enc(state.aktif)}/siapkan`, 'DELETE');
            await muatMesin();
            renderRun();
            tampilPesan($('#msg-run'), res.message || 'Operator dibatalkan', true);
        } catch (err) {
            tampilPesan($('#msg-run'), err.message);
            e.target.disabled = false;
        }
    }

    async function submitJo(e) {
        e.preventDefault();
        const body = Object.fromEntries([...new FormData(e.target)].filter(([, v]) => v !== ''));
        await kirim(e.target, '#msg-jo', () => api(`/mesin/${enc(state.aktif)}/jo`, 'POST', body));
    }

    async function ubahJo(noJo, body) {
        try {
            await api(`/mesin/${enc(state.aktif)}/jo/${enc(noJo)}`, 'PUT', body);
            await muatJo();
            tampilPesan($('#msg-jo'), `JO ${noJo} diperbarui`, true);
        } catch (err) {
            await muatJo();
            tampilPesan($('#msg-jo'), err.message);
        }
    }

    async function hapusJo(noJo) {
        if (!confirm(`Hapus JO ${noJo} dari mesin ${state.aktif}?`)) return;
        try {
            await api(`/mesin/${enc(state.aktif)}/jo/${enc(noJo)}`, 'DELETE');
            await muatJo();
            tampilPesan($('#msg-jo'), `JO ${noJo} dihapus`, true);
        } catch (err) {
            tampilPesan($('#msg-jo'), err.message);
        }
    }

    async function kirim(form, msgSel, aksi) {
        const tombol = form.querySelector('button[type=submit]');
        tombol.disabled = true;
        try {
            const res = await aksi();
            await muatMesin();
            await muatJo();
            tampilPesan($(msgSel), res.message || 'Berhasil', true);
        } catch (err) {
            tampilPesan($(msgSel), err.message);
            tombol.disabled = false;
        }
    }

    async function muatOperator() {
        try {
            const res = await api('/operators');
            $('#dl-operator').innerHTML = res.data.map((n) => `<option value="${esc(n)}">`).join('');
        } catch (e) {
            // saran nama operator bersifat opsional
        }
    }

    $('#tabs').addEventListener('click', (e) => {
        const b = e.target.closest('button[data-filter]');
        if (!b) return;
        state.filter = b.dataset.filter;
        document.querySelectorAll('#tabs button').forEach((x) => x.classList.toggle('on', x === b));
        render();
    });
    $('#cari').addEventListener('input', (e) => { state.cari = e.target.value; render(); });
    $('#grid').addEventListener('click', (e) => {
        const card = e.target.closest('.card');
        if (card) bukaDrawer(card.dataset.no);
    });
    $('#btn-close').addEventListener('click', () => $('#drawer').close());
    $('#drawer').addEventListener('click', (e) => { if (e.target.id === 'drawer') $('#drawer').close(); });
    $('#drawer').addEventListener('close', () => { state.aktif = null; });
    $('#btn-refresh').addEventListener('click', muatMesin);

    muatMesin();
    muatOperator();
    setInterval(muatMesin, 10000);
    setInterval(tickTimer, 1000);
</script>
</body>
</html>
