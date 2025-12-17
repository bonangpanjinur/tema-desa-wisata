<?php
/**
 * Template Name: Arsip Wisata Modern
 * Description: Menampilkan daftar wisata dengan filter canggih.
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

// --- 1. PARAMETER FILTER (Dari URL) ---
$pencarian  = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kabupaten  = isset($_GET['kab']) ? sanitize_text_field($_GET['kab']) : '';
$kategori   = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$urutan     = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';

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
// Jika ada kolom kategori di tabel wisata (asumsi 'kategori')
if ($kategori) {
    $sql .= $wpdb->prepare(" AND w.kategori = %s", $kategori); 
}

// Sorting
switch ($urutan) {
    case 'termurah':
        $sql .= " ORDER BY w.harga_tiket ASC";
        break;
    case 'termahal':
        $sql .= " ORDER BY w.harga_tiket DESC";
        break;
    case 'terpopuler':
        $sql .= " ORDER BY w.rating_avg DESC"; // Asumsi ada rating
        break;
    default: // terbaru
        $sql .= " ORDER BY w.created_at DESC";
        break;
}

// Pagination Logic (Simple)
$items_per_page = 12;
$page = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
$offset = ($page - 1) * $items_per_page;

// Count Total for Pagination
$count_sql = str_replace("SELECT w.*, d.nama_desa, d.kabupaten, d.kecamatan", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

// Final SQL with Limit
$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$wisata_list = $wpdb->get_results($sql);

// --- 3. DATA UNTUK FILTER (Ambil Unique Values) ---
$list_kabupaten = $wpdb->get_col("SELECT DISTINCT kabupaten FROM $table_desa WHERE status='aktif' ORDER BY kabupaten ASC");
// $list_kategori = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_wisata WHERE status='aktif'"); // Uncomment jika kolom kategori ada
$list_kategori = ['Alam', 'Budaya', 'Religi', 'Kuliner', 'Edukasi']; // Dummy kategori jika kolom belum ada
?>

<!-- === HEADER HERO SEARCH === -->
<div class="bg-white border-b border-gray-100 pt-8 pb-12">
    <div class="container mx-auto px-4 text-center">
        <span class="text-primary font-bold tracking-wider text-xs uppercase mb-2 block">Jelajahi Indonesia</span>
        <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 mb-6">Temukan Destinasi Impianmu</h1>
        
        <form action="" method="GET" class="max-w-2xl mx-auto relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400 group-focus-within:text-primary transition"></i>
            </div>
            <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                   class="w-full pl-11 pr-4 py-4 rounded-full border border-gray-200 focus:border-primary focus:ring-4 focus:ring-primary/10 transition shadow-lg shadow-gray-100 text-gray-700 placeholder-gray-400" 
                   placeholder="Cari wisata, misal: 'Curug' atau 'Pantai'...">
            <!-- Hidden inputs untuk menjaga filter lain saat search -->
            <?php if($kabupaten): ?><input type="hidden" name="kab" value="<?php echo esc_attr($kabupaten); ?>"><?php endif; ?>
            <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>
        </form>

        <!-- Quick Tags -->
        <div class="mt-6 flex flex-wrap justify-center gap-2 text-sm">
            <span class="text-gray-400">Populer:</span>
            <a href="?kat=Alam" class="hover:text-primary hover:underline transition">Wisata Alam</a>
            <span class="text-gray-300">•</span>
            <a href="?kat=Budaya" class="hover:text-primary hover:underline transition">Desa Budaya</a>
            <span class="text-gray-300">•</span>
            <a href="?sort=termurah" class="hover:text-primary hover:underline transition">Tiket Murah</a>
        </div>
    </div>
</div>

<!-- === MAIN CONTENT AREA === -->
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- === SIDEBAR FILTER (Desktop Sticky) === -->
            <div class="w-full lg:w-1/4">
                
                <!-- Tombol Filter Mobile -->
                <button onclick="document.getElementById('mobile-filter').classList.toggle('hidden')" class="lg:hidden w-full bg-white border border-gray-200 text-gray-700 font-bold py-3 rounded-xl shadow-sm mb-4 flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> Filter & Urutkan
                </button>

                <div id="mobile-filter" class="hidden lg:block bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-900 text-lg">Filter</h3>
                        <?php if($pencarian || $kabupaten || $kategori): ?>
                            <a href="<?php echo home_url('/wisata'); ?>" class="text-xs text-red-500 hover:underline">Reset</a>
                        <?php endif; ?>
                    </div>

                    <form action="" method="GET" id="filter-form">
                        <!-- Pertahankan query search -->
                        <?php if($pencarian): ?><input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>"><?php endif; ?>

                        <!-- Filter Lokasi -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Lokasi (Kabupaten)</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="kab" value="" <?php checked($kabupaten, ''); ?> onchange="this.form.submit()" class="text-primary focus:ring-primary border-gray-300">
                                    <span class="text-sm text-gray-600 group-hover:text-primary transition">Semua Lokasi</span>
                                </label>
                                <?php foreach($list_kabupaten as $kab): ?>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="kab" value="<?php echo esc_attr($kab); ?>" <?php checked($kabupaten, $kab); ?> onchange="this.form.submit()" class="text-primary focus:ring-primary border-gray-300">
                                    <span class="text-sm text-gray-600 group-hover:text-primary transition"><?php echo esc_html($kab); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Filter Kategori -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach($list_kategori as $kat): 
                                    $is_active = ($kategori == $kat);
                                    $cls = $is_active ? 'bg-primary text-white border-primary' : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-primary hover:text-primary';
                                ?>
                                <a href="?kat=<?php echo urlencode($kat); ?><?php echo $pencarian ? '&q='.$pencarian : ''; ?><?php echo $kabupaten ? '&kab='.$kabupaten : ''; ?>" 
                                   class="text-xs font-medium px-3 py-1.5 rounded-full border transition <?php echo $cls; ?>">
                                   <?php echo esc_html($kat); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Filter Urutan -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Urutkan</label>
                            <select name="sort" onchange="this.form.submit()" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2.5">
                                <option value="terbaru" <?php selected($urutan, 'terbaru'); ?>>Terbaru Ditambahkan</option>
                                <option value="terpopuler" <?php selected($urutan, 'terpopuler'); ?>>Rating Tertinggi</option>
                                <option value="termurah" <?php selected($urutan, 'termurah'); ?>>Harga Terendah</option>
                                <option value="termahal" <?php selected($urutan, 'termahal'); ?>>Harga Tertinggi</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-primary text-white font-bold py-2.5 rounded-lg hover:bg-green-700 transition shadow-lg shadow-green-200">
                            Terapkan Filter
                        </button>
                    </form>
                </div>
            </div>

            <!-- === GRID CONTENT === -->
            <div class="w-full lg:w-3/4">
                
                <!-- Result Count -->
                <div class="flex items-center justify-between mb-6">
                    <p class="text-gray-500 text-sm">Menampilkan <span class="font-bold text-gray-900"><?php echo $total_items; ?></span> destinasi wisata</p>
                    <div class="hidden md:flex gap-2 text-sm">
                        <span class="text-gray-400">Tampilan:</span>
                        <button class="text-primary"><i class="fas fa-th-large"></i></button>
                    </div>
                </div>

                <?php if ($wisata_list) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($wisata_list as $w) : 
                        $link_w = !empty($w->slug) ? home_url('/wisata/detail/' . $w->slug) : home_url('/detail-wisata/?id=' . $w->id);
                        $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/600x400?text=Wisata';
                        $rating = ($w->rating_avg > 0) ? $w->rating_avg : 'Baru';
                        $kab = !empty($w->kabupaten) ? $w->kabupaten : 'Indonesia';
                    ?>
                    <a href="<?php echo esc_url($link_w); ?>" class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition duration-300 flex flex-col h-full">
                        <!-- Image Wrapper -->
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700" loading="lazy">
                            
                            <!-- Badges -->
                            <div class="absolute top-3 left-3 flex gap-2">
                                <?php if(isset($w->kategori)): ?>
                                <span class="bg-black/60 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded-md">
                                    <?php echo esc_html($w->kategori); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Rating -->
                            <div class="absolute top-3 right-3 bg-white/95 backdrop-blur px-2 py-1 rounded-lg text-xs font-bold shadow-sm flex items-center gap-1">
                                <i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-5 flex flex-col flex-1">
                            <div class="mb-2 flex items-center gap-1 text-xs text-gray-400">
                                <i class="fas fa-map-marker-alt"></i>
                                <span class="truncate"><?php echo esc_html($kab); ?></span>
                            </div>
                            
                            <h3 class="font-bold text-gray-800 text-lg mb-2 leading-tight group-hover:text-primary transition line-clamp-2">
                                <?php echo esc_html($w->nama_wisata); ?>
                            </h3>
                            
                            <!-- Price & Action -->
                            <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-gray-400 uppercase font-bold block">Tiket Mulai</span>
                                    <span class="text-primary font-bold text-base">
                                        <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                                    </span>
                                </div>
                                <span class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition">
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
                        $active_cls = ($i == $page) ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary';
                        // Bangun URL Pagination
                        $query_params = $_GET;
                        $query_params['halaman'] = $i;
                        $page_url = '?' . http_build_query($query_params);
                    ?>
                    <a href="<?php echo esc_url($page_url); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border font-bold text-sm transition <?php echo $active_cls; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="text-center py-20 bg-white rounded-2xl border-2 border-dashed border-gray-200">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-search text-3xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Tidak ditemukan</h3>
                        <p class="text-gray-500 max-w-md mx-auto">Coba ubah kata kunci pencarian atau reset filter untuk melihat hasil lainnya.</p>
                        <a href="<?php echo home_url('/wisata'); ?>" class="inline-block mt-6 px-6 py-2 bg-white border border-gray-300 text-gray-700 font-bold rounded-full hover:bg-gray-50 transition">
                            Reset Filter
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar untuk filter */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #bbb; }
</style>

<?php get_footer(); ?>