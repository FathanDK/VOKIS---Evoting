<?php
session_start();
include 'koneksi.php';
if(!isset($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }

$total_pemilih  = $conn->query("SELECT COUNT(id) AS total FROM pemilih")->fetch_assoc()['total'];
$kategori_aktif = $conn->query("SELECT COUNT(id) AS total FROM pemilihan WHERE status='aktif'")->fetch_assoc()['total'];
$total_suara    = $conn->query("SELECT COUNT(id) AS total FROM riwayat_suara")->fetch_assoc()['total'];
$total_kandidat = $conn->query("SELECT COUNT(id) AS total FROM kandidat")->fetch_assoc()['total'];
$persen         = $total_pemilih > 0 ? round(($total_suara / $total_pemilih) * 100, 1) : 0;

$page_title = 'Dashboard';
include 'admin_shared.php';
?>
<main class="main-content">

    <!-- Header -->
    <div class="anim-in mb-10">
        <p class="section-label mb-3">Pusat Kendali</p>
        <h1 style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.4rem;letter-spacing:.04em;line-height:1.1;">
            Selamat datang,<br><span style="color:rgba(255,255,255,.5);"><?= htmlspecialchars($_SESSION['admin_user']) ?>.</span>
        </h1>
        <p style="font-size:12px;color:rgba(255,255,255,.3);margin-top:8px;letter-spacing:.05em;">
            <?= date('l, d F Y') ?> &nbsp;·&nbsp; Auto-data realtime
        </p>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="stat-card sr" data-d="1">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:12px;">Pemilih Terdaftar</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.6rem;line-height:1;letter-spacing:.04em;"><?= number_format($total_pemilih) ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Siswa</p>
        </div>
        <div class="stat-card sr" data-d="2">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:12px;">Suara Masuk</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.6rem;line-height:1;letter-spacing:.04em;"><?= number_format($total_suara) ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Total Suara</p>
        </div>
        <div class="stat-card sr" data-d="3">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:12px;">Kategori Aktif</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.6rem;line-height:1;letter-spacing:.04em;"><?= $kategori_aktif ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Pemilihan</p>
        </div>
        <div class="stat-card sr" data-d="4">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:12px;">Total Kandidat</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.6rem;line-height:1;letter-spacing:.04em;"><?= $total_kandidat ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Kandidat</p>
        </div>
    </div>

    <!-- Partisipasi -->
    <div class="glass-card p-7 sr mb-8">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div>
                <p class="section-label mb-1">Tingkat Partisipasi</p>
                <p style="font-size:11px;color:rgba(255,255,255,.4);"><?= $total_suara ?> dari <?= $total_pemilih ?> pemilih terdaftar</p>
            </div>
            <span style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2rem;color:rgba(255,255,255,.85);"><?= $persen ?>%</span>
        </div>
        <div style="width:100%;height:8px;background:rgba(255,255,255,.07);border-radius:999px;overflow:hidden;">
            <div id="partisipasi-bar" style="height:100%;width:0%;background:linear-gradient(90deg,rgba(255,255,255,.5),#fff);border-radius:999px;box-shadow:0 0 12px rgba(255,255,255,.3);transition:width 1.6s cubic-bezier(.16,1,.3,1);"></div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="sr">
        <p class="section-label mb-5">Aksi Cepat</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="admin_kategori.php" class="glass-card p-6 block" style="text-decoration:none;transition:transform .3s,border-color .25s;">
                <span style="font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.35);">◈ &nbsp;Kelola</span>
                <p style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;font-weight:700;margin-top:8px;">Kategori Voting</p>
                <p style="font-size:11px;color:rgba(255,255,255,.3);margin-top:4px;">Buat atau tutup pemilihan</p>
            </a>
            <a href="admin_kandidat.php" class="glass-card p-6 block" style="text-decoration:none;transition:transform .3s,border-color .25s;">
                <span style="font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.35);">◈ &nbsp;Kelola</span>
                <p style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;font-weight:700;margin-top:8px;">Kandidat</p>
                <p style="font-size:11px;color:rgba(255,255,255,.3);margin-top:4px;">Tambah & edit profil kandidat</p>
            </a>
            <a href="index.php" target="_blank" class="glass-card p-6 block" style="text-decoration:none;transition:transform .3s,border-color .25s;">
                <span style="font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.35);">◈ &nbsp;Lihat</span>
                <p style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;font-weight:700;margin-top:8px;">Live Results</p>
                <p style="font-size:11px;color:rgba(255,255,255,.3);margin-top:4px;">Halaman hasil publik</p>
            </a>
        </div>
    </div>

</main>
<script>
    const io = new IntersectionObserver(e => e.forEach(x => { if(x.isIntersecting){ x.target.classList.add('visible'); io.unobserve(x.target); } }), { threshold:.08 });
    document.querySelectorAll('.sr').forEach(el => io.observe(el));
    setTimeout(() => { document.getElementById('partisipasi-bar').style.width = '<?= $persen ?>%'; }, 400);
</script>
</body>
</html>