<?php
/**
 * Template Name: Halaman Depan Sadesa
 */
get_header();

// =================================================================================
// 1. DATA FETCHING (DATABASE & WP_QUERY)
// =================================================================================

$debug_messages = [];

// --- A. Banner Data (Dari Tabel Database Plugin) ---
global $wpdb;
$table_banner = $wpdb->prefix . 'dw_banners';
$banners = [];

// Cek apakah tabel ada di database
if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_banner)) == $table_banner) {
    // Ambil banner yang statusnya active
    $db_banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'active' ORDER BY id DESC LIMIT 5");
    
    if (!empty($db_banners)) {
        foreach ($db_banners as $b) {
            $banners[] = [
                'gambar'      => !empty($b->image_url) ? $b->image_url : get_template_directory_uri() . '/assets/img/banner-placeholder.jpg',
                'judul'       => $b->title,
                'label'       => 'Info Desa', 
                'label_color' => 'bg-blue-600',
                'link'        => !empty($b->link) ? $b->link : '#'
            ];
        }
        // Set banner pertama jadi 'Terbaru' (Styling)
        if (isset($banners[0])) {
            $banners[0]['label'] = 'Terbaru';
            $banners[0]['label_color'] = 'bg-orange-500';
        }
    }
}

// Fallback Banner (Jika Database Kosong / Tabel Tidak Ada)
if (empty($banners)) {
    $banners = [
        [
            'gambar' => 'https://images.unsplash.com/photo-1596423736798-75b43694f540?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul' => 'Pesona Alam Desa', 'label' => 'Terbaru', 'label_color' => 'bg-orange-500', 'link' => '#'
        ],
        [
            'gambar' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul' => 'Kuliner Tradisional', 'label' => 'Info Desa', 'label_color' => 'bg-blue-500', 'link' => '#'
        ]
    ];
}

// --- FUNGSI HELPER KATEGORI ---
function get_first_category_name($post_id, $post_type) {
    // Tentukan nama taxonomy berdasarkan post type
    $taxonomy = 'category'; // Default WP
    if ($post_type === 'dw_wisata') {
        $taxonomy = 'dw_kategori_wisata'; // Sesuaikan dengan register_taxonomy di plugin
    } elseif ($post_type === 'dw_produk') {
        $taxonomy = 'dw_kategori_produk'; // Sesuaikan dengan register_taxonomy di plugin
    }

    // Cek apakah taxonomy tersebut ada, jika tidak, cari taxonomy pertama yang tersedia untuk post type ini
    if (!taxonomy_exists($taxonomy)) {
        $taxonomies = get_object_taxonomies($post_type);
        if (!empty($taxonomies)) {
            $taxonomy = $taxonomies[0];
        }
    }

    $terms = get_the_terms($post_id, $taxonomy);
    if (!empty($terms) && !is_wp_error($terms)) {
        return $terms[0]->name;
    }
    
    // Fallback jika tidak ada kategori
    return ($post_type === 'dw_wisata') ? 'Wisata' : 'Produk';
}

// --- B. Wisata Data ---
$list_wisata = [];
$args_wisata = array(
    'post_type'      => 'dw_wisata',
    'posts_per_page' => 4,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish'
);
$query_wisata = new WP_Query($args_wisata);

if ($query_wisata->have_posts()) {
    while ($query_wisata->have_posts()) {
        $query_wisata->the_post();
        
        // Ambil Gambar dengan Fallback
        $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
        if (empty($thumb_url)) {
            $thumb_url = 'https://via.placeholder.com/400x300?text=No+Image'; // Gambar placeholder default
        }

        $list_wisata[] = [
            'id'          => get_the_ID(),
            'nama_wisata' => get_the_title(),
            'lokasi'      => get_post_meta(get_the_ID(), 'dw_lokasi', true) ?: 'Desa Wisata',
            'harga_tiket' => get_post_meta(get_the_ID(), 'dw_harga_tiket', true) ?: 0,
            'rating'      => 4.8, 
            'thumbnail'   => $thumb_url,
            'slug'        => get_post_field('post_name'),
            'kategori'    => get_first_category_name(get_the_ID(), 'dw_wisata') // Dinamis
        ];
    }
    wp_reset_postdata();
}

// --- C. Produk Data ---
$list_produk = [];
$args_produk = array(
    'post_type'      => 'dw_produk',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish'
);
$query_produk = new WP_Query($args_produk);

if ($query_produk->have_posts()) {
    while ($query_produk->have_posts()) {
        $query_produk->the_post();
        
        $seller_id = get_post_field('post_author');
        $nama_toko = get_the_author_meta('display_name', $seller_id);
        $harga = function_exists('dw_get_product_price') ? dw_get_product_price(get_the_ID()) : get_post_meta(get_the_ID(), 'dw_harga', true);

        // Ambil Gambar dengan Fallback
        $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
        if (empty($thumb_url)) {
            $thumb_url = 'https://via.placeholder.com/300x300?text=Produk'; 
        }

        $list_produk[] = [
            'id'          => get_the_ID(),
            'nama_produk' => get_the_title(),
            'nama_toko'   => $nama_toko ?: 'UMKM Desa',
            'harga_dasar' => $harga ?: 0,
            'thumbnail'   => $thumb_url,
            'kategori'    => get_first_category_name(get_the_ID(), 'dw_produk') // Dinamis
        ];
    }
    wp_reset_postdata();
}
?>

<!-- =================================================================================
     2. VIEW SECTION (SADESA STYLE) - 100% ORIGINAL DESIGN
     ================================================================================= -->

<!-- SECTION 1: HERO BANNERS -->
<div class="mb-10 mt-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
        <?php foreach ($banners as $index => $banner) : 
            $img = $banner['gambar'];
            $title = $banner['judul'];
            $label = $banner['label'];
            $label_bg = $banner['label_color'];
            $link_url = $banner['link'];
        ?>
        <div class="relative h-48 md:h-64 rounded-2xl overflow-hidden group shadow-md cursor-pointer">
            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110" onerror="this.src='https://via.placeholder.com/800x400?text=Banner+Image'">
            <div class="banner-overlay absolute inset-0"></div> <!-- CSS di main.css -->
            
            <div class="absolute bottom-6 left-6 max-w-xs z-10 text-white">
                <span class="<?php echo $label_bg; ?> text-[10px] md:text-xs font-bold px-3 py-1 rounded-full mb-3 inline-block shadow-sm uppercase tracking-wide">
                    <?php echo esc_html($label); ?>
                </span>
                <h2 class="font-bold text-2xl md:text-3xl leading-tight mb-4 drop-shadow-md">
                    <?php echo esc_html($title); ?>
                </h2>
                <a href="<?php echo esc_url($link_url); ?>" class="inline-block bg-white text-gray-800 text-xs md:text-sm font-bold px-5 py-2.5 rounded-lg hover:bg-gray-100 transition shadow-lg transform hover:-translate-y-0.5">
                    Lihat Detail
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SECTION 2: MENU KATEGORI -->
<div class="mb-12">
    <div class="grid grid-cols-4 md:flex md:justify-center md:gap-16 gap-4">
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-green-600 group-hover:text-white">
                <i class="fas fa-map-marked-alt text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-green-600 transition-colors">Wisata</span>
        </a>
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-orange-500 group-hover:text-white">
                <i class="fas fa-box-open text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-orange-500 transition-colors">Produk</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-blue-100 text-blue-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-blue-500 group-hover:text-white">
                <i class="fas fa-bed text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-blue-500 transition-colors">Homestay</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-purple-100 text-purple-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-purple-500 group-hover:text-white">
                <i class="fas fa-utensils text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-purple-500 transition-colors">Kuliner</span>
        </a>
    </div>
</div>

<!-- SECTION 3: WISATA POPULER -->
<div class="mb-10">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h3 class="font-bold text-xl md:text-2xl text-gray-800">Wisata Populer</h3>
            <p class="text-sm text-gray-500 mt-1">Destinasi favorit wisatawan minggu ini</p>
        </div>
        <a href="<?php echo home_url('/wisata'); ?>" class="text-primary text-sm font-bold hover:underline flex items-center gap-1">
            Lihat Semua <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>

    <!-- Grid Card Wisata -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        <?php if (!empty($list_wisata)) : ?>
            <?php foreach($list_wisata as $wisata): 
                $wisata = (array)$wisata;
                $id = $wisata['id'];
                $img = $wisata['thumbnail'];
                $title = $wisata['nama_wisata'];
                $loc = $wisata['lokasi'];
                $price = ($wisata['harga_tiket'] > 0) ? 'Rp '.number_format($wisata['harga_tiket'],0,',','.') : 'Gratis';
                $rating = $wisata['rating'];
                $kategori_label = $wisata['kategori']; // Kategori Dinamis
                
                $link = get_permalink($id);
            ?>
            <div class="card-sadesa group">
                <div class="card-img-wrap">
                    <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" onerror="this.src='https://via.placeholder.com/400x300?text=Wisata'">
                    <div class="badge-rating"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?></div>
                    
                    <!-- DINAMIS: Label Kategori -->
                    <div class="badge-category"><?php echo esc_html($kategori_label); ?></div>
                </div>
                
                <div class="card-body">
                    <h3 class="card-title group-hover:text-primary transition"><?php echo esc_html($title); ?></h3>
                    <div class="card-meta">
                        <i class="fas fa-map-marker-alt text-red-400"></i>
                        <span class="truncate"><?php echo esc_html($loc); ?></span>
                    </div>
                    
                    <div class="card-footer">
                        <div>
                            <p class="price-label">Tiket Masuk</p>
                            <p class="price-tag"><?php echo $price; ?></p>
                        </div>
                        <a href="<?php echo esc_url($link); ?>" class="btn-detail">Lihat Detail <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-4 text-center text-gray-500 py-10">Belum ada data wisata.</div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 4: PRODUK UMKM -->
<div class="mb-10">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h3 class="font-bold text-xl md:text-2xl text-gray-800">Produk UMKM</h3>
            <p class="text-sm text-gray-500 mt-1">Oleh-oleh autentik langsung dari desa</p>
        </div>
        <a href="<?php echo home_url('/produk'); ?>" class="text-primary text-sm font-bold hover:underline flex items-center gap-1">
            Lihat Semua <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>

    <!-- Grid Produk -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
        <?php if (!empty($list_produk)) : ?>
            <?php foreach($list_produk as $produk): 
                $produk = (array)$produk;
                $id = $produk['id'];
                $img = $produk['thumbnail'];
                $title = $produk['nama_produk'];
                $shop = $produk['nama_toko'];
                $price = number_format($produk['harga_dasar'], 0, ',', '.');
                $kategori_label = $produk['kategori']; // Kategori Dinamis
                
                $link = get_permalink($id);
            ?>
            <div class="card-sadesa group relative">
                <a href="<?php echo esc_url($link); ?>" class="block h-full flex flex-col">
                    <div class="card-img-wrap aspect-square bg-gray-100 relative">
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" onerror="this.src='https://via.placeholder.com/300x300?text=Produk'">
                        
                        <!-- DINAMIS: Label Kategori (Opsional: ditampilkan kecil di atas gambar) -->
                        <span class="absolute top-2 left-2 bg-white/90 backdrop-blur text-[9px] px-2 py-1 rounded text-gray-600 font-bold shadow-sm">
                            <?php echo esc_html($kategori_label); ?>
                        </span>
                    </div>
                    <div class="card-body p-3 flex-1">
                        <h4 class="text-sm font-bold text-gray-800 leading-snug mb-1 line-clamp-2 min-h-[2.5em] group-hover:text-primary transition"><?php echo esc_html($title); ?></h4>
                        <div class="flex items-center gap-1 mb-2 text-[10px] text-gray-500">
                            <i class="fas fa-store"></i> <span class="truncate"><?php echo esc_html($shop); ?></span>
                        </div>
                        <div class="mt-auto pt-2 border-t border-dashed border-gray-100 flex justify-between items-end">
                            <div>
                                <p class="text-primary font-bold text-sm">Rp <?php echo $price; ?></p>
                                <p class="text-[9px] text-gray-400">Terjual 0</p>
                            </div>
                        </div>
                    </div>
                </a>
                <!-- Add to Cart Button (Overlay) -->
                <button class="btn-add-cart absolute bottom-3 right-3 shadow-sm z-10 add-to-cart-btn"
                        data-product-id="<?php echo $id; ?>" 
                        data-product-name="<?php echo esc_attr($title); ?>" 
                        data-product-price="<?php echo $produk['harga_dasar']; ?>" 
                        data-product-image="<?php echo esc_url($img); ?>">
                    <i class="fas fa-cart-plus text-xs"></i>
                </button>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-5 text-center text-gray-500 py-10">Belum ada data produk.</div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>