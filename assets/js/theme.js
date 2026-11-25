/**
 * ==============================================================================
 * FILE: assets/js/theme.js
 * FUNGSI: Pengatur Tema Mode Gelap (Dark Mode) & Terang (Light Mode)
 * - Menyimpan preferensi ke localStorage browser (agar tidak reset saat refresh)
 * - Mencegah kedipan putih (FOUC) saat halaman dimuat
 * - Mengubah ikon bulan (moon) dan matahari (sun) secara otomatis
 * ==============================================================================
 */

// 1. Eksekusi instan saat script dimuat (mencegah kedipan putih saat reload)
(function() {
    const savedTheme = localStorage.getItem('taskflow_theme');
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark', 'dark-mode');
    } else {
        document.documentElement.classList.remove('dark', 'dark-mode');
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    const themeButtons = document.querySelectorAll('#btn-theme-toggle, .btn-theme-toggle');
    const isDark = document.documentElement.classList.contains('dark-mode');

    // Pastikan body juga memiliki class dark-mode jika aktif
    if (isDark) {
        document.body.classList.add('dark-mode');
    }

    // Fungsi memperbarui ikon pada tombol toggle
    function updateIcons(darkActive) {
        themeButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (darkActive) {
                    icon.className = 'fa-solid fa-sun text-amber-400';
                    btn.setAttribute('title', 'Beralih ke Mode Terang');
                } else {
                    icon.className = 'fa-solid fa-moon text-slate-600';
                    btn.setAttribute('title', 'Beralih ke Mode Gelap');
                }
            }
        });
    }

    // Sinkronisasi ikon awal saat halaman dibuka
    updateIcons(isDark);

    // Event Listener untuk semua tombol tema
    themeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const currentlyDark = document.documentElement.classList.contains('dark-mode');
            const nextDark = !currentlyDark;

            if (nextDark) {
                document.documentElement.classList.add('dark', 'dark-mode');
                document.body.classList.add('dark-mode');
                localStorage.setItem('taskflow_theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark', 'dark-mode');
                document.body.classList.remove('dark-mode');
                localStorage.setItem('taskflow_theme', 'light');
            }

            updateIcons(nextDark);
        });
    });
});
