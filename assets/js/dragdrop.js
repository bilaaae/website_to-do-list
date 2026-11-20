/**
 * ==============================================================================
 * FILE: assets/js/dragdrop.js
 * FUNGSI: Mengatur fitur Drag & Drop interaktif menggunakan HTML5 Drag and Drop API
 *         dan mengirim perubahan status ke server PHP melalui Fetch API (AJAX).
 * ==============================================================================
 * PENJELASAN UNTUK UKK:
 * 1. Event "dragstart": Terjadi saat pengguna mulai menyeret kartu tugas.
 * 2. Event "dragover": Terjadi saat kartu berada di atas kolom tujuan (wajib e.preventDefault() agar bisa di-drop).
 * 3. Event "dragleave": Terjadi saat kartu keluar dari kolom tujuan.
 * 4. Event "drop": Terjadi saat kartu dilepaskan di kolom tujuan.
 *    - Kartu langsung dipindahkan ke kolom baru di tampilan (DOM).
 *    - Dikirimkan request AJAX POST ke actions.php?action=update_status dengan format JSON.
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    let draggedCard = null; // Menyimpan elemen kartu yang sedang diseret
    let sourceColumn = null; // Kolom asal kartu sebelum diseret

    const cards = document.querySelectorAll('.task-card-item[draggable="true"]');
    const dropZones = document.querySelectorAll('.kanban-drop-zone');

    // --------------------------------------------------------------------------
    // 1. Inisialisasi Event Listener pada Setiap Kartu Tugas (Draggable Items)
    // --------------------------------------------------------------------------
    cards.forEach(card => {
        // Saat kartu mulai diseret
        card.addEventListener('dragstart', (e) => {
            draggedCard = card;
            sourceColumn = card.closest('.kanban-drop-zone');
            
            // Simpan data ID tugas ke DataTransfer
            const taskId = card.getAttribute('data-id');
            e.dataTransfer.setData('text/plain', taskId);
            e.dataTransfer.effectAllowed = 'move';

            // Efek visual saat diseret (sedikit transparan)
            setTimeout(() => {
                card.classList.add('opacity-40', 'scale-95', 'rotate-1');
            }, 0);
        });

        // Saat selesai menyeret (baik berhasil di-drop atau dibatalkan)
        card.addEventListener('dragend', () => {
            if (draggedCard) {
                draggedCard.classList.remove('opacity-40', 'scale-95', 'rotate-1');
            }
            draggedCard = null;
            sourceColumn = null;

            // Bersihkan semua highlight dropzone
            dropZones.forEach(zone => zone.classList.remove('drag-over-active'));
        });
    });

    // --------------------------------------------------------------------------
    // 2. Inisialisasi Event Listener pada Kolom Kanban (Drop Zones)
    // --------------------------------------------------------------------------
    dropZones.forEach(zone => {
        // Wajib: Mencegah default browser agar drop diizinkan
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.classList.add('drag-over-active');
        });

        // Saat kursor meninggalkan area kolom tujuan
        zone.addEventListener('dragleave', (e) => {
            // Pastikan bukan berpindah ke elemen anak di dalam kolom
            if (!zone.contains(e.relatedTarget)) {
                zone.classList.remove('drag-over-active');
            }
        });

        // Saat kartu dilepaskan ke kolom ini
        zone.addEventListener('drop', async (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over-active');

            if (!draggedCard) return;

            const targetStatus = zone.getAttribute('data-status');
            const currentStatus = draggedCard.getAttribute('data-status');
            const taskId = draggedCard.getAttribute('data-id');

            // Jika status tidak berubah (dilepas di kolom yang sama), abaikan
            if (targetStatus === currentStatus) return;

            // Hapus placeholder kosong di kolom tujuan jika ada
            const targetPlaceholder = zone.querySelector('.empty-column-placeholder');
            if (targetPlaceholder) targetPlaceholder.remove();

            // Pindahkan kartu secara visual ke kolom baru di tampilan (DOM)
            zone.appendChild(draggedCard);

            // Perbarui atribut status pada kartu
            draggedCard.setAttribute('data-status', targetStatus);

            // Jika statusnya 'completed', beri coretan garis (line-through), jika bukan hilangkan
            const titleElem = draggedCard.querySelector('h4');
            if (titleElem) {
                if (targetStatus === 'completed') {
                    titleElem.classList.add('line-through', 'text-slate-400');
                } else {
                    titleElem.classList.remove('line-through', 'text-slate-400');
                }
            }

            // Perbarui angka counter di header kolom
            updateColumnCounters();

            // Kirim request AJAX (Fetch API) ke server backend PHP
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
                    showQuickToast('success', result.message);
                } else {
                    showQuickToast('danger', result.message || 'Gagal mengubah status.');
                    // Jika gagal, kembalikan kartu ke kolom asal
                    if (sourceColumn) sourceColumn.appendChild(draggedCard);
                    updateColumnCounters();
                }
            } catch (error) {
                console.error('Error saat update status via drag & drop:', error);
                showQuickToast('danger', 'Terjadi kesalahan jaringan atau server MySQL.');
                if (sourceColumn) sourceColumn.appendChild(draggedCard);
                updateColumnCounters();
            }
        });
    });

    /**
     * Fungsi menghitung ulang jumlah kartu pada setiap kolom
     */
    function updateColumnCounters() {
        dropZones.forEach(zone => {
            const status = zone.getAttribute('data-status');
            const cardCount = zone.querySelectorAll('.task-card-item').length;
            const badge = document.querySelector(`.column-count-badge[data-col="${status}"]`);
            if (badge) {
                badge.textContent = cardCount;
            }

            // Tampilkan kembali placeholder jika kolom menjadi kosong
            if (cardCount === 0 && !zone.querySelector('.empty-column-placeholder')) {
                const placeholder = document.createElement('div');
                placeholder.className = 'empty-column-placeholder text-center py-10 border-2 border-dashed border-slate-200 rounded-xl';
                placeholder.innerHTML = `
                    <i class="fa-solid fa-arrows-up-down-left-right text-slate-300 text-xl mb-1"></i>
                    <p class="text-xs text-slate-400">Geser tugas ke sini</p>
                `;
                zone.appendChild(placeholder);
            }
        });
    }

    /**
     * Fungsi menampilkan notifikasi toast melayang kecil di pojok kanan bawah
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

        // Animasi masuk
        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 10);

        // Animasi keluar setelah 3 detik
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
