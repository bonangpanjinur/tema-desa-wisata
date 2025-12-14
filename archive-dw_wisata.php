<?php
/**
 * Template Name: Arsip Wisata Sadesa
 * Description: Menampilkan daftar wisata dengan kategori dinamis dari API.
 */
get_header();

// --- 1. FETCH DATA UTAMA (WISATA) ---
$search   = get_query_var('s') ?: (isset($_GET['s']) ? $_GET['s'] : '');
$kategori = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';
$page     = get_query_var('paged') ?: 1;

// Siapkan parameter API Wisata
$endpoint_wisata = '/wisata?per_page=9&page=' . $page;
if ($search) $endpoint_wisata .= '&search=' . urlencode($search);
if ($kategori) $endpoint_wisata .= '&kategori=' . urlencode($kategori);

$data_api = function_exists('dw_fetch_api_data') ? dw_fetch_api_data('/wp-json/dw/v1' . $endpoint_wisata) : [];
$list_wisata = $data_api['data'] ?? [];
$total_pages = $data_api['total_pages'] ?? 1;

// --- 2. FETCH DATA KATEGORI (DINAMIS) ---
$categories = ['' => 'Semua']; // Opsi default

if (function_exists('dw_fetch_api_data')) {
    // Mengambil data dari endpoint kategori
    $api_cats = dw_fetch_api_data('/wp-json/dw/v1/kategori/wisata');
    
    // Validasi dan masukkan ke array dropdown/filter
    if (!empty($api_cats) && !isset($api_cats['error']) && is_array($api_cats)) {
        foreach ($api_cats as $cat) {
            // API mengembalikan array: ['id' => 1, 'nama' => 'Alam', 'slug' => 'alam']
            $slug = is_array($cat) ? $cat['slug'] : $cat->slug;
            $name = is_array($cat) ? $cat['nama'] : $cat->nama;
            $categories[$slug] = $name;
        }
    }
}
?>

<div class="bg-gray-50 min-h-screen pb-20">
    
    <!-- HEADER FILTER SECTION -->
    <div class="bg-white pt-4 pb-6 rounded-b-3xl shadow-sm border-b border-gray-100 sticky top-[60px] z-30">
        <div class="container mx-auto px-4">
            
            <!-- Pencarian -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-4">
                <div class="w-full md:w-auto">
                    <h1 class="text-2xl font-bold text-gray-800">Jelajah Wisata</h1>
                    <p class="text-sm text-gray-500 mt-1">Temukan destinasi favoritmu</p>
                </div>
                <div class="flex-1 w-full md:w-auto md:max-w-md relative">
                    <form action="" method="get">
                        <?php if($kategori): ?>
                            <input type="hidden" name="kategori" value="<?php echo esc_attr($kategori); ?>">
                        <?php endif; ?>
                        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Cari destinasi..." class="w-full pl-10 pr-4 py-3 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary focus:bg-white transition">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400"></i>
                    </form>
                </div>
            </div>

            <!-- Kategori Pills (Dinamis) -->
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                <?php foreach($categories as $slug => $label): 
                    $isActive = ($kategori === (string)$slug);
                    $cls = $isActive 
                        ? 'bg-primary text-white border-primary shadow-lg shadow-green-100' 
                        : 'bg-white text-gray-500 border-gray-200 hover:border-primary hover:text-primary';
                    
                    // Buat Link Filter (Pertahankan query search jika ada)
                    $filter_link = '?kategori=' . $slug . ($search ? '&s=' . urlencode($search) : '');
                ?>
                <a href="<?php echo esc_url($filter_link); ?>" class="px-5 py-2 rounded-full border text-xs font-bold whitespace-nowrap transition-all <?php echo $cls; ?>">
                    <?php echo esc_html($label); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- LIST CONTENT -->
    <div class="container mx-auto px-4 py-8">
        <?php if (!empty($list_wisata)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($list_wisata as $wisata): 
                    // Mapping Data
                    $id = $wisata['id'];
                    $img = $wisata['thumbnail'] ?? 'https://via.placeholder.com/600x400';
                    $title = $wisata['nama_wisata'] ?? 'Wisata';
                    $loc = $wisata['lokasi'] ?? 'Desa Wisata';
                    $price = isset($wisata['harga_tiket']) && $wisata['harga_tiket'] > 0 ? 'Rp '.number_format($wisata['harga_tiket'],0,',','.') : 'Gratis';
                    $rating = $wisata['rating'] ?? 4.8;
                    
                    // Link Detail (Fallback ke parameter post_type)
                    $link = get_permalink($id);
                    if (!$link || strpos($link, 'page_id') !== false) {
                        $link = home_url('/?p='.$id.'&post_type=dw_wisata');
                    }
                ?>
                <!-- Card Style Sadesa -->
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-10 gap-2">
                    <?php if($page > 1): ?>
                        <a href="?paged=<?php echo $page-1; ?>&kategori=<?php echo $kategori; ?>&s=<?php echo $search; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary hover:text-primary transition"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <span class="px-4 h-10 flex items-center justify-center bg-primary text-white rounded-lg font-bold text-sm">
                        <?php echo $page; ?>
                    </span>

                    <?php if($page < $total_pages): ?>
                        <a href="?paged=<?php echo $page+1; ?>&kategori=<?php echo $kategori; ?>&s=<?php echo $search; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary hover:text-primary transition"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <i class="fas fa-map-signs text-4xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 text-lg">Tidak Ditemukan</h3>
                <p class="text-gray-500 text-sm mt-1 max-w-xs mx-auto">Wisata untuk kategori atau pencarian ini belum tersedia.</p>
                <a href="<?php echo home_url('/wisata'); ?>" class="mt-4 text-primary font-bold text-sm hover:underline">Reset Filter</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>