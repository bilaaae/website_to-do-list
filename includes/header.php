<?php
// ==============================================================================
// FILE: includes/header.php
// FUNGSI: Header halaman, memuat tag <head>, CDN CSS (Tailwind, FontAwesome),
//         Navbar, Profil Pengguna, Lonceng Notifikasi Deadline, dan Alert Banner.
// ==============================================================================

// Fallback variabel agar tidak muncul peringatan merah di editor/VS Code
require_once __DIR__ . '/../config/database.php';
$hariIni     = $hariIni ?? getHariIniIndo();
$dbError     = $dbError ?? false;
$flash       = $flash ?? getFlash();
$urgentTasks = $urgentTasks ?? [];
$urgentCount = count($urgentTasks);
$currentUser = $currentUser ?? getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow &mdash; To-Do List & Agenda Elegan</title>

    <!-- Tailwind CSS (Framework CSS untuk antarmuka modern) -->
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

    <!-- FontAwesome 6 (Ikon modern dan estetik) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS (File styling kustom untuk scrollbar, animasi, kalender, dan drag & drop) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased pb-20">

    <!-- ============================================================== -->
    <!-- NAVBAR (NAVIGASI ATAS)                                          -->
    <!-- ============================================================== -->
    <nav class="sticky top-0 z-30 glass-nav border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo & Judul Aplikasi -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                        <i class="fa-solid fa-layer-group text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xl font-bold bg-gradient-to-r from-slate-900 via-indigo-950 to-brand-700 bg-clip-text text-transparent">TaskFlow</span>
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">Workspace</span>
                        </div>
                        <p class="text-xs text-slate-600 hidden sm:block">To-Do List, Agenda Kalender & Manajemen Produktivitas</p>
                    </div>
                </div>

                <!-- Bagian Kanan: Lonceng Notifikasi, Tanggal Real-Time, Tombol Tambah, & Profil Akun -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    
                    <!-- LONCENG NOTIFIKASI DEADLINE MEPET -->
                    <div class="relative">
                        <button type="button" id="btn-toggle-notif" 
                                class="relative w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 flex items-center justify-center text-sm transition shadow-sm"
                                title="Notifikasi Deadline">
                            <i class="fa-solid fa-bell <?= $urgentCount > 0 ? 'text-amber-500 fa-shake' : '' ?>"></i>
                            <?php if ($urgentCount > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center shadow-sm">
                                <?= $urgentCount ?>
                            </span>
                            <?php endif; ?>
                        </button>

                        <!-- DROPDOWN PANEL NOTIFIKASI -->
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 p-4 z-50">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2">
                                    <i class="fa-solid fa-clock-rotate-left text-brand-600"></i>
                                    <h4 class="text-xs font-bold text-slate-800">Peringatan Deadline</h4>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $urgentCount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-500' ?>">
                                    <?= $urgentCount ?> Tugas Mepet
                                </span>
                            </div>

                            <div class="py-2 max-h-64 overflow-y-auto space-y-2">
                                <?php if ($urgentCount === 0): ?>
                                <div class="py-6 text-center text-xs text-slate-400">
                                    <i class="fa-regular fa-circle-check text-emerald-500 text-2xl mb-1"></i>
                                    <p class="font-medium">Tidak ada deadline yang mepet!</p>
                                    <p class="text-[11px] text-slate-600">Semua tugas Anda aman dan terkendali.</p>
                                </div>
                                <?php else: ?>
                                <?php foreach ($urgentTasks as $ut): 
                                    $info = $ut['deadline_info'] ?? [];
                                    $timeTxt = formatRentangWaktu($ut['start_time'] ?? null, $ut['due_time'] ?? null);
                                ?>
                                <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-slate-100 transition flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-800 truncate"><?= e($ut['title']) ?></p>
                                        <div class="flex items-center gap-1.5 mt-1 text-[10px] text-slate-500">
                                            <span><?= formatTanggalIndo($ut['due_date']) ?></span>
                                            <?php if ($timeTxt): ?>
                                             <span>&bull; <?= $timeTxt ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= $info['badge_class'] ?? 'bg-amber-100 text-amber-800' ?>">
                                        <?= $info['label'] ?? 'Mepet' ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Tombol Aktifkan Notifikasi Browser -->
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <button type="button" id="btn-request-browser-notif" class="text-[11px] text-brand-600 hover:text-brand-700 font-semibold inline-flex items-center space-x-1">
                                    <i class="fa-solid fa-bell-concierge"></i>
                                    <span>Aktifkan Notifikasi Desktop</span>
                                </button>
                                <span class="text-[10px] text-slate-600">TaskFlow Alerts</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Real-Time -->
                    <div class="hidden md:flex items-center text-xs text-slate-600 bg-slate-100/90 px-3.5 py-1.5 rounded-full border border-slate-200">
                        <i class="fa-regular fa-calendar-days mr-2 text-brand-600"></i>
                        <span><?= $hariIni ?></span>
                    </div>

                    <!-- Tombol Tambah Agenda/Tugas -->
                    <?php if (!$dbError): ?>
                    <button type="button" class="btn-open-add-modal inline-flex items-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white font-medium text-xs sm:text-sm px-3.5 sm:px-4 py-2 rounded-xl shadow-md shadow-brand-600/20 transition-all duration-200 active:scale-95">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span class="hidden sm:inline">Tambah Agenda</span>
                        <span class="sm:hidden">Tambah</span>
                    </button>
                    <?php endif; ?>

                    <!-- PROFIL PENGGUNA & TOMBOL LOGOUT -->
                    <?php if ($currentUser): ?>
                    <div class="flex items-center pl-2 sm:pl-3 border-l border-slate-200 space-x-2">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs uppercase shadow-inner border border-brand-200" title="<?= e($currentUser['name']) ?>">
                                <?= mb_substr($currentUser['name'] ?? 'U', 0, 1) ?>
                            </div>
                            <div class="hidden xl:block text-left">
                                <p class="text-xs font-bold text-slate-800 leading-tight truncate max-w-[120px]"><?= e($currentUser['name'] ?? 'Pengguna') ?></p>
                                <p class="text-[10px] text-slate-500 leading-tight">@<?= e($currentUser['username'] ?? 'user') ?></p>
                            </div>
                        </div>

                        <!-- Tombol Keluar (Logout) -->
                        <a href="auth.php?action=logout" 
                           onclick="return confirm('Apakah Anda yakin ingin keluar dari TaskFlow?');"
                           class="w-8 h-8 rounded-xl border border-slate-200 hover:border-rose-200 bg-white hover:bg-rose-50 text-slate-500 hover:text-rose-600 flex items-center justify-center text-xs transition shadow-sm"
                           title="Keluar (Logout)">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    </div>
                    <?php endif; ?>

                </div>

            </div>
        </div>
    </nav>

    <!-- WRAPPER KONTEN UTAMA -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">

        <!-- ALERT BANNER DEADLINE MEPET / TERLAMBAT -->
        <?php if ($urgentCount > 0): ?>
        <div id="urgent-alert-banner" class="mb-6 p-4 rounded-2xl shadow-sm border bg-gradient-to-r from-amber-50 to-rose-50 border-amber-200 flex items-center justify-between transition-all duration-300">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-base shadow-sm animate-pulse">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-amber-950">
                        Peringatan Deadline: Ada <?= $urgentCount ?> tugas/agenda yang mendekati batas waktu atau telah terlambat!
                    </h4>
                    <p class="text-[11px] sm:text-xs text-amber-800 mt-0.5">
                        Klik ikon lonceng di atas atau periksa kartu tugas bertanda <span class="font-bold underline">Mepet</span> untuk menyelesaikannya tepat waktu.
                    </p>
                </div>
            </div>
            <button onclick="document.getElementById('urgent-alert-banner').remove()" class="text-amber-500 hover:text-amber-700 p-1.5 rounded-lg hover:bg-amber-100 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- NOTIFIKASI FLASH MESSAGE (Jika ada aksi berhasil/gagal) -->
        <?php if ($flash): ?>
        <div id="flash-toast" class="mb-6 flex items-center justify-between p-4 rounded-xl shadow-lg border transition-all duration-300 <?= $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' ?>">
            <div class="flex items-center space-x-3">
                <i class="<?= $flash['type'] === 'success' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-triangle-exclamation text-rose-600' ?> text-lg"></i>
                <span class="text-sm font-medium"><?= e($flash['message']) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <?php endif; ?>
