<?php
/**
 * Template Name: Arsip Wisata Elegant
 * Description: Layout modern dengan search di atas, kategori, dan sidebar filter harga/lokasi.
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

// --- 1. PARAMETER FILTER ---
$pencarian  = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kabupaten  = isset($_GET['kab']) ? sanitize_text_field($_GET['kab']) : '';
$kategori   = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$urutan     = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';
$max_harga  = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;

// --- 2. QUERY BUILDER ---
$sql = "SELECT w.*, d.nama_desa, d.kabupaten, d.kecamatan 
        FROM $table_wisata w 
        LEFT JOIN $table_desa d ON w.id_desa = d.id 
        WHERE w.status = 'aktif'";

if ($pencarian) {
    $sql .= $wpdb->prepare(" AND (w.nama_wisata LIKE %s OR w.deskripsi LIKE %s)", '%' . $pencarian . '%', '%' . $pencarian . '%');
}
if ($kabupaten) {
    $sql .= $wpdb->prepare(" AND d.kabupaten = %s", $kabupaten);
}
if ($kategori) {
    $sql .= $wpdb->prepare(" AND w.kategori = %s", $kategori);
}
if ($max_harga > 0) {
    $sql .= $wpdb->prepare(" AND w.harga_tiket <= %d", $max_harga);
}

// Sorting
switch ($urutan) {
    case 'termurah': $sql .= " ORDER BY w.harga_tiket ASC"; break;
    case 'termahal': $sql .= " ORDER BY w.harga_tiket DESC"; break;
    case 'terpopuler': $sql .= " ORDER BY w.rating_avg DESC"; break;
    default: $sql .= " ORDER BY w.created_at DESC"; break;
}

// Pagination
$items_per_page = 9;
$page = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
$offset = ($page - 1) * $items_per_page;

$count_sql = str_replace("SELECT w.*, d.nama_desa, d.kabupaten, d.kecamatan", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$wisata_list = $wpdb->get_results($sql);

// Data Filter Dinamis
$list_kabupaten = $wpdb->get_col("SELECT DISTINCT kabupaten FROM $table_desa WHERE status='aktif' AND kabupaten != '' ORDER BY kabupaten ASC");
$list_kategori = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_wisata WHERE status='aktif' AND kategori != '' ORDER BY kategori ASC");
if(empty($list_kategori)) $list_kategori = ['Alam', 'Budaya', 'Religi', 'Kuliner', 'Edukasi'];
?>

<div class="bg-[#F8F9FB] min-h-screen font-sans text-gray-800 pb-20">
    
    <!-- === 1. HEADER SEARCH (TOP) === -->
    <div class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm/50 transition-all duration-300">
        <div class="container mx-auto px-4 py-4">
            <form action="" method="GET" class="relative max-w-3xl mx-auto flex items-center">
                <i class="fas fa-search absolute left-5 text-gray-400 text-lg"></i>
                <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                       class="w-full pl-14 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-full focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-100 transition-all outline-none placeholder-gray-400 text-gray-700 shadow-sm" 
                       placeholder="Cari destinasi wisata impianmu...">
                <!-- Keep other filters -->
                <?php if($kabupaten): ?><input type="hidden" name="kab" value="<?php echo esc_attr($kabupaten); ?>"><?php endif; ?>
                <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>
                <?php if($max_harga): ?><input type="hidden" name="max_price" value="<?php echo esc_attr($max_harga); ?>"><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="container mx-auto px-4 pt-6">
        
        <!-- === 2. CATEGORY BADGES (BELOW SEARCH) === -->
        <div class="mb-10 flex justify-center">
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="?kat=" class="px-4 py-2 rounded-full text-xs font-bold border transition-all duration-200 <?php echo $kategori == '' ? 'bg-green-600 text-white border-green-600 shadow-lg shadow-green-200 transform scale-105' : 'bg-white text-gray-600 border-gray-200 hover:border-green-500 hover:text-green-600'; ?>">
                    Semua
                </a>
                <?php foreach($list_kategori as $kat): ?>
                <a href="?kat=<?php echo urlencode($kat); ?>&q=<?php echo urlencode($pencarian); ?>&kab=<?php echo urlencode($kabupaten); ?>" 
                   class="px-4 py-2 rounded-full text-xs font-bold border transition-all duration-200 <?php echo $kategori == $kat ? 'bg-green-600 text-white border-green-600 shadow-lg shadow-green-200 transform scale-105' : 'bg-white text-gray-600 border-gray-200 hover:border-green-500 hover:text-green-600'; ?>">
                    <?php echo esc_html($kat); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8 items-start">
            
            <!-- === 3. SIDEBAR FILTERS (LEFT) === -->
            <aside class="w-full lg:w-1/4 lg:sticky lg:top-28 space-y-6">
                
                <!-- Toggle Mobile -->
                <button id="mobile-filter-toggle" class="lg:hidden w-full flex items-center justify-between bg-white p-4 rounded-xl border border-gray-200 font-bold text-gray-700 shadow-sm">
                    <span><i class="fas fa-filter mr-2 text-green-500"></i> Filter Pencarian</span>
                    <i class="fas fa-chevron-down transition-transform duration-300" id="filter-chevron"></i>
                </button>

                <div id="sidebar-content" class="hidden lg:block space-y-6">
                    <form action="" method="GET" id="sidebar-form">
                        <?php if($pencarian): ?><input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>"><?php endif; ?>
                        <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>

                        <!-- A. Filter Harga -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span class="w-1 h-5 bg-green-500 rounded-full"></span> Budget Tiket
                            </h3>
                            <div class="space-y-3">
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="max_price" value="0" <?php checked($max_harga, 0); ?> onchange="this.form.submit()" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500 cursor-pointer">
                                    <span class="ml-3 text-sm text-gray-600 group-hover:text-green-600 transition">Semua Harga</span>
                                </label>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="max_price" value="0.1" <?php checked($max_harga, 0.1); ?> onchange="this.form.submit()" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500 cursor-pointer">
                                    <span class="ml-3 text-sm text-gray-600 group-hover:text-green-600 transition">Gratis</span>
                                </label>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="max_price" value="10000" <?php checked($max_harga, 10000); ?> onchange="this.form.submit()" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500 cursor-pointer">
                                    <span class="ml-3 text-sm text-gray-600 group-hover:text-green-600 transition">< Rp 10.000</span>
                                </label>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="max_price" value="25000" <?php checked($max_harga, 25000); ?> onchange="this.form.submit()" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500 cursor-pointer">
                                    <span class="ml-3 text-sm text-gray-600 group-hover:text-green-600 transition">< Rp 25.000</span>
                                </label>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="max_price" value="50000" <?php checked($max_harga, 50000); ?> onchange="this.form.submit()" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500 cursor-pointer">
                                    <span class="ml-3 text-sm text-gray-600 group-hover:text-green-600 transition">< Rp 50.000</span>
                                </label>
                            </div>
                        </div>

                        <!-- B. Filter Lokasi -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span class="w-1 h-5 bg-blue-500 rounded-full"></span> Lokasi
                            </h3>
                            <div class="max-h-60 overflow-y-auto custom-scrollbar pr-2 space-y-2">
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="kab" value="" <?php checked($kabupaten, ''); ?> onchange="this.form.submit()" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer">
                                    <span class="ml-3 text-sm text-gray-600 group-hover:text-blue-600 transition">Semua Lokasi</span>
                                </label>
                                <?php foreach($list_kabupaten as $kab): ?>
                                <label class="flex items-center cursor-pointer group">
                                    <input type="radio" name="kab" value="<?php echo esc_attr($kab); ?>" <?php checked($kabupaten, $kab); ?> onchange="this.form.submit()" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer">
                                    <span class="ml-3 text-sm text-gray-600 group-hover:text-blue-600 transition"><?php echo esc_html($kab); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- C. Urutan -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <span class="w-1 h-5 bg-orange-500 rounded-full"></span> Urutkan
                            </h3>
                            <select name="sort" onchange="this.form.submit()" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5">
                                <option value="terbaru" <?php selected($urutan, 'terbaru'); ?>>Terbaru</option>
                                <option value="terpopuler" <?php selected($urutan, 'terpopuler'); ?>>Rating Tertinggi</option>
                                <option value="termurah" <?php selected($urutan, 'termurah'); ?>>Harga Terendah</option>
                                <option value="termahal" <?php selected($urutan, 'termahal'); ?>>Harga Tertinggi</option>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <?php if($pencarian || $kabupaten || $kategori || $max_harga > 0): ?>
                        <a href="<?php echo home_url('/wisata'); ?>" class="block w-full text-center py-3 bg-red-50 text-red-600 font-bold rounded-xl hover:bg-red-100 transition text-sm">
                            <i class="fas fa-trash-alt mr-2"></i> Reset Filter
                        </a>
                        <?php endif; ?>

                    </form>
                </div>
            </aside>

            <!-- === 4. CONTENT CARDS (RIGHT) === -->
            <main class="w-full lg:w-3/4">
                
                <div class="flex items-center justify-between mb-6">
                    <p class="text-sm text-gray-500">Menampilkan <span class="font-bold text-gray-900"><?php echo $total_items; ?></span> destinasi wisata</p>
                </div>

                <?php if ($wisata_list) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($wisata_list as $w) : 
                        $link_w = !empty($w->slug) ? home_url('/wisata/detail/' . $w->slug) : home_url('/detail-wisata/?id=' . $w->id);
                        $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/600x400?text=Wisata';
                        $rating = ($w->rating_avg > 0) ? $w->rating_avg : 'Baru';
                        $lokasi = !empty($w->kabupaten) ? $w->kabupaten : 'Indonesia';
                    ?>
                    
                    <!-- CARD ELEGANT -->
                    <a href="<?php echo esc_url($link_w); ?>" class="group bg-white rounded-2xl overflow-hidden hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.1)] transition-all duration-300 flex flex-col h-full border border-gray-100 hover:border-transparent transform hover:-translate-y-1">
                        
                        <!-- Image Container -->
                        <div class="relative h-52 overflow-hidden">
                            <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110" alt="<?php echo esc_attr($w->nama_wisata); ?>">
                            
                            <!-- Dark Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>

                            <!-- Category Badge (Dynamic) -->
                            <?php if(isset($w->kategori)): ?>
                            <div class="absolute top-3 left-3">
                                <span class="bg-white/95 backdrop-blur-md text-gray-800 text-[10px] font-bold px-3 py-1 rounded-full shadow-sm tracking-wide uppercase border border-gray-100 group-hover:border-white">
                                    <?php echo esc_html($w->kategori); ?>
                                </span>
                            </div>
                            <?php endif; ?>

                            <!-- Rating -->
                            <div class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-md text-white px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow-lg transform translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition duration-300">
                                <i class="fas fa-star text-yellow-400 text-[10px]"></i> <?php echo $rating; ?>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-center gap-1 text-xs text-gray-400 mb-2">
                                <i class="fas fa-map-marker-alt text-red-400"></i>
                                <span class="truncate max-w-[150px]"><?php echo esc_html($lokasi); ?></span>
                            </div>

                            <h3 class="font-bold text-gray-800 text-lg leading-snug mb-3 group-hover:text-green-600 transition line-clamp-2">
                                <?php echo esc_html($w->nama_wisata); ?>
                            </h3>

                            <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                                <div>
                                    <span class="block text-[10px] text-gray-400 uppercase font-bold tracking-wider">Tiket Masuk</span>
                                    <span class="text-green-600 font-bold text-base">
                                        <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                                    </span>
                                </div>
                                <span class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-green-500 group-hover:text-white transition shadow-sm">
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-12 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): 
                        $active = ($i == $page) ? 'bg-green-600 text-white border-green-600 shadow-lg shadow-green-200' : 'bg-white text-gray-600 border-gray-200 hover:border-green-600 hover:text-green-600';
                        $url = add_query_arg('halaman', $i);
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border font-bold text-sm transition-all duration-200 <?php echo $active; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-gray-100 text-center shadow-sm">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="far fa-compass text-3xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada wisata ditemukan</h3>
                        <p class="text-gray-500 max-w-xs mx-auto mb-6 text-sm">Sesuaikan filter pencarian Anda.</p>
                        <a href="<?php echo home_url('/wisata'); ?>" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition text-sm">
                            Reset
                        </a>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</div>

<style>
/* Sidebar Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Mobile toggle logic
    const toggleBtn = document.getElementById('mobile-filter-toggle');
    const content = document.getElementById('sidebar-content');
    const chevron = document.getElementById('filter-chevron');

    if(toggleBtn && content) {
        toggleBtn.addEventListener('click', () => {
            content.classList.toggle('hidden');
            if(chevron) chevron.classList.toggle('rotate-180');
        });
    }
});
</script>

<?php get_footer(); ?>