<?php
// ==============================================================================
// FILE: welcome.php
// FUNGSI: Halaman Sambutan (Onboarding) untuk Pengguna Baru
// PENJELASAN UNTUK UKK:
// - Ditampilkan setelah pendaftaran akun baru berhasil.
// - Memberikan gambaran singkat mengenai fitur-fitur utama TaskFlow.
// - Terdapat tombol 'Lanjut ke Halaman Utama' untuk menuju dashboard bersih (0 tugas).
// ==============================================================================

require_once __DIR__ . '/config/database.php';

// Route Guard: Pastikan pengguna sudah login
requireLogin();

$currentUser = getCurrentUser();
$name = htmlspecialchars($currentUser['name'] ?? 'Pengguna');
$username = htmlspecialchars($currentUser['username'] ?? 'user');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang &mdash; TaskFlow Workspace</title>

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

    <div class="w-full max-w-2xl">

        <!-- KARTU SAMBUTAN UTAMA -->
        <div class="welcome-card bg-white/95 backdrop-blur-md rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-200/50 p-8 sm:p-12 text-center">
            
            <!-- Logo Emblem Sambutan -->
            <img src="assets/img/logo.jpg" alt="Logo TaskFlow" class="w-20 h-20 rounded-3xl object-contain p-2 bg-white shadow-xl shadow-brand-500/20 border border-slate-200 mx-auto mb-6 transform hover:scale-105 transition duration-300">

            <!-- Teks Sambutan -->
            <span class="welcome-badge inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200/70 mb-3">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                Akun Berhasil Dibuat
            </span>

            <h1 class="text-2xl sm:text-3xl font-black">
                <span class="bg-gradient-to-r from-slate-900 via-indigo-950 to-brand-700 bg-clip-text text-transparent">Selamat Datang, <?= $name ?>!</span>
                <span class="inline-block transform hover:rotate-12 hover:scale-125 transition duration-300 cursor-default">🎉</span>
            </h1>
            
            <!-- jika mau diubah jadi justify tambahkan kode ini : text-justify, di belakang tanda petik awal -->
            <p class="text-sm sm:text-base text-slate-600 mt-2 max-w-lg mx-auto leading-relaxed">
                Akun Anda (<strong class="text-slate-800">@<?= $username ?></strong>) telah aktif. Ruang kerja Anda masih bersih dan siap diisi dengan agenda kegiatan harian Anda.
            </p>

            <!-- 3 KARTU FITUR UNGGULAN -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 my-8 text-left"> 
                <!-- jika mau diubah jadi justify tambahkan kode text-left diubah jadi : text-justify -->
                
                <!-- Fitur 1 -->
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 hover:border-brand-200 transition duration-200">
                    <div class="w-9 h-9 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-sm font-bold mb-2.5">
                        <i class="fa-solid fa-table-columns"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800">Papan Kanban</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Tarik & lepas (*drag & drop*) tugas antar status secara real-time.
                    </p>
                </div>

                <!-- Fitur 2 -->
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 hover:border-brand-200 transition duration-200">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold mb-2.5">
                        <i class="fa-regular fa-calendar-days"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800">Kalender Agenda</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Jadwalkan tanggal dan deadline dengan tampilan bulanan yang intuitif.
                    </p>
                </div>

                <!-- Fitur 3 -->
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 hover:border-brand-200 transition duration-200">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm font-bold mb-2.5">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <h3 class="text-xs font-bold text-slate-800">Pantau Deadline</h3>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                        Peringatan otomatis saat batas waktu tugas mendekat agar tidak terlewat.
                    </p>
                </div>

            </div>

            <!-- TOMBOL NEXT / KE HALAMAN UTAMA -->
            <div class="space-y-3">
                <a href="index.php" 
                   class="btn-welcome-next inline-flex items-center justify-center space-x-2 bg-brand-600 hover:bg-brand-700 active:scale-[0.99] text-white font-bold text-sm sm:text-base px-8 py-3.5 rounded-xl shadow-lg shadow-brand-500/25 transition-all duration-200 w-full sm:w-auto">
                    <span>Lanjut ke Halaman Utama</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>

                <p class="text-[11px] text-slate-400">
                    Klik tombol di atas untuk mulai menambahkan agenda pertama Anda
                </p>
            </div>

        </div>

    </div>

</body>
</html>
