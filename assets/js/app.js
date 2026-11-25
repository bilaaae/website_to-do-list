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
    // 7. SISTEM ALARM, SUARA LONCENG, & NOTIFIKASI DESKTOP
    // --------------------------------------------------------------------------
    const btnToggleNotif  = document.getElementById('btn-toggle-notif');
    const notifDropdown   = document.getElementById('notif-dropdown');
    const btnRequestNotif = document.getElementById('btn-request-browser-notif');

    // 🔊 AUDIO ENGINE (Dual Engine: HTML5 Audio + Web Audio API Reusable Context)
    let sharedAudioCtx = null;

    function getAudioContext() {
        try {
            if (!sharedAudioCtx) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) sharedAudioCtx = new AudioCtx();
            }
            if (sharedAudioCtx && sharedAudioCtx.state === 'suspended') {
                sharedAudioCtx.resume();
            }
            return sharedAudioCtx;
        } catch (e) {
            return null;
        }
    }

    // Aktifkan AudioContext begitu ada interaksi apapun di halaman
    ['click', 'keydown', 'mousedown', 'touchstart', 'pointerdown'].forEach(evt => {
        window.addEventListener(evt, () => {
            getAudioContext();
        }, { once: false, passive: true });
    });

    // 🔊 FUNGSI PEMBUNYIAN NADA LONCENG ("Ding-Dong! 🔔")
    function playNotificationChime() {
        // 1. Putar file audio asli (assets/chime.wav)
        try {
            const sound = new Audio('assets/chime.wav');
            sound.volume = 1.0;
            sound.play().catch(() => {});
        } catch (e) {}

        // 2. Putar juga nada sintetis kristal (Jaminan 100% bunyi melalui Web Audio)
        try {
            const ctx = getAudioContext();
            if (!ctx) return;
            const now = ctx.currentTime;

            // NADA 1: "Ding" (E5 - 659.25 Hz)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'triangle';
            osc1.frequency.setValueAtTime(659.25, now);
            gain1.gain.setValueAtTime(0.85, now);
            gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.28);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.28);

            // NADA 2: "Dong" (A5 - 880 Hz)
            const t2 = now + 0.14;
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880, t2);
            gain2.gain.setValueAtTime(0.9, t2);
            gain2.gain.exponentialRampToValueAtTime(0.001, t2 + 0.55);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(t2);
            osc2.stop(t2 + 0.55);
        } catch (err) {
            console.error('Chime error:', err);
        }
    }

    // 🌟 POP-UP ALARM VISUAL LANGSUNG DI LAYAR (Tidak Bisa Diblokir Windows)
    function showInAppAlarmToast(title, message, isUrgent = true, badgeLabel = '') {
        let existing = document.getElementById('taskflow-live-toast');
        if (existing) existing.remove();

        const defaultBadge = isUrgent ? '⏰ ALARM WAKTU TIBA' : '⏳ PENGINGAT DEADLINE';
        const displayBadge = badgeLabel || defaultBadge;

        const toast = document.createElement('div');
        toast.id = 'taskflow-live-toast';
        toast.className = 'fixed bottom-5 right-5 z-[9999] bg-white border-2 ' + 
            (isUrgent ? 'border-rose-500 shadow-rose-500/25' : 'border-amber-500 shadow-amber-500/25') + 
            ' shadow-2xl rounded-2xl p-4 max-w-sm flex items-start gap-3 transition-all duration-300 animate-bounce cursor-pointer';
        
        toast.innerHTML = `
            <div class="w-10 h-10 rounded-xl ${isUrgent ? 'bg-rose-500' : 'bg-amber-500'} text-white flex items-center justify-center text-lg shrink-0 shadow-md">
                <i class="fa-solid fa-bell fa-shake"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold ${isUrgent ? 'text-rose-600' : 'text-amber-600'} uppercase tracking-wider flex items-center gap-1.5">
                    <span>${displayBadge}</span>
                    <span class="w-2 h-2 rounded-full ${isUrgent ? 'bg-rose-500' : 'bg-amber-500'} animate-ping"></span>
                </p>
                <h4 class="text-xs font-bold text-slate-800 leading-snug mt-0.5 truncate">${title}</h4>
                <p class="text-[11px] text-slate-600 mt-1">${message}</p>
                <p class="text-[10px] text-brand-600 mt-1 font-semibold flex items-center gap-1"><i class="fa-solid fa-volume-high"></i> Klik untuk bunyikan suara</p>
            </div>
            <button type="button" onclick="this.closest('#taskflow-live-toast').remove()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        `;

        toast.addEventListener('click', () => {
            playNotificationChime();
        });

        document.body.appendChild(toast);
    }

    // 🎯 FUNGSI HAPUS NOTIFIKASI SAAT TUGAS DISELESAIKAN (VIA DRAG & DROP / KLIK SELESAI)
    window.resolveTaskAlarm = function(taskId) {
        // 1. Tandai status tugas di memori JavaScript sebagai completed
        if (window.TASKFLOW_URGENT && Array.isArray(window.TASKFLOW_URGENT)) {
            const task = window.TASKFLOW_URGENT.find(t => t.id == taskId);
            if (task) {
                task.status = 'completed';
            }
        }

        // 2. Hilangkan kotak toast alarm melayang di layar seketika dengan animasi halus
        const liveToast = document.getElementById('taskflow-live-toast');
        if (liveToast) {
            liveToast.style.transition = 'all 0.3s ease';
            liveToast.style.opacity = '0';
            liveToast.style.transform = 'translateY(15px) scale(0.95)';
            setTimeout(() => liveToast.remove(), 300);
        }

        // 3. Kembalikan judul tab ke normal
        document.title = 'TaskFlow &mdash; To-Do List & Agenda Elegan';

        // 4. Periksa apakah masih ada tugas mepet lain yang belum selesai
        const remaining = (window.TASKFLOW_URGENT || []).filter(t => t.status !== 'completed');
        if (remaining.length === 0) {
            const banner = document.getElementById('urgent-alert-banner');
            if (banner) {
                banner.style.transition = 'all 0.3s ease';
                banner.style.opacity = '0';
                setTimeout(() => banner.remove(), 300);
            }
            const badge = document.querySelector('#btn-toggle-notif span');
            if (badge) badge.remove();
        }
    };

    // Toggle Dropdown Lonceng Notifikasi
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

    // Tombol Uji Suara Notifikasi Manual
    if (btnRequestNotif) {
        if ('Notification' in window && Notification.permission === 'granted') {
            btnRequestNotif.innerHTML = '<i class="fa-solid fa-volume-high text-brand-600"></i> <span>Uji Suara Notifikasi</span>';
        }

        btnRequestNotif.addEventListener('click', async () => {
            playNotificationChime();
            showInAppAlarmToast('Uji Coba Notifikasi', 'Suara lonceng dan pop-up pengingat deadline berfungsi normal!', false);

            if ('Notification' in window) {
                if (Notification.permission !== 'granted') {
                    await Notification.requestPermission();
                }
                if (Notification.permission === 'granted') {
                    new Notification('TaskFlow: Notifikasi Aktif! 🔔', {
                        body: 'Peringatan deadline agenda mepet aktif dan bersuara normal.',
                        icon: 'assets/img/logo.jpg'
                    });
                }
            }
        });
    }

    // ⏰ 1. ALARM PENGINGAT REAL-TIME (15 Menit Sebelum Deadline & Saat Jam Tiba)
    let notifiedTasks = {};

    function timeToMinutes(timeStr) {
        if (!timeStr) return null;
        const parts = timeStr.split(':');
        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    function checkTaskAlarms() {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        const todayStr = `${yyyy}-${mm}-${dd}`;

        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        if (window.TASKFLOW_URGENT && Array.isArray(window.TASKFLOW_URGENT)) {
            window.TASKFLOW_URGENT.forEach(task => {
                // Periksa semua tugas yang belum selesai dan jatuh tempo hari ini atau sebelumnya
                if (task.status !== 'completed' && (!task.due_date || task.due_date <= todayStr)) {
                    const taskMinutes = timeToMinutes(task.due_time);
                    if (taskMinutes === null) return;
                    const diffMinutes = taskMinutes - currentMinutes;

                    // 🟡 1. PENGINGAT SEBELUM DEADLINE (15 Sampai 6 Menit Sebelum Jam Deadline)
                    if (diffMinutes > 5 && diffMinutes <= 15 && !notifiedTasks[task.id + '_15m']) {
                        notifiedTasks[task.id + '_15m'] = true;
                        playNotificationChime();
                        document.title = `⏳ (${diffMinutes} Mnt Lagi) ${task.title}!`;
                        showInAppAlarmToast(task.title, `Pukul ${task.due_time} adalah batas waktu tugas ini (tinggal ${diffMinutes} menit lagi). Bersiaplah!`, false, `⏳ SISA ${diffMinutes} MENIT LAGI`);

                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification(`⏳ ${diffMinutes} Menit Lagi: ${task.title}!`, {
                                body: `Pukul ${task.due_time} adalah batas waktu tugas Anda (sisa ${diffMinutes} menit lagi).`,
                                icon: 'assets/img/logo.jpg'
                            });
                        }
                    }

                    // 🟠 2. PENGINGAT 5 MENIT SEBELUM DEADLINE (1 Sampai 5 Menit Sebelum Jam Deadline)
                    if (diffMinutes > 0 && diffMinutes <= 5 && !notifiedTasks[task.id + '_5m']) {
                        notifiedTasks[task.id + '_5m'] = true;
                        playNotificationChime();
                        document.title = `⚠️ (${diffMinutes} Mnt Lagi) ${task.title}!`;
                        showInAppAlarmToast(task.title, `Tinggal ${diffMinutes} menit lagi menuju pukul ${task.due_time}! Segera selesaikan.`, false, `⚠️ SISA ${diffMinutes} MENIT LAGI`);

                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification(`⚠️ ${diffMinutes} Menit Lagi: ${task.title}!`, {
                                body: `Tinggal ${diffMinutes} menit lagi menuju batas waktu pukul ${task.due_time}!`,
                                icon: 'assets/img/logo.jpg'
                            });
                        }
                    }

                    // 🔴 3. TEPAT JAM DEADLINE & JATUH TEMPO (0 Menit / Lewat)
                    if (diffMinutes <= 0 && !notifiedTasks[task.id + '_0m']) {
                        notifiedTasks[task.id + '_0m'] = true;
                        playNotificationChime();
                        document.title = `⏰ WAKTUNYA: ${task.title}!`;
                        showInAppAlarmToast(task.title, `Sudah jam ${task.due_time} tepat! Waktunya menyelesaikan agenda: ${task.title}.`, true, '⏰ ALARM WAKTU TIBA');

                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification(`⏰ WAKTUNYA TUGAS: ${task.title}!`, {
                                body: `Sudah jam ${task.due_time}! Waktunya menyelesaikan agenda: ${task.title}.`,
                                icon: 'assets/img/logo.jpg',
                                requireInteraction: true
                            });
                        }
                    }
                }
            });
        }
    }

    // Jalankan pengecekan pertama dan berkala setiap 5 detik
    checkTaskAlarms();
    setInterval(checkTaskAlarms, 5000);

    // ⏰ 2. Notifikasi pertama saat halaman dibuka jika ada banner tugas mepet
    const urgentBanner = document.getElementById('urgent-alert-banner');
    if (urgentBanner) {
        setTimeout(() => {
            playNotificationChime();
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('TaskFlow: Perhatian Deadline! ⚠️', {
                    body: 'Ada tugas atau agenda Anda yang mendekati batas waktu atau telah jatuh tempo. Segera periksa!',
                    icon: 'assets/img/logo.jpg'
                });
            }
        }, 1200);
    }

});
