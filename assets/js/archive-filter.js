/**
 * Archive Filter Logic
 * Menangani interaksi filter pada halaman arsip wisata dan produk.
 * Desain interaksi yang simpel dan modern.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. Auto-Submit untuk Radio Button (Lokasi & Kategori) ---
    // Memberikan pengalaman instan saat pengguna memilih filter.
    const radioFilters = document.querySelectorAll('input[type="radio"][name="kab"], input[type="radio"][name="kat"]');
    
    radioFilters.forEach(radio => {
        radio.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                // Opsional: Tambahkan indikator loading visual di sini jika diinginkan
                // showLoadingState(); 
                form.submit();
            }
        });
    });

    // --- 2. Auto-Submit untuk Dropdown Urutan (Sort) ---
    // Memudahkan pengguna mengurutkan hasil tanpa tombol tambahan.
    const sortSelects = document.querySelectorAll('select[name="sort"]');
    
    sortSelects.forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });

    // --- 3. Toggle Filter Mobile (Smooth & Elegan) ---
    // Menangani tampilan filter di perangkat mobile dengan transisi halus.
    const filterBtn = document.getElementById('mobile-filter-btn'); 
    const filterContainer = document.getElementById('mobile-filter');
    
    if (filterBtn && filterContainer) {
        filterBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Mencegah perilaku default tombol
            
            // Toggle visibility dengan kelas utility Tailwind atau CSS custom
            filterContainer.classList.toggle('hidden');
            
            // Logika untuk scroll otomatis yang mulus ke area filter saat dibuka
            if (!filterContainer.classList.contains('hidden')) {
                // Memberikan sedikit delay agar transisi display selesai (jika ada animasi CSS)
                setTimeout(() => {
                    filterContainer.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }, 100);
            }
        });
    }

    // --- 4. Fungsi Tambahan: Reset Filter (Opsional) ---
    // Jika ada tombol reset, tambahkan logika untuk membersihkan form dengan bersih.
    const resetBtn = document.querySelector('.reset-filter-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            // Logika reset bisa disesuaikan, misal redirect ke URL dasar arsip
            // window.location.href = window.location.pathname;
        });
    }

});

// --- Helper Functions (Bisa dikembangkan) ---
// Fungsi untuk menampilkan indikator loading (placeholder)
function showLoadingState() {
    const gridContainer = document.querySelector('.grid-content'); // Sesuaikan selector
    if (gridContainer) {
        gridContainer.style.opacity = '0.5';
        gridContainer.style.pointerEvents = 'none';
    }
}