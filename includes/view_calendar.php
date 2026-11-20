<?php
// ==============================================================================
// FILE: includes/view_calendar.php
// FUNGSI: Menampilkan Kalender Agenda Interaktif secara Real-Time.
//         Mendukung SEMUA BULAN dan SEMUA TAHUN di dunia nyata.
//         Pengguna dapat bernavigasi ke bulan/tahun mana pun dan menambahkan
//         agenda langsung dengan mengeklik tanggal pada kalender!
// ==============================================================================

require_once __DIR__ . '/../config/database.php';
$tasks = $tasks ?? [];

// 1. Ambil Parameter Bulan dan Tahun (Default ke Bulan & Tahun Real-Time Saat Ini)
$currentRealMonth = (int)date('n');
$currentRealYear  = (int)date('Y');
$currentRealDay   = (int)date('j');

$calMonth = isset($_GET['month']) ? (int)$_GET['month'] : $currentRealMonth;
$calYear  = isset($_GET['year'])  ? (int)$_GET['year']  : $currentRealYear;

// Normalisasi jika bulan kurang dari 1 atau lebih dari 12
if ($calMonth < 1) {
    $calMonth = 12;
    $calYear--;
} elseif ($calMonth > 12) {
    $calMonth = 1;
    $calYear++;
}

// 2. Hitung Bulan Sebelumnya dan Bulan Berikutnya
$prevMonth = ($calMonth == 1) ? 12 : $calMonth - 1;
$prevYear  = ($calMonth == 1) ? $calYear - 1 : $calYear;

$nextMonth = ($calMonth == 12) ? 1 : $calMonth + 1;
$nextYear  = ($calMonth == 12) ? $calYear + 1 : $calYear;

// 3. Kalkulasi Hari pada Kalender
$firstDayTimestamp = mktime(0, 0, 0, $calMonth, 1, $calYear);
$daysInMonth       = (int)date('t', $firstDayTimestamp);
$firstDayOfWeek    = (int)date('w', $firstDayTimestamp); // 0 (Minggu) sampai 6 (Sabtu)

// Hitung hari bulan sebelumnya untuk mengisi grid awal
$prevMonthTimestamp = mktime(0, 0, 0, $prevMonth, 1, $prevYear);
$daysInPrevMonth    = (int)date('t', $prevMonthTimestamp);

// Nama-nama Bulan Bahasa Indonesia
$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// 4. Petakan Agenda / Tugas Berdasarkan Tanggal (due_date)
$agendaByDate = [];
foreach ($tasks as $task) {
    if (!empty($task['due_date'])) {
        $agendaByDate[$task['due_date']][] = $task;
    }
}

// Helper URL untuk mempertahankan filter
$baseParams = [
    'view'     => 'calendar',
    'category' => $currentCategory ?? 'all',
    'priority' => $currentPriority ?? 'all',
    'q'        => $searchQuery ?? ''
];
function buildCalUrl($params, $month, $year) {
    $p = array_merge($params, ['month' => $month, 'year' => $year]);
    return '?' . http_build_query($p);
}
?>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
    
    <!-- ============================================================== -->
    <!-- HEADER NAVIGASI KALENDER                                       -->
    <!-- ============================================================== -->
    <div class="p-4 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Bagian Kiri: Judul Bulan & Tahun Aktif -->
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
                <i class="fa-regular fa-calendar-days"></i>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-800">
                    <?= $namaBulan[$calMonth] ?> <?= $calYear ?>
                </h2>
                <p class="text-xs text-slate-500">
                    Total <?= count(array_filter($tasks, fn($t) => !empty($t['due_date']) && date('n', strtotime($t['due_date'])) == $calMonth && date('Y', strtotime($t['due_date'])) == $calYear)) ?> agenda terjadwal di bulan ini
                </p>
            </div>
        </div>

        <!-- Bagian Kanan: Kontrol Navigasi Bulan, Tahun & Hari Ini -->
        <div class="flex flex-wrap items-center gap-2">
            
            <!-- Tombol Bulan Sebelumnya -->
            <a href="<?= buildCalUrl($baseParams, $prevMonth, $prevYear) ?>" 
               title="Bulan Sebelumnya (<?= $namaBulan[$prevMonth] ?> <?= $prevYear ?>)"
               class="p-2 w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-600 hover:text-brand-600 hover:border-brand-300 flex items-center justify-center text-xs transition shadow-sm">
                <i class="fa-solid fa-chevron-left"></i>
            </a>

            <!-- Dropdown Pilihan Bulan & Tahun Cepat -->
            <form method="GET" class="inline-flex items-center space-x-1.5 m-0">
                <input type="hidden" name="view" value="calendar">
                <input type="hidden" name="category" value="<?= e($currentCategory ?? 'all') ?>">
                <input type="hidden" name="priority" value="<?= e($currentPriority ?? 'all') ?>">
                <input type="hidden" name="q" value="<?= e($searchQuery ?? '') ?>">

                <!-- Pilih Bulan -->
                <select name="month" onchange="this.form.submit()" 
                        class="bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-brand-500 focus:outline-none transition shadow-sm">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $calMonth ? 'selected' : '' ?>>
                        <?= $namaBulan[$m] ?>
                    </option>
                    <?php endfor; ?>
                </select>

                <!-- Input / Pilih Tahun (Bisa Semua Tahun Real-Time!) -->
                <input type="number" name="year" value="<?= $calYear ?>" min="1900" max="2100" 
                       onchange="this.form.submit()"
                       class="w-20 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-brand-500 focus:outline-none transition shadow-sm text-center">
            </form>

            <!-- Tombol Bulan Berikutnya -->
            <a href="<?= buildCalUrl($baseParams, $nextMonth, $nextYear) ?>" 
               title="Bulan Berikutnya (<?= $namaBulan[$nextMonth] ?> <?= $nextYear ?>)"
               class="p-2 w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-600 hover:text-brand-600 hover:border-brand-300 flex items-center justify-center text-xs transition shadow-sm">
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            <!-- Tombol Loncat ke Hari Ini (Real-Time) -->
            <a href="<?= buildCalUrl($baseParams, $currentRealMonth, $currentRealYear) ?>" 
               class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-sm flex items-center space-x-1.5">
                <i class="fa-regular fa-clock text-brand-600"></i>
                <span>Hari Ini</span>
            </a>

        </div>

    </div>

    <!-- ============================================================== -->
    <!-- GRID KALENDER BULANAN                                          -->
    <!-- ============================================================== -->
    <div class="w-full overflow-x-auto">
        <div class="min-w-[700px]">
            
            <!-- Baris Hari dalam Seminggu (Header) -->
            <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50 text-center text-xs font-bold py-3 text-slate-600 uppercase tracking-wider">
                <div class="text-rose-500">Minggu</div>
                <div>Senin</div>
                <div>Selasa</div>
                <div>Rabu</div>
                <div>Kamis</div>
                <div>Jumat</div>
                <div>Sabtu</div>
            </div>

            <!-- Grid Tanggal / Kotak Hari -->
            <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100 bg-white text-xs">
                
                <!-- 1. Hari dari Bulan Sebelumnya (Trailing Days) -->
                <?php for ($i = $firstDayOfWeek - 1; $i >= 0; $i--): 
                    $prevDateNum = $daysInPrevMonth - $i;
                    $fullPrevDate = sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $prevDateNum);
                ?>
                <div class="min-h-[105px] p-2 bg-slate-50/40 text-slate-300 flex flex-col justify-between select-none">
                    <span class="font-medium text-[11px]"><?= $prevDateNum ?></span>
                </div>
                <?php endfor; ?>

                <!-- 2. Hari Bulan Aktif (1 s/d daysInMonth) -->
                <?php for ($day = 1; $day <= $daysInMonth; $day++): 
                    $currentFormattedDate = sprintf('%04d-%02d-%02d', $calYear, $calMonth, $day);
                    $isRealToday = ($day == $currentRealDay && $calMonth == $currentRealMonth && $calYear == $currentRealYear);
                    $dayOfWeek = date('w', mktime(0, 0, 0, $calMonth, $day, $calYear));
                    $isSunday = ($dayOfWeek == 0);
                    $agendasOnDate = $agendaByDate[$currentFormattedDate] ?? [];
                ?>
                <div class="min-h-[110px] p-2 flex flex-col justify-between group transition hover:bg-slate-50/70 relative <?= $isRealToday ? 'bg-brand-50/20' : '' ?>">
                    
                    <!-- Header Kotak Tanggal: Angka Tanggal & Tombol Tambah Cepat -->
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold <?= $isRealToday ? 'bg-brand-600 text-white font-bold shadow-sm' : ($isSunday ? 'text-rose-600' : 'text-slate-700') ?>">
                            <?= $day ?>
                        </span>

                        <!-- Tombol Tambah Agenda Khusus Tanggal Ini (Muncul saat di-hover) -->
                        <button type="button" 
                                class="btn-add-agenda-date opacity-0 group-hover:opacity-100 w-5 h-5 rounded-md bg-brand-50 hover:bg-brand-600 text-brand-600 hover:text-white flex items-center justify-center text-[10px] transition"
                                title="Tambah agenda pada <?= $day ?> <?= $namaBulan[$calMonth] ?> <?= $calYear ?>"
                                data-date="<?= $currentFormattedDate ?>">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>

                    <!-- Daftar Agenda pada Tanggal Ini -->
                    <div class="space-y-1 flex-1 overflow-y-auto max-h-[85px]">
                        <?php 
                        $displayLimit = 3;
                        $agendaCount = count($agendasOnDate);
                        foreach (array_slice($agendasOnDate, 0, $displayLimit) as $agenda): 
                            $isDone = (($agenda['status'] ?? 'todo') === 'completed');
                            $timeStr = formatRentangWaktu($agenda['start_time'] ?? null, $agenda['due_time'] ?? null);
                            $prioColors = [
                                'high'   => 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100',
                                'medium' => 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100',
                                'low'    => 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                            ];
                            $chipColor = $isDone ? 'bg-slate-100 text-slate-400 border-slate-200 line-through' : ($prioColors[$agenda['priority'] ?? 'medium'] ?? 'bg-slate-100 text-slate-700 border-slate-200');
                        ?>
                        <!-- Chip Agenda (Klik untuk Edit) -->
                        <div class="btn-open-edit cursor-pointer px-1.5 py-1 rounded-md border text-[10px] font-medium truncate transition <?= $chipColor ?>"
                             title="<?= e($agenda['title']) ?><?= $timeStr ? " ($timeStr)" : "" ?> (Klik untuk melihat/edit)"
                             data-id="<?= $agenda['id'] ?>"
                             data-title="<?= e($agenda['title']) ?>"
                             data-description="<?= e($agenda['description'] ?? '') ?>"
                             data-category="<?= e($agenda['category'] ?? 'Umum') ?>"
                             data-priority="<?= e($agenda['priority'] ?? 'medium') ?>"
                             data-status="<?= e($agenda['status'] ?? 'todo') ?>"
                             data-due-date="<?= e($agenda['due_date'] ?? '') ?>"
                             data-start-time="<?= e($agenda['start_time'] ?? '') ?>"
                             data-due-time="<?= e($agenda['due_time'] ?? '') ?>">
                            <?php if ($isDone): ?>
                            <i class="fa-solid fa-check text-[9px] mr-1 text-emerald-600"></i>
                            <?php else: ?>
                            <i class="fa-solid fa-circle text-[6px] mr-1 opacity-70"></i>
                            <?php endif; ?>
                            <?php if ($timeStr): ?>
                            <span class="font-bold opacity-80 mr-0.5 text-[9px]"><?= $timeStr ?></span>
                            <?php endif; ?>
                            <span><?= e($agenda['title']) ?></span>
                        </div>
                        <?php endforeach; ?>

                        <!-- Jika lebih dari limit tampilan -->
                        <?php if ($agendaCount > $displayLimit): ?>
                        <div class="text-[10px] text-brand-600 font-semibold px-1">
                            +<?= $agendaCount - $displayLimit ?> agenda lainnya
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endfor; ?>

                <!-- 3. Hari Bulan Berikutnya (Leading Days untuk melengkapi grid) -->
                <?php 
                $totalCells = $firstDayOfWeek + $daysInMonth;
                $remainingCells = (7 - ($totalCells % 7)) % 7;
                for ($nextDay = 1; $nextDay <= $remainingCells; $nextDay++): 
                ?>
                <div class="min-h-[105px] p-2 bg-slate-50/40 text-slate-300 flex flex-col justify-between select-none">
                    <span class="font-medium text-[11px]"><?= $nextDay ?></span>
                </div>
                <?php endfor; ?>

            </div>

        </div>
    </div>

</div>
