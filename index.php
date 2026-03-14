<?php
include 'koneksi.php';
// Ambil semua pemilihan yang sedang aktif
$pemilihan_query = $conn->query("SELECT * FROM pemilihan WHERE status = 'aktif'");

// Statistik global
$total_pemilih = $conn->query("SELECT COUNT(id) AS total FROM pemilih")->fetch_assoc()['total'];
$total_suara_global = $conn->query("SELECT COUNT(id) AS total FROM riwayat_suara")->fetch_assoc()['total'];
$persen_partisipasi = $total_pemilih > 0 ? round(($total_suara_global / $total_pemilih) * 100, 1) : 0;

// Ambil semua kandidat untuk galeri poster
$poster_query = $conn->query("SELECT k.nama_kandidat, k.foto, k.jumlah_suara, p.judul as kategori FROM kandidat k JOIN pemilihan p ON k.pemilihan_id = p.id WHERE p.status='aktif' ORDER BY k.jumlah_suara DESC");
$semua_kandidat = [];
while ($row = $poster_query->fetch_assoc()) $semua_kandidat[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="15">
    <title>VOKIS — Live Election Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Inter:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --bg-image   : url('assets/smk46.jpg');
            --bg-opacity : 0.78;
            --bg-blur    : 2px;
            --bg-scale   : 1.05;
            --glass-bg   : rgba(255, 255, 255, 0.055);
            --glass-blur : 26px;
            --glass-border: rgba(255, 255, 255, 0.10);
            --card-bg    : rgba(255, 255, 255, 0.05);
            --card-border: rgba(255, 255, 255, 0.09);
            --card-hover : rgba(255, 255, 255, 0.09);
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            background: #000;
        }

        body::after {
            content: '';
            position: fixed; inset: 0; z-index: -2;
            background: var(--bg-image) center center / cover no-repeat;
            filter: grayscale(100%) contrast(1.1) brightness(0.85);
            transform: scale(var(--bg-scale));
        }
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: -1;
            background: rgba(0, 0, 0, var(--bg-opacity));
        }

        .vignette {
            position: fixed; inset: 0; z-index: 0;
            background: radial-gradient(ellipse at center, transparent 45%, rgba(0,0,0,0.65) 100%);
            pointer-events: none;
        }
        .grain {
            position: fixed; inset: 0; z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: .038; pointer-events: none;
        }

        .font-vokis {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            letter-spacing: .12em;
        }
        .serif-font { font-family: 'Playfair Display', serif; }

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
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,.3), 0 0 0 1px rgba(255,255,255,.03) inset;
            transition: background .35s, border-color .35s, box-shadow .4s;
        }
        .glass-card:hover {
            background: var(--card-hover);
            border-color: rgba(255,255,255,.18);
            box-shadow: 0 16px 48px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.05) inset;
        }

        /* ── STAT CARDS ── */
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.5rem;
            position: relative; overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.3);
            transition: transform .35s cubic-bezier(.16,1,.3,1), border-color .3s, box-shadow .35s;
        }
        .stat-card::before {
            content: '◈';
            position: absolute; top: 14px; right: 16px;
            font-size: 11px; color: rgba(255,255,255,.1);
            transition: color .3s, text-shadow .3s;
        }
        .stat-card:hover { transform: translateY(-4px); border-color: rgba(255,255,255,.2); box-shadow: 0 16px 40px rgba(0,0,0,.5); }
        .stat-card:hover::before { color: rgba(255,255,255,.45); text-shadow: 0 0 10px rgba(255,255,255,.5); }

        /* ── SECTION LABEL ── */
        .section-label {
            display: flex; align-items: center; gap: 10px;
            font-size: 9px; font-weight: 700; letter-spacing: .28em; text-transform: uppercase;
            color: rgba(255,255,255,.3); margin-bottom: 6px;
        }
        .section-label::before { content: '◈'; font-size: 10px; color: rgba(255,255,255,.22); }
        .section-label::after  { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.08); }

        /* ── LIVE BADGE ── */
        .live-badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 9px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase;
            color: rgba(255,255,255,.65); border: 1px solid rgba(255,255,255,.15);
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
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        /* ── PROGRESS ── */
        .progress-track {
            width: 100%; height: 5px; background: rgba(255,255,255,.08);
            border-radius: 999px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; width: 0%; background: linear-gradient(90deg, rgba(255,255,255,.6), #fff);
            border-radius: 999px; box-shadow: 0 0 10px rgba(255,255,255,.35);
            transition: width 1.8s cubic-bezier(.16,1,.3,1);
        }

        /* ── POSTER GALLERY ── */
        .poster-card {
            position: relative; border-radius: 14px; overflow: hidden;
            border: 1px solid rgba(255,255,255,.09);
            box-shadow: 0 8px 30px rgba(0,0,0,.45);
            aspect-ratio: 3/4;
            transition: transform .4s cubic-bezier(.16,1,.3,1), border-color .3s, box-shadow .4s;
            cursor: pointer;
        }
        .poster-card img {
            width: 100%; height: 100%; object-fit: cover;
            filter: grayscale(30%) contrast(1.05);
            transition: transform .6s cubic-bezier(.16,1,.3,1), filter .4s;
        }
        .poster-card:hover { transform: translateY(-6px) scale(1.01); border-color: rgba(255,255,255,.25); box-shadow: 0 20px 50px rgba(0,0,0,.65); }
        .poster-card:hover img { transform: scale(1.06); filter: grayscale(0%) contrast(1.08); }
        .poster-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.2) 50%, transparent 100%);
            display: flex; flex-direction: column; justify-content: flex-end;
            padding: 1.2rem;
        }
        .poster-votes {
            position: absolute; top: 12px; right: 12px;
            font-size: 9px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
            color: rgba(255,255,255,.85); border: 1px solid rgba(255,255,255,.2);
            border-radius: 999px; padding: 3px 10px;
            backdrop-filter: blur(8px); background: rgba(0,0,0,.3);
        }

        /* ── CHART CONTAINER ── */
        .chart-wrap { position: relative; height: 240px; }

        /* ── DIVIDER ── */
        .ornament-divider {
            display: flex; align-items: center; gap: 16px;
            margin: 4rem 0 3rem;
        }
        .ornament-divider::before,
        .ornament-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.08); }
        .ornament-divider span { font-size: 12px; color: rgba(255,255,255,.2); letter-spacing: .3em; }

        /* ── SCROLL REVEAL ── */
        .scroll-reveal { opacity: 0; transform: translateY(28px); transition: opacity .75s cubic-bezier(.16,1,.3,1), transform .75s cubic-bezier(.16,1,.3,1); }
        .scroll-reveal.visible { opacity: 1; transform: translateY(0); }
        .scroll-reveal[data-d="1"] { transition-delay: .07s; }
        .scroll-reveal[data-d="2"] { transition-delay: .15s; }
        .scroll-reveal[data-d="3"] { transition-delay: .23s; }
        .scroll-reveal[data-d="4"] { transition-delay: .31s; }

        @keyframes fadeSlideDown { from { opacity:0; transform:translateY(-16px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        .anim-nav { animation: fadeSlideDown .7s cubic-bezier(.16,1,.3,1) both; }

        .vokis-logo {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700; font-size: 1.5rem;
            letter-spacing: .18em; text-transform: uppercase; line-height: 1;
        }
        .vokis-sub {
            font-size: 9px; font-weight: 400; letter-spacing: .3em; text-transform: uppercase;
            color: rgba(255,255,255,.35); font-family: 'Inter', sans-serif; margin-left: 2px;
        }
        .vokis-dot { font-size: 14px; color: rgba(255,255,255,.28); margin-right: 8px; line-height: 1; }

        /* custom scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,.03); }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 10px; }

        /* ── RANKING ROWS ── */
        .rank-row {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,.05);
            transition: background .2s;
        }
        .rank-row:last-child { border-bottom: none; }
        .rank-number {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700; font-size: 1.2rem;
            color: rgba(255,255,255,.2); width: 24px; text-align: center; flex-shrink: 0;
        }
        .rank-row:first-child .rank-number { color: rgba(255,255,255,.85); }
    </style>
</head>
<body class="pb-20 min-h-screen">

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
            <div class="flex items-center gap-3">
                <div class="live-badge">
                    <span class="live-dot"></span>
                    Live
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10 pt-12">

        <!-- ── HEADER ── -->
        <div class="text-center mb-14 scroll-reveal">
            <h1 class="font-vokis text-5xl md:text-6xl lg:text-7xl tracking-widest mb-4">
                Live Results
            </h1>
            <p class="text-white/40 text-sm tracking-widest uppercase">Sistem E-Voting Demokratis & Transparan</p>
        </div>


        <!-- ── SECTION LABEL CHARTS ── -->
        <div class="scroll-reveal mb-8">
            <p class="section-label">Perolehan Suara Per Kategori</p>
        </div>

        <!-- ── CHART GRID ── -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
            <?php
            // Reset query
            $pemilihan_query = $conn->query("SELECT * FROM pemilihan WHERE status = 'aktif'");
            $chart_index = 0;
            while($pem = $pemilihan_query->fetch_assoc()):
                $pem_id = $pem['id'];
                $kandidat_q = $conn->query("SELECT nama_kandidat, jumlah_suara FROM kandidat WHERE pemilihan_id = $pem_id ORDER BY no_urut ASC");
                $labels = []; $data = []; $total_suara = 0;
                $kandidat_rows = [];
                while($k = $kandidat_q->fetch_assoc()) {
                    $labels[] = $k['nama_kandidat'];
                    $data[] = (int)$k['jumlah_suara'];
                    $total_suara += $k['jumlah_suara'];
                    $kandidat_rows[] = $k;
                }
                $chart_index++;
            ?>
            <div class="glass-card p-7 scroll-reveal" data-d="<?= $chart_index ?>">
                <!-- Card Header -->
                <div class="flex items-start justify-between mb-5">
                    <div>
                        <p class="text-xs text-white/35 uppercase tracking-widest mb-1"><?= $chart_index == 1 ? 'Pemilihan Utama' : 'Pemilihan' ?></p>
                        <h2 class="serif-font text-xl font-bold tracking-tight"><?= htmlspecialchars($pem['judul']) ?></h2>
                    </div>
                    <span class="text-xs text-white/35 border border-white/10 rounded-full px-3 py-1 flex-shrink-0"><?= $total_suara ?> suara</span>
                </div>

                <!-- Chart -->
                <div class="chart-wrap mb-6">
                    <canvas id="chart-<?= $pem_id ?>"
                            data-labels='<?= json_encode($labels) ?>'
                            data-values='<?= json_encode($data) ?>'></canvas>
                </div>

                <!-- Ranking Rows -->
                <?php
                // Sort by votes descending for ranking
                $sorted = $kandidat_rows;
                usort($sorted, fn($a, $b) => $b['jumlah_suara'] - $a['jumlah_suara']);
                foreach($sorted as $idx => $k):
                    $pct = $total_suara > 0 ? round(($k['jumlah_suara'] / $total_suara) * 100) : 0;
                ?>
                <div class="rank-row">
                    <span class="rank-number"><?= $idx + 1 ?></span>
                    <div class="flex-grow min-w-0">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-semibold truncate"><?= htmlspecialchars($k['nama_kandidat']) ?></span>
                            <span class="text-xs text-white/50 ml-2 flex-shrink-0"><?= $pct ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" data-w="<?= $pct ?>"></div>
                        </div>
                    </div>
                    <span class="text-sm font-bold text-white/80 flex-shrink-0 w-8 text-right"><?= $k['jumlah_suara'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if (!empty($semua_kandidat)): ?>
        <!-- ── ORNAMENT DIVIDER ── -->
        <div class="ornament-divider scroll-reveal">
            <span>◈ ◈ ◈</span>
        </div>

        <!-- ── POSTER GALLERY ── -->
        <div class="scroll-reveal mb-6">
            <p class="section-label">Galeri Kandidat</p>
            <h2 class="serif-font text-2xl font-bold tracking-tight text-white/90">Para Kandidat</h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-<?= min(count($semua_kandidat), 5) ?> gap-4 mb-6">
            <?php foreach($semua_kandidat as $idx => $k): ?>
            <div class="poster-card scroll-reveal" data-d="<?= ($idx % 4) + 1 ?>">
                <?php if(!empty($k['foto'])): ?>
                <img src="assets/<?= htmlspecialchars($k['foto']) ?>" alt="<?= htmlspecialchars($k['nama_kandidat']) ?>">
                <?php else: ?>
                <div class="w-full h-full bg-white/5 flex items-center justify-center">
                    <span class="text-white/20 text-5xl">◈</span>
                </div>
                <?php endif; ?>
                <div class="poster-overlay">
                    <p class="text-xs text-white/45 uppercase tracking-widest mb-1"><?= htmlspecialchars($k['kategori']) ?></p>
                    <p class="font-semibold text-sm leading-tight"><?= htmlspecialchars($k['nama_kandidat']) ?></p>
                </div>
                <span class="poster-votes"><?= $k['jumlah_suara'] ?> suara</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── ORNAMENT DIVIDER ── -->
        <div class="ornament-divider scroll-reveal">
            <span>◈</span>
        </div>

    </div>

    <!-- ── FOOTER ── -->
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
                <div class="flex items-center gap-2 text-xs text-white/25 uppercase tracking-widest">
                    <span class="live-dot" style="width:5px;height:5px;"></span>
                    Auto-refresh aktif
                </div>
            </div>
        </div>
    </footer>

    <script>
    // ── INIT CHARTS ──
    function initCharts() {
        Chart.defaults.color = 'rgba(255,255,255,.45)';
        Chart.defaults.borderColor = 'rgba(255,255,255,.06)';

        document.querySelectorAll('canvas[data-labels]').forEach(canvas => {
            const labels = JSON.parse(canvas.dataset.labels);
            const values = JSON.parse(canvas.dataset.values);
            const max    = Math.max(...values);

            const bgColors = labels.map((_, i) => {
                const alpha = max > 0 ? 0.5 + (values[i] / max) * 0.45 : 0.65;
                return `rgba(255,255,255,${alpha.toFixed(2)})`;
            });

            new Chart(canvas.getContext('2d'), {
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
        });
    }

    // ── SCROLL REVEAL ──
    function initScrollReveal() {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        document.querySelectorAll('.scroll-reveal').forEach(el => io.observe(el));
    }

    // ── PROGRESS BARS ──
    function initProgressBars() {
        setTimeout(() => {
            document.querySelectorAll('.progress-fill').forEach(b => {
                b.style.width = (b.dataset.w || 0) + '%';
            });
        }, 400);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initScrollReveal();
        initProgressBars();
        initCharts();
    });
    </script>
</body>
</html>