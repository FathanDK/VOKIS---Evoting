<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['pemilih_id'])) {
    header("Location: login.php");
    exit;
}

$pemilih_id   = $_SESSION['pemilih_id'];
$nama_pemilih = $_SESSION['nama_pemilih'];

$kategori_q    = $conn->query("SELECT * FROM pemilihan WHERE status = 'aktif'");
$kategori_list = [];
while ($row = $kategori_q->fetch_assoc()) $kategori_list[] = $row;

$pemilihan_id = isset($_GET['id']) ? (int)$_GET['id'] : (!empty($kategori_list) ? $kategori_list[0]['id'] : 0);

$pem_aktif_q = $conn->query("SELECT * FROM pemilihan WHERE id = $pemilihan_id");
if ($pem_aktif_q->num_rows == 0) die("Pemilihan tidak ditemukan atau belum aktif.");
$pemilihan_aktif = $pem_aktif_q->fetch_assoc();

$sudah_milih = false;
$cek = $conn->query("SELECT id FROM riwayat_suara WHERE pemilih_id=$pemilih_id AND pemilihan_id=$pemilihan_id");
if ($cek->num_rows > 0) $sudah_milih = true;

$kandidat_q    = $conn->query("SELECT * FROM kandidat WHERE pemilihan_id=$pemilihan_id ORDER BY no_urut ASC LIMIT 3");
$kandidat_data = []; $total_suara = 0;
while ($k = $kandidat_q->fetch_assoc()) { $kandidat_data[] = $k; $total_suara += $k['jumlah_suara']; }
if ($total_suara == 0) $total_suara = 1;

$jml        = count($kandidat_data);
$grid_class = $jml == 1 ? "grid grid-cols-1 max-w-sm mx-auto"
            : ($jml == 2 ? "grid grid-cols-1 md:grid-cols-2 max-w-4xl mx-auto"
            : "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3");

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    include __DIR__ . '/_vote_content.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VOKIS - Bilik Suara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        /* ══════════════════════════════════════════
           ✏️  KONFIGURASI MUDAH — UBAH DI SINI
           ══════════════════════════════════════════
           BG_IMAGE  : path ke gambar background Anda
           BG_OPACITY: gelap/terang overlay hitam (0.0 – 1.0)
           BG_BLUR   : blur pada gambar background (px)
           BG_SCALE  : zoom gambar background
           GLASS_BG  : warna panel kaca
           GLASS_BLUR: blur glassmorphism (px)
        ══════════════════════════════════════════ */
        :root {
            --bg-image   : url('assets/smk46.jpg'); /* ← GANTI PATH GAMBAR */
            --bg-opacity : 0.72;                          /* ← gelap overlay */
            --bg-blur    : 2px;                           /* ← blur gambar bg */
            --bg-scale   : 1.05;                          /* ← zoom gambar bg */

            --glass-bg   : rgba(255, 255, 255, 0.06);     /* ← warna kaca panel */
            --glass-blur : 24px;                          /* ← blur kaca */
            --glass-border: rgba(255, 255, 255, 0.11);    /* ← border kaca */

            --card-bg    : rgba(255, 255, 255, 0.05);
            --card-border: rgba(255, 255, 255, 0.09);
            --card-hover : rgba(255, 255, 255, 0.10);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            background: #000;
        }

        /* ── BACKGROUND IMAGE (hitam putih) ── */
        body::after {
            content: '';
            position: fixed; inset: 0; z-index: -2;
            background: var(--bg-image) center center / cover no-repeat;
            filter: grayscale(100%) contrast(1.1) brightness(0.85);
            transform: scale(var(--bg-scale));
            transition: transform 20s ease;
        }
        /* dark overlay di atas gambar */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: -1;
            background: rgba(0, 0, 0, var(--bg-opacity));
        }

        /* Vignette di tepi layar */
        .vignette {
            position: fixed; inset: 0; z-index: 0;
            background: radial-gradient(ellipse at center, transparent 50%, rgba(0,0,0,0.55) 100%);
            pointer-events: none;
        }

        /* noise grain */
        .grain {
            position: fixed; inset: 0; z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: .035; pointer-events: none;
        }

        /* ── FONT VOKIS ── */
        .font-vokis {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            letter-spacing: .12em;
        }
        .serif-font { font-family: 'Playfair Display', serif; }

        /* ── SCROLLBARS ── */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,.05); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.18); border-radius: 10px; }

        /* ── GLASSMORPHISM PANELS ── */
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur)) saturate(1.3);
            -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(1.3);
            border-bottom: 1px solid var(--glass-border);
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(var(--glass-blur)) saturate(1.2);
            -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(1.2);
            border: 1px solid var(--card-border);
            box-shadow: 0 8px 32px rgba(0,0,0,.3), 0 0 0 1px rgba(255,255,255,.03) inset;
            transition: background .35s, border-color .35s, box-shadow .4s;
        }

        /* ── KANDIDAT CARD ── */
        .kandidat-card {
            background: var(--card-bg);
            backdrop-filter: blur(var(--glass-blur)) saturate(1.2);
            -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(1.2);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem 1.75rem;
            display: flex; flex-direction: column; align-items: center; text-align: center;
            position: relative; overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.03) inset;
            transition: background .35s, border-color .35s, box-shadow .45s, transform .4s cubic-bezier(.16,1,.3,1);
        }
        .kandidat-card::before,
        .kandidat-card::after {
            content: '◈'; position: absolute; font-size: 11px;
            color: rgba(255,255,255,.1); transition: color .35s, text-shadow .35s; line-height: 1;
        }
        .kandidat-card::before { top: 14px; left: 16px; }
        .kandidat-card::after  { bottom: 14px; right: 16px; }
        .kandidat-card:hover {
            background: var(--card-hover);
            border-color: rgba(255,255,255,.22);
            box-shadow: 0 20px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.07) inset;
            transform: translateY(-5px);
        }
        .kandidat-card:hover::before,
        .kandidat-card:hover::after {
            color: rgba(255,255,255,.55);
            text-shadow: 0 0 10px rgba(255,255,255,.65);
        }

        .no-badge {
            position: absolute; top: 14px; right: 16px;
            font-size: 9px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
            color: rgba(255,255,255,.3); border: 1px solid rgba(255,255,255,.1);
            border-radius: 999px; padding: 2px 10px;
            transition: color .35s, border-color .35s, text-shadow .35s;
        }
        .kandidat-card:hover .no-badge {
            color: rgba(255,255,255,.85); border-color: rgba(255,255,255,.35);
            text-shadow: 0 0 10px rgba(255,255,255,.5);
        }

        /* foto */
        .foto-wrap {
            border-radius: 50%; overflow: hidden;
            border: 3px solid rgba(255,255,255,.12); margin-bottom: 1.25rem;
            box-shadow: 0 4px 20px rgba(0,0,0,.5);
            transition: border-color .35s, box-shadow .4s, transform .4s cubic-bezier(.16,1,.3,1);
            cursor: pointer;
        }
        .kandidat-card:hover .foto-wrap {
            border-color: rgba(255,255,255,.45);
            box-shadow: 0 0 0 4px rgba(255,255,255,.06), 0 10px 32px rgba(0,0,0,.6);
            transform: scale(1.04);
        }
        .foto-overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,0);
            display: flex; align-items: center; justify-content: center;
            transition: background .3s;
        }
        .foto-overlay span {
            opacity: 0; font-size: 10px; font-weight: 700;
            letter-spacing: .18em; text-transform: uppercase; text-align: center; padding: 0 8px;
            transition: opacity .3s;
        }
        .kandidat-card:hover .foto-overlay { background: rgba(0,0,0,.5); }
        .kandidat-card:hover .foto-overlay span { opacity: 1; }

        /* ── TOMBOL PILIH ── */
        .btn-pilih {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: rgba(255,255,255,.09); color: #fff;
            border: 1px solid rgba(255,255,255,.22);
            padding: 11px; border-radius: 10px;
            font-size: 11px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase;
            cursor: pointer; position: relative; overflow: hidden;
            backdrop-filter: blur(8px);
            transition: background .28s, border-color .28s, color .28s, transform .2s, box-shadow .3s;
        }
        .btn-pilih .btn-icon { font-size: 10px; color: rgba(255,255,255,.3); transition: color .28s; }
        .btn-pilih:hover:not([disabled]) {
            background: rgba(255,255,255,.95); color: #000; border-color: rgba(255,255,255,.9);
            transform: translateY(-1px); box-shadow: 0 6px 24px rgba(255,255,255,.18);
        }
        .btn-pilih:hover:not([disabled]) .btn-icon { color: #000; }
        .btn-pilih:active:not([disabled]) { transform: translateY(0); }
        .btn-ripple {
            position: absolute; border-radius: 50%; background: rgba(255,255,255,.2);
            width: 10px; height: 10px; transform: scale(0);
            animation: rippleAnim .6s linear forwards; pointer-events: none;
        }
        @keyframes rippleAnim { to { transform: scale(30); opacity: 0; } }

        /* ── TABS ── */
        .tab-link {
            padding: 14px 0; font-size: 11px; font-weight: 700;
            letter-spacing: .18em; text-transform: uppercase;
            border-bottom: 2px solid transparent; color: rgba(255,255,255,.4);
            white-space: nowrap; cursor: pointer;
            transition: color .25s, border-color .25s;
            background: none; border-top: none; border-left: none; border-right: none;
        }
        .tab-link:hover { color: rgba(255,255,255,.85); }
        .tab-link.is-active { border-bottom-color: #fff; color: #fff; }
        .tab-link .tab-dot { font-size: 9px; color: rgba(255,255,255,.18); margin-right: 5px; transition: color .25s, text-shadow .25s; }
        .tab-link.is-active .tab-dot, .tab-link:hover .tab-dot { color: rgba(255,255,255,.65); text-shadow: 0 0 8px rgba(255,255,255,.5); }

        /* ── PROGRESS ── */
        .progress-track {
            width: 100%; height: 5px; background: rgba(255,255,255,.08);
            border-radius: 999px; overflow: hidden; border: 1px solid rgba(255,255,255,.05);
        }
        .progress-fill {
            height: 100%; width: 0%; background: #fff; border-radius: 999px;
            box-shadow: 0 0 10px rgba(255,255,255,.4);
            transition: width 1.5s cubic-bezier(.16,1,.3,1);
        }

        /* ── SECTION LABEL ── */
        .section-label {
            display: flex; align-items: center; gap: 10px;
            font-size: 9px; font-weight: 700; letter-spacing: .25em; text-transform: uppercase;
            color: rgba(255,255,255,.3); margin-bottom: 6px;
        }
        .section-label::before { content: '◈'; font-size: 10px; color: rgba(255,255,255,.22); }
        .section-label::after  { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.08); }

        /* ── SCROLL REVEAL ── */
        .scroll-reveal { opacity: 0; transform: translateY(26px); transition: opacity .75s cubic-bezier(.16,1,.3,1), transform .75s cubic-bezier(.16,1,.3,1); }
        .scroll-reveal.visible { opacity: 1; transform: translateY(0); }
        .scroll-reveal[data-d="1"] { transition-delay: .08s; }
        .scroll-reveal[data-d="2"] { transition-delay: .18s; }
        .scroll-reveal[data-d="3"] { transition-delay: .28s; }

        /* ── ENTER ANIMATIONS ── */
        @keyframes fadeSlideDown { from { opacity:0; transform:translateY(-18px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeIn        { from { opacity:0; }                             to { opacity:1; } }
        .anim-nav  { animation: fadeSlideDown .7s cubic-bezier(.16,1,.3,1) both; }
        .anim-tabs { animation: fadeSlideDown .7s cubic-bezier(.16,1,.3,1) both .12s; }

        /* ── CONTENT SWAP ── */
        #main-content { transition: opacity .3s ease, transform .3s cubic-bezier(.16,1,.3,1); }
        #main-content.swapping { opacity: 0; transform: translateY(10px); pointer-events: none; }

        /* ── LOGOUT BTN ── */
        .btn-logout {
            font-size: 10px; letter-spacing: .2em; text-transform: uppercase;
            border: 1px solid rgba(255,255,255,.22); padding: 7px 16px; border-radius: 6px;
            display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,.65);
            backdrop-filter: blur(8px);
            transition: background .25s, color .25s, border-color .25s;
        }
        .btn-logout .btn-icon { font-size: 9px; color: rgba(255,255,255,.22); transition: color .25s; }
        .btn-logout:hover { background: #fff; color: #000; border-color: #fff; }
        .btn-logout:hover .btn-icon { color: #000; }

        /* ── LIVE BADGE ── */
        .live-badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 9px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
            color: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.15);
            border-radius: 999px; padding: 4px 12px;
            backdrop-filter: blur(8px);
        }
        .live-dot {
            width: 6px; height: 6px; border-radius: 50%; background: #4ade80;
            box-shadow: 0 0 8px #4ade80;
            animation: pulse-dot 1.8s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .5; transform: scale(.8); }
        }


        /* ── SWEETALERT CUSTOM ── */
        .swal2-popup {
            border: 1px solid rgba(255,255,255,.09) !important;
            box-shadow: 0 32px 80px rgba(0,0,0,.8), 0 0 0 1px rgba(255,255,255,.04) inset !important;
            border-radius: 20px !important;
        }
        .swal-vote-popup { padding: 28px 24px !important; }
        .swal-vote-confirm {
            font-size: 10px !important; font-weight: 700 !important;
            letter-spacing: .2em !important; text-transform: uppercase !important;
            padding: 12px 28px !important; border-radius: 8px !important;
            color: #000 !important; box-shadow: none !important;
        }
        .swal-vote-cancel {
            font-size: 10px !important; font-weight: 600 !important;
            letter-spacing: .12em !important; text-transform: uppercase !important;
            padding: 12px 20px !important; border-radius: 8px !important;
            color: rgba(255,255,255,.45) !important; box-shadow: none !important;
            border: 1px solid rgba(255,255,255,.12) !important;
        }
        .swal2-timer-progress-bar { background: rgba(255,255,255,.3) !important; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── VOKIS LOGO ── */
        .vokis-logo {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: .18em;
            text-transform: uppercase;
            line-height: 1;
        }
        .vokis-sub {
            font-size: 9px; font-weight: 400;
            letter-spacing: .3em; text-transform: uppercase;
            color: rgba(255,255,255,.35);
            font-family: 'Inter', sans-serif;
            margin-left: 2px;
        }
        .vokis-dot {
            font-size: 14px;
            color: rgba(255,255,255,.28);
            margin-right: 8px;
            line-height: 1;
        }
    </style>
</head>
<body class="pb-16 min-h-screen">

    <div class="vignette"></div>
    <div class="grain"></div>

    <!-- ── NAV ── -->
    <nav class="anim-nav glass-panel sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="vokis-dot">◈</span>
                <div>
                    <div class="vokis-logo">VOKIS</div>
                </div>
                <span class="vokis-sub hidden md:inline-block ml-1">SMK 46</span>
            </div>
            <div class="flex items-center gap-4 md:gap-6">
                <span class="text-xs text-white/45 hidden md:block">
                    Halo, <b class="text-white/90 font-semibold"><?= htmlspecialchars($nama_pemilih) ?></b>
                </span>
                <!-- Session Timer -->
                <div id="session-timer" class="live-badge hidden md:inline-flex">
                    <span id="timer-dot" class="live-dot" style="background:#4ade80;box-shadow:0 0 8px #4ade80;"></span>
                    <span id="timer-text">10:00</span>
                </div>
                <a href="logout.php?from=vote" class="btn-logout">
                    <span class="btn-icon">◈</span>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- ── TABS ── -->
    <div class="anim-tabs glass-panel border-t-0 mb-8 md:mb-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex space-x-7 overflow-x-auto hide-scrollbar">
                <?php foreach ($kategori_list as $kat): ?>
                    <button class="tab-link <?= ($kat['id'] == $pemilihan_id) ? 'is-active' : '' ?>"
                            data-id="<?= $kat['id'] ?>"
                            onclick="switchTab(this, <?= $kat['id'] ?>)">
                        <span class="tab-dot">◈</span><?= htmlspecialchars($kat['judul']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── MAIN CONTENT ── -->
    <div id="main-content" class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10">
        <?php include __DIR__ . '/_vote_content.php'; ?>
    </div>


    <!-- FOOTER -->
     <footer class="relative z-10 border-t border-white/08 mt-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-3">
                    <span class="vokis-dot text-white/20">◈</span>
                    <div>
                        <div class="vokis-logo text-lg text-white/70">VOKIS</div>
                        <p class="text-xs text-white/25 tracking-widest uppercase mt-0.5">SMK Negeri 46 Jakarta</p>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-xs text-white/25 uppercase tracking-widest">Sistem E-Voting Transparan &amp; Demokratis</p>
                    <p class="text-xs text-white/15 mt-1">Diselenggarakan oleh OSIS &amp; Panitia Pemilu · <?= date('Y') ?></p>
                </div>
                </div>
        </div>
    </footer>


    <script>
    function initScrollReveal() {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        document.querySelectorAll('.scroll-reveal').forEach(el => io.observe(el));
    }

    function initProgressBars() {
        setTimeout(() => {
            document.querySelectorAll('.progress-fill').forEach(b => b.style.width = b.dataset.w + '%');
        }, 500);
    }

    let chartInstance = null;
    function initChart() {
        const canvas = document.getElementById('voteChart');
        if (!canvas) return;
        if (chartInstance) { chartInstance.destroy(); chartInstance = null; }

        Chart.defaults.color = 'rgba(255,255,255,.45)';
        Chart.defaults.borderColor = 'rgba(255,255,255,.06)';

        const labels = JSON.parse(canvas.dataset.labels);
        const values = JSON.parse(canvas.dataset.values);
        const max    = Math.max(...values);

        // Warna bar dinamis berdasarkan suara (lebih banyak = lebih terang)
        const bgColors = labels.map((_, i) => {
            const alpha = max > 0 ? 0.45 + (values[i] / max) * 0.5 : 0.6;
            return `rgba(255,255,255,${alpha.toFixed(2)})`;
        });

        chartInstance = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Perolehan Suara',
                    data: values,
                    backgroundColor: bgColors,
                    borderRadius: 6,
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1200, easing: 'easeOutQuart' },
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 } },
                        grid: { color: 'rgba(255,255,255,.05)' }
                    },
                    x: {
                        ticks: { autoSkip: false, maxRotation: 0, font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function bukaModal(id) {
        document.getElementById('modal-' + id).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function tutupModal(id) {
        document.getElementById('modal-' + id).classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal-"]:not(.hidden)').forEach(m => m.classList.add('hidden'));
            document.body.style.overflow = 'auto';
        }
    });
    document.addEventListener('click', e => {
        if (e.target.matches('[id^="modal-"]')) {
            e.target.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });

    function konfirmasiVote(idForm, nama, foto, noUrut) {
        Swal.fire({
            html: `
                <div style="text-align:center;padding:8px 0 4px;">
                    <p style="font-size:9px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:16px;">Konfirmasi Pilihan Anda</p>
                    <div style="width:100px;height:100px;border-radius:50%;overflow:hidden;margin:0 auto 16px;border:3px solid rgba(255,255,255,.2);box-shadow:0 0 30px rgba(255,255,255,.08);">
                        <img src="${foto}" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <p style="font-size:9px;color:rgba(255,255,255,.35);letter-spacing:.2em;text-transform:uppercase;margin-bottom:6px;">Kandidat No. 0${noUrut}</p>
                    <p style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:#fff;line-height:1.2;margin-bottom:16px;">${nama}</p>
                    <p style="font-size:11px;color:rgba(255,255,255,.4);line-height:1.6;">Pilihan <b style="color:rgba(255,255,255,.8);">tidak dapat diubah</b> setelah dikonfirmasi.<br>Pastikan pilihan Anda sudah benar.</p>
                </div>`,
            showCancelButton: true,
            confirmButtonText: '◈ &nbsp;Ya, Pilih Kandidat Ini',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#fff',
            cancelButtonColor: 'rgba(255,255,255,.07)',
            background: '#0e0e0e',
            color: '#fff',
            width: '380px',
            customClass: {
                confirmButton: 'swal-vote-confirm',
                cancelButton: 'swal-vote-cancel',
                popup: 'swal-vote-popup',
            },
            allowOutsideClick: true,
        }).then(result => {
            if (!result.isConfirmed) return;

            // Popup proses
            Swal.fire({
                html: `<div style="text-align:center;padding:16px 0;">
                    <div style="width:48px;height:48px;border:2px solid rgba(255,255,255,.15);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px;"></div>
                    <p style="font-size:9px;font-weight:700;letter-spacing:.25em;text-transform:uppercase;color:rgba(255,255,255,.5);">Memproses suara...</p>
                </div>`,
                background: '#0e0e0e', color: '#fff',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: '280px',
            });

            fetch('proses_vote.php', { method: 'POST', body: new FormData(document.getElementById('form-vote-' + idForm)) })
                .then(r => r.json())
                .then(data => {
                    const ok = data.status === 'success';
                    if (ok) {
                        Swal.fire({
                            html: `<div style="text-align:center;padding:12px 0 8px;">
                                <div style="font-size:2.5rem;margin-bottom:12px;">◈</div>
                                <p style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;margin-bottom:10px;">Suara Tercatat!</p>
                                <p style="font-size:12px;color:rgba(255,255,255,.45);line-height:1.6;">Pilihan Anda untuk <b style="color:rgba(255,255,255,.8);">${nama}</b><br>telah berhasil disimpan.</p>
                            </div>`,
                            confirmButtonText: 'Selesai',
                            confirmButtonColor: '#fff',
                            background: '#0e0e0e', color: '#fff',
                            width: '340px',
                            timer: 3000,
                            timerProgressBar: true,
                            customClass: { confirmButton: 'swal-vote-confirm' }
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire({
                            html: `<div style="text-align:center;padding:8px 0;">
                                <p style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;margin-bottom:10px;color:#f87171;">Gagal</p>
                                <p style="font-size:12px;color:rgba(255,255,255,.45);">${data.message}</p>
                            </div>`,
                            confirmButtonText: 'Tutup',
                            confirmButtonColor: '#fff',
                            background: '#0e0e0e', color: '#fff',
                            width: '320px',
                        }).then(() => window.location.reload());
                    }
                })
                .catch(() => Swal.fire({
                    html: '<p style="color:rgba(255,255,255,.5);font-size:13px;">Terjadi kesalahan koneksi. Coba lagi.</p>',
                    confirmButtonText: 'OK', confirmButtonColor: '#fff',
                    background: '#0e0e0e', color: '#fff', width: '300px',
                }));
        });
    }

    function initRipple() {
        document.querySelectorAll('.btn-pilih:not([disabled])').forEach(btn => {
            btn.addEventListener('mousedown', function(e) {
                const rect = this.getBoundingClientRect();
                const r = document.createElement('span');
                r.className = 'btn-ripple';
                r.style.left = (e.clientX - rect.left - 5) + 'px';
                r.style.top  = (e.clientY - rect.top  - 5) + 'px';
                this.appendChild(r);
                setTimeout(() => r.remove(), 700);
            });
        });
    }

    function switchTab(btn, id) {
        document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('is-active'));
        btn.classList.add('is-active');
        const wrap = document.getElementById('main-content');
        wrap.classList.add('swapping');
        fetch('vote.php?id=' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                wrap.innerHTML = html;
                history.pushState(null, '', 'vote.php?id=' + id);
                wrap.classList.remove('swapping');
                initScrollReveal();
                initProgressBars();
                initChart();
                initRipple();
            })
            .catch(() => { window.location.href = 'vote.php?id=' + id; });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initScrollReveal();
        initProgressBars();
        initChart();
        initRipple();
    });

    // ── AUTO LOGOUT (10 menit tidak aktif) ──
    const BATAS_MENIT      = 10;
    const PERINGATAN_DETIK = 60;
    const BATAS_MS         = BATAS_MENIT * 60 * 1000;
    const PERINGATAN_MS    = BATAS_MS - (PERINGATAN_DETIK * 1000);

    let timerLogout, timerPeringatan, timerCountdown, timerNavbar;
    let swalTampil  = false;
    let sisaMs      = BATAS_MS;

    function updateNavbarTimer() {
        sisaMs -= 1000;
        if (sisaMs < 0) sisaMs = 0;
        const totalDetik = Math.ceil(sisaMs / 1000);
        const menit  = Math.floor(totalDetik / 60);
        const detik  = totalDetik % 60;
        const teks   = menit + ':' + String(detik).padStart(2, '0');

        const elText = document.getElementById('timer-text');
        const elDot  = document.getElementById('timer-dot');
        if (elText) elText.textContent = teks;

        // Warna dot berubah sesuai sisa waktu
        if (elDot) {
            if (sisaMs <= 60000) {
                // < 1 menit — merah
                elDot.style.background   = '#f87171';
                elDot.style.boxShadow    = '0 0 8px #f87171';
            } else if (sisaMs <= 180000) {
                // < 3 menit — kuning
                elDot.style.background   = '#facc15';
                elDot.style.boxShadow    = '0 0 8px #facc15';
            } else {
                // normal — hijau
                elDot.style.background   = '#4ade80';
                elDot.style.boxShadow    = '0 0 8px #4ade80';
            }
        }
    }

    function resetTimer() {
        clearTimeout(timerLogout);
        clearTimeout(timerPeringatan);
        clearInterval(timerCountdown);
        clearInterval(timerNavbar);

        if (swalTampil) {
            Swal.close();
            swalTampil = false;
        }

        // Reset sisa waktu & tampilan navbar
        sisaMs = BATAS_MS;
        const elText = document.getElementById('timer-text');
        const elDot  = document.getElementById('timer-dot');
        if (elText) elText.textContent = BATAS_MENIT + ':00';
        if (elDot)  { elDot.style.background = '#4ade80'; elDot.style.boxShadow = '0 0 8px #4ade80'; }

        // Navbar countdown setiap detik
        timerNavbar = setInterval(updateNavbarTimer, 1000);

        // Timer peringatan — muncul 60 detik sebelum logout
        timerPeringatan = setTimeout(() => {
            swalTampil = true;
            let sisa = PERINGATAN_DETIK;

            Swal.fire({
                html: `<div style="text-align:center;padding:8px 0 4px;">
                    <div style="font-size:1.8rem;margin-bottom:10px;">⏱</div>
                    <p style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;margin-bottom:10px;color:#facc15;">Sesi Hampir Berakhir</p>
                    <p style="font-size:12px;color:rgba(255,255,255,.45);line-height:1.7;">
                        Kamu tidak aktif selama 9 menit.<br>
                        Sesi akan berakhir dalam <b id="swal-countdown" style="color:#facc15;font-size:1.1rem;">${sisa}</b> detik.
                    </p>
                </div>`,
                showCancelButton: true,
                confirmButtonText: 'Saya Masih Di Sini',
                cancelButtonText: 'Logout',
                confirmButtonColor: '#fff',
                cancelButtonColor: 'rgba(255,255,255,.07)',
                background: '#0e0e0e', color: '#fff',
                width: '340px',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    timerCountdown = setInterval(() => {
                        sisa--;
                        const el = document.getElementById('swal-countdown');
                        if (el) el.textContent = sisa;
                        if (sisa <= 0) clearInterval(timerCountdown);
                    }, 1000);
                }
            }).then(result => {
                swalTampil = false;
                clearInterval(timerCountdown);
                if (result.isConfirmed) {
                    resetTimer();
                } else {
                    window.location.href = 'logout.php?from=vote';
                }
            });
        }, PERINGATAN_MS);

        // Timer logout otomatis
        timerLogout = setTimeout(() => {
            clearInterval(timerNavbar);
            clearInterval(timerCountdown);
            Swal.fire({
                html: `<div style="text-align:center;padding:8px 0 4px;">
                    <p style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;margin-bottom:10px;color:#f87171;">Sesi Berakhir</p>
                    <p style="font-size:12px;color:rgba(255,255,255,.45);line-height:1.7;">
                        Kamu tidak aktif selama ${BATAS_MENIT} menit.<br>Silakan login kembali untuk melanjutkan.
                    </p>
                </div>`,
                confirmButtonText: 'Login Kembali',
                confirmButtonColor: '#fff',
                background: '#0e0e0e', color: '#fff',
                width: '320px',
                allowOutsideClick: false, allowEscapeKey: false,
            }).then(() => {
                window.location.href = 'logout.php?from=vote';
            });
        }, BATAS_MS);
    }

    // Semua jenis aktivitas yang dianggap "aktif"
    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'touchmove', 'scroll', 'click'].forEach(event => {
        document.addEventListener(event, resetTimer, { passive: true });
    });

    resetTimer(); // mulai timer saat halaman dibuka
    </script>
</body>
</html>