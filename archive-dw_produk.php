<?php
/**
 * Template Name: Arsip Produk Modern
 * Description: Menampilkan daftar produk UMKM dengan filter.
 */

get_header();

global $wpdb;
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

// --- 1. PARAMETER FILTER ---
$pencarian = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kategori  = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$min_harga = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_harga = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;
$urutan    = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';

// --- 2. QUERY BUILDER ---
// Join Produk -> Pedagang -> Desa (Untuk filter lokasi desa jika perlu)
$sql = "SELECT p.*, pd.nama_toko, pd.slug_toko, d.kabupaten 
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

// Sorting
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

$count_sql = str_replace("SELECT p.*, pd.nama_toko, pd.slug_toko, d.kabupaten", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$produk_list = $wpdb->get_results($sql);

// --- 3. DATA FILTER ---
$list_kategori = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE status='aktif' AND kategori != '' ORDER BY kategori ASC");
?>

<!-- === HEADER HERO SEARCH === -->
<div class="bg-white border-b border-gray-100 pt-8 pb-12">
    <div class="container mx-auto px-4 text-center">
        <span class="text-orange-500 font-bold tracking-wider text-xs uppercase mb-2 block">Produk Lokal Terbaik</span>
        <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 mb-6">Oleh-Oleh Khas Desa</h1>
        
        <form action="" method="GET" class="max-w-2xl mx-auto relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400 group-focus-within:text-orange-500 transition"></i>
            </div>
            <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                   class="w-full pl-11 pr-4 py-4 rounded-full border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition shadow-lg shadow-gray-100 text-gray-700 placeholder-gray-400" 
                   placeholder="Cari produk, misal: 'Keripik' atau 'Kain Tenun'...">
        </form>

        <div class="mt-6 flex flex-wrap justify-center gap-2 text-sm">
            <span class="text-gray-400">Tren:</span>
            <a href="?sort=terlaris" class="hover:text-orange-500 hover:underline transition">Paling Laris</a>
            <span class="text-gray-300">•</span>
            <a href="?kat=Makanan" class="hover:text-orange-500 hover:underline transition">Makanan</a>
            <span class="text-gray-300">•</span>
            <a href="?kat=Kerajinan" class="hover:text-orange-500 hover:underline transition">Kerajinan</a>
        </div>
    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- === SIDEBAR FILTER === -->
            <div class="w-full lg:w-1/4">
                <button onclick="document.getElementById('mobile-filter').classList.toggle('hidden')" class="lg:hidden w-full bg-white border border-gray-200 text-gray-700 font-bold py-3 rounded-xl shadow-sm mb-4 flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Filter Produk
                </button>

                <div id="mobile-filter" class="hidden lg:block bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-900 text-lg">Filter</h3>
                        <?php if($pencarian || $kategori || $min_harga || $max_harga): ?>
                            <a href="<?php echo home_url('/produk'); ?>" class="text-xs text-red-500 hover:underline">Reset</a>
                        <?php endif; ?>
                    </div>

                    <form action="" method="GET">
                        <?php if($pencarian): ?><input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>"><?php endif; ?>

                        <!-- Kategori -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-3">Kategori</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="kat" value="" <?php checked($kategori, ''); ?> onchange="this.form.submit()" class="text-orange-500 focus:ring-orange-500 border-gray-300">
                                    <span class="text-sm text-gray-600 group-hover:text-orange-500 transition">Semua Kategori</span>
                                </label>
                                <?php foreach($list_kategori as $kat): ?>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="kat" value="<?php echo esc_attr($kat); ?>" <?php checked($kategori, $kat); ?> onchange="this.form.submit()" class="text-orange-500 focus:ring-orange-500 border-gray-300">
                                    <span class="text-sm text-gray-600 group-hover:text-orange-500 transition"><?php echo esc_html($kat); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Rentang Harga -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-3">Rentang Harga</label>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="number" name="min_price" value="<?php echo $min_harga ?: ''; ?>" placeholder="Min" class="w-full text-xs border-gray-200 rounded-lg focus:border-orange-500 focus:ring-orange-500">
                                <input type="number" name="max_price" value="<?php echo $max_harga ?: ''; ?>" placeholder="Max" class="w-full text-xs border-gray-200 rounded-lg focus:border-orange-500 focus:ring-orange-500">
                            </div>
                        </div>

                        <!-- Urutan -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Urutkan</label>
                            <select name="sort" onchange="this.form.submit()" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block p-2.5">
                                <option value="terbaru" <?php selected($urutan, 'terbaru'); ?>>Terbaru</option>
                                <option value="terlaris" <?php selected($urutan, 'terlaris'); ?>>Paling Laris</option>
                                <option value="termurah" <?php selected($urutan, 'termurah'); ?>>Harga Terendah</option>
                                <option value="termahal" <?php selected($urutan, 'termahal'); ?>>Harga Tertinggi</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-orange-500 text-white font-bold py-2.5 rounded-lg hover:bg-orange-600 transition shadow-lg shadow-orange-200">
                            Terapkan
                        </button>
                    </form>
                </div>
            </div>

            <!-- === GRID CONTENT === -->
            <div class="w-full lg:w-3/4">
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-500 text-sm">Menampilkan <span class="font-bold text-gray-900"><?php echo $total_items; ?></span> produk lokal</p>
                </div>

                <?php if ($produk_list) : ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    <?php foreach ($produk_list as $p) : 
                        $link_p = !empty($p->slug_produk) ? home_url('/produk/detail/' . $p->slug_produk) : home_url('/detail-produk/?id=' . $p->id);
                        $img_p = !empty($p->foto_produk) ? $p->foto_produk : 'https://via.placeholder.com/400x400?text=Produk';
                        $lokasi = !empty($p->kabupaten) ? $p->kabupaten : 'Indonesia';
                    ?>
                    <a href="<?php echo esc_url($link_p); ?>" class="group bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:border-orange-200 transition duration-300 flex flex-col h-full">
                        <div class="relative aspect-square overflow-hidden bg-gray-100">
                            <img src="<?php echo esc_url($img_p); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <?php if($p->terjual > 10): ?>
                            <div class="absolute top-2 left-2 bg-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">
                                Terlaris
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <h3 class="font-medium text-gray-800 text-sm md:text-base mb-1 line-clamp-2 leading-snug group-hover:text-orange-500 transition">
                                <?php echo esc_html($p->nama_produk); ?>
                            </h3>
                            <div class="text-xs text-gray-400 mb-3 flex items-center gap-1">
                                <i class="fas fa-store"></i> <?php echo esc_html($p->nama_toko); ?>
                            </div>
                            
                            <div class="mt-auto">
                                <span class="block text-lg font-bold text-orange-600">
                                    Rp <?php echo number_format($p->harga, 0, ',', '.'); ?>
                                </span>
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-50 text-[10px] text-gray-400">
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($lokasi); ?></span>
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
                        $active_cls = ($i == $page) ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-600 border-gray-200 hover:border-orange-500 hover:text-orange-500';
                        $query_params = $_GET;
                        $query_params['halaman'] = $i;
                        $page_url = '?' . http_build_query($query_params);
                    ?>
                    <a href="<?php echo esc_url($page_url); ?>" class="w-8 h-8 flex items-center justify-center rounded border font-bold text-xs transition <?php echo $active_cls; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-20 bg-white rounded-2xl border-2 border-dashed border-gray-200">
                        <i class="fas fa-box-open text-3xl text-gray-300 mb-4 block"></i>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Produk tidak ditemukan</h3>
                        <a href="<?php echo home_url('/produk'); ?>" class="text-orange-500 font-bold hover:underline">Reset Pencarian</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>