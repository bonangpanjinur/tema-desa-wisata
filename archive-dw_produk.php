<?php
/**
 * Template Name: Arsip Produk Modern (Desain Wisata Style)
 * Description: Layout full-width dengan filter drawer dan desain kartu estetik ala Wisata.
 */

get_header();

global $wpdb;
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

// --- 1. PARAMETER FILTER ---
$pencarian  = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kategori   = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$kabupaten  = isset($_GET['kab']) ? sanitize_text_field($_GET['kab']) : ''; // Filter lokasi toko
$max_harga  = isset($_GET['max_price']) ? intval($_GET['max_price']) : 0;
$urutan     = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';

// --- 2. QUERY BUILDER ---
// Pastikan nama tabel benar dan ada prefix wp_
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
$items_per_page = 12; // Konsisten dengan wisata
$page = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
$offset = ($page - 1) * $items_per_page;

$count_sql = str_replace("SELECT p.*, pd.nama_toko, pd.slug_toko, d.kabupaten, d.nama_desa", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$produk_list = $wpdb->get_results($sql);

// Data Filter Dinamis (Gunakan DISTINCT agar efisien)
$list_kabupaten = $wpdb->get_col("SELECT DISTINCT kabupaten FROM $table_desa WHERE status='aktif' AND kabupaten != '' ORDER BY kabupaten ASC");
$list_kategori = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE status='aktif' AND kategori != '' ORDER BY kategori ASC");

if(empty($list_kategori)) $list_kategori = ['Makanan', 'Minuman', 'Kerajinan', 'Fashion', 'Pertanian'];

// Warna Kategori (Style Wisata)
function get_cat_color_prod($cat) {
    $colors = [
        'Makanan'   => 'bg-orange-600 text-white border-orange-700 shadow-orange-200',
        'Minuman'   => 'bg-blue-600 text-white border-blue-700 shadow-blue-200',
        'Kerajinan' => 'bg-purple-600 text-white border-purple-700 shadow-purple-200',
        'Fashion'   => 'bg-pink-600 text-white border-pink-700 shadow-pink-200',
        'Pertanian' => 'bg-green-600 text-white border-green-700 shadow-green-200'
    ];
    // Default fallback
    return isset($colors[$cat]) ? $colors[$cat] : 'bg-gray-800 text-white border-gray-900';
}
?>

<div class="bg-[#FAFAFA] min-h-screen font-sans text-gray-800 pb-20 relative overflow-x-hidden">
    
    <!-- Background Decor (Sama dengan Wisata tapi nuansa Orange) -->
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-orange-50/50 to-transparent -z-10"></div>
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-yellow-50/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 -z-10"></div>

    <!-- === 1. TOP HEADER & SEARCH (PERSIS ARSIP WISATA) === -->
    <div class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Logo Area -->
                <div class="flex items-center gap-2 self-start md:self-center cursor-pointer" onclick="window.location.href='<?php echo home_url('/produk'); ?>'">
                    <div class="w-8 h-8 bg-gradient-to-tr from-orange-500 to-amber-400 rounded-lg flex items-center justify-center text-white shadow-lg shadow-orange-200">
                        <i class="fas fa-box-open text-sm"></i>
                    </div>
                    <span class="font-bold text-lg tracking-tight">Pasar<span class="text-orange-600">Desa</span></span>
                </div>

                <!-- Search & Filter Group -->
                <div class="w-full md:w-auto flex items-center gap-3 flex-1 md:justify-center max-w-3xl">
                    <form action="" method="GET" class="relative w-full group">
                        <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                               class="w-full pl-12 pr-4 py-3 bg-gray-50 hover:bg-white focus:bg-white border border-gray-200 hover:border-gray-300 focus:border-orange-500 rounded-full transition-all outline-none text-sm shadow-sm focus:shadow-md focus:ring-4 focus:ring-orange-50" 
                               placeholder="Cari produk desa...">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                        
                        <!-- Hidden Filters -->
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
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- User/Cart Actions (Optional - Menyesuaikan Layout Wisata) -->
                <div class="hidden md:flex items-center gap-3">
                     <!-- Cart Button Placeholder jika diperlukan -->
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
                   class="shrink-0 px-4 py-1.5 rounded-full text-xs font-bold border transition-all duration-300 <?php echo $is_active ? 'bg-orange-500 text-white border-orange-500 shadow-md shadow-orange-200' : 'bg-white text-gray-500 border-gray-200 hover:border-orange-500 hover:text-orange-600'; ?>">
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
                <?php echo $pencarian ? 'Hasil pencarian: "'.esc_html($pencarian).'"' : 'Jelajahi Produk Desa'; ?>
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

        <?php if ($produk_list) : ?>
        <!-- GRID LAYOUT -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($produk_list as $p) : 
                $link_p = !empty($p->slug_produk) ? home_url('/produk/detail/' . $p->slug_produk) : home_url('/detail-produk/?id=' . $p->id);
                
                // --- LOGIKA GAMBAR PRODUK (Disesuaikan dengan Front Page) ---
                $img_p = 'https://via.placeholder.com/400x400?text=Produk'; 
                $fotos = !empty($p->foto_produk) ? json_decode($p->foto_produk) : [];
                
                // Ambil foto pertama dari array, atau jika bukan array gunakan langsung
                $foto_utama = (is_array($fotos) && !empty($fotos)) ? $fotos[0] : $p->foto_produk;

                if (!empty($foto_utama)) {
                    if (is_numeric($foto_utama)) {
                        // Jika berupa ID Attachment
                        $img_src = wp_get_attachment_image_src($foto_utama, 'medium_large');
                        if ($img_src) {
                            $img_p = $img_src[0];
                        }
                    } else {
                        // Jika berupa URL string
                        $img_p = $foto_utama;
                    }
                }
                
                $lokasi = !empty($p->nama_desa) ? 'Desa ' . $p->nama_desa : ($p->kabupaten ?: 'Indonesia');
                $rating = isset($p->rating_avg) && $p->rating_avg > 0 ? $p->rating_avg : '4.5';
                $cat_class = get_cat_color_prod($p->kategori);
            ?>
            
            <!-- CARD MODERN (Style Wisata) -->
            <div class="group bg-white rounded-2xl overflow-hidden hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 flex flex-col h-full border border-gray-100 relative">
                
                <!-- Image Wrapper -->
                <div class="relative aspect-square overflow-hidden bg-gray-200">
                    <a href="<?php echo esc_url($link_p); ?>" class="block w-full h-full">
                        <img src="<?php echo esc_url($img_p); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110 group-hover:rotate-1" alt="<?php echo esc_attr($p->nama_produk); ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/400x400?text=No+Image';">
                        
                        <!-- Overlay Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>
                    </a>

                    <!-- Category Badge -->
                    <?php if(isset($p->kategori)): ?>
                    <div class="absolute top-3 left-3">
                        <span class="text-[10px] font-bold px-3 py-1 rounded-full border shadow-lg <?php echo $cat_class; ?> backdrop-blur-sm uppercase tracking-wide">
                            <?php echo esc_html($p->kategori); ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Terlaris Badge (Conditional) -->
                    <?php if($p->terjual > 10): ?>
                    <div class="absolute top-3 right-3 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm flex items-center gap-1">
                        <i class="fas fa-fire"></i> Terlaris
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-4 flex flex-col flex-1">
                    <!-- Rating & Location Row -->
                    <div class="flex items-center justify-between mb-2 text-xs">
                        <div class="flex items-center gap-1 text-yellow-500 font-bold bg-yellow-50 px-2 py-0.5 rounded-md">
                            <i class="fas fa-star"></i> <?php echo $rating; ?>
                        </div>
                        <div class="flex items-center gap-1 text-gray-500 font-medium">
                            <i class="fas fa-map-marker-alt text-red-400"></i> 
                            <span class="truncate max-w-[80px] sm:max-w-[120px]"><?php echo esc_html($lokasi); ?></span>
                        </div>
                    </div>

                    <!-- Title -->
                    <a href="<?php echo esc_url($link_p); ?>">
                        <h3 class="font-bold text-gray-900 text-sm md:text-base leading-snug mb-1 line-clamp-2 group-hover:text-orange-600 transition min-h-[2.5em]">
                            <?php echo esc_html($p->nama_produk); ?>
                        </h3>
                    </a>

                    <!-- Shop Name -->
                    <div class="text-xs text-gray-400 mb-3 flex items-center gap-1">
                        <i class="fas fa-store text-[10px]"></i> <span class="truncate"><?php echo esc_html($p->nama_toko ?: 'UMKM Desa'); ?></span>
                    </div>

                    <!-- Divider -->
                    <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-orange-600 font-bold text-lg leading-none">
                                Rp <?php echo number_format($p->harga, 0, ',', '.'); ?>
                            </span>
                            <span class="text-[10px] text-gray-400 mt-1"><?php echo $p->terjual; ?> Terjual</span>
                        </div>
                        
                        <!-- Add Cart Button -->
                        <button class="w-9 h-9 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-orange-500 hover:text-white transition shadow-sm active:scale-95 btn-add-to-cart" data-product-id="<?php echo $p->id; ?>" data-is-custom="1" title="Tambah ke Keranjang">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-12 flex justify-center gap-2">
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

<!-- === FILTER DRAWER / MODAL (Sama dengan Wisata) === -->
<div id="filter-drawer" class="fixed inset-0 z-50 hidden transition-opacity duration-300" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0" id="filter-backdrop"></div>

    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none">
        <div class="pointer-events-auto w-screen max-w-md transform transition ease-in-out duration-300 translate-x-full" id="filter-panel">
            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                
                <!-- Header Drawer -->
                <div class="px-4 py-6 sm:px-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900" id="slide-over-title">Filter Produk</h2>
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
                                <!-- Terbaru (Biru) -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="terbaru" <?php checked($urutan, 'terbaru'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-center text-sm font-medium text-gray-600 peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition flex flex-col items-center justify-center gap-2 group">
                                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center group-peer-checked:bg-blue-100 transition">
                                            <i class="fas fa-clock text-blue-400 group-peer-checked:text-blue-600 text-lg"></i>
                                        </div>
                                        <span>Terbaru</span>
                                    </div>
                                </label>
                                
                                <!-- Terlaris (Merah/Api) -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="terlaris" <?php checked($urutan, 'terlaris'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-center text-sm font-medium text-gray-600 peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition flex flex-col items-center justify-center gap-2 group">
                                        <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center group-peer-checked:bg-red-100 transition">
                                            <i class="fas fa-fire text-red-400 group-peer-checked:text-red-600 text-lg"></i>
                                        </div>
                                        <span>Terlaris</span>
                                    </div>
                                </label>

                                <!-- Termurah (Hijau/Tag) -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="termurah" <?php checked($urutan, 'termurah'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-center text-sm font-medium text-gray-600 peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition flex flex-col items-center justify-center gap-2 group">
                                        <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center group-peer-checked:bg-green-100 transition">
                                            <i class="fas fa-tag text-green-400 group-peer-checked:text-green-600 text-lg"></i>
                                        </div>
                                        <span>Termurah</span>
                                    </div>
                                </label>
                                
                                <!-- Termahal (Ungu/Mahkota) -->
                                <label class="cursor-pointer">
                                    <input type="radio" name="sort" value="termahal" <?php checked($urutan, 'termahal'); ?> class="peer sr-only">
                                    <div class="px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-center text-sm font-medium text-gray-600 peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition flex flex-col items-center justify-center gap-2 group">
                                        <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center group-peer-checked:bg-purple-100 transition">
                                            <i class="fas fa-crown text-purple-400 group-peer-checked:text-purple-600 text-lg"></i>
                                        </div>
                                        <span>Termahal</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Section: Budget -->
                        <div class="mb-8">
                            <label class="text-sm font-bold text-gray-900 block mb-3">Maksimal Harga</label>
                            <input type="range" min="0" max="500000" step="10000" value="<?php echo $max_harga > 0 ? $max_harga : 500000; ?>" 
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500"
                                   oninput="document.getElementById('price-label').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(this.value)">
                            <div class="flex justify-between mt-2 text-xs font-bold text-gray-500">
                                <span>Rp 0</span>
                                <span id="price-label" class="text-orange-600">Rp <?php echo $max_harga > 0 ? number_format($max_harga,0,',','.') : '500.000+'; ?></span>
                            </div>
                            <input type="hidden" name="max_price" value="<?php echo esc_attr($max_harga); ?>" id="input-max-price">
                        </div>

                        <!-- Section: Lokasi (Asal Produk/Kabupaten) -->
                        <div class="mb-8">
                            <label class="text-sm font-bold text-gray-900 block mb-3">Lokasi (Kabupaten)</label>
                            <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
                                <label class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition">
                                    <input type="radio" name="kab" value="" <?php checked($kabupaten, ''); ?> class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700">Semua Lokasi</span>
                                </label>
                                <?php foreach($list_kabupaten as $kab): ?>
                                <label class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer transition">
                                    <input type="radio" name="kab" value="<?php echo esc_attr($kab); ?>" <?php checked($kabupaten, $kab); ?> class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                    <span class="ml-3 text-sm text-gray-700"><?php echo esc_html($kab); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-6 border-t border-gray-100 flex gap-3">
                            <a href="<?php echo home_url('/produk'); ?>" class="flex-1 py-3 text-center border border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 transition">
                                Reset
                            </a>
                            <button type="submit" class="flex-1 py-3 bg-orange-600 text-white rounded-xl font-bold hover:bg-orange-700 shadow-lg shadow-orange-200 transition">
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
    // Drawer Logic (Sama seperti Wisata)
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
    
    // Add to Cart Logic (AJAX)
    jQuery('.btn-add-to-cart').on('click', function(e) {
        e.preventDefault();
        var btn = jQuery(this);
        var originalIcon = btn.html();
        var isCustom = btn.data('is-custom') ? 1 : 0;
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin text-xs"></i>');
        
        jQuery.post(dw_ajax.ajax_url, {
            action: 'dw_theme_add_to_cart',
            nonce: dw_ajax.nonce,
            product_id: btn.data('product-id'),
            quantity: 1,
            is_custom_db: isCustom
        }, function(response) {
            if(response.success) {
                btn.html('<i class="fas fa-check text-xs"></i>').addClass('bg-green-600 text-white').removeClass('bg-gray-50 text-gray-600 hover:bg-orange-500');
                if(response.data.count) {
                    jQuery('#header-cart-count, #header-cart-count-mobile').text(response.data.count).removeClass('hidden').addClass('flex');
                }
                setTimeout(function() {
                    btn.html(originalIcon)
                        .removeClass('bg-green-600 text-white')
                        .addClass('bg-gray-50 text-gray-600 hover:bg-orange-500')
                        .prop('disabled', false);
                }, 2000);
            } else {
                alert('Gagal: ' + (response.data.message || 'Error'));
                btn.html(originalIcon).prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>