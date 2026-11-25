<?php
// ==============================================================================
// FILE: config/database.php
// FUNGSI UTAMA:
// 1. Mengatur koneksi ke database MySQL menggunakan PDO (PHP Data Objects).
// 2. Mengelola autentikasi sesi pengguna (Login, User Session, Route Guard).
// 3. Menyediakan fungsi helper keamanan XSS sanitasi, format tanggal, dan hitung deadline.
//
// 🎓 PANDUAN BELAJAR UKK:
// - PDO dipilih karena mendukung Prepared Statements untuk mencegah SQL Injection.
// - Menggunakan arsitektur modular agar fungsi-fungsi ini bisa dipanggil dari mana saja.
// ==============================================================================

// Memulai session PHP jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ⏰ [ZONA WAKTU]: Mengatur zona waktu default ke Waktu Indonesia Barat (WIB / GMT+7)
// Agar fungsi date(), time(), dan strtotime() sesuai dengan jam lokal di Indonesia.
date_default_timezone_set('Asia/Jakarta');

// ------------------------------------------------------------------------------
// 1. KONFIGURASI KONEKSI DATABASE (Default Bawaan Laragon)
// ------------------------------------------------------------------------------
// ✏️ [TIPS UJIAN UKK]:
// Jika penguji meminta mengubah database atau jika port MySQL berbeda (misal 3307),
// atau jika MySQL memiliki password, cukup ubah konstanta di bawah ini:
define('DB_HOST', '127.0.0.1');   // Host database lokal (localhost)
define('DB_PORT', '3306');        // Port default MySQL di Laragon
define('DB_USER', 'root');        // Username default MySQL di Laragon
define('DB_PASS', '');            // Password default MySQL di Laragon (kosong)
define('DB_NAME', 'todolist_db'); // Nama database aplikasi

/**
 * 🎯 [FUNGSI]: getDBConnection()
 * Membuka dan mengembalikan objek koneksi PDO ke MySQL menggunakan Singleton Pattern.
 *
 * 💡 [CARA KERJA]:
 * - Menggunakan variabel 'static $pdo' agar koneksi hanya dibuat 1 kali per request (hemat memori).
 * - Menentukan DSN (Data Source Name) dengan charset utf8mb4 agar mendukung emoji & karakter global.
 * - Jika database 'todolist_db' belum ada, secara cerdas menangkap error (code 1049) dan otomatis
 *   menjalankan query "CREATE DATABASE IF NOT EXISTS" sehingga aplikasi tidak error saat pertama kali di-run.
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Jika penguji bertanya: "Kenapa pakai PDO dibanding mysqli_connect()?"
 * Jawab: "PDO lebih aman karena prepared statements bawaan, mendukung banyak jenis database,
 * dan penanganan error menggunakan Exception (Try-Catch) yang rapi."
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Error dilempar sebagai Exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Hasil query berupa array asosiatif
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Prepared statement asli dari MySQL
        ]);
        
        // Samakan zona waktu koneksi MySQL ke GMT+7 (WIB)
        $pdo->exec("SET time_zone = '+07:00'");
        
        // Buat tabel users & tasks otomatis jika belum tersedia
        initDatabaseTables($pdo);
        return $pdo;
    } catch (PDOException $e) {
        // Jika database belum ada (Error 1049: Unknown database), buat otomatis
        if ($e->getCode() == 1049 || stripos($e->getMessage(), 'Unknown database') !== false) {
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Sambungkan ulang setelah database berhasil dibuat
                $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                initDatabaseTables($pdo);
                return $pdo;
            } catch (PDOException $createEx) {
                error_log("Gagal membuat database otomatis: " . $createEx->getMessage());
                return null;
            }
        }

        error_log("Koneksi database gagal: " . $e->getMessage());
        return null;
    }
}

/**
 * 🎯 [FUNGSI]: initDatabaseTables($pdo)
 * Inisialisasi struktur database: membuat tabel 'users' dan 'tasks', migrasi kolom, serta seed data demo.
 *
 * 💡 [CARA KERJA]:
 * - Menjalankan CREATE TABLE IF NOT EXISTS untuk tabel 'users' dan 'tasks'.
 * - Mengecek keberadaan kolom baru (user_id, status, start_time, due_time) via query SHOW COLUMNS.
 * - Membuat 1 akun penguji bawaan (username: penguji / password: password123) untuk kemudahan demo.
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Jika penguji meminta menambahkan kolom baru (misal: 'attachment' atau 'keterangan_tambahan'),
 * tambahkan definisi kolomnya di tabel dan tambahkan blok migrasi ALTER TABLE di bawah ini.
 */
function initDatabaseTables($pdo) {
    if (!$pdo) return;

    // 1. Buat Tabel `users` untuk Autentikasi Pengguna
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createUsersTable);

    // Buat akun demo bawaan jika tabel users masih kosong
    $userCount = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($userCount == 0) {
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmtUser = $pdo->prepare("
            INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`)
            VALUES (1, 'Penguji UKK', 'penguji', 'penguji@taskflow.id', :pwd)
        ");
        $stmtUser->execute([':pwd' => $hashedPassword]);
    }

    // 2. Buat Tabel `tasks`
    $createTasksTable = "
    CREATE TABLE IF NOT EXISTS `tasks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `category` VARCHAR(50) NOT NULL DEFAULT 'Umum',
        `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
        `status` ENUM('todo', 'in_progress', 'completed') NOT NULL DEFAULT 'todo',
        `due_date` DATE NULL,
        `start_time` TIME NULL,
        `due_time` TIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createTasksTable);

    // Migrasi kolom 'user_id' pada tabel tasks jika belum ada
    try {
        $checkUserId = $pdo->query("SHOW COLUMNS FROM `tasks` LIKE 'user_id'")->fetch();
        if (!$checkUserId) {
            $pdo->exec("ALTER TABLE `tasks` ADD COLUMN `user_id` INT NULL AFTER `id`");
            $pdo->exec("UPDATE `tasks` SET `user_id` = 1 WHERE `user_id` IS NULL");
        }
    } catch (Exception $ex) {}

    // Migrasi kolom 'status' jika belum ada
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM `tasks` LIKE 'status'")->fetch();
        if (!$checkCol) {
            $pdo->exec("ALTER TABLE `tasks` ADD COLUMN `status` ENUM('todo', 'in_progress', 'completed') NOT NULL DEFAULT 'todo' AFTER `priority`");
        }
    } catch (Exception $ex) {}

    // Migrasi kolom 'start_time' jika belum ada
    try {
        $checkStartTime = $pdo->query("SHOW COLUMNS FROM `tasks` LIKE 'start_time'")->fetch();
        if (!$checkStartTime) {
            $pdo->exec("ALTER TABLE `tasks` ADD COLUMN `start_time` TIME NULL AFTER `due_date`");
        }
    } catch (Exception $ex) {}

    // Migrasi kolom 'due_time' jika belum ada
    try {
        $checkDueTime = $pdo->query("SHOW COLUMNS FROM `tasks` LIKE 'due_time'")->fetch();
        if (!$checkDueTime) {
            $pdo->exec("ALTER TABLE `tasks` ADD COLUMN `due_time` TIME NULL AFTER `start_time`");
        }
    } catch (Exception $ex) {}
}

// ------------------------------------------------------------------------------
// 2. HELPER AUTENTIKASI PENGGUNA (SESSION MANAGEMENT & ROUTE GUARD)
// ------------------------------------------------------------------------------

/**
 * 🎯 [FUNGSI]: isLoggedIn()
 * Memeriksa apakah ada user yang sedang login di sesi browser saat ini.
 *
 * 💡 [CARA KERJA]:
 * Mengecek apakah variabel $_SESSION['user_id'] telah diset dan tidak kosong.
 * Mengembalikan true jika sudah login, atau false jika belum.
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Gunakan 'if (isLoggedIn()) { ... }' untuk menyembunyikan/menampilkan tombol tertentu.
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * 🎯 [FUNGSI]: getCurrentUser()
 * Mengambil array data profil pengguna yang sedang aktif dari Session.
 *
 * 💡 [CARA KERJA]:
 * Mengembalikan data yang disimpan saat proses login di auth.php:
 * ['id' => ..., 'name' => ..., 'username' => ..., 'email' => ...]
 */
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
}

/**
 * 🎯 [FUNGSI]: requireLogin()
 * Penjaga Gerbang Halaman (Route Guard / Middleware).
 *
 * 💡 [CARA KERJA]:
 * Memeriksa status login dengan isLoggedIn(). Jika pengunjung belum login,
 * otomatis diarahkan (redirect) ke halaman login.php dengan membawa pesan peringatan,
 * lalu script langsung dihentikan dengan 'exit;' agar konten rahasia tidak bocor.
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Wajib diletakkan di baris paling atas halaman apa pun yang ingin diproteksi (misal: index.php).
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            setFlash('danger', 'Silakan login terlebih dahulu untuk mengakses webiste.');
            header("Location: login.php");
            exit;
        }
    }
}

// ------------------------------------------------------------------------------
// 3. HELPER FORMAT DATA, KEAMANAN XSS & WAKTU
// ------------------------------------------------------------------------------

/**
 * 🎯 [FUNGSI]: e($string) - Singkatan dari Escape HTML
 * Mencegah celah keamanan XSS (Cross-Site Scripting) pada aplikasi web.
 *
 * 💡 [CARA KERJA]:
 * Membungkus fungsi bawaan PHP htmlspecialchars() dengan flag ENT_QUOTES dan UTF-8.
 * Mengubah karakter khusus HTML berbahaya seperti '<', '>', '"', '&' menjadi entitas aman:
 * Contoh: <script> diubah menjadi &lt;script&gt; sehingga tidak dieksekusi oleh browser.
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Jawaban wajib untuk pertanyaan penguji tentang keamanan:
 * "Setiap data dari input user yang dicetak ke HTML selalu dilewatkan ke fungsi e()!"
 */
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * 🎯 [FUNGSI]: formatTanggalIndo($dateStr)
 * Mengubah format tanggal database (YYYY-MM-DD) menjadi format Bahasa Indonesia (Contoh: '23 Nov 2026').
 *
 * 💡 [CARA KERJA]:
 * Menggunakan strtotime() untuk membaca tanggal, lalu memetakan angka bulan (1-12)
 * ke array singkatan bulan Indonesia ($bulan).
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Jika penguji meminta nama bulan ditulis lengkap (contoh: "23 November 2026"),
 * ubah isi array $bulan dari 'Nov' menjadi 'November'.
 */
if (!function_exists('formatTanggalIndo')) {
    function formatTanggalIndo($dateStr) {
        if (!$dateStr) return null;
        $bulan = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        $timestamp = strtotime($dateStr);
        $d = date('j', $timestamp);
        $m = $bulan[(int)date('n', $timestamp)];
        $y = date('Y', $timestamp);
        return "$d $m $y";
    }
}

/**
 * 🎯 [FUNGSI]: formatJam($timeStr)
 * Memotong format waktu database MySQL 'HH:MM:SS' menjadi 'HH:MM'.
 *
 * 💡 [CARA KERJA]:
 * Menggunakan substr($timeStr, 0, 5) untuk mengambil 5 karakter pertama saja (jam dan menit).
 * Contoh: '15:40:00' menjadi '15:40'.
 */
if (!function_exists('formatJam')) {
    function formatJam($timeStr) {
        if (empty($timeStr)) return null;
        return substr($timeStr, 0, 5);
    }
}

/**
 * 🎯 [FUNGSI]: formatRentangWaktu($startTime, $dueTime)
 * Menampilkan label waktu pengerjaan atau deadline agenda secara rapi di kartu tugas.
 *
 * 💡 [CARA KERJA]:
 * Karena aplikasi disederhanakan hanya menggunakan jam deadline, fungsi ini akan
 * mengembalikan jam deadline yang sudah diformat (contoh: '15:40').
 */
if (!function_exists('formatRentangWaktu')) {
    function formatRentangWaktu($startTime, $dueTime) {
        $start = formatJam($startTime);
        $due   = formatJam($dueTime);

        if ($start && $due) {
            return "$start - $due";
        } elseif ($due) {
            return $due;
        } elseif ($start) {
            return "Mulai $start";
        }
        return null;
    }
}

/**
 * 🎯 [FUNGSI]: getDeadlineStatus($dueDate, $dueTime, $isCompleted)
 * Menghitung sisa waktu pengerjaan tugas secara real-time dan memberikan badge status deadline.
 *
 * 💡 [CARA KERJA]:
 * 1. Jika tugas sudah 'completed' (selesai) atau tanggal deadline kosong, return null (tidak perlu badge urgensi).
 * 2. Menggabungkan tanggal dan jam deadline, lalu diubah ke unix timestamp dengan strtotime().
 * 3. Menghitung selisih waktu: $diffSeconds = $dueTimestamp - time().
 * 4. Logika Penentuan Badge:
 *    - Selisih < 0 detik           => 'Terlambat!' (badge merah berkedip/pulse)
 *    - Selisih <= 21600 detik (6 jam)=> 'Mepet!' (badge merah muda menyala dengan hitung mundur jam & menit)
 *    - Tanggal sama dengan hari ini => 'Hari Ini' (badge kuning/amber)
 *    - Tanggal besok (+1 hari)      => 'Besok' (badge biru muda)
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Jika penguji meminta: "Tolong ubah ambang batas waktu mepet dari 6 jam jadi 3 jam atau 12 jam":
 * - Untuk 3 jam : Ubah angka 21600 menjadi 10800 (3 jam x 3600 detik)
 * - Untuk 12 jam: Ubah angka 21600 menjadi 43200 (12 jam x 3600 detik)
 */
if (!function_exists('getDeadlineStatus')) {
    function getDeadlineStatus($dueDate, $dueTime, $isCompleted) {
        if ($isCompleted || empty($dueDate)) {
            return null;
        }

        $now = time();
        $timePart = !empty($dueTime) ? $dueTime : '23:59:59';
        $dueTimestamp = strtotime($dueDate . ' ' . $timePart);
        $diffSeconds = $dueTimestamp - $now;

        // Kategori 1: Sudah lewat waktu (Terlambat)
        if ($diffSeconds < 0) {
            return [
                'type'        => 'overdue',
                'label'       => 'Terlambat!',
                'badge_class' => 'bg-rose-100 text-rose-700 border-rose-300 font-bold',
                'pulse'       => true,
                'icon'        => 'fa-solid fa-triangle-exclamation',
                'is_urgent'   => true
            ];
        }

        // Kategori 2: Sangat mepet (Kurang dari atau sama dengan 6 jam = 21600 detik)
        if ($diffSeconds <= 21600) {
            $hoursLeft = floor($diffSeconds / 3600);
            $minsLeft  = floor(($diffSeconds % 3600) / 60);
            $label = $hoursLeft > 0 ? "Mepet! ($hoursLeft Jam $minsLeft Mnt Lagi)" : "Sangat Mepet! ($minsLeft Mnt Lagi)";
            return [
                'type'        => 'super_urgent',
                'label'       => $label,
                'badge_class' => 'bg-rose-50 text-rose-600 border-rose-200 font-semibold',
                'pulse'       => true,
                'icon'        => 'fa-solid fa-fire-flame-curved',
                'is_urgent'   => true
            ];
        }

        // Kategori 3: Jatuh tempo hari ini
        if ($dueDate === date('Y-m-d')) {
            return [
                'type'        => 'today',
                'label'       => 'Hari Ini',
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-300 font-semibold',
                'pulse'       => false,
                'icon'        => 'fa-solid fa-clock',
                'is_urgent'   => true
            ];
        }

        // Kategori 4: Jatuh tempo besok
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        if ($dueDate === $tomorrow) {
            return [
                'type'        => 'tomorrow',
                'label'       => 'Besok',
                'badge_class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'pulse'       => false,
                'icon'        => 'fa-regular fa-calendar-check',
                'is_urgent'   => false
            ];
        }

        return null;
    }
}

/**
 * 🎯 [FUNGSI]: getHariIniIndo()
 * Menghasilkan teks nama hari, tanggal, bulan, dan tahun saat ini dalam Bahasa Indonesia.
 * Contoh hasil: "Senin, 23 November 2026"
 *
 * 💡 [CARA KERJA]:
 * Memetakan date('w') (0 untuk Minggu s.d. 6 untuk Sabtu) dan date('n') (1 s.d. 12 untuk bulan).
 */
if (!function_exists('getHariIniIndo')) {
    function getHariIniIndo() {
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanIndo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $hariIndo[date('w')] . ', ' . date('j') . ' ' . $bulanIndo[(int)date('n')] . ' ' . date('Y');
    }
}

/**
 * 🎯 [FUNGSI]: setFlash($type, $message)
 * Menyimpan notifikasi pesan kilat (Flash Message) ke dalam Session.
 *
 * 💡 [CARA KERJA]:
 * Menyimpan array ['type' => $type, 'message' => $message] ke $_SESSION['flash'].
 * Tipe yang didukung:
 * - 'success': Warna hijau (tugas berhasil ditambah/diubah/dihapus)
 * - 'danger' : Warna merah (validasi gagal / error)
 * - 'warning': Warna kuning (peringatan)
 * - 'info'   : Warna biru (informasi logout, dll)
 *
 * ✏️ [TIPS MENGUBAH / UJIAN UKK]:
 * Panggil setFlash('success', 'Pesan Anda') setiap kali menyelesaikan aksi CRUD berhasil.
 */
if (!function_exists('setFlash')) {
    function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message
        ];
    }
}

/**
 * 🎯 [FUNGSI]: getFlash()
 * Mengambil notifikasi pesan kilat (Flash Message) dan langsung menghapusnya dari Session.
 *
 * 💡 [CARA KERJA]:
 * Mengambil $_SESSION['flash'], lalu menjalankan unset($_SESSION['flash']) sehingga pesan
 * hanya muncul 1 KALI saja dan otomatis hilang saat halaman dimuat ulang (refresh).
 */
if (!function_exists('getFlash')) {
    function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
