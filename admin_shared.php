<?php
/* ── SHARED STYLES & SIDEBAR — include di setiap halaman admin ── */
$current_page = basename($_SERVER['PHP_SELF']);

$nav_items = [
    ['href' => 'admin_dashboard.php', 'label' => 'Dashboard'],
    ['href' => 'admin_kategori.php',  'label' => 'Kategori Voting'],
    ['href' => 'admin_kandidat.php',  'label' => 'Kandidat'],
    ['href' => 'admin_pemilih.php',   'label' => 'Data Pemilih'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin' ?> — VOKIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root {
            --bg-opacity  : 0.82;
            --glass-blur  : 22px;
            --glass-bg    : rgba(255,255,255,.055);
            --glass-border: rgba(255,255,255,.10);
            --card-bg     : rgba(255,255,255,.05);
            --card-border : rgba(255,255,255,.09);
            --sidebar-w   : 240px;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: #fff; min-height: 100vh;
            overflow-x: hidden; background: #000;
            display: flex;
        }
        body::after {
            content: '';
            position: fixed; inset: 0; z-index: -2;
            background: url('assets/smk46.jpg') center / cover no-repeat;
            filter: grayscale(100%) contrast(1.1) brightness(0.8);
            transform: scale(1.05);
        }
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: -1;
            background: rgba(0,0,0,var(--bg-opacity));
        }
        .vignette {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(ellipse at center, transparent 45%, rgba(0,0,0,.6) 100%);
        }
        .grain {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: .035;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 40;
            background: rgba(0,0,0,.45);
            backdrop-filter: blur(28px) saturate(1.2);
            -webkit-backdrop-filter: blur(28px) saturate(1.2);
            border-right: 1px solid rgba(255,255,255,.08);
            display: flex; flex-direction: column;
        }
        .sidebar-logo {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700; font-size: 1.4rem;
            letter-spacing: .18em; text-transform: uppercase;
        }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 11px; font-weight: 600;
            letter-spacing: .12em; text-transform: uppercase;
            color: rgba(255,255,255,.35);
            text-decoration: none;
            transition: background .22s, color .22s, border-color .22s;
            border: 1px solid transparent;
        }
        .nav-link .nav-dot { font-size: 8px; color: rgba(255,255,255,.15); transition: color .22s; }
        .nav-link:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.85); border-color: rgba(255,255,255,.08); }
        .nav-link:hover .nav-dot { color: rgba(255,255,255,.5); }
        .nav-link.active { background: rgba(255,255,255,.1); color: #fff; border-color: rgba(255,255,255,.14); }
        .nav-link.active .nav-dot { color: rgba(255,255,255,.7); text-shadow: 0 0 8px rgba(255,255,255,.5); }

        /* ── MAIN ── */
        .main-content {
            margin-left: var(--sidebar-w);
            flex-grow: 1;
            min-height: 100vh;
            padding: 2.5rem 2.5rem 4rem;
            position: relative; z-index: 10;
        }

        /* ── GLASS CARDS ── */
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(var(--glass-blur)) saturate(1.2);
            -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(1.2);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,.3), 0 0 0 1px rgba(255,255,255,.03) inset;
        }

        /* ── SECTION LABEL ── */
        .section-label {
            display: flex; align-items: center; gap: 10px;
            font-size: 9px; font-weight: 700; letter-spacing: .28em; text-transform: uppercase;
            color: rgba(255,255,255,.3); margin-bottom: 6px;
        }
        .section-label::before { content: '◈'; font-size: 10px; color: rgba(255,255,255,.22); }
        .section-label::after  { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.08); }

        /* ── INPUTS ── */
        .input-field, .select-field, .textarea-field {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 8px;
            padding: 10px 13px;
            color: #fff;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color .22s, background .22s;
        }
        .input-field::placeholder, .textarea-field::placeholder { color: rgba(255,255,255,.22); }
        .input-field:focus, .select-field:focus, .textarea-field:focus {
            border-color: rgba(255,255,255,.4);
            background: rgba(255,255,255,.08);
        }
        .select-field option { background: #1a1a1a; color: #fff; }
        .textarea-field { resize: vertical; }
        label.field-label {
            display: block;
            font-size: 9px; font-weight: 700;
            letter-spacing: .22em; text-transform: uppercase;
            color: rgba(255,255,255,.3);
            margin-bottom: 6px;
        }

        /* ── BUTTONS ── */
        .btn-primary {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 8px; color: #fff;
            font-size: 10px; font-weight: 700;
            letter-spacing: .22em; text-transform: uppercase;
            padding: 12px 20px; cursor: pointer;
            transition: background .22s, color .22s, border-color .22s, box-shadow .22s;
            width: 100%; display: block; text-align: center; text-decoration: none;
        }
        .btn-primary:hover {
            background: rgba(255,255,255,.95); color: #000;
            border-color: #fff; box-shadow: 0 6px 24px rgba(255,255,255,.15);
        }
        .btn-secondary {
            background: transparent;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 8px; color: rgba(255,255,255,.5);
            font-size: 10px; font-weight: 700;
            letter-spacing: .18em; text-transform: uppercase;
            padding: 10px 18px; cursor: pointer;
            transition: background .22s, color .22s, border-color .22s;
            display: inline-block; text-decoration: none;
        }
        .btn-secondary:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.85); border-color: rgba(255,255,255,.3); }

        .btn-sm {
            font-size: 9px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase;
            padding: 4px 12px; border-radius: 999px;
            border: 1px solid rgba(255,255,255,.15);
            color: rgba(255,255,255,.55);
            text-decoration: none; display: inline-block;
            transition: background .2s, color .2s, border-color .2s;
        }
        .btn-sm:hover { background: rgba(255,255,255,.1); color: #fff; border-color: rgba(255,255,255,.3); }
        .btn-sm.danger { border-color: rgba(239,68,68,.3); color: rgba(239,68,68,.7); }
        .btn-sm.danger:hover { background: rgba(239,68,68,.15); color: rgb(239,68,68); border-color: rgba(239,68,68,.5); }
        .btn-sm.active-btn { border-color: rgba(74,222,128,.3); color: rgba(74,222,128,.8); }
        .btn-sm.active-btn:hover { background: rgba(74,222,128,.12); }

        /* ── TABLE ── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            font-size: 9px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase;
            color: rgba(255,255,255,.3); padding: 10px 14px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            text-align: left;
        }
        .data-table td {
            padding: 13px 14px;
            border-bottom: 1px solid rgba(255,255,255,.05);
            font-size: 13px; color: rgba(255,255,255,.75);
            vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr { transition: background .2s; }
        .data-table tbody tr:hover td { background: rgba(255,255,255,.03); }

        /* ── STATUS BADGE ── */
        .badge-aktif {
            font-size: 8px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
            color: rgba(74,222,128,.9); border: 1px solid rgba(74,222,128,.25);
            border-radius: 999px; padding: 3px 10px;
            background: rgba(74,222,128,.08);
        }
        .badge-selesai {
            font-size: 8px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
            color: rgba(255,255,255,.3); border: 1px solid rgba(255,255,255,.1);
            border-radius: 999px; padding: 3px 10px;
        }

        /* ── ALERTS ── */
        .alert-success {
            background: rgba(74,222,128,.08); border: 1px solid rgba(74,222,128,.2);
            border-radius: 10px; padding: 12px 16px; margin-bottom: 20px;
            font-size: 12px; color: rgba(74,222,128,.9);
        }
        .alert-error {
            background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2);
            border-radius: 10px; padding: 12px 16px; margin-bottom: 20px;
            font-size: 12px; color: rgba(239,68,68,.9);
        }

        /* ── STAT CARD ── */
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--card-border);
            border-radius: 14px; padding: 1.5rem;
            position: relative; overflow: hidden;
            transition: transform .3s cubic-bezier(.16,1,.3,1), border-color .25s;
        }
        .stat-card::before {
            content: '◈'; position: absolute; top: 14px; right: 14px;
            font-size: 11px; color: rgba(255,255,255,.1);
            transition: color .25s;
        }
        .stat-card:hover { transform: translateY(-3px); border-color: rgba(255,255,255,.18); }
        .stat-card:hover::before { color: rgba(255,255,255,.4); }

        /* ── SCROLL REVEAL ── */
        .sr { opacity: 0; transform: translateY(20px); transition: opacity .6s cubic-bezier(.16,1,.3,1), transform .6s cubic-bezier(.16,1,.3,1); }
        .sr.visible { opacity: 1; transform: translateY(0); }
        .sr[data-d="1"] { transition-delay: .06s; }
        .sr[data-d="2"] { transition-delay: .12s; }
        .sr[data-d="3"] { transition-delay: .18s; }
        .sr[data-d="4"] { transition-delay: .24s; }

        /* upload zone */
        .upload-zone {
            border: 1px dashed rgba(255,255,255,.18);
            border-radius: 10px; padding: 14px;
            background: rgba(255,255,255,.02);
            transition: border-color .22s, background .22s;
        }
        .upload-zone:hover { border-color: rgba(255,255,255,.35); background: rgba(255,255,255,.04); }
        .upload-zone input[type=file] { color: rgba(255,255,255,.5); font-size: 12px; width: 100%; }
        .upload-zone input[type=file]::file-selector-button {
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
            color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .12em;
            padding: 4px 12px; border-radius: 6px; cursor: pointer; margin-right: 10px;
            transition: background .2s;
        }
        .upload-zone input[type=file]::file-selector-button:hover { background: rgba(255,255,255,.2); }

        /* scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,.03); }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 10px; }

        @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        .anim-in { animation: fadeUp .6s cubic-bezier(.16,1,.3,1) both; }
    </style>
</head>
<body>
<div class="vignette"></div>
<div class="grain"></div>

<!-- ── SIDEBAR ── -->
<aside class="sidebar hidden md:flex flex-col">
    <!-- Logo -->
    <div style="padding:1.5rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.07);">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:13px;color:rgba(255,255,255,.25);">◈</span>
            <div>
                <div class="sidebar-logo">VOKIS</div>
                <p style="font-size:8px;letter-spacing:.28em;text-transform:uppercase;color:rgba(255,255,255,.25);margin-top:2px;">Admin Panel</p>
            </div>
        </div>
        <p style="font-size:10px;color:rgba(255,255,255,.2);margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.06);">
            ◈ &nbsp;<?= $_SESSION['admin_user'] ?? 'Admin' ?>
        </p>
    </div>

    <!-- Nav -->
    <nav style="flex-grow:1;padding:1rem .75rem;display:flex;flex-direction:column;gap:4px;">
        <?php foreach($nav_items as $item): ?>
        <a href="<?= $item['href'] ?>" class="nav-link <?= $current_page === $item['href'] ? 'active' : '' ?>">
            <span class="nav-dot">◈</span><?= $item['label'] ?>
        </a>
        <?php endforeach; ?>

        <div style="height:1px;background:rgba(255,255,255,.07);margin:10px 0;"></div>
        <a href="index.php" target="_blank" class="nav-link">
            <span class="nav-dot">◈</span>Live Results
        </a>
    </nav>

    <!-- Logout -->
    <div style="padding:.75rem;border-top:1px solid rgba(255,255,255,.07);">
        <a href="logout.php?from=admin" class="nav-link" style="justify-content:center;color:rgba(255,255,255,.3);">
            <span class="nav-dot">◈</span>Logout
        </a>
    </div>
</aside>