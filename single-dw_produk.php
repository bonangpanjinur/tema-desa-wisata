<?php get_header(); ?>

<?php while (have_posts()) : the_post(); 
    $product_id = get_the_ID();
    
    // Ambil Meta Data dari Plugin
    $harga = get_post_meta($product_id, '_dw_harga_dasar', true); // Sesuaikan meta key dengan plugin
    $stok = get_post_meta($product_id, '_dw_stok', true);
    $gallery = get_post_meta($product_id, '_dw_galeri_foto', true);
    
    // Data Penjual (Pedagang)
    $author_id = get_the_author_meta('ID');
    
    // Ambil data detail pedagang dari custom table plugin
    global $wpdb;
    $pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pedagang WHERE id_user = %d", $author_id));
    
    $nama_toko = $pedagang ? $pedagang->nama_toko : get_the_author_meta('display_name');
    $foto_profil_toko = $pedagang && $pedagang->foto_profil ? $pedagang->foto_profil : 'https://via.placeholder.com/100?text=Toko';
    $id_desa = $pedagang ? $pedagang->id_desa : 0;
    
    // Ambil Data Desa jika ada
    $nama_desa = '';
    if ($id_desa) {
        $desa = $wpdb->get_row($wpdb->prepare("SELECT nama_desa FROM {$wpdb->prefix}dw_desa WHERE id = %d", $id_desa));
        if ($desa) {
            $nama_desa = $desa->nama_desa;
        }
    }

    // Link Profile Toko (Mengarah ke Archive Produk difilter by Author)
    $link_toko = get_author_posts_url($author_id); 
    
    // Link Profile Desa (Mengarah ke halaman Wisata difilter by Desa ID - asumsi implementasi filter di page-wisata.php)
    // Atau bisa buat page khusus profile desa. Untuk sekarang kita arahkan ke pencarian wisata di desa tersebut.
    $link_desa = site_url('/wisata/?desa_id=' . $id_desa); 

    $main_image = get_the_post_thumbnail_url($product_id, 'full');
?>

<!-- Full Image with Back Button Overlay -->
<div class="relative w-full h-[320px] bg-gray-200">
    <?php if($main_image): ?>
        <img src="<?php echo esc_url($main_image); ?>" class="w-full h-full object-cover">
    <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100"><i class="ph-duotone ph-image text-4xl"></i></div>
    <?php endif; ?>
    
    <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-b from-black/60 to-transparent p-5 flex justify-between items-start">
        <a href="javascript:history.back()" class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/30 transition">
            <i class="ph-bold ph-arrow-left text-xl"></i>
        </a>
        <a href="<?php echo site_url('/keranjang'); ?>" class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/30 transition relative">
            <i class="ph-bold ph-shopping-cart text-xl"></i>
            <!-- Badge count logic here if needed -->
        </a>
    </div>
</div>

<!-- Content Container (Rounded Top) -->
<div class="relative -mt-8 bg-white rounded-t-[2rem] px-6 py-8 min-h-[50vh] pb-32 shadow-[0_-10px_40px_rgba(0,0,0,0.1)]">
    
    <!-- Title & Price -->
    <div class="flex justify-between items-start mb-2 gap-4">
        <h1 class="text-xl font-bold text-gray-900 leading-snug flex-1"><?php the_title(); ?></h1>
        <div class="text-right">
            <?php if(function_exists('dw_format_price')): ?>
                <span class="block text-xl font-bold text-primary"><?php echo dw_format_price($harga); ?></span>
            <?php else: ?>
                <span class="block text-xl font-bold text-primary">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Meta Info (Stok & Rating) -->
    <div class="flex items-center gap-4 text-xs text-gray-500 mb-6 border-b border-gray-50 pb-6">
        <div class="flex items-center gap-1.5 bg-gray-50 px-2.5 py-1 rounded-lg">
            <i class="ph-fill ph-package text-gray-400"></i> 
            <span>Stok: <strong><?php echo esc_html($stok); ?></strong></span>
        </div>
        <div class="flex items-center gap-1.5 bg-gray-50 px-2.5 py-1 rounded-lg">
            <i class="ph-fill ph-star text-yellow-400"></i> 
            <span class="font-bold text-gray-900">4.8</span> 
            <span class="text-gray-400">(24 ulasan)</span>
        </div>
        <?php if($nama_desa): ?>
        <a href="<?php echo esc_url($link_desa); ?>" class="flex items-center gap-1.5 bg-blue-50 text-blue-600 px-2.5 py-1 rounded-lg hover:bg-blue-100 transition">
            <i class="ph-fill ph-map-pin"></i>
            <span class="font-bold truncate max-w-[100px]"><?php echo esc_html($nama_desa); ?></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- Seller Card (Profile Pedagang) -->
    <div class="flex items-center gap-4 bg-white border border-gray-100 p-4 rounded-2xl shadow-sm mb-8 hover:border-primary/30 transition-colors group">
        <div class="w-12 h-12 rounded-full overflow-hidden border border-gray-200 flex-shrink-0">
            <img src="<?php echo esc_url($foto_profil_toko); ?>" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-bold text-gray-900 truncate"><?php echo esc_html($nama_toko); ?></h4>
            <div class="flex items-center gap-2 text-[10px] text-gray-500 mt-0.5">
                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Online</span>
                <span>•</span>
                <span><?php echo $nama_desa ? esc_html($nama_desa) : 'Lokasi Penjual'; ?></span>
            </div>
        </div>
        <a href="<?php echo esc_url($link_toko); ?>" class="text-xs font-bold text-primary bg-primary/5 border border-primary/20 px-4 py-2 rounded-xl hover:bg-primary hover:text-white transition-all group-hover:shadow-md">
            Kunjungi
        </a>
    </div>

    <!-- Deskripsi -->
    <div>
        <h3 class="font-bold text-gray-900 text-lg mb-3">Deskripsi Produk</h3>
        <div class="text-sm text-gray-600 leading-relaxed space-y-3 product-content">
            <?php the_content(); ?>
        </div>
    </div>

</div>

<!-- Sticky Bottom Action -->
<div class="fixed bottom-0 w-full max-w-[480px] bg-white border-t border-gray-100 p-4 pb-8 z-50 flex items-center gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
    <a href="https://wa.me/?text=Halo%20saya%20tertarik%20dengan%20produk%20<?php echo urlencode(get_the_title()); ?>" target="_blank" class="w-12 h-12 border border-gray-200 rounded-2xl flex items-center justify-center text-gray-500 hover:text-green-600 hover:border-green-600 hover:bg-green-50 transition-all">
        <i class="ph-bold ph-whatsapp-logo text-2xl"></i>
    </a>
    
    <!-- Tombol Add to Cart dengan ID dan Data Attributes yang benar untuk JS -->
    <button id="btn-add-to-cart" class="flex-1 bg-gray-900 text-white font-bold h-12 rounded-2xl flex items-center justify-center gap-2 active:scale-95 transition-transform hover:bg-gray-800 add-to-cart-btn" 
            data-id="<?php echo get_the_ID(); ?>"
            data-title="<?php echo esc_attr(get_the_title()); ?>"
            data-price="<?php echo esc_attr($harga); ?>"
            data-thumb="<?php echo esc_url($main_image); ?>">
        <i class="ph-bold ph-plus"></i> Masuk Keranjang
    </button>
    
    <!-- Tombol Beli Langsung (Bisa diarahkan ke Checkout langsung dengan parameter produk) -->
    <a href="<?php echo site_url('/checkout/?product_id=' . get_the_ID()); ?>" class="flex-1 bg-primary text-white font-bold h-12 rounded-2xl shadow-lg shadow-primary/30 active:scale-95 transition-transform flex items-center justify-center hover:bg-teal-700">
        Beli Langsung
    </a>
</div>

<style>
    /* Styling tambahan untuk konten deskripsi agar rapi */
    .product-content p { margin-bottom: 1em; }
    .product-content ul { list-style: disc; padding-left: 1.5em; margin-bottom: 1em; }
</style>

<?php endwhile; ?>
<?php get_footer(); ?>