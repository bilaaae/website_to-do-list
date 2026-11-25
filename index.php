<?php
// ==============================================================================
// FILE: index.php
// FUNGSI UTAMA: Titik Masuk Utama (Main Entry Point & Dashboard) Aplikasi TaskFlow
//
// 🎓 PANDUAN BELAJAR UKK:
// 1. File ini memproses logika backend utama: membaca data tugas dari database MySQL,
//    menghitung statistik progres, memfilter berdasarkan kategori/prioritas, dan pengurutan deadline.
// 2. Kode antarmuka (UI) dipecah menjadi file-file kecil (modular includes) di folder 'includes/'
//    seperti sidebar, toolbar, kanban, list, dan kalender agar kode rapi dan mudah diuji.
// ==============================================================================

// 1. Muat Konfigurasi Database dan Helper
require_once __DIR__ . '/config/database.php';

// 🎯 [ROUTE GUARD]: Memastikan hanya pengguna yang sudah login yang bisa mengakses dashboard
// Jika belum login, fungsi ini otomatis me-redirect ke login.php
requireLogin();

// 💡 [DATA USER AKTIF]: Ambil profil akun pengguna yang sedang login dari session
$currentUser   = getCurrentUser();
$currentUserId = (int)($currentUser['id'] ?? 1);

// Cek koneksi ke database MySQL
$pdo = getDBConnection();
$dbError = ($pdo === null);

// Inisialisasi variabel dasar aplikasi
$tasks = [];
$stats = [
    'total'       => 0,
    'todo'        => 0,
    'in_progress' => 0,
    'completed'   => 0,
    'percent'     => 0
];
$categories = ['Umum', 'Pekerjaan', 'Pribadi', 'Belajar', 'Keuangan', 'Kesehatan', 'Organisasi', 'Magang PKL'];

// 🎯 [MENANGKAP PARAMETER URL / QUERY STRING]:
// Digunakan untuk navigasi mode, pagination bulan kalender, filter kategori, prioritas, sorting, dan live search
$viewMode        = $_GET['view'] ?? 'kanban';          // Mode tampilan: 'kanban', 'list', atau 'calendar'
$calMonth        = (int)($_GET['month'] ?? date('n')); // Bulan untuk kalender real-time (1-12)
$calYear         = (int)($_GET['year'] ?? date('Y'));  // Tahun untuk kalender real-time (contoh: 2026)
$currentCategory = $_GET['category'] ?? 'all';        // Filter kategori terpilih ('all' atau nama kategori)
$currentPriority = $_GET['priority'] ?? 'all';        // Filter prioritas terpilih ('all', 'low', 'medium', 'high')
$currentSort     = $_GET['sort'] ?? 'deadline_asc';    // Pengurutan tugas
$searchQuery     = trim($_GET['q'] ?? '');            // Kata kunci pencarian agenda
$urgentTasks     = [];                                // Menampung tugas dengan deadline mepet/terlambat

if (!$dbError) {
    // --------------------------------------------------------------------------
    // A. READ: Ambil Data Statistik Tugas Khusus Pengguna Aktif (Multi-User Scoped)
    // --------------------------------------------------------------------------
    // 🎯 [FUNGSI]: Menghitung total tugas, status tugas, dan persentase progres secara real-time di MySQL
    // 💡 [CARA KERJA]: Menggunakan agregasi COUNT() dan SUM(CASE WHEN ...) dalam satu query efisien
    // ✏️ [TIPS MENGUBAH / UJIAN UKK]:
    // Filter 'WHERE user_id = :uid' menjamin data tugas antar akun tidak akan tercampur (Isolasi Data).
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
        // Hitung persentase tugas selesai (0% jika total masih 0 untuk mencegah pembagian dengan nol)
        $stats['percent']     = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;
    }

    // --------------------------------------------------------------------------
    // B. READ: Ambil Daftar Kategori Unik Milik Pengguna Ini
    // --------------------------------------------------------------------------
    // 💡 [CARA KERJA]: Mengambil kategori yang pernah dibuat user dari database
    // lalu digabungkan dengan kategori default agar pilihan di sidebar & modal selalu lengkap
    $catStmt = $pdo->prepare("
        SELECT DISTINCT category 
        FROM tasks 
        WHERE user_id = :uid AND category IS NOT NULL AND category != ''
    ");
    $catStmt->execute([':uid' => $currentUserId]);
    $existingCats = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    $categories = array_unique(array_merge($categories, $existingCats));

    // --------------------------------------------------------------------------
    // C. READ: Query Dinamis Pengambilan Daftar Tugas Sesuai Filter
    // --------------------------------------------------------------------------
    // 🎯 [FUNGSI]: Menyaring tugas berdasarkan kategori, prioritas, pencarian, dan pengurutan
    // 💡 [CARA KERJA]: Menyusun klausa SQL 'WHERE' secara bertahap menggunakan parameter binding PDO
    $sql = "SELECT * FROM tasks WHERE user_id = :uid";
    $params = [':uid' => $currentUserId];

    // 1. Filter Kategori
    if ($currentCategory !== 'all' && !empty($currentCategory)) {
        $sql .= " AND category = :category";
        $params[':category'] = $currentCategory;
    }

    // 2. Filter Prioritas (Tinggi, Sedang, Rendah)
    if ($currentPriority !== 'all' && !empty($currentPriority)) {
        $sql .= " AND priority = :priority";
        $params[':priority'] = $currentPriority;
    }

    // 3. Filter Pencarian Teks (Judul atau Deskripsi)
    if (!empty($searchQuery)) {
        $sql .= " AND (title LIKE :search1 OR description LIKE :search2)";
        $params[':search1'] = '%' . $searchQuery . '%';
        $params[':search2'] = '%' . $searchQuery . '%';
    }

    // 4. Pengurutan (Sorting) Data Sesuai Pilihan Pengguna
    // ✏️ [TIPS MENGUBAH / UJIAN UKK]:
    // Jika penguji meminta opsi sorting baru (misal: urutkan abjad judul A ke Z),
    // tambahkan case baru:
    // case 'title_asc':
    //     $sql .= " ORDER BY title ASC";
    //     break;
    switch ($currentSort) {
        case 'deadline_desc':
            // Deadline Terjauh ke Terdekat
            $sql .= " ORDER BY (due_date IS NULL) ASC, due_date DESC, (due_time IS NULL) ASC, due_time DESC, id DESC";
            break;
        case 'priority_desc':
            // Prioritas Tertinggi (Tinggi -> Sedang -> Rendah)
            $sql .= " ORDER BY CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END ASC, (due_date IS NULL) ASC, due_date ASC, id DESC";
            break;
        case 'created_desc':
            // Tugas Terbaru Dibuat
            $sql .= " ORDER BY id DESC";
            break;
        case 'deadline_asc':
        default:
            // Deadline Terdekat ke Terjauh (Paling Mendesak Tampil Teratas)
            $sql .= " ORDER BY (due_date IS NULL) ASC, due_date ASC, (due_time IS NULL) ASC, due_time ASC, id ASC";
            break;
    }

    // Eksekusi query dengan aman
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    // --------------------------------------------------------------------------
    // D. DETEKSI TUGAS DENGAN DEADLINE MEPET / TERLAMBAT (UNTUK NOTIFIKASI BELL)
    // --------------------------------------------------------------------------
    // 💡 [CARA KERJA]: Melakukan looping untuk mengecek sisa waktu setiap tugas yang belum selesai.
    // Jika sisa waktu <= 6 jam atau terlambat, dimasukkan ke array $urgentTasks untuk lonceng notifikasi header.
    foreach ($tasks as $t) {
        $isDone = (($t['status'] ?? 'todo') === 'completed');
        $deadlineInfo = getDeadlineStatus($t['due_date'] ?? null, $t['due_time'] ?? null, $isDone);
        if ($deadlineInfo && !empty($deadlineInfo['is_urgent'])) {
            $t['deadline_info'] = $deadlineInfo;
            $urgentTasks[] = $t;
        }
    }
}

// Ambil pesan notifikasi flash jika ada (misal: "Agenda berhasil ditambahkan")
$flash   = getFlash();
// Ambil tanggal hari ini dalam format Bahasa Indonesia
$hariIni = getHariIniIndo();

// ==============================================================================
// 2. PEMANGGILAN KOMPONEN MODULAR TAMPILAN (INCLUDES)
// ==============================================================================

// 1. Header (HTML Head, Navbar Atas, Profil Pengguna, dan Flash Alert)
require_once __DIR__ . '/includes/header.php';

// Jika database MySQL belum aktif, tampilkan panduan aktivasi Laragon yang ramah pemula
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
?>

<!-- ============================================================================ -->
<!-- 3. STRUKTUR TATA LETAK 2 KOLOM (GRID RESPONSIVE TAILWIND CSS)               -->
<!-- - Kolom Kiri  : Sidebar Navigasi Menu & Filter Kategori                     -->
<!-- - Kolom Kanan : Kartu Statistik, Toolbar Filter, dan Konten Utama Tugas      -->
<!-- ============================================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">

    <!-- 1. SIDEBAR KIRI (NAVIGASI & FILTER KATEGORI) -->
    <div class="lg:col-span-1">
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    </div>

    <!-- 2. KONTEN KANAN (STATISTIK, TOOLBAR LAMA, & KONTEN TUGAS) -->
    <div class="lg:col-span-3 space-y-6">
        <?php
        // A. Kartu Statistik Produktivitas (Total Tugas, Belum Mulai, Dalam Proses, Selesai)
        require_once __DIR__ . '/includes/stats.php';

        // B. Bilah Alat (Toolbar Filter, Mode Tampilan, Pengurutan, dan Pencarian)
        require_once __DIR__ . '/includes/toolbar.php';

        // C. Tampilan Konten Tugas Sesuai Mode yang Dipilih Pengguna:
        //    - 'calendar': Tampilan Kalender Bulanan Interaktif
        //    - 'list'    : Tampilan Tabel / Daftar Baris Ringkas
        //    - default   : Papan Kanban Interaktif (Bisa Drag & Drop)
        if ($viewMode === 'calendar') {
            require_once __DIR__ . '/includes/view_calendar.php';
        } elseif ($viewMode === 'list') {
            require_once __DIR__ . '/includes/view_list.php';
        } else {
            require_once __DIR__ . '/includes/view_kanban.php';
        }
        ?>
    </div>

</div>

<?php
// 4. Komponen Pop-up Modal (Tambah Agenda, Edit Agenda, dan Konfirmasi Hapus)
require_once __DIR__ . '/includes/modals.php';

// 5. Footer (Penutup Kontainer, Copyright, dan Pemuatan File JavaScript)
require_once __DIR__ . '/includes/footer.php';
