/**
 * ==============================================================================
 * FILE: assets/js/app.js
 * FUNGSI UTAMA: Mengatur seluruh interaktivitas antarmuka aplikasi:
 *               1. Pop-up Modal (Tambah Tugas, Edit Tugas, Konfirmasi Hapus)
 *               2. Pengisian otomatis form edit dari kartu yang diklik
 *               3. Quick-Add: Klik tanggal di kalender otomatis isi tanggal form
 *               4. Live Search: Pencarian instan di sisi browser tanpa reload
 *               5. Dropdown lonceng notifikasi & Desktop notification API
 *               6. Auto-dismiss notifikasi toast
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // --------------------------------------------------------------------------
    // 1. NOTIFIKASI FLASH TOAST
    // 🎯 FUNGSI: Menghilangkan notifikasi sukses/gagal secara otomatis setelah beberapa detik
    // ✏️ TIPS UKK: Angka 4000 = 4 detik. Jika ingin lebih cepat, ganti ke 2000 (2 detik).
    // --------------------------------------------------------------------------
    const flashToast = document.getElementById('flash-toast');
    if (flashToast) {
        setTimeout(() => {
            flashToast.style.opacity = '0';
            flashToast.style.transform = 'translateY(-10px)';
            setTimeout(() => flashToast.remove(), 400);
        }, 4000);
    }

    // --------------------------------------------------------------------------
    // 2. MODAL TAMBAH TUGAS & TAMBAH AGENDA KALENDER
    // 🎯 FUNGSI: Membuka pop-up Tambah Tugas dan mengosongkan form agar siap diisi baru
    // --------------------------------------------------------------------------
    const addModal              = document.getElementById('add-task-modal');
    const openAddButtons        = document.querySelectorAll('.btn-open-add-modal');
    const closeAddButtons       = document.querySelectorAll('.btn-close-add-modal');
    const addAgendaDateButtons  = document.querySelectorAll('.btn-add-agenda-date');
    const addDueDateInput       = document.getElementById('add-task-due-date');
    const addDueTimeInput       = document.getElementById('add-task-due-time');

    // 💡 A. Buka modal tambah umum (dari tombol "+ Tambah Agenda" di navbar / sidebar)
    openAddButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (addModal) {
                // Reset kolom tanggal & jam deadline
                if (addDueDateInput) addDueDateInput.value = '';
                if (addDueTimeInput) addDueTimeInput.value = '';

                // Hapus class 'modal-hidden' agar pop-up muncul di layar
                addModal.classList.remove('modal-hidden');
                
                // Otomatis arahkan kursor ke input judul tugas
                const titleInput = addModal.querySelector('input[name="title"]');
                if (titleInput) setTimeout(() => titleInput.focus(), 150);
            }
        });
    });

    // 💡 B. Buka modal tambah dari klik tanggal kalender tertentu (Fitur Quick-Add)
    addAgendaDateButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Mencegah event bubbling ke elemen pembungkus
            const chosenDate = btn.getAttribute('data-date'); // contoh: '2026-11-23'
            
            if (addModal) {
                // Otomatis isi kolom tanggal sesuai tanggal yang diklik di kalender
                if (addDueDateInput && chosenDate) {
                    addDueDateInput.value = chosenDate;
                }
                if (addDueTimeInput) addDueTimeInput.value = '';

                addModal.classList.remove('modal-hidden');
                const titleInput = addModal.querySelector('input[name="title"]');
                if (titleInput) setTimeout(() => titleInput.focus(), 150);
            }
        });
    });

    // 💡 C. Tutup modal tambah saat tombol 'Batal' atau 'X' diklik
    closeAddButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (addModal) addModal.classList.add('modal-hidden');
        });
    });

    // --------------------------------------------------------------------------
    // 3. MODAL EDIT TUGAS (POPULASI DATA DINAMIS)
    // 🎯 FUNGSI: Membuka pop-up Edit dan mengisi kolom form secara otomatis
    // 💡 CARA KERJA: Data diambil dari atribut HTML 'data-*' pada tombol edit yang diklik,
    //               lalu diisikan ke masing-masing input modal edit.
    // --------------------------------------------------------------------------
    const editModal        = document.getElementById('edit-task-modal');
    const editButtons      = document.querySelectorAll('.btn-open-edit');
    const closeEditButtons = document.querySelectorAll('.btn-close-edit-modal');

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // 🎯 Ambil data tugas dari atribut data-* tombol yang diklik
            const id          = btn.getAttribute('data-id');
            const title       = btn.getAttribute('data-title');
            const description = btn.getAttribute('data-description');
            const category    = btn.getAttribute('data-category');
            const priority    = btn.getAttribute('data-priority');
            const status      = btn.getAttribute('data-status');
            const dueDate     = btn.getAttribute('data-due-date');
            const dueTime     = btn.getAttribute('data-due-time');

            if (editModal) {
                // 💡 Masukkan data ke dalam masing-masing form input modal edit
                document.getElementById('edit-task-id').value         = id;
                document.getElementById('edit-task-title').value      = title || '';
                document.getElementById('edit-task-desc').value       = description || '';
                document.getElementById('edit-task-category').value   = category || 'Umum';
                document.getElementById('edit-task-due-date').value   = dueDate || '';
                document.getElementById('edit-task-due-time').value   = dueTime ? dueTime.substring(0, 5) : '';

                // Pilih dropdown status (Belum Mulai / Sedang Dikerjakan / Selesai)
                const statusSelect = document.getElementById('edit-task-status');
                if (statusSelect && status) {
                    statusSelect.value = status;
                }

                // Pilih radio button prioritas yang sesuai (Rendah / Sedang / Tinggi)
                const priorityRadio = editModal.querySelector(`input[name="priority"][value="${priority}"]`);
                if (priorityRadio) priorityRadio.checked = true;

                // Tampilkan modal edit
                editModal.classList.remove('modal-hidden');
                setTimeout(() => document.getElementById('edit-task-title').focus(), 150);
            }
        });
    });

    // Tutup modal edit
    closeEditButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (editModal) editModal.classList.add('modal-hidden');
        });
    });

    // --------------------------------------------------------------------------
    // 4. MODAL KONFIRMASI HAPUS
    // 🎯 FUNGSI: Memastikan pengguna tidak sengaja menghapus tugas penting
    // --------------------------------------------------------------------------
    const deleteModal        = document.getElementById('delete-task-modal');
    const deleteButtons      = document.querySelectorAll('.btn-open-delete');
    const closeDeleteButtons = document.querySelectorAll('.btn-close-delete-modal');

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const id    = btn.getAttribute('data-id');
            const title = btn.getAttribute('data-title');

            if (deleteModal) {
                // Kirim ID ke hidden input form hapus dan tampilkan judul tugas yang akan dihapus
                document.getElementById('delete-task-id').value = id;
                document.getElementById('delete-task-name').textContent = `"${title}"`;
                deleteModal.classList.remove('modal-hidden');
            }
        });
    });

    // Tutup modal hapus
    closeDeleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (deleteModal) deleteModal.classList.add('modal-hidden');
        });
    });

    // --------------------------------------------------------------------------
    // 5. TUTUP MODAL SAAT KLIK AREA LUAR (BACKDROP) ATAU TEKAN ESCAPE
    // 🎯 FUNGSI: Aksesibilitas keyboard dan navigasi ramah pengguna
    // --------------------------------------------------------------------------
    [addModal, editModal, deleteModal].forEach(modal => {
        if (!modal) return;
        modal.addEventListener('click', (e) => {
            // Jika yang diklik adalah background gelap di luar kotak modal
            if (e.target === modal) {
                modal.classList.add('modal-hidden');
            }
        });
    });

    window.addEventListener('keydown', (e) => {
        // Tombol Escape untuk menutup pop-up yang sedang terbuka
        if (e.key === 'Escape') {
            [addModal, editModal, deleteModal].forEach(modal => {
                if (modal && !modal.classList.contains('modal-hidden')) {
                    modal.classList.add('modal-hidden');
                }
            });
        }
    });

    // --------------------------------------------------------------------------
    // 6. LIVE SEARCH FILTER (Pencarian Instan Sisi Klien Tanpa Reload Halaman)
    // 🎯 FUNGSI: Menyaring kartu tugas secara real-time saat pengguna mengetik kata kunci
    // 💡 CARA KERJA: Mengambil teks yang diketik, mengecek apakah ada di judul/deskripsi/kategori.
    //               Jika cocok, tampilkan (display: ''); jika tidak, sembunyikan (display: 'none').
    // --------------------------------------------------------------------------
    const searchInput = document.getElementById('search-task-input');
    const taskCards   = document.querySelectorAll('.task-card-item');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();

            taskCards.forEach(card => {
                const title    = (card.getAttribute('data-search-title') || '').toLowerCase();
                const desc     = (card.getAttribute('data-search-desc') || '').toLowerCase();
                const category = (card.getAttribute('data-search-category') || '').toLowerCase();

                // Periksa apakah query pencarian ada pada judul, deskripsi, atau kategori
                if (title.includes(query) || desc.includes(query) || category.includes(query)) {
                    card.style.display = ''; // Tampilkan kartu
                } else {
                    card.style.display = 'none'; // Sembunyikan kartu
                }
            });
        });
    }

    // --------------------------------------------------------------------------
    // 7. DROPDOWN LONCENG NOTIFIKASI & BROWSER DESKTOP NOTIFICATION
    // 🎯 FUNGSI: Membuka panel lonceng notifikasi dan meminta izin notifikasi desktop
    // --------------------------------------------------------------------------
    const btnToggleNotif  = document.getElementById('btn-toggle-notif');
    const notifDropdown   = document.getElementById('notif-dropdown');
    const btnRequestNotif = document.getElementById('btn-request-browser-notif');

    if (btnToggleNotif && notifDropdown) {
        // Buka/tutup dropdown notifikasi saat lonceng diklik
        btnToggleNotif.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
        });

        // Tutup dropdown jika pengguna mengklik di area luar dropdown
        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && e.target !== btnToggleNotif) {
                notifDropdown.classList.add('hidden');
            }
        });
    }

    // 💡 Fitur Web Notification API (Notifikasi Desktop Komputer)
    if (btnRequestNotif && 'Notification' in window) {
        btnRequestNotif.addEventListener('click', async () => {
            // Minta izin ke pengguna di browser
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                new Notification('TaskFlow: Notifikasi Aktif! 🔔', {
                    body: 'Peringatan deadline tugas yang mepet akan muncul langsung di layar komputer Anda.'
                });
                btnRequestNotif.textContent = '✅ Notifikasi Desktop Aktif';
            } else {
                alert('Izin notifikasi tidak diberikan di browser Anda.');
            }
        });

        // Jika izin sudah diberikan sebelumnya dan ada peringatan deadline mepet di banner atas
        if (Notification.permission === 'granted') {
            const urgentBanner = document.getElementById('urgent-alert-banner');
            if (urgentBanner) {
                setTimeout(() => {
                    new Notification('TaskFlow: Perhatian Deadline! ⚠️', {
                        body: 'Ada tugas atau agenda Anda yang mendekati batas waktu atau telah jatuh tempo. Segera periksa!'
                    });
                }, 1500);
            }
        }
    }

});
