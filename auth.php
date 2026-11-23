<?php
// ==============================================================================
// FILE: auth.php
// FUNGSI UTAMA: Controller Autentikasi Pengguna (Login, Register, Logout)
//
// 🎓 PANDUAN BELAJAR UKK:
// - Menggunakan PHP Session ($_SESSION) untuk menyimpan status dan identitas user yang aktif.
// - Menggunakan PDO Prepared Statements (:u1, :u2, dll) untuk mencegah celah SQL Injection.
// - Menggunakan password_hash(..., PASSWORD_DEFAULT) dengan algoritma Bcrypt yang aman.
// - Menggunakan password_verify() untuk membandingkan password plain-text dengan hash di database.
// ==============================================================================

require_once __DIR__ . '/config/database.php';

// Pastikan koneksi database PDO tersedia
$pdo = getDBConnection();
if (!$pdo) {
    die("Koneksi database terputus. Pastikan MySQL di Laragon aktif.");
}

// Tangkap aksi yang diminta (contoh: action=login atau action=register)
$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ==========================================================================
    // 1. PROSES LOGIN PENGGUNA
    // Method: POST (dari form login.php)
    // ==========================================================================
    case 'login':
        // 🎯 [FUNGSI]: Memverifikasi identitas pengguna berdasarkan Username atau Email dan Password
        // 💡 [CARA KERJA]:
        // 1. Menolak jika request bukan metode POST.
        // 2. Membersihkan input username dan password.
        // 3. Mengambil record user dari MySQL menggunakan query prepared statement.
        // 4. Memverifikasi password menggunakan password_verify().
        // 5. Jika cocok, session dibuat dan user diarahkan ke dashboard (index.php).
        // ✏️ [TIPS MENGUBAH / UJIAN UKK]:
        // Jika penguji bertanya: "Kenapa parameter di query :u1 dan :u2 dibedakan?"
        // Jawab: "Karena beberapa driver PDO membutuhkan nama parameter unik untuk setiap placeholder!"
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
            // Cari user berdasarkan username ATAU email
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

                // ✏️ [TIPS UJIAN UKK]: Pesan sambutan setelah login berhasil
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
    // Method: POST (dari form register.php)
    // ==========================================================================
    case 'register':
        // 🎯 [FUNGSI]: Mendaftarkan akun baru ke database MySQL
        // 💡 [CARA KERJA]:
        // 1. Validasi kelengkapan form, format email, format karakter username, dan konfirmasi password.
        // 2. Mengecek apakah username/email sudah terdaftar di database (mencegah duplikasi).
        // 3. Mengenkripsi password dengan password_hash($password, PASSWORD_DEFAULT).
        // 4. Memasukkan data user ke tabel `users`.
        // 5. Mengarahkan akun baru ke halaman Onboarding (welcome.php) tanpa agenda bawaan.
        // ✏️ [TIPS MENGUBAH / UJIAN UKK]:
        // Jika penguji meminta: "Minimal password diubah jadi 8 karakter",
        // ubah 'strlen($password) < 6' menjadi 'strlen($password) < 8'.
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

        // Validasi format email standar RFC
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

        // Validasi kesesuaian konfirmasi password
        if ($password !== $passwordConfirm) {
            setFlash('danger', 'Konfirmasi password tidak cocok dengan password yang dimasukkan!');
            header("Location: register.php");
            exit;
        }

        try {
            // Cek apakah username atau email sudah digunakan sebelumnya
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

            // Otomatis login setelah pendaftaran sukses
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user'] = [
                'id'       => $newUserId,
                'name'     => $name,
                'username' => $username,
                'email'    => $email
            ];

            // Arahkan ke halaman sambutan (welcome.php) untuk akun baru
            header("Location: welcome.php");
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
        // 🎯 [FUNGSI]: Menghapus seluruh sesi pengguna dan keluar dari sistem dengan aman
        // 💡 [CARA KERJA]:
        // 1. Mengosongkan variabel array $_SESSION = [].
        // 2. Menghapus cookie session di browser client.
        // 3. Menghancurkan session server dengan session_destroy().
        // 4. Membuka session baru sebentar untuk membawa notifikasi flash 'info' ke halaman login.
        // ✏️ [TIPS MENGUBAH / UJIAN UKK]:
        // Ini adalah prosedur logout paling bersih dan aman sesuai standar keamanan PHP.
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
