
<?php
session_start();
if(isset($_SESSION['pemilih_id'])) {
    header("Location: dashboard_pemilih.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOKIS - Login Pemilih</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,600;1,600&display=swap');

        body { 
            font-family: 'Inter', sans-serif; 
            color: #ffffff;
        }
        .serif-font { font-family: 'Playfair Display', serif; }

        /* ── VIDEO BACKGROUND ── */
        .video-bg {
            position: fixed;
            right: 0; bottom: 0;
            min-width: 100%; min-height: 100%;
            z-index: -10;
            object-fit: cover;
        }

        /* ── OVERLAY — redam cahaya terang dari video ──
           Dua lapis: satu gelap merata, satu vignette di tepi.
           Ini agar card tetap terbaca meski video terang. */
        .video-overlay {
            position: fixed; inset: 0; z-index: -9;
            /* Gelap merata — naikkan angka alpha (0.0–1.0) jika video terlalu terang */
            background: rgba(0, 0, 0, 0.52);
        }
        .video-vignette {
            position: fixed; inset: 0; z-index: -8;
            /* Vignette: tepi lebih gelap, tengah agak terang */
            background: radial-gradient(ellipse at center,
                rgba(0,0,0,0.10) 0%,
                rgba(0,0,0,0.55) 100%);
            pointer-events: none;
        }

        /* ── AUTOFILL FIX ── */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px transparent inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* ── ANIMASI MASUK ── */
        @keyframes fadeSlideDown {
            from { opacity: 0; transform: translateY(-22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .anim-card    { animation: fadeSlideUp 0.9s cubic-bezier(0.16,1,0.3,1) both; animation-delay: 0.1s; }
        .anim-title   { animation: fadeSlideDown 0.7s cubic-bezier(0.16,1,0.3,1) both; animation-delay: 0.35s; }
        .anim-subtitle{ animation: fadeIn 0.6s ease both; animation-delay: 0.55s; }
        .anim-error   { animation: fadeSlideDown 0.4s ease both; }
        .anim-field-1 { animation: fadeSlideUp 0.65s cubic-bezier(0.16,1,0.3,1) both; animation-delay: 0.6s; }
        .anim-field-2 { animation: fadeSlideUp 0.65s cubic-bezier(0.16,1,0.3,1) both; animation-delay: 0.75s; }
        .anim-button  { animation: fadeSlideUp 0.65s cubic-bezier(0.16,1,0.3,1) both; animation-delay: 0.9s; }
        .anim-footer  { animation: fadeIn 0.6s ease both; animation-delay: 1.05s; }

        /* ── GLASSMORPHISM CARD ──
           Disesuaikan untuk video dengan cahaya terang:
           - background lebih gelap & opak agar teks kontras
           - blur lebih kuat agar video terang di balik tidak mengganggu
           - border lebih terang agar card tetap "terbaca" sebagai entitas terpisah
           - inner shadow agar ada kedalaman meski background terang */
        .glass-card {
            background: rgba(0, 0, 0, 0.42);          /* gelap tapi tembus pandang */
            backdrop-filter: blur(28px) saturate(1.5) brightness(0.85);
            -webkit-backdrop-filter: blur(28px) saturate(1.5) brightness(0.85);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.06) inset,  /* inner glow tipis */
                0 8px 32px rgba(0, 0, 0, 0.55),           /* shadow bawah tebal */
                0 1px 0 rgba(255,255,255,0.10) inset;     /* top highlight */
        }

        /* ── DIVIDER DALAM CARD ── */
        .card-divider {
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* ── IKON ◈ + GARIS GESER ── */
        .input-wrap { position: relative; }

        .input-icon {
            position: absolute; left: 0; top: 50%;
            transform: translateY(-50%);
            font-size: 13px; color: rgba(255,255,255,0.3);
            pointer-events: none; user-select: none;
            transition: color 0.35s ease, text-shadow 0.35s ease;
        }
        .input-wrap:focus-within .input-icon {
            color: rgba(255,255,255,1);
            text-shadow: 0 0 6px rgba(255,255,255,0.8), 0 0 14px rgba(255,255,255,0.4);
        }
        .input-wrap input { padding-left: 20px !important; }

        .input-line {
            position: absolute; bottom: 0; left: 0;
            height: 2px; width: 0;
            background: linear-gradient(90deg, rgba(255,255,255,0.9), rgba(255,255,255,0.3));
            box-shadow: 0 0 8px rgba(255,255,255,0.55);
            border-radius: 999px;
            transition: width 0.45s cubic-bezier(0.16,1,0.3,1);
            pointer-events: none;
        }
        .input-wrap:focus-within .input-line { width: 100%; }

        /* ── TOMBOL SUBMIT ──
           Lebih opak agar tombol terbaca di atas video terang */
        .btn-submit {
            transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
        }
        .btn-submit:hover {
            background: rgba(255,255,255,0.28) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: translateY(0); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <!-- VIDEO -->
    <video autoplay muted loop playsinline class="video-bg">
        <source src="assets/paper3.mp4" type="video/mp4">
        Browser Anda tidak mendukung tag video.
    </video>

    <!-- OVERLAY GELAP + VIGNETTE agar video terang tidak menembus card -->
    <div class="video-overlay"></div>
    <div class="video-vignette"></div>

    <!-- CARD -->
    <div class="anim-card glass-card rounded-2xl p-10 max-w-md w-full relative z-10">

        <div class="text-center mb-10">
            <div class="anim-title">
                <h1 class="text-3xl serif-font font-bold mb-2">AKSES VOKIS</h1>
            </div>
            <p class="anim-subtitle text-sm text-gray-300 uppercase tracking-widest">Silakan identifikasi diri Anda</p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="anim-error bg-red-500/40 border border-red-500/50 backdrop-blur-sm text-white text-sm text-center py-3 mb-6 rounded-lg">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
            <div class="anim-field-1 mb-6">
                <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-2">NISN</label>
                <div class="input-wrap">
                    <span class="input-icon">◈</span>
                    <input type="text" name="nisn" required
                        class="w-full border-b-2 border-white/30 py-2 px-1 focus:outline-none focus:border-white transition-colors bg-transparent placeholder-gray-400 text-white"
                        placeholder="Masukkan NISN Anda">
                    <div class="input-line"></div>
                </div>
            </div>

            <div class="anim-field-2 mb-10">
                <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-2">Token Rahasia</label>
                <div class="input-wrap">
                    <span class="input-icon">◈</span>
                    <input type="password" name="token" required
                        class="w-full border-b-2 border-white/30 py-2 px-1 focus:outline-none focus:border-white transition-colors bg-transparent placeholder-gray-400 text-white"
                        placeholder="••••••••">
                    <div class="input-line"></div>
                </div>
            </div>

            <button type="submit"
                class="btn-submit anim-button w-full bg-white/20 border border-white/30 text-white py-4 uppercase tracking-widest text-sm font-semibold rounded-lg backdrop-blur-sm shadow-lg">
                Masuk Sistem
            </button>
        </form>

        <div class="anim-footer mt-8 text-center pt-6 card-divider" style="border-top: 1px solid;">
            <p class="text-xs text-gray-400">Pastikan Anda menjaga kerahasiaan Token.<br>© 2026 VOKIS SMKN 46</p>
        </div>
    </div>

</body>
</html>

















