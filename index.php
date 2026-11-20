<?php
// ==============================================================================
// FILE: index.php
// FUNGSI: Titik Masuk Utama (Main Entry Point) Aplikasi To-Do List UKK
//         File ini sengaja dibuat ringkas dan bersih dengan memanggil file-file
//         modular (includes) agar kode mudah dipelajari, dipresentasikan, dan diuji.
// ==============================================================================

// 1. Muat Konfigurasi Database dan Helper
require_once __DIR__ . '/config/database.php';

// Route Guard: Pastikan pengguna sudah terautentikasi (login)
requireLogin();

// Ambil data akun pengguna yang sedang login
$currentUser   = getCurrentUser();
$currentUserId = (int)($currentUser['id'] ?? 1);

// Cek koneksi ke database
$pdo = getDBConnection();
$dbError = ($pdo === null);

// Inisialisasi variabel dasar
$tasks = [];
$stats = [
    'total'       => 0,
    'todo'        => 0,
    'in_progress' => 0,
    'completed'   => 0,
    'percent'     => 0
];
$categories = ['Umum', 'Pekerjaan', 'Pribadi', 'Belajar', 'Keuangan', 'Kesehatan'];

// Tangkap parameter filter dari URL (GET Request)
$viewMode        = $_GET['view'] ?? 'kanban';          // 'kanban', 'list', atau 'calendar'
$calMonth        = (int)($_GET['month'] ?? date('n')); // Bulan untuk kalender real-time
$calYear         = (int)($_GET['year'] ?? date('Y'));  // Tahun untuk kalender real-time
$currentCategory = $_GET['category'] ?? 'all';        // Kategori terpilih
$currentPriority = $_GET['priority'] ?? 'all';        // Prioritas terpilih
$currentSort     = $_GET['sort'] ?? 'deadline_asc';    // Pengurutan: deadline_asc, deadline_desc, priority_desc, created_desc
$searchQuery     = trim($_GET['q'] ?? '');            // Kata kunci pencarian
$urgentTasks     = [];                                // Menampung tugas yang deadline mepet/terlambat

if (!$dbError) {
    // --------------------------------------------------------------------------
    // A. READ: Ambil Data Statistik Tugas Khusus Pengguna Aktif (Multi-User Scoped)
    // --------------------------------------------------------------------------
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM tasks
        WHERE user_id = :uid
    ");
    $statsStmt->execute([':uid' => $currentUserId]);
    $statsData = $statsStmt->fetch();

    if ($statsData) {
        $stats['total']       = (int)($statsData['total'] ?? 0);
        $stats['todo']        = (int)($statsData['todo'] ?? 0);
        $stats['in_progress'] = (int)($statsData['in_progress'] ?? 0);
        $stats['completed']   = (int)($statsData['completed'] ?? 0);
        $stats['percent']     = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;
    }

    // --------------------------------------------------------------------------
    // B. READ: Ambil Daftar Kategori Unik Milik Pengguna Ini
    // --------------------------------------------------------------------------
    $catStmt = $pdo->prepare("
        SELECT DISTINCT category 
        FROM tasks 
        WHERE user_id = :uid AND category IS NOT NULL AND category != ''
    ");
    $catStmt->execute([':uid' => $currentUserId]);
    $existingCats = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    $categories = array_unique(array_merge($categories, $existingCats));

    // --------------------------------------------------------------------------
    // C. READ: Query Pengambilan Daftar Tugas Milik Pengguna Sesuai Filter
    // --------------------------------------------------------------------------
    $sql = "SELECT * FROM tasks WHERE user_id = :uid";
    $params = [':uid' => $currentUserId];

    // Filter Kategori
    if ($currentCategory !== 'all' && !empty($currentCategory)) {
        $sql .= " AND category = :category";
        $params[':category'] = $currentCategory;
    }

    // Filter Prioritas
    if ($currentPriority !== 'all' && !empty($currentPriority)) {
        $sql .= " AND priority = :priority";
        $params[':priority'] = $currentPriority;
    }

    // Filter Pencarian (Judul atau Deskripsi)
    if (!empty($searchQuery)) {
        $sql .= " AND (title LIKE :search1 OR description LIKE :search2)";
        $params[':search1'] = '%' . $searchQuery . '%';
        $params[':search2'] = '%' . $searchQuery . '%';
    }

    // Pengurutan (Sorting) data sesuai pilihan pengguna
    switch ($currentSort) {
        case 'deadline_desc':
            // Terjauh ke Terdekat
            $sql .= " ORDER BY (due_date IS NULL) ASC, due_date DESC, (due_time IS NULL) ASC, due_time DESC, id DESC";
            break;
        case 'priority_desc':
            // Prioritas Tertinggi (Tinggi -> Sedang -> Rendah)
            $sql .= " ORDER BY CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END ASC, (due_date IS NULL) ASC, due_date ASC, id DESC";
            break;
        case 'created_desc':
            // Terbaru Dibuat
            $sql .= " ORDER BY id DESC";
            break;
        case 'deadline_asc':
        default:
            // Terdekat ke Terjauh (Default)
            $sql .= " ORDER BY (due_date IS NULL) ASC, due_date ASC, (due_time IS NULL) ASC, due_time ASC, id ASC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    // --------------------------------------------------------------------------
    // D. DETEKSI TUGAS DENGAN DEADLINE MEPET ATAU TERLAMBAT (UNTUK NOTIFIKASI)
    // --------------------------------------------------------------------------
    foreach ($tasks as $t) {
        $isDone = (($t['status'] ?? 'todo') === 'completed');
        $deadlineInfo = getDeadlineStatus($t['due_date'] ?? null, $t['due_time'] ?? null, $isDone);
        if ($deadlineInfo && !empty($deadlineInfo['is_urgent'])) {
            $t['deadline_info'] = $deadlineInfo;
            $urgentTasks[] = $t;
        }
    }
}

$flash   = getFlash();
$hariIni = getHariIniIndo();

// ==============================================================================
// 2. PEMANGGILAN KOMPONEN MODULAR TAMPILAN (INCLUDES)
// ==============================================================================

// 1. Header (HTML Head, Navbar, Profil Pengguna, dan Notifikasi Flash)
require_once __DIR__ . '/includes/header.php';

// Jika database MySQL belum aktif, tampilkan panduan aktivasi Laragon
if ($dbError) {
    echo '
    <div class="bg-white rounded-2xl p-8 border border-rose-200 shadow-sm text-center max-w-2xl mx-auto my-12">
        <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="fa-solid fa-database"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-800 mb-2">MySQL di Laragon Belum Aktif</h2>
        <p class="text-slate-600 text-sm mb-6 leading-relaxed">
            Aplikasi belum dapat terhubung ke server database MySQL. Ikuti langkah berikut:
        </p>
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-left text-sm text-slate-700 space-y-2 mb-6 font-mono">
            <p>1. Buka aplikasi <strong>Laragon</strong>.</p>
            <p>2. Klik tombol <strong>"Start All"</strong> untuk menyalakan Apache & MySQL.</p>
            <p>3. Muat ulang (Refresh) halaman ini.</p>
        </div>
        <button onclick="window.location.reload()" class="inline-flex items-center space-x-2 bg-brand-600 hover:bg-brand-700 text-white font-medium text-sm px-6 py-2.5 rounded-xl shadow-md transition">
            <i class="fa-solid fa-rotate-right"></i>
            <span>Muat Ulang Halaman</span>
        </button>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 2. Kartu Statistik Produktivitas
require_once __DIR__ . '/includes/stats.php';

// 3. Bilah Alat (Filter Kategori, Prioritas, Pencarian, & Ganti Mode Tampilan)
require_once __DIR__ . '/includes/toolbar.php';

// 4. Konten Tugas: Tampilan Papan Kanban (Drag & Drop), Tampilan Daftar (List), ATAU Kalender Agenda Real-Time
if ($viewMode === 'calendar') {
    require_once __DIR__ . '/includes/view_calendar.php';
} elseif ($viewMode === 'list') {
    require_once __DIR__ . '/includes/view_list.php';
} else {
    require_once __DIR__ . '/includes/view_kanban.php';
}

// 5. Komponen Modal (Pop-up Tambah, Edit, dan Hapus)
require_once __DIR__ . '/includes/modals.php';

// 6. Footer (Penutup Halaman & Pemanggilan Script JavaScript)
require_once __DIR__ . '/includes/footer.php';
