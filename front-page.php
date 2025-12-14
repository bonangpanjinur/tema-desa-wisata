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

// Cek apakah tabel ada
if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
    $db_banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'active' ORDER BY id DESC LIMIT 5");
    
    if (!empty($db_banners)) {
        foreach ($db_banners as $b) {
            $banners[] = [
                'gambar'      => $b->image_url,
                'judul'       => $b->title,
                'label'       => 'Info Desa', // Default label
                'label_color' => 'bg-blue-600', // Default color
                'link'        => $b->link
            ];
        }
        // Set banner pertama jadi 'Terbaru' & Orange (sesuai style desain awal)
        if (isset($banners[0])) {
            $banners[0]['label'] = 'Terbaru';
            $banners[0]['label_color'] = 'bg-orange-500';
        }
    }
}

// Fallback Banner (Jika Database Kosong)
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

// --- B. Wisata Data (Dari Post Type: dw_wisata) ---
$list_wisata = [];
$args_wisata = array(
    'post_type'      => 'dw_wisata',
    'posts_per_page' => 4,
    'orderby'        => 'date',
    'order'          => 'DESC'
);
$query_wisata = new WP_Query($args_wisata);

if ($query_wisata->have_posts()) {
    while ($query_wisata->have_posts()) {
        $query_wisata->the_post();
        $list_wisata[] = [
            'id'          => get_the_ID(),
            'nama_wisata' => get_the_title(),
            'lokasi'      => get_post_meta(get_the_ID(), 'dw_lokasi', true) ?: 'Desa Wisata',
            'harga_tiket' => get_post_meta(get_the_ID(), 'dw_harga_tiket', true) ?: 0,
            'rating'      => 4.8, // Default rating (karena belum ada sistem rating real)
            'thumbnail'   => get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: 'https://via.placeholder.com/400x300',
            'slug'        => get_post_field('post_name')
        ];
    }
    wp_reset_postdata();
}

// Fallback Wisata
$using_dummy_wisata = false;
if (empty($list_wisata)) {
    $using_dummy_wisata = true;
    $list_wisata = [
        ['id' => 1, 'nama_wisata' => 'Bukit Senja Indah', 'lokasi' => 'Desa Wisata', 'harga_tiket' => 15000, 'rating' => 4.8, 'thumbnail' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80', 'slug' => 'bukit-senja'],
        ['id' => 2, 'nama_wisata' => 'Air Terjun Segar', 'lokasi' => 'Desa Wisata', 'harga_tiket' => 10000, 'rating' => 4.5, 'thumbnail' => 'https://images.unsplash.com/photo-1432405972618-c60b0225b8f9?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80', 'slug' => 'air-terjun'],
        ['id' => 3, 'nama_wisata' => 'Kampung Budaya', 'lokasi' => 'Desa Wisata', 'harga_tiket' => 20000, 'rating' => 4.9, 'thumbnail' => 'https://images.unsplash.com/photo-1518182170546-07661d42a560?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80', 'slug' => 'kampung-budaya'],
        ['id' => 4, 'nama_wisata' => 'Danau Biru', 'lokasi' => 'Desa Wisata', 'harga_tiket' => 5000, 'rating' => 4.7, 'thumbnail' => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80', 'slug' => 'danau-biru']
    ];
}

// --- C. Produk Data (Dari Post Type: dw_produk) ---
$list_produk = [];
$args_produk = array(
    'post_type'      => 'dw_produk',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC'
);
$query_produk = new WP_Query($args_produk);

if ($query_produk->have_posts()) {
    while ($query_produk->have_posts()) {
        $query_produk->the_post();
        
        // Ambil nama toko dari author atau meta
        $seller_id = get_post_field('post_author');
        $nama_toko = get_the_author_meta('display_name', $seller_id);

        // Gunakan helper harga yang sudah dibuat sebelumnya (jika ada), kalau tidak manual
        $harga = function_exists('dw_get_product_price') ? dw_get_product_price(get_the_ID()) : get_post_meta(get_the_ID(), 'dw_harga', true);

        $list_produk[] = [
            'id'          => get_the_ID(),
            'nama_produk' => get_the_title(),
            'nama_toko'   => $nama_toko ?: 'UMKM Desa',
            'harga_dasar' => $harga ?: 0,
            'thumbnail'   => get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://via.placeholder.com/300x300'
        ];
    }
    wp_reset_postdata();
}

// Fallback Produk
$using_dummy_produk = false;
if (empty($list_produk)) {
    $using_dummy_produk = true;
    $list_produk = [
        ['id' => 1, 'nama_produk' => 'Kopi Robusta Asli', 'nama_toko' => 'Kopi Desa', 'harga_dasar' => 25000, 'thumbnail' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80'],
        ['id' => 2, 'nama_produk' => 'Keripik Singkong', 'nama_toko' => 'Snack Maknyus', 'harga_dasar' => 12000, 'thumbnail' => 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80'],
        ['id' => 3, 'nama_produk' => 'Anyaman Bambu', 'nama_toko' => 'Kerajinan Tangan', 'harga_dasar' => 45000, 'thumbnail' => 'https://images.unsplash.com/photo-1589366623678-ebf984e7233c?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80'],
        ['id' => 4, 'nama_produk' => 'Madu Hutan Murni', 'nama_toko' => 'Madu Alami', 'harga_dasar' => 80000, 'thumbnail' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&q=80']
    ];
}
?>

<!-- =================================================================================
     2. VIEW SECTION (SADESA STYLE) - 100% ORIGINAL DESIGN
     ================================================================================= -->

<!-- DEBUG ALERT (Only Admin) -->
<?php if (current_user_can('administrator') && !empty($debug_messages)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 m-4 rounded-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Debug Mode: Gagal Mengambil Data</h3>
                <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <?php foreach ($debug_messages as $msg): ?>
                            <li><?php echo esc_html($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-2 font-bold">Menampilkan data dummy untuk sementara.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- SECTION 1: HERO BANNERS -->
<div class="mb-10 mt-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
        <?php foreach ($banners as $index => $banner) : 
            $img = $banner['gambar'] ?? $banner['image_url'] ?? '';
            $title = $banner['judul'] ?? 'Promo Desa';
            $label = $banner['label'] ?? (($index == 0) ? 'Terbaru' : 'Info Desa');
            $label_bg = $banner['label_color'] ?? (($index == 0) ? 'bg-orange-500' : 'bg-blue-600');
            $link_url = $banner['link'] ?? '#';
        ?>
        <div class="relative h-48 md:h-64 rounded-2xl overflow-hidden group shadow-md cursor-pointer">
            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
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
        <?php foreach($list_wisata as $wisata): 
            $wisata = (array)$wisata;
            $id = $wisata['id'];
            $img = $wisata['thumbnail'] ?? 'https://via.placeholder.com/400x300';
            $title = $wisata['nama_wisata'] ?? $wisata['title'] ?? 'Wisata';
            $loc = $wisata['lokasi'] ?? 'Desa Wisata';
            $price = isset($wisata['harga_tiket']) && $wisata['harga_tiket'] > 0 ? 'Rp '.number_format($wisata['harga_tiket'],0,',','.') : 'Gratis';
            $rating = $wisata['rating'] ?? 4.8;
            
            // LINK LOGIC (Fix Link Detail)
            if ($using_dummy_wisata) {
                $link = '#'; // Dummy link
            } else {
                // Coba ambil permalink post asli
                $link = get_permalink($id);
                // Jika tidak valid (karena post type mungkin belum di flush), gunakan custom link parameter
                if (!$link || strpos($link, 'page_id') !== false) {
                    $link = home_url('/?p=' . $id . '&post_type=dw_wisata');
                }
            }
        ?>
        <div class="card-sadesa group">
            <div class="card-img-wrap">
                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>">
                <div class="badge-rating"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?></div>
                <div class="badge-category">Wisata</div>
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
        <?php foreach($list_produk as $produk): 
            $produk = (array)$produk;
            $id = $produk['id'];
            $img = $produk['thumbnail'] ?? 'https://via.placeholder.com/300x300';
            $title = $produk['nama_produk'] ?? 'Produk';
            $shop = $produk['nama_toko'] ?? 'UMKM Desa';
            $price = number_format($produk['harga_dasar'] ?? 0, 0, ',', '.');
            
            // LINK LOGIC
            if ($using_dummy_produk) {
                $link = '#';
            } else {
                $link = get_permalink($id);
                if (!$link || strpos($link, 'page_id') !== false) {
                    $link = home_url('/?p=' . $id . '&post_type=dw_produk');
                }
            }
        ?>
        <div class="card-sadesa group relative">
            <a href="<?php echo esc_url($link); ?>" class="block h-full flex flex-col">
                <div class="card-img-wrap aspect-square bg-gray-100">
                    <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>">
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
    </div>
</div>

<?php get_footer(); ?>