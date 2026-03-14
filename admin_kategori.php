<?php
session_start();
include 'koneksi.php';
if(!isset($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }

if(isset($_POST['tambah_kategori'])) {
    $judul     = $conn->real_escape_string($_POST['judul']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    if($conn->query("INSERT INTO pemilihan (judul, deskripsi, status) VALUES ('$judul', '$deskripsi', 'aktif')"))
        $pesan_sukses = "Kategori baru berhasil ditambahkan!";
    else
        $pesan_error = "Gagal menyimpan kategori.";
}

if(isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $s  = $conn->query("SELECT status FROM pemilihan WHERE id=$id")->fetch_assoc();
    $ns = ($s['status'] == 'aktif') ? 'selesai' : 'aktif';
    $conn->query("UPDATE pemilihan SET status='$ns' WHERE id=$id");
    header("Location: admin_kategori.php"); exit;
}

if(isset($_GET['hapus'])) {
    $conn->query("DELETE FROM pemilihan WHERE id=".(int)$_GET['hapus']);
    header("Location: admin_kategori.php"); exit;
}

$page_title = 'Kategori Voting';
include 'admin_shared.php';
?>
<main class="main-content">

    <div class="anim-in mb-10">
        <p class="section-label mb-3">Manajemen</p>
        <h1 style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.2rem;letter-spacing:.04em;">Kategori Voting</h1>
        <p style="font-size:12px;color:rgba(255,255,255,.3);margin-top:6px;">Buat, kelola, dan tutup sesi pemilihan.</p>
    </div>

    <?php if(isset($pesan_sukses)): ?>
    <div class="alert-success sr">◈ &nbsp;<?= $pesan_sukses ?></div>
    <?php endif; ?>
    <?php if(isset($pesan_error)): ?>
    <div class="alert-error sr">◈ &nbsp;<?= $pesan_error ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Tambah -->
        <div class="glass-card p-6 sr h-fit">
            <p class="section-label mb-5">Buat Kategori Baru</p>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="field-label">Judul Pemilihan</label>
                    <input type="text" name="judul" required class="input-field" placeholder="Contoh: Pemilihan Ketua OSIS 2026">
                </div>
                <div>
                    <label class="field-label">Deskripsi Singkat</label>
                    <textarea name="deskripsi" required rows="3" class="textarea-field" placeholder="Deskripsi pemilihan..."></textarea>
                </div>
                <button type="submit" name="tambah_kategori" class="btn-primary" style="margin-top:4px;">Simpan Kategori</button>
            </form>
        </div>

        <!-- Tabel -->
        <div class="lg:col-span-2 glass-card p-6 sr" data-d="2">
            <p class="section-label mb-5">Daftar Kategori</p>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Deskripsi</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = $conn->query("SELECT * FROM pemilihan ORDER BY id ASC");
                        while($row = $q->fetch_assoc()):
                        ?>
                        <tr>
                            <td style="font-weight:600;color:#fff;"><?= htmlspecialchars($row['judul']) ?></td>
                            <td style="font-size:11px;color:rgba(255,255,255,.4);max-width:220px;">
                                <?= mb_strimwidth(htmlspecialchars($row['deskripsi']), 0, 80, '...') ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if($row['status'] == 'aktif'): ?>
                                    <span class="badge-aktif">Aktif</span>
                                <?php else: ?>
                                    <span class="badge-selesai">Selesai</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="admin_kategori.php?toggle=<?= $row['id'] ?>" class="btn-sm <?= $row['status']=='aktif' ? '' : 'active-btn' ?>">
                                        <?= $row['status']=='aktif' ? 'Tutup' : 'Buka' ?>
                                    </a>
                                    <a href="admin_kategori.php?hapus=<?= $row['id'] ?>"
                                       onclick="return confirm('Hapus kategori ini? Data kandidat di dalamnya ikut terhapus.')"
                                       class="btn-sm danger">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
<script>
    const io = new IntersectionObserver(e => e.forEach(x => { if(x.isIntersecting){ x.target.classList.add('visible'); io.unobserve(x.target); } }), { threshold:.08 });
    document.querySelectorAll('.sr').forEach(el => io.observe(el));
</script>
</body>
</html>