<?php
// ==============================================================================
// FILE: includes/modals.php
// FUNGSI: Menampung komponen pop-up dialog (Modal) untuk Tambah, Edit, dan Hapus tugas.
// ==============================================================================
?>

<!-- ========================================================================== -->
<!-- 1. MODAL TAMBAH TUGAS                                                      -->
<!-- ========================================================================== -->
<div id="add-task-modal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm modal-backdrop">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden modal-content">
        
        <!-- Header Modal -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Tambah Tugas Baru</h3>
            </div>
            <button type="button" class="btn-close-add-modal text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Form Tambah (Mengirim ke actions.php?action=add) -->
        <form action="actions.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">

            <!-- Judul Tugas -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Judul Tugas <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" required placeholder="Contoh: Menyiapkan materi presentasi UKK" 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
            </div>

            <!-- Deskripsi Tugas -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Deskripsi / Catatan Tambahan
                </label>
                <textarea name="description" rows="3" placeholder="Tambahkan rincian atau poin-poin penting tugas..." 
                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition resize-none"></textarea>
            </div>

            <!-- Grid Kategori & Status Kolom Awal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kategori</label>
                    <select name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                        <option value="Umum">Umum</option>
                        <option value="Pekerjaan">Pekerjaan</option>
                        <option value="Pribadi">Pribadi</option>
                        <option value="Belajar">Belajar</option>
                        <option value="Keuangan">Keuangan</option>
                        <option value="Kesehatan">Kesehatan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kolom Status</label>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                        <option value="todo">Belum Mulai</option>
                        <option value="in_progress">Sedang Dikerjakan</option>
                        <option value="completed">Selesai</option>
                    </select>
                </div>
            </div>

            <!-- Grid Tanggal, Jam Mulai & Jam Deadline -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Agenda</label>
                    <input type="date" name="due_date" id="add-task-due-date" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jam Mulai</label>
                    <input type="time" name="start_time" id="add-task-start-time" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jam Deadline</label>
                    <input type="time" name="due_time" id="add-task-due-time" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Pilihan Prioritas -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tingkat Prioritas</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="priority" value="low" class="peer sr-only">
                        <div class="p-2.5 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition">
                            <i class="fa-solid fa-arrow-down text-emerald-500 mr-1"></i> Rendah
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="priority" value="medium" checked class="peer sr-only">
                        <div class="p-2.5 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:bg-amber-50 peer-checked:border-amber-500 peer-checked:text-amber-700 transition">
                            <i class="fa-solid fa-minus text-amber-500 mr-1"></i> Sedang
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="priority" value="high" class="peer sr-only">
                        <div class="p-2.5 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700 transition">
                            <i class="fa-solid fa-arrow-up text-rose-500 mr-1"></i> Tinggi
                        </div>
                    </label>
                </div>
            </div>

            <!-- Footer Tombol -->
            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                <button type="button" class="btn-close-add-modal px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-md shadow-brand-600/20 transition active:scale-95">
                    Simpan Tugas
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================== -->
<!-- 2. MODAL EDIT TUGAS                                                        -->
<!-- ========================================================================== -->
<div id="edit-task-modal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm modal-backdrop">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden modal-content">
        
        <!-- Header Modal -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-sm">
                    <i class="fa-regular fa-pen-to-square"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Edit Tugas</h3>
            </div>
            <button type="button" class="btn-close-edit-modal text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Form Edit (actions.php?action=update) -->
        <form action="actions.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-task-id">

            <!-- Judul Tugas -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Judul Tugas <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="edit-task-title" required 
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
            </div>

            <!-- Deskripsi -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Deskripsi / Catatan Tambahan
                </label>
                <textarea name="description" id="edit-task-desc" rows="3" 
                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition resize-none"></textarea>
            </div>

            <!-- Grid Kategori & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kategori</label>
                    <select name="category" id="edit-task-category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                        <option value="Umum">Umum</option>
                        <option value="Pekerjaan">Pekerjaan</option>
                        <option value="Pribadi">Pribadi</option>
                        <option value="Belajar">Belajar</option>
                        <option value="Keuangan">Keuangan</option>
                        <option value="Kesehatan">Kesehatan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kolom Status</label>
                    <select name="status" id="edit-task-status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                        <option value="todo">Belum Mulai</option>
                        <option value="in_progress">Sedang Dikerjakan</option>
                        <option value="completed">Selesai</option>
                    </select>
                </div>
            </div>

            <!-- Grid Tanggal, Jam Mulai & Jam Deadline -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Agenda</label>
                    <input type="date" name="due_date" id="edit-task-due-date" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jam Mulai</label>
                    <input type="time" name="start_time" id="edit-task-start-time" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Jam Deadline</label>
                    <input type="time" name="due_time" id="edit-task-due-time" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Prioritas -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tingkat Prioritas</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="priority" value="low" class="peer sr-only">
                        <div class="p-2.5 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition">
                            <i class="fa-solid fa-arrow-down text-emerald-500 mr-1"></i> Rendah
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="priority" value="medium" class="peer sr-only">
                        <div class="p-2.5 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:bg-amber-50 peer-checked:border-amber-500 peer-checked:text-amber-700 transition">
                            <i class="fa-solid fa-minus text-amber-500 mr-1"></i> Sedang
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="priority" value="high" class="peer sr-only">
                        <div class="p-2.5 text-center rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700 transition">
                            <i class="fa-solid fa-arrow-up text-rose-500 mr-1"></i> Tinggi
                        </div>
                    </label>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                <button type="button" class="btn-close-edit-modal px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-md shadow-brand-600/20 transition active:scale-95">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================== -->
<!-- 3. MODAL KONFIRMASI HAPUS                                                  -->
<!-- ========================================================================== -->
<div id="delete-task-modal" class="modal-hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm modal-backdrop">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl border border-slate-200 overflow-hidden modal-content">
        <div class="p-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 text-xl">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800 mb-1">Hapus Tugas Ini?</h3>
            <p class="text-xs text-slate-500 mb-4">
                Apakah Anda yakin ingin menghapus tugas <br>
                <span id="delete-task-name" class="font-semibold text-slate-700"></span>?
            </p>

            <form action="actions.php" method="POST" class="flex items-center justify-center space-x-3">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete-task-id">

                <button type="button" class="btn-close-delete-modal w-1/2 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="w-1/2 px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-md shadow-rose-600/20 transition active:scale-95">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>
