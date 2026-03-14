<?php
session_start();
include 'koneksi.php';
if(!isset($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit; }

if(isset($_POST['tambah_pemilih'])) {
    $nisn  = $conn->real_escape_string($_POST['nisn']);
    $nama  = $conn->real_escape_string($_POST['nama']);
    $token = $conn->real_escape_string($_POST['token']);
    $cek   = $conn->query("SELECT id FROM pemilih WHERE nisn='$nisn'");
    if($cek->num_rows > 0)
        $pesan_error = "NISN sudah terdaftar di sistem!";
    elseif($conn->query("INSERT INTO pemilih (nisn, nama, token) VALUES ('$nisn','$nama','$token')"))
        $pesan_sukses = "Data siswa berhasil ditambahkan!";
    else
        $pesan_error = "Gagal menyimpan data ke database.";
}

if(isset($_GET['hapus'])) {
    $conn->query("DELETE FROM pemilih WHERE id=".(int)$_GET['hapus']);
    header("Location: admin_pemilih.php"); exit;
}

// Import CSV/Excel
function autoToken() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $t = '';
    for($i=0;$i<6;$i++) $t .= $chars[rand(0,strlen($chars)-1)];
    return $t;
}

function parseXlsx($path) {
    // Parse xlsx menggunakan ZipArchive + XML (tanpa library eksternal)
    if(!class_exists('ZipArchive')) return 'no_zip';
    $rows = [];
    $zip  = new ZipArchive();
    if($zip->open($path) !== true) return false;

    // Baca shared strings
    $strings = [];
    $sst = $zip->getFromName('xl/sharedStrings.xml');
    if($sst) {
        $xml = simplexml_load_string($sst);
        foreach($xml->si as $si) {
            // Gabungkan semua teks dalam satu cell (termasuk rich text)
            $val = '';
            foreach($si->r as $r) { if(isset($r->t)) $val .= (string)$r->t; }
            if(empty($val) && isset($si->t)) $val = (string)$si->t;
            $strings[] = $val;
        }
    }

    // Baca sheet1
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if(!$sheet) return false;

    $xml  = simplexml_load_string($sheet);
    $ns   = $xml->getNamespaces(true);
    $data = $xml->sheetData->row ?? [];

    foreach($data as $row) {
        $cols = [];
        foreach($row->c as $cell) {
            $t   = (string)($cell['t'] ?? '');
            $val = (string)($cell->v ?? '');
            if($t === 's') $val = $strings[(int)$val] ?? '';
            elseif($t === 'str' || $t === 'inlineStr') $val = (string)($cell->is->t ?? $val);
            $cols[] = $val;
        }
        $rows[] = $cols;
    }
    return $rows;
}

if(isset($_POST['import_csv']) && isset($_FILES['file_csv'])) {
    $file     = $_FILES['file_csv'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $berhasil = 0; $gagal = 0; $duplikat = 0;

    if(!in_array($ext, ['csv','txt','xlsx','xls'])) {
        $pesan_error = "Format tidak didukung. Gunakan file .csv atau .xlsx";
    } elseif($file['error'] !== UPLOAD_ERR_OK) {
        $pesan_error = "Gagal upload file.";
    } else {
        $rows = [];

        if(in_array($ext, ['csv','txt'])) {
            // Parse CSV
            $handle = fopen($file['tmp_name'], 'r');
            while(($row = fgetcsv($handle, 1000, ',')) !== false) $rows[] = $row;
            fclose($handle);
        } elseif($ext === 'xlsx') {
            // Parse XLSX
            $parsed = parseXlsx($file['tmp_name']);
            if($parsed === 'no_zip') {
                $pesan_error = "Extension ZIP belum aktif di PHP. Buka <b>php.ini</b>, cari <code>;extension=zip</code>, hapus titik koma jadi <code>extension=zip</code>, lalu restart Apache di XAMPP.";
                $rows = null;
            } elseif($parsed === false) {
                $pesan_error = "Gagal membaca file Excel. Pastikan format .xlsx valid (bukan .xls).";
                $rows = null;
            } else {
                $rows = $parsed;
            }
        } elseif($ext === 'xls') {
            $pesan_error = "Format .xls lama tidak didukung. Simpan ulang file sebagai .xlsx lalu upload lagi.";
            $rows = null;
        }

        if($rows !== null) {
            foreach($rows as $i => $row) {
                // Skip header
                if($i === 0 && isset($row[0]) && strtolower(trim($row[0])) === 'nisn') continue;
                if(count($row) < 2) continue;
                $nisn  = $conn->real_escape_string(trim($row[0]));
                $nama  = $conn->real_escape_string(trim($row[1]));
                $token = isset($row[2]) ? trim($row[2]) : '';
                if(empty($nisn) || empty($nama)) continue;
                if(empty($token)) $token = autoToken();
                $token = $conn->real_escape_string($token);
                $cek   = $conn->query("SELECT id FROM pemilih WHERE nisn='$nisn'");
                if($cek->num_rows > 0) { $duplikat++; continue; }
                if($conn->query("INSERT INTO pemilih (nisn, nama, token) VALUES ('$nisn','$nama','$token')"))
                    $berhasil++;
                else
                    $gagal++;
            }
            $pesan_import = "Import selesai: <b>$berhasil</b> berhasil, <b>$duplikat</b> duplikat dilewati" . ($gagal > 0 ? ", <b>$gagal</b> gagal" : "") . ".";
        }
    }
}

$total_row      = $conn->query("SELECT COUNT(id) as t FROM pemilih")->fetch_assoc()['t'];
$total_kategori_stat = $conn->query("SELECT COUNT(id) as t FROM pemilihan WHERE status='aktif'")->fetch_assoc()['t'];
// Selesai = sudah vote semua kategori aktif
$sudah_row      = $conn->query("SELECT COUNT(*) as t FROM (SELECT pemilih_id FROM riwayat_suara GROUP BY pemilih_id HAVING COUNT(DISTINCT pemilihan_id) >= $total_kategori_stat) x")->fetch_assoc()['t'];
$sebagian_row   = $conn->query("SELECT COUNT(*) as t FROM (SELECT pemilih_id FROM riwayat_suara GROUP BY pemilih_id HAVING COUNT(DISTINCT pemilihan_id) < $total_kategori_stat) x")->fetch_assoc()['t'];
$belum_row      = $total_row - $sudah_row - $sebagian_row;

$page_title  = 'Data Pemilih';
include 'admin_shared.php';
?>
<main class="main-content">

    <div class="anim-in mb-10">
        <p class="section-label mb-3">Manajemen</p>
        <h1 style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.2rem;letter-spacing:.04em;">Data Pemilih</h1>
        <p style="font-size:12px;color:rgba(255,255,255,.3);margin-top:6px;">Kelola daftar siswa yang memiliki hak suara.</p>
    </div>

    <?php if(isset($pesan_sukses)): ?>
    <div class="alert-success sr">◈ &nbsp;<?= $pesan_sukses ?></div>
    <?php endif; ?>
    <?php if(isset($pesan_error)): ?>
    <div class="alert-error sr">◈ &nbsp;<?= $pesan_error ?></div>
    <?php endif; ?>
    <?php if(isset($pesan_import)): ?>
    <div class="alert-success sr">◈ &nbsp;<?= $pesan_import ?></div>
    <?php endif; ?>

    <!-- Stat Mini -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="stat-card sr" data-d="1">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:10px;">Total Pemilih</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.4rem;line-height:1;"><?= $total_row ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Terdaftar</p>
        </div>
        <div class="stat-card sr" data-d="2">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(74,222,128,.4);margin-bottom:10px;">Selesai</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.4rem;line-height:1;color:rgba(74,222,128,.85);"><?= $sudah_row ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Semua kategori</p>
        </div>
        <div class="stat-card sr" data-d="3">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(250,204,21,.4);margin-bottom:10px;">Sebagian</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.4rem;line-height:1;color:rgba(250,204,21,.85);"><?= $sebagian_row ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Belum semua</p>
        </div>
        <div class="stat-card sr" data-d="4">
            <p style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:10px;">Belum Memilih</p>
            <p style="font-family:'Cormorant Garamond',serif;font-weight:700;font-size:2.4rem;line-height:1;color:rgba(255,255,255,.5);"><?= $belum_row ?></p>
            <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:4px;">Sama sekali</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kolom Kiri: Form Tambah + Import -->
        <div class="flex flex-col gap-6">
        <div class="glass-card p-6 sr h-fit">
            <p class="section-label mb-5">Tambah Pemilih Baru</p>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="field-label">NISN</label>
                    <input type="text" name="nisn" required class="input-field" placeholder="Contoh: 0041234567">
                </div>
                <div>
                    <label class="field-label">Nama Lengkap Siswa</label>
                    <input type="text" name="nama" required class="input-field" placeholder="Contoh: Budi Santoso">
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label class="field-label" style="margin-bottom:0;">Token Login</label>
                        <button type="button" onclick="generateToken()"
                            style="font-size:9px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;
                                   color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.12);
                                   border-radius:999px;padding:3px 10px;background:transparent;cursor:pointer;
                                   transition:color .2s,border-color .2s;"
                            onmouseover="this.style.color='rgba(255,255,255,.85)';this.style.borderColor='rgba(255,255,255,.3)'"
                            onmouseout="this.style.color='rgba(255,255,255,.4)';this.style.borderColor='rgba(255,255,255,.12)'">
                            ◈ Acak
                        </button>
                    </div>
                    <input type="text" name="token" id="inputToken" required class="input-field"
                           placeholder="Token otomatis" style="font-family:monospace;font-weight:700;letter-spacing:.12em;">
                    <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:5px;">Token digunakan siswa sebagai kata sandi masuk.</p>
                </div>
                <button type="submit" name="tambah_pemilih" class="btn-primary" style="margin-top:4px;">Simpan Data Pemilih</button>
            </form>
        </div>

        <!-- Import CSV -->
        <div class="glass-card p-6 sr h-fit">
            <p class="section-label mb-5">Import Pemilih</p>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="field-label">Pilih File CSV atau Excel</label>
                    <div class="upload-zone">
                        <input type="file" name="file_csv" accept=".csv,.xlsx,.txt" required>
                    </div>
                    <p style="font-size:10px;color:rgba(255,255,255,.25);margin-top:6px;line-height:1.6;">
                        Format kolom: <span style="font-family:monospace;color:rgba(255,255,255,.45);">nisn, nama, token</span><br>
                        Mendukung <b style="color:rgba(255,255,255,.4);">.csv</b> dan <b style="color:rgba(255,255,255,.4);">.xlsx</b><br>
                        Token boleh dikosongkan — digenerate otomatis.<br>
                        NISN duplikat akan dilewati otomatis.
                    </p>
                </div>
                <button type="submit" name="import_csv" class="btn-primary">Import Data</button>
            </form>

        </div>

        </div><!-- /kolom kiri -->

        <!-- Tabel -->
        <div class="lg:col-span-2 glass-card p-6 sr" data-d="2">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <p class="section-label" style="margin-bottom:0;flex:1;">Daftar Siswa Terdaftar</p>
                <span style="font-size:9px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;
                              color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.1);
                              border-radius:999px;padding:3px 12px;flex-shrink:0;">
                    <?= $total_row ?> Siswa
                </span>
            </div>

            <!-- Search -->
            <div style="margin-bottom:16px;">
                <input type="text" id="searchInput" oninput="filterTable()" class="input-field"
                       placeholder="Cari nama atau NISN..." style="font-size:12px;">
            </div>

            <div style="overflow-x:auto;">
                <table class="data-table" id="pemilihTable">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama Lengkap</th>
                            <th>Token</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_kategori = $conn->query("SELECT COUNT(id) as t FROM pemilihan WHERE status='aktif'")->fetch_assoc()['t'];
                        $q = $conn->query("SELECT p.*, (SELECT COUNT(DISTINCT pemilihan_id) FROM riwayat_suara rs WHERE rs.pemilih_id=p.id) as sudah_milih FROM pemilih p ORDER BY p.nama ASC");
                        while($row = $q->fetch_assoc()):
                            $voted   = (int)$row['sudah_milih'];
                            $selesai = ($total_kategori > 0 && $voted >= $total_kategori);
                        ?>
                        <tr>
                            <td style="font-family:monospace;font-size:12px;color:rgba(255,255,255,.45);">
                                <?= htmlspecialchars($row['nisn']) ?>
                            </td>
                            <td style="font-weight:600;color:#fff;"><?= htmlspecialchars($row['nama']) ?></td>
                            <td style="font-family:monospace;font-weight:700;font-size:13px;letter-spacing:.1em;color:rgba(255,255,255,.75);">
                                <?= htmlspecialchars($row['token']) ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if($selesai): ?>
                                <span class="badge-aktif">Selesai &nbsp;<span style="opacity:.65;font-weight:400;"><?= $voted ?>/<?= $total_kategori ?></span></span>
                                <?php elseif($voted > 0): ?>
                                <span style="font-size:8px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;
                                             color:rgba(250,204,21,.9);border:1px solid rgba(250,204,21,.25);
                                             border-radius:999px;padding:3px 10px;background:rgba(250,204,21,.08);white-space:nowrap;">
                                    Sebagian &nbsp;<span style="opacity:.7;font-weight:400;"><?= $voted ?>/<?= $total_kategori ?></span>
                                </span>
                                <?php else: ?>
                                <span class="badge-selesai">Belum &nbsp;<span style="opacity:.5;font-weight:400;">0/<?= $total_kategori ?></span></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="admin_pemilih.php?hapus=<?= $row['id'] ?>"
                                   onclick="return confirm('Hapus data siswa ini?')"
                                   class="btn-sm danger">Hapus</a>
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
    // Scroll reveal
    const io = new IntersectionObserver(e => e.forEach(x => { if(x.isIntersecting){ x.target.classList.add('visible'); io.unobserve(x.target); } }), { threshold:.08 });
    document.querySelectorAll('.sr').forEach(el => io.observe(el));

    // Generate token
    function generateToken() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let r = '';
        for(let i = 0; i < 6; i++) r += chars[Math.floor(Math.random() * chars.length)];
        document.getElementById('inputToken').value = r;
    }
    window.addEventListener('DOMContentLoaded', generateToken);

    // Search filter
    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#pemilihTable tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    }
</script>
</body>
</html>