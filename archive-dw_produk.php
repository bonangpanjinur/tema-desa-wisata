<?php
/**
 * Template Name: Arsip Produk Sadesa
 * Description: Menampilkan daftar produk dengan filter kategori dan pencarian.
 */
get_header();

// 1. Ambil Parameter URL
$search   = get_query_var('s') ?: (isset($_GET['s']) ? $_GET['s'] : '');
$kategori = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';
$page     = get_query_var('paged') ?: 1;

// 2. Susun Parameter API
$endpoint = '/produk?per_page=10&page=' . $page;
if ($search) $endpoint .= '&search=' . urlencode($search);
if ($kategori) $endpoint .= '&kategori=' . urlencode($kategori);

// 3. Fetch Data API
$data_api = function_exists('dw_fetch_api_data') ? dw_fetch_api_data('/wp-json/dw/v1' . $endpoint) : [];
$products = $data_api['data'] ?? [];
$total_pages = $data_api['total_pages'] ?? 1;

// 4. Kategori Produk (Hardcoded Dummy / Bisa diganti fetch API /kategori/produk)
$cats = [
    '' => 'Semua',
    'makanan' => 'Makanan',
    'minuman' => 'Minuman',
    'kerajinan' => 'Kerajinan',
    'fashion' => 'Fashion',
    'pertanian' => 'Pertanian'
];
?>

<div class="bg-gray-50 min-h-screen pb-20">
    
    <!-- HEADER SECTION (Search & Filter) -->
    <div class="bg-white pt-6 pb-6 rounded-b-3xl shadow-sm border-b border-gray-100 sticky top-[60px] z-30">
        <div class="container mx-auto px-4">
            
            <!-- Title & Search Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Produk UMKM</h1>
                    <p class="text-sm text-gray-500 mt-1">Oleh-oleh autentik langsung dari desa</p>
                </div>
                
                <form action="" method="get" class="relative w-full md:w-80">
                    <!-- Simpan kategori saat search -->
                    <?php if($kategori): ?>
                        <input type="hidden" name="kategori" value="<?php echo esc_attr($kategori); ?>">
                    <?php endif; ?>
                    
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" 
                           placeholder="Cari produk..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary focus:bg-white transition">
                    <i class="fas fa-search absolute left-3.5 top-3 text-gray-400"></i>
                </form>
            </div>

            <!-- Category Pills (Horizontal Scroll) -->
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                <?php foreach($cats as $slug => $label): 
                    $isActive = ($kategori == $slug);
                    $cls = $isActive 
                        ? 'bg-primary text-white shadow-lg shadow-green-100 border-primary' 
                        : 'bg-white text-gray-500 border-gray-200 hover:border-primary hover:text-primary';
                    
                    // Build Link (Keep Search param if exists)
                    $link = '?kategori=' . $slug . ($search ? '&s=' . urlencode($search) : '');
                ?>
                <a href="<?php echo esc_url($link); ?>" 
                   class="px-5 py-2 rounded-full border text-xs font-bold whitespace-nowrap transition-all <?php echo $cls; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- PRODUCT GRID CONTENT -->
    <div class="container mx-auto px-4 py-8">
        <?php if (!empty($products)): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
                <?php foreach($products as $p): 
                    $id = $p['id'];
                    $img = $p['thumbnail'] ?? 'https://via.placeholder.com/300';
                    $title = $p['nama_produk'] ?? 'Produk';
                    $shop = $p['nama_toko'] ?? 'UMKM';
                    $price = number_format($p['harga_dasar'] ?? 0, 0, ',', '.');
                    
                    // Link Detail (Fallback ke parameter jika permalink belum diset)
                    $link = get_permalink($id);
                    if (!$link || strpos($link, 'page_id') !== false) {
                        $link = home_url('/?p=' . $id . '&post_type=dw_produk');
                    }
                ?>
                <!-- Card Sadesa -->
                <div class="card-sadesa group relative bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition flex flex-col h-full overflow-hidden">
                    <a href="<?php echo esc_url($link); ?>" class="flex-1 flex flex-col">
                        <div class="card-img-wrap aspect-square bg-gray-100 relative overflow-hidden">
                            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                            <!-- Badge Category (Optional) -->
                            <!-- <span class="badge-category top-2 left-2 bottom-auto">Baru</span> -->
                        </div>
                        
                        <div class="card-body p-3 flex-1 flex flex-col">
                            <h4 class="text-sm font-bold text-gray-800 leading-snug mb-1 line-clamp-2 min-h-[2.5em] group-hover:text-primary transition"><?php echo esc_html($title); ?></h4>
                            
                            <div class="flex items-center gap-1.5 mb-2 text-[10px] text-gray-500">
                                <i class="fas fa-store"></i> 
                                <span class="truncate max-w-[100px]"><?php echo esc_html($shop); ?></span>
                            </div>

                            <div class="mt-auto pt-2 border-t border-dashed border-gray-100 flex justify-between items-end">
                                <div>
                                    <p class="text-primary font-bold text-sm">Rp <?php echo $price; ?></p>
                                    <p class="text-[9px] text-gray-400">Terjual 0</p>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Add to Cart Button (Absolute Overlay) -->
                    <button class="add-to-cart-btn btn-add-cart absolute bottom-3 right-3 shadow-md z-10 bg-green-50 hover:bg-primary text-primary hover:text-white"
                            data-id="<?php echo $id; ?>" 
                            data-title="<?php echo esc_attr($title); ?>" 
                            data-price="<?php echo $p['harga_dasar']; ?>" 
                            data-thumb="<?php echo esc_url($img); ?>">
                        <i class="fas fa-cart-plus text-xs"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-10 gap-2">
                    <?php if($page > 1): ?>
                        <a href="?paged=<?php echo $page-1; ?>&s=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary hover:text-primary transition"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <span class="px-4 h-10 flex items-center justify-center bg-primary text-white rounded-lg font-bold text-sm">
                        <?php echo $page; ?>
                    </span>

                    <?php if($page < $total_pages): ?>
                        <a href="?paged=<?php echo $page+1; ?>&s=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary hover:text-primary transition"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="py-20 text-center flex flex-col items-center justify-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <i class="fas fa-box-open text-4xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 text-lg">Produk Tidak Ditemukan</h3>
                <p class="text-gray-500 text-sm mt-1">Coba cari dengan kata kunci lain atau ubah filter.</p>
                <a href="<?php echo home_url('/produk'); ?>" class="mt-4 text-primary font-bold text-sm hover:underline">Reset Filter</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>