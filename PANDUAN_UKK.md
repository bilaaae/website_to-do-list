# 🎓 BUKU PANDUAN & KISI-KISI UJI KOMPETENSI KEAHLIAN (UKK)
## Proyek: TaskFlow Workspace &mdash; To-Do List & Agenda Elegan (PHP Native & MySQL)

Panduan ini dibuat khusus untuk membantu kamu **mempersiapkan presentasi**, **menjawab pertanyaan penguji**, dan **menghadapi tantangan live-coding** saat ujian UKK!

---

## 📑 DAFTAR ISI
1. [Struktur File Modular (Separation of Concerns)](#1-struktur-file-modular)
2. [Fitur Autentikasi & Multi-User Isolation](#2-fitur-autentikasi--multi-user-isolation)
3. [Alur Kerja Fitur CRUD, Drag & Drop, dan Kalender](#3-alur-kerja-crud-drag--drop-dan-kalender)
4. [Naskah Presentasi di Depan Penguji](#4-naskah-presentasi-di-depan-penguji)
5. [Tanya Jawab Populer Penguji UKK](#5-tanya-jawab-populer-penguji-ukk)
6. [Trik Menghadapi Tantangan Penguji (Live Coding)](#6-trik-menghadapi-tantangan-penguji)

---

## 1. Struktur File Modular

File sengaja dipecah per bagian agar rapi, mudah dicari, dan sesuai standar industri (*Modular Programming*):

| Nama File | Letak | Fungsi & Penjelasan |
|---|---|---|
| `login.php` | Root | Halaman masuk (*Login*), |
| `register.php` | Root | Halaman pendaftaran (*Register*) pengguna baru dengan validasi lengkap. |
| `auth.php` | Root | Controller autentikasi (proses verifikasi login, registrasi enkripsi hash, dan logout). |
| `index.php` | Root | File utama (*entry point*). Terproteksi `requireLogin()`, mengambil data tugas milik user aktif, dan merender tampilan. |
| `actions.php` | Root | Controller backend. Memproses logika **Create**, **Update**, **Delete**, dan **Drag & Drop AJAX** terisolasi per `user_id`. |
| `database.sql` | Root | Skema tabel `users` dan `tasks`, relasi Foreign Key, serta data akun bawaan. |
| `config/database.php` | Folder `config/` | Koneksi database **PDO**, Route Guard (`requireLogin()`), Session Helper, dan Format Tanggal/Jam. |
| `includes/header.php` | Folder `includes/` | Bagian atas web: Navbar, Info Profil User, Lonceng Notifikasi Deadline Mepet, dan Alert Banner. |
| `includes/stats.php` | Folder `includes/` | 4 Kartu ringkasan statistik (Total, Belum Mulai, Dalam Proses, Selesai + Progress bar). |
| `includes/toolbar.php` | Folder `includes/` | Tombol ganti tampilan (Kanban, List, Kalender), sorting deadline, filter kategori, prioritas, dan live search. |
| `includes/view_kanban.php` | Folder `includes/` | Tampilan 3 kolom papan Kanban dengan fitur **Drag & Drop** dan indikator jam/deadline. |
| `includes/view_list.php` | Folder `includes/` | Tampilan daftar list tradisional dengan checkbox status selesai. |
| `includes/view_calendar.php` | Folder `includes/` | Kalender bulanan real-time dinamis (semua tahun), dengan tombol navigasi dan klik tanggal untuk tambah agenda. |
| `includes/modals.php` | Folder `includes/` | Pop-up dialog untuk Tambah Tugas, Edit Tugas (dilengkapi input Jam Mulai & Deadline), dan Hapus. |
| `includes/footer.php` | Folder `includes/` | Bagian bawah web dan pemanggilan file JavaScript. |
| `assets/js/dragdrop.js` | Folder `assets/js/` | Logika Drag & Drop (HTML5 API) dan pengiriman data ke server lewat `fetch()` AJAX. |
| `assets/js/app.js` | Folder `assets/js/` | Logika membuka/menutup modal, isi form edit otomatis, kalender quick-add, dan desktop alert. |
| `assets/css/style.css` | Folder `assets/css/` | Styling kustom animasi, scrollbar, kalender, dan efek *glassmorphism*. |

---

## 2. Fitur Autentikasi & Multi-User Isolation

Aplikasi ini telah mengimplementasikan sistem multi-pengguna yang aman:
1. **Enkripsi Kata Sandi:**
   Menggunakan `password_hash($password, PASSWORD_DEFAULT)` dengan algoritma **Bcrypt**. Kata sandi tidak pernah disimpan dalam bentuk teks biasa (*plain text*) di MySQL.
2. **Verifikasi Login:**
   Menggunakan `password_verify($password, $user['password'])` yang aman dari serangan *timing attack*.
3. **Session Guard (`requireLogin()`):**
   Setiap kali halaman `index.php` atau `actions.php` diakses, sistem memvalidasi keberadaan `$_SESSION['user_id']`. Jika belum login, pengunjung langsung diarahkan ke `login.php`.
4. **Isolasi Data (Multi-Tenant Data Privacy):**
   Setiap data di tabel `tasks` memiliki kolom `user_id`. Semua query `SELECT`, `INSERT`, `UPDATE`, dan `DELETE` difilter dengan klausa `WHERE user_id = :uid`. Pengguna A tidak akan bisa melihat atau mengubah tugas milik Pengguna B.
5. **Akun Penguji UKK Bawaan:**
   - **Username:** `penguji`
   - **Password:** `password123`
   *(Tersedia tombol praktis "Gunakan Akun Demo Penguji (1-Klik)" di halaman login)*.

---

## 3. Alur Kerja CRUD, Drag & Drop, dan Kalender

Jika penguji meminta kamu menjelaskan alur aplikasi:

```
[Pengguna] 
    │
    ├─► 0. AUTH     : Form Login/Register ─► auth.php (Verifikasi password_verify / Hash Bcrypt)
    │
    ├─► 1. CREATE   : Isi Modal Tambah ───► actions.php?action=add (INSERT ke MySQL berdasar user_id)
    │
    ├─► 2. READ     : Buka Halaman ───────► index.php (SELECT dari MySQL via PDO WHERE user_id = :uid)
    │
    ├─► 3. UPDATE   : Edit Modal / Form ──► actions.php?action=update (UPDATE ke MySQL)
    │
    ├─► 4. DRAG&DROP: Tarik Kartu ────────► assets/js/dragdrop.js (Fetch API JSON)
    │                                       └──► actions.php?action=update_status
    │
    ├─► 5. KALENDER : Klik Tanggal ───────► Otomatis buka Modal Tambah dengan tanggal terpilih
    │
    └─► 6. DELETE   : Klik Tombol Hapus ──► actions.php?action=delete (DELETE dari MySQL)
```

---

## 4. Naskah Presentasi di Depan Penguji

Gunakan naskah ini sebagai contekan urutan berbicara saat giliranmu maju presentasi:

### A. Pembukaan (30 detik)
> *"Selamat pagi/siang Bapak/Ibu penguji. Nama saya [Nama Kamu]. Pada kesempatan uji kompetensi keahlian ini, saya mempresentasikan aplikasi web yang telah saya kembangkan, yaitu **TaskFlow Workspace &mdash; To-Do List & Agenda Elegan dengan Fitur Drag and Drop, Kalender Real-Time, dan Autentikasi Pengguna** berbasis **PHP Native** dan database **MySQL**."*

### B. Demonstrasi Fitur (2-3 menit)
1. **Autentikasi & Akun Penguji**:
   > *"Pertama, aplikasi dilengkapi sistem login dan registrasi multi-user. Di halaman login, saya menyediakan tombol 1-klik untuk akun demo penguji (`penguji` / `password123`). Password dienkripsi menggunakan fungsi `password_hash()` standar Bcrypt."*
2. **Koneksi & Tampilan**:
   > *"Aplikasi ini menggunakan konsep modular, di mana kode dipisahkan per komponen (header, footer, views, controller) agar terstruktur dan mudah dikelola."*
3. **Tambah Agenda & Jam (Create)**:
   > *"Pengguna dapat menambahkan tugas beserta kategori, prioritas, tanggal deadline, jam mulai, dan jam selesai. Ketika disimpan, data otomatis terikat ke user yang sedang login."*
4. **Fitur Unggulan: Drag and Drop (Update Real-Time)**:
   > *"Kedua, fitur unggulan aplikasi ini adalah **Drag and Drop Kanban Board**. Pengguna cukup menarik kartu tugas dari kolom 'Belum Mulai' ke 'Dalam Proses' atau 'Selesai'. Event ini ditangkap oleh JavaScript (`dragdrop.js`), lalu status di database langsung diperbarui melalui **Fetch API (AJAX)** secara asinkron tanpa memuat ulang halaman."*
5. **Fitur Unggulan: Kalender Bulanan Real-Time**:
   > *"Ketiga, tersedia tab tampilan Kalender yang menampilkan semua bulan dan tahun secara real-time. Jika kita mengklik tanggal tertentu di kalender, aplikasi langsung membuka form tambah tugas dengan tanggal tersebut terisi otomatis."*
6. **Peringatan Deadline Mepet & Pengurutan**:
   > *"Keempat, sistem secara cerdas mendeteksi tugas yang mendekati batas waktu (kurang dari 6 jam atau hari ini) dan memberikan badge peringatan 'Mepet', banner peringatan atas, lonceng notifikasi, serta dukungan Web Desktop Notification."*

### C. Keamanan (30 detik)
> *"Dari sisi keamanan, aplikasi ini menerapkan **PDO Prepared Statements** untuk mencegah serangan **SQL Injection**, fungsi sanitasi `htmlspecialchars` untuk mencegah serangan **XSS**, serta enkripsi kata sandi dan manajemen **Session Guard**."*

---

## 5. Tanya Jawab Populer Penguji UKK

Berikut adalah daftar pertanyaan yang paling sering ditanyakan oleh penguji dan cara menjawabnya dengan tepat:

### Q1: *"Bagaimana cara kamu mengamankan password pengguna di database?"*
> **Jawaban:** 
> *"Saya menggunakan fungsi bawaan PHP `password_hash($password, PASSWORD_DEFAULT)`. Fungsi ini menggunakan algoritma hashing satu arah yang kuat (Bcrypt) lengkap dengan *salt* otomatis yang acak. Saat login, pencocokan kata sandi dilakukan menggunakan fungsi `password_verify($password, $user['password'])`. Dengan demikian, tidak ada password berbentuk teks polos yang tersimpan di database."*

### Q2: *"Bagaimana cara memastikan data pengguna A tidak bisa dilihat oleh pengguna B?"*
> **Jawaban:**
> *"Tabel `tasks` memiliki relasi foreign key `user_id` ke tabel `users`. Di setiap query MySQL (baik `SELECT`, `UPDATE`, maupun `DELETE`), saya selalu menyertakan klausa `WHERE user_id = :uid`, di mana `:uid` diambil langsung dari nilai session server yang terpercaya (`$_SESSION['user_id']`), bukan dari parameter input form."*

### Q3: *"Kenapa kamu menggunakan PDO, bukan mysqli?"*
> **Jawaban:** 
> *"Saya memilih **PDO (PHP Data Objects)** karena PDO merupakan standar industri modern di PHP. PDO mendukung **Prepared Statements** murni yang sangat aman dari SQL Injection, fleksibel karena bisa mendukung berbagai jenis database (tidak hanya MySQL), dan mendukung penanganan error berbasis Object-Oriented Exception (`PDOException`)."*

### Q4: *"Bagaimana cara kerja fitur Drag and Drop tanpa reload halaman?"*
> **Jawaban:**
> *"Fitur ini menggunakan **HTML5 Drag and Drop API** bawaan browser. Setiap kartu memiliki atribut `draggable='true'`, dan kolom memiliki event `drop`. Saat kartu dijatuhkan ke kolom baru, JavaScript mengambil ID kartu dan status kolom tujuan, lalu mengirimkan request JSON ke server menggunakan **Fetch API** (`actions.php?action=update_status`). Di server, PHP mengeksekusi query UPDATE, lalu mengembalikan respon JSON `{success: true}`. Jadi halaman tidak perlu dimuat ulang."*

### Q5: *"Di mana letak query untuk mencegah SQL Injection?"*
> **Jawaban:**
> *"Ada di file `actions.php`, `auth.php`, dan `index.php`. Saya tidak pernah menggabungkan variabel langsung ke dalam teks query (string concatenation). Sebagai gantinya, saya menggunakan placeholder berparameter seperti `:title`, `:description`, `:id`, `:uid`, lalu mengeksekusinya lewat `$stmt->execute([...])`."*

---

## 6. Trik Menghadapi Tantangan Penguji (Live Coding)

Penguji sering memberikan tantangan mendadak untuk membuktikan apakah kamu benar-benar memahami kodenya. Berikut contoh tantangan yang mungkin diberikan:

### Tantangan 1: *"Coba tambahkan satu kategori baru, misalnya 'Olahraga'!"*
**Cara mengerjakannya:**
1. Buka file `includes/modals.php`.
2. Cari tag `<select name="category">`.
3. Tambahkan baris ini di dalam select modal Tambah dan modal Edit:
   ```html
   <option value="Olahraga">Olahraga</option>
   ```
4. Buka file `index.php` line 26:
   Tambahkan `'Olahraga'` ke dalam array `$categories`:
   ```php
   $categories = ['Umum', 'Pekerjaan', 'Pribadi', 'Belajar', 'Keuangan', 'Kesehatan', 'Olahraga'];
   ```
5. Simpan dan refresh browser. Tunjukkan ke penguji!

### Tantangan 2: *"Coba ubah pesan notifikasi setelah berhasil menambah tugas!"*
**Cara mengerjakannya:**
1. Buka file `actions.php`.
2. Cari baris `case 'add':` (sekitar baris 60-70).
3. Ubah teks di dalam:
   ```php
   setFlash('success', 'Agenda tugas baru berhasil disimpan ke database!');
   ```

### Tantangan 3: *"Coba tambahkan validasi agar judul minimal harus 5 karakter!"*
**Cara mengerjakannya:**
1. Buka file `actions.php`.
2. Cari bagian validasi judul di `case 'add':`:
   ```php
   if (empty($title) || strlen($title) < 5) {
       setFlash('danger', 'Judul tugas minimal harus 5 karakter!');
       header("Location: index.php");
       exit;
   }
   ```

### Tantangan 4: *"Coba buat user baru lewat form register lalu uji apakah datanya terpisah dari akun penguji!"*
**Cara mengerjakannya:**
1. Klik tombol **Logout** di navbar kanan atas.
2. Di halaman login, klik **"Daftar Akun Baru"**.
3. Daftarkan akun baru (misal Nama: `Siswa RPL`, Username: `siswarpl`, Password: `password123`).
4. Setelah masuk, buat tugas baru.
5. Logout kembali, lalu masuk menggunakan **Akun Demo Penguji**.
6. Tunjukkan kepada penguji bahwa tugas milik `Siswa RPL` tidak muncul di akun `penguji`. Ini membuktikan fitur *Multi-Tenant Data Isolation* berjalan dengan sempurna!

---
*Semoga ujian UKK kamu sukses, lancar, dan mendapat nilai maksimal! Semangat! 🚀*
