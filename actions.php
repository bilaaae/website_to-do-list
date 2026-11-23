<?php
// ==============================================================================
// FILE: actions.php
// FUNGSI UTAMA: Controller Backend untuk memproses semua aksi CRUD
//               (Create, Read, Update, Delete) dan menerima request AJAX Drag & Drop.
// 
// 🎓 PANDUAN BELAJAR UKK:
// - File ini menerima request (GET atau POST) dari form HTML atau AJAX JavaScript.
// - Mengharuskan pengguna login (requireLogin).
// - Menggunakan PDO Prepared Statements (:placeholder) untuk mencegah SQL Injection.
// - Setiap query selalu difilter 'WHERE user_id = :uid' untuk keamanan isolasi data.
// ==============================================================================

require_once __DIR__ . '/config/database.php';

// [KEAMANAN]: Pastikan pengguna sudah login sebelum mengeksekusi aksi apapun
requireLogin();

// [DATA USER]: Ambil ID pengguna aktif dari session server
$currentUserId = (int)$_SESSION['user_id'];

// [KONEKSI]: Ambil objek koneksi database PDO
$pdo = getDBConnection();
if (!$pdo) {
    die("Koneksi database terputus. Pastikan MySQL di Laragon aktif.");
}

// Tangkap nama aksi yang diminta pengguna melalui URL/Form (contoh: ?action=add)
$action = $_REQUEST['action'] ?? '';

// Halaman asal pengirim request (untuk redirect kembali setelah proses selesai)
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

switch ($action) {

    // ==========================================================================
    // 1. CREATE: Menambahkan Tugas / Agenda Baru
    // Method: POST (dari form modal #add-task-modal)
    // ==========================================================================
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 🎯 [MENGAMBIL & MEMBERSIHKAN DATA INPUT]:
            // trim() berfungsi membuang spasi kosong di awal dan akhir teks input.
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category    = trim($_POST['category'] ?? 'Umum');
            $priority    = trim($_POST['priority'] ?? 'medium');
            $status      = trim($_POST['status'] ?? 'todo');
            $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $start_time  = null; // Diset null karena input waktu difokuskan pada jam deadline
            $due_time    = !empty($_POST['due_time']) ? $_POST['due_time'] : null;

            // ✏️ [TIPS UJIAN UKK - VALIDASI INPUT]:
            // Jika penguji meminta "Judul tidak boleh kosong dan minimal 5 karakter":
            // if (empty($title) || strlen($title) < 5) { ... }
            if (empty($title)) {
                setFlash('danger', 'Judul tugas wajib diisi!');
                header("Location: index.php");
                exit;
            }

            // [VALIDASI ENUM]: Memastikan nilai prioritas & status sesuai dengan kolom MySQL
            if (!in_array($priority, ['low', 'medium', 'high'])) $priority = 'medium';
            if (!in_array($status, ['todo', 'in_progress', 'completed'])) $status = 'todo';

            try {
                // 💡 [QUERY INSERT]: Memasukkan data baru menggunakan Prepared Statement
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

                // ✏️ [TIPS UJIAN UKK]: Jika ingin mengubah pesan notifikasi berhasil, ubah teks di bawah ini
                setFlash('success', 'Agenda/Tugas baru berhasil ditambahkan! 🚀');
            } catch (PDOException $e) {
                setFlash('danger', 'Gagal menambahkan tugas: ' . $e->getMessage());
            }
        }
        // Redirect kembali ke halaman tempat tombol diklik
        header("Location: " . $referer);
        exit;

    // ==========================================================================
    // 2. UPDATE: Memperbarui Rincian Data Tugas
    // Method: POST (dari form modal #edit-task-modal)
    // ==========================================================================
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // filter_input() memastikan ID yang dikirim berupa angka integer yang valid
            $id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category    = trim($_POST['category'] ?? 'Umum');
            $priority    = trim($_POST['priority'] ?? 'medium');
            $status      = trim($_POST['status'] ?? 'todo');
            $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
            $start_time  = null;
            $due_time    = !empty($_POST['due_time']) ? $_POST['due_time'] : null;

            if (!$id || empty($title)) {
                setFlash('danger', 'ID tugas atau judul tidak valid.');
                header("Location: index.php");
                exit;
            }

            if (!in_array($priority, ['low', 'medium', 'high'])) $priority = 'medium';
            if (!in_array($status, ['todo', 'in_progress', 'completed'])) $status = 'todo';

            try {
                // 💡 [QUERY UPDATE]: Memperbarui data tugas berdasarkan ID dan User ID pemiliknya
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
    // 3. UPDATE STATUS VIA DRAG & DROP (AJAX / Fetch API)
    // Dipanggil otomatis oleh berkas assets/js/dragdrop.js saat kartu digeser
    // Method: POST (Format payload: JSON, Respon: JSON)
    // ==========================================================================
    case 'update_status':
        // Memberitahu browser bahwa respon server berupa format JSON
        header('Content-Type: application/json');

        // Membaca raw data JSON yang dikirimkan oleh fetch() JavaScript
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);

        $id     = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $status = trim($data['status'] ?? '');

        // Validasi status yang diperbolehkan (hanya todo, in_progress, completed)
        $validStatuses = ['todo', 'in_progress', 'completed'];
        if (!$id || !in_array($status, $validStatuses)) {
            echo json_encode([
                'success' => false,
                'message' => 'Data tidak valid atau status tidak dikenali.'
            ]);
            exit;
        }

        try {
            // Update status kolom di database
            $stmt = $pdo->prepare("UPDATE `tasks` SET `status` = :status WHERE `id` = :id AND `user_id` = :uid");
            $stmt->execute([
                ':status' => $status,
                ':id'     => $id,
                ':uid'    => $currentUserId
            ]);

            // Label pesan ramah pengguna untuk notifikasi toast
            $labels = [
                'todo'        => 'Belum Dikerjakan 📋',
                'in_progress' => 'Sedang Dikerjakan ⚡',
                'completed'   => 'Selesai Dikerjakan 🎉'
            ];

            // 💡 Kirim respon sukses dalam format JSON ke JavaScript
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
    // 4. TOGGLE STATUS: Mengubah Selesai / Belum Selesai (Klik Checkbox di List)
    // Method: GET (contoh: actions.php?action=toggle&id=5)
    // ==========================================================================
    case 'toggle':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                // Ambil status saat ini
                $stmt = $pdo->prepare("SELECT `status` FROM `tasks` WHERE `id` = :id AND `user_id` = :uid LIMIT 1");
                $stmt->execute([
                    ':id'  => $id,
                    ':uid' => $currentUserId
                ]);
                $task = $stmt->fetch();

                if ($task) {
                    // 💡 Logika saklar (Toggle): Jika sudah 'completed' maka ubah ke 'todo', begitu sebaliknya
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
    // Method: POST / GET (contoh: actions.php?action=delete&id=3)
    // ==========================================================================
    case 'delete':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                // 💡 [QUERY DELETE]: Menghapus tugas berdasarkan ID dan terikat ke user_id pemiliknya
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
    // 6. DELETE: Membersihkan Semua Tugas yang Telah Selesai (Clear Completed)
    // Method: POST
    // ==========================================================================
    case 'clear_completed':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Hapus semua tugas yang statusnya sudah 'completed' milik user ini
                $stmt = $pdo->prepare("DELETE FROM `tasks` WHERE `status` = 'completed' AND `user_id` = :uid");
                $stmt->execute([':uid' => $currentUserId]);
                $count = $stmt->rowCount(); // rowCount() menghitung berapa banyak baris yang terhapus
                setFlash('success', "Sebanyak {$count} tugas selesai berhasil dibersihkan! 🧹");
            } catch (PDOException $e) {
                setFlash('danger', 'Gagal membersihkan tugas: ' . $e->getMessage());
            }
        }
        header("Location: " . $referer);
        exit;

    // ==========================================================================
    // DEFAULT: Alihkan kembali ke index.php jika nama aksi tidak dikenali
    // ==========================================================================
    default:
        header("Location: index.php");
        exit;
}
