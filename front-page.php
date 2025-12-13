<?php
/**
 * Template Name: Halaman Depan Sadesa
 */
get_header();

// =================================================================================
// 1. DATA FETCHING (API)
// =================================================================================

// --- Banner Data ---
$endpoint_banner = '/wp-json/dw/v1/banner';
$data_banner = function_exists('dw_fetch_api_data') ? dw_fetch_api_data($endpoint_banner) : [];
$banners = [];

if (isset($data_banner['data']) && is_array($data_banner['data'])) {
    $banners = $data_banner['data'];
} elseif (is_array($data_banner) && !isset($data_banner['error'])) {
    $banners = $data_banner;
}

// Fallback Dummy jika API kosong
if (empty($banners)) {
    $banners = [
        [
            'gambar' => 'https://images.unsplash.com/photo-1596423736798-75b43694f540?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul' => 'Pesona Alam Desa', 'label' => 'Terbaru', 'label_color' => 'bg-orange-500'
        ],
        [
            'gambar' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul' => 'Kuliner Tradisional', 'label' => 'Info Desa', 'label_color' => 'bg-blue-500'
        ]
    ];
}

// --- Wisata Data ---
$endpoint_wisata = '/wp-json/dw/v1/wisata';
$raw_wisata = function_exists('dw_fetch_api_data') ? dw_fetch_api_data($endpoint_wisata) : [];
$list_wisata = $raw_wisata['data'] ?? ($raw_wisata['error'] ? [] : $raw_wisata);
$list_wisata = array_slice($list_wisata, 0, 4); // Limit 4

// --- Produk Data ---
$endpoint_produk = '/wp-json/dw/v1/produk?per_page=10';
$raw_produk = function_exists('dw_fetch_api_data') ? dw_fetch_api_data($endpoint_produk) : [];
$list_produk = $raw_produk['data'] ?? ($raw_produk['error'] ? [] : $raw_produk);
?>

<!-- =================================================================================
     2. VIEW SECTION
     ================================================================================= -->

<!-- SECTION 1: HERO BANNERS (2 Kolom) -->
<div class="mb-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
        <?php foreach ($banners as $index => $banner) : 
            $img = $banner['gambar'] ?? $banner['image_url'] ?? '';
            $title = $banner['judul'] ?? 'Promo Desa';
            $label = $banner['label'] ?? (($index == 0) ? 'Terbaru' : 'Info Desa');
            $label_bg = $banner['label_color'] ?? (($index == 0) ? 'bg-orange-500' : 'bg-blue-600');
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
                <button class="bg-white text-gray-800 text-xs md:text-sm font-bold px-5 py-2.5 rounded-lg hover:bg-gray-100 transition shadow-lg transform hover:-translate-y-0.5">
                    Lihat Detail
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SECTION 2: MENU KATEGORI (Lingkaran Pastel) -->
<div class="mb-12">
    <div class="grid grid-cols-4 md:flex md:justify-center md:gap-16 gap-4">
        
        <!-- Item: Wisata -->
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-green-600 group-hover:text-white">
                <i class="fas fa-map-marked-alt text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-green-600 transition-colors">Wisata</span>
        </a>

        <!-- Item: Produk -->
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-orange-500 group-hover:text-white">
                <i class="fas fa-box-open text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-orange-500 transition-colors">Produk</span>
        </a>

        <!-- Item: Homestay -->
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-blue-100 text-blue-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-blue-500 group-hover:text-white">
                <i class="fas fa-bed text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-blue-500 transition-colors">Homestay</span>
        </a>

        <!-- Item: Kuliner -->
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

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
        <?php if($list_wisata): foreach($list_wisata as $wisata): 
            $img = $wisata['thumbnail'] ?? 'https://via.placeholder.com/400x300';
            $title = $wisata['nama_wisata'] ?? 'Wisata';
            $loc = $wisata['lokasi'] ?? 'Desa Wisata';
            $price = isset($wisata['harga_tiket']) && $wisata['harga_tiket'] > 0 ? 'Rp '.number_format($wisata['harga_tiket'],0,',','.') : 'Gratis';
        ?>
        <div class="card-sadesa group"> <!-- class card-sadesa ada di main.css -->
            <div class="relative h-48 overflow-hidden">
                <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                <div class="absolute top-3 right-3 rating-badge"> <!-- class rating-badge ada di main.css -->
                    <i class="fas fa-star text-yellow-400 text-xs"></i> 4.8
                </div>
            </div>
            <div class="p-4 flex flex-col flex-1">
                <h4 class="font-bold text-gray-800 text-lg mb-1 truncate"><?php echo esc_html($title); ?></h4>
                <div class="flex items-center text-xs text-gray-500 mb-4">
                    <i class="fas fa-map-marker-alt text-red-400 mr-1.5"></i> <?php echo esc_html($loc); ?>
                </div>
                
                <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex justify-between items-center">
                    <div>
                        <p class="text-[10px] text-gray-400 font-medium">Tiket Masuk</p>
                        <p class="text-primary font-bold text-sm"><?php echo $price; ?></p>
                    </div>
                    <a href="<?php echo home_url('/?wisata_id='.$wisata['id']); ?>" class="btn-detail-soft">Detail</a>
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
            <p class="col-span-4 text-center text-gray-400 py-10">Belum ada data wisata.</p>
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

    <!-- Grid Produk (2 Col Mobile, 5 Col Desktop) -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
        <?php if($list_produk): foreach($list_produk as $produk): 
            $img = $produk['thumbnail'] ?? 'https://via.placeholder.com/300x300';
            $title = $produk['nama_produk'] ?? 'Produk';
            $shop = $produk['nama_toko'] ?? 'UMKM Desa';
            $price = number_format($produk['harga_dasar'] ?? 0, 0, ',', '.');
        ?>
        <div class="card-sadesa group">
            <div class="aspect-square relative overflow-hidden bg-gray-100">
                <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
            </div>
            <div class="p-3 flex flex-col flex-1">
                <h4 class="font-semibold text-gray-800 text-sm leading-snug mb-1 line-clamp-2 min-h-[2.5em]"><?php echo esc_html($title); ?></h4>
                
                <div class="flex items-center gap-1 mb-3">
                    <i class="fas fa-store text-[10px] text-gray-400"></i>
                    <p class="text-[10px] text-gray-500 truncate max-w-[100px]"><?php echo esc_html($shop); ?></p>
                </div>

                <div class="mt-auto flex items-end justify-between">
                    <div>
                        <p class="text-primary font-bold text-sm">Rp <?php echo $price; ?></p>
                        <p class="text-[10px] text-gray-400">Terjual 0</p>
                    </div>
                    <!-- Cart Button (AJAX Class di main.js) -->
                    <button class="add-to-cart-btn btn-cart-circle" 
                            data-id="<?php echo $produk['id']; ?>" 
                            data-title="<?php echo esc_attr($title); ?>" 
                            data-price="<?php echo $produk['harga_dasar']; ?>" 
                            data-thumb="<?php echo esc_url($img); ?>">
                        <i class="fas fa-cart-plus text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
            <p class="col-span-full text-center text-gray-400 py-10">Belum ada produk.</p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>