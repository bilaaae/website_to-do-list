-- ==============================================================
-- DATABASE & TABEL UNTUK TO-DO LIST & AGENDA (PHP NATIVE & MYSQL)
-- TASKFLOW WORKSPACE - MULTI-USER & AUTHENTICATION
-- ==============================================================

-- 1. Buat Database (jika belum ada)
CREATE DATABASE IF NOT EXISTS `todolist_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `todolist_db`;

-- 2. Buat Tabel `users` untuk Autentikasi Pengguna
-- Penjelasan kolom:
-- - id: Primary Key unik tiap akun
-- - name: Nama lengkap pemilik akun
-- - username: Username login unik (tidak boleh kembar)
-- - email: Alamat email unik
-- - password: Hash password Bcrypt (dihasilkan oleh password_hash())
-- - created_at: Waktu pendaftaran akun
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Akun Demo Bawaan untuk Penguji UKK
-- Username: penguji
-- Password: password123
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`) VALUES
(1, 'Penguji UKK', 'penguji', 'penguji@taskflow.id', '$2y$10$wN9a3Tskg23m9q8z.wR3z.o4Qk5j9C1M0R0pB1U6qF5q8c5fB3uXe')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 4. Buat Tabel `tasks`
-- Penjelasan kolom:
-- - id: Kunci utama (Primary Key) bernomor unik otomatis
-- - user_id: Relasi ke tabel `users` (Foreign Key)
-- - title: Judul tugas / agenda (wajib diisi / NOT NULL)
-- - description: Catatan / rincian tugas
-- - category: Kategori (Pekerjaan, Pribadi, Belajar, Keuangan, Kesehatan, dll)
-- - priority: Tingkat prioritas (low, medium, high)
-- - status: Status alur kerja: 'todo', 'in_progress', 'completed'
-- - due_date: Tanggal jatuh tempo / tanggal agenda
-- - start_time: Jam mulai agenda (Contoh: '09:00:00')
-- - due_time: Jam deadline / selesai agenda (Contoh: '17:00:00')
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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_tasks` (`user_id`),
    CONSTRAINT `fk_tasks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Data Contoh Awal untuk Akun Demo (user_id = 1)
INSERT INTO `tasks` (`user_id`, `title`, `description`, `category`, `priority`, `status`, `due_date`, `start_time`, `due_time`) VALUES
(1, 'Merancang Desain Antarmuka Modern 🎨', 'Membuat Kanban board & Kalender real-time dengan Tailwind CSS.', 'Pekerjaan', 'high', 'in_progress', CURDATE(), '08:30:00', '11:00:00'),
(1, 'Rapat Evaluasi Proyek Mingguan ☕', 'Diskusi progres fitur baru bersama tim pengembang.', 'Pekerjaan', 'medium', 'todo', CURDATE(), '13:00:00', '14:30:00'),
(1, 'Belajar Query MySQL & Prepared Statements 💻', 'Pelajari cara INSERT, SELECT, UPDATE, dan DELETE dengan parameter binding.', 'Belajar', 'high', 'todo', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '12:00:00'),
(1, 'Membeli perlengkapan harian 🛒', 'Beli buku catatan, pulpen baru, dan camilan untuk belajar.', 'Pribadi', 'low', 'todo', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '16:00:00', '17:30:00');
