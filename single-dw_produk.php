<?php
/**
 * Template Name: Single Produk Desa Wisata
 * Description: Menampilkan detail lengkap produk, galeri, dan info penjual.
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    // Mengambil Meta Data dari Plugin
    $post_id = get_the_ID();
    $harga = get_post_meta($post_id, '_dw_harga_dasar', true);
    $stok = get_post_meta($post_id, '_dw_stok', true); // Bisa angka atau string kosong (unlimited)
    $gallery_ids = get_post_meta($post_id, '_dw_galeri_foto', true); // String ID dipisah koma
    $author_id = get_the_author_meta('ID');
    
    // Data Toko / Penjual (Integrasi User Meta / Custom Table Plugin)
    // Asumsi: Nama toko disimpan di user meta atau tabel pedagang. Kita pakai Display Name dulu untuk aman.
    $store_name = get_the_author(); 
    $store_avatar = get_avatar_url($author_id);
    
    // Thumbnail Utama
    $main_thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'large') : 'https://via.placeholder.com/800x600?text=No+Image';
?>

<div class="bg-white py-8 min-h-screen">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb -->
        <nav class="flex text-sm text-gray-500 mb-8 overflow-x-auto whitespace-nowrap pb-2">
            <a href="<?php echo home_url(); ?>" class="hover:text-primary transition">Beranda</a>
            <span class="mx-2 text-gray-300">/</span>
            <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="hover:text-primary transition">Produk</a>
            <span class="mx-2 text-gray-300">/</span>
            <span class="text-gray-800 font-medium truncate"><?php the_title(); ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- KOLOM KIRI: Galeri Foto (7 kolom) -->
            <div class="lg:col-span-7">
                <!-- Main Image -->
                <div class="bg-gray-100 rounded-2xl overflow-hidden mb-4 border border-gray-200 relative group cursor-zoom-in">
                    <img src="<?php echo esc_url($main_thumb_url); ?>" id="main-product-image" alt="<?php the_title(); ?>" class="w-full h-[400px] md:h-[500px] object-cover transition duration-300 group-hover:scale-105">
                    <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition"></div>
                </div>

                <!-- Thumbnail Gallery -->
                <?php if ( ! empty( $gallery_ids ) ) : 
                    $ids = explode(',', $gallery_ids);
                    if(count($ids) > 0):
                ?>
                <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
                    <!-- Thumb 1 (Featured) -->
                    <button onclick="changeImage('<?php echo esc_url($main_thumb_url); ?>')" class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 border-primary cursor-pointer hover:opacity-80 transition">
                        <img src="<?php echo esc_url($main_thumb_url); ?>" class="w-full h-full object-cover">
                    </button>
                    <!-- Gallery Loop -->
                    <?php foreach($ids as $img_id): 
                        $img_url = wp_get_attachment_image_url($img_id, 'large');
                        $thumb_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                        if($img_url):
                    ?>
                    <button onclick="changeImage('<?php echo esc_url($img_url); ?>')" class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 border-transparent hover:border-primary cursor-pointer transition">
                        <img src="<?php echo esc_url($thumb_url); ?>" class="w-full h-full object-cover">
                    </button>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; endif; ?>
            </div>

            <!-- KOLOM KANAN: Info & Aksi (5 kolom) -->
            <div class="lg:col-span-5">
                <div class="sticky top-24">
                    
                    <!-- Judul & Rating -->
                    <h1 class="text-3xl font-bold text-gray-900 mb-2 leading-tight"><?php the_title(); ?></h1>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex text-yellow-400 text-sm">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="text-sm text-gray-500 border-l border-gray-300 pl-4">4.8 (24 Ulasan)</span>
                        <span class="text-sm text-gray-500 border-l border-gray-300 pl-4">Terjual 100+</span>
                    </div>

                    <!-- Harga -->
                    <div class="mb-8">
                        <span class="text-4xl font-bold text-primary">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                    </div>

                    <!-- Deskripsi Singkat -->
                    <div class="prose prose-sm text-gray-600 mb-8 line-clamp-4">
                        <?php the_excerpt(); ?>
                    </div>

                    <!-- Pilihan Stok / Variasi (Placeholder UI) -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <label class="font-semibold text-gray-700">Kuantitas</label>
                            <span class="text-sm text-gray-500">Stok: <span class="font-medium text-gray-800"><?php echo !empty($stok) ? $stok : 'Tersedia'; ?></span></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border border-gray-300 rounded-lg w-max">
                                <button onclick="updateQty(-1)" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-l-lg transition">-</button>
                                <input type="number" id="qty-input" value="1" min="1" max="<?php echo is_numeric($stok) ? $stok : 999; ?>" class="w-14 text-center border-none focus:ring-0 p-0 text-gray-800 font-bold bg-transparent">
                                <button onclick="updateQty(1)" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-r-lg transition">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <button id="single-add-cart" 
                                class="flex-1 bg-white border-2 border-primary text-primary font-bold py-3.5 px-6 rounded-xl hover:bg-green-50 transition flex justify-center items-center gap-2"
                                data-id="<?php echo $post_id; ?>" 
                                data-title="<?php the_title(); ?>"
                                data-price="<?php echo esc_attr($harga); ?>"
                                data-thumb="<?php echo esc_url($main_thumb_url); ?>">
                            <i class="fas fa-cart-plus"></i> Tambah Keranjang
                        </button>
                        <button class="flex-1 bg-primary text-white font-bold py-3.5 px-6 rounded-xl hover:bg-secondary transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 duration-200">
                            Beli Sekarang
                        </button>
                    </div>

                    <!-- Info Penjual -->
                    <div class="border-t border-gray-100 pt-6">
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <img src="<?php echo esc_url($store_avatar); ?>" class="w-12 h-12 rounded-full border border-gray-200">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800 text-sm"><?php echo esc_html($store_name); ?></h4>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                    <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-400"></i> Jawa Tengah</span>
                                    <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                    <span class="text-green-600 font-medium">Online</span>
                                </div>
                            </div>
                            <a href="<?php echo get_author_posts_url($author_id); ?>" class="text-xs font-bold text-primary border border-primary px-3 py-1.5 rounded-lg hover:bg-primary hover:text-white transition">
                                Kunjungi Toko
                            </a>
                        </div>
                    </div>

                    <!-- Jaminan -->
                    <div class="grid grid-cols-3 gap-4 mt-6 text-center">
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-shield-alt text-2xl text-gray-300 mb-2 block"></i>
                            Jaminan Aman
                        </div>
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-check-circle text-2xl text-gray-300 mb-2 block"></i>
                            Produk Asli
                        </div>
                        <div class="text-xs text-gray-500">
                            <i class="fas fa-shipping-fast text-2xl text-gray-300 mb-2 block"></i>
                            Pengiriman Cepat
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Deskripsi Lengkap & Ulasan (Tabs) -->
        <div class="mt-16 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex border-b border-gray-100">
                <button class="px-8 py-4 text-sm font-bold text-primary border-b-2 border-primary bg-green-50/50">Deskripsi Lengkap</button>
                <button class="px-8 py-4 text-sm font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 transition">Ulasan Pembeli</button>
                <button class="px-8 py-4 text-sm font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 transition">Info Pengiriman</button>
            </div>
            <div class="p-8">
                <div class="prose max-w-none text-gray-600">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>

        <!-- Produk Terkait -->
        <div class="mt-16">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">Produk Lain dari Toko Ini</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php
                // Query Produk Terkait (dari Author yang sama)
                $related_args = array(
                    'post_type' => 'dw_produk',
                    'posts_per_page' => 4,
                    'post__not_in' => array($post_id),
                    'author' => $author_id,
                );
                $related_query = new WP_Query($related_args);
                
                if($related_query->have_posts()):
                    while($related_query->have_posts()): $related_query->the_post();
                    $rel_thumb = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'medium') : 'https://via.placeholder.com/300';
                    $rel_price = get_post_meta(get_the_ID(), '_dw_harga_dasar', true);
                ?>
                <div class="bg-white border border-gray-100 rounded-lg hover:shadow-lg transition group">
                    <div class="relative h-40 overflow-hidden rounded-t-lg bg-gray-100">
                        <a href="<?php the_permalink(); ?>">
                            <img src="<?php echo esc_url($rel_thumb); ?>" class="w-full h-full object-cover">
                        </a>
                    </div>
                    <div class="p-3">
                        <h4 class="font-semibold text-gray-800 mb-1 text-sm line-clamp-2">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h4>
                        <div class="text-primary font-bold text-sm">Rp <?php echo number_format((float)$rel_price, 0, ',', '.'); ?></div>
                    </div>
                </div>
                <?php endwhile; wp_reset_postdata(); endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
// Fungsi Ganti Gambar Utama
function changeImage(src) {
    document.getElementById('main-product-image').src = src;
}

// Fungsi Update Qty
function updateQty(delta) {
    var input = document.getElementById('qty-input');
    var currentVal = parseInt(input.value) || 1;
    var maxVal = parseInt(input.getAttribute('max')) || 999;
    var newVal = currentVal + delta;
    
    if (newVal >= 1 && newVal <= maxVal) {
        input.value = newVal;
    }
}
</script>

<?php endwhile; ?>

<?php get_footer(); ?>