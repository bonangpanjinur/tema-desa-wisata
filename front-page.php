<?php get_header(); ?>

<!-- 1. Search Bar -->
<div class="px-5 mt-2 mb-4">
    <form action="<?php echo home_url('/'); ?>" method="get" class="relative group">
        <i class="ph ph-magnifying-glass absolute left-4 top-3.5 text-gray-400 text-lg group-focus-within:text-primary transition-colors"></i>
        <input type="text" name="s" class="w-full pl-11 pr-4 py-3 bg-white border-none rounded-2xl text-sm shadow-soft focus:ring-2 focus:ring-primary/20 placeholder-gray-400" placeholder="Cari desa, wisata, atau produk...">
    </form>
</div>

<!-- 2. Banner Slider (Modern Carousel with Dots & Autoplay) -->
<div class="mt-4 mb-6 relative px-5">
    <?php
    global $wpdb;
    $banners = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}dw_banner WHERE status = 'aktif' ORDER BY prioritas ASC");
    
    // Fallback data jika tidak ada banner di DB
    if (empty($banners)) {
        $banners = [
            (object)[
                'gambar' => 'https://images.unsplash.com/photo-1540206351-d6465b3ac5c1?q=80&w=600&auto=format&fit=crop',
                'judul' => 'Festival Panen Raya',
                'link' => '#'
            ],
            (object)[
                'gambar' => 'https://images.unsplash.com/photo-1516483638261-f4dbaf036963?q=80&w=600&auto=format&fit=crop',
                'judul' => 'Promo Staycation Desa',
                'link' => '#'
            ]
        ];
    }
    ?>

    <!-- Carousel Container -->
    <div class="relative w-full overflow-hidden rounded-2xl shadow-md h-[180px]">
        <!-- Slides Wrapper -->
        <div id="carousel-track" class="flex transition-transform duration-500 ease-out h-full">
            <?php foreach ($banners as $index => $banner) : ?>
                <div class="min-w-full h-full relative">
                    <img src="<?php echo esc_url($banner->gambar); ?>" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                    <div class="absolute bottom-8 left-4 right-4 text-white">
                        <span class="bg-primary/90 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-0.5 rounded mb-1 inline-block">Info</span>
                        <h3 class="font-bold text-lg leading-tight line-clamp-2"><?php echo esc_html($banner->judul); ?></h3>
                    </div>
                    <?php if (!empty($banner->link) && $banner->link !== '#') : ?>
                        <a href="<?php echo esc_url($banner->link); ?>" class="absolute inset-0 z-10"></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Dots -->
        <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 z-20">
            <?php foreach ($banners as $index => $banner) : ?>
                <button class="carousel-dot w-1.5 h-1.5 rounded-full bg-white/50 transition-all duration-300 <?php echo $index === 0 ? 'bg-white w-4' : ''; ?>" 
                        onclick="goToSlide(<?php echo $index; ?>)"></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 3. Menu Kategori -->
<div class="px-5 grid grid-cols-4 gap-4 mb-8">
    <?php
    $belanja_link = function_exists('get_post_type_archive_link') ? get_post_type_archive_link('dw_produk') : site_url('/produk');
    if (!$belanja_link) $belanja_link = site_url('/produk');

    $menus = [
        ['icon' => 'ph-map-trifold', 'label' => 'Wisata', 'link' => site_url('/wisata'), 'color' => 'text-blue-600 bg-blue-50'],
        ['icon' => 'ph-storefront', 'label' => 'Belanja', 'link' => $belanja_link, 'color' => 'text-orange-600 bg-orange-50'],
        ['icon' => 'ph-ticket', 'label' => 'Tiket', 'link' => '#', 'color' => 'text-purple-600 bg-purple-50'],
        ['icon' => 'ph-info', 'label' => 'Info', 'link' => '#', 'color' => 'text-green-600 bg-green-50'],
    ];
    foreach ($menus as $menu) : ?>
    <a href="<?php echo $menu['link']; ?>" class="flex flex-col items-center gap-2 group">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-sm <?php echo $menu['color']; ?> group-hover:scale-105 transition-transform">
            <i class="ph-fill <?php echo $menu['icon']; ?>"></i>
        </div>
        <span class="text-xs font-medium text-gray-600"><?php echo $menu['label']; ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- 4. Wisata Populer (Horizontal Scroll) -->
<div class="mb-8">
    <div class="flex justify-between items-center px-5 mb-3">
        <h2 class="text-lg font-bold text-gray-800">Wisata Pilihan</h2>
        <a href="<?php echo site_url('/wisata'); ?>" class="text-xs text-primary font-bold">Lihat Semua</a>
    </div>
    <div class="flex overflow-x-auto gap-4 px-5 pb-4 no-scrollbar">
        <?php
        $wisata = new WP_Query(['post_type' => 'dw_wisata', 'posts_per_page' => 5]);
        while ($wisata->have_posts()) : $wisata->the_post();
            $rating = get_post_meta(get_the_ID(), '_dw_rating', true) ?: '4.5';
        ?>
        <a href="<?php the_permalink(); ?>" class="min-w-[200px] bg-white rounded-2xl shadow-soft overflow-hidden block relative group">
            <div class="h-28 bg-gray-200 relative overflow-hidden">
                <?php if(has_post_thumbnail()) the_post_thumbnail('medium', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-110']); ?>
                <span class="absolute top-2 right-2 bg-white/90 px-1.5 py-0.5 rounded text-[10px] font-bold flex items-center gap-1 shadow-sm">
                    <i class="ph-fill ph-star text-yellow-400"></i> <?php echo $rating; ?>
                </span>
            </div>
            <div class="p-3">
                <h3 class="font-bold text-sm text-gray-800 truncate"><?php the_title(); ?></h3>
                <p class="text-[10px] text-gray-500 mt-1 flex items-center gap-1">
                    <i class="ph-fill ph-map-pin"></i> <?php echo get_post_meta(get_the_ID(), '_dw_lokasi', true) ?: 'Desa Wisata'; ?>
                </p>
            </div>
        </a>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</div>

<!-- 5. Produk UMKM (Grid) -->
<div class="px-5 mb-24">
    <div class="flex justify-between items-center mb-3">
        <h2 class="text-lg font-bold text-gray-800">Pasar Desa</h2>
        <a href="<?php echo $belanja_link; ?>" class="text-xs text-primary font-bold">Lihat Semua</a>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <?php
        $produk = new WP_Query(['post_type' => 'dw_produk', 'posts_per_page' => 6, 'orderby' => 'rand']);
        while ($produk->have_posts()) : $produk->the_post();
            $harga = get_post_meta(get_the_ID(), '_dw_harga', true);
            
            // Format Price Helper Logic inside template for safety
            $formatted_price = function_exists('dw_format_price') ? dw_format_price($harga) : 'Rp ' . number_format((float)$harga, 0, ',', '.');
        ?>
        <a href="<?php the_permalink(); ?>" class="bg-white p-2 rounded-2xl shadow-soft border border-gray-50 block group">
            <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden mb-2 relative">
                <?php if(has_post_thumbnail()) the_post_thumbnail('medium', ['class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105']); ?>
            </div>
            <h3 class="font-bold text-sm text-gray-800 line-clamp-2 leading-tight min-h-[2.5em]"><?php the_title(); ?></h3>
            <div class="mt-2 flex justify-between items-center">
                <span class="text-sm font-bold text-secondary"><?php echo esc_html($formatted_price); ?></span>
                <span class="w-6 h-6 bg-primary/10 rounded-full flex items-center justify-center text-primary text-xs group-hover:bg-primary group-hover:text-white transition-colors">
                    <i class="ph-bold ph-plus"></i>
                </span>
            </div>
        </a>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</div>

<!-- Carousel Logic Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const track = document.getElementById('carousel-track');
        const dots = document.querySelectorAll('.carousel-dot');
        const slides = track.children;
        const totalSlides = slides.length;
        let currentIndex = 0;
        let interval;

        // Function to move slide
        window.goToSlide = function(index) {
            currentIndex = index;
            const translateX = -(currentIndex * 100);
            track.style.transform = `translateX(${translateX}%)`;
            
            // Update dots
            dots.forEach((dot, idx) => {
                if (idx === currentIndex) {
                    dot.classList.add('bg-white', 'w-4');
                    dot.classList.remove('bg-white/50');
                } else {
                    dot.classList.remove('bg-white', 'w-4');
                    dot.classList.add('bg-white/50');
                }
            });
        }

        // Auto Play
        function startAutoPlay() {
            interval = setInterval(() => {
                let nextIndex = (currentIndex + 1) % totalSlides;
                goToSlide(nextIndex);
            }, 4000); // 4 seconds
        }

        if (totalSlides > 1) {
            startAutoPlay();
            
            // Pause on interaction (optional)
            track.addEventListener('touchstart', () => clearInterval(interval));
            track.addEventListener('touchend', startAutoPlay);
        }
    });
</script>

<?php get_footer(); ?>