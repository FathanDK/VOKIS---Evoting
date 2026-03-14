<?php
/*
 * _vote_content.php
 * Fragment konten — dipanggil oleh vote.php (full render) dan via AJAX.
 * PENTING: Modal dirender TERPISAH di luar card agar position:fixed bekerja benar.
 */

$labels_json = json_encode(array_map(fn($k) => "No.".$k['no_urut']." - ".$k['nama_kandidat'], $kandidat_data));
$data_json   = json_encode(array_map(fn($k) => $k['jumlah_suara'], $kandidat_data));
?>

<?php if ($sudah_milih): ?>
<div class="scroll-reveal glass-card mb-8 px-5 py-4 flex items-center gap-4 border-l-4 border-l-emerald-500 bg-emerald-900/10 rounded-r-xl">
    <span style="color:#34d399;font-size:16px;">◈</span>
    <div>
        <h3 class="font-bold tracking-widest uppercase text-xs text-emerald-400">Status: Selesai</h3>
        <p class="text-[11px] text-white/60 mt-0.5">Anda sudah menggunakan hak suara di pemilihan ini.</p>
    </div>
</div>
<?php endif; ?>

<!-- HEADER PEMILIHAN -->
<div class="scroll-reveal glass-card mb-8 md:mb-12 rounded-2xl overflow-hidden">
    <div class="border-b border-white/08 p-6 md:p-10 text-center" style="background:rgba(255,255,255,.02);">
        <p class="section-label justify-center mb-3">Pemilihan Aktif</p>
        <h1 class="text-2xl md:text-4xl serif-font font-bold mb-3 text-white">
            <?= htmlspecialchars($pemilihan_aktif['judul']) ?>
        </h1>
        <p class="text-white/50 text-xs md:text-sm max-w-2xl mx-auto leading-relaxed">
            <?= htmlspecialchars($pemilihan_aktif['deskripsi']) ?>
        </p>
    </div>

    <!-- GRID KANDIDAT — tidak ada modal di sini -->
    <div class="p-5 md:p-8">
        <div class="<?= $grid_class ?> gap-5 md:gap-7">

            <?php foreach ($kandidat_data as $idx => $k): ?>

            <div class="scroll-reveal kandidat-card" data-d="<?= $idx + 1 ?>">

                <span class="no-badge">No. 0<?= $k['no_urut'] ?></span>

                <!-- FOTO — klik buka modal -->
                <div class="foto-wrap w-32 h-32 md:w-44 md:h-44 relative mb-4 md:mb-5"
                     onclick="bukaModal(<?= $k['id'] ?>)">
                    <img src="assets/<?= htmlspecialchars($k['foto']) ?>"
                         alt="<?= htmlspecialchars($k['nama_kandidat']) ?>"
                         class="w-full h-full object-cover">
                    <div class="foto-overlay">
                        <span>Lihat<br>Detail</span>
                    </div>
                </div>

                <span class="text-white/35 font-bold tracking-widest text-[9px] mb-1 uppercase">Kandidat 0<?= $k['no_urut'] ?></span>
                <h2 class="text-xl md:text-2xl serif-font font-bold mb-3 text-white leading-tight w-full break-words px-2">
                    <?= htmlspecialchars($k['nama_kandidat']) ?>
                </h2>
                <span class="border border-white/10 px-4 py-1.5 rounded-full text-[9px] font-bold mb-6 tracking-wide text-white/60"
                      style="background:rgba(255,255,255,.04);">
                    ◈ &nbsp;<?= htmlspecialchars(!empty($k['kelas']) ? $k['kelas'] : 'Kelas') ?>
                    &nbsp;·&nbsp;
                    <?= htmlspecialchars(!empty($k['jurusan']) ? $k['jurusan'] : 'Jurusan') ?>
                </span>

                <!-- FORM VOTE -->
                <form id="form-vote-<?= $k['id'] ?>" action="proses_vote.php" method="POST"
                      class="w-full mt-auto pt-4" style="border-top:1px solid rgba(255,255,255,.08);">
                    <input type="hidden" name="kandidat_id"  value="<?= $k['id'] ?>">
                    <input type="hidden" name="pemilihan_id" value="<?= $pemilihan_id ?>">

                    <?php if ($sudah_milih): ?>
                        <button type="button" disabled class="btn-pilih opacity-25" style="pointer-events:none;">
                            <span class="btn-icon">◈</span> Terkunci
                        </button>
                    <?php else: ?>
                        <button type="button"
                            onclick="konfirmasiVote(<?= $k['id'] ?>, '<?= addslashes($k['nama_kandidat']) ?>', 'assets/<?= htmlspecialchars($k['foto']) ?>', <?= $k['no_urut'] ?>)"
                            class="btn-pilih">
                            <span class="btn-icon">◈</span>
                            PILIH 0<?= $k['no_urut'] ?>
                        </button>
                    <?php endif; ?>
                </form>

            </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<!-- LIVE REAL COUNT — style selaras index.php -->
<div class="scroll-reveal glass-card p-6 md:p-8 rounded-2xl mb-8">

    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
        <div>
            <p class="section-label mb-1">Statistik Suara</p>
            <h3 class="text-xl md:text-2xl serif-font font-bold">Live Real Count</h3>
            <p class="text-[11px] text-white/45 mt-1">Perolehan suara sementara — otomatis diperbarui.</p>
        </div>
        <span class="text-xs text-white/35 border border-white/10 rounded-full px-3 py-1 flex-shrink-0">
            <?php
                $ts_real = 0;
                foreach($kandidat_data as $k) $ts_real += $k['jumlah_suara'];
                echo $ts_real;
            ?> suara
        </span>
    </div>

    <!-- Chart -->
    <div style="position:relative; height:220px;" class="mb-7">
        <canvas id="voteChart"
            data-labels="<?= htmlspecialchars($labels_json) ?>"
            data-values="<?= htmlspecialchars($data_json) ?>">
        </canvas>
    </div>

    <!-- Ranking Rows (seperti index.php) -->
    <div class="space-y-0 border-t border-white/08 pt-4">
        <?php
        $sorted_kandidat = $kandidat_data;
        usort($sorted_kandidat, fn($a, $b) => $b['jumlah_suara'] - $a['jumlah_suara']);
        $ts_for_rank = $ts_real > 0 ? $ts_real : 1;
        foreach($sorted_kandidat as $rank_idx => $k):
            $pct = round(($k['jumlah_suara'] / $ts_for_rank) * 100);
        ?>
        <div style="display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.05);"
             class="<?= $rank_idx === count($sorted_kandidat)-1 ? 'border-b-0' : '' ?>">

            <!-- Rank number -->
            <span style="font-family:'Cormorant Garamond',serif; font-weight:700; font-size:1.2rem;
                         color:<?= $rank_idx === 0 ? 'rgba(255,255,255,.85)' : 'rgba(255,255,255,.2)' ?>;
                         width:24px; text-align:center; flex-shrink:0;">
                <?= $rank_idx + 1 ?>
            </span>

            <!-- Name + bar -->
            <div style="flex-grow:1; min-width:0;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                    <span style="font-size:.83rem; font-weight:600;
                                 <?= $rank_idx === 0 ? 'color:#fff;' : 'color:rgba(255,255,255,.7);' ?>
                                 white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        No. <?= $k['no_urut'] ?> — <?= htmlspecialchars($k['nama_kandidat']) ?>
                    </span>
                    <span style="font-size:.72rem; color:rgba(255,255,255,.45); margin-left:8px; flex-shrink:0;">
                        <?= $pct ?>%
                    </span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" data-w="<?= $pct ?>"></div>
                </div>
            </div>

            <!-- Vote count -->
            <span style="font-size:.83rem; font-weight:700;
                         color:<?= $rank_idx === 0 ? 'rgba(255,255,255,.9)' : 'rgba(255,255,255,.5)' ?>;
                         flex-shrink:0; width:32px; text-align:right;">
                <?= $k['jumlah_suara'] ?>
            </span>

            <!-- Crown / badge untuk posisi 1 -->
            <?php if($rank_idx === 0): ?>
            <span style="font-size:8px; font-weight:700; letter-spacing:.22em; text-transform:uppercase; color:rgba(255,255,255,.85); border:1px solid rgba(255,255,255,.3); border-radius:999px; padding:2px 8px; flex-shrink:0; white-space:nowrap; text-shadow:0 0 10px rgba(255,255,255,.4);">◈</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- ── STATISTIK PARTISIPASI ── -->
<?php
    // Pakai $ts_real (sudah dihitung dari jumlah_suara kandidat) supaya konsisten dengan index.php
    $stat_total_pemilih = $conn->query("SELECT COUNT(id) AS total FROM pemilih")->fetch_assoc()['total'];
    $stat_suara_masuk   = $ts_real; // sama dengan yang ditampilkan di chart & index.php
    $stat_persen        = $stat_total_pemilih > 0 ? round(($stat_suara_masuk / $stat_total_pemilih) * 100, 1) : 0;
?>
<div class="scroll-reveal glass-card p-6 md:p-8 rounded-2xl mb-8">

    <p class="section-label mb-5">Partisipasi Pemilih</p>

    <!-- 3 Stat Items -->
    <div class="grid grid-cols-3 gap-4 md:gap-6 mb-7">

        <!-- Total Pemilih -->
        <div style="text-align:center; padding:1.25rem 1rem; border-radius:12px;
                    background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);">
            <p style="font-size:9px; font-weight:700; letter-spacing:.25em; text-transform:uppercase;
                      color:rgba(255,255,255,.3); margin-bottom:.75rem;">Total Pemilih</p>
            <p style="font-family:'Cormorant Garamond',serif; font-weight:700; font-size:2.4rem;
                      line-height:1; color:#fff; letter-spacing:.05em;">
                <?= number_format($stat_total_pemilih) ?>
            </p>
            <p style="font-size:10px; color:rgba(255,255,255,.3); margin-top:.4rem;">Terdaftar</p>
        </div>

        <!-- Suara Masuk -->
        <div style="text-align:center; padding:1.25rem 1rem; border-radius:12px;
                    background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);">
            <p style="font-size:9px; font-weight:700; letter-spacing:.25em; text-transform:uppercase;
                      color:rgba(255,255,255,.3); margin-bottom:.75rem;">Suara Masuk</p>
            <p style="font-family:'Cormorant Garamond',serif; font-weight:700; font-size:2.4rem;
                      line-height:1; color:#fff; letter-spacing:.05em;">
                <?= number_format($stat_suara_masuk) ?>
            </p>
            <p style="font-size:10px; color:rgba(255,255,255,.3); margin-top:.4rem;">Suara</p>
        </div>

        <!-- Partisipasi % -->
        <div style="text-align:center; padding:1.25rem 1rem; border-radius:12px;
                    background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);">
            <p style="font-size:9px; font-weight:700; letter-spacing:.25em; text-transform:uppercase;
                      color:rgba(255,255,255,.3); margin-bottom:.75rem;">Partisipasi</p>
            <p style="font-family:'Cormorant Garamond',serif; font-weight:700; font-size:2.4rem;
                      line-height:1; color:#fff; letter-spacing:.05em;">
                <?= $stat_persen ?>%
            </p>
            <p style="font-size:10px; color:rgba(255,255,255,.3); margin-top:.4rem;">Hadir Memilih</p>
        </div>

    </div>

    <!-- Progress Bar Partisipasi -->
    <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <span style="font-size:10px; color:rgba(255,255,255,.35); letter-spacing:.15em; text-transform:uppercase;">
                Tingkat Partisipasi
            </span>
            <span style="font-size:10px; font-weight:700; color:rgba(255,255,255,.6);">
                <?= $stat_suara_masuk ?> / <?= $stat_total_pemilih ?> pemilih
            </span>
        </div>
        <div class="progress-track" style="height:8px;">
            <div class="progress-fill" data-w="<?= $stat_persen ?>" style="height:8px;"></div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════════
     SEMUA MODAL — di luar card agar position:fixed tidak terputus
     oleh CSS transform pada parent (.kandidat-card hover effect)
══════════════════════════════════════════════════════════════ -->
<?php foreach ($kandidat_data as $k): ?>
<div id="modal-<?= $k['id'] ?>"
     class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 p-3 sm:p-4 overflow-y-auto backdrop-blur-sm">
    <div class="bg-[#121212] max-w-5xl w-full rounded-2xl shadow-2xl shadow-white/5 overflow-hidden flex flex-col md:flex-row relative my-auto border border-white/10 max-h-[95vh] md:max-h-[85vh]">

        <button type="button" onclick="tutupModal(<?= $k['id'] ?>)"
            class="absolute top-2 right-2 md:top-4 md:right-4 z-10 bg-black/50 md:bg-white/10 text-white w-8 h-8 rounded-full font-bold hover:bg-white hover:text-black border border-white/20 transition flex items-center justify-center text-lg md:text-xl backdrop-blur-md">
            &times;
        </button>

        <!-- POSTER KIRI -->
        <div class="w-full md:w-5/12 bg-black/30 flex items-center justify-center p-4 border-b md:border-b-0 md:border-r border-white/10 min-h-[150px] max-h-52 md:max-h-full">
            <?php if (!empty($k['poster'])): ?>
                <img src="assets/<?= htmlspecialchars($k['poster']) ?>" alt="Poster"
                     class="w-full h-full object-contain drop-shadow-xl rounded border border-white/5">
            <?php else: ?>
                <div class="text-white/30 text-xs md:text-sm italic">Poster belum diupload</div>
            <?php endif; ?>
        </div>

        <!-- KONTEN KANAN -->
        <div class="w-full md:w-7/12 p-5 md:p-8 overflow-y-auto text-white custom-scrollbar max-h-[50vh] md:max-h-full">
            <div class="border-b-2 border-white/10 pb-4 mb-5 md:mb-6">
                <span class="bg-white text-black px-2 md:px-3 py-1 text-[10px] md:text-xs font-bold tracking-widest uppercase rounded shadow-md">
                    PASLON NO. <?= $k['no_urut'] ?>
                </span>
                <h2 class="text-2xl md:text-3xl serif-font font-black mt-3 text-white leading-tight">
                    <?= htmlspecialchars($k['nama_kandidat']) ?>
                </h2>
                <p class="text-white/70 font-bold mt-2 text-xs md:text-sm bg-white/5 inline-block px-2 py-1 rounded border border-white/10">
                    <?= htmlspecialchars(!empty($k['kelas']) ? $k['kelas'] : 'Kelas') ?>
                    | <?= htmlspecialchars(!empty($k['jurusan']) ? $k['jurusan'] : 'Jurusan') ?>
                </p>
            </div>

            <div class="space-y-5 md:space-y-6">
                <div>
                    <h3 class="font-bold text-base md:text-lg mb-1.5 md:mb-2 border-l-4 border-white pl-2 md:pl-3">Visi</h3>
                    <p class="text-white/70 text-xs md:text-sm leading-relaxed">
                        <?= !empty($k['visi']) ? nl2br(htmlspecialchars($k['visi'])) : '-' ?>
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-base md:text-lg mb-1.5 md:mb-2 border-l-4 border-white pl-2 md:pl-3">Misi</h3>
                    <p class="text-white/70 text-xs md:text-sm leading-relaxed">
                        <?= !empty($k['misi']) ? nl2br(htmlspecialchars($k['misi'])) : '-' ?>
                    </p>
                </div>
                <div>
                    <h3 class="font-bold text-base md:text-lg mb-1.5 md:mb-2 border-l-4 border-white pl-2 md:pl-3">Program Kerja</h3>
                    <div class="text-white/70 text-xs md:text-sm leading-relaxed bg-white/5 p-3 md:p-4 border border-white/10 rounded-lg">
                        <?= !empty($k['program_kerja']) ? nl2br(htmlspecialchars($k['program_kerja'])) : '-' ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<?php endforeach; ?>