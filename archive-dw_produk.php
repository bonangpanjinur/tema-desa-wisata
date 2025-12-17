<?php
/**
 * Template Name: Arsip Produk Modern (With Photo)
 * Description: Menampilkan daftar produk dengan desain kartu modern beserta foto produk.
 */

get_header();

global $wpdb;
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

// --- PARAMETER FILTER ---
$pencarian = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kategori  = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$min_harga = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_harga = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;
$urutan    = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';

// --- QUERY BUILDER ---
$sql = "SELECT p.*, pd.nama_toko, pd.slug_toko, d.kabupaten, d.nama_desa 
        FROM $table_produk p 
        LEFT JOIN $table_pedagang pd ON p.id_pedagang = pd.id
        LEFT JOIN $table_desa d ON pd.id_desa = d.id
        WHERE p.status = 'aktif' AND pd.status_akun = 'aktif'";

if ($pencarian) {
    $sql .= $wpdb->prepare(" AND (p.nama_produk LIKE %s OR p.deskripsi_produk LIKE %s)", '%' . $pencarian . '%', '%' . $pencarian . '%');
}
if ($kategori) {
    $sql .= $wpdb->prepare(" AND p.kategori = %s", $kategori);
}
if ($min_harga > 0) {
    $sql .= $wpdb->prepare(" AND p.harga >= %d", $min_harga);
}
if ($max_harga > 0) {
    $sql .= $wpdb->prepare(" AND p.harga <= %d", $max_harga);
}

switch ($urutan) {
    case 'termurah': $sql .= " ORDER BY p.harga ASC"; break;
    case 'termahal': $sql .= " ORDER BY p.harga DESC"; break;
    case 'terlaris': $sql .= " ORDER BY p.terjual DESC"; break;
    default: $sql .= " ORDER BY p.created_at DESC"; break;
}

// Pagination
$items_per_page = 16; 
$page = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
$offset = ($page - 1) * $items_per_page;

$count_sql = str_replace("SELECT p.*, pd.nama_toko, pd.slug_toko, d.kabupaten, d.nama_desa", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$produk_list = $wpdb->get_results($sql);

$list_kategori = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE status='aktif' AND kategori != '' ORDER BY kategori ASC");
?>

<!-- === HEADER SEARCH === -->
<div class="bg-gray-900 text-white pt-10 pb-16 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 to-gray-800"></div>
    <div class="absolute top-0 right-0 w-64 h-64 bg-orange-500 rounded-full blur-[100px] opacity-20 transform translate-x-1/2 -translate-y-1/2"></div>
    
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-4">Katalog Produk Desa</h1>
        <p class="text-gray-400 mb-8 max-w-2xl mx-auto">Temukan berbagai produk lokal berkualitas langsung dari pengrajin dan UMKM desa.</p>
        
        <form action="" method="GET" class="max-w-xl mx-auto relative group">
            <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                   class="w-full pl-6 pr-12 py-4 rounded-full bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:bg-white/20 focus:border-white/30 backdrop-blur-sm transition" 
                   placeholder="Cari nama produk...">
            <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white hover:bg-orange-600 transition shadow-lg">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen -mt-8 relative z-20 rounded-t-3xl">
    <div class="container mx-auto px-4 py-8">
        
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- SIDEBAR FILTER -->
            <div class="w-full lg:w-1/4">
                <button id="mobile-filter-btn" class="lg:hidden w-full bg-white border border-gray-200 text-gray-700 font-bold py-3 rounded-xl shadow-sm mb-4 flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Filter Produk
                </button>

                <div id="mobile-filter" class="hidden lg:block bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-24 transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-800">Filter</h3>
                        <?php if($pencarian || $kategori || $min_harga): ?>
                        <a href="<?php echo home_url('/produk'); ?>" class="text-xs text-red-500 hover:underline reset-filter-btn">Reset</a>
                        <?php endif; ?>
                    </div>
                    
                    <form action="" method="GET">
                        <?php if($pencarian): ?><input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>"><?php endif; ?>
                        
                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Kategori</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="kat" value="" <?php checked($kategori, ''); ?> class="text-orange-500 focus:ring-orange-500 border-gray-300">
                                    <span class="text-sm text-gray-600 group-hover:text-orange-500 transition">Semua</span>
                                </label>
                                <?php foreach($list_kategori as $kat): ?>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="kat" value="<?php echo esc_attr($kat); ?>" <?php checked($kategori, $kat); ?> class="text-orange-500 focus:ring-orange-500 border-gray-300">
                                    <span class="text-sm text-gray-600 group-hover:text-orange-500 transition"><?php echo esc_html($kat); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Urutkan</label>
                            <select name="sort" class="w-full bg-gray-50 border-gray-200 text-sm rounded-lg focus:border-orange-500 focus:ring-orange-500 p-2.5 cursor-pointer">
                                <option value="terbaru" <?php selected($urutan, 'terbaru'); ?>>Terbaru</option>
                                <option value="terlaris" <?php selected($urutan, 'terlaris'); ?>>Terlaris</option>
                                <option value="termurah" <?php selected($urutan, 'termurah'); ?>>Harga Terendah</option>
                                <option value="termahal" <?php selected($urutan, 'termahal'); ?>>Harga Tertinggi</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PRODUCT GRID (WITH PHOTO) -->
            <div class="w-full lg:w-3/4 grid-content transition-opacity duration-200">
                <p class="text-gray-500 text-sm mb-6 flex items-center gap-2">
                    Menampilkan <span class="font-bold text-gray-900"><?php echo $total_items; ?></span> produk
                    <span class="hidden md:inline-block w-1 h-1 bg-gray-300 rounded-full"></span>
                    <span class="hidden md:inline text-xs text-gray-400">Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?></span>
                </p>

                <?php if ($produk_list) : ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    <?php foreach ($produk_list as $p) : 
                        $link_p = !empty($p->slug_produk) ? home_url('/produk/detail/' . $p->slug_produk) : home_url('/detail-produk/?id=' . $p->id);
                        $link_toko = home_url('/toko/' . $p->slug_toko);
                        $img_p = !empty($p->foto_produk) ? $p->foto_produk : 'https://via.placeholder.com/400x400?text=Produk';
                        $lokasi = !empty($p->kabupaten) ? $p->kabupaten : 'Indonesia';
                    ?>
                    
                    <!-- CARD PRODUK MODERN -->
                    <a href="<?php echo esc_url($link_p); ?>" class="group bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:border-orange-200 hover:-translate-y-1 transition duration-300 flex flex-col h-full relative">
                        <!-- Image Container -->
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            <img src="<?php echo esc_url($img_p); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="<?php echo esc_attr($p->nama_produk); ?>" loading="lazy">
                            
                            <!-- Badge Terlaris -->
                            <?php if($p->terjual > 10): ?>
                            <div class="absolute top-2 left-2 bg-orange-500/90 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                                Terlaris
                            </div>
                            <?php endif; ?>

                            <!-- Kategori Badge -->
                             <div class="absolute bottom-2 right-2 bg-black/50 backdrop-blur text-white text-[10px] font-medium px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition duration-300">
                                <?php echo esc_html($p->kategori ?: 'Umum'); ?>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-4 flex flex-col flex-1">
                            <!-- Judul Produk -->
                            <h3 class="font-medium text-gray-800 text-sm md:text-base leading-snug mb-1 line-clamp-2 group-hover:text-orange-600 transition">
                                <?php echo esc_html($p->nama_produk); ?>
                            </h3>
                            
                            <!-- Nama Toko -->
                            <div class="text-xs text-gray-400 mb-3 flex items-center gap-1">
                                <i class="fas fa-store text-[10px]"></i> <span class="truncate"><?php echo esc_html($p->nama_toko); ?></span>
                            </div>
                            
                            <!-- Footer: Harga & Lokasi -->
                            <div class="mt-auto">
                                <span class="block text-lg font-bold text-gray-900">
                                    Rp <?php echo number_format($p->harga, 0, ',', '.'); ?>
                                </span>
                                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50 text-[10px] text-gray-400">
                                    <span class="flex items-center gap-1 truncate max-w-[60%]">
                                        <i class="fas fa-map-marker-alt text-gray-300"></i> <?php echo esc_html($lokasi); ?>
                                    </span>
                                    <span><?php echo $p->terjual; ?> Terjual</span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-12 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): 
                        $active = ($i == $page) ? 'bg-orange-500 text-white border-orange-500 shadow-md' : 'bg-white text-gray-600 border-gray-200 hover:border-orange-500 hover:text-orange-500';
                        $url = add_query_arg('halaman', $i);
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border font-bold text-sm transition <?php echo $active; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="text-center py-20 bg-white rounded-2xl border-2 border-dashed border-gray-200">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                            <i class="fas fa-box-open text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Produk tidak ditemukan</h3>
                        <p class="text-gray-500 text-sm mb-6">Coba ubah kata kunci pencarian atau reset filter.</p>
                        <a href="<?php echo home_url('/produk'); ?>" class="inline-block px-6 py-2 bg-orange-50 text-orange-600 font-bold rounded-full hover:bg-orange-100 transition text-sm">
                            Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar untuk Filter Sidebar */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f9f9f9; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
</style>

<?php get_footer(); ?>