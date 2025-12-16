<?php
/**
 * Template Name: Profil Toko Custom
 * URL akses: /profil/toko/slug-toko
 */

get_header();

global $wpdb;
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_desa     = $wpdb->prefix . 'dw_desa';

// 1. Tangkap Slug dari URL (Rewrite Rule)
$slug_toko = get_query_var('dw_slug_toko');

// 2. Tangkap ID (Fallback jika diakses manual ?id=)
$toko_id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

$toko = null;

// 3. Query Data Toko
if (!empty($slug_toko)) {
    // Query berdasarkan SLUG
    $toko = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, d.nama_desa 
        FROM $table_pedagang p
        LEFT JOIN $table_desa d ON p.id_desa = d.id
        WHERE p.slug_toko = %s AND p.status_akun = 'aktif'
    ", $slug_toko));
} elseif ($toko_id_param > 0) {
    // Query berdasarkan ID (Fallback)
    $toko = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, d.nama_desa 
        FROM $table_pedagang p
        LEFT JOIN $table_desa d ON p.id_desa = d.id
        WHERE p.id = %d AND p.status_akun = 'aktif'
    ", $toko_id_param));
}

// 4. Not Found
if (!$toko) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="text-6xl text-gray-200 mb-4"><i class="fas fa-store-slash"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Toko Tidak Ditemukan</h1>';
    echo '<p class="text-gray-500 mb-6">Mungkin tautan rusak atau toko telah nonaktif.</p>';
    echo '<a href="'.home_url('/').'" class="bg-primary text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 transition">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 5. Ambil Produk Toko
$produk_list = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM $table_produk 
    WHERE id_pedagang = %d AND status = 'aktif' 
    ORDER BY created_at DESC
", $toko->id));

// Ambil Kategori Unik dari Produk Toko untuk filter
$kategori_toko = [];
foreach ($produk_list as $prod) {
    if (!empty($prod->kategori)) {
        $kategori_toko[$prod->kategori] = $prod->kategori;
    }
}

// Data Pendukung Tampilan
$foto_profil = !empty($toko->foto_profil) ? $toko->foto_profil : 'https://via.placeholder.com/150';
$foto_sampul = !empty($toko->foto_sampul) ? $toko->foto_sampul : 'https://via.placeholder.com/1200x300?text=Banner+Toko'; 
$wa_link = 'https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $toko->nomor_wa));
$rating_toko = isset($toko->rating_toko) ? $toko->rating_toko : 0;
?>

<!-- === HEADER TOKO === -->
<div class="bg-white border-b border-gray-200 pb-4">
    <!-- Banner Area -->
    <div class="h-32 md:h-48 bg-gray-200 w-full overflow-hidden relative group">
        <img src="<?php echo esc_url($foto_sampul); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
    </div>

    <div class="container mx-auto px-4 relative">
        <!-- Info Toko -->
        <div class="flex flex-col md:flex-row items-center md:items-end -mt-12 md:-mt-16 gap-4 mb-4">
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-white shadow-md bg-white overflow-hidden flex-shrink-0 relative z-10">
                <img src="<?php echo esc_url($foto_profil); ?>" class="w-full h-full object-cover">
            </div>
            
            <div class="flex-1 text-center md:text-left mb-2 md:mb-0 relative z-10 md:text-white md:mix-blend-normal text-gray-800">
                <h1 class="text-2xl font-bold mb-1 md:text-white drop-shadow-sm"><?php echo esc_html($toko->nama_toko); ?></h1>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 text-sm md:text-gray-100 text-gray-500 font-medium">
                    <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-500 md:text-red-400"></i> <?php echo esc_html($toko->nama_desa ?: 'Desa Wisata'); ?></span>
                    <span class="hidden md:inline">•</span>
                    <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> <?php echo number_format($rating_toko, 1); ?> Rating</span>
                </div>
            </div>

            <div class="flex gap-2 relative z-10 pt-2 md:pt-0">
                <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2 transform hover:-translate-y-0.5 transition">
                    <i class="fab fa-whatsapp text-lg"></i> Chat Penjual
                </a>
                <button onclick="navigator.share({title: '<?php echo esc_js($toko->nama_toko); ?>', url: window.location.href})" class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>
        </div>

        <!-- Tab Menu -->
        <div class="flex border-b border-gray-100 mt-6 overflow-x-auto hide-scroll gap-6 text-sm font-medium text-gray-500">
            <button class="tab-btn pb-3 border-b-2 border-primary text-primary font-bold active" onclick="switchTab('produk', this)">Produk</button>
            <button class="tab-btn pb-3 border-b-2 border-transparent hover:text-gray-800 transition" onclick="switchTab('info', this)">Informasi Toko</button>
        </div>
    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen py-6">
    <div class="container mx-auto px-4">
        
        <!-- TAB: PRODUK -->
        <div id="tab-produk" class="tab-content">
            
            <!-- Filter Kategori -->
            <?php if (!empty($kategori_toko)) : ?>
            <div class="flex gap-2 overflow-x-auto hide-scroll mb-6 pb-2">
                <button class="px-4 py-1.5 rounded-full bg-primary text-white text-xs font-bold shadow-sm whitespace-nowrap filter-btn" onclick="filterProduk('all', this)">Semua</button>
                <?php foreach ($kategori_toko as $kat) : ?>
                <button class="px-4 py-1.5 rounded-full bg-white text-gray-600 border border-gray-200 text-xs font-bold whitespace-nowrap hover:border-primary hover:text-primary transition filter-btn" onclick="filterProduk('<?php echo esc_attr(sanitize_title($kat)); ?>', this)">
                    <?php echo esc_html($kat); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Grid Produk -->
            <?php if ($produk_list) : ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php foreach ($produk_list as $prod) : 
                    $img = !empty($prod->foto_utama) ? $prod->foto_utama : 'https://via.placeholder.com/300';
                    // Link Detail Produk menggunakan Slug
                    $link = home_url('/produk/detail/' . $prod->slug);
                    $cat_slug = sanitize_title($prod->kategori);
                ?>
                <div class="product-card-item bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md transition group block" data-category="<?php echo esc_attr($cat_slug); ?>">
                    <a href="<?php echo esc_url($link); ?>" class="block relative aspect-square bg-gray-100 overflow-hidden">
                        <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <?php if($prod->stok < 1): ?>
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center text-white font-bold text-sm">HABIS</div>
                        <?php endif; ?>
                    </a>
                    <div class="p-3">
                        <h3 class="text-sm font-bold text-gray-800 line-clamp-2 mb-1 group-hover:text-primary min-h-[2.5em]">
                            <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($prod->nama_produk); ?></a>
                        </h3>
                        <div class="font-bold text-primary text-sm mb-2">
                            Rp <?php echo number_format($prod->harga, 0, ',', '.'); ?>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-gray-400 pt-2 border-t border-gray-50">
                            <span>Terjual <?php echo $prod->terjual; ?></span>
                            <span class="flex items-center gap-0.5 text-yellow-500"><i class="fas fa-star"></i> <?php echo ($prod->rating_avg > 0) ? $prod->rating_avg : '4.5'; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
                <div class="flex flex-col items-center justify-center py-20 text-gray-400 bg-white rounded-xl border border-dashed border-gray-200">
                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                    <p>Belum ada produk di toko ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: INFORMASI -->
        <div id="tab-info" class="tab-content hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Info Utama -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-primary"></i> Tentang Toko
                        </h3>
                        <div class="space-y-4 text-sm text-gray-600">
                            <div class="flex gap-4">
                                <div class="w-8 flex justify-center"><i class="fas fa-map-marker-alt text-gray-400 text-lg"></i></div>
                                <div>
                                    <span class="font-bold text-gray-800 block">Alamat</span>
                                    <?php echo esc_html($toko->alamat_lengkap); ?>
                                </div>
                            </div>
                            
                            <div class="flex gap-4">
                                <div class="w-8 flex justify-center"><i class="fas fa-user text-gray-400 text-lg"></i></div>
                                <div>
                                    <span class="font-bold text-gray-800 block">Pemilik</span>
                                    <?php echo esc_html($toko->nama_pemilik); ?>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="w-8 flex justify-center"><i class="fas fa-calendar-check text-gray-400 text-lg"></i></div>
                                <div>
                                    <span class="font-bold text-gray-800 block">Bergabung Sejak</span>
                                    <?php echo date('d M Y', strtotime($toko->created_at)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peta / Tambahan -->
                <div class="md:col-span-1">
                    <?php if($toko->url_gmaps): ?>
                    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-4">Lokasi</h3>
                        <a href="<?php echo esc_url($toko->url_gmaps); ?>" target="_blank" class="block w-full bg-gray-100 h-32 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 transition mb-3">
                            <div class="text-center">
                                <i class="fas fa-map-marked-alt text-2xl mb-1"></i>
                                <span class="block text-xs font-bold">Buka Peta</span>
                            </div>
                        </a>
                        <a href="<?php echo esc_url($toko->url_gmaps); ?>" target="_blank" class="text-primary font-bold text-sm hover:underline flex items-center gap-1 justify-center">
                            Buka di Google Maps <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Tab Switcher Logic
function switchTab(tabName, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-primary', 'text-primary', 'font-bold');
        el.classList.add('border-transparent');
    });

    document.getElementById('tab-' + tabName).classList.remove('hidden');
    btn.classList.remove('border-transparent');
    btn.classList.add('border-primary', 'text-primary', 'font-bold');
}

// Product Filter Logic
function filterProduk(category, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('bg-primary', 'text-white');
        b.classList.add('bg-white', 'text-gray-600', 'border-gray-200');
    });
    btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-200');
    btn.classList.add('bg-primary', 'text-white', 'border-transparent');

    const items = document.querySelectorAll('.product-card-item');
    items.forEach(item => {
        if(category === 'all' || item.dataset.category === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php get_footer(); ?>