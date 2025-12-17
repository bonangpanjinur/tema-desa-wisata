<?php
/**
 * Template Name: Arsip Wisata Modern Clean
 * Description: Layout full-width dengan filter drawer/modal dan desain kartu estetik yang konsisten.
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
$items_per_page = 12; 
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

<div class="bg-[#FAFAFA] min-h-screen font-sans text-gray-800 pb-20 relative overflow-x-hidden">
    
    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-green-50/50 to-transparent -z-10"></div>
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-50/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 -z-10"></div>

    <!-- === 1. TOP HEADER & SEARCH === -->
    <div class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Logo Area -->
                <div class="flex items-center gap-2 self-start md:self-center cursor-pointer" onclick="window.location.href='<?php echo home_url('/wisata'); ?>'">
                    <div class="w-8 h-8 bg-gradient-to-tr from-green-500 to-emerald-400 rounded-lg flex items-center justify-center text-white shadow-lg shadow-green-200">
                        <i class="fas fa-compass text-sm"></i>
                    </div>
                    <span class="font-bold text-lg tracking-tight">Wisata<span class="text-green-600">Desa</span></span>
                </div>

                <!-- Search & Filter Group -->
                <div class="w-full md:w-auto flex items-center gap-3 flex-1 md:justify-center max-w-3xl">
                    <form action="" method="GET" class="relative w-full group">
                        <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                               class="w-full pl-12 pr-4 py-3 bg-gray-50 hover:bg-white focus:bg-white border border-gray-200 hover:border-gray-300 focus:border-green-500 rounded-full transition-all outline-none text-sm shadow-sm focus:shadow-md focus:ring-4 focus:ring-green-50" 
                               placeholder="Cari destinasi, lokasi...">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-green-500 transition-colors"></i>
                        
                        <!-- Hidden Filters -->
                        <?php if($kabupaten): ?><input type="hidden" name="kab" value="<?php echo esc_attr($kabupaten); ?>"><?php endif; ?>
                        <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>
                        <?php if($max_harga): ?><input type="hidden" name="max_price" value="<?php echo esc_attr($max_harga); ?>"><?php endif; ?>
                        <?php if($urutan): ?><input type="hidden" name="sort" value="<?php echo esc_attr($urutan); ?>"><?php endif; ?>
                    </form>

                    <!-- Filter Trigger Button -->
                    <button type="button" id="open-filter" class="flex-none flex items-center gap-2 px-5 py-3 bg-white border border-gray-200 rounded-full text-sm font-bold text-gray-700 hover:bg-gray-50 hover:border-green-500 hover:text-green-600 transition-all shadow-sm active:scale-95">
                        <i class="fas fa-sliders-h"></i>
                        <span class="hidden sm:inline">Filter</span>
                        <?php if($kabupaten || $max_harga): ?>
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- === 2. CATEGORY NAV (SWIPEABLE) === -->
    <div class="border-b border-gray-100 bg-white/50">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-2 overflow-x-auto py-3 no-scrollbar mask-gradient">
                <a href="?kat=" class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold border transition-all duration-300 <?php echo $kategori == '' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-400 hover:text-gray-800'; ?>">
                    Semua
                </a>
                <?php foreach($list_kategori as $kat): 
                    $is_active = $kategori == $kat;
                ?>
                <a href="?kat=<?php echo urlencode($kat); ?>&q=<?php echo urlencode($pencarian); ?>" 
                   class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold border transition-all duration-300 <?php echo $is_active ? 'bg-green-500 text-white border-green-500 shadow-md shadow-green-200' : 'bg-white text-gray-500 border-gray-200 hover:border-green-500 hover:text-green-600'; ?>">
                    <?php echo esc_html($kat); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- === 3. CONTENT GRID === -->
    <div class="container mx-auto px-4 py-8">
        
        <!-- Stats & Active Filters -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800">
                <?php echo $pencarian ? 'Hasil pencarian: "'.esc_html($pencarian).'"' : 'Jelajahi Wisata'; ?>
                <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo $total_items; ?> ditemukan)</span>
            </h2>
            
            <?php if($kabupaten || $max_harga): ?>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-gray-400">Filter Aktif:</span>
                <?php if($kabupaten): ?>
                    <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded border border-blue-100 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($kabupaten); ?>
                        <a href="?kab=&q=<?php echo urlencode($pencarian); ?>&kat=<?php echo urlencode($kategori); ?>" class="hover:text-red-500"><i class="fas fa-times"></i></a>
                    </span>
                <?php endif; ?>
                <?php if($max_harga): ?>
                    <span class="px-2 py-1 bg-green-50 text-green-600 rounded border border-green-100 flex items-center gap-1">
                        <i class="fas fa-tag"></i> Max Rp <?php echo number_format($max_harga); ?>
                        <a href="?max_price=&q=<?php echo urlencode($pencarian); ?>&kat=<?php echo urlencode($kategori); ?>" class="hover:text-red-500"><i class="fas fa-times"></i></a>
                    </span>
                <?php endif; ?>
                <a href="?q=<?php echo urlencode($pencarian); ?>" class="text-red-500 hover:underline ml-2">Hapus Semua</a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($wisata_list) : ?>
        <!-- GRID LAYOUT -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($wisata_list as $w) : ?>
                
                <!-- PANGGIL PARENT DESIGN: Card Wisata -->
                <?php get_template_part('template-parts/card', 'wisata', array('item' => $w)); ?>

            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-12 flex justify-center gap-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): 
                $active = ($i == $page) ? 'bg-green-600 text-white shadow-lg shadow-green-200' : 'bg-white text-gray-600 border border-gray-200 hover:border-green-500 hover:text-green-600';
                $url = add_query_arg('halaman', $i);
            ?>
            <a href="<?php echo esc_url($url); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all duration-200 <?php echo $active; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20">
                <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6 animate-pulse">
                    <i class="far fa-map text-5xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Wisata Tidak Ditemukan</h3>
                <p class="text-gray-500 mt-2 mb-6">Coba ganti kata kunci atau reset filter pencarian Anda.</p>
                <a href="<?php echo home_url('/wisata'); ?>" class="px-6 py-2.5 bg-green-600 text-white rounded-full font-bold hover:bg-green-700 transition shadow-lg">
                    Lihat Semua Wisata
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- === FILTER DRAWER / MODAL === -->
<div id="filter-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0" id="filter-backdrop"></div>

    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none">
        <div class="pointer-events-auto w-screen max-w-md transform transition ease-in-out duration-300 translate-x-full" id="filter-panel">
            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                
                <!-- Header Drawer -->
                <div class="px-4 py-6 sm:px-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900" id="slide-over-title">Filter Wisata</h2>
                    <button type="button" id="close-filter" class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Form Filter -->
                <div class="relative mt-6 flex-1 px-4 sm:px-6">
                    <form action="" method="GET" id="drawer-form">
                        <input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>">
                        <input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>">

                        <!-- Section: Urutkan -->
                        <div class="mb-8">
                            <label class="text-sm font-bold text-gray-900 block mb-3">Urutkan Berdasarkan</label>
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Terbaru -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="terbaru" <?php checked($urutan, 'terbaru'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 text-center text-sm font-medium text-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 transition">
                                        ✨ Terbaru
                                    </div>
                                </label>
                                
                                <!-- Populer -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="terpopuler" <?php checked($urutan, 'terpopuler'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 text-center text-sm font-medium text-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 transition">
                                        🔥 Populer
                                    </div>
                                </label>

                                <!-- Termurah -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="termurah" <?php checked($urutan, 'termurah'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 text-center text-sm font-medium text-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 transition">
                                        💰 Termurah
                                    </div>
                                </label>
                                
                                <!-- Termahal -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="termahal" <?php checked($urutan, 'termahal'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 text-center text-sm font-medium text-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 transition">
                                        💎 Termahal
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Section: Budget -->
                        <div class="mb-8">
                            <label class="text-sm font-bold text-gray-900 block mb-3">Maksimal Harga Tiket</label>
                            <input type="range" min="0" max="100000" step="5000" value="<?php echo $max_harga > 0 ? $max_harga : 100000; ?>" 
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-green-500"
                                   oninput="document.getElementById('price-label').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(this.value)">
                            <div class="flex justify-between mt-2 text-xs font-bold text-gray-500">
                                <span>Gratis</span>
                                <span id="price-label" class="text-green-600">Rp <?php echo $max_harga > 0 ? number_format($max_harga,0,',','.') : '100.000+'; ?></span>
                            </div>
                            <input type="hidden" name="max_price" value="<?php echo esc_attr($max_harga); ?>" id="input-max-price">
                        </div>

                        <!-- Section: Lokasi -->
                        <div class="mb-8">
                            <label class="text-sm font-bold text-gray-900 block mb-3">Lokasi (Kabupaten)</label>
                            <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
                                <label class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition">
                                    <input type="radio" name="kab" value="" <?php checked($kabupaten, ''); ?> class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                                    <span class="ml-3 text-sm text-gray-700">Semua Lokasi</span>
                                </label>
                                <?php foreach($list_kabupaten as $kab): ?>
                                <label class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition">
                                    <input type="radio" name="kab" value="<?php echo esc_attr($kab); ?>" <?php checked($kabupaten, $kab); ?> class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                                    <span class="ml-3 text-sm text-gray-700"><?php echo esc_html($kab); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-6 border-t border-gray-100 flex gap-3">
                            <a href="<?php echo home_url('/wisata'); ?>" class="flex-1 py-3 text-center border border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition">
                                Reset
                            </a>
                            <button type="submit" class="flex-1 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 shadow-lg shadow-green-200 transition">
                                Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Utilities */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.mask-gradient { -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); }
/* Custom Scrollbar for Drawer */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Drawer Logic
    const drawer = document.getElementById('filter-drawer');
    const backdrop = document.getElementById('filter-backdrop');
    const panel = document.getElementById('filter-panel');
    const openBtn = document.getElementById('open-filter');
    const closeBtn = document.getElementById('close-filter');
    
    // Range Slider Logic Sync
    const rangeInput = document.querySelector('input[type="range"]');
    const hiddenInput = document.getElementById('input-max-price');
    if(rangeInput) {
        rangeInput.addEventListener('change', (e) => {
            hiddenInput.value = e.target.value;
        });
    }

    function openDrawer() {
        drawer.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('translate-x-full');
        }, 10);
    }

    function closeDrawer() {
        backdrop.classList.add('opacity-0');
        panel.classList.add('translate-x-full');
        setTimeout(() => {
            drawer.classList.add('hidden');
        }, 300);
    }

    if(openBtn) openBtn.addEventListener('click', openDrawer);
    if(closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if(backdrop) backdrop.addEventListener('click', closeDrawer);
});
</script>

<?php get_footer(); ?>