<?php
/**
 * Template Name: Arsip Wisata Sadesa
 */
get_header();

// 1. Fetch Data API
$search = get_query_var('s') ?: '';
$kategori = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';
$page = get_query_var('paged') ?: 1;

// Endpoint API Publik
$endpoint = '/wisata?per_page=9&page=' . $page;
if ($search) $endpoint .= '&search=' . urlencode($search);
if ($kategori) $endpoint .= '&kategori=' . urlencode($kategori);

$data_api = function_exists('dw_fetch_api_data') ? dw_fetch_api_data('/wp-json/dw/v1' . $endpoint) : [];
$list_wisata = $data_api['data'] ?? [];
$total_pages = $data_api['total_pages'] ?? 1;

// Dummy Categories
$cats = ['' => 'Semua', 'alam' => 'Alam', 'budaya' => 'Budaya', 'edukasi' => 'Edukasi'];
?>

<div class="bg-gray-50 min-h-screen pb-20">
    <!-- Header Filter -->
    <div class="bg-white pt-4 pb-6 rounded-b-3xl shadow-sm sticky top-[60px] z-30">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 relative">
                    <form>
                        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Cari destinasi..." class="w-full pl-10 pr-4 py-3 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary focus:bg-white transition">
                        <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400"></i>
                    </form>
                </div>
            </div>
            <div class="flex gap-2 overflow-x-auto no-scrollbar">
                <?php foreach($cats as $slug => $label): 
                    $active = ($kategori == $slug) ? 'bg-primary text-white border-primary shadow-lg shadow-green-100' : 'bg-white text-gray-500 border-gray-200 hover:border-primary hover:text-primary';
                ?>
                <a href="?kategori=<?php echo $slug; ?>" class="px-5 py-2 rounded-full border text-xs font-bold whitespace-nowrap transition-all <?php echo $active; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="container mx-auto px-4 py-6">
        <?php if (!empty($list_wisata)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($list_wisata as $wisata): 
                    // Mapping Data
                    $id = $wisata['id'];
                    $slug = $wisata['slug'] ?? ''; // Pastikan API mengembalikan slug
                    $img = $wisata['thumbnail'] ?? 'https://via.placeholder.com/600x400';
                    $title = $wisata['nama_wisata'] ?? 'Wisata';
                    $loc = $wisata['lokasi'] ?? 'Desa Wisata';
                    $price = isset($wisata['harga_tiket']) && $wisata['harga_tiket'] > 0 ? 'Rp '.number_format($wisata['harga_tiket'],0,',','.') : 'Gratis';
                    $rating = 4.8;
                    
                    // Link Detail (Fallback jika permalink lokal tidak jalan)
                    $link = get_permalink($id);
                    if (!$link || strpos($link, 'page_id') !== false) {
                        // Gunakan parameter ?post_type=dw_wisata agar template single-dw_wisata.php terpanggil
                        $link = home_url('/?p=' . $id . '&post_type=dw_wisata');
                    }
                ?>
                <div class="card-sadesa group">
                    <div class="card-img-wrap">
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>">
                        <div class="badge-rating"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?></div>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title group-hover:text-primary transition"><?php echo esc_html($title); ?></h3>
                        <div class="card-meta">
                            <i class="fas fa-map-marker-alt text-red-400"></i> <span class="truncate"><?php echo esc_html($loc); ?></span>
                        </div>
                        <div class="card-footer">
                            <div>
                                <p class="price-label">Tiket Masuk</p>
                                <p class="price-tag"><?php echo $price; ?></p>
                            </div>
                            <a href="<?php echo esc_url($link); ?>" class="btn-detail">Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-8 gap-2">
                    <?php if($page > 1): ?><a href="?paged=<?php echo $page-1; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                    <span class="px-4 h-10 flex items-center justify-center bg-primary text-white rounded-lg font-bold text-sm"><?php echo $page; ?></span>
                    <?php if($page < $total_pages): ?><a href="?paged=<?php echo $page+1; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20 text-gray-400">
                <i class="fas fa-map-signs text-5xl mb-4 opacity-30"></i>
                <p>Belum ada wisata ditemukan.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>