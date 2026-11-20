<?php
// ==============================================================================
// FILE: auth.php
// FUNGSI: Controller Autentikasi Pengguna (Login, Register, Logout)
// PENJELASAN UNTUK UKK:
// - Menggunakan PHP Session untuk menyimpan status login pengguna.
// - Menggunakan PDO Prepared Statement untuk mencegah celah SQL Injection.
// - Menggunakan password_hash() dengan algoritma BCRYPT / PASSWORD_DEFAULT 
//   dan password_verify() untuk verifikasi kata sandi secara aman standar industri.
// ==============================================================================

require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("Koneksi database terputus. Pastikan MySQL di Laragon aktif.");
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ==========================================================================
    // 1. PROSES LOGIN PENGGUNA
    // Method: POST
    // ==========================================================================
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: login.php");
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi form kosong
        if (empty($username) || empty($password)) {
            setFlash('danger', 'Username/Email dan Password wajib diisi!');
            header("Location: login.php");
            exit;
        }

        try {
            // Cari user berdasarkan username ATAU email (gunakan nama parameter terpisah untuk kompatibilitas PDO)
            $stmt = $pdo->prepare("
                SELECT * FROM `users` 
                WHERE `username` = :u1 OR `email` = :u2 
                LIMIT 1
            ");
            $stmt->execute([
                ':u1' => $username,
                ':u2' => $username
            ]);
            $user = $stmt->fetch();

            // Verifikasi kecocokan password menggunakan fungsi bawaan PHP password_verify()
            if ($user && password_verify($password, $user['password'])) {
                // Simpan identitas user ke Session
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user'] = [
                    'id'       => (int)$user['id'],
                    'name'     => $user['name'],
                    'username' => $user['username'],
                    'email'    => $user['email']
                ];

                setFlash('success', 'Selamat datang kembali, ' . htmlspecialchars($user['name']) . '! 👋');
                header("Location: index.php");
                exit;
            } else {
                setFlash('danger', 'Username/Email atau Password yang Anda masukkan salah!');
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            setFlash('danger', 'Terjadi kesalahan sistem: ' . $e->getMessage());
            header("Location: login.php");
            exit;
        }
        break;

    // ==========================================================================
    // 2. PROSES REGISTRASI PENGGUNA BARU
    // Method: POST
    // ==========================================================================
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: register.php");
            exit;
        }

        $name            = trim($_POST['name'] ?? '');
        $username        = strtolower(trim($_POST['username'] ?? ''));
        $email           = strtolower(trim($_POST['email'] ?? ''));
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validasi kelengkapan data
        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            setFlash('danger', 'Semua kolom formulir pendaftaran wajib diisi!');
            header("Location: register.php");
            exit;
        }

        // Validasi format email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Format email tidak valid!');
            header("Location: register.php");
            exit;
        }

        // Validasi format username (hanya huruf, angka, dan underscore)
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            setFlash('danger', 'Username hanya boleh huruf, angka, underscore (3-30 karakter tanpa spasi)!');
            header("Location: register.php");
            exit;
        }

        // Validasi panjang password minimal 6 karakter
        if (strlen($password) < 6) {
            setFlash('danger', 'Password minimal harus 6 karakter!');
            header("Location: register.php");
            exit;
        }

        // Validasi konfirmasi password
        if ($password !== $passwordConfirm) {
            setFlash('danger', 'Konfirmasi password tidak cocok dengan password yang dimasukkan!');
            header("Location: register.php");
            exit;
        }

        try {
            // Cek apakah username atau email sudah digunakan
            $checkStmt = $pdo->prepare("
                SELECT id FROM `users` 
                WHERE `username` = :u OR `email` = :e 
                LIMIT 1
            ");
            $checkStmt->execute([':u' => $username, ':e' => $email]);
            if ($checkStmt->fetch()) {
                setFlash('danger', 'Username atau Email sudah terdaftar! Silakan gunakan yang lain.');
                header("Location: register.php");
                exit;
            }

            // Enkripsi password menggunakan Hash Bcrypt standar industri
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Simpan pengguna baru ke database
            $insertStmt = $pdo->prepare("
                INSERT INTO `users` (`name`, `username`, `email`, `password`)
                VALUES (:name, :username, :email, :password)
            ");
            $insertStmt->execute([
                ':name'     => $name,
                ':username' => $username,
                ':email'    => $email,
                ':password' => $hashedPassword
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Tambahkan 2 tugas pembuka sebagai contoh/panduan bagi akun baru
            $sampleStmt = $pdo->prepare("
                INSERT INTO `tasks` (`user_id`, `title`, `description`, `category`, `priority`, `status`, `due_date`, `start_time`, `due_time`)
                VALUES (:uid, :title, :desc, :cat, :prio, :stat, :due, :start, :due_time)
            ");
            $sampleStmt->execute([
                ':uid'      => $newUserId,
                ':title'    => 'Selamat Datang di TaskFlow! 🎉',
                ':desc'     => 'Coba geser (drag & drop) kartu ini ke kolom "Dalam Proses" atau "Selesai".',
                ':cat'      => 'Umum',
                ':prio'     => 'high',
                ':stat'     => 'todo',
                ':due'      => date('Y-m-d'),
                ':start'    => '09:00:00',
                ':due_time' => '17:00:00'
            ]);
            $sampleStmt->execute([
                ':uid'      => $newUserId,
                ':title'    => 'Jelajahi Kalender & Pengingat Waktu 📅',
                ':desc'     => 'Klik tab "Kalender" di atas untuk melihat agenda bulanan real-time.',
                ':cat'      => 'Belajar',
                ':prio'     => 'medium',
                ':stat'     => 'todo',
                ':due'      => date('Y-m-d', strtotime('+1 day')),
                ':start'    => '10:00:00',
                ':due_time' => '12:00:00'
            ]);

            // Otomatis login setelah pendaftaran sukses
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user'] = [
                'id'       => $newUserId,
                'name'     => $name,
                'username' => $username,
                'email'    => $email
            ];

            setFlash('success', 'Akun berhasil dibuat! Selamat datang di TaskFlow, ' . htmlspecialchars($name) . '! 🚀');
            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            setFlash('danger', 'Gagal mendaftarkan akun: ' . $e->getMessage());
            header("Location: register.php");
            exit;
        }
        break;

    // ==========================================================================
    // 3. PROSES LOGOUT (KELUAR)
    // Method: GET atau POST
    // ==========================================================================
    case 'logout':
        // Hapus variabel session
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        // Mulai session baru khusus untuk membawa pesan flash ke halaman login
        session_start();
        setFlash('info', 'Anda telah berhasil keluar (logout). Sampai jumpa kembali! 👋');
        header("Location: login.php");
        exit;

    default:
        header("Location: index.php");
        exit;
}
