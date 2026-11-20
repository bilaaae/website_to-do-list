/**
 * ==============================================================================
 * FILE: assets/js/app.js
 * FUNGSI: Mengatur interaktivitas antarmuka aplikasi:
 *         - Membuka dan menutup Modal Tambah, Edit, dan Hapus
 *         - Mengisi form Edit secara otomatis saat tombol edit diklik
 *         - Fitur live search (pencarian instan di sisi klien)
 *         - Auto dismiss notifikasi flash message
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {

    // --------------------------------------------------------------------------
    // 1. NOTIFIKASI TOAST (Auto-close setelah 4 detik)
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
    // --------------------------------------------------------------------------
    const addModal = document.getElementById('add-task-modal');
    const openAddButtons = document.querySelectorAll('.btn-open-add-modal');
    const closeAddButtons = document.querySelectorAll('.btn-close-add-modal');
    const addAgendaDateButtons = document.querySelectorAll('.btn-add-agenda-date');
    const addDueDateInput = document.getElementById('add-task-due-date');
    const addStartTimeInput = document.getElementById('add-task-start-time');
    const addDueTimeInput = document.getElementById('add-task-due-time');

    // Buka modal tambah umum
    openAddButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (addModal) {
                if (addDueDateInput) addDueDateInput.value = '';
                if (addStartTimeInput) addStartTimeInput.value = '';
                if (addDueTimeInput) addDueTimeInput.value = '';
                addModal.classList.remove('modal-hidden');
                const titleInput = addModal.querySelector('input[name="title"]');
                if (titleInput) setTimeout(() => titleInput.focus(), 150);
            }
        });
    });

    // Buka modal tambah dari klik tanggal kalender tertentu
    addAgendaDateButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const chosenDate = btn.getAttribute('data-date');
            if (addModal) {
                if (addDueDateInput && chosenDate) {
                    addDueDateInput.value = chosenDate;
                }
                if (addStartTimeInput) addStartTimeInput.value = '';
                if (addDueTimeInput) addDueTimeInput.value = '';
                addModal.classList.remove('modal-hidden');
                const titleInput = addModal.querySelector('input[name="title"]');
                if (titleInput) setTimeout(() => titleInput.focus(), 150);
            }
        });
    });

    // Tutup modal tambah
    closeAddButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (addModal) addModal.classList.add('modal-hidden');
        });
    });

    // --------------------------------------------------------------------------
    // 3. MODAL EDIT TUGAS (Pengisian form secara dinamis dari atribut data-*)
    // --------------------------------------------------------------------------
    const editModal = document.getElementById('edit-task-modal');
    const editButtons = document.querySelectorAll('.btn-open-edit');
    const closeEditButtons = document.querySelectorAll('.btn-close-edit-modal');

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Ambil data tugas dari atribut data-* tombol yang diklik
            const id          = btn.getAttribute('data-id');
            const title       = btn.getAttribute('data-title');
            const description = btn.getAttribute('data-description');
            const category    = btn.getAttribute('data-category');
            const priority    = btn.getAttribute('data-priority');
            const status      = btn.getAttribute('data-status');
            const dueDate     = btn.getAttribute('data-due-date');
            const startTime   = btn.getAttribute('data-start-time');
            const dueTime     = btn.getAttribute('data-due-time');

            if (editModal) {
                // Isi form input di dalam modal edit
                document.getElementById('edit-task-id').value         = id;
                document.getElementById('edit-task-title').value      = title || '';
                document.getElementById('edit-task-desc').value       = description || '';
                document.getElementById('edit-task-category').value   = category || 'Umum';
                document.getElementById('edit-task-due-date').value   = dueDate || '';
                document.getElementById('edit-task-start-time').value = startTime ? startTime.substring(0, 5) : '';
                document.getElementById('edit-task-due-time').value   = dueTime ? dueTime.substring(0, 5) : '';

                // Set status dropdown jika ada
                const statusSelect = document.getElementById('edit-task-status');
                if (statusSelect && status) {
                    statusSelect.value = status;
                }

                // Pilih radio button prioritas yang sesuai
                const priorityRadio = editModal.querySelector(`input[name="priority"][value="${priority}"]`);
                if (priorityRadio) priorityRadio.checked = true;

                // Tampilkan modal
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
    // --------------------------------------------------------------------------
    const deleteModal = document.getElementById('delete-task-modal');
    const deleteButtons = document.querySelectorAll('.btn-open-delete');
    const closeDeleteButtons = document.querySelectorAll('.btn-close-delete-modal');

    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const id    = btn.getAttribute('data-id');
            const title = btn.getAttribute('data-title');

            if (deleteModal) {
                document.getElementById('delete-task-id').value = id;
                document.getElementById('delete-task-name').textContent = `"${title}"`;
                deleteModal.classList.remove('modal-hidden');
            }
        });
    });

    closeDeleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (deleteModal) deleteModal.classList.add('modal-hidden');
        });
    });

    // --------------------------------------------------------------------------
    // 5. TUTUP MODAL SAAT KLIK AREA LUAR (BACKDROP) ATAU TEKAN ESCAPE
    // --------------------------------------------------------------------------
    [addModal, editModal, deleteModal].forEach(modal => {
        if (!modal) return;
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('modal-hidden');
            }
        });
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            [addModal, editModal, deleteModal].forEach(modal => {
                if (modal && !modal.classList.contains('modal-hidden')) {
                    modal.classList.add('modal-hidden');
                }
            });
        }
    });

    // --------------------------------------------------------------------------
    // 6. LIVE SEARCH FILTER (Pencarian Instan Tanpa Reload Halaman)
    // --------------------------------------------------------------------------
    const searchInput = document.getElementById('search-task-input');
    const taskCards   = document.querySelectorAll('.task-card-item');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();

            taskCards.forEach(card => {
                const title    = card.getAttribute('data-search-title') || '';
                const desc     = card.getAttribute('data-search-desc') || '';
                const category = card.getAttribute('data-search-category') || '';

                if (title.includes(query) || desc.includes(query) || category.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // --------------------------------------------------------------------------
    // 7. DROPDOWN LONCENG NOTIFIKASI & BROWSER DESKTOP NOTIFICATION
    // --------------------------------------------------------------------------
    const btnToggleNotif = document.getElementById('btn-toggle-notif');
    const notifDropdown  = document.getElementById('notif-dropdown');
    const btnRequestNotif = document.getElementById('btn-request-browser-notif');

    if (btnToggleNotif && notifDropdown) {
        btnToggleNotif.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && e.target !== btnToggleNotif) {
                notifDropdown.classList.add('hidden');
            }
        });
    }

    if (btnRequestNotif && 'Notification' in window) {
        btnRequestNotif.addEventListener('click', async () => {
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

        // Jika izin sudah diberikan sebelumnya dan ada peringatan deadline mepet
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
