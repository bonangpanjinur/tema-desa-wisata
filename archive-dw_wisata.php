<?php
/**
 * Template Name: Arsip Wisata Immersive
 * Description: Menampilkan daftar wisata dengan kartu visual besar dan layout modern.
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

// --- FILTER PARAMS ---
$pencarian  = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$kabupaten  = isset($_GET['kab']) ? sanitize_text_field($_GET['kab']) : '';
$kategori   = isset($_GET['kat']) ? sanitize_text_field($_GET['kat']) : '';
$urutan     = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'terbaru';

// --- QUERY ---
$sql = "SELECT w.*, d.nama_desa, d.kabupaten, d.kecamatan 
        FROM $table_wisata w 
        LEFT JOIN $table_desa d ON w.id_desa = d.id 
        WHERE w.status = 'aktif'";

if ($pencarian) $sql .= $wpdb->prepare(" AND (w.nama_wisata LIKE %s OR w.deskripsi LIKE %s)", '%' . $pencarian . '%', '%' . $pencarian . '%');
if ($kabupaten) $sql .= $wpdb->prepare(" AND d.kabupaten = %s", $kabupaten);
if ($kategori) $sql .= $wpdb->prepare(" AND w.kategori = %s", $kategori);

switch ($urutan) {
    case 'termurah': $sql .= " ORDER BY w.harga_tiket ASC"; break;
    case 'termahal': $sql .= " ORDER BY w.harga_tiket DESC"; break;
    case 'terpopuler': $sql .= " ORDER BY w.rating_avg DESC"; break;
    default: $sql .= " ORDER BY w.created_at DESC"; break;
}

$items_per_page = 9;
$page = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
$offset = ($page - 1) * $items_per_page;

$count_sql = str_replace("SELECT w.*, d.nama_desa, d.kabupaten, d.kecamatan", "SELECT COUNT(*)", $sql);
$total_items = $wpdb->get_var($count_sql);
$total_pages = ceil($total_items / $items_per_page);

$sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);
$wisata_list = $wpdb->get_results($sql);

$list_kabupaten = $wpdb->get_col("SELECT DISTINCT kabupaten FROM $table_desa WHERE status='aktif' ORDER BY kabupaten ASC");
$list_kategori = ['Alam', 'Budaya', 'Religi', 'Kuliner', 'Edukasi']; 
?>

<!-- === HERO BANNER === -->
<div class="relative bg-gray-900 h-[400px] flex items-center justify-center overflow-hidden">
    <!-- Background Image dengan Parallax effect (via CSS fixed kalau mau, ini simple abs) -->
    <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80" 
         class="absolute inset-0 w-full h-full object-cover opacity-50" alt="Wisata Indonesia">
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-gray-900/50"></div>
    
    <div class="relative z-10 text-center max-w-3xl px-4 mt-10">
        <span class="text-green-400 font-bold tracking-[0.2em] uppercase text-sm mb-4 block">Pesona Indonesia</span>
        <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">Jelajahi Keindahan<br>Desa Wisata</h1>
        
        <!-- Search Bar Floating -->
        <form action="" method="GET" class="relative max-w-lg mx-auto">
            <input type="text" name="q" value="<?php echo esc_attr($pencarian); ?>" 
                   class="w-full py-4 pl-6 pr-14 rounded-full bg-white text-gray-800 placeholder-gray-400 shadow-2xl focus:outline-none focus:ring-4 focus:ring-green-500/30 transition text-lg" 
                   placeholder="Mau kemana hari ini?">
            <button type="submit" class="absolute right-2 top-2 w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white hover:bg-green-700 transition shadow-md">
                <i class="fas fa-search"></i>
            </button>
            <?php if($kabupaten): ?><input type="hidden" name="kab" value="<?php echo esc_attr($kabupaten); ?>"><?php endif; ?>
        </form>
    </div>
</div>

<!-- === CONTENT SECTION === -->
<div class="bg-gray-50 min-h-screen py-12">
    <div class="container mx-auto px-4">
        
        <!-- FILTER BAR (Horizontal Scrollable) -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-10">
            <!-- Filter Kategori (Pill) -->
            <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar w-full md:w-auto">
                <a href="?kat=" class="px-5 py-2 rounded-full text-sm font-bold border transition whitespace-nowrap <?php echo $kategori == '' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-200 hover:border-green-600 hover:text-green-600'; ?>">
                    Semua
                </a>
                <?php foreach($list_kategori as $kat): ?>
                <a href="?kat=<?php echo urlencode($kat); ?>" class="px-5 py-2 rounded-full text-sm font-bold border transition whitespace-nowrap <?php echo $kategori == $kat ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-200 hover:border-green-600 hover:text-green-600'; ?>">
                    <?php echo esc_html($kat); ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Filter Dropdowns -->
            <div class="flex gap-3 w-full md:w-auto">
                <form action="" method="GET" class="flex gap-3 w-full">
                    <?php if($pencarian): ?><input type="hidden" name="q" value="<?php echo esc_attr($pencarian); ?>"><?php endif; ?>
                    <?php if($kategori): ?><input type="hidden" name="kat" value="<?php echo esc_attr($kategori); ?>"><?php endif; ?>
                    
                    <select name="kab" onchange="this.form.submit()" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5 w-full md:w-48 cursor-pointer">
                        <option value="">Semua Lokasi</option>
                        <?php foreach($list_kabupaten as $kab): ?>
                        <option value="<?php echo esc_attr($kab); ?>" <?php selected($kabupaten, $kab); ?>><?php echo esc_html($kab); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="sort" onchange="this.form.submit()" class="bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-green-500 focus:border-green-500 p-2.5 w-full md:w-40 cursor-pointer">
                        <option value="terbaru" <?php selected($urutan, 'terbaru'); ?>>Terbaru</option>
                        <option value="terpopuler" <?php selected($urutan, 'terpopuler'); ?>>Populer</option>
                        <option value="termurah" <?php selected($urutan, 'termurah'); ?>>Termurah</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- WISATA GRID (IMMERSIVE CARDS) -->
        <?php if ($wisata_list) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($wisata_list as $w) : 
                $link_w = !empty($w->slug) ? home_url('/wisata/detail/' . $w->slug) : home_url('/detail-wisata/?id=' . $w->id);
                $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/800x600?text=Wisata';
                $rating = ($w->rating_avg > 0) ? $w->rating_avg : 'Baru';
                $lokasi = !empty($w->kabupaten) ? $w->kabupaten : 'Indonesia';
            ?>
            <a href="<?php echo esc_url($link_w); ?>" class="group relative h-[400px] rounded-3xl overflow-hidden shadow-lg transition-all duration-500 hover:shadow-2xl hover:-translate-y-2">
                
                <!-- Background Image Full -->
                <img src="<?php echo esc_url($img_w); ?>" class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-110" alt="<?php echo esc_attr($w->nama_wisata); ?>">
                
                <!-- Overlay Gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-80 group-hover:opacity-90 transition"></div>

                <!-- Top Badges -->
                <div class="absolute top-4 left-4 flex gap-2">
                    <?php if(isset($w->kategori)): ?>
                    <span class="bg-white/20 backdrop-blur-md border border-white/20 text-white text-xs font-bold px-3 py-1 rounded-full">
                        <?php echo esc_html($w->kategori); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Rating Badge -->
                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur text-gray-900 text-xs font-bold px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-sm">
                    <i class="fas fa-star text-yellow-500"></i> <?php echo $rating; ?>
                </div>

                <!-- Content (Bottom) -->
                <div class="absolute bottom-0 left-0 w-full p-6 text-white transform translate-y-2 group-hover:translate-y-0 transition duration-300">
                    <div class="flex items-center gap-2 text-xs text-gray-300 mb-2">
                        <i class="fas fa-map-marker-alt text-green-400"></i> <?php echo esc_html($lokasi); ?>
                    </div>
                    
                    <h3 class="text-2xl font-bold mb-3 leading-tight group-hover:text-green-300 transition">
                        <?php echo esc_html($w->nama_wisata); ?>
                    </h3>
                    
                    <div class="flex items-center justify-between border-t border-white/10 pt-4 mt-2">
                        <div>
                            <span class="block text-[10px] text-gray-400 uppercase tracking-widest">Tiket Masuk</span>
                            <span class="font-bold text-lg text-white">
                                <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                            </span>
                        </div>
                        <span class="w-10 h-10 rounded-full bg-white/20 hover:bg-green-500 flex items-center justify-center backdrop-blur transition">
                            <i class="fas fa-arrow-right -rotate-45 group-hover:rotate-0 transition duration-300"></i>
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-16 flex justify-center gap-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): 
                $active = ($i == $page) ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-200 hover:border-green-600 hover:text-green-600';
                $url = add_query_arg('halaman', $i);
            ?>
            <a href="<?php echo esc_url($url); ?>" class="w-12 h-12 flex items-center justify-center rounded-full border font-bold text-sm transition <?php echo $active; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20 bg-white rounded-3xl border border-gray-100">
                <i class="far fa-compass text-5xl text-gray-200 mb-4 block"></i>
                <h3 class="text-xl font-bold text-gray-800">Tidak ada destinasi ditemukan</h3>
                <p class="text-gray-500 mt-2">Coba ubah filter lokasi atau kata kunci pencarian Anda.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php get_footer(); ?>