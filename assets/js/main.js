/**
 * Main JS - Tema Desa Wisata
 */

jQuery(document).ready(function($) {
    
    // --- PRODUCT TABS ---
    $('.product-tabs .tab-nav a').on('click', function(e) {
        e.preventDefault();
        $('.product-tabs .tab-nav a').removeClass('active');
        $('.product-tabs .tab-pane').removeClass('active');
        $(this).addClass('active');
        var targetId = $(this).attr('href');
        $(targetId).addClass('active');
    });

    // --- CAROUSEL BANNER LOGIC ---
    const track = document.getElementById('carouselTrack');
    
    if (track) {
        const slides = Array.from(track.children);
        const indicators = document.querySelectorAll('.indicator');
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        let currentIndex = 0;

        const updateCarousel = (index) => {
            const slideWidth = slides[0].getBoundingClientRect().width;
            track.style.transform = 'translateX(-' + (slideWidth * index) + 'px)';
            
            indicators.forEach(ind => ind.classList.remove('active'));
            if(indicators[index]) indicators[index].classList.add('active');
        };

        if(nextBtn) nextBtn.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % slides.length;
            updateCarousel(currentIndex);
        });

        if(prevBtn) prevBtn.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            updateCarousel(currentIndex);
        });

        // Auto Slide (4 detik)
        setInterval(() => {
            currentIndex = (currentIndex + 1) % slides.length;
            updateCarousel(currentIndex);
        }, 4000);

        // Responsiveness
        window.addEventListener('resize', () => {
            updateCarousel(currentIndex);
        });
        
        // Indicator Click
        indicators.forEach(ind => {
            ind.addEventListener('click', (e) => {
                const index = parseInt(e.target.dataset.index);
                currentIndex = index;
                updateCarousel(currentIndex);
            });
        });
    }

});