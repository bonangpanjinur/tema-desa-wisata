<?php
/**
 * Template Name: Arsip Wisata Sadesa
 * Description: Menampilkan daftar wisata dari API dengan desain modern.
 */
get_header();

// --- 1. FETCH DATA API ---
$search = get_query_var('s') ? get_query_var('s') : (isset($_GET['s']) ? $_GET['s'] : '');
$kategori = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';
$page = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Siapkan parameter API
$api_params = '/wisata?per_page=9&page=' . $page;
if ($search) $api_params .= '&search=' . urlencode($search);
if ($kategori) $api_params .= '&kategori=' . urlencode($kategori);

// Panggil API (gunakan fungsi helper tema)
$data_wisata = function_exists('dw_fetch_api_data') ? dw_fetch_api_data('/wp-json/dw/v1' . $api_params) : [];
$list_wisata = $data_wisata['data'] ?? [];
$total_pages = $data_wisata['total_pages'] ?? 1;

// Kategori Dummy (atau bisa fetch dari API /kategori/wisata)
$categories = [
    '' => 'Semua',
    'alam' => 'Alam',
    'budaya' => 'Budaya',
    'edukasi' => 'Edukasi',
    'kuliner' => 'Kuliner'
];
?>

<div class="bg-gray-50 min-h-screen pb-20">
    
    <!-- HEADER SECTION -->
    <div class="bg-white pb-6 pt-4 rounded-b-3xl shadow-sm border-b border-gray-100 sticky top-[60px] z-20">
        <div class="container mx-auto px-4">
            
            <!-- Title & Search -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Jelajah Wisata</h1>
                    <p class="text-sm text-gray-500">Temukan surga tersembunyi di desa kami</p>
                </div>
                
                <!-- Search Bar Mobile/Desktop -->
                <form action="" method="get" class="relative w-full md:w-80">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Cari destinasi..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary focus:bg-white transition">
                    <i class="fas fa-search absolute left-3.5 top-3 text-gray-400"></i>
                </form>
            </div>

            <!-- Category Filter (Horizontal Scroll) -->
            <div class="flex gap-3 overflow-x-auto no-scrollbar pb-2">
                <?php foreach ($categories as $slug => $label): 
                    $active = ($kategori === $slug) ? 'bg-primary text-white border-primary shadow-lg shadow-green-200' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary';
                ?>
                <a href="?kategori=<?php echo $slug; ?>" 
                   class="whitespace-nowrap px-5 py-2 rounded-full border text-xs font-bold transition-all <?php echo $active; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- CONTENT GRID -->
    <div class="container mx-auto px-4 py-8">
        <?php if (!empty($list_wisata)) : ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($list_wisata as $wisata) : 
                    // Mapping Data
                    $id = $wisata['id'];
                    $img = $wisata['thumbnail'] ?? 'https://via.placeholder.com/600x400';
                    $title = $wisata['nama_wisata'] ?? 'Wisata Alam';
                    $loc = $wisata['lokasi'] ?? 'Desa Wisata';
                    $price = (isset($wisata['harga_tiket']) && $wisata['harga_tiket'] > 0) ? 'Rp ' . number_format($wisata['harga_tiket'], 0, ',', '.') : 'Gratis';
                    $rating = $wisata['rating'] ?? 4.8;
                    
                    // Link Detail (Menggunakan Single PHP)
                    $link = get_permalink($id); 
                    // Fallback jika permalink lokal tidak ada, gunakan parameter
                    if (!$link || strpos($link, 'page_id') !== false) {
                        $link = home_url('/?p=' . $id . '&post_type=dw_wisata');
                    }
                ?>
                <!-- Card Sadesa Style -->
                <div class="card-sadesa group h-full flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition overflow-hidden">
                    <div class="relative h-48 overflow-hidden">
                        <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                        <!-- Rating Badge -->
                        <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm px-2.5 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm">
                            <i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?>
                        </div>
                    </div>
                    
                    <div class="p-5 flex flex-col flex-1">
                        <div class="mb-4">
                            <h3 class="font-bold text-lg text-gray-800 leading-tight mb-1 group-hover:text-primary transition"><?php echo esc_html($title); ?></h3>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <i class="fas fa-map-marker-alt text-red-400"></i>
                                <span><?php echo esc_html($loc); ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-auto pt-4 border-t border-dashed border-gray-100 flex justify-between items-center">
                            <div>
                                <p class="text-[10px] text-gray-400 font-medium">Mulai Dari</p>
                                <p class="text-primary font-bold text-sm"><?php echo $price; ?></p>
                            </div>
                            <a href="<?php echo esc_url($link); ?>" class="btn-detail-soft">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2 mt-10">
                <?php if ($page > 1): ?>
                    <a href="?paged=<?php echo $page - 1; ?>&kategori=<?php echo $kategori; ?>&s=<?php echo $search; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border border-gray-200 text-gray-600 hover:bg-primary hover:text-white hover:border-primary transition"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                
                <span class="px-4 h-10 flex items-center justify-center bg-primary text-white rounded-lg font-bold text-sm">
                    Halaman <?php echo $page; ?>
                </span>

                <?php if ($page < $total_pages): ?>
                    <a href="?paged=<?php echo $page + 1; ?>&kategori=<?php echo $kategori; ?>&s=<?php echo $search; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border border-gray-200 text-gray-600 hover:bg-primary hover:text-white hover:border-primary transition"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else : ?>
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <i class="fas fa-map-signs text-4xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 text-lg">Tidak Ditemukan</h3>
                <p class="text-gray-500 text-sm mt-1 max-w-xs mx-auto">Maaf, destinasi wisata yang Anda cari belum tersedia.</p>
                <a href="<?php echo home_url('/wisata'); ?>" class="mt-4 text-primary font-bold text-sm hover:underline">Lihat Semua</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>