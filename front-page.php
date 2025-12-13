<?php
/**
 * Template Name: Halaman Depan API (Desain Fixed)
 */
get_header();

// =================================================================================
// 1. LOGIKA PENGAMBILAN DATA (VIA API)
// =================================================================================

// --- A. GET BANNERS ---
$endpoint_banner = '/wp-json/dw/v1/banner';
$data_banner = dw_fetch_api_data( $endpoint_banner );
$banners = [];

if ( isset( $data_banner['data'] ) && is_array( $data_banner['data'] ) ) {
    $banners = $data_banner['data'];
} elseif ( is_array( $data_banner ) && !isset( $data_banner['error'] ) ) {
    $banners = $data_banner;
}

// Fallback jika kosong (Dummy Data agar tampilan tidak rusak saat pertama install)
if (empty($banners)) {
    $banners = [
        [
            'gambar'      => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul'       => 'Jelajah Alam Desa',
            'link'        => '#',
            'deskripsi'   => 'Nikmati keindahan alam yang asri dan menenangkan.'
        ],
        [
            'gambar'      => 'https://images.unsplash.com/photo-1596423736798-75b43694f540?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul'       => 'Kerajinan Bambu',
            'link'        => '#',
            'deskripsi'   => 'Produk lokal berkualitas dari pengrajin desa.'
        ]
    ];
}

// --- B. GET WISATA (POPULER) ---
$endpoint_wisata = '/wp-json/dw/v1/wisata'; 
$data_wisata = dw_fetch_api_data( $endpoint_wisata );
$list_wisata = [];

if ( isset( $data_wisata['data'] ) && is_array( $data_wisata['data'] ) ) {
    $list_wisata = $data_wisata['data'];
} elseif ( is_array( $data_wisata ) && !isset( $data_wisata['error'] ) ) {
    $list_wisata = $data_wisata;
}
// Ambil 4 item saja
$list_wisata = array_slice($list_wisata, 0, 4);

// --- C. GET PRODUK (UMKM) ---
$endpoint_produk = '/wp-json/dw/v1/produk?per_page=10';
$data_produk = dw_fetch_api_data( $endpoint_produk );
$list_produk = [];

if ( isset( $data_produk['data'] ) && is_array( $data_produk['data'] ) ) {
    $list_produk = $data_produk['data'];
} elseif ( is_array( $data_produk ) && !isset( $data_produk['error'] ) ) {
    $list_produk = $data_produk;
}
?>

<!-- =================================================================================
     2. TAMPILAN (VIEW) - DESAIN ORIGINAL
     ================================================================================= -->

<div id="primary" class="content-area">
    <main id="main" class="site-main container mx-auto px-4">

        <!-- HERO SECTION: Banner Slider -->
        <div class="mt-4 px-0 md:px-0">
            <div class="flex md:grid md:grid-cols-2 overflow-x-auto gap-4 no-scrollbar snap-x md:snap-none pb-4 md:pb-0">
                
                <?php foreach ($banners as $index => $banner) : 
                    // Mapping Variabel API
                    // Prioritas: Key API -> Key Dummy -> Default
                    $img   = $banner['gambar'] ?? $banner['image_url'] ?? 'https://via.placeholder.com/800x400?text=No+Image';
                    $title = $banner['judul'] ?? $banner['title'] ?? 'Info Desa Wisata';
                    $link  = $banner['link'] ?? $banner['url'] ?? '#';
                    $desc  = $banner['deskripsi'] ?? $banner['description'] ?? ''; 
                    
                    // Style Labels
                    $label = ($index == 0) ? 'Terbaru' : 'Info Desa';
                    $label_bg = ($index % 2 == 0) ? 'bg-orange-500' : 'bg-blue-500';
                ?>
                
                <!-- Banner Item -->
                <div class="min-w-[90%] md:min-w-0 h-48 md:h-80 bg-gray-200 rounded-2xl relative snap-center shadow-lg overflow-hidden group">
                    <!-- Background Image -->
                    <img src="<?php echo esc_url($img); ?>" 
                         class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-700"
                         alt="<?php echo esc_attr($title); ?>">
                    
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent pointer-events-none"></div>

                    <!-- Content Overlay -->
                    <div class="absolute bottom-6 left-6 text-white max-w-xs z-10">
                        <span class="text-xs font-bold <?php echo $label_bg; ?> px-3 py-1 rounded-full mb-3 inline-block shadow-sm">
                            <?php echo esc_html($label); ?>
                        </span>
                        
                        <h2 class="font-bold text-2xl md:text-4xl mb-2 leading-tight drop-shadow-md">
                            <?php echo esc_html($title); ?>
                        </h2>
                        
                        <?php if ($desc) : ?>
                        <p class="text-sm md:text-base text-gray-100 mb-4 line-clamp-2 drop-shadow-sm">
                            <?php echo esc_html($desc); ?>
                        </p>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url($link); ?>" class="inline-block bg-white text-gray-800 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition shadow-md">
                            Lihat Detail
                        </a>
                    </div>
                </div>
                
                <?php endforeach; ?>

            </div>
        </div>

        <!-- Categories Menu -->
        <div class="mt-8 mb-8">
            <div class="grid grid-cols-4 md:flex md:justify-center md:gap-12 gap-4 text-center">
                <!-- Link Kategori disesuaikan dengan parameter query string untuk tema client -->
                <a href="<?php echo home_url('/?view=wisata'); ?>" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-green-100 rounded-2xl flex items-center justify-center text-emerald-600 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition duration-300 group-hover:-translate-y-1">
                        <i class="fas fa-map-marked-alt text-2xl md:text-3xl"></i>
                    </div>
                    <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-emerald-600">Wisata</span>
                </a>
                <a href="<?php echo home_url('/?view=produk'); ?>" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 shadow-sm group-hover:bg-orange-600 group-hover:text-white transition duration-300 group-hover:-translate-y-1">
                        <i class="fas fa-box-open text-2xl md:text-3xl"></i>
                    </div>
                    <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-orange-600">Produk</span>
                </a>
                <a href="#" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 shadow-sm group-hover:bg-blue-600 group-hover:text-white transition duration-300 group-hover:-translate-y-1">
                        <i class="fas fa-bed text-2xl md:text-3xl"></i>
                    </div>
                    <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-blue-600">Homestay</span>
                </a>
                <a href="#" class="flex flex-col items-center gap-2 group">
                    <div class="w-14 h-14 md:w-16 md:h-16 bg-purple-100 rounded-2xl flex items-center justify-center text-purple-600 shadow-sm group-hover:bg-purple-600 group-hover:text-white transition duration-300 group-hover:-translate-y-1">
                        <i class="fas fa-utensils text-2xl md:text-3xl"></i>
                    </div>
                    <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-purple-600">Kuliner</span>
                </a>
            </div>
        </div>

        <!-- Section: Wisata Populer -->
        <div class="mt-10">
            <div class="flex justify-between items-end mb-4 px-1">
                <div>
                    <h3 class="font-bold text-xl text-gray-800">Wisata Populer</h3>
                    <p class="text-xs md:text-sm text-gray-500 hidden md:block">Destinasi favorit wisatawan minggu ini</p>
                </div>
                <a href="<?php echo home_url('/?view=wisata'); ?>" class="text-emerald-600 text-sm font-bold hover:underline">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            
            <div class="flex md:grid md:grid-cols-4 gap-4 overflow-x-auto md:overflow-visible no-scrollbar pb-4 md:pb-0 -mx-4 md:mx-0 px-4 md:px-0">
                <?php
                if (!empty($list_wisata)) :
                    foreach ($list_wisata as $wisata) :
                        // Mapping Data API -> Variable
                        $id         = $wisata['id'] ?? 0;
                        $judul      = $wisata['nama_wisata'] ?? $wisata['title'] ?? 'Wisata Tanpa Nama';
                        $lokasi     = $wisata['lokasi'] ?? $wisata['alamat'] ?? 'Desa Wisata';
                        $harga      = $wisata['harga_tiket'] ?? 0;
                        $rating     = $wisata['rating'] ?? 4.8;
                        $image_url  = $wisata['thumbnail'] ?? $wisata['featured_image_url'] ?? 'https://via.placeholder.com/500x300?text=No+Image';
                        
                        $link_detail = home_url('/?wisata_id=' . $id);
                ?>
                    <!-- Wisata Card -->
                    <div class="min-w-[240px] md:min-w-0 md:w-full bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-lg transition duration-300 flex flex-col">
                        <div class="h-36 md:h-48 bg-gray-200 relative overflow-hidden">
                            <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <span class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm">
                                <i class="fas fa-star text-yellow-400"></i> <?php echo esc_html($rating); ?>
                            </span>
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <h4 class="font-bold text-gray-800 truncate mb-1 text-base"><?php echo esc_html($judul); ?></h4>
                            <p class="text-xs text-gray-500 mb-3 truncate"><i class="fas fa-map-marker-alt text-red-400 mr-1"></i> <?php echo esc_html($lokasi); ?></p>
                            <div class="mt-auto flex justify-between items-center border-t border-dashed border-gray-100 pt-3">
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-gray-400">Tiket Masuk</span>
                                    <span class="text-emerald-600 font-bold text-sm">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                                </div>
                                <a href="<?php echo esc_url($link_detail); ?>" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">Detail</a>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="col-span-full text-center py-10 bg-gray-50 rounded-xl w-full">
                        <p class="text-gray-500">Belum ada data wisata.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section: Produk Desa -->
        <div class="mt-12 mb-8">
            <div class="flex justify-between items-end mb-4 px-1">
                <div>
                    <h3 class="font-bold text-xl text-gray-800">Produk UMKM</h3>
                    <p class="text-xs md:text-sm text-gray-500 hidden md:block">Oleh-oleh autentik langsung dari desa</p>
                </div>
                <a href="<?php echo home_url('/?view=produk'); ?>" class="text-emerald-600 text-sm font-bold hover:underline">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-6">
                <?php
                if (!empty($list_produk)) :
                    foreach ($list_produk as $produk) :
                        // Mapping Data API Produk
                        $id         = $produk['id'] ?? 0;
                        $judul      = $produk['nama_produk'] ?? $produk['title'] ?? 'Produk Tanpa Nama';
                        $harga      = $produk['harga_dasar'] ?? $produk['harga'] ?? 0;
                        $penjual    = $produk['nama_toko'] ?? $produk['store_name'] ?? 'UMKM Desa';
                        $terjual    = $produk['terjual'] ?? 0;
                        $image_url  = $produk['thumbnail'] ?? $produk['featured_image_url'] ?? 'https://via.placeholder.com/500x500?text=No+Image';
                        
                        $link_detail = home_url('/?produk_id=' . $id);
                ?>
                    <!-- Product Card -->
                    <div class="bg-white p-2.5 md:p-3 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-xl hover:-translate-y-1 transition duration-300 group">
                        <a href="<?php echo esc_url($link_detail); ?>">
                            <div class="aspect-square rounded-lg bg-gray-100 overflow-hidden mb-3 relative">
                                <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            </div>
                            <h4 class="font-medium text-gray-800 text-sm line-clamp-2 leading-snug mb-1 group-hover:text-emerald-600 transition"><?php echo esc_html($judul); ?></h4>
                            <div class="flex items-center gap-1 mb-2">
                                <i class="fas fa-store text-[10px] text-gray-400"></i>
                                <p class="text-[10px] text-gray-500 truncate"><?php echo esc_html($penjual); ?></p>
                            </div>
                        </a>
                        <div class="mt-auto flex justify-between items-end">
                            <div class="flex flex-col">
                                <span class="text-emerald-700 font-bold text-sm">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                                <span class="text-[10px] text-gray-400">Terjual <?php echo esc_html($terjual); ?>+</span>
                            </div>
                            <button class="bg-emerald-50 text-emerald-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-emerald-600 hover:text-white transition shadow-sm border border-emerald-100">
                                <i class="fas fa-cart-plus text-xs"></i>
                            </button>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                     <div class="col-span-full text-center py-10">
                         <p class="text-gray-500">Belum ada produk tersedia.</p>
                     </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="h-6"></div>

    </main>
</div>

<?php get_footer(); ?>