<?php
// ==============================================================================
// FILE: login.php
// FUNGSI: Halaman Masuk (Login) Aplikasi TaskFlow
// PENJELASAN UNTUK UKK:
// - Menyediakan form otentikasi akun pengguna.
// - Dilengkapi tombol "Akun Demo Penguji" untuk kemudahan pengujian saat UKK.
// - Menampilkan pesan flash (notifikasi) jika password salah atau baru logout.
// ==============================================================================

require_once __DIR__ . '/config/database.php';

// Jika pengguna sudah dalam keadaan login, langsung alihkan ke dashboard utama
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk &mdash; TaskFlow Workspace</title>

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="assets/img/logo.jpg">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">

    <!-- Script Pengatur Mode Gelap (Dark Mode) -->
    <script src="assets/js/theme.js?v=<?= time() ?>"></script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-indigo-50/40 to-slate-100 min-h-screen flex flex-col justify-center items-center p-4 antialiased selection:bg-brand-500 selection:text-white">

    <!-- Tombol Beralih Mode Gelap/Terang -->
    <div class="fixed top-5 right-5 z-50">
        <button type="button" class="btn-theme-toggle w-10 h-10 rounded-2xl border border-slate-200 bg-white/90 backdrop-blur-md shadow-md hover:bg-slate-50 flex items-center justify-center text-sm transition" title="Beralih Mode Gelap/Terang">
            <i class="fa-solid fa-moon text-slate-600"></i>
        </button>
    </div>

    <div class="w-full max-w-md">

        <!-- LOGO & HEADING -->
        <div class="text-center mb-8">
            <img src="assets/img/logo.jpg" alt="Logo TaskFlow" class="w-16 h-16 rounded-2xl object-contain p-1 bg-white mx-auto mb-3 shadow-md border border-slate-200">
            <h1 class="text-2xl font-black bg-gradient-to-r from-slate-900 via-indigo-950 to-brand-700 bg-clip-text text-transparent">
                TaskFlow Workspace
            </h1>
            <p class="text-sm text-slate-500 mt-1">Silakan masuk untuk mengelola tugas dan agenda Anda</p>
        </div>

        <!-- FLASH NOTIFICATION -->
        <?php if ($flash): ?>
        <div class="mb-5 p-4 rounded-2xl border text-sm flex items-start space-x-3 shadow-sm <?= $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($flash['type'] === 'info' ? 'bg-blue-50 border-blue-200 text-blue-800' : 'bg-rose-50 border-rose-200 text-rose-800') ?>">
            <i class="<?= $flash['type'] === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : ($flash['type'] === 'info' ? 'fa-solid fa-circle-info text-blue-600' : 'fa-solid fa-circle-exclamation text-rose-600') ?> text-lg mt-0.5"></i>
            <div class="flex-1">
                <span class="font-medium"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- KARTU LOGIN UTAMA -->
        <div class="auth-card bg-white/90 backdrop-blur-md rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-200/50 p-6 sm:p-8">
            
            <form action="auth.php?action=login" method="POST" class="space-y-5">

                <!-- Input Username / Email -->
                <div>
                    <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Username atau Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-regular fa-user"></i>
                        </div>
                        <input type="text" id="username" name="username" required autofocus
                               placeholder="Masukkan username atau email"
                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm transition">
                    </div>
                </div>

                <!-- Input Password -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Kata Sandi (Password)
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               placeholder="Masukkan password Anda"
                               class="w-full pl-10 pr-11 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm transition">
                        <button type="button" onclick="togglePasswordVisibility('password', this)"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                                title="Lihat/Sembunyikan Password">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Tombol Submit Masuk -->
                <button type="submit"
                        class="w-full py-3.5 px-4 bg-brand-600 hover:bg-brand-700 active:scale-[0.99] text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-500/25 transition-all duration-200 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Masuk ke Workspace</span>
                </button>

            </form>

        </div>

        <!-- LINK MENUJU DAFTAR AKUN BARU -->
        <p class="text-center text-sm text-slate-500 mt-6">
            Belum memiliki akun? 
            <a href="register.php" class="font-bold text-brand-600 hover:text-brand-700 hover:underline transition">
                Daftar Akun Baru
            </a>
        </p>

    </div>

    <!-- SCRIPT INTERAKTIF KHUSUS FORM LOGIN -->
    <script>
        // Fungsi untuk toggle lihat/sembunyikan password
        function togglePasswordVisibility(fieldId, btnElement) {
            const input = document.getElementById(fieldId);
            const icon = btnElement.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
