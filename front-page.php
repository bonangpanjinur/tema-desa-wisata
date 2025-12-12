<?php
/**
 * Template Name: Halaman Depan
 * Description: Template khusus untuk halaman utama website Desa Wisata.
 */

get_header(); ?>

<!-- HERO SECTION -->
<div class="relative h-[600px] flex items-center justify-center overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80" alt="Pemandangan Desa" class="w-full h-full object-cover">
        <!-- Overlay Gradient -->
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>
    </div>

    <!-- Hero Content -->
    <div class="relative z-10 container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight drop-shadow-lg">
            Jelajahi Pesona <br> <span class="text-primary-light">Desa Nusantara</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-200 mb-10 max-w-2xl mx-auto font-light leading-relaxed">
            Temukan destinasi wisata tersembunyi, nikmati kuliner otentik, dan dukung ekonomi lokal dengan membeli produk asli dari desa.
        </p>
        
        <!-- Search Box -->
        <div class="bg-white/95 backdrop-blur-sm p-3 rounded-2xl shadow-2xl max-w-4xl mx-auto flex flex-col md:flex-row gap-3 transform transition hover:scale-[1.01] duration-300">
            <div class="flex-1 relative group">
                <i class="fas fa-search absolute left-4 top-4 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <input type="text" placeholder="Cari desa, wisata, atau produk..." class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 text-gray-700 placeholder-gray-400 bg-gray-50/50 outline-none transition-all">
            </div>
            
            <div class="md:w-1/3 relative group">
                <i class="fas fa-map-marker-alt absolute left-4 top-4 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                <select class="w-full pl-12 pr-10 py-3.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 text-gray-700 bg-gray-50/50 outline-none appearance-none cursor-pointer transition-all">
                    <option value="">Semua Lokasi</option>
                    <option value="jateng">Jawa Tengah</option>
                    <option value="diy">Yogyakarta</option>
                    <option value="bali">Bali</option>
                    <option value="jatim">Jawa Timur</option>
                </select>
                <i class="fas fa-chevron-down absolute right-4 top-4 text-gray-400 pointer-events-none text-xs"></i>
            </div>
            
            <button class="bg-primary hover:bg-primary-dark text-white font-bold py-3.5 px-8 rounded-xl transition-all shadow-lg hover:shadow-primary/30 flex items-center justify-center gap-2">
                <span>Cari</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
        
        <!-- Trending Tags -->
        <div class="mt-6 flex flex-wrap justify-center gap-2 text-sm text-gray-300">
            <span>Sedang Populer:</span>
            <a href="#" class="hover:text-white underline decoration-primary decoration-2 underline-offset-4">#DesaPenglipuran</a>
            <a href="#" class="hover:text-white underline decoration-primary decoration-2 underline-offset-4">#KopiGayo</a>
            <a href="#" class="hover:text-white underline decoration-primary decoration-2 underline-offset-4">#BatikTulis</a>
        </div>
    </div>
</div>

<!-- SECTION: KATEGORI -->
<section class="py-20 bg-white relative">
    <!-- Decorative Blob -->
    <div class="absolute top-0 left-0 w-64 h-64 bg-green-50 rounded-full mix-blend-multiply filter blur-3xl opacity-70 -translate-x-1/2 -translate-y-1/2"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-16">
            <span class="text-primary font-bold tracking-wider uppercase text-sm mb-2 block">Jelajahi Kategori</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Temukan Minat Anda</h2>
            <p class="text-gray-500 max-w-xl mx-auto">Kami mengelompokkan pengalaman terbaik agar Anda lebih mudah menemukan apa yang Anda cari.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <!-- Item 1 -->
            <a href="<?php echo home_url('/wisata'); ?>" class="group block text-center p-6 rounded-2xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-transparent hover:border-gray-100">
                <div class="w-20 h-20 mx-auto bg-green-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary group-hover:scale-110 transition-all duration-300">
                    <i class="fas fa-mountain text-3xl text-primary group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-primary transition-colors">Wisata Alam</h3>
                <p class="text-sm text-gray-500 line-clamp-2">Pegunungan, air terjun, dan pemandangan asri desa.</p>
            </a>
            
            <!-- Item 2 -->
            <a href="<?php echo home_url('/produk?cat=kuliner'); ?>" class="group block text-center p-6 rounded-2xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-transparent hover:border-gray-100">
                <div class="w-20 h-20 mx-auto bg-orange-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-orange-500 group-hover:scale-110 transition-all duration-300">
                    <i class="fas fa-utensils text-3xl text-orange-500 group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-orange-500 transition-colors">Kuliner Desa</h3>
                <p class="text-sm text-gray-500 line-clamp-2">Makanan tradisional dan oleh-oleh khas daerah.</p>
            </a>
            
            <!-- Item 3 -->
            <a href="<?php echo home_url('/produk?cat=kerajinan'); ?>" class="group block text-center p-6 rounded-2xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-transparent hover:border-gray-100">
                <div class="w-20 h-20 mx-auto bg-blue-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:scale-110 transition-all duration-300">
                    <i class="fas fa-shopping-bag text-3xl text-blue-600 group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">Kerajinan Tangan</h3>
                <p class="text-sm text-gray-500 line-clamp-2">Karya seni unik buatan tangan pengrajin lokal.</p>
            </a>
            
            <!-- Item 4 -->
            <a href="<?php echo home_url('/wisata?cat=budaya'); ?>" class="group block text-center p-6 rounded-2xl hover:bg-white hover:shadow-xl transition-all duration-300 border border-transparent hover:border-gray-100">
                <div class="w-20 h-20 mx-auto bg-purple-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:scale-110 transition-all duration-300">
                    <i class="fas fa-theater-masks text-3xl text-purple-600 group-hover:text-white transition-colors"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-purple-600 transition-colors">Seni & Budaya</h3>
                <p class="text-sm text-gray-500 line-clamp-2">Pertunjukan seni, tarian, dan upacara adat.</p>
            </a>
        </div>
    </div>
</section>

<!-- SECTION: DESTINASI POPULER (Slider Style) -->
<section class="py-20 bg-gray-50 border-y border-gray-200">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-4">
            <div>
                <span class="text-primary font-bold tracking-wider uppercase text-sm mb-2 block">Rekomendasi</span>
                <h2 class="text-3xl font-bold text-gray-800">Destinasi Populer</h2>
            </div>
            <a href="<?php echo home_url('/wisata'); ?>" class="group flex items-center gap-2 text-gray-600 hover:text-primary font-semibold transition-colors">
                Lihat Semua Wisata <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        <!-- Grid Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $args_wisata = array(
                'post_type'      => 'dw_wisata',
                'posts_per_page' => 3,
                'orderby'        => 'date',
                'order'          => 'DESC'
            );
            $query_wisata = new WP_Query( $args_wisata );

            if ( $query_wisata->have_posts() ) :
                while ( $query_wisata->have_posts() ) : $query_wisata->the_post();
                    $thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'large') : 'https://via.placeholder.com/600x400';
                    $lokasi = get_post_meta(get_the_ID(), '_dw_alamat', true) ?: 'Lokasi Desa';
                    $harga = get_post_meta(get_the_ID(), '_dw_harga_tiket', true) ?: 'Gratis';
            ?>
            <article class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 group flex flex-col h-full">
                <!-- Image Wrapper -->
                <div class="relative h-64 overflow-hidden">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                    
                    <!-- Overlay Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex gap-2">
                        <span class="bg-white/95 backdrop-blur text-xs font-bold px-3 py-1.5 rounded-full text-gray-800 shadow-sm flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i> 4.8
                        </span>
                    </div>
                    
                    <!-- Wishlist Button -->
                    <button class="absolute top-4 right-4 w-8 h-8 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-white hover:bg-white hover:text-red-500 transition">
                        <i class="far fa-heart"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 flex-1 flex flex-col">
                    <div class="flex items-start gap-2 mb-3 text-sm text-gray-500">
                        <i class="fas fa-map-marker-alt mt-1 text-primary"></i>
                        <span class="line-clamp-1"><?php echo esc_html($lokasi); ?></span>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-3 leading-snug group-hover:text-primary transition-colors">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>
                    
                    <p class="text-gray-500 text-sm line-clamp-2 mb-6 flex-1">
                        <?php echo wp_trim_words(get_the_excerpt(), 18); ?>
                    </p>
                    
                    <div class="pt-5 border-t border-dashed border-gray-200 flex items-center justify-between">
                        <div>
                            <span class="block text-xs text-gray-400 uppercase tracking-wide font-semibold">Tiket Masuk</span>
                            <span class="text-lg font-bold text-primary">
                                <?php echo is_numeric($harga) ? 'Rp ' . number_format($harga, 0, ',', '.') : esc_html($harga); ?>
                            </span>
                        </div>
                        
                        <!-- TOMBOL LIHAT DETAIL (WISATA) -->
                        <a href="<?php the_permalink(); ?>" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-bold text-gray-600 hover:bg-primary hover:text-white transition-colors">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            </article>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <div class="col-span-full py-12 text-center bg-white rounded-xl border border-dashed border-gray-300">
                    <i class="fas fa-map-marked-alt text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">Belum ada destinasi wisata yang ditampilkan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- SECTION: PROMO BANNER (Parallax) -->
<section class="relative py-24 bg-fixed bg-center bg-cover" style="background-image: url('https://images.unsplash.com/photo-1596401490209-6635c5dfb6d6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');">
    <div class="absolute inset-0 bg-gray-900/70"></div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-3 rounded-full bg-accent/20 border border-accent text-accent text-xs font-bold uppercase tracking-wider mb-6">Penawaran Spesial</span>
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6 leading-tight">Liburan Hemat di Desa Wisata</h2>
        <p class="text-lg text-gray-300 mb-10 max-w-2xl mx-auto">Dapatkan diskon penginapan dan paket wisata menarik untuk kunjungan Anda berikutnya. Rasakan keramahan warga lokal yang tak terlupakan.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?php echo home_url('/wisata'); ?>" class="bg-primary hover:bg-primary-dark text-white font-bold py-4 px-10 rounded-full transition shadow-lg hover:shadow-primary/50 transform hover:-translate-y-1">
                Lihat Paket Wisata
            </a>
            <a href="<?php echo home_url('/register'); ?>" class="bg-white hover:bg-gray-100 text-gray-900 font-bold py-4 px-10 rounded-full transition shadow-lg transform hover:-translate-y-1">
                Daftar Jadi Member
            </a>
        </div>
    </div>
</section>

<!-- SECTION: PRODUK UMKM -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-4">
            <div>
                <span class="text-primary font-bold tracking-wider uppercase text-sm mb-2 block">Oleh-Oleh & Kerajinan</span>
                <h2 class="text-3xl font-bold text-gray-800">Produk Asli Desa</h2>
            </div>
            
            <!-- Category Tabs -->
            <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                <button class="px-5 py-2 rounded-full bg-gray-900 text-white text-sm font-bold shadow-md whitespace-nowrap">Semua</button>
                <button class="px-5 py-2 rounded-full bg-white border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition whitespace-nowrap">Kuliner</button>
                <button class="px-5 py-2 rounded-full bg-white border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition whitespace-nowrap">Fashion</button>
                <button class="px-5 py-2 rounded-full bg-white border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition whitespace-nowrap">Kriya</button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            $args_produk = array(
                'post_type'      => 'dw_produk',
                'posts_per_page' => 8,
                'orderby'        => 'rand', // Randomize untuk variasi
            );
            $query_produk = new WP_Query( $args_produk );

            if ( $query_produk->have_posts() ) :
                while ( $query_produk->have_posts() ) : $query_produk->the_post();
                    $thumb_prod = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'medium') : 'https://via.placeholder.com/300x300';
                    $harga = get_post_meta(get_the_ID(), '_dw_harga_dasar', true);
                    $product_id = get_the_ID();
            ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col overflow-hidden relative">
                
                <!-- Image -->
                <div class="relative h-48 bg-gray-100 overflow-hidden">
                    <a href="<?php the_permalink(); ?>">
                        <img src="<?php echo esc_url($thumb_prod); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                    </a>
                    
                    <!-- Quick Add Button (Icon) -->
                    <button class="add-to-cart-btn absolute bottom-3 right-3 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-800 hover:bg-primary hover:text-white transition transform translate-y-12 group-hover:translate-y-0"
                            data-id="<?php echo $product_id; ?>"
                            data-title="<?php the_title(); ?>"
                            data-price="<?php echo esc_attr($harga); ?>"
                            data-thumb="<?php echo esc_url($thumb_prod); ?>"
                            title="Tambah ke Keranjang">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <!-- Info -->
                <div class="p-4 flex-1 flex flex-col">
                    <div class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">
                        <?php 
                        $cats = get_the_terms($product_id, 'kategori_produk');
                        echo $cats ? esc_html($cats[0]->name) : 'Umum';
                        ?>
                    </div>
                    
                    <h3 class="font-bold text-gray-800 mb-2 text-sm leading-snug line-clamp-2 hover:text-primary transition-colors flex-1">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    
                    <div class="flex items-center justify-between mt-3">
                        <div class="flex flex-col">
                            <span class="text-base font-bold text-primary">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                        </div>
                        
                        <!-- TOMBOL LIHAT DETAIL (PRODUK) -->
                        <a href="<?php the_permalink(); ?>" class="px-3 py-1.5 bg-gray-100 rounded-lg text-xs font-bold text-gray-600 hover:bg-primary hover:text-white transition-colors">
                            Detail
                        </a>
                    </div>
                </div>
            </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else:
            ?>
                <div class="col-span-full py-12 text-center text-gray-500 bg-gray-50 rounded-xl">Belum ada produk yang tersedia.</div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-12">
            <a href="<?php echo home_url('/produk'); ?>" class="inline-flex items-center gap-2 border-2 border-gray-200 text-gray-700 font-bold py-3 px-8 rounded-full hover:border-primary hover:text-primary transition">
                Lihat Semua Produk <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- SECTION: PETA DESA & INFO (Visual only for now) -->
<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col lg:flex-row">
            <div class="lg:w-1/2 relative min-h-[400px]">
                <!-- Placeholder Peta -->
                <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" class="absolute inset-0 w-full h-full object-cover" alt="Peta Desa">
                <div class="absolute inset-0 bg-primary/10 flex items-center justify-center group cursor-pointer">
                    <div class="bg-white/90 backdrop-blur px-6 py-3 rounded-full shadow-lg transform group-hover:scale-110 transition flex items-center gap-3">
                        <i class="fas fa-map-marked-alt text-primary text-xl"></i>
                        <span class="font-bold text-gray-800">Buka Peta Interaktif</span>
                    </div>
                </div>
            </div>
            <div class="lg:w-1/2 p-10 lg:p-16 flex flex-col justify-center">
                <span class="text-primary font-bold tracking-wider uppercase text-sm mb-4 block">Lokasi & Akses</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Mudah Dijangkau, Sulit Dilupakan</h2>
                <p class="text-gray-600 text-lg mb-8 leading-relaxed">
                    Desa-desa wisata kami tersebar di lokasi strategis yang mudah diakses dengan kendaraan pribadi maupun umum. Dilengkapi fasilitas parkir luas dan pusat informasi yang siap membantu.
                </p>
                
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-primary flex-shrink-0">
                            <i class="fas fa-car text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Akses Jalan Baik</h4>
                            <p class="text-sm text-gray-500">Jalan aspal mulus hingga ke pusat desa.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-primary flex-shrink-0">
                            <i class="fas fa-wifi text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Digital Friendly</h4>
                            <p class="text-sm text-gray-500">Akses internet tersedia di area publik utama.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>