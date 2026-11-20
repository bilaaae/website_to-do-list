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
        <p class="font-medium text-slate-600">TaskFlow &mdash; Sistem Manajemen Tugas & Agenda Kalender Real-Time</p>
        <p class="mt-1 text-[11px] text-slate-500">Dibangun dengan PHP Native (PDO) &bull; MySQL &bull; Tailwind CSS &bull; Dynamic Real-Time Calendar</p>
    </footer>

    <!-- BERKAS JAVASCRIPT -->
    <!-- 1. app.js: Mengatur pembukaan modal pop-up, live search filter, dan pemilihan tanggal kalender -->
    <script src="assets/js/app.js"></script>
    <!-- 2. dragdrop.js: Mengatur fitur Drag and Drop interaktif dan update status real-time via AJAX -->
    <script src="assets/js/dragdrop.js"></script>

</body>
</html>
