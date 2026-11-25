<?php
// ==============================================================================
// FILE: includes/toolbar.php
// FUNGSI: Bilah kontrol untuk menyaring data (filter kategori, prioritas, pencarian,
//         pengurutan deadline terdekat/terjauh), serta beralih mode tampilan.
// ==============================================================================

// Fallback variabel agar tidak muncul peringatan merah di editor/VS Code
require_once __DIR__ . '/../config/database.php';
$viewMode        = $viewMode ?? 'kanban';
$currentCategory = $currentCategory ?? 'all';
$currentPriority = $currentPriority ?? 'all';
$currentSort     = $currentSort ?? 'deadline_asc';
$searchQuery     = $searchQuery ?? '';
$categories      = $categories ?? ['Umum', 'Pekerjaan', 'Pribadi', 'Belajar', 'Keuangan', 'Kesehatan', 'Organisasi', 'UKK & LSP', 'Magang PKL'];
$stats           = $stats ?? ['completed' => 0];

// Parameter bulan dan tahun aktif untuk kalender
$calMonth = $calMonth ?? (int)date('n');
$calYear  = $calYear ?? (int)date('Y');
?>
<section class="toolbar-container bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm mb-6">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        
        <!-- SISI KIRI: Mode Tampilan di Atas & Kotak Pencarian di Bawahnya -->
        <div class="flex flex-col gap-2.5">
            
            <!-- 1. Pilihan 3 Mode Tampilan (Kanban, List, Kalender) -->
            <div class="flex items-center space-x-2">
                <span class="text-xs font-semibold text-slate-500 hidden sm:inline">Mode:</span>
                <div class="toolbar-mode-wrapper inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 overflow-x-auto">
                    <!-- A. Papan Kanban -->
                    <a href="?view=kanban&sort=<?= urlencode($currentSort) ?>&category=<?= urlencode($currentCategory) ?>&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>" 
                       class="toolbar-mode-btn inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap <?= $viewMode === 'kanban' ? 'toolbar-mode-active bg-white text-brand-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-table-columns"></i>
                        <span>Kanban (Drag & Drop)</span>
                    </a>
                    
                    <!-- B. Daftar List -->
                    <a href="?view=list&sort=<?= urlencode($currentSort) ?>&category=<?= urlencode($currentCategory) ?>&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>" 
                       class="toolbar-mode-btn inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap <?= $viewMode === 'list' ? 'toolbar-mode-active bg-white text-brand-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                        <i class="fa-solid fa-list-ul"></i>
                        <span>Daftar List</span>
                    </a>

                    <!-- C. Kalender Agenda -->
                    <a href="?view=calendar&month=<?= $calMonth ?>&year=<?= $calYear ?>&sort=<?= urlencode($currentSort) ?>&category=<?= urlencode($currentCategory) ?>&priority=<?= urlencode($currentPriority) ?>&q=<?= urlencode($searchQuery) ?>" 
                       class="toolbar-mode-btn inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap <?= $viewMode === 'calendar' ? 'toolbar-mode-active bg-white text-brand-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                        <i class="fa-regular fa-calendar-days text-brand-600"></i>
                        <span>Kalender Agenda</span>
                    </a>
                </div>
            </div>

            <!-- 2. Kotak Pencarian (TEPAT DI BAWAH TOMBOL MODE) -->
            <div class="relative w-full sm:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="search-task-input" placeholder="Cari agenda/tugas..." value="<?= e($searchQuery) ?>" 
                       class="toolbar-search-input w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
            </div>

        </div>

        <!-- SISI KANAN: Urutkan Deadline, Filter Kategori, Filter Prioritas -->
        <div class="flex flex-wrap items-center gap-2">
            
            <!-- PENGURUTAN (SORTING) DEADLINE TERDEKAT / TERJAUH -->
            <form method="GET" class="inline-block m-0">
                <input type="hidden" name="view" value="<?= e($viewMode) ?>">
                <?php if ($viewMode === 'calendar'): ?>
                <input type="hidden" name="month" value="<?= $calMonth ?>">
                <input type="hidden" name="year" value="<?= $calYear ?>">
                <?php endif; ?>
                <input type="hidden" name="category" value="<?= e($currentCategory) ?>">
                <input type="hidden" name="priority" value="<?= e($currentPriority) ?>">
                <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                
                <div class="relative">
                    <select name="sort" onchange="this.form.submit()" 
                            class="toolbar-filter-select bg-slate-50 border border-slate-200 text-slate-700 text-xs font-medium rounded-xl pl-7 pr-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none transition cursor-pointer">
                          <option value="deadline_asc" <?= $currentSort === 'deadline_asc' ? 'selected' : '' ?>>
                            ⏳ Deadline: Terdekat &rarr; Terjauh
                        </option>
                        <option value="deadline_desc" <?= $currentSort === 'deadline_desc' ? 'selected' : '' ?>>
                            ⌛ Deadline: Terjauh &rarr; Terdekat
                        </option>
                        <option value="priority_desc" <?= $currentSort === 'priority_desc' ? 'selected' : '' ?>>
                            ⚡ Prioritas: Tertinggi &rarr; Rendah
                        </option>
                        <option value="created_desc" <?= $currentSort === 'created_desc' ? 'selected' : '' ?>>
                            🕒 Terbaru Dibuat
                        </option>
                    </select>
                    <i class="fa-solid fa-arrow-down-short-wide absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                </div>
            </form>

            <!-- Filter Kategori Dropdown -->
            <form method="GET" class="inline-block m-0">
                <input type="hidden" name="view" value="<?= e($viewMode) ?>">
                <?php if ($viewMode === 'calendar'): ?>
                <input type="hidden" name="month" value="<?= $calMonth ?>">
                <input type="hidden" name="year" value="<?= $calYear ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($currentSort) ?>">
                <input type="hidden" name="priority" value="<?= e($currentPriority) ?>">
                <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                <select name="category" onchange="this.form.submit()" 
                        class="toolbar-filter-select bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-xl px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                    <option value="all" <?= $currentCategory === 'all' ? 'selected' : '' ?>>Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $currentCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Filter Prioritas Dropdown -->
            <form method="GET" class="inline-block m-0">
                <input type="hidden" name="view" value="<?= e($viewMode) ?>">
                <?php if ($viewMode === 'calendar'): ?>
                <input type="hidden" name="month" value="<?= $calMonth ?>">
                <input type="hidden" name="year" value="<?= $calYear ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($currentSort) ?>">
                <input type="hidden" name="category" value="<?= e($currentCategory) ?>">
                <input type="hidden" name="q" value="<?= e($searchQuery) ?>">
                <select name="priority" onchange="this.form.submit()" 
                        class="toolbar-filter-select bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-xl px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                    <option value="all" <?= $currentPriority === 'all' ? 'selected' : '' ?>>Semua Prioritas</option>
                    <option value="high" <?= $currentPriority === 'high' ? 'selected' : '' ?>>Prioritas Tinggi</option>
                    <option value="medium" <?= $currentPriority === 'medium' ? 'selected' : '' ?>>Prioritas Sedang</option>
                    <option value="low" <?= $currentPriority === 'low' ? 'selected' : '' ?>>Prioritas Rendah</option>
                </select>
            </form>

           

            <!-- Tombol Bersihkan Selesai (Jika ada tugas selesai) -->
            <?php if (($stats['completed'] ?? 0) > 0): ?>
            <form action="actions.php" method="POST" class="inline-block m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua tugas yang telah selesai?');">
                <input type="hidden" name="action" value="clear_completed">
                <button type="submit" title="Bersihkan semua tugas selesai" 
                        class="btn-clear-completed p-2.5 rounded-xl text-xs border transition shadow-sm">
                    <i class="fa-regular fa-trash-can text-sm"></i>
                </button>
            </form>
            <?php endif; ?>

        </div>

    </div>
</section>

<!-- CARA PAKE EMOJI LEWAT LAPTOP  : Windows + Titik  -->