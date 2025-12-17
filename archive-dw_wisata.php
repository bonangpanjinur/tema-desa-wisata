<?php
/**
 * Template Name: Arsip Wisata Minimalis
 * Description: Katalog wisata dengan desain clean, modern, dan filter UX friendly.
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

// --- 1. PARAMETER FILTER (Aman & Bersih) ---
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
// Kategori (Pastikan kolom kategori ada di tabel, jika tidak gunakan dummy logic)
if ($kategori) {
    $sql .= $wpdb->prepare(" AND w.kategori = %s", $kategori);
}

// Sorting Logic
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

// --- 3. DATA FILTER DINAMIS ---
$list_kabupaten = $wpdb->get_col("SELECT DISTINCT kabupaten FROM $table_desa WHERE status='aktif' AND kabupaten != '' ORDER BY kabupaten ASC");
// Ambil kategori unik dari database (Jika kolom kategori ada)
$list_kategori = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_wisata WHERE status='aktif' AND kategori != '' ORDER BY kategori ASC");
// Fallback jika database kosong
if(empty($list_kategori)) {
    $list_kategori = ['Alam', 'Budaya', 'Religi', 'Kuliner', 'Edukasi'];
}
?>

<!-- === MAIN LAYOUT === -->
<div class="bg-[#F8F9FA] min-h-screen font-sans text-gray-800">
    
    <!-- HEADER SEARCH (Minimalis) -->
    <div class="bg-white border-b border-gray-100 sticky top-0 z-40 shadow-sm">
        <div class="container mx-auto px-4 py-4 md:py-5">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <!-- Logo / Title -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center text-white shadow-green-200 shadow-lg">
                        <i class="fas fa-map-marked-alt text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 leading-none">Jelajah Desa</h1>
                        <span class="text-xs text-gray-500">Temukan destinasi terbaik</span>
                    </div>
                </div>

                <!-- Search Bar (Lebar & Elegan) -->
                <form action="" method="GET" class="w-full md:max-w-xl relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-green-500 transition"></i>
                    <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                           class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-full focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-50 transition-all text-sm outline-none placeholder-gray-400" 
                           placeholder="Cari wisata, lokasi, atau aktivitas...">
                    <!-- Hidden inputs untuk menjaga filter -->
                    <?php if($kabupaten): ?><input type="hidden" name="kab" value="<?php echo esc_attr($kabupaten); ?>"><?php endif; ?>
                    <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>
                </form>
                
                <!-- Mobile Filter Toggle -->
                <button id="mobile-filter-btn" class="md:hidden w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-full text-gray-600 active:bg-gray-50">
                    <i class="fas fa-sliders-h"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- CONTENT BODY -->
    <div class="container mx-auto px-4 py-8 md:py-12">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- === SIDEBAR FILTER (Sticky Desktop) === -->
            <aside class="w-full lg:w-1/4">
                <div id="filter-sidebar" class="hidden lg:block bg-white p-6 rounded-2xl border border-gray-100 shadow-sm sticky top-28 transition-all">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-900 text-lg">Filter</h3>
                        <?php if($pencarian || $kabupaten || $kategori): ?>
                            <a href="<?php echo home_url('/wisata'); ?>" class="text-xs font-semibold text-red-500 hover:text-red-600 hover:bg-red-50 px-2 py-1 rounded transition">
                                Reset Semua
                            </a>
                        <?php endif; ?>
                    </div>

                    <form action="" method="GET" id="filter-form">
                        <?php if($pencarian): ?><input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>"><?php endif; ?>

                        <!-- Filter Urutan -->
                        <div class="mb-8">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Urutkan</label>
                            <div class="relative">
                                <select name="sort" onchange="this.form.submit()" class="w-full appearance-none bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-4 py-3 focus:outline-none focus:border-green-500 focus:bg-white transition cursor-pointer">
                                    <option value="terbaru" <?php selected($urutan, 'terbaru'); ?>>✨ Terbaru Ditambahkan</option>
                                    <option value="terpopuler" <?php selected($urutan, 'terpopuler'); ?>>🔥 Paling Populer</option>
                                    <option value="termurah" <?php selected($urutan, 'termurah'); ?>>💰 Harga Terendah</option>
                                    <option value="termahal" <?php selected($urutan, 'termahal'); ?>>💎 Harga Tertinggi</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>

                        <!-- Filter Kategori (Pill Style) -->
                        <div class="mb-8">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Kategori</label>
                            <div class="flex flex-wrap gap-2">
                                <!-- Option All -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="kat" value="" <?php checked($kategori, ''); ?> onchange="this.form.submit()" class="peer sr-only">
                                    <span class="block px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 bg-white text-gray-600 peer-checked:bg-green-500 peer-checked:text-white peer-checked:border-green-500 hover:border-green-300 transition select-none">
                                        Semua
                                    </span>
                                </label>
                                <?php foreach($list_kategori as $kat): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="kat" value="<?php echo esc_attr($kat); ?>" <?php checked($kategori, $kat); ?> onchange="this.form.submit()" class="peer sr-only">
                                    <span class="block px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 bg-white text-gray-600 peer-checked:bg-green-500 peer-checked:text-white peer-checked:border-green-500 hover:border-green-300 transition select-none">
                                        <?php echo esc_html($kat); ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Filter Lokasi (List Style) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Lokasi (Kabupaten)</label>
                            <div class="space-y-1 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                                <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 transition group">
                                    <div class="relative flex items-center">
                                        <input type="radio" name="kab" value="" <?php checked($kabupaten, ''); ?> onchange="this.form.submit()" class="peer sr-only">
                                        <div class="w-4 h-4 border-2 border-gray-300 rounded-full peer-checked:border-green-500 peer-checked:bg-green-500 transition"></div>
                                    </div>
                                    <span class="text-sm text-gray-600 group-hover:text-green-600 transition">Semua Lokasi</span>
                                </label>
                                <?php foreach($list_kabupaten as $kab): ?>
                                <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 transition group">
                                    <div class="relative flex items-center">
                                        <input type="radio" name="kab" value="<?php echo esc_attr($kab); ?>" <?php checked($kabupaten, $kab); ?> onchange="this.form.submit()" class="peer sr-only">
                                        <div class="w-4 h-4 border-2 border-gray-300 rounded-full peer-checked:border-green-500 peer-checked:bg-green-500 transition"></div>
                                    </div>
                                    <span class="text-sm text-gray-600 group-hover:text-green-600 transition"><?php echo esc_html($kab); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </form>
                </div>
            </aside>

            <!-- === MAIN GRID === -->
            <main class="w-full lg:w-3/4">
                
                <!-- Results Header -->
                <div class="flex items-end justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Destinasi Wisata</h2>
                        <p class="text-sm text-gray-500 mt-1">Menampilkan <span class="font-bold text-gray-900"><?php echo $total_items; ?></span> tempat menarik</p>
                    </div>
                </div>

                <?php if ($wisata_list) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($wisata_list as $w) : 
                        $link_w = !empty($w->slug) ? home_url('/wisata/detail/' . $w->slug) : home_url('/detail-wisata/?id=' . $w->id);
                        $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/600x400?text=Wisata';
                        $rating = ($w->rating_avg > 0) ? $w->rating_avg : 'Baru';
                        $lokasi = !empty($w->kabupaten) ? $w->kabupaten : 'Indonesia';
                    ?>
                    
                    <!-- CARD WISATA (Minimalist Clean) -->
                    <a href="<?php echo esc_url($link_w); ?>" class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-xl hover:shadow-gray-200/50 hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">
                        
                        <!-- Image Area -->
                        <div class="relative h-56 overflow-hidden bg-gray-100">
                            <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="<?php echo esc_attr($w->nama_wisata); ?>" loading="lazy">
                            
                            <!-- Badges Overlay -->
                            <div class="absolute top-3 left-3 flex flex-wrap gap-2">
                                <?php if(isset($w->kategori)): ?>
                                <span class="bg-white/90 backdrop-blur-sm text-gray-800 text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-white/50">
                                    <?php echo esc_html($w->kategori); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Rating Overlay -->
                            <div class="absolute bottom-3 left-3 bg-black/60 backdrop-blur-md text-white text-xs font-bold px-2 py-1 rounded-lg flex items-center gap-1">
                                <i class="fas fa-star text-yellow-400 text-[10px]"></i> <?php echo $rating; ?>
                            </div>
                        </div>

                        <!-- Content Area -->
                        <div class="p-5 flex flex-col flex-1">
                            <!-- Location -->
                            <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-2">
                                <i class="fas fa-map-marker-alt text-green-500"></i>
                                <span class="truncate"><?php echo esc_html($lokasi); ?></span>
                            </div>

                            <!-- Title -->
                            <h3 class="font-bold text-gray-900 text-lg leading-snug mb-2 group-hover:text-green-600 transition line-clamp-2">
                                <?php echo esc_html($w->nama_wisata); ?>
                            </h3>

                            <!-- Price & Action -->
                            <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                                <div>
                                    <span class="block text-[10px] text-gray-400 uppercase font-bold tracking-wide">Tiket Masuk</span>
                                    <span class="text-green-600 font-bold text-base">
                                        <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                                    </span>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-green-500 group-hover:text-white transition shadow-sm">
                                    <i class="fas fa-arrow-right text-xs transform group-hover:translate-x-0.5 transition"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination (Clean) -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-12 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): 
                        $active_cls = ($i == $page) ? 'bg-green-500 text-white border-green-500 shadow-md shadow-green-200' : 'bg-white text-gray-600 border-gray-200 hover:border-green-500 hover:text-green-500';
                        $query_params = $_GET;
                        $query_params['halaman'] = $i;
                        $page_url = '?' . http_build_query($query_params);
                    ?>
                    <a href="<?php echo esc_url($page_url); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border font-bold text-sm transition-all <?php echo $active_cls; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border-2 border-dashed border-gray-200 text-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="far fa-compass text-3xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada wisata ditemukan</h3>
                        <p class="text-gray-500 max-w-xs mx-auto mb-6 text-sm">Coba ubah kata kunci pencarian atau sesuaikan filter Anda.</p>
                        <a href="<?php echo home_url('/wisata'); ?>" class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-full hover:bg-gray-50 hover:text-green-600 transition text-sm">
                            Reset Filter
                        </a>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar for Sidebar */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>

<script>
// Mobile Filter Toggle Logic (Inline for simplicity, can be moved to main js)
document.addEventListener('DOMContentLoaded', () => {
    const mobileBtn = document.getElementById('mobile-filter-btn');
    const sidebar = document.getElementById('filter-sidebar');
    
    if(mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            // Smooth scroll to filter if opened
            if(!sidebar.classList.contains('hidden')) {
                sidebar.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        });
    }
});
</script>

<?php get_footer(); ?>