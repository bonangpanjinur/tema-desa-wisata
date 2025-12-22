<?php
/**
 * Template Name: Arsip Produk Modern Clean
 * Description: Layout full-width dengan filter drawer, search centered, dan card template part untuk Produk.
 */

get_header();

global $wpdb;
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

// --- 1. PARAMETER FILTER ---
$pencarian  = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kategori   = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$kondisi    = isset($_GET['kondisi']) ? sanitize_text_field($_GET['kondisi']) : '';
$urutan     = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';
$max_harga  = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;

// --- 2. QUERY BUILDER ---
// Select data produk + info pedagang & desa untuk keperluan card
$sql = "SELECT p.*, t.nama_toko, t.kabupaten_nama as kabupaten, d.nama_desa 
        FROM $table_produk p 
        JOIN $table_pedagang t ON p.id_pedagang = t.id 
        LEFT JOIN $table_desa d ON t.id_desa = d.id 
        WHERE p.status = 'aktif'";

if ($pencarian) {
    $sql .= $wpdb->prepare(" AND (p.nama_produk LIKE %s OR p.deskripsi LIKE %s)", '%' . $pencarian . '%', '%' . $pencarian . '%');
}
if ($kategori) {
    $sql .= $wpdb->prepare(" AND p.kategori = %s", $kategori);
}
if ($kondisi) {
    $sql .= $wpdb->prepare(" AND p.kondisi = %s", $kondisi);
}
if ($max_harga > 0) {
    $sql .= $wpdb->prepare(" AND p.harga <= %d", $max_harga);
}

// Sorting
switch ($urutan) {
    case 'termurah': $sql .= " ORDER BY p.harga ASC"; break;
    case 'termahal': $sql .= " ORDER BY p.harga DESC"; break;
    case 'terpopuler': $sql .= " ORDER BY p.terjual DESC"; break; // Sorting by sales
    default: $sql .= " ORDER BY p.created_at DESC"; break; // Terbaru
}

// Pagination
$items_per_page = 12; 
$page = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
$offset = ($page - 1) * $items_per_page;

// Count Total
$count_sql = str_replace("SELECT p.*, t.nama_toko, t.kabupaten_nama as kabupaten, d.nama_desa", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

// Fetch Data
$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$produk_list = $wpdb->get_results($sql);

// Data Filter Dinamis (Kategori Produk)
$list_kategori = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE status='aktif' AND kategori != '' ORDER BY kategori ASC");
if(empty($list_kategori)) $list_kategori = ['Makanan', 'Kerajinan', 'Fashion', 'Pertanian']; // Fallback
?>

<div class="bg-[#FAFAFA] min-h-screen font-sans text-gray-800 pb-20 relative overflow-x-hidden">
    
    <!-- Background Decor (Subtle - Blue theme for Product) -->
    <div class="absolute top-0 left-0 w-full h-[300px] bg-gradient-to-b from-blue-50/40 to-transparent -z-10"></div>

    <!-- === 1. TOP HEADER & SEARCH (CENTERED & CLEAN) === -->
    <div class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300 shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <!-- Centered Layout -->
            <div class="flex justify-center w-full">
                <div class="w-full max-w-3xl flex items-center gap-3">
                    
                    <!-- Search Form Utama -->
                    <form action="" method="GET" class="relative w-full group shadow-sm rounded-full flex-grow">
                        <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                               class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 hover:border-gray-300 focus:border-blue-500 rounded-full transition-all outline-none text-sm focus:ring-4 focus:ring-blue-50/50" 
                               placeholder="Cari produk lokal...">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                        
                        <!-- Hidden Filters Persistence -->
                        <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>
                        <?php if($kondisi): ?><input type="hidden" name="kondisi" value="<?php echo esc_attr($kondisi); ?>"><?php endif; ?>
                        <?php if($max_harga): ?><input type="hidden" name="max_price" value="<?php echo esc_attr($max_harga); ?>"><?php endif; ?>
                        <?php if($urutan): ?><input type="hidden" name="sort" value="<?php echo esc_attr($urutan); ?>"><?php endif; ?>
                    </form>

                    <!-- Filter Trigger Button -->
                    <button type="button" id="open-filter" class="flex-none flex items-center gap-2 px-5 py-3 bg-white border border-gray-200 rounded-full text-sm font-bold text-gray-700 hover:bg-gray-50 hover:border-blue-500 hover:text-blue-600 transition-all shadow-sm active:scale-95 relative">
                        <i class="fas fa-sliders-h"></i>
                        <span class="hidden sm:inline">Filter</span>
                        <?php if($kondisi || $max_harga > 0 || $urutan !== 'terbaru'): ?>
                            <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                        <?php endif; ?>
                    </button>

                </div>
            </div>
        </div>
    </div>

    <!-- === 2. CATEGORY NAV (SWIPEABLE) === -->
    <div class="border-b border-gray-100 bg-white/50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-start md:justify-center gap-2 overflow-x-auto py-3 no-scrollbar mask-gradient">
                <?php 
                // Helper function untuk URL kategori
                function get_prod_cat_url($cat_slug) {
                    $params = $_GET;
                    $params['kat'] = $cat_slug;
                    if(isset($params['halaman'])) unset($params['halaman']); 
                    return '?' . http_build_query($params);
                }
                ?>

                <!-- Link Semua -->
                <a href="<?php echo esc_url(get_prod_cat_url('')); ?>" 
                   class="category-filter-link shrink-0 px-5 py-2 rounded-full text-xs font-bold border transition-all duration-300 <?php echo $kategori == '' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-400 hover:text-gray-800'; ?>">
                    Semua
                </a>

                <!-- Loop Kategori -->
                <?php foreach($list_kategori as $kat): 
                    $is_active = ($kategori == $kat);
                ?>
                <a href="<?php echo esc_url(get_prod_cat_url($kat)); ?>" 
                   class="category-filter-link shrink-0 px-5 py-2 rounded-full text-xs font-bold border transition-all duration-300 <?php echo $is_active ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-200' : 'bg-white text-gray-500 border-gray-200 hover:border-blue-500 hover:text-blue-600'; ?>">
                    <?php echo esc_html($kat); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- === 3. CONTENT GRID === -->
    <div id="produk-grid-container" class="container mx-auto px-4 py-8 transition-opacity duration-300 min-h-[400px]">
        
        <!-- Stats & Active Filters Bar -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8 border-b border-gray-100 pb-4">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <?php if($pencarian): ?>
                    Hasil: "<?php echo esc_html($pencarian); ?>"
                <?php else: ?>
                    Produk Unggulan
                <?php endif; ?>
                <span class="text-xs font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full border border-gray-200"><?php echo $total_items; ?></span>
            </h2>
            
            <!-- Active Chips -->
            <?php if($kondisi || $max_harga > 0 || $kategori): ?>
            <div class="flex flex-wrap items-center gap-2 text-xs w-full md:w-auto">
                <?php if($kategori): ?>
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full border border-gray-200 flex items-center gap-1 group">
                        <span>Kategori: <b><?php echo esc_html($kategori); ?></b></span>
                        <a href="<?php echo esc_url(get_prod_cat_url('')); ?>" class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-gray-300 transition"><i class="fas fa-times"></i></a>
                    </span>
                <?php endif; ?>

                <?php if($kondisi): 
                    $params_kond = $_GET; unset($params_kond['kondisi']); $url_kond = '?' . http_build_query($params_kond);
                ?>
                    <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-full border border-purple-100 flex items-center gap-1">
                        <i class="fas fa-box"></i> <?php echo ucfirst(esc_html($kondisi)); ?>
                        <a href="<?php echo esc_url($url_kond); ?>" class="hover:text-red-500 ml-1"><i class="fas fa-times"></i></a>
                    </span>
                <?php endif; ?>

                <?php if($max_harga > 0): 
                     $params_prc = $_GET; unset($params_prc['max_price']); $url_prc = '?' . http_build_query($params_prc);
                ?>
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full border border-blue-100 flex items-center gap-1">
                        <i class="fas fa-tag"></i> < <?php echo number_format($max_harga, 0, ',', '.'); ?>
                        <a href="<?php echo esc_url($url_prc); ?>" class="hover:text-red-500 ml-1"><i class="fas fa-times"></i></a>
                    </span>
                <?php endif; ?>
                
                <a href="?post_type=dw_produk" class="text-gray-400 hover:text-red-500 ml-auto md:ml-2 transition-colors font-medium border-b border-transparent hover:border-red-500">Reset Semua</a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($produk_list) : ?>
        
        <!-- GRID LAYOUT (RESPONSIVE) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($produk_list as $p) : ?>
                <!-- Memanggil Template Part Card Produk -->
                <?php get_template_part('template-parts/card-produk', null, array('data' => $p)); ?>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-16 flex justify-center gap-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): 
                $active = ($i == $page) ? 'bg-blue-600 text-white shadow-lg shadow-blue-200 ring-2 ring-blue-600 ring-offset-2' : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-500 hover:text-blue-600';
                $params_page = $_GET;
                $params_page['halaman'] = $i;
                $url = '?' . http_build_query($params_page);
            ?>
            <a href="<?php echo esc_url($url); ?>" class="w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all duration-200 <?php echo $active; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-dashed border-gray-200">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-box-open text-4xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Tidak ada produk ditemukan</h3>
                <p class="text-gray-500 text-sm mt-1 mb-6 text-center max-w-xs">Kami tidak dapat menemukan produk yang cocok dengan filter Anda.</p>
                <a href="?" class="px-6 py-2.5 bg-gray-900 text-white rounded-full text-sm font-bold hover:bg-gray-800 transition shadow-lg">
                    Reset Filter
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- === FILTER DRAWER / MODAL (SIDEBAR) === -->
<div id="filter-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="filter-backdrop"></div>

    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none">
        <div class="pointer-events-auto w-screen max-w-sm transform transition ease-in-out duration-300 translate-x-full" id="filter-panel">
            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-2xl">
                
                <!-- Header Drawer -->
                <div class="px-5 py-6 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10">
                    <h2 class="text-lg font-bold text-gray-900">Filter Produk</h2>
                    <button type="button" id="close-filter" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Form Filter -->
                <div class="relative flex-1 px-5 py-6">
                    <form action="" method="GET" id="drawer-form">
                        <input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>">
                        <input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>">

                        <!-- Section: Urutkan -->
                        <div class="mb-8">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-3">Urutkan</label>
                            <div class="space-y-2">
                                <?php 
                                $sort_options = [
                                    'terbaru' => '✨ Terbaru Ditambahkan',
                                    'terpopuler' => '🔥 Paling Laris',
                                    'termurah' => '💰 Harga Terendah',
                                    'termahal' => '💎 Harga Tertinggi'
                                ];
                                foreach($sort_options as $val => $label):
                                ?>
                                <label class="flex items-center p-3 rounded-xl border border-gray-100 cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition-all">
                                    <input type="radio" name="sort" value="<?php echo $val; ?>" <?php checked($urutan, $val); ?> class="peer w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="ml-3 text-sm font-medium text-gray-700 peer-checked:text-blue-800"><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Section: Budget -->
                        <div class="mb-8">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-3">Maksimal Harga</label>
                            <input type="range" min="0" max="500000" step="10000" value="<?php echo $max_harga > 0 ? $max_harga : 500000; ?>" 
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                   id="range-slider">
                            <div class="flex justify-between mt-3 text-sm font-bold">
                                <span class="text-gray-400">Rp 0</span>
                                <span id="price-label" class="text-blue-600">
                                    <?php echo $max_harga > 0 ? 'Rp ' . number_format($max_harga,0,',','.') : 'Rp 500.000+'; ?>
                                </span>
                            </div>
                            <input type="hidden" name="max_price" value="<?php echo esc_attr($max_harga); ?>" id="input-max-price">
                        </div>

                        <!-- Section: Kondisi -->
                        <div class="mb-8">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-3">Kondisi</label>
                            <div class="space-y-2">
                                <label class="flex items-center p-3 rounded-xl border border-gray-100 cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition-all">
                                    <input type="radio" name="kondisi" value="" <?php checked($kondisi, ''); ?> class="peer w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="ml-3 text-sm font-medium text-gray-700">Semua Kondisi</span>
                                </label>
                                <label class="flex items-center p-3 rounded-xl border border-gray-100 cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition-all">
                                    <input type="radio" name="kondisi" value="baru" <?php checked($kondisi, 'baru'); ?> class="peer w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="ml-3 text-sm font-medium text-gray-700">Baru</span>
                                </label>
                                <label class="flex items-center p-3 rounded-xl border border-gray-100 cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition-all">
                                    <input type="radio" name="kondisi" value="bekas" <?php checked($kondisi, 'bekas'); ?> class="peer w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="ml-3 text-sm font-medium text-gray-700">Bekas</span>
                                </label>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Footer Drawer -->
                <div class="p-5 border-t border-gray-100 bg-gray-50 sticky bottom-0 z-10">
                    <div class="flex gap-3">
                        <a href="?" class="flex-1 py-3 text-center border border-gray-300 rounded-xl text-sm font-bold text-gray-600 hover:bg-white transition">
                            Reset
                        </a>
                        <button onclick="document.getElementById('drawer-form').submit()" class="flex-1 py-3 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                            Terapkan
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* Utilities */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.mask-gradient { -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent); }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Drawer Logic
    const drawer = document.getElementById('filter-drawer');
    const backdrop = document.getElementById('filter-backdrop');
    const panel = document.getElementById('filter-panel');
    const openBtn = document.getElementById('open-filter');
    const closeBtn = document.getElementById('close-filter');
    
    // Slider Logic Sync
    const rangeInput = document.getElementById('range-slider');
    const hiddenInput = document.getElementById('input-max-price');
    const priceLabel = document.getElementById('price-label');

    if(rangeInput) {
        rangeInput.addEventListener('input', (e) => {
            const val = e.target.value;
            hiddenInput.value = val;
            priceLabel.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
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

    // --- ENHANCED AJAX FILTER ---
    const gridContainer = document.getElementById('produk-grid-container');
    const filterLinks = document.querySelectorAll('.category-filter-link');

    filterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;

            // 1. Visual Feedback
            gridContainer.style.opacity = '0.4'; 
            
            // 2. Update UI Class
            filterLinks.forEach(l => {
                l.className = 'category-filter-link shrink-0 px-5 py-2 rounded-full text-xs font-bold border transition-all duration-300 bg-white text-gray-500 border-gray-200 hover:border-blue-500 hover:text-blue-600';
            });
            this.className = 'category-filter-link shrink-0 px-5 py-2 rounded-full text-xs font-bold border transition-all duration-300 bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-200';

            // 3. Fetch Data
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('produk-grid-container');
                    
                    if (newContent) {
                        gridContainer.innerHTML = newContent.innerHTML;
                        window.history.pushState({}, '', url);
                        
                        // Sync Hidden Form Input
                        const urlParams = new URLSearchParams(url.split('?')[1]);
                        const katVal = urlParams.get('kat') || '';
                        const inputKat = document.querySelector('input[name="kat"]');
                        if(inputKat) inputKat.value = katVal;
                    }
                    gridContainer.style.opacity = '1';
                })
                .catch(err => {
                    console.error('Filter Error:', err);
                    window.location.href = url;
                });
        });
    });
});
</script>

<?php get_footer(); ?>