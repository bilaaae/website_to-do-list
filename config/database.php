<?php
// ==============================================================================
// FILE: config/database.php
// FUNGSI: Mengatur koneksi ke database MySQL menggunakan PDO (PHP Data Objects),
//         mengelola autentikasi sesi pengguna (Login/Session Guard),
//         serta menyediakan fungsi helper keamanan dan format data.
// ==============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------------------
// 1. KONFIGURASI KONEKSI DATABASE (Default bawaan Laragon)
// ------------------------------------------------------------------------------
define('DB_HOST', '127.0.0.1');  // Host database lokal
define('DB_PORT', '3306');       // Port default MySQL
define('DB_USER', 'root');       // Username default MySQL di Laragon
define('DB_PASS', '');           // Password default MySQL di Laragon (kosong)
define('DB_NAME', 'todolist_db');// Nama database aplikasi

/**
 * Fungsi getDBConnection()
 * Mengambil objek koneksi PDO ke MySQL (Singleton Pattern)
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        
        initDatabaseTables($pdo);
        return $pdo;
    } catch (PDOException $e) {
        if ($e->getCode() == 1049 || stripos($e->getMessage(), 'Unknown database') !== false) {
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

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
 * Fungsi initDatabaseTables()
 * Membuat struktur tabel `users` dan `tasks`, serta melakukan migrasi kolom jika belum ada
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
    $defaultUserId = 1;
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

    // 3. Migrasi kolom 'user_id' pada tabel tasks jika belum ada
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

    // Jika tabel tasks kosong, tambahkan data contoh untuk akun demo (user_id = 1)
    $taskCount = $pdo->query("SELECT COUNT(*) FROM `tasks`")->fetchColumn();
    if ($taskCount == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO `tasks` (`user_id`, `title`, `description`, `category`, `priority`, `status`, `due_date`, `start_time`, `due_time`) 
            VALUES (:uid, :title, :description, :category, :priority, :status, :due_date, :start_time, :due_time)
        ");

        $samples = [
            [
                'uid'         => 1,
                'title'       => 'Merancang Desain Antarmuka Modern 🎨',
                'description' => 'Membuat Kanban board & Kalender real-time dengan Tailwind CSS.',
                'category'    => 'Pekerjaan',
                'priority'    => 'high',
                'status'      => 'in_progress',
                'due_date'    => date('Y-m-d'),
                'start_time'  => '08:30:00',
                'due_time'    => '11:00:00'
            ],
            [
                'uid'         => 1,
                'title'       => 'Rapat Evaluasi Proyek Mingguan ☕',
                'description' => 'Diskusi progres fitur baru bersama tim pengembang.',
                'category'    => 'Pekerjaan',
                'priority'    => 'medium',
                'status'      => 'todo',
                'due_date'    => date('Y-m-d'),
                'start_time'  => '13:00:00',
                'due_time'    => '14:30:00'
            ],
            [
                'uid'         => 1,
                'title'       => 'Membeli perlengkapan harian 🛒',
                'description' => 'Beli buku catatan, pulpen baru, dan camilan untuk belajar.',
                'category'    => 'Pribadi',
                'priority'    => 'low',
                'status'      => 'todo',
                'due_date'    => date('Y-m-d', strtotime('+2 days')),
                'start_time'  => '16:00:00',
                'due_time'    => '17:30:00'
            ]
        ];

        foreach ($samples as $s) {
            $stmt->execute([
                ':uid'         => $s['uid'],
                ':title'       => $s['title'],
                ':description' => $s['description'],
                ':category'    => $s['category'],
                ':priority'    => $s['priority'],
                ':status'      => $s['status'],
                ':due_date'    => $s['due_date'],
                ':start_time'  => $s['start_time'],
                ':due_time'    => $s['due_time']
            ]);
        }
    }
}

// ------------------------------------------------------------------------------
// 2. HELPER AUTENTIKASI PENGGUNA (SESSION MANAGEMENT)
// ------------------------------------------------------------------------------
if (!function_exists('isLoggedIn')) {
    /**
     * Memeriksa apakah pengguna sudah login
     */
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('getCurrentUser')) {
    /**
     * Mengambil data akun pengguna yang sedang aktif
     */
    function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('requireLogin')) {
    /**
     * Penjaga halaman (Route Guard)
     * Mengalihkan pengunjung ke login.php jika belum login
     */
    function requireLogin() {
        if (!isLoggedIn()) {
            setFlash('danger', 'Silakan login terlebih dahulu untuk mengakses aplikasi.');
            header("Location: login.php");
            exit;
        }
    }
}

// ------------------------------------------------------------------------------
// 3. HELPER FORMAT DATA & KEAMANAN
// ------------------------------------------------------------------------------
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

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

if (!function_exists('formatJam')) {
    function formatJam($timeStr) {
        if (empty($timeStr)) return null;
        return substr($timeStr, 0, 5);
    }
}

if (!function_exists('formatRentangWaktu')) {
    function formatRentangWaktu($startTime, $dueTime) {
        $start = formatJam($startTime);
        $due   = formatJam($dueTime);

        if ($start && $due) {
            return "$start - $due";
        } elseif ($start) {
            return "Mulai $start";
        } elseif ($due) {
            return "Deadline $due";
        }
        return null;
    }
}

if (!function_exists('getDeadlineStatus')) {
    function getDeadlineStatus($dueDate, $dueTime, $isCompleted) {
        if ($isCompleted || empty($dueDate)) {
            return null;
        }

        $now = time();
        $timePart = !empty($dueTime) ? $dueTime : '23:59:59';
        $dueTimestamp = strtotime($dueDate . ' ' . $timePart);
        $diffSeconds = $dueTimestamp - $now;

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

        if ($diffSeconds <= 86400 && date('Y-m-d', $dueTimestamp) === date('Y-m-d')) {
            return [
                'type'        => 'today',
                'label'       => 'Hari Ini',
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-300 font-semibold',
                'pulse'       => false,
                'icon'        => 'fa-solid fa-clock',
                'is_urgent'   => true
            ];
        }

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

if (!function_exists('getHariIniIndo')) {
    function getHariIniIndo() {
        $hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulanIndo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $hariIndo[date('w')] . ', ' . date('j') . ' ' . $bulanIndo[(int)date('n')] . ' ' . date('Y');
    }
}

if (!function_exists('setFlash')) {
    function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message
        ];
    }
}

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
