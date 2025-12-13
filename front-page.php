<?php
/**
 * Template Name: Halaman Depan Sadesa Style
 */
get_header();

// =================================================================================
// 1. LOGIKA PENGAMBILAN DATA (VIA API)
// =================================================================================

// --- A. GET BANNERS ---
$endpoint_banner = '/wp-json/dw/v1/banner';
$banners = [];
if (function_exists('dw_fetch_api_data')) {
    $raw_banner = dw_fetch_api_data($endpoint_banner);
    $banners = $raw_banner['data'] ?? ($raw_banner['error'] ? [] : $raw_banner);
}
// Fallback Dummy Banners (Sesuai gambar referensi)
if (empty($banners)) {
    $banners = [
        [
            'gambar'      => 'https://img.freepik.com/free-vector/gradient-abstract-banner-template_23-2149120352.jpg?w=1380&t=st=1688000000~exp=1688000600~hmac=abc',
            'judul'       => 'Banner Web Template',
            'label'       => 'Terbaru',
            'label_color' => 'bg-orange-500', // Warna badge
            'link'        => '#'
        ],
        [
            'gambar'      => 'https://img.freepik.com/free-psd/food-menu-restaurant-web-banner-template_106176-1549.jpg?w=1380&t=st=1688000000~exp=1688000600~hmac=def',
            'judul'       => 'Super Delicious Menu',
            'label'       => 'Info Desa',
            'label_color' => 'bg-blue-500',
            'link'        => '#'
        ]
    ];
}

// --- B. GET WISATA (POPULER) ---
$endpoint_wisata = '/wp-json/dw/v1/wisata'; 
$raw_wisata = function_exists('dw_fetch_api_data') ? dw_fetch_api_data($endpoint_wisata) : [];
$list_wisata = $raw_wisata['data'] ?? ($raw_wisata['error'] ? [] : $raw_wisata);
$list_wisata = array_slice($list_wisata, 0, 4);

// --- C. GET PRODUK (UMKM) ---
$endpoint_produk = '/wp-json/dw/v1/produk?per_page=10';
$raw_produk = function_exists('dw_fetch_api_data') ? dw_fetch_api_data($endpoint_produk) : [];
$list_produk = $raw_produk['data'] ?? ($raw_produk['error'] ? [] : $raw_produk);

?>

<!-- =================================================================================
     2. TAMPILAN (VIEW) - SADESA STYLE
     ================================================================================= -->

<!-- SECTION 1: HERO BANNERS (2 Kolom) -->
<div class="mb-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
        <?php foreach ($banners as $index => $banner) : 
            $img = $banner['gambar'] ?? $banner['image_url'] ?? '';
            $label = $banner['label'] ?? (($index == 0) ? 'Terbaru' : 'Info Desa');
            $label_bg = $banner['label_color'] ?? (($index == 0) ? 'bg-orange-500' : 'bg-blue-600');
            $judul = $banner['judul'] ?? 'Promo Desa';
        ?>
        <div class="relative h-48 md:h-64 rounded-2xl overflow-hidden group shadow-md">
            <!-- Background Image -->
            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="Banner">
            
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-transparent to-transparent"></div>
            
            <!-- Content -->
            <div class="absolute top-6 left-6 max-w-xs">
                <!-- Badge -->
                <span class="<?php echo $label_bg; ?> text-white text-[10px] md:text-xs font-bold px-3 py-1 rounded-full mb-3 inline-block shadow-sm">
                    <?php echo esc_html($label); ?>
                </span>
                <!-- Title -->
                <h2 class="text-white font-bold text-2xl md:text-3xl leading-tight mb-4 drop-shadow-md">
                    <?php echo esc_html($judul); ?>
                </h2>
                <!-- Button -->
                <a href="<?php echo esc_url($banner['link'] ?? '#'); ?>" class="bg-white text-gray-800 text-xs md:text-sm font-bold px-4 py-2 rounded-lg hover:bg-gray-100 transition shadow-sm inline-block">
                    Lihat Detail
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SECTION 2: KATEGORI MENU (Bulat Pastel) -->
<div class="mb-10">
    <div class="grid grid-cols-4 md:flex md:justify-center md:gap-16 gap-4">
        
        <!-- Menu 1: Wisata (Hijau) -->
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-green-100 rounded-2xl flex items-center justify-center text-[#00BA61] shadow-sm group-hover:bg-[#00BA61] group-hover:text-white transition duration-300 transform group-hover:-translate-y-1">
                <i class="fas fa-map-marked-alt text-2xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600">Wisata</span>
        </a>

        <!-- Menu 2: Produk (Oranye) -->
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-500 shadow-sm group-hover:bg-orange-500 group-hover:text-white transition duration-300 transform group-hover:-translate-y-1">
                <i class="fas fa-box-open text-2xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600">Produk</span>
        </a>

        <!-- Menu 3: Homestay (Biru) -->
        <a href="#" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-500 shadow-sm group-hover:bg-blue-500 group-hover:text-white transition duration-300 transform group-hover:-translate-y-1">
                <i class="fas fa-bed text-2xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600">Homestay</span>
        </a>

        <!-- Menu 4: Kuliner (Ungu) -->
        <a href="#" class="flex flex-col items-center gap-2 group cursor-pointer">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-purple-100 rounded-2xl flex items-center justify-center text-purple-500 shadow-sm group-hover:bg-purple-500 group-hover:text-white transition duration-300 transform group-hover:-translate-y-1">
                <i class="fas fa-utensils text-2xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600">Kuliner</span>
        </a>

    </div>
</div>

<!-- SECTION 3: WISATA POPULER -->
<div class="mb-10">
    <div class="flex justify-between items-end mb-5">
        <div>
            <h3 class="font-bold text-xl text-gray-800">Wisata Populer</h3>
            <p class="text-sm text-gray-500 hidden md:block">Destinasi favorit wisatawan minggu ini</p>
        </div>
        <a href="<?php echo home_url('/wisata'); ?>" class="text-[#00BA61] text-sm font-bold hover:underline flex items-center">
            Lihat Semua <i class="fas fa-arrow-right ml-1 text-xs"></i>
        </a>
    </div>

    <!-- Scrollable Horizontal di Mobile, Grid di Desktop -->
    <div class="flex md:grid md:grid-cols-4 gap-4 overflow-x-auto no-scrollbar pb-4 -mx-4 px-4 md:mx-0 md:px-0">
        <?php foreach ($list_wisata as $wisata) : 
            $img = $wisata['thumbnail'] ?? 'https://via.placeholder.com/300x200';
            $title = $wisata['nama_wisata'] ?? $wisata['title'] ?? 'Wisata';
            $loc = $wisata['lokasi'] ?? 'Desa Wisata';
        ?>
        <!-- Card Wisata -->
        <div class="min-w-[260px] md:min-w-0 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden group hover:shadow-md transition">
            <div class="h-40 relative">
                <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
                <!-- Rating Badge (Top Right) -->
                <div class="absolute top-3 right-3 bg-white px-2 py-1 rounded-lg text-xs font-bold shadow-sm flex items-center gap-1">
                    <i class="fas fa-star text-yellow-400"></i> 4.8
                </div>
            </div>
            <div class="p-4">
                <h4 class="font-bold text-gray-800 mb-1 text-base capitalize"><?php echo esc_html($title); ?></h4>
                <div class="flex items-center text-xs text-gray-500 mb-4">
                    <i class="fas fa-map-marker-alt text-red-400 mr-1.5"></i>
                    <?php echo esc_html($loc); ?>
                </div>
                
                <div class="flex items-center justify-between border-t border-dashed border-gray-100 pt-3">
                    <div>
                        <p class="text-[10px] text-gray-400 mb-0.5">Tiket Masuk</p>
                        <p class="text-[#00BA61] font-bold text-sm">Rp 0</p>
                    </div>
                    <a href="<?php echo home_url('/?wisata_id='.$wisata['id']); ?>" class="bg-[#E0F7EB] text-[#00964E] text-xs font-bold px-4 py-1.5 rounded-lg hover:bg-[#00BA61] hover:text-white transition">
                        Detail
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SECTION 4: PRODUK UMKM -->
<div class="mb-10">
    <div class="flex justify-between items-end mb-5">
        <div>
            <h3 class="font-bold text-xl text-gray-800">Produk UMKM</h3>
            <p class="text-sm text-gray-500 hidden md:block">Oleh-oleh autentik langsung dari desa</p>
        </div>
        <a href="<?php echo home_url('/produk'); ?>" class="text-[#00BA61] text-sm font-bold hover:underline flex items-center">
            Lihat Semua <i class="fas fa-arrow-right ml-1 text-xs"></i>
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <?php foreach ($list_produk as $produk) : 
            $img = $produk['thumbnail'] ?? 'https://via.placeholder.com/300x300';
            $title = $produk['nama_produk'] ?? 'Produk';
            $shop = $produk['nama_toko'] ?? 'UMKM Desa';
            $terjual = $produk['terjual'] ?? 0;
        ?>
        <!-- Card Produk -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-1 transition duration-300">
            <div class="aspect-square bg-gray-100 relative">
                <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
            </div>
            <div class="p-3">
                <h4 class="font-semibold text-gray-800 text-sm mb-1 line-clamp-2 leading-snug capitalize"><?php echo esc_html($title); ?></h4>
                <div class="flex items-center gap-1 mb-3">
                    <i class="fas fa-store text-[10px] text-gray-400"></i>
                    <span class="text-[10px] text-gray-500 truncate"><?php echo esc_html($shop); ?></span>
                </div>
                
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-[#00BA61] font-bold text-sm">Rp 0</p>
                        <p class="text-[10px] text-gray-400">Terjual <?php echo $terjual; ?>+</p>
                    </div>
                    <!-- Cart Button Hijau Bulat -->
                    <button class="w-8 h-8 rounded-full bg-[#E0F7EB] flex items-center justify-center text-[#00964E] hover:bg-[#00BA61] hover:text-white transition shadow-sm">
                        <i class="fas fa-shopping-cart text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php get_footer(); ?>