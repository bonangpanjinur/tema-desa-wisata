<?php
/**
 * Template Name: Halaman Depan Sadesa
 */
get_header();

global $wpdb;

// === 1. GET BANNER FROM DB ===
$table_banner = $wpdb->prefix . 'dw_banner';
$banners = [];
// Cek tabel ada dulu agar tidak error fatal jika plugin mati
if($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
    $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'aktif' ORDER BY prioritas ASC LIMIT 5");
}

// Fallback Banner
if(empty($banners)) {
    $banners = [
        (object)['gambar' => 'https://via.placeholder.com/1200x500', 'judul' => 'Selamat Datang di Desa Wisata', 'link' => '#']
    ];
}
?>

<!-- HERO SLIDER / BANNER -->
<div class="container mx-auto px-4 mt-6 mb-10">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <!-- Main Banner (Ambil yg pertama) -->
        <div class="md:col-span-8 relative h-[300px] md:h-[400px] rounded-2xl overflow-hidden group">
            <?php $main_banner = $banners[0]; ?>
            <img src="<?php echo esc_url($main_banner->gambar); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
            <div class="absolute bottom-8 left-8 text-white max-w-lg">
                <span class="bg-yellow-500 text-black text-xs font-bold px-3 py-1 rounded-full mb-3 inline-block">Unggulan</span>
                <h2 class="text-3xl font-bold mb-2 leading-tight"><?php echo esc_html($main_banner->judul); ?></h2>
                <a href="<?php echo esc_url($main_banner->link); ?>" class="inline-block bg-primary hover:bg-green-700 text-white px-6 py-2 rounded-lg font-bold transition mt-2">Lihat Detail</a>
            </div>
        </div>

        <!-- Side Banners (Sisa) -->
        <div class="md:col-span-4 flex flex-col gap-6 h-[300px] md:h-[400px]">
            <?php 
            // Ambil banner ke-2 dan ke-3
            $side_banners = array_slice($banners, 1, 2); 
            foreach($side_banners as $b): 
            ?>
            <div class="relative flex-1 rounded-2xl overflow-hidden group">
                <img src="<?php echo esc_url($b->gambar); ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="font-bold text-lg"><?php echo esc_html($b->judul); ?></h3>
                </div>
                <a href="<?php echo esc_url($b->link); ?>" class="absolute inset-0 z-10"></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- CATEGORY MENU -->
<div class="container mx-auto px-4 mb-12">
    <div class="grid grid-cols-4 gap-4 md:flex md:justify-center md:gap-10">
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center text-2xl shadow-sm group-hover:bg-primary group-hover:text-white transition">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <span class="font-bold text-gray-600 text-sm">Wisata</span>
        </a>
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="w-16 h-16 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center text-2xl shadow-sm group-hover:bg-orange-500 group-hover:text-white transition">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <span class="font-bold text-gray-600 text-sm">Produk</span>
        </a>
        <a href="<?php echo home_url('/desa'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="w-16 h-16 bg-blue-100 text-blue-500 rounded-2xl flex items-center justify-center text-2xl shadow-sm group-hover:bg-blue-500 group-hover:text-white transition">
                <i class="fas fa-home"></i>
            </div>
            <span class="font-bold text-gray-600 text-sm">Jelajah Desa</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="w-16 h-16 bg-purple-100 text-purple-500 rounded-2xl flex items-center justify-center text-2xl shadow-sm group-hover:bg-purple-500 group-hover:text-white transition">
                <i class="fas fa-utensils"></i>
            </div>
            <span class="font-bold text-gray-600 text-sm">Kuliner</span>
        </a>
    </div>
</div>

<!-- PRODUK TERBARU (CPT: dw_produk) -->
<div class="container mx-auto px-4 mb-16">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Produk UMKM Terbaru</h2>
            <p class="text-gray-500 text-sm">Oleh-oleh asli dari desa wisata</p>
        </div>
        <a href="<?php echo home_url('/produk'); ?>" class="text-primary font-bold text-sm hover:underline">Lihat Semua</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
        <?php
        $args = array(
            'post_type' => 'dw_produk',
            'posts_per_page' => 5,
            'post_status' => 'publish'
        );
        $produk = new WP_Query($args);
        
        if($produk->have_posts()): while($produk->have_posts()): $produk->the_post();
            // Ambil harga dari meta plugin
            $harga = get_post_meta(get_the_ID(), 'dw_harga_produk', true);
            $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi_produk', true);
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition group">
            <div class="relative aspect-square bg-gray-100">
                <?php if(has_post_thumbnail()): ?>
                    <img src="<?php the_post_thumbnail_url('medium'); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <img src="https://via.placeholder.com/300" class="w-full h-full object-cover">
                <?php endif; ?>
                
                <!-- Add to Cart Button -->
                <button class="absolute bottom-3 right-3 bg-white text-primary w-10 h-10 rounded-full flex items-center justify-center shadow-md hover:bg-primary hover:text-white transition btn-add-cart" 
                        data-product-id="<?php the_ID(); ?>" 
                        data-quantity="1">
                    <i class="fas fa-cart-plus"></i>
                </button>
            </div>
            <div class="p-4">
                <div class="text-xs text-gray-400 mb-1 flex items-center gap-1">
                    <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($lokasi ?: 'Desa Wisata'); ?>
                </div>
                <h3 class="font-bold text-gray-800 line-clamp-2 mb-2 min-h-[3rem]">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                <div class="font-bold text-primary text-lg"><?php echo dw_format_rupiah($harga); ?></div>
            </div>
        </div>
        <?php endwhile; wp_reset_postdata(); else: ?>
            <div class="col-span-full text-center py-10 text-gray-500">Belum ada produk.</div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>