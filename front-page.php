<?php get_header(); ?>

<!-- WRAPPER UTAMA (Background Abu-abu ala Marketplace) -->
<div class="bg-gray-50 min-h-screen pb-20 font-sans text-gray-700 relative">

    <!-- 1. HERO BANNER SECTION (DYNAMIC CAROUSEL) -->
    <section class="pt-4 md:pt-6">
        <div class="container mx-auto px-4">
            <!-- Banner Container -->
            <div class="relative rounded-2xl overflow-hidden shadow-md bg-white min-h-[160px] md:min-h-[400px]">
                <?php
                global $wpdb;
                $table_banner = $wpdb->prefix . 'dw_banner';
                
                $banners = [];
                // Cek tabel & ambil data
                if($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
                    $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'aktif' ORDER BY prioritas ASC");
                }

                if ($banners) : 
                ?>
                    <!-- Dynamic Carousel Wrapper -->
                    <div id="banner-carousel" class="relative w-full h-48 md:h-[400px] group">
                        <!-- Slides -->
                        <?php foreach($banners as $index => $banner): ?>
                            <div class="banner-slide absolute inset-0 transition-opacity duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'; ?>" data-index="<?php echo $index; ?>">
                                <a href="<?php echo $banner->link ? esc_url($banner->link) : '#'; ?>" class="block w-full h-full cursor-pointer">
                                    <img src="<?php echo esc_url($banner->gambar); ?>" alt="<?php echo esc_attr($banner->judul); ?>" class="w-full h-full object-cover">
                                    
                                    <?php if($banner->judul): ?>
                                    <!-- Caption Overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent flex flex-col justify-end p-6 md:p-16">
                                        <h2 class="text-white text-xl md:text-4xl font-bold mb-2 leading-tight drop-shadow-lg max-w-2xl">
                                            <?php echo esc_html($banner->judul); ?>
                                        </h2>
                                    </div>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Controls -->
                        <?php if(count($banners) > 1): ?>
                            <button id="prev-slide" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 backdrop-blur-md text-white border border-white/30 p-3 rounded-full z-20 transition hidden group-hover:block"><i class="fas fa-chevron-left"></i></button>
                            <button id="next-slide" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 backdrop-blur-md text-white border border-white/30 p-3 rounded-full z-20 transition hidden group-hover:block"><i class="fas fa-chevron-right"></i></button>
                            
                            <!-- Dots -->
                            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex space-x-2">
                                <?php foreach($banners as $index => $banner): ?>
                                    <button class="indicator-btn h-1.5 rounded-full transition-all duration-300 shadow-sm <?php echo $index === 0 ? 'bg-white w-8' : 'bg-white/50 w-2 hover:bg-white'; ?>" data-index="<?php echo $index; ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Script Slider Sederhana -->
                    <?php if(count($banners) > 1): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const slides = document.querySelectorAll('.banner-slide');
                            const indicators = document.querySelectorAll('.indicator-btn');
                            const prevBtn = document.getElementById('prev-slide');
                            const nextBtn = document.getElementById('next-slide');
                            let currentIndex = 0;
                            let slideInterval;
                            const totalSlides = slides.length;

                            function showSlide(index) {
                                slides.forEach((slide, i) => {
                                    if(i === index) {
                                        slide.classList.remove('opacity-0', 'z-0');
                                        slide.classList.add('opacity-100', 'z-10');
                                    } else {
                                        slide.classList.remove('opacity-100', 'z-10');
                                        slide.classList.add('opacity-0', 'z-0');
                                    }
                                });
                                indicators.forEach((dot, i) => {
                                    if(i === index) {
                                        dot.classList.remove('bg-white/50', 'w-2');
                                        dot.classList.add('bg-white', 'w-8');
                                    } else {
                                        dot.classList.add('bg-white/50', 'w-2');
                                        dot.classList.remove('bg-white', 'w-8');
                                    }
                                });
                                currentIndex = index;
                            }
                            function nextSlide() { showSlide((currentIndex + 1) % totalSlides); }
                            function prevSlide() { showSlide((currentIndex - 1 + totalSlides) % totalSlides); }
                            function startAutoPlay() { slideInterval = setInterval(nextSlide, 5000); }
                            function stopAutoPlay() { clearInterval(slideInterval); }

                            if(prevBtn && nextBtn) {
                                nextBtn.addEventListener('click', () => { stopAutoPlay(); nextSlide(); startAutoPlay(); });
                                prevBtn.addEventListener('click', () => { stopAutoPlay(); prevSlide(); startAutoPlay(); });
                            }
                            startAutoPlay();
                        });
                    </script>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Fallback Static Banner -->
                    <div class="relative h-48 md:h-[400px] w-full bg-gray-200 overflow-hidden group">
                        <img src="https://images.unsplash.com/photo-1596423735742-8367096e21df?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" 
                             alt="Desa Wisata" 
                             class="w-full h-full object-cover object-center">
                        <div class="absolute inset-0 bg-black/40 flex flex-col justify-center items-center text-center p-6">
                            <h2 class="text-white text-2xl md:text-5xl font-bold mb-2 drop-shadow-lg">Jelajahi Desa Wisata</h2>
                            <p class="text-white/90 text-sm md:text-lg max-w-lg">Temukan keindahan alam dan produk lokal terbaik langsung dari sumbernya.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Features Bar (Desktop Only) -->
            <div class="hidden md:grid grid-cols-4 gap-6 mt-6 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="bg-green-50 p-3 rounded-xl text-green-600"><i class="fas fa-check-circle text-xl"></i></div>
                    <div><h4 class="font-bold text-gray-800">Terverifikasi</h4><p class="text-xs text-gray-500">Wisata Resmi Desa</p></div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-orange-50 p-3 rounded-xl text-orange-500"><i class="fas fa-box-open text-xl"></i></div>
                    <div><h4 class="font-bold text-gray-800">Produk Asli</h4><p class="text-xs text-gray-500">Langsung dari UMKM</p></div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-blue-50 p-3 rounded-xl text-blue-600"><i class="fas fa-motorcycle text-xl"></i></div>
                    <div><h4 class="font-bold text-gray-800">Ojek Wisata</h4><p class="text-xs text-gray-500">Antar Jemput Aman</p></div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="bg-purple-50 p-3 rounded-xl text-purple-600"><i class="fas fa-headset text-xl"></i></div>
                    <div><h4 class="font-bold text-gray-800">Layanan 24/7</h4><p class="text-xs text-gray-500">Bantuan Wisatawan</p></div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. MOBILE MENU NAVIGATION -->
    <section class="md:hidden py-6 px-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="grid grid-cols-3 gap-2">
                <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-lg"><i class="fas fa-map-marked-alt"></i></div>
                    <span class="text-xs font-medium text-gray-600">Wisata</span>
                </a>
                <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center text-lg"><i class="fas fa-shopping-basket"></i></div>
                    <span class="text-xs font-medium text-gray-600">Produk</span>
                </a>
                <a href="<?php echo home_url('/ojek'); ?>" class="flex flex-col items-center gap-2">
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-lg"><i class="fas fa-motorcycle"></i></div>
                    <span class="text-xs font-medium text-gray-600">Ojek</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 3. WISATA POPULER SECTION -->
    <section class="py-6 md:py-10">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-xl md:text-3xl font-bold text-gray-900 mb-1">Destinasi Populer</h2>
                    <p class="text-gray-500 text-sm md:text-base">Tempat terbaik untuk liburanmu selanjutnya</p>
                </div>
                <a href="<?php echo home_url('/wisata'); ?>" class="text-green-600 font-semibold text-sm hover:text-green-700 flex items-center gap-1 transition">
                    Lihat Semua <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            <!-- List Wisata Horizontal Scroll on Mobile -->
            <div class="flex overflow-x-auto pb-4 -mx-4 px-4 md:mx-0 md:px-0 gap-4 md:grid md:grid-cols-3 lg:grid-cols-4 snap-x snap-mandatory scrollbar-hide">
                <?php 
                global $wpdb;
                $table_wisata = $wpdb->prefix . 'dw_wisata';
                $table_desa   = $wpdb->prefix . 'dw_desa';
                
                // Query: Ambil Wisata + Nama Desa
                $query_wisata = "
                    SELECT w.*, d.nama_desa 
                    FROM $table_wisata w
                    LEFT JOIN $table_desa d ON w.id_desa = d.id
                    WHERE w.status = 'aktif'
                    ORDER BY w.rating_avg DESC, w.created_at DESC
                    LIMIT 4
                ";
                
                $list_wisata = [];
                if($wpdb->get_var("SHOW TABLES LIKE '$table_wisata'") == $table_wisata) {
                    $list_wisata = $wpdb->get_results($query_wisata);
                }

                if (!empty($list_wisata)) :
                    foreach ($list_wisata as $wisata) :
                        // Kirim data ke Template Part
                        echo '<div class="min-w-[280px] w-[280px] md:w-auto flex-shrink-0 snap-center h-full">';
                        get_template_part('template-parts/card', 'wisata', array('data' => $wisata));
                        echo '</div>';
                    endforeach;
                else :
                    echo '<div class="col-span-full py-8 text-center bg-white rounded-xl border border-dashed border-gray-300 text-gray-500">Belum ada data wisata.</div>';
                endif; 
                ?>
            </div>
        </div>
    </section>

    <!-- 4. PRODUK DESA SECTION -->
    <section class="py-6 md:py-10 bg-white border-t border-gray-100">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-orange-100 text-orange-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Produk Lokal</span>
                    </div>
                    <h2 class="text-xl md:text-3xl font-bold text-gray-900">Oleh-oleh Khas Desa</h2>
                </div>
                <a href="<?php echo home_url('/produk'); ?>" class="text-orange-600 font-semibold text-sm hover:text-orange-700 transition">
                    Lihat Semua
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-6">
                <?php 
                $table_produk   = $wpdb->prefix . 'dw_produk';
                $table_pedagang = $wpdb->prefix . 'dw_pedagang';
                // Penting: Table desa harus ada di query ini agar 'nama_desa' tersedia di card produk
                $table_desa_prod = $wpdb->prefix . 'dw_desa';

                // Query: Produk + Info Toko + Info Desa
                $query_produk = "
                    SELECT p.*, pd.nama_toko, pd.kabupaten_nama, d.nama_desa 
                    FROM $table_produk p
                    LEFT JOIN $table_pedagang pd ON p.id_pedagang = pd.id
                    LEFT JOIN $table_desa_prod d ON pd.id_desa = d.id 
                    WHERE p.status = 'aktif' AND p.stok > 0
                    ORDER BY p.created_at DESC
                    LIMIT 12
                ";
                
                $list_produk = [];
                if($wpdb->get_var("SHOW TABLES LIKE '$table_produk'") == $table_produk) {
                    $list_produk = $wpdb->get_results($query_produk);
                }
                
                if (!empty($list_produk)) :
                    foreach ($list_produk as $produk) :
                        get_template_part('template-parts/card', 'produk', array('data' => $produk));
                    endforeach;
                else :
                    echo '<div class="col-span-full py-12 text-center text-gray-400 italic">Belum ada produk yang dijual saat ini.</div>';
                endif; 
                ?>
            </div>
            
            <div class="mt-8 text-center md:hidden">
                <a href="<?php echo home_url('/produk'); ?>" class="inline-block w-full py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 font-medium text-sm hover:bg-gray-100">
                    Jelajahi Produk Lainnya
                </a>
            </div>
        </div>
    </section>

    <!-- 5. ABOUT / SEO TEXT -->
    <section class="py-12 md:py-16 bg-gray-50 border-t border-gray-200">
        <div class="container mx-auto px-4 text-center">
            <div class="max-w-3xl mx-auto">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 text-green-600 mb-4">
                    <i class="fas fa-leaf text-xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Tentang Desa Wisata</h3>
                <p class="text-gray-500 leading-relaxed mb-6">
                    Platform digital terintegrasi yang menghubungkan Anda langsung dengan keautentikan desa-desa di Indonesia. 
                    Dukung ekonomi lokal dengan berwisata dan membeli produk asli dari UMKM desa.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="<?php echo home_url('/tentang'); ?>" class="text-green-600 font-medium hover:underline">Pelajari Lebih Lanjut</a>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. FLOATING UP BUTTON -->
    <!-- Updated: Hidden on Mobile (md:flex) -->
    <button id="scrollToTopBtn" class="fixed bottom-10 right-10 w-12 h-12 rounded-full bg-primary hover:bg-primaryDark text-white shadow-lg transition-all duration-300 opacity-0 invisible translate-y-4 z-50 hidden md:flex items-center justify-center group" aria-label="Kembali ke atas">
        <i class="fas fa-arrow-up text-lg group-hover:-translate-y-1 transition-transform"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollBtn = document.getElementById('scrollToTopBtn');
            
            // Show/Hide button on scroll
            window.addEventListener('scroll', function() {
                if (window.scrollY > 300) {
                    scrollBtn.classList.remove('opacity-0', 'invisible', 'translate-y-4');
                    scrollBtn.classList.add('opacity-100', 'visible', 'translate-y-0');
                } else {
                    scrollBtn.classList.add('opacity-0', 'invisible', 'translate-y-4');
                    scrollBtn.classList.remove('opacity-100', 'visible', 'translate-y-0');
                }
            });

            // Smooth scroll to top
            scrollBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>

</div>

<?php get_footer(); ?>