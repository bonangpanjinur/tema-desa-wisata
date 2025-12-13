<?php get_header(); ?>

<!-- Banner Slider (Horizontal Scroll) -->
<!-- Sementara statis, idealnya query dari post type dw_banner -->
<div class="mt-4 px-4">
    <div class="flex overflow-x-auto gap-4 no-scrollbar snap-x">
        <!-- Banner 1 -->
        <div class="min-w-[85%] h-40 bg-gradient-to-r from-blue-500 to-cyan-400 rounded-2xl relative snap-center shadow-lg overflow-hidden">
            <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-60">
            <div class="absolute bottom-4 left-4 text-white">
                <p class="text-xs font-semibold bg-orange-500 inline-block px-2 py-0.5 rounded-md mb-1">Promo</p>
                <h2 class="font-bold text-xl">Jelajah Alam</h2>
                <p class="text-sm">Diskon 20% Paket Camping</p>
            </div>
        </div>
        <!-- Banner 2 -->
        <div class="min-w-[85%] h-40 bg-gradient-to-r from-emerald-500 to-green-400 rounded-2xl relative snap-center shadow-lg overflow-hidden">
                <img src="https://images.unsplash.com/photo-1596423736798-75b43694f540?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-60">
            <div class="absolute bottom-4 left-4 text-white">
                <p class="text-xs font-semibold bg-blue-500 inline-block px-2 py-0.5 rounded-md mb-1">Produk</p>
                <h2 class="font-bold text-xl">Kerajinan Bambu</h2>
                <p class="text-sm">Karya Asli Warga Desa</p>
            </div>
        </div>
    </div>
</div>

<!-- Categories Menu -->
<div class="px-4 mt-6">
    <div class="flex justify-between items-start text-center">
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-1 group">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center text-emerald-600 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition">
                <i class="fas fa-map-marked-alt text-2xl"></i>
            </div>
            <span class="text-xs font-medium text-gray-600">Wisata</span>
        </a>
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-1 group">
            <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 shadow-sm group-hover:bg-orange-600 group-hover:text-white transition">
                <i class="fas fa-box-open text-2xl"></i>
            </div>
            <span class="text-xs font-medium text-gray-600">Produk</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-1 group">
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition">
                <i class="fas fa-bed text-2xl"></i>
            </div>
            <span class="text-xs font-medium text-gray-600">Homestay</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-1 group">
            <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition">
                <i class="fas fa-utensils text-2xl"></i>
            </div>
            <span class="text-xs font-medium text-gray-600">Kuliner</span>
        </a>
    </div>
</div>

<!-- Section: Wisata Populer (Dynamic Loop) -->
<div class="px-4 mt-8">
    <div class="flex justify-between items-end mb-3">
        <h3 class="font-bold text-lg text-gray-800">Wisata Populer</h3>
        <a href="<?php echo home_url('/wisata'); ?>" class="text-emerald-600 text-xs font-semibold">Lihat Semua</a>
    </div>
    
    <!-- Horizontal Scroll Cards -->
    <div class="flex overflow-x-auto gap-4 no-scrollbar pb-2">
        <?php
        $args_wisata = [
            'post_type' => 'dw_wisata',
            'posts_per_page' => 5,
        ];
        $query_wisata = new WP_Query($args_wisata);
        
        if ($query_wisata->have_posts()) :
            while ($query_wisata->have_posts()) : $query_wisata->the_post();
                $harga = get_post_meta(get_the_ID(), 'harga_tiket', true) ?: 0;
                $lokasi = get_post_meta(get_the_ID(), 'lokasi', true) ?: 'Desa Wisata';
                $rating = 4.8; // Placeholder, nanti ambil dari meta reviews
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : 'https://via.placeholder.com/500x300?text=No+Image';
        ?>
            <!-- Card Item -->
            <div class="min-w-[220px] w-[220px] bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden shrink-0">
                <div class="h-32 bg-gray-200 relative">
                    <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover">
                    <span class="absolute top-2 right-2 bg-white/90 px-1.5 py-0.5 rounded text-[10px] font-bold flex items-center gap-1">
                        <i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?>
                    </span>
                </div>
                <div class="p-3">
                    <h4 class="font-bold text-gray-800 truncate"><?php the_title(); ?></h4>
                    <p class="text-xs text-gray-500 mb-2 truncate"><i class="fas fa-map-marker-alt text-red-400 mr-1"></i> <?php echo esc_html($lokasi); ?></p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-emerald-600 font-bold text-sm">Rp <?php echo number_format($harga, 0, ',', '.'); ?></span>
                        <a href="<?php the_permalink(); ?>" class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-[10px] font-medium hover:bg-emerald-700 transition">Detail</a>
                    </div>
                </div>
            </div>
        <?php 
            endwhile;
            wp_reset_postdata();
        else:
        ?>
            <p class="text-xs text-gray-500 ml-1">Belum ada data wisata.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Section: Produk Desa (Dynamic Grid Loop) -->
<div class="px-4 mt-6">
    <div class="flex justify-between items-end mb-3">
        <h3 class="font-bold text-lg text-gray-800">Produk UMKM</h3>
        <a href="<?php echo home_url('/produk'); ?>" class="text-emerald-600 text-xs font-semibold">Lihat Semua</a>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <?php
        $args_produk = [
            'post_type' => 'dw_produk',
            'posts_per_page' => 6,
        ];
        $query_produk = new WP_Query($args_produk);
        
        if ($query_produk->have_posts()) :
            while ($query_produk->have_posts()) : $query_produk->the_post();
                $harga = get_post_meta(get_the_ID(), 'harga', true) ?: 0;
                $penjual = get_post_meta(get_the_ID(), 'nama_toko', true) ?: 'UMKM Desa';
                $terjual = get_post_meta(get_the_ID(), 'terjual', true) ?: 0;
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : 'https://via.placeholder.com/500x300?text=No+Image';
        ?>
            <!-- Product Item -->
            <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-full">
                <a href="<?php the_permalink(); ?>">
                    <div class="h-32 rounded-lg bg-gray-200 overflow-hidden mb-2 relative">
                        <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover transform hover:scale-110 transition duration-300">
                    </div>
                    <h4 class="font-medium text-gray-800 text-sm line-clamp-2 leading-snug"><?php the_title(); ?></h4>
                    <p class="text-[10px] text-gray-500 mt-1"><?php echo esc_html($penjual); ?></p>
                </a>
                <div class="mt-2 flex justify-between items-end">
                    <div class="flex flex-col">
                        <span class="text-emerald-700 font-bold text-sm">Rp <?php echo number_format($harga, 0, ',', '.'); ?></span>
                        <span class="text-[10px] text-gray-400">Terjual <?php echo $terjual; ?>+</span>
                    </div>
                    <!-- Tombol Tambah Keranjang (Perlu AJAX Handler nanti) -->
                    <button class="add-to-cart-btn bg-emerald-50 text-emerald-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-emerald-600 hover:text-white transition shadow-sm border border-emerald-100" data-product-id="<?php echo get_the_ID(); ?>">
                        <i class="fas fa-cart-plus text-xs"></i>
                    </button>
                </div>
            </div>
        <?php 
            endwhile;
            wp_reset_postdata();
        else:
        ?>
             <p class="text-xs text-gray-500 col-span-2 text-center py-4">Belum ada produk tersedia.</p>
        <?php endif; ?>
    </div>
</div>

<div class="h-6"></div> <!-- Spacer before footer -->

<?php get_footer(); ?>