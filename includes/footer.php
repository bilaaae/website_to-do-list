<?php
// ==============================================================================
// FILE: includes/footer.php
// FUNGSI: Footer halaman web, menyertakan berkas JavaScript (app.js & dragdrop.js),
//         serta tag penutup </body> dan </html>.
// ==============================================================================
?>
    </main>

    <!-- FOOTER INFORMASI PROYEK -->
    <footer class="mt-16 text-center text-xs text-slate-500 border-t border-slate-200/80 py-6 max-w-7xl mx-auto px-4">
        <p class="font-semibold text-slate-700">TaskFlow &copy; <?= date('Y') ?> &mdash; To-Do List & Agenda Kalender Produktivitas</p>
        <p class="mt-1 text-[11px] text-slate-500">
            Dibuat untuk Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak &bull; PHP (PDO) &bull; MySQL &bull; Custom CSS3 &bull; JavaScript
        </p>
    </footer>

    <!-- BERKAS JAVASCRIPT -->
    <!-- 1. app.js: Mengatur pembukaan modal pop-up, live search filter, dan pemilihan tanggal kalender -->
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
    <!-- 2. dragdrop.js: Mengatur fitur Drag and Drop interaktif dan update status real-time via AJAX -->
    <script src="assets/js/dragdrop.js?v=<?= time() ?>"></script>

</body>
</html>
