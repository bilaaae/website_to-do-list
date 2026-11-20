<?php
// ==============================================================================
// FILE: includes/stats.php
// FUNGSI: Menampilkan kartu ringkasan statistik tugas secara real-time.
//         Semua kartu kini memiliki warna tematik yang seragam dan konsisten:
//         - Total Tugas   : Biru / Indigo
//         - Belum Mulai   : Merah / Rose (Sesuai permintaan)
//         - Dalam Proses  : Kuning / Amber (Ikon statis, tidak berputar)
//         - Selesai       : Hijau / Emerald
// ==============================================================================

// Fallback variabel agar tidak muncul peringatan merah (Undefined variable) di editor/VS Code
$stats = $stats ?? [
    'total'       => 0,
    'todo'        => 0,
    'in_progress' => 0,
    'completed'   => 0,
    'percent'     => 0
];
?>
<section class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <!-- Card 1: Total Tugas (Biru / Indigo) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Tugas</p>
            <p class="text-2xl font-extrabold text-brand-600 mt-1"><?= $stats['total'] ?></p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
            <i class="fa-solid fa-list-check"></i>
        </div>
    </div>

    <!-- Card 2: Belum Mulai (Merah / Rose - Seragam Berwarna) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Belum Mulai</p>
            <p class="text-2xl font-extrabold text-rose-600 mt-1"><?= $stats['todo'] ?></p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
            <i class="fa-solid fa-circle-pause"></i>
        </div>
    </div>

    <!-- Card 3: Dalam Proses (Kuning / Amber - Ikon Diam / Tidak Berputar) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Dalam Proses</p>
            <p class="text-2xl font-extrabold text-amber-600 mt-1"><?= $stats['in_progress'] ?></p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
    </div>

    <!-- Card 4: Selesai & Progress Bar (Hijau / Emerald) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div class="flex-1 pr-2">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Selesai</p>
                <span class="text-xs font-bold text-emerald-600"><?= $stats['percent'] ?>%</span>
            </div>
            <p class="text-2xl font-extrabold text-emerald-600"><?= $stats['completed'] ?></p>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: <?= $stats['percent'] ?>%"></div>
            </div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg ml-2">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>
</section>
