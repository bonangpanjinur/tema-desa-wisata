document.addEventListener('DOMContentLoaded', function() {
    // 1. Auto-submit form when radio buttons change (Lokasi / Kategori)
    const radioFilters = document.querySelectorAll('input[type="radio"][name="kab"], input[type="radio"][name="kat"]');
    radioFilters.forEach(radio => {
        radio.addEventListener('change', function() {
            // Temukan form terdekat dan submit
            this.closest('form').submit();
        });
    });

    // 2. Auto-submit sort dropdown
    const sortSelects = document.querySelectorAll('select[name="sort"]');
    sortSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // 3. Smooth Toggle Mobile Filter
    const filterBtn = document.getElementById('mobile-filter-btn'); // Pastikan ID ini ada di HTML jika digunakan
    const filterContainer = document.getElementById('mobile-filter');
    
    if(filterBtn && filterContainer) {
        filterBtn.addEventListener('click', () => {
            filterContainer.classList.toggle('hidden');
            // Opsional: Animasi slide down
            if(!filterContainer.classList.contains('hidden')) {
                filterContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
});