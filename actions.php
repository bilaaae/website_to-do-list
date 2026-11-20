<?php
// ==============================================================================
// FILE: actions.php
// FUNGSI: Controller Backend untuk memproses semua aksi CRUD (Create, Read, Update, Delete)
//         dan menerima request AJAX Drag & Drop dari JavaScript.
// PENJELASAN UNTUK UKK:
// - File ini menerima request (GET atau POST) dari pengguna.
// - Mengharuskan pengguna login (requireLogin).
// - Semua operasi dibatasi hanya untuk tugas milik pengguna aktif (WHERE user_id = :uid).
// - Menggunakan Prepared Statements untuk mencegah SQL Injection.
// - Mengembalikan respon berupa pengalihan halaman (redirect) atau JSON (untuk AJAX).
// ==============================================================================

require_once __DIR__ . '/config/database.php';

// Pastikan pengguna sudah login
requireLogin();

// Ambil ID pengguna aktif dari session
$currentUserId = (int)$_SESSION['user_id'];

// Ambil koneksi database
$pdo = getDBConnection();
if (!$pdo) {
    die("Koneksi database terputus. Pastikan MySQL di Laragon aktif.");
}

// Tangkap nama aksi yang diminta pengguna
$action = $_REQUEST['action'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

switch ($action) {

    // ==========================================================================
    // 1. CREATE: Menambahkan Tugas Baru
    // Method: POST
    // ==========================================================================
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Ambil dan bersihkan data input dari form
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category    = trim($_POST['category'] ?? 'Umum');
            $priority    = trim($_POST['priority'] ?? 'medium');
            $status      = trim($_POST['status'] ?? 'todo');
            $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $start_time  = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
            $due_time    = !empty($_POST['due_time']) ? $_POST['due_time'] : null;

            // Validasi: Judul tidak boleh kosong
            if (empty($title)) {
                setFlash('danger', 'Judul tugas wajib diisi!');
                header("Location: index.php");
                exit;
            }

            // Validasi nilai ENUM agar tidak error di MySQL
            if (!in_array($priority, ['low', 'medium', 'high'])) $priority = 'medium';
            if (!in_array($status, ['todo', 'in_progress', 'completed'])) $status = 'todo';

            try {
                // Query INSERT dengan Prepared Statement terikat ke user_id
                $stmt = $pdo->prepare("
                    INSERT INTO `tasks` (`user_id`, `title`, `description`, `category`, `priority`, `status`, `due_date`, `start_time`, `due_time`)
                    VALUES (:uid, :title, :description, :category, :priority, :status, :due_date, :start_time, :due_time)
                ");
                $stmt->execute([
                    ':uid'         => $currentUserId,
                    ':title'       => $title,
                    ':description' => $description ?: null,
                    ':category'    => $category ?: 'Umum',
                    ':priority'    => $priority,
                    ':status'      => $status,
                    ':due_date'    => $due_date,
                    ':start_time'  => $start_time,
                    ':due_time'    => $due_time
                ]);

                setFlash('success', 'Agenda/Tugas baru berhasil ditambahkan! 🚀');
            } catch (PDOException $e) {
                setFlash('danger', 'Gagal menambahkan tugas: ' . $e->getMessage());
            }
        }
        header("Location: " . $referer);
        exit;

    // ==========================================================================
    // 2. UPDATE: Memperbarui Rincian Data Tugas
    // Method: POST
    // ==========================================================================
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category    = trim($_POST['category'] ?? 'Umum');
            $priority    = trim($_POST['priority'] ?? 'medium');
            $status      = trim($_POST['status'] ?? 'todo');
            $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $start_time  = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
            $due_time    = !empty($_POST['due_time']) ? $_POST['due_time'] : null;

            if (!$id || empty($title)) {
                setFlash('danger', 'ID tugas atau judul tidak valid.');
                header("Location: index.php");
                exit;
            }

            if (!in_array($priority, ['low', 'medium', 'high'])) $priority = 'medium';
            if (!in_array($status, ['todo', 'in_progress', 'completed'])) $status = 'todo';

            try {
                // Query UPDATE dengan Prepared Statement dibatasi hanya milik user yang login
                $stmt = $pdo->prepare("
                    UPDATE `tasks`
                    SET `title`       = :title,
                        `description` = :description,
                        `category`    = :category,
                        `priority`    = :priority,
                        `status`      = :status,
                        `due_date`    = :due_date,
                        `start_time`  = :start_time,
                        `due_time`    = :due_time
                    WHERE `id`        = :id AND `user_id` = :uid
                ");
                $stmt->execute([
                    ':title'       => $title,
                    ':description' => $description ?: null,
                    ':category'    => $category ?: 'Umum',
                    ':priority'    => $priority,
                    ':status'      => $status,
                    ':due_date'    => $due_date,
                    ':start_time'  => $start_time,
                    ':due_time'    => $due_time,
                    ':id'          => $id,
                    ':uid'         => $currentUserId
                ]);

                setFlash('success', 'Perubahan agenda/tugas berhasil disimpan! ✨');
            } catch (PDOException $e) {
                setFlash('danger', 'Gagal memperbarui tugas: ' . $e->getMessage());
            }
        }
        header("Location: " . $referer);
        exit;

    // ==========================================================================
    // 3. UPDATE VIA DRAG & DROP (AJAX / Fetch API)
    // Dipanggil otomatis oleh assets/js/dragdrop.js saat kartu digeser
    // Respon: JSON (tanpa reload halaman)
    // ==========================================================================
    case 'update_status':
        header('Content-Type: application/json');

        // Membaca data JSON yang dikirimkan oleh fetch() JavaScript
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        $id     = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $status = trim($data['status'] ?? '');

        // Validasi status yang diperbolehkan
        $validStatuses = ['todo', 'in_progress', 'completed'];
        if (!$id || !in_array($status, $validStatuses)) {
            echo json_encode([
                'success' => false,
                'message' => 'Data tidak valid atau status tidak dikenali.'
            ]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE `tasks` SET `status` = :status WHERE `id` = :id AND `user_id` = :uid");
            $stmt->execute([
                ':status' => $status,
                ':id'     => $id,
                ':uid'    => $currentUserId
            ]);

            $labels = [
                'todo'        => 'Belum Dikerjakan 📋',
                'in_progress' => 'Sedang Dikerjakan ⚡',
                'completed'   => 'Selesai Dikerjakan 🎉'
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Status tugas dipindahkan ke ' . ($labels[$status] ?? $status),
                'status'  => $status
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengubah status di database: ' . $e->getMessage()
            ]);
        }
        exit;

    // ==========================================================================
    // 4. TOGGLE STATUS (Klik Checkbox Selesai / Belum)
    // Method: GET
    // ==========================================================================
    case 'toggle':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                // Ambil status saat ini (hanya milik user yang login)
                $stmt = $pdo->prepare("SELECT `status` FROM `tasks` WHERE `id` = :id AND `user_id` = :uid LIMIT 1");
                $stmt->execute([
                    ':id'  => $id,
                    ':uid' => $currentUserId
                ]);
                $task = $stmt->fetch();

                if ($task) {
                    // Jika sudah 'completed', ubah jadi 'todo'. Jika belum, ubah jadi 'completed'
                    $newStatus = ($task['status'] === 'completed') ? 'todo' : 'completed';
                    
                    $updateStmt = $pdo->prepare("UPDATE `tasks` SET `status` = :status WHERE `id` = :id AND `user_id` = :uid");
                    $updateStmt->execute([
                        ':status' => $newStatus,
                        ':id'     => $id,
                        ':uid'    => $currentUserId
                    ]);

                    $msg = ($newStatus === 'completed') 
                        ? 'Tugas ditandai selesai! 🎉' 
                        : 'Tugas dipindahkan kembali ke daftar aktif. ⏳';
                    setFlash('success', $msg);
                } else {
                    setFlash('danger', 'Tugas tidak ditemukan atau bukan milik Anda.');
                }
            } catch (PDOException $e) {
                setFlash('danger', 'Gagal mengubah status tugas: ' . $e->getMessage());
            }
        }
        header("Location: " . $referer);
        exit;

    // ==========================================================================
    // 5. DELETE: Menghapus Satu Tugas
    // Method: POST / GET
    // ==========================================================================
    case 'delete':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                // Query DELETE dibatasi hanya milik user yang login
                $stmt = $pdo->prepare("DELETE FROM `tasks` WHERE `id` = :id AND `user_id` = :uid");
                $stmt->execute([
                    ':id'  => $id,
                    ':uid' => $currentUserId
                ]);
                setFlash('success', 'Tugas berhasil dihapus. 🗑️');
            } catch (PDOException $e) {
                setFlash('danger', 'Gagal menghapus tugas: ' . $e->getMessage());
            }
        }
        header("Location: " . $referer);
        exit;

    // ==========================================================================
    // 6. DELETE: Membersihkan Semua Tugas yang Telah Selesai Milik Pengguna Ini
    // Method: POST
    // ==========================================================================
    case 'clear_completed':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $stmt = $pdo->prepare("DELETE FROM `tasks` WHERE `status` = 'completed' AND `user_id` = :uid");
                $stmt->execute([':uid' => $currentUserId]);
                $count = $stmt->rowCount();
                setFlash('success', "Sebanyak {$count} tugas selesai berhasil dibersihkan! 🧹");
            } catch (PDOException $e) {
                setFlash('danger', 'Gagal membersihkan tugas: ' . $e->getMessage());
            }
        }
        header("Location: " . $referer);
        exit;

    // ==========================================================================
    // DEFAULT: Redirect kembali ke halaman utama jika aksi tidak dikenali
    // ==========================================================================
    default:
        header("Location: index.php");
        exit;
}
