<?php
/**
 * Template Name: Single Produk (Tailwind App Style)
 */
get_header();

global $wpdb;

while ( have_posts() ) :
    the_post();
    $post_id = get_the_ID();

    // Data Fetching (Hybrid: Custom Table + Meta Fallback)
    $harga = 0; $stok = 0; $terjual = 0; $rating = 0; $lokasi = ''; $wa_penjual = ''; $kategori = '';
    $img_url = 'https://via.placeholder.com/600?text=No+Image';

    // Coba ambil dari tabel custom
    $table_produk = $wpdb->prefix . 'dw_produk';
    $table_pedagang = $wpdb->prefix . 'dw_pedagang';
    
    // Gunakan prepare dengan benar
    $custom_data = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, pd.nama_toko, pd.kabupaten_nama, pd.no_hp as wa_pedagang 
        FROM $table_produk p 
        LEFT JOIN $table_pedagang pd ON p.id_pedagang = pd.id 
        WHERE p.post_id = %d OR p.id = %d LIMIT 1
    ", $post_id, $post_id));

    if ($custom_data) {
        $harga      = floatval($custom_data->harga);
        $stok       = intval($custom_data->stok);
        $terjual    = intval($custom_data->terjual);
        $rating     = floatval($custom_data->rating_avg);
        $lokasi     = !empty($custom_data->kabupaten_nama) ? $custom_data->kabupaten_nama : $custom_data->nama_toko;
        $wa_penjual = $custom_data->wa_pedagang;
        $kategori   = $custom_data->kategori;

        if (!empty($custom_data->foto_utama)) {
            $img_url = (strpos($custom_data->foto_utama, 'http') === 0) ? $custom_data->foto_utama : wp_get_upload_dir()['baseurl'] . '/' . $custom_data->foto_utama;
        }
    } else {
        // Fallback Post Meta
        $harga      = (float) get_post_meta($post_id, 'harga', true);
        $stok       = (int) get_post_meta($post_id, 'stok', true);
        $terjual    = (int) get_post_meta($post_id, 'terjual', true);
        $rating     = (float) get_post_meta($post_id, 'rating_avg', true);
        $lokasi     = get_post_meta($post_id, 'lokasi_toko', true);
        $wa_penjual = get_post_meta($post_id, 'wa_penjual', true);
        $thumb_url  = get_the_post_thumbnail_url($post_id, 'full');
        if ($thumb_url) $img_url = $thumb_url;
    }
?>

<div class="bg-gray-100 min-h-screen pb-24 md:pb-0 font-sans">
    
    <div class="max-w-6xl mx-auto md:grid md:grid-cols-[400px_1fr] md:gap-8 md:py-8 md:px-4">
        
        <!-- Kolom Kiri: Gambar -->
        <div class="bg-white md:rounded-2xl overflow-hidden shadow-sm relative">
            <div class="relative pt-[100%]">
                <img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title(); ?>" class="absolute inset-0 w-full h-full object-cover">
            </div>
        </div>

        <!-- Kolom Kanan: Detail -->
        <div class="flex flex-col gap-3">
            
            <!-- Info Utama -->
            <div class="bg-white p-4 md:rounded-2xl shadow-sm">
                <div class="text-2xl font-bold text-red-600 mb-2">Rp <?php echo number_format($harga, 0, ',', '.'); ?></div>
                <h1 class="text-lg font-medium text-gray-900 mb-3 leading-snug"><?php the_title(); ?></h1>
                
                <div class="flex items-center text-sm text-gray-500 space-x-4">
                    <div class="flex items-center">
                        <i class="fas fa-star text-yellow-400 mr-1"></i> <?php echo number_format($rating, 1); ?>
                    </div>
                    <div class="w-px h-3 bg-gray-300"></div>
                    <div>Terjual <?php echo $terjual; ?></div>
                    <div class="w-px h-3 bg-gray-300"></div>
                    <div>Stok <?php echo $stok; ?></div>
                </div>

                <?php if(!empty($kategori)): ?>
                <div class="mt-3">
                    <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded border border-gray-200"><?php echo esc_html($kategori); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Penjual -->
            <div class="bg-white p-4 md:rounded-2xl shadow-sm flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                    <i class="fas fa-store text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 text-sm"><?php echo !empty($lokasi) ? 'Toko ' . esc_html($lokasi) : 'Pedagang Desa'; ?></h4>
                    <span class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt"></i> <?php echo !empty($lokasi) ? esc_html($lokasi) : 'Lokasi Lokal'; ?>
                    </span>
                </div>
            </div>

            <!-- Deskripsi -->
            <div class="bg-white p-4 md:rounded-2xl shadow-sm min-h-[200px]">
                <h3 class="font-bold text-gray-900 mb-3">Deskripsi Produk</h3>
                <div class="prose prose-sm text-gray-600 max-w-none">
                    <?php the_content(); ?>
                </div>
            </div>

            <!-- Desktop Action Card -->
            <div class="hidden md:block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mt-4">
                <div class="font-bold text-lg mb-4">Atur Pembelian</div>
                <div class="flex gap-3">
                    <button class="flex-1 border border-primary text-primary font-bold py-3 rounded-xl hover:bg-green-50 transition-colors" onclick="dwAddCart(<?php echo $post_id; ?>)">
                        <i class="fas fa-cart-plus mr-2"></i> Keranjang
                    </button>
                    <a href="https://wa.me/<?php echo esc_attr($wa_penjual); ?>?text=Halo, saya tertarik dengan produk <?php the_title(); ?>" class="flex-1 bg-primary text-white font-bold py-3 rounded-xl flex items-center justify-center hover:bg-green-700 transition-colors">
                        Beli Sekarang
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- STICKY BOTTOM BAR (Mobile) -->
    <div class="fixed bottom-0 left-0 right-0 bg-white h-[70px] flex items-center justify-between px-4 border-t border-gray-100 z-50 md:hidden pb-safe">
        <div class="flex gap-4 mr-4">
            <a href="#" class="flex flex-col items-center justify-center text-gray-500 text-[10px]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 mb-0.5">
                    <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0112 2.25c2.43 0 4.817.178 7.152.52 1.978.292 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.678-3.348 3.97a48.901 48.901 0 01-3.476.383.39.39 0 00-.297.17l-2.755 4.133a.75.75 0 01-1.248 0l-2.755-4.133a.39.39 0 00-.297-.17 48.9 48.9 0 01-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.68 3.348-3.97zM6.75 8.25a.75.75 0 01.75-.75h9a.75.75 0 010 1.5h-9a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H7.5z" clip-rule="evenodd" />
                </svg>
                <span>Chat</span>
            </a>
        </div>
        
        <div class="flex flex-1 gap-2">
            <button class="flex-1 bg-green-50 border border-primary text-primary font-bold rounded-lg text-sm flex items-center justify-center h-10" onclick="dwAddCart(<?php echo $post_id; ?>)">
                <i class="fas fa-plus mr-1"></i> Keranjang
            </button>
            <a href="https://wa.me/<?php echo esc_attr($wa_penjual); ?>?text=Halo, saya tertarik dengan produk <?php the_title(); ?>" class="flex-1 bg-primary text-white font-bold rounded-lg text-sm flex items-center justify-center h-10">
                Beli Sekarang
            </a>
        </div>
    </div>

</div>

<script>
function dwAddCart(id) {
    if(typeof jQuery !== 'undefined') {
        jQuery(document.body).trigger('dw_add_to_cart', [id, 1]);
        // alert('Produk ditambahkan ke keranjang'); 
    } else {
        alert('jQuery belum dimuat');
    }
}
</script>

<?php 
endwhile;
get_footer(); 
?>