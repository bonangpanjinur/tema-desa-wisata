<?php
/**
 * Template Name: Arsip Produk Modern (Desain Wisata Style)
 * Description: Layout full-width dengan filter drawer, kategori swipeable, dan kartu produk standar + AJAX.
 */

// --- 1. LOGIKA UTAMA (Dijalankan sebelum Header) ---
global $wpdb;
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

// Parameter Filter
$pencarian  = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kategori   = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$kabupaten  = isset($_GET['kab']) ? sanitize_text_field($_GET['kab']) : ''; 
$max_harga  = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;
$urutan     = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';
$is_ajax    = isset($_GET['ajax_update']) && $_GET['ajax_update'] == '1'; // Cek mode AJAX

// Query Builder
$sql = "SELECT p.*, pd.nama_toko, pd.slug_toko, d.kabupaten, d.nama_desa 
        FROM $table_produk p 
        LEFT JOIN $table_pedagang pd ON p.id_pedagang = pd.id
        LEFT JOIN $table_desa d ON pd.id_desa = d.id
        WHERE p.status = 'aktif' AND pd.status_akun = 'aktif'";

if ($pencarian) {
    $sql .= $wpdb->prepare(" AND (p.nama_produk LIKE %s OR p.deskripsi LIKE %s)", '%' . $pencarian . '%', '%' . $pencarian . '%');
}
if ($kategori) {
    $sql .= $wpdb->prepare(" AND p.kategori = %s", $kategori);
}
if ($kabupaten) {
    $sql .= $wpdb->prepare(" AND d.kabupaten = %s", $kabupaten);
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
$items_per_page = 12; 
$page           = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
$offset         = ($page - 1) * $items_per_page;

// Count Total (Hanya hitung jika bukan AJAX pagination biasa, atau simpan di cache jika perlu optimalisasi)
$count_sql = str_replace("SELECT p.*, pd.nama_toko, pd.slug_toko, d.kabupaten, d.nama_desa", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

// Fetch Data
$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$produk_list = $wpdb->get_results($sql);

// Data Filter Dinamis (Helper untuk Drawer)
$list_kabupaten = $wpdb->get_col("SELECT DISTINCT kabupaten FROM $table_desa WHERE status='aktif' AND kabupaten != '' ORDER BY kabupaten ASC");
$list_kategori_db = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE status='aktif' AND kategori != '' ORDER BY kategori ASC");
$list_kategori = !empty($list_kategori_db) ? $list_kategori_db : ['Makanan', 'Minuman', 'Kerajinan', 'Fashion', 'Pertanian'];

// --- 2. AJAX HANDLER (Jika request AJAX, output hanya grid produk lalu STOP) ---
if ($is_ajax) {
    if ($produk_list) {
        // Output Grid
        echo '<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6 animate-fade-in">';
        foreach ($produk_list as $p) {
            get_template_part('template-parts/card', 'produk', array('item' => $p));
        }
        echo '</div>';

        // Output Pagination
        if ($total_pages > 1) {
            echo '<div class="mt-12 flex justify-center gap-2 pagination-ajax">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = ($i == $page) ? 'bg-orange-600 text-white shadow-lg shadow-orange-200 pointer-events-none' : 'bg-white text-gray-600 border border-gray-200 hover:border-orange-500 hover:text-orange-600';
                // Kita perlu URL dasar tanpa parameter 'halaman' lama untuk membangun link baru
                $url_params = $_GET;
                $url_params['halaman'] = $i;
                unset($url_params['ajax_update']); // Jangan masukkan flag ajax di link href
                $url = '?' . http_build_query($url_params);
                
                echo '<a href="' . esc_url($url) . '" class="w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all duration-200 ' . $active . '">' . $i . '</a>';
            }
            echo '</div>';
        }
    } else {
        // Empty State
        echo '<div class="flex flex-col items-center justify-center py-20 animate-fade-in">
                <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-box-open text-5xl text-gray-300"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Tidak ditemukan produk</h3>
                <p class="text-gray-500 mt-2">Coba kategori lain atau reset filter.</p>
              </div>';
    }
    
    // Update Judul Hasil Pencarian via JSON header khusus atau script parsing nanti
    // Untuk simplifikasi, kita kirim HTML grid saja.
    exit; // STOP eksekusi halaman di sini agar header/footer tidak termuat ulang
}

// --- 3. HALAMAN NORMAL (Header & Layout) ---
get_header(); 
?>

<div class="bg-[#FAFAFA] min-h-screen font-sans text-gray-800 pb-20 relative overflow-x-hidden">
    
    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-orange-50/50 to-transparent -z-10"></div>
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-yellow-50/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 -z-10"></div>

    <!-- === TOP HEADER & SEARCH === -->
    <div class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Logo Area -->
                <div class="flex items-center gap-2 self-start md:self-center cursor-pointer" onclick="window.location.href='<?php echo home_url('/produk'); ?>'">
                    <div class="w-8 h-8 bg-gradient-to-tr from-orange-500 to-amber-400 rounded-lg flex items-center justify-center text-white shadow-lg shadow-orange-200">
                        <i class="fas fa-box-open text-sm"></i>
                    </div>
                    <span class="font-bold text-lg tracking-tight"><?php echo get_bloginfo('name'); ?></span>
                </div>

                <!-- Search & Filter Group -->
                <div class="w-full md:w-auto flex items-center gap-3 flex-1 md:justify-center max-w-3xl">
                    <form action="" method="GET" class="relative w-full group">
                        <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                               class="w-full pl-12 pr-4 py-3 bg-gray-50 hover:bg-white focus:bg-white border border-gray-200 hover:border-gray-300 focus:border-orange-500 rounded-full transition-all outline-none text-sm shadow-sm focus:shadow-md focus:ring-4 focus:ring-orange-50" 
                               placeholder="Cari produk desa...">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                        
                        <!-- Hidden Filter States -->
                        <?php if($kabupaten): ?><input type="hidden" name="kab" value="<?php echo esc_attr($kabupaten); ?>"><?php endif; ?>
                        <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>
                        <?php if($max_harga): ?><input type="hidden" name="max_price" value="<?php echo esc_attr($max_harga); ?>"><?php endif; ?>
                        <?php if($urutan): ?><input type="hidden" name="sort" value="<?php echo esc_attr($urutan); ?>"><?php endif; ?>
                    </form>

                    <!-- Filter Trigger Button -->
                    <button type="button" id="open-filter" class="flex-none flex items-center gap-2 px-5 py-3 bg-white border border-gray-200 rounded-full text-sm font-bold text-gray-700 hover:bg-gray-50 hover:border-orange-500 hover:text-orange-600 transition-all shadow-sm active:scale-95">
                        <i class="fas fa-sliders-h"></i>
                        <span class="hidden sm:inline">Filter</span>
                        <?php if($kabupaten || $max_harga): ?>
                            <span class="flex w-2 h-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- === CATEGORY NAV (SWIPEABLE & AJAX TRIGGER) === -->
    <div class="border-b border-gray-100 bg-white/50">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-2 overflow-x-auto py-3 no-scrollbar mask-gradient" id="category-wrapper">
                
                <!-- Badge 'Semua' -->
                <a href="?kat=" 
                   class="category-badge shrink-0 px-4 py-1.5 rounded-full text-xs font-bold border transition-all duration-300 cursor-pointer <?php echo $kategori == '' ? 'bg-gray-900 text-white border-gray-900 shadow-md active' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-400 hover:text-gray-800'; ?>"
                   data-kat="">
                    Semua
                </a>

                <?php foreach($list_kategori as $kat): 
                    $is_active = $kategori == $kat;
                ?>
                <a href="?kat=<?php echo urlencode($kat); ?>&q=<?php echo urlencode($pencarian); ?>" 
                   class="category-badge shrink-0 px-4 py-1.5 rounded-full text-xs font-bold border transition-all duration-300 cursor-pointer <?php echo $is_active ? 'bg-orange-500 text-white border-orange-500 shadow-md shadow-orange-200 active' : 'bg-white text-gray-500 border-gray-200 hover:border-orange-500 hover:text-orange-600'; ?>"
                   data-kat="<?php echo esc_attr($kat); ?>">
                    <?php echo esc_html($kat); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- === CONTENT GRID CONTAINER === -->
    <div class="container mx-auto px-4 py-8">
        
        <!-- Stats & Active Filters -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800">
                <span id="page-title-text"><?php echo $pencarian ? 'Hasil pencarian: "'.esc_html($pencarian).'"' : 'Jelajahi Produk Desa'; ?></span>
                <span id="page-count-text" class="text-sm font-normal text-gray-500 ml-2">(<?php echo $total_items; ?> ditemukan)</span>
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

        <!-- AREA DINAMIS: INI YANG AKAN DI-UPDATE AJAX -->
        <div id="product-results-area" class="min-h-[300px] transition-opacity duration-200">
            <?php if ($produk_list) : ?>
                <!-- Grid Layout -->
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                    <?php foreach ($produk_list as $p) : ?>
                        <?php get_template_part('template-parts/card', 'produk', array('item' => $p)); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-12 flex justify-center gap-2 pagination-ajax">
                    <?php for ($i = 1; $i <= $total_pages; $i++): 
                        $active = ($i == $page) ? 'bg-orange-600 text-white shadow-lg shadow-orange-200' : 'bg-white text-gray-600 border border-gray-200 hover:border-orange-500 hover:text-orange-600';
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
                        <i class="fas fa-box-open text-5xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Tidak ditemukan produk</h3>
                    <p class="text-gray-500 mt-2 mb-6">Coba ganti kata kunci atau reset filter pencarian Anda.</p>
                    <a href="<?php echo home_url('/produk'); ?>" class="px-6 py-2.5 bg-gray-900 text-white rounded-full font-bold hover:bg-gray-800 transition shadow-lg">
                        Lihat Semua Produk
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- === FILTER DRAWER / MODAL === -->
<div id="filter-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0" id="filter-backdrop"></div>
    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none">
        <div class="pointer-events-auto w-screen max-w-md transform transition ease-in-out duration-300 translate-x-full" id="filter-panel">
            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                <!-- Header Drawer -->
                <div class="px-4 py-6 sm:px-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Filter Produk</h2>
                    <button type="button" id="close-filter" class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <!-- Form Filter (sama seperti sebelumnya, disingkat untuk keterbacaan) -->
                <div class="relative mt-6 flex-1 px-4 sm:px-6">
                    <form action="" method="GET" id="drawer-form">
                        <!-- Pertahankan input hidden dan elemen form seperti kode asli Anda di sini -->
                        <input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>">
                        <input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>">
                        
                        <!-- (Salin konten form drawer dari kode asli Anda ke sini) -->
                        <!-- Bagian Sort, Price, Location dsb -->
                        <div class="mb-8">
                             <label class="text-sm font-bold text-gray-900 block mb-3">Urutkan</label>
                             <select name="sort" class="w-full border p-2 rounded">
                                <option value="terbaru" <?php selected($urutan, 'terbaru'); ?>>Terbaru</option>
                                <option value="terlaris" <?php selected($urutan, 'terlaris'); ?>>Terlaris</option>
                                <option value="termurah" <?php selected($urutan, 'termurah'); ?>>Termurah</option>
                                <option value="termahal" <?php selected($urutan, 'termahal'); ?>>Termahal</option>
                             </select>
                        </div>

                         <div class="pt-6 border-t border-gray-100 flex gap-3">
                            <button type="submit" class="flex-1 py-3 bg-orange-600 text-white rounded-xl font-bold">Terapkan</button>
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
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

/* Animasi Fade In untuk AJAX */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. AJAX CATEGORY LOGIC ---
    const resultArea = document.getElementById('product-results-area');
    const categoryBadges = document.querySelectorAll('.category-badge');
    const titleText = document.getElementById('page-title-text');

    categoryBadges.forEach(badge => {
        badge.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah reload halaman
            
            const url = this.getAttribute('href');
            const categoryName = this.innerText.trim();
            const isActive = this.classList.contains('active');

            if(isActive) return; // Jika sudah aktif, tidak perlu reload

            // A. Update Visual Badge INSTAN
            categoryBadges.forEach(b => {
                b.classList.remove('bg-orange-500', 'text-white', 'border-orange-500', 'shadow-md', 'shadow-orange-200', 'active');
                b.classList.add('bg-white', 'text-gray-500', 'border-gray-200', 'hover:border-orange-500');
            });
            this.classList.remove('bg-white', 'text-gray-500', 'border-gray-200', 'hover:border-orange-500');
            this.classList.add('bg-orange-500', 'text-white', 'border-orange-500', 'shadow-md', 'shadow-orange-200', 'active');

            // B. Efek Loading Halus pada Area Hasil
            resultArea.style.opacity = '0.4';

            // C. Fetch Data
            fetch(url + '&ajax_update=1')
                .then(response => response.text())
                .then(html => {
                    // Update Konten
                    resultArea.innerHTML = html;
                    
                    // Update URL Browser (Push State)
                    window.history.pushState({path: url}, '', url);
                    
                    // Kembalikan Opacity
                    resultArea.style.opacity = '1';
                    
                    // Update Title Text (Optional)
                    if(categoryName === 'Semua') {
                        titleText.innerText = 'Jelajahi Produk Desa';
                    } else {
                        titleText.innerText = 'Kategori: ' + categoryName;
                    }

                    // Re-init listener untuk pagination yang baru dimuat
                    initPaginationListeners();
                })
                .catch(err => {
                    console.error('Error:', err);
                    resultArea.style.opacity = '1';
                });
        });
    });

    // Helper: Agar pagination yang baru muncul via AJAX juga tidak reload halaman
    function initPaginationListeners() {
        const pageLinks = document.querySelectorAll('.pagination-ajax a');
        pageLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                resultArea.style.opacity = '0.4';
                
                fetch(url + '&ajax_update=1')
                    .then(response => response.text())
                    .then(html => {
                        resultArea.innerHTML = html;
                        window.history.pushState({path: url}, '', url);
                        resultArea.style.opacity = '1';
                        // Scroll ke atas grid sedikit
                        document.querySelector('.container').scrollIntoView({ behavior: 'smooth' });
                        initPaginationListeners(); // Rekursif untuk halaman berikutnya
                    });
            });
        });
    }

    // Jalankan init pagination saat load pertama
    initPaginationListeners();


    // --- 2. DRAWER & EXISTING LOGIC (Kode Lama Anda) ---
    const drawer = document.getElementById('filter-drawer');
    const backdrop = document.getElementById('filter-backdrop');
    const panel = document.getElementById('filter-panel');
    const openBtn = document.getElementById('open-filter');
    const closeBtn = document.getElementById('close-filter');
    
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