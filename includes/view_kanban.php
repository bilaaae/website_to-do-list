<?php
// ==============================================================================
// FILE: includes/view_kanban.php
// FUNGSI: Menampilkan tugas dalam format Papan Kanban 3 Kolom
//         (Belum Mulai, Sedang Berjalan, Selesai) dengan fitur DRAG & DROP.
// ==============================================================================

// Fallback variabel agar tidak muncul peringatan merah di editor/VS Code
require_once __DIR__ . '/../config/database.php';
$tasks = $tasks ?? [];

// Kelompokkan tugas berdasarkan statusnya
$tasksTodo = array_filter($tasks, fn($t) => ($t['status'] ?? 'todo') === 'todo');
$tasksInProgress = array_filter($tasks, fn($t) => ($t['status'] ?? 'todo') === 'in_progress');
$tasksCompleted = array_filter($tasks, fn($t) => ($t['status'] ?? 'todo') === 'completed');

// Konfigurasi 3 Kolom Kanban (Semua Kolom Memiliki Warna Tematik yang Seragam)
$kanbanColumns = [
    [
        'status' => 'todo',
        'title'  => 'Belum Mulai',
        'count'  => count($tasksTodo),
        'tasks'  => $tasksTodo,
        'badge'  => 'bg-rose-50 text-rose-700 border-rose-200',
        'icon'   => 'fa-solid fa-circle-pause text-rose-500',
        'border' => 'border-rose-200/80'
    ],
    [
        'status' => 'in_progress',
        'title'  => 'Sedang Dikerjakan',
        'count'  => count($tasksInProgress),
        'tasks'  => $tasksInProgress,
        'badge'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'icon'   => 'fa-solid fa-hourglass-half text-amber-500',
        'border' => 'border-amber-200/80'
    ],
    [
        'status' => 'completed',
        'title'  => 'Selesai Dikerjakan',
        'count'  => count($tasksCompleted),
        'tasks'  => $tasksCompleted,
        'badge'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'icon'   => 'fa-solid fa-circle-check text-emerald-500',
        'border' => 'border-emerald-200/80'
    ],
];
?>

<!-- GRID KANBAN BOARD 3 KOLOM -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <?php foreach ($kanbanColumns as $col): ?>
    
    <!-- Kolom Kanban (Drop Zone) -->
    <div class="bg-slate-100/70 rounded-2xl p-4 border <?= $col['border'] ?> flex flex-col min-h-[500px]">
        
        <!-- Header Kolom -->
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-200/60">
            <div class="flex items-center space-x-2">
                <i class="<?= $col['icon'] ?>"></i>
                <h3 class="text-sm font-bold text-slate-800"><?= $col['title'] ?></h3>
            </div>
            <span class="column-count-badge px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $col['badge'] ?>" data-col="<?= $col['status'] ?>">
                <?= $col['count'] ?>
            </span>
        </div>

        <!-- Wadah Drop Area (Drag Target) -->
        <div class="kanban-drop-zone flex-1 space-y-3 transition-colors duration-200 rounded-xl p-1"
             data-status="<?= $col['status'] ?>">
            
            <?php if (empty($col['tasks'])): ?>
            <!-- Placeholder jika kolom kosong -->
            <div class="empty-column-placeholder text-center py-10 border-2 border-dashed border-slate-200 rounded-xl">
                <i class="fa-solid fa-arrows-up-down-left-right text-slate-300 text-xl mb-1"></i>
                <p class="text-xs text-slate-400">Geser agenda/tugas ke sini</p>
            </div>
            <?php endif; ?>

            <?php foreach ($col['tasks'] as $task): 
                $isDone = (($task['status'] ?? 'todo') === 'completed');

                // Konfigurasi Prioritas
                $priorityConfig = [
                    'high'   => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'dot' => 'bg-rose-500 priority-high-pulse', 'label' => 'Tinggi'],
                    'medium' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'dot' => 'bg-amber-500', 'label' => 'Sedang'],
                    'low'    => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500', 'label' => 'Rendah']
                ];
                $prio = $priorityConfig[$task['priority'] ?? 'medium'] ?? $priorityConfig['medium'];

                // Tenggat Waktu & Peringatan Deadline Mepet
                $dueBadge = null;
                $deadlineStatus = getDeadlineStatus($task['due_date'] ?? null, $task['due_time'] ?? null, $isDone);

                if (!empty($task['due_date'])) {
                    $dueBadge = ['color' => 'bg-slate-100 text-slate-600', 'text' => formatTanggalIndo($task['due_date'])];
                }
            ?>
            <!-- KARTU TUGAS KANBAN (DRAGGABLE) -->
            <div class="task-card task-card-item bg-white rounded-xl p-4 border shadow-sm cursor-grab active:cursor-grabbing select-none group <?= !empty($deadlineStatus['pulse']) ? 'border-amber-300 ring-1 ring-amber-200' : 'border-slate-200/90' ?>"
                 draggable="true"
                 data-id="<?= $task['id'] ?>"
                 data-status="<?= $task['status'] ?>"
                 data-search-title="<?= strtolower(e($task['title'] ?? '')) ?>"
                 data-search-desc="<?= strtolower(e($task['description'] ?? '')) ?>"
                 data-search-category="<?= strtolower(e($task['category'] ?? '')) ?>">
                
                <!-- Baris Atas: Kategori & Tombol Aksi -->
                <div class="flex items-center justify-between gap-1 mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600">
                        <i class="fa-regular fa-folder mr-1 text-slate-400"></i>
                        <?= e($task['category'] ?? 'Umum') ?>
                    </span>

                    <div class="flex items-center space-x-1 opacity-40 group-hover:opacity-100 transition">
                        <!-- Tombol Edit -->
                        <button type="button" 
                                class="btn-open-edit p-1 text-slate-400 hover:text-brand-600 rounded transition"
                                title="Edit Agenda"
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
                        <!-- Tombol Hapus -->
                        <button type="button" 
                                class="btn-open-delete p-1 text-slate-400 hover:text-rose-600 rounded transition"
                                title="Hapus Agenda"
                                data-id="<?= $task['id'] ?>"
                                data-title="<?= e($task['title'] ?? '') ?>">
                            <i class="fa-regular fa-trash-can text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Judul Tugas -->
                <h4 class="text-xs sm:text-sm font-semibold text-slate-900 leading-snug mb-1.5 <?= $isDone ? 'line-through text-slate-400' : '' ?>">
                    <?= e($task['title'] ?? '') ?>
                </h4>

                <!-- Deskripsi Singkat -->
                <?php if (!empty($task['description'])): ?>
                <p class="text-[11px] text-slate-500 mb-3 line-clamp-2 leading-relaxed <?= $isDone ? 'line-through text-slate-400' : '' ?>">
                    <?= e($task['description']) ?>
                </p>
                <?php endif; ?>

                <!-- Baris Bawah: Prioritas & Tenggat Waktu & Jam -->
                <div class="flex flex-wrap items-center justify-between gap-1.5 pt-2 border-t border-slate-100 text-[10px]">
                    <!-- Prioritas -->
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border <?= $prio['bg'] ?> font-medium">
                        <span class="w-1.5 h-1.5 rounded-full mr-1 <?= $prio['dot'] ?>"></span>
                        <?= $prio['label'] ?>
                    </span>

                    <div class="flex flex-wrap items-center gap-1">
                        <!-- Peringatan Deadline Mepet / Terlambat -->
                        <?php if ($deadlineStatus): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md border text-[9px] <?= $deadlineStatus['badge_class'] ?> <?= !empty($deadlineStatus['pulse']) ? 'priority-high-pulse' : '' ?>">
                            <i class="<?= $deadlineStatus['icon'] ?> mr-1"></i>
                            <?= $deadlineStatus['label'] ?>
                        </span>
                        <?php endif; ?>

                        <!-- Tanggal Jatuh Tempo -->
                        <?php if ($dueBadge): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md <?= $dueBadge['color'] ?>">
                            <i class="fa-regular fa-calendar mr-1"></i>
                            <?= $dueBadge['text'] ?>
                        </span>
                        <?php endif; ?>

                        <!-- Jam Mulai & Deadline -->
                        <?php $timeRange = formatRentangWaktu($task['start_time'] ?? null, $task['due_time'] ?? null); ?>
                        <?php if ($timeRange): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                            <i class="fa-regular fa-clock mr-1 text-indigo-500"></i>
                            <?= $timeRange ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>

        </div>
    </div>
    <?php endforeach; ?>
</div>
