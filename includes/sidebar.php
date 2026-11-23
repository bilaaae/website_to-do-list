<?php
// ==============================================================================
// FILE: includes/sidebar.php
// FUNGSI: Sidebar Navigasi Kiri (Tambahan Menu Cepat, Mode Tampilan & Kategori)
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
$viewMode        = $viewMode ?? 'kanban';
$currentCategory = $currentCategory ?? 'all';
$currentPriority = $currentPriority ?? 'all';
$currentSort     = $currentSort ?? 'deadline_asc';
$searchQuery     = $searchQuery ?? '';
$categories      = $categories ?? ['Umum', 'Pekerjaan', 'Pribadi', 'Belajar', 'Keuangan', 'Kesehatan', 'Organisasi', 'UKK & LSP'];
$stats           = $stats ?? ['total' => 0, 'completed' => 0, 'percent' => 0];
$calMonth        = $calMonth ?? (int)date('n');
$calYear         = $calYear ?? (int)date('Y');
?>
<aside class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 space-y-6 lg:sticky lg:top-24">

    <!-- 1. TOMBOL TAMBAH AGENDA CEPAT -->
    <button type="button" class="btn-open-add-modal w-full inline-flex items-center justify-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs sm:text-sm py-3 px-4 rounded-xl shadow-md shadow-brand-500/25 transition active:scale-[0.98]">
        <i class="fa-solid fa-plus text-xs"></i>
        <span>Tambah Agenda</span>
    </button>

    <!-- 2. NAVIGASI PILIHAN MODE TAMPILAN -->
    <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 px-1 flex items-center justify-between">
            <span>Menu Tampilan</span>
            <i class="fa-solid fa-compass text-slate-300"></i>
        </p>
        <nav class="space-y-1">
            <!-- Papan Kanban -->
            <a href="?view=kanban&sort=<?= urlencode($currentSort) ?>&category=<?= urlencode($currentCategory) ?>&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>"
               class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $viewMode === 'kanban' ? 'bg-brand-50 text-brand-700 border border-brand-200/60 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <div class="flex items-center space-x-2.5">
                    <i class="fa-solid fa-table-columns text-sm <?= $viewMode === 'kanban' ? 'text-brand-600' : 'text-slate-400' ?>"></i>
                    <span>Kanban Board</span>
                </div>
                <?php if ($viewMode === 'kanban'): ?>
                <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                <?php endif; ?>
            </a>

            <!-- Daftar List -->
            <a href="?view=list&sort=<?= urlencode($currentSort) ?>&category=<?= urlencode($currentCategory) ?>&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>"
               class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $viewMode === 'list' ? 'bg-brand-50 text-brand-700 border border-brand-200/60 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <div class="flex items-center space-x-2.5">
                    <i class="fa-solid fa-list-ul text-sm <?= $viewMode === 'list' ? 'text-brand-600' : 'text-slate-400' ?>"></i>
                    <span>Daftar List</span>
                </div>
                <?php if ($viewMode === 'list'): ?>
                <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                <?php endif; ?>
            </a>

            <!-- Kalender Agenda -->
            <a href="?view=calendar&month=<?= $calMonth ?>&year=<?= $calYear ?>&sort=<?= urlencode($currentSort) ?>&category=<?= urlencode($currentCategory) ?>&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>"
               class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition <?= $viewMode === 'calendar' ? 'bg-brand-50 text-brand-700 border border-brand-200/60 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <div class="flex items-center space-x-2.5">
                    <i class="fa-regular fa-calendar-days text-sm <?= $viewMode === 'calendar' ? 'text-brand-600' : 'text-slate-400' ?>"></i>
                    <span>Kalender Agenda</span>
                </div>
                <?php if ($viewMode === 'calendar'): ?>
                <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>

    <!-- 3. FILTER KATEGORI CEPAT -->
    <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 px-1 flex items-center justify-between">
            <span>Kategori</span>
            <i class="fa-solid fa-tags text-slate-300"></i>
        </p>
        <div class="space-y-1">
            <a href="?view=<?= urlencode($viewMode) ?>&sort=<?= urlencode($currentSort) ?>&category=all&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>"
               class="flex items-center justify-between px-3 py-1.5 rounded-lg text-xs transition <?= $currentCategory === 'all' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium' ?>">
                <span>Semua Kategori</span>
                <span class="text-[10px] text-slate-400">Semua</span>
            </a>

            <?php foreach ($categories as $cat): ?>
            <a href="?view=<?= urlencode($viewMode) ?>&sort=<?= urlencode($currentSort) ?>&category=<?= urlencode($cat) ?>&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>"
               class="flex items-center justify-between px-3 py-1.5 rounded-lg text-xs transition <?= $currentCategory === $cat ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium' ?>">
                <span class="truncate"><?= e($cat) ?></span>
                <?php if ($currentCategory === $cat): ?>
                <i class="fa-solid fa-check text-[10px] text-brand-600"></i>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 4. RINGKASAN PROGRESS PRODUKTIVITAS -->
    <div class="pt-4 border-t border-slate-100">
        <div class="flex items-center justify-between text-xs mb-1.5">
            <span class="text-slate-500 font-medium">Progres Selesai</span>
            <span class="font-bold text-emerald-600"><?= $stats['percent'] ?>%</span>
        </div>
        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: <?= $stats['percent'] ?>%"></div>
        </div>
        <p class="text-[10px] text-slate-400 mt-1.5"><?= $stats['completed'] ?> dari <?= $stats['total'] ?> tugas selesai</p>
    </div>

</aside>