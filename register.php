<?php
// ==============================================================================
// FILE: register.php
// FUNGSI: Halaman Pendaftaran (Registrasi) Pengguna Baru
// PENJELASAN UNTUK UKK:
// - Menyediakan form pendaftaran multi-user.
// - Password di-hash menggunakan password_hash() BCRYPT sebelum disimpan ke database.
// - Memastikan username dan email bersifat unik (tidak boleh duplikat).
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
    <title>Daftar Akun Baru &mdash; TaskFlow Workspace</title>

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
<body class="bg-gradient-to-br from-slate-50 via-indigo-50/40 to-slate-100 min-h-screen flex flex-col justify-center items-center p-4 antialiased selection:bg-brand-500 selection:text-white py-12">

    <!-- Tombol Beralih Mode Gelap/Terang -->
    <div class="fixed top-5 right-5 z-50">
        <button type="button" class="btn-theme-toggle w-10 h-10 rounded-2xl border border-slate-200 bg-white/90 backdrop-blur-md shadow-md hover:bg-slate-50 flex items-center justify-center text-sm transition" title="Beralih Mode Gelap/Terang">
            <i class="fa-solid fa-moon text-slate-600"></i>
        </button>
    </div>

    <div class="w-full max-w-lg">

        <!-- LOGO & HEADING -->
        <div class="text-center mb-8">
            <img src="assets/img/logo.jpg" alt="Logo TaskFlow" class="w-16 h-16 rounded-2xl object-contain p-1 bg-white mx-auto mb-3 shadow-md border border-slate-200">
            <h1 class="text-2xl font-black bg-gradient-to-r from-slate-900 via-indigo-950 to-brand-700 bg-clip-text text-transparent">
                Bergabung dengan TaskFlow
            </h1>
            <p class="text-sm text-slate-500 mt-1">Buat akun untuk mulai mengatur produktivitas dan agenda harian</p>
        </div>

        <!-- FLASH NOTIFICATION -->
        <?php if ($flash): ?>
        <div class="mb-5 p-4 rounded-2xl border text-sm flex items-start space-x-3 shadow-sm <?= $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
            <i class="<?= $flash['type'] === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-triangle-exclamation text-rose-600' ?> text-lg mt-0.5"></i>
            <div class="flex-1">
                <span class="font-medium"><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- KARTU FORM PENDAFTARAN -->
        <div class="auth-card bg-white/90 backdrop-blur-md rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-200/50 p-6 sm:p-8">
            
            <form action="auth.php?action=register" method="POST" class="space-y-4">

                <!-- Nama Lengkap -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-regular fa-id-badge"></i>
                        </div>
                        <input type="text" id="name" name="name" required autofocus
                               placeholder="Contoh: Budi Santoso"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm transition">
                    </div>
                </div>

                <!-- Baris: Username & Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Username <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-regular fa-user"></i>
                            </div>
                            <input type="text" id="username" name="username" required
                                   placeholder="budisantoso"
                                   pattern="^[a-zA-Z0-9_]{3,30}$"
                                   title="Hanya huruf, angka, underscore (3-30 karakter tanpa spasi)"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm transition">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Alamat Email <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            <input type="email" id="email" name="email" required
                                   placeholder="budi@contoh.com"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm transition">
                        </div>
                    </div>
                </div>

                <!-- Baris: Password & Konfirmasi Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kata Sandi -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <input type="password" id="password" name="password" required minlength="6"
                                   placeholder="Min. 6 karakter"
                                   class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm transition">
                            <button type="button" onclick="togglePasswordVisibility('password', this)"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Konfirmasi Kata Sandi -->
                    <div>
                        <label for="password_confirm" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Konfirmasi Sandi <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <input type="password" id="password_confirm" name="password_confirm" required minlength="6"
                                   placeholder="Ulangi kata sandi"
                                   class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm transition">
                            <button type="button" onclick="togglePasswordVisibility('password_confirm', this)"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">
                        Dengan mendaftar, data tugas Anda akan tersimpan secara terisolasi dan aman di database MySQL.
                    </p>

                    <!-- Tombol Daftar -->
                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-brand-600 hover:bg-brand-700 active:scale-[0.99] text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-500/25 transition-all duration-200 flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Daftar Akun Baru</span>
                    </button>
                </div>

            </form>

        </div>

        <!-- LINK MENUJU LOGIN -->
        <p class="text-center text-sm text-slate-500 mt-6">
            Sudah memiliki akun? 
            <a href="login.php" class="font-bold text-brand-600 hover:text-brand-700 hover:underline transition">
                Masuk di sini
            </a>
        </p>

    </div>

    <!-- SCRIPT TOGGLE PASSWORD -->
    <script>
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
