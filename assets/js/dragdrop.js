/**
 * ==============================================================================
 * FILE: assets/js/dragdrop.js
 * FUNGSI UTAMA: Mengatur fitur Drag & Drop interaktif menggunakan HTML5 Drag and Drop API
 *               dan mengirim pembaruan status ke server PHP via Fetch API (AJAX).
 * ==============================================================================
 * 🎓 PANDUAN BELAJAR UKK (ALUR KERJA DRAG & DROP):
 * 1. Event 'dragstart' : Dipicu saat pengguna mulai menekan dan menarik kartu tugas.
 * 2. Event 'dragover'  : Dipicu saat kartu berada di atas kolom tujuan. 
 *                        (Wajib ada e.preventDefault() agar browser mengizinkan drop).
 * 3. Event 'dragleave' : Dipicu saat kartu keluar dari kolom tujuan tanpa dijatuhkan.
 * 4. Event 'drop'      : Dipicu saat kartu dilepaskan ke kolom baru.
 *                        - Kartu langsung dipindahkan di tampilan layar via DOM.
 *                        - Mengirim data JSON ke actions.php?action=update_status via Fetch API.
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    let draggedCard = null;  // [VARIABEL]: Menyimpan elemen kartu yang sedang dipegang/diseret
    let sourceColumn = null; // [VARIABEL]: Menyimpan kolom asal kartu sebelum diseret

    // Mengambil semua elemen kartu yang dapat diseret (draggable="true")
    const cards = document.querySelectorAll('.task-card-item[draggable="true"]');
    
    // Mengambil 3 kolom drop zone (Belum Mulai, Dalam Proses, Selesai)
    const dropZones = document.querySelectorAll('.kanban-drop-zone');

    // --------------------------------------------------------------------------
    // 1. EVENT PADA SETIAP KARTU TUGAS (DRAGGABLE ITEMS)
    // --------------------------------------------------------------------------
    cards.forEach(card => {
        
        // 🎯 [EVENT DRAGSTART]: Saat pengguna mulai menyeret kartu
        card.addEventListener('dragstart', (e) => {
            draggedCard = card;
            sourceColumn = card.closest('.kanban-drop-zone'); // Catat kolom asalnya
            
            // Simpan ID tugas ke memori clipboard sementara DataTransfer browser
            const taskId = card.getAttribute('data-id');
            e.dataTransfer.setData('text/plain', taskId);
            e.dataTransfer.effectAllowed = 'move';

            // ✏️ [TIPS UJIAN]: Mengubah efek visual kartu saat sedang melayang diseret
            // Di sini kartu dibuat agak transparan (opacity-40) dan mengecil sedikit (scale-95)
            setTimeout(() => {
                card.classList.add('opacity-40', 'scale-95', 'rotate-1');
            }, 0);
        });

        // 🎯 [EVENT DRAGEND]: Saat menyeret selesai (baik berhasil di-drop atau dibatalkan)
        card.addEventListener('dragend', () => {
            if (draggedCard) {
                // Kembalikan transparansi kartu ke semula
                draggedCard.classList.remove('opacity-40', 'scale-95', 'rotate-1');
            }
            draggedCard = null;
            sourceColumn = null;

            // Bersihkan efek border highlight dari semua kolom
            dropZones.forEach(zone => zone.classList.remove('drag-over-active'));
        });
    });

    // --------------------------------------------------------------------------
    // 2. EVENT PADA KOLOM KANBAN (DROP ZONES)
    // --------------------------------------------------------------------------
    dropZones.forEach(zone => {
        
        // 🎯 [EVENT DRAGOVER]: Saat kartu berada melayang di atas kolom tujuan
        // 💡 Catatan: Baris e.preventDefault() WAJIB ada, jika tidak event 'drop' tidak akan pernah menyala!
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.classList.add('drag-over-active'); // Beri highlight border pada kolom
        });

        // 🎯 [EVENT DRAGLEAVE]: Saat kursor keluar dari batas kolom tujuan
        zone.addEventListener('dragleave', (e) => {
            if (!zone.contains(e.relatedTarget)) {
                zone.classList.remove('drag-over-active');
            }
        });

        // 🎯 [EVENT DROP]: Saat kartu dilepaskan ke dalam kolom tujuan
        zone.addEventListener('drop', async (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over-active'); // Hilangkan highlight border

            if (!draggedCard) return;

            // Ambil data status tujuan, status saat ini, dan ID tugas
            const targetStatus  = zone.getAttribute('data-status');      // contoh: 'completed'
            const currentStatus = draggedCard.getAttribute('data-status'); // contoh: 'todo'
            const taskId        = draggedCard.getAttribute('data-id');     // contoh: '5'

            // Jika dilepas di kolom yang sama (tidak berpindah), batalkan proses
            if (targetStatus === currentStatus) return;

            // Hapus placeholder dashed garis putus-putus jika ada
            const targetPlaceholder = zone.querySelector('.empty-column-placeholder');
            if (targetPlaceholder) targetPlaceholder.remove();

            // 💡 [DOM MANIPULATION]: Pindahkan kartu ke kolom baru secara visual seketika
            zone.appendChild(draggedCard);

            // Perbarui atribut status pada elemen kartu
            draggedCard.setAttribute('data-status', targetStatus);

            // 🎯 JIKA TUGAS DI-DROP KE KOLOM SELESAI (COMPLETED):
            // Otomatis hilangkan kotak pop-up notifikasi dan matikan alarmnya seketika!
            if (targetStatus === 'completed') {
                if (typeof window.resolveTaskAlarm === 'function') {
                    window.resolveTaskAlarm(taskId);
                }
            } else {
                if (window.TASKFLOW_URGENT && Array.isArray(window.TASKFLOW_URGENT)) {
                    const taskObj = window.TASKFLOW_URGENT.find(t => t.id == taskId);
                    if (taskObj) taskObj.status = targetStatus;
                }
            }

            // ✏️ [TIPS UJIAN]: Jika tugas dipindah ke kolom 'completed' (Selesai),
            // coret teks judulnya dengan class 'line-through'
            const titleElem = draggedCard.querySelector('h4');
            if (titleElem) {
                if (targetStatus === 'completed') {
                    titleElem.classList.add('line-through', 'text-slate-400');
                } else {
                    titleElem.classList.remove('line-through', 'text-slate-400');
                }
            }

            // Hitung ulang angka badge jumlah tugas di header setiap kolom
            updateColumnCounters();

            // 💡 [AJAX FETCH API]: Kirim data perubahan status ke server backend PHP tanpa reload halaman
            try {
                const response = await fetch('actions.php?action=update_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: parseInt(taskId),
                        status: targetStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Munculkan notifikasi pop-up kecil di pojok kanan bawah
                    showQuickToast('success', result.message);
                } else {
                    showQuickToast('danger', result.message || 'Gagal mengubah status.');
                    // Jika database menolak, kembalikan kartu ke kolom asalnya
                    if (sourceColumn) sourceColumn.appendChild(draggedCard);
                    updateColumnCounters();
                }
            } catch (error) {
                console.error('Error saat update status via drag & drop:', error);
                showQuickToast('danger', 'Terjadi kesalahan jaringan atau server MySQL.');
                // Jika jaringan error, kembalikan posisi kartu ke kolom asal
                if (sourceColumn) sourceColumn.appendChild(draggedCard);
                updateColumnCounters();
            }
        });
    });

    /**
     * --------------------------------------------------------------------------
     * 3. FUNGSI UPDATE HITUNGAN JUMLAH TUGAS DI HEADER KOLOM
     * 🎯 FUNGSI: Menghitung berapa banyak kartu tugas yang ada di setiap kolom
     * --------------------------------------------------------------------------
     */
    function updateColumnCounters() {
        dropZones.forEach(zone => {
            const status = zone.getAttribute('data-status');
            const cardCount = zone.querySelectorAll('.task-card-item').length;
            const badge = document.querySelector(`.column-count-badge[data-col="${status}"]`);
            
            // Perbarui angka di badge
            if (badge) {
                badge.textContent = cardCount;
            }

            // Jika kolom menjadi kosong (0 kartu), buat kembali placeholder dashed
            if (cardCount === 0 && !zone.querySelector('.empty-column-placeholder')) {
                const placeholder = document.createElement('div');
                placeholder.className = 'empty-column-placeholder text-center py-10 border-2 border-dashed border-slate-200 rounded-xl';
                placeholder.innerHTML = `
                    <i class="fa-solid fa-arrows-up-down-left-right text-slate-300 text-xl mb-1"></i>
                    <p class="text-xs text-slate-400">Geser agenda/tugas ke sini</p>
                `;
                zone.appendChild(placeholder);
            }
        });
    }

    /**
     * --------------------------------------------------------------------------
     * 4. FUNGSI NOTIFIKASI TOAST MELAYANG (QUICK TOAST)
     * 🎯 FUNGSI: Menampilkan pesan melayang kecil di pojok kanan bawah
     * ✏️ TIPS: Jika ingin mengubah durasi tampilan pesan, ubah angka 3000 (3 detik)
     * --------------------------------------------------------------------------
     */
    function showQuickToast(type, message) {
        let toastContainer = document.getElementById('quick-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'quick-toast-container';
            toastContainer.className = 'fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        const isSuccess = (type === 'success');
        toast.className = `flex items-center space-x-2 px-4 py-3 rounded-xl shadow-lg border text-xs font-semibold transition-all duration-300 pointer-events-auto transform translate-y-2 opacity-0 ${
            isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
        }`;
        toast.innerHTML = `
            <i class="${isSuccess ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-exclamation text-rose-600'} text-sm"></i>
            <span>${message}</span>
        `;

        toastContainer.appendChild(toast);

        // Efek animasi meluncur masuk
        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 10);

        // ✏️ [TIPS MENGUBAH DURASI]: Angka 3000 ms = 3 detik sebelum toast hilang
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
