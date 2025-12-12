<?php get_header(); ?>

<!-- HERO SECTION -->
<section class="relative h-[600px] flex items-center justify-center bg-gray-900">
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80" alt="Pemandangan Desa" class="w-full h-full object-cover opacity-50">
    </div>

    <div class="relative z-10 container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
            Jelajahi Keindahan <br> <span class="text-accent">Desa Nusantara</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-200 mb-8 max-w-2xl mx-auto">
            Temukan pengalaman wisata otentik dan dukung ekonomi lokal dengan membeli produk asli dari desa.
        </p>
        
        <div class="bg-white p-2 rounded-lg shadow-xl max-w-3xl mx-auto flex flex-col md:flex-row gap-2">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                <input type="text" placeholder="Cari desa, wisata, atau produk..." class="w-full pl-10 pr-4 py-3 rounded-md border-none focus:ring-0 text-gray-700 placeholder-gray-400 bg-transparent">
            </div>
            <div class="w-px bg-gray-200 hidden md:block"></div>
            <div class="md:w-1/3 relative">
                <i class="fas fa-map-marker-alt absolute left-4 top-3.5 text-gray-400"></i>
                <select class="w-full pl-10 pr-4 py-3 rounded-md border-none focus:ring-0 text-gray-700 bg-transparent cursor-pointer appearance-none">
                    <option value="">Semua Lokasi</option>
                    <option value="jateng">Jawa Tengah</option>
                    <option value="diy">Yogyakarta</option>
                    <option value="bali">Bali</option>
                </select>
            </div>
            <button class="bg-primary hover:bg-secondary text-white font-bold py-3 px-8 rounded-md transition shadow-md">
                Cari
            </button>
        </div>
    </div>
</section>

<!-- KATEGORI UNGGULAN -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Kategori Pilihan</h2>
            <p class="text-gray-500">Temukan apa yang Anda butuhkan untuk liburan Anda</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <a href="<?php echo home_url('/wisata'); ?>" class="group block text-center">
                <div class="w-24 h-24 mx-auto bg-green-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-primary transition duration-300">
                    <i class="fas fa-mountain text-3xl text-primary group-hover:text-white transition"></i>
                </div>
                <h3 class="font-semibold text-gray-800 group-hover:text-primary">Wisata Alam</h3>
            </a>
            <a href="<?php echo home_url('/produk?cat=kuliner'); ?>" class="group block text-center">
                <div class="w-24 h-24 mx-auto bg-orange-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-accent transition duration-300">
                    <i class="fas fa-utensils text-3xl text-accent group-hover:text-white transition"></i>
                </div>
                <h3 class="font-semibold text-gray-800 group-hover:text-accent">Kuliner Desa</h3>
            </a>
            <a href="<?php echo home_url('/produk?cat=kerajinan'); ?>" class="group block text-center">
                <div class="w-24 h-24 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-600 transition duration-300">
                    <i class="fas fa-shopping-bag text-3xl text-blue-600 group-hover:text-white transition"></i>
                </div>
                <h3 class="font-semibold text-gray-800 group-hover:text-blue-600">Oleh-Oleh Khas</h3>
            </a>
            <a href="<?php echo home_url('/homestay'); ?>" class="group block text-center">
                <div class="w-24 h-24 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-purple-600 transition duration-300">
                    <i class="fas fa-bed text-3xl text-purple-600 group-hover:text-white transition"></i>
                </div>
                <h3 class="font-semibold text-gray-800 group-hover:text-purple-600">Homestay</h3>
            </a>
        </div>
    </div>
</section>

<!-- DESTINASI POPULER -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Destinasi Populer</h2>
                <p class="text-gray-500">Jelajahi desa wisata yang sedang tren saat ini</p>
            </div>
            <a href="<?php echo home_url('/wisata'); ?>" class="text-primary font-semibold hover:underline">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            $args = array(
                'post_type'      => 'dw_wisata',
                'posts_per_page' => 3,
            );
            $query = new WP_Query( $args );

            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) : $query->the_post();
                    $thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'medium_large') : 'https://via.placeholder.com/400x300';
                    $lokasi = get_post_meta(get_the_ID(), 'lokasi_desa', true);
            ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition duration-300 group">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold text-primary">
                        <i class="fas fa-star text-accent mr-1"></i> 4.8
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <i class="fas fa-map-marker-alt text-primary mr-2"></i> <?php echo $lokasi ? esc_html($lokasi) : 'Desa Wisata'; ?>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-primary transition">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <p class="text-gray-600 text-sm line-clamp-2 mb-4">
                        <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                    </p>
                    <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                        <span class="text-xs text-gray-500">Mulai dari</span>
                        <span class="text-lg font-bold text-primary">Rp 15.000</span>
                    </div>
                </div>
            </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <div class="col-span-3 text-center py-10 text-gray-500">Belum ada data wisata.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- PRODUK UMKM TERBARU -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Produk UMKM Desa</h2>
                <p class="text-gray-500">Dukung ekonomi lokal dengan membeli produk asli</p>
            </div>
            <a href="<?php echo home_url('/produk'); ?>" class="text-primary font-semibold hover:underline">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            $args_produk = array(
                'post_type'      => 'dw_produk',
                'posts_per_page' => 4,
            );
            $query_produk = new WP_Query( $args_produk );

            if ( $query_produk->have_posts() ) :
                while ( $query_produk->have_posts() ) : $query_produk->the_post();
                    $thumb_prod = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'medium') : 'https://via.placeholder.com/300';
            ?>
            <div class="border border-gray-100 rounded-lg hover:shadow-lg transition bg-white group">
                <div class="relative h-48 overflow-hidden rounded-t-lg bg-gray-100">
                    <img src="<?php echo esc_url($thumb_prod); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover">
                    <div class="absolute bottom-0 left-0 right-0 bg-black/50 p-2 translate-y-full group-hover:translate-y-0 transition duration-300 flex justify-center">
                        <button class="bg-primary text-white text-xs px-4 py-2 rounded-md hover:bg-secondary w-full add-to-cart-btn" data-id="<?php the_ID(); ?>">
                            <i class="fas fa-cart-plus mr-1"></i> Tambah Keranjang
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <div class="text-xs text-gray-400 mb-1">Kategori</div>
                    <h3 class="font-semibold text-gray-800 mb-2 text-sm md:text-base leading-tight">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <div class="flex justify-between items-center mt-3">
                        <!-- UPDATE: Menggunakan fungsi baru tema_dw_... -->
                        <?php echo tema_dw_get_product_price_html(get_the_ID()); ?>
                        <div class="text-xs text-gray-500">Terjual 10+</div>
                    </div>
                </div>
            </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else:
            ?>
                <div class="col-span-4 text-center py-10 text-gray-500">Belum ada produk.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="py-20 bg-primary relative overflow-hidden">
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 rounded-full bg-white opacity-10 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-60 h-60 rounded-full bg-yellow-400 opacity-20 blur-3xl"></div>

    <div class="container mx-auto px-4 relative z-10 text-center text-white">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">Punya Produk Unggulan Desa?</h2>
        <p class="text-lg text-green-100 mb-8 max-w-2xl mx-auto">
            Bergabunglah dengan ribuan pedagang desa lainnya. Pasarkan produk dan wisata desa Anda ke jangkauan yang lebih luas melalui platform digital kami.
        </p>
        <div class="flex justify-center gap-4">
            <a href="<?php echo home_url('/register'); ?>" class="bg-white text-primary font-bold py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition transform hover:-translate-y-1">
                Daftar Sebagai Mitra
            </a>
            <a href="#" class="border-2 border-white text-white font-bold py-3 px-8 rounded-full hover:bg-white/10 transition">
                Pelajari Lebih Lanjut
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>