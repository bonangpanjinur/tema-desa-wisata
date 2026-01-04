<?php if ( ! defined( "ABSPATH" ) ) { exit; } ?>
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
                    // Optimasi: Gunakan cache jika memungkinkan (manual via transient jika di server asli)
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
                
                $list_wisata = get_transient( 'dw_home_wisata' );
                if ( false === $list_wisata ) {
                    if($wpdb->get_var("SHOW TABLES LIKE '$table_wisata'") == $table_wisata) {
                        $query_wisata = "
                            SELECT w.*, d.nama_desa 
                            FROM $table_wisata w
                            LEFT JOIN $table_desa d ON w.id_desa = d.id
                            WHERE w.status = 'aktif'
                            ORDER BY w.rating_avg DESC
                            LIMIT 4
                        ";
                        $list_wisata = $wpdb->get_results($query_wisata);
                        set_transient( 'dw_home_wisata', $list_wisata, 4 * HOUR_IN_SECONDS );
                    }
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

    <!-- 4. PRODUK UNGGULAN SECTION -->
    <section class="py-6 md:py-10 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-xl md:text-3xl font-bold text-gray-900 mb-1">Produk Unggulan</h2>
                    <p class="text-gray-500 text-sm md:text-base">Karya terbaik dari UMKM desa kami</p>
                </div>
                <a href="<?php echo home_url('/produk'); ?>" class="text-orange-600 font-semibold text-sm hover:text-orange-700 flex items-center gap-1 transition">
                    Lihat Semua <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6">
                <?php 
                $table_produk = $wpdb->prefix . 'dw_produk';
                
                $list_produk = get_transient( 'dw_home_produk' );
                if ( false === $list_produk ) {
                    if($wpdb->get_var("SHOW TABLES LIKE '$table_produk'") == $table_produk) {
                        $query_produk = "
                            SELECT p.*, t.nama_toko 
                            FROM $table_produk p
                            LEFT JOIN {$wpdb->prefix}dw_pedagang t ON p.id_pedagang = t.id
                            WHERE p.status = 'aktif'
                            ORDER BY p.terjual DESC
                            LIMIT 8
                        ";
                        $list_produk = $wpdb->get_results($query_produk);
                        set_transient( 'dw_home_produk', $list_produk, 4 * HOUR_IN_SECONDS );
                    }
                }

                if (!empty($list_produk)) :
                    foreach ($list_produk as $produk) :
                        get_template_part('template-parts/card', 'produk', array('data' => $produk));
                    endforeach;
                else :
                    echo '<div class="col-span-full py-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-300 text-gray-500">Belum ada produk tersedia.</div>';
                endif; 
                ?>
            </div>
        </div>
    </section>

    <!-- 5. CTA SECTION -->
    <section class="py-12 md:py-20">
        <div class="container mx-auto px-4">
            <div class="bg-primary rounded-3xl p-8 md:p-16 text-center text-white relative overflow-hidden shadow-xl">
                <!-- Background Decor -->
                <div class="absolute top-0 left-0 w-64 h-64 bg-white/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-black/5 rounded-full translate-x-1/3 translate-y-1/3"></div>
                
                <div class="relative z-10 max-w-3xl mx-auto">
                    <h2 class="text-2xl md:text-4xl font-bold mb-4">Punya Produk Desa atau Destinasi Wisata?</h2>
                    <p class="text-white/90 text-sm md:text-lg mb-8">Bergabunglah bersama ribuan pengelola desa lainnya dan mulai pasarkan potensi desamu secara digital.</p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a href="<?php echo home_url('/register'); ?>" class="bg-white text-primary font-bold px-8 py-4 rounded-xl hover:bg-gray-100 transition shadow-lg">Daftar Sekarang</a>
                        <a href="<?php echo home_url('/tentang'); ?>" class="bg-primaryDark text-white font-bold px-8 py-4 rounded-xl hover:bg-primaryDark/80 transition border border-white/20">Pelajari Lebih Lanjut</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<?php get_footer(); ?>
