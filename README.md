# 📋 TaskFlow &mdash; To-Do List Elegan dengan Drag & Drop (UKK Pro)

Aplikasi **To-Do List** berbasis **PHP Native (PDO)** dan **MySQL** dengan antarmuka modern (**Tailwind CSS**), dilengkapi fitur **Papan Kanban interaktif dengan Drag & Drop (HTML5 API + Fetch AJAX)**, serta struktur kode yang terpecah rapi (*modular components*) dan sarat komentar untuk persiapan **Uji Kompetensi Keahlian (UKK)**.

---

## 🌟 Fitur Utama untuk UKK

- 🔄 **Papan Kanban Interaktif (Drag & Drop)**:
  - 3 Kolom Alur Kerja: **Belum Mulai** &rarr; **Sedang Dikerjakan** &rarr; **Selesai**.
  - Tarik dan lepas kartu tugas untuk memperbarui status di MySQL secara asinkron (AJAX) tanpa reload halaman!
- 📑 **Mode Tampilan Ganda (Switch Views)**:
  - Mode **Papan Kanban** (Drag & Drop).
  - Mode **Daftar List** tradisional dengan tombol checkbox toggle cepat.
- 📊 **Kartu Ringkasan Statistik Produktivitas**:
  - Total tugas, tugas belum mulai, tugas dalam proses, tugas selesai, dan *progress bar* persentase.
- ⚡ **Operasi CRUD Lengkap**:
  - **Create**: Tambah tugas baru via modal pop-up elegan.
  - **Read**: Tampilkan daftar tugas, statistik, filter kategori, filter prioritas, dan live search.
  - **Update**: Edit tugas lengkap (judul, deskripsi, kategori, prioritas, status, batas waktu).
  - **Delete**: Hapus tugas satuan dengan konfirmasi dialog aman, serta fitur bersihkan semua tugas selesai.
- 🛡️ **Keamanan Standar Industri**:
  - Menggunakan **PDO Prepared Statements** untuk mencegah serangan **SQL Injection**.
  - Menggunakan fungsi sanitasi `htmlspecialchars` untuk mencegah serangan **XSS**.
- 🧩 **Struktur File Modular (Separation of Concerns)**:
  - Kode dipecah ke file-file terpisah di folder `includes/` agar rapi dan mudah dicari saat ditanya oleh penguji.
- 📚 **Buku Panduan Khusus UKK**:
  - Disertakan file `PANDUAN_UKK.md` yang berisi naskah presentasi, prediksi pertanyaan penguji, dan contekan menghadapi tantangan *live coding*!

---

## 📁 Struktur File Modular

```
todolist/
├── assets/
│   ├── css/
│   │   └── style.css            # Styling animasi, scrollbar, dan efek drag-over
│   └── js/
│       ├── app.js               # Modal controller, auto-dismiss toast, live search
│       └── dragdrop.js          # HTML5 Drag & Drop API + Fetch API (AJAX) ke server
├── config/
│   └── database.php             # Koneksi PDO MySQL & auto-create database/tabel
├── includes/
│   ├── header.php               # Head tag, CDN Tailwind & FontAwesome, Navbar
│   ├── stats.php                # Kartu statistik ringkasan & progress bar
│   ├── toolbar.php              # Filter kategori, prioritas, switch view, search
│   ├── view_kanban.php          # Tampilan Papan Kanban 3 Kolom (Drag & Drop)
│   ├── view_list.php            # Tampilan Daftar List Tradisional
│   ├── modals.php               # Modal dialog Tambah, Edit, dan Hapus
│   └── footer.php               # Bagian bawah halaman & pemanggilan JavaScript
├── actions.php                  # Controller Backend (CRUD & Endpoint AJAX)
├── database.sql                 # Skema tabel MySQL & contoh data awal
├── index.php                    # Entry point utama (ringkas & memanggil file includes)
├── PANDUAN_UKK.md               # PANDUAN LENGKAP PRESENTASI & TANYA JAWAB PENGUJI UKK
└── README.md                    # Dokumentasi umum proyek
```

---

## 🚀 Panduan Menjalankan di Laragon

1. Buka aplikasi **Laragon** di komputer Anda.
2. Klik tombol **"Start All"** untuk mengaktifkan Apache dan MySQL.
3. Buka peramban (browser) dan akses:
   - **`http://localhost/todolist`**
   - atau **`http://todolist.test`**
4. *Selesai!* Database `todolist_db` dan tabel `tasks` akan otomatis terbuat beserta contoh data pemula.

---

## 🎓 Tips untuk UKK
Silakan buka dan baca file **[PANDUAN_UKK.md](file:///d:/laragon/www/todolist/PANDUAN_UKK.md)** untuk mempelajari cara mempresentasikan aplikasi ini di depan penguji serta trik menjawab tantangan live coding!
