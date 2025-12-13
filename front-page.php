<?php
/**
 * The template for displaying the front page
 *
 * @package DesaWisataTheme
 */

get_header();

// =================================================================================
// 1. LOGIKA PENGAMBILAN DATA (DATA FETCHING)
// =================================================================================

// --- A. GET BANNERS (VIA DATABASE LANGSUNG) ---
global $wpdb;
$banners = [];

// PERBAIKAN: Nama tabel sesuai plugin adalah 'dw_banner' (bukan jamak)
$table_banner = $wpdb->prefix . 'dw_banner';

// 1. Cek apakah tabel plugin 'dw_banner' ada
if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") === $table_banner) {
    
    // 2. Query data: 
    // - status = 'aktif' (sesuai struktur tabel plugin)
    // - Urutkan berdasarkan 'prioritas' ASC (banner prioritas kecil muncul duluan)
    $results = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'aktif' ORDER BY prioritas ASC", ARRAY_A);
    
    if (!empty($results)) {
        $banners = $results;
    }
}

// Fallback: Jika Database Kosong, gunakan data dummy
if (empty($banners)) {
    $banners = [
        [
            // Struktur Dummy disamakan dengan DB agar loop di bawah konsisten
            'gambar'      => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul'       => 'Jelajah Alam Desa',
            'link'        => '#',
            'description' => 'Data belum diisi. Silakan upload Banner di Menu Desa Wisata > Banner.' // Dummy field tambahan
        ],
        [
            'gambar'      => 'https://images.unsplash.com/photo-1596423736798-75b43694f540?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul'       => 'Kerajinan Bambu',
            'link'        => '#',
            'description' => 'Ini adalah tampilan contoh jika belum ada banner aktif.'
        ]
    ];
}

// Variasi warna gradient background
$gradients = [
    'from-blue-600 to-cyan-500',
    'from-emerald-600 to-green-500',
    'from-purple-600 to-indigo-500',
    'from-orange-500 to-red-500',
    'from-pink-600 to-rose-500'
];

?>

<!-- =================================================================================
     2. TAMPILAN (VIEW)
     ================================================================================= -->

<!-- HERO SECTION: Desktop Layout Update -->
<div class="mt-4 px-0 md:px-0">
    <!-- Banner Slider (Horizontal Scroll on Mobile, Grid on Desktop) -->
    <div class="flex md:grid md:grid-cols-2 overflow-x-auto gap-4 no-scrollbar snap-x md:snap-none pb-4 md:pb-0">
        
        <?php foreach ($banners as $index => $banner) : 
            // PERBAIKAN: Mapping variabel sesuai kolom database plugin (dw_banner)
            // Kolom DB: id, judul, gambar, link, status, prioritas
            
            $img   = !empty($banner['gambar']) ? $banner['gambar'] : 'https://via.placeholder.com/800x400?text=No+Image';
            $title = !empty($banner['judul']) ? $banner['judul'] : 'Info Desa Wisata';
            $link  = !empty($banner['link']) ? $banner['link'] : '#';
            
            // Kolom deskripsi tidak ada di DB plugin, kita kosongkan atau isi default
            $desc  = isset($banner['description']) ? $banner['description'] : ''; 
            
            // Logika Tampilan
            $gradient_class = $gradients[$index % count($gradients)];
            $label = ($index == 0) ? 'Terbaru' : 'Info Desa';
            $label_bg = ($index % 2 == 0) ? 'bg-orange-500' : 'bg-blue-500';
        ?>
        
        <!-- Banner Item -->
        <div class="min-w-[90%] md:min-w-0 h-48 md:h-80 bg-gradient-to-r <?php echo $gradient_class; ?> rounded-2xl relative snap-center shadow-lg overflow-hidden group">
            <!-- Background Image -->
            <img src="<?php echo esc_url($img); ?>" 
                 class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-50 group-hover:scale-105 transition duration-700"
                 alt="<?php echo esc_attr($title); ?>">
            
            <!-- Content Overlay -->
            <div class="absolute bottom-6 left-6 text-white max-w-xs z-10">
                <span class="text-xs font-bold <?php echo $label_bg; ?> px-3 py-1 rounded-full mb-3 inline-block shadow-sm">
                    <?php echo esc_html($label); ?>
                </span>
                
                <h2 class="font-bold text-2xl md:text-4xl mb-2 leading-tight drop-shadow-md">
                    <?php echo esc_html($title); ?>
                </h2>
                
                <?php if ($desc) : ?>
                <p class="text-sm md:text-base opacity-90 mb-4 line-clamp-2 drop-shadow-sm">
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
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-2 group">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-green-100 rounded-2xl flex items-center justify-center text-emerald-600 shadow-sm group-hover:bg-emerald-600 group-hover:text-white transition duration-300 group-hover:-translate-y-1">
                <i class="fas fa-map-marked-alt text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-emerald-600">Wisata</span>
        </a>
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-2 group">
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
        <a href="<?php echo home_url('/wisata'); ?>" class="text-emerald-600 text-sm font-bold hover:underline">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
    </div>
    
    <!-- Responsive Grid: Scroll on Mobile, Grid on Desktop -->
    <div class="flex md:grid md:grid-cols-4 gap-4 overflow-x-auto md:overflow-visible no-scrollbar pb-4 md:pb-0 -mx-4 md:mx-0 px-4 md:px-0">
        <?php
        $args_wisata = [
            'post_type' => 'dw_wisata',
            'posts_per_page' => 4,
        ];
        $query_wisata = new WP_Query($args_wisata);
        
        if ($query_wisata->have_posts()) :
            while ($query_wisata->have_posts()) : $query_wisata->the_post();
                // Mengambil meta data (pastikan key meta sesuai dengan plugin backend)
                $harga = get_post_meta(get_the_ID(), 'harga_tiket', true) ?: 0;
                $lokasi = get_post_meta(get_the_ID(), 'lokasi', true) ?: 'Desa Wisata';
                $rating = 4.8; // Hardcode sementara atau ambil dari meta reviews
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large') : 'https://via.placeholder.com/500x300?text=No+Image';
        ?>
            <!-- Wisata Card -->
            <div class="min-w-[240px] md:min-w-0 md:w-full bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-lg transition duration-300 flex flex-col">
                <div class="h-36 md:h-48 bg-gray-200 relative overflow-hidden">
                    <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <span class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm">
                        <i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?>
                    </span>
                </div>
                <div class="p-4 flex flex-col flex-1">
                    <h4 class="font-bold text-gray-800 truncate mb-1 text-base"><?php the_title(); ?></h4>
                    <p class="text-xs text-gray-500 mb-3 truncate"><i class="fas fa-map-marker-alt text-red-400 mr-1"></i> <?php echo esc_html($lokasi); ?></p>
                    <div class="mt-auto flex justify-between items-center border-t border-dashed border-gray-100 pt-3">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-400">Tiket Masuk</span>
                            <span class="text-emerald-600 font-bold text-sm">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">Detail</a>
                    </div>
                </div>
            </div>
        <?php 
            endwhile;
            wp_reset_postdata();
        else:
        ?>
            <div class="col-span-full text-center py-10 bg-gray-50 rounded-xl">
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
        <a href="<?php echo home_url('/produk'); ?>" class="text-emerald-600 text-sm font-bold hover:underline">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
    </div>

    <!-- Responsive Grid: 2 Cols Mobile, 5 Cols Desktop -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-6">
        <?php
        $args_produk = [
            'post_type' => 'dw_produk',
            'posts_per_page' => 10,
        ];
        $query_produk = new WP_Query($args_produk);
        
        if ($query_produk->have_posts()) :
            while ($query_produk->have_posts()) : $query_produk->the_post();
                // Mengambil meta data
                $harga = get_post_meta(get_the_ID(), 'harga', true) ?: 0;
                $penjual = get_post_meta(get_the_ID(), 'nama_toko', true) ?: 'UMKM Desa';
                $terjual = get_post_meta(get_the_ID(), 'terjual', true) ?: 0;
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : 'https://via.placeholder.com/500x500?text=No+Image';
        ?>
            <!-- Product Card -->
            <div class="bg-white p-2.5 md:p-3 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-xl hover:-translate-y-1 transition duration-300 group">
                <a href="<?php the_permalink(); ?>">
                    <div class="aspect-square rounded-lg bg-gray-100 overflow-hidden mb-3 relative">
                        <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </div>
                    <h4 class="font-medium text-gray-800 text-sm line-clamp-2 leading-snug mb-1 group-hover:text-emerald-600 transition"><?php the_title(); ?></h4>
                    <div class="flex items-center gap-1 mb-2">
                        <i class="fas fa-store text-[10px] text-gray-400"></i>
                        <p class="text-[10px] text-gray-500 truncate"><?php echo esc_html($penjual); ?></p>
                    </div>
                </a>
                <div class="mt-auto flex justify-between items-end">
                    <div class="flex flex-col">
                        <span class="text-emerald-700 font-bold text-sm">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                        <span class="text-[10px] text-gray-400">Terjual <?php echo $terjual; ?>+</span>
                    </div>
                    <button class="bg-emerald-50 text-emerald-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-emerald-600 hover:text-white transition shadow-sm border border-emerald-100">
                        <i class="fas fa-cart-plus text-xs"></i>
                    </button>
                </div>
            </div>
        <?php 
            endwhile;
            wp_reset_postdata();
        else:
        ?>
             <div class="col-span-full text-center py-10">
                 <p class="text-gray-500">Belum ada produk tersedia.</p>
             </div>
        <?php endif; ?>
    </div>
</div>

<div class="h-6"></div>

<?php get_footer(); ?>