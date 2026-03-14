<?php
session_start();
include 'koneksi.php';

if(isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = md5($_POST['password']);
    $query = $conn->query("SELECT * FROM admin WHERE username='$username' AND password='$password'");
    if ($query->num_rows > 0) {
        $admin = $query->fetch_assoc();
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOKIS — Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root {
            --bg-opacity: 0.80;
            --glass-blur: 24px;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: #fff;
            min-height: 100vh;
            background: #000;
            display: flex; align-items: center; justify-content: center;
            padding: 1rem;
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
            background: radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.7) 100%);
        }
        .grain {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: .035;
        }
        .glass-card {
            background: rgba(255,255,255,.06);
            backdrop-filter: blur(var(--glass-blur)) saturate(1.3);
            -webkit-backdrop-filter: blur(var(--glass-blur)) saturate(1.3);
            border: 1px solid rgba(255,255,255,.11);
            box-shadow: 0 24px 64px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.03) inset;
        }
        .vokis-logo {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700; font-size: 2.2rem;
            letter-spacing: .18em; text-transform: uppercase; line-height: 1;
        }
        .section-label {
            display: flex; align-items: center; gap: 10px;
            font-size: 9px; font-weight: 700; letter-spacing: .28em; text-transform: uppercase;
            color: rgba(255,255,255,.3);
        }
        .section-label::before { content: '◈'; font-size: 10px; color: rgba(255,255,255,.22); }
        .section-label::after  { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.08); }
        .input-field {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 8px;
            padding: 11px 14px;
            color: #fff;
            font-size: 13px;
            outline: none;
            transition: border-color .25s, background .25s;
        }
        .input-field::placeholder { color: rgba(255,255,255,.25); }
        .input-field:focus {
            border-color: rgba(255,255,255,.45);
            background: rgba(255,255,255,.08);
        }
        .btn-submit {
            width: 100%;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 8px;
            color: #fff;
            font-size: 10px; font-weight: 700;
            letter-spacing: .28em; text-transform: uppercase;
            padding: 13px;
            cursor: pointer;
            backdrop-filter: blur(8px);
            transition: background .25s, border-color .25s, color .25s, box-shadow .25s;
        }
        .btn-submit:hover {
            background: rgba(255,255,255,.95);
            color: #000;
            border-color: #fff;
            box-shadow: 0 6px 24px rgba(255,255,255,.18);
        }
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .anim-in { animation: fadeUp .65s cubic-bezier(.16,1,.3,1) both; }
    </style>
</head>
<body>
    <div class="vignette"></div>
    <div class="grain"></div>

    <div class="glass-card rounded-2xl p-10 w-full max-w-sm relative z-10 anim-in">
        <!-- Logo -->
        <div class="text-center mb-10">
            <span style="font-size:13px;color:rgba(255,255,255,.25);letter-spacing:.3em;">◈</span>
            <div class="vokis-logo mt-2">VOKIS</div>
            <p style="font-size:9px;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-top:4px;">Admin Portal · SMK 46</p>
        </div>

        <div class="section-label mb-6">Masuk ke Dashboard</div>

        <?php if(isset($error)): ?>
        <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:8px;padding:10px 14px;margin-bottom:20px;">
            <p style="font-size:11px;color:rgba(239,68,68,.9);letter-spacing:.05em;">◈ &nbsp;<?= $error ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);display:block;margin-bottom:7px;">Username</label>
                <input type="text" name="username" required class="input-field" placeholder="Masukkan username">
            </div>
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.3);display:block;margin-bottom:7px;">Password</label>
                <input type="password" name="password" required class="input-field" placeholder="••••••••">
            </div>
            <div style="padding-top:8px;">
                <button type="submit" class="btn-submit">Masuk Dashboard</button>
            </div>
        </form>

        <p style="text-align:center;font-size:10px;color:rgba(255,255,255,.15);margin-top:28px;letter-spacing:.15em;">SISTEM E-VOTING DEMOKRATIS</p>
    </div>
</body>
</html>