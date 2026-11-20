<?php
// ==============================================================================
// FILE: includes/view_list.php
// FUNGSI: Menampilkan tugas dalam bentuk format Daftar (List View) tradisional,
//         lengkap dengan tombol checkbox toggle status cepat.
// ==============================================================================

// Fallback variabel agar tidak muncul peringatan merah (Undefined variable) di editor/VS Code
require_once __DIR__ . '/../config/database.php';
$tasks           = $tasks ?? [];
$searchQuery     = $searchQuery ?? '';
$currentPriority = $currentPriority ?? 'all';
$currentCategory = $currentCategory ?? 'all';
?>

<div class="space-y-3">
    <?php if (empty($tasks)): ?>
    <!-- Tampilan Kosong (Empty State) -->
    <div class="bg-white rounded-2xl p-12 text-center border border-slate-200/80 shadow-sm">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-brand-600 flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="fa-solid fa-clipboard-check"></i>
        </div>
        <h3 class="text-base font-semibold text-slate-800 mb-1">Belum Ada Tugas di Sini</h3>
        <p class="text-xs text-slate-500 mb-5 max-w-sm mx-auto">
            <?= !empty($searchQuery) || $currentPriority !== 'all' || $currentCategory !== 'all' 
                ? 'Tidak ada tugas yang sesuai dengan kriteria filter atau pencarian Anda.' 
                : 'Mulai harimu dengan menambahkan tugas baru agar semua rencana tercapai!' ?>
        </p>
        <button type="button" class="btn-open-add-modal inline-flex items-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Tugas Baru</span>
        </button>
    </div>
    <?php else: ?>

    <!-- Perulangan Menampilkan Tugas -->
    <?php foreach ($tasks as $task): 
        $isDone = (($task['status'] ?? 'todo') === 'completed');

        // Format Prioritas
        $priorityConfig = [
            'high'   => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'dot' => 'bg-rose-500 priority-high-pulse', 'label' => 'Tinggi'],
            'medium' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'dot' => 'bg-amber-500', 'label' => 'Sedang'],
            'low'    => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500', 'label' => 'Rendah']
        ];
        $prio = $priorityConfig[$task['priority'] ?? 'medium'] ?? $priorityConfig['medium'];

        // Format Status Badge (Semua Status Memiliki Warna Berkarakter)
        $statusBadge = [
            'todo'        => ['color' => 'bg-rose-50 text-rose-700 border border-rose-200', 'label' => 'Belum Mulai'],
            'in_progress' => ['color' => 'bg-amber-50 text-amber-700 border border-amber-200', 'label' => 'Dalam Proses'],
            'completed'   => ['color' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'label' => 'Selesai']
        ][$task['status'] ?? 'todo'] ?? ['color' => 'bg-rose-50 text-rose-700 border border-rose-200', 'label' => 'Belum Mulai'];

        // Tanggal Jatuh Tempo & Peringatan Mepet
        $dueBadge = null;
        $deadlineStatus = getDeadlineStatus($task['due_date'] ?? null, $task['due_time'] ?? null, $isDone);

        if (!empty($task['due_date'])) {
            $dueBadge = ['color' => 'bg-slate-100 text-slate-600', 'text' => formatTanggalIndo($task['due_date'])];
        }
    ?>
    <div class="task-card task-card-item bg-white rounded-2xl p-4 sm:p-5 border shadow-sm flex items-start gap-4 <?= $isDone ? 'opacity-60 bg-slate-50/50 border-slate-200/80' : (!empty($deadlineStatus['pulse']) ? 'border-amber-300 ring-1 ring-amber-200' : 'border-slate-200/80') ?>"
         data-search-title="<?= strtolower(e($task['title'] ?? '')) ?>"
         data-search-desc="<?= strtolower(e($task['description'] ?? '')) ?>"
         data-search-category="<?= strtolower(e($task['category'] ?? '')) ?>">
        
        <!-- Checkbox Toggle Cepat (Actions.php?action=toggle) -->
        <a href="actions.php?action=toggle&id=<?= $task['id'] ?>" 
           class="custom-checkbox mt-0.5 flex-shrink-0 w-6 h-6 rounded-lg border flex items-center justify-center transition-all <?= $isDone ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm shadow-emerald-500/20' : 'border-slate-300 hover:border-brand-500 text-transparent hover:text-slate-300 bg-white' ?>">
            <i class="fa-solid fa-check text-xs"></i>
        </a>

        <!-- Rincian Tugas -->
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-slate-900 leading-snug break-words <?= $isDone ? 'line-through text-slate-400' : '' ?>">
                <?= e($task['title'] ?? '') ?>
            </h4>

            <?php if (!empty($task['description'])): ?>
            <p class="text-xs text-slate-500 mt-1 mb-2 leading-relaxed break-words <?= $isDone ? 'line-through text-slate-400' : '' ?>">
                <?= nl2br(e($task['description'])) ?>
            </p>
            <?php endif; ?>

            <!-- Badges -->
            <div class="flex flex-wrap items-center gap-2 mt-2 text-[11px]">
                <!-- Status Badge -->
                <span class="inline-flex items-center px-2 py-0.5 rounded-full <?= $statusBadge['color'] ?> font-medium">
                    <?= $statusBadge['label'] ?>
                </span>

                <!-- Kategori -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">
                    <i class="fa-regular fa-folder mr-1.5 text-slate-400"></i>
                    <?= e($task['category'] ?? 'Umum') ?>
                </span>

                <!-- Prioritas -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border <?= $prio['bg'] ?> font-medium">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $prio['dot'] ?>"></span>
                    <?= $prio['label'] ?>
                </span>

                <!-- Peringatan Deadline Mepet / Terlambat -->
                <?php if ($deadlineStatus): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full border <?= $deadlineStatus['badge_class'] ?> <?= !empty($deadlineStatus['pulse']) ? 'priority-high-pulse' : '' ?>">
                    <i class="<?= $deadlineStatus['icon'] ?> mr-1.5"></i>
                    <?= $deadlineStatus['label'] ?>
                </span>
                <?php endif; ?>

                <!-- Jatuh Tempo -->
                <?php if ($dueBadge): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full <?= $dueBadge['color'] ?>">
                    <i class="fa-regular fa-calendar mr-1.5"></i>
                    <?= $dueBadge['text'] ?>
                </span>
                <?php endif; ?>

                <!-- Jam Mulai & Deadline -->
                <?php $timeRange = formatRentangWaktu($task['start_time'] ?? null, $task['due_time'] ?? null); ?>
                <?php if ($timeRange): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                    <i class="fa-regular fa-clock mr-1.5 text-indigo-500"></i>
                    <?= $timeRange ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tombol Aksi Edit & Hapus -->
        <div class="flex items-center space-x-1 flex-shrink-0 ml-2">
            <button type="button" 
                    class="btn-open-edit p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition"
                    title="Edit Tugas"
                    data-id="<?= $task['id'] ?>"
                    data-title="<?= e($task['title'] ?? '') ?>"
                    data-description="<?= e($task['description'] ?? '') ?>"
                    data-category="<?= e($task['category'] ?? 'Umum') ?>"
                    data-priority="<?= e($task['priority'] ?? 'medium') ?>"
                    data-status="<?= e($task['status'] ?? 'todo') ?>"
                    data-due-date="<?= e($task['due_date'] ?? '') ?>"
                    data-start-time="<?= e($task['start_time'] ?? '') ?>"
                    data-due-time="<?= e($task['due_time'] ?? '') ?>">
                <i class="fa-regular fa-pen-to-square text-xs"></i>
            </button>
            <button type="button" 
                    class="btn-open-delete p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition"
                    title="Hapus Tugas"
                    data-id="<?= $task['id'] ?>"
                    data-title="<?= e($task['title'] ?? '') ?>">
                <i class="fa-regular fa-trash-can text-xs"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>
