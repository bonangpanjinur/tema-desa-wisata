<?php get_header(); ?>

<!-- HERO SECTION -->
<section class="relative h-[600px] flex items-center justify-center bg-gray-900 text-white overflow-hidden">
    <!-- Background Image with Overlay -->
    <?php 
        $hero_bg = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'full') : 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80';
    ?>
    <div class="absolute inset-0 z-0">
        <img src="<?php echo $hero_bg; ?>" alt="Hero Background" class="w-full h-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 container mx-auto px-4 text-center">
        <span class="inline-block py-1 px-3 rounded-full bg-secondary/80 backdrop-blur-sm text-white text-xs font-bold uppercase tracking-wider mb-4 animate-fade-in-down">
            Selamat Datang di Desa Kami
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-6 leading-tight drop-shadow-lg max-w-4xl mx-auto">
            Temukan Keindahan Alam & <br> <span class="text-primary text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-green-600">Kearifan Lokal</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-200 mb-10 max-w-2xl mx-auto drop-shadow-md">
            Jelajahi destinasi wisata terbaik, nikmati kuliner khas, dan dukung produk UMKM desa kami.
        </p>

        <!-- Search Bar Besar -->
        <div class="max-w-3xl mx-auto bg-white/10 backdrop-blur-md p-2 rounded-full border border-white/20 shadow-2xl">
            <form action="<?php echo home_url('/'); ?>" method="get" class="flex flex-col md:flex-row gap-2">
                <div class="flex-grow relative">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-300"></i>
                    <input type="text" name="s" placeholder="Cari wisata, produk, atau artikel..." 
                           class="w-full bg-white/90 border-0 rounded-full py-3 pl-12 pr-4 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:bg-white transition h-12">
                </div>
                <button type="submit" class="bg-primary hover:bg-green-600 text-white font-bold py-3 px-8 rounded-full transition shadow-lg h-12 flex items-center justify-center gap-2">
                    Cari Sekarang
                </button>
            </form>
        </div>
    </div>
</section>

<!-- SECTION: Destinasi Populer -->
<section class="py-20 bg-surface">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-end mb-12">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">Destinasi Favorit</h2>
                <div class="h-1 w-20 bg-primary rounded-full"></div>
                <p class="mt-4 text-gray-600 max-w-xl">Tempat-tempat wisata yang paling sering dikunjungi dan mendapatkan rating tertinggi dari wisatawan.</p>
            </div>
            <a href="<?php echo home_url('/wisata'); ?>" class="hidden md:inline-flex items-center text-primary font-semibold hover:text-green-800 transition">
                Lihat Semua <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php 
            $args_wisata = array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 3,
                'orderby' => 'meta_value_num',
                'meta_key' => 'rating_wisata', // Urutkan rating
                'order' => 'DESC'
            );
            $query_wisata = new WP_Query($args_wisata);
            
            if ($query_wisata->have_posts()) :
                while ($query_wisata->have_posts()) : $query_wisata->the_post();
                    get_template_part('template-parts/card', 'wisata');
                endwhile;
                wp_reset_postdata();
            else :
                echo '<div class="col-span-3 text-center py-10 bg-white rounded-xl shadow-sm border border-dashed border-gray-300">Belum ada data wisata.</div>';
            endif; 
            ?>
        </div>

        <div class="mt-8 text-center md:hidden">
            <a href="<?php echo home_url('/wisata'); ?>" class="btn-outline-primary">Lihat Semua Wisata</a>
        </div>
    </div>
</section>

<!-- SECTION: Produk Desa (Grid Produk) -->
<section class="py-20 bg-white border-t border-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <span class="text-secondary font-bold uppercase tracking-wider text-sm">Belanja Lokal</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mt-2 mb-4">Produk Unggulan Desa</h2>
            <p class="text-gray-500 max-w-2xl mx-auto">
                Dukung ekonomi warga dengan membeli produk asli buatan tangan mereka. Kualitas terjamin dan autentik.
            </p>
        </div>

        <!-- Grid 4 Kolom -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-8">
            <?php 
            $args_produk = array(
                'post_type' => 'dw_produk',
                'posts_per_page' => 8,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $query_produk = new WP_Query($args_produk);
            
            if ($query_produk->have_posts()) :
                while ($query_produk->have_posts()) : $query_produk->the_post();
                    get_template_part('template-parts/card', 'produk');
                endwhile;
                wp_reset_postdata();
            else :
                echo '<div class="col-span-full text-center py-12 text-gray-500">Belum ada produk yang dijual.</div>';
            endif; 
            ?>
        </div>

        <div class="text-center mt-12">
            <a href="<?php echo home_url('/produk'); ?>" class="inline-block bg-white border-2 border-primary text-primary px-8 py-3 rounded-full font-bold hover:bg-primary hover:text-white transition shadow-sm">
                Lihat Katalog Lengkap
            </a>
        </div>
    </div>
</section>

<!-- SECTION: CTA Mitra -->
<section class="py-16 bg-gradient-to-r from-primary to-green-700 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 -mr-20 -mt-20 opacity-10">
        <i class="fas fa-handshake text-[300px]"></i>
    </div>
    
    <div class="container mx-auto px-4 relative z-10 flex flex-col md:flex-row items-center justify-between">
        <div class="mb-8 md:mb-0 md:max-w-2xl">
            <h2 class="text-3xl font-bold mb-4">Ingin Memasarkan Produk atau Jasa Anda?</h2>
            <p class="text-green-100 text-lg">
                Bergabunglah dengan platform digital Desa Wisata. Gratis pendaftaran untuk warga lokal.
                Tingkatkan pendapatan dengan jangkauan pasar yang lebih luas.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="<?php echo home_url('/register'); ?>" class="bg-secondary text-white px-6 py-4 rounded-lg font-bold shadow-lg hover:bg-yellow-600 transition text-center">
                Daftar Sekarang
            </a>
            <a href="<?php echo home_url('/tentang'); ?>" class="bg-white/10 backdrop-blur-sm border border-white/30 text-white px-6 py-4 rounded-lg font-bold hover:bg-white/20 transition text-center">
                Pelajari Syarat
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>