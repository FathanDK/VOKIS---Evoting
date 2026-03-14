<?php
session_start();
include 'koneksi.php';
if(!isset($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }

if(isset($_POST['simpan_kandidat'])) {
    $kandidat_id  = isset($_POST['kandidat_id']) ? (int)$_POST['kandidat_id'] : 0;
    $pemilihan_id = $_POST['pemilihan_id'];
    $no_urut      = $_POST['no_urut'];
    $nama         = $conn->real_escape_string($_POST['nama_kandidat']);
    $kelas        = $conn->real_escape_string($_POST['kelas']);
    $jurusan      = $conn->real_escape_string($_POST['jurusan']);
    $visi         = $conn->real_escape_string($_POST['visi']);
    $misi         = $conn->real_escape_string($_POST['misi']);
    $program      = $conn->real_escape_string($_POST['program_kerja']);

    $query_foto = $query_poster = "";
    if(!empty($_FILES['foto']['name'])) {
        $fn = 'profil_'.date('dmYHis').$_FILES['foto']['name'];
        move_uploaded_file($_FILES['foto']['tmp_name'], "assets/".$fn);
        $query_foto = $fn;
    }
    if(!empty($_FILES['poster']['name'])) {
        $fn = 'poster_'.date('dmYHis').$_FILES['poster']['name'];
        move_uploaded_file($_FILES['poster']['tmp_name'], "assets/".$fn);
        $query_poster = $fn;
    }

    if($kandidat_id > 0) {
        $parts = ["pemilihan_id='$pemilihan_id'","no_urut='$no_urut'","nama_kandidat='$nama'","kelas='$kelas'","jurusan='$jurusan'","visi='$visi'","misi='$misi'","program_kerja='$program'"];
        if($query_foto)   $parts[] = "foto='$query_foto'";
        if($query_poster) $parts[] = "poster='$query_poster'";
        $sql = "UPDATE kandidat SET ".implode(",",$parts)." WHERE id=$kandidat_id";
        $conn->query($sql) ? $pesan_sukses = "Kandidat berhasil diperbarui!" : $pesan_error = $conn->error;
    } else {
        $sql = "INSERT INTO kandidat (pemilihan_id,no_urut,nama_kandidat,kelas,jurusan,visi,misi,program_kerja,foto,poster) VALUES ('$pemilihan_id','$no_urut','$nama','$kelas','$jurusan','$visi','$misi','$program','$query_foto','$query_poster')";
        $conn->query($sql) ? $pesan_sukses = "Kandidat baru berhasil ditambahkan!" : $pesan_error = $conn->error;
    }
}

if(isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $f  = $conn->query("SELECT foto,poster FROM kandidat WHERE id=$id")->fetch_assoc();
    if($f['foto']   && file_exists("assets/".$f['foto']))   unlink("assets/".$f['foto']);
    if($f['poster'] && file_exists("assets/".$f['poster'])) unlink("assets/".$f['poster']);
    $conn->query("DELETE FROM kandidat WHERE id=$id");
    header("Location: admin_kandidat.php"); exit;
}

$edit_mode = false; $data_edit = [];
if(isset($_GET['edit'])) {
    $edit_mode = true;
    $data_edit = $conn->query("SELECT * FROM kandidat WHERE id=".(int)$_GET['edit'])->fetch_assoc();
}

$pemilihan_q = $conn->query("SELECT * FROM pemilihan ORDER BY id ASC");
$page_title  = 'Kelola Kandidat';
include 'admin_shared.php';
?>
<main class="main-content">

    <div class="anim-in mb-10">
        <p class="section-label mb-3">Manajemen</p>
        <h1 style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.2rem;letter-spacing:.04em;">
            <?= $edit_mode ? 'Edit Kandidat' : 'Kelola Kandidat' ?>
        </h1>
        <p style="font-size:12px;color:rgba(255,255,255,.3);margin-top:6px;">Tambah, edit, dan hapus profil kandidat.</p>
    </div>

    <?php if(isset($pesan_sukses)): ?>
    <div class="alert-success sr">◈ &nbsp;<?= $pesan_sukses ?></div>
    <?php endif; ?>
    <?php if(isset($pesan_error)): ?>
    <div class="alert-error sr">◈ &nbsp;<?= $pesan_error ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form -->
        <div class="glass-card p-6 sr h-fit" style="max-height:calc(100vh - 140px);overflow-y:auto;">
            <p class="section-label mb-5"><?= $edit_mode ? 'Edit Data Kandidat' : 'Tambah Kandidat Baru' ?></p>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php if($edit_mode): ?>
                <input type="hidden" name="kandidat_id" value="<?= $data_edit['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="field-label">Kategori Pemilihan</label>
                    <select name="pemilihan_id" required class="select-field">
                        <option value="">— Pilih Kategori —</option>
                        <?php while($p = $pemilihan_q->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>" <?= ($edit_mode && $data_edit['pemilihan_id']==$p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['judul']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 2fr;gap:10px;">
                    <div>
                        <label class="field-label">No. Urut</label>
                        <input type="number" name="no_urut" required class="input-field" placeholder="1" value="<?= $edit_mode ? $data_edit['no_urut'] : '' ?>">
                    </div>
                    <div>
                        <label class="field-label">Nama Kandidat</label>
                        <input type="text" name="nama_kandidat" required class="input-field" placeholder="Nama lengkap / paslon" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_kandidat']) : '' ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label class="field-label">Kelas</label>
                        <input type="text" name="kelas" required class="input-field" placeholder="XI" value="<?= $edit_mode ? htmlspecialchars($data_edit['kelas']) : '' ?>">
                    </div>
                    <div>
                        <label class="field-label">Jurusan</label>
                        <input type="text" name="jurusan" required class="input-field" placeholder="RPL" value="<?= $edit_mode ? htmlspecialchars($data_edit['jurusan']) : '' ?>">
                    </div>
                </div>

                <div>
                    <label class="field-label">Visi</label>
                    <textarea name="visi" required rows="2" class="textarea-field" placeholder="Visi kandidat..."><?= $edit_mode ? htmlspecialchars($data_edit['visi']) : '' ?></textarea>
                </div>
                <div>
                    <label class="field-label">Misi</label>
                    <textarea name="misi" required rows="3" class="textarea-field" placeholder="Misi kandidat..."><?= $edit_mode ? htmlspecialchars($data_edit['misi']) : '' ?></textarea>
                </div>
                <div>
                    <label class="field-label">Program Kerja</label>
                    <textarea name="program_kerja" required rows="3" class="textarea-field" placeholder="Program kerja unggulan..."><?= $edit_mode ? htmlspecialchars($data_edit['program_kerja']) : '' ?></textarea>
                </div>

                <div class="upload-zone">
                    <label class="field-label" style="margin-bottom:8px;">Foto Wajah (Profil)</label>
                    <input type="file" name="foto" accept="image/*" <?= $edit_mode ? '' : 'required' ?>>
                    <?php if($edit_mode): ?>
                    <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:6px;">Kosongkan jika tidak ingin mengubah</p>
                    <?php endif; ?>
                </div>

                <div class="upload-zone">
                    <label class="field-label" style="margin-bottom:8px;">Gambar Poster</label>
                    <input type="file" name="poster" accept="image/*" <?= $edit_mode ? '' : 'required' ?>>
                    <?php if($edit_mode): ?>
                    <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:6px;">Kosongkan jika tidak ingin mengubah</p>
                    <?php endif; ?>
                </div>

                <button type="submit" name="simpan_kandidat" class="btn-primary">
                    <?= $edit_mode ? 'Simpan Perubahan' : 'Simpan Kandidat Baru' ?>
                </button>
                <?php if($edit_mode): ?>
                <a href="admin_kandidat.php" class="btn-secondary" style="display:block;text-align:center;margin-top:8px;">Batal Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Kandidat -->
        <div class="lg:col-span-2 glass-card p-6 sr" data-d="2">
            <p class="section-label mb-5">Daftar Kandidat Terdaftar</p>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Kategori</th>
                            <th>Kandidat</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $list = $conn->query("SELECT k.*,p.judul FROM kandidat k JOIN pemilihan p ON k.pemilihan_id=p.id ORDER BY p.id ASC, k.no_urut ASC");
                        while($row = $list->fetch_assoc()):
                        ?>
                        <tr>
                            <td>
                                <div style="width:44px;height:44px;border-radius:50%;overflow:hidden;border:1px solid rgba(255,255,255,.1);flex-shrink:0;">
                                    <img src="assets/<?= htmlspecialchars($row['foto']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                            </td>
                            <td style="font-size:11px;color:rgba(255,255,255,.4);">
                                <?= htmlspecialchars($row['judul']) ?>
                            </td>
                            <td>
                                <span style="font-size:8px;font-weight:700;letter-spacing:.18em;color:rgba(255,255,255,.3);text-transform:uppercase;">No. <?= $row['no_urut'] ?></span><br>
                                <span style="font-weight:600;color:#fff;"><?= htmlspecialchars($row['nama_kandidat']) ?></span><br>
                                <span style="font-size:10px;color:rgba(255,255,255,.35);"><?= htmlspecialchars($row['kelas']) ?> · <?= htmlspecialchars($row['jurusan']) ?></span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="admin_kandidat.php?edit=<?= $row['id'] ?>" class="btn-sm">Edit</a>
                                    <a href="admin_kandidat.php?hapus=<?= $row['id'] ?>"
                                       onclick="return confirm('Hapus kandidat ini beserta fotonya?')"
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