<?php
/**
 * Template Name: Profil Toko Custom
 * Gunakan page ini dengan slug: profil-toko
 * URL akses: /profil-toko/?id=123 (ID Toko/Pedagang)
 */

get_header();

global $wpdb;
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_desa     = $wpdb->prefix . 'dw_desa';

// 1. Tangkap ID Toko
$toko_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$toko = null;

if ($toko_id > 0) {
    $toko = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, d.nama_desa 
        FROM $table_pedagang p
        LEFT JOIN $table_desa d ON p.id_desa = d.id
        WHERE p.id = %d AND p.status_akun = 'aktif'
    ", $toko_id));
}

// 2. Not Found
if (!$toko) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="text-6xl text-gray-200 mb-4"><i class="fas fa-store-slash"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Toko Tidak Ditemukan</h1>';
    echo '<a href="'.home_url('/').'" class="text-primary font-bold hover:underline">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 3. Ambil Produk Toko
$produk_list = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM $table_produk 
    WHERE id_pedagang = %d AND status = 'aktif' 
    ORDER BY created_at DESC
", $toko_id));

// Ambil Kategori Unik dari Produk Toko
$kategori_toko = [];
foreach ($produk_list as $prod) {
    if (!empty($prod->kategori)) {
        $kategori_toko[$prod->kategori] = $prod->kategori;
    }
}

// Data Pendukung
$foto_profil = !empty($toko->foto_profil) ? $toko->foto_profil : 'https://via.placeholder.com/150';
$banner_toko = 'https://via.placeholder.com/1200x300?text=Banner+Toko'; // Bisa tambah kolom banner di DB
$wa_link = 'https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $toko->nomor_wa));
?>

<!-- === HEADER TOKO === -->
<div class="bg-white border-b border-gray-200 pb-4">
    <!-- Banner Area -->
    <div class="h-32 md:h-48 bg-gray-200 w-full overflow-hidden relative">
        <img src="<?php echo esc_url($banner_toko); ?>" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
    </div>

    <div class="container mx-auto px-4 relative">
        <!-- Info Toko -->
        <div class="flex flex-col md:flex-row items-center md:items-end -mt-12 md:-mt-16 gap-4 mb-4">
            <div class="w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-white shadow-md bg-white overflow-hidden flex-shrink-0">
                <img src="<?php echo esc_url($foto_profil); ?>" class="w-full h-full object-cover">
            </div>
            
            <div class="flex-1 text-center md:text-left mb-2 md:mb-0">
                <h1 class="text-2xl font-bold text-gray-900 mb-1"><?php echo esc_html($toko->nama_toko); ?></h1>
                <p class="text-sm text-gray-500 flex items-center justify-center md:justify-start gap-2">
                    <i class="fas fa-map-marker-alt text-red-500"></i> 
                    <?php echo esc_html($toko->alamat_lengkap); ?>
                    <?php if($toko->nama_desa) echo ' • ' . esc_html($toko->nama_desa); ?>
                </p>
            </div>

            <div class="flex gap-2">
                <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2">
                    <i class="fab fa-whatsapp"></i> Chat Penjual
                </a>
                <button class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-bold">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>
        </div>

        <!-- Tab Menu -->
        <div class="flex border-b border-gray-100 mt-6 overflow-x-auto hide-scroll gap-6 text-sm font-medium text-gray-500">
            <button class="tab-btn pb-3 border-b-2 border-primary text-primary active" onclick="switchTab('produk')">Produk</button>
            <button class="tab-btn pb-3 border-b-2 border-transparent hover:text-gray-800" onclick="switchTab('info')">Informasi Toko</button>
        </div>
    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen py-6">
    <div class="container mx-auto px-4">
        
        <!-- TAB: PRODUK -->
        <div id="tab-produk" class="tab-content">
            
            <!-- Filter Kategori (Jika ada) -->
            <?php if (!empty($kategori_toko)) : ?>
            <div class="flex gap-2 overflow-x-auto hide-scroll mb-6 pb-2">
                <button class="px-4 py-1.5 rounded-full bg-primary text-white text-xs font-bold shadow-sm whitespace-nowrap">Semua</button>
                <?php foreach ($kategori_toko as $kat) : ?>
                <button class="px-4 py-1.5 rounded-full bg-white text-gray-600 border border-gray-200 text-xs font-bold whitespace-nowrap hover:border-primary hover:text-primary transition">
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
                    $link = home_url('/detail-produk/?id=' . $prod->id);
                ?>
                <a href="<?php echo esc_url($link); ?>" class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-md transition group block">
                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                        <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-bold text-gray-800 line-clamp-2 mb-1 group-hover:text-primary"><?php echo esc_html($prod->nama_produk); ?></h3>
                        <div class="font-bold text-primary text-sm">
                            Rp <?php echo number_format($prod->harga, 0, ',', '.'); ?>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-[10px] text-gray-400">
                            <span>Terjual <?php echo $prod->terjual; ?></span>
                            <span class="flex items-center gap-0.5"><i class="fas fa-star text-yellow-400"></i> <?php echo ($prod->rating_avg > 0) ? $prod->rating_avg : 'N/A'; ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
                <div class="text-center py-20 text-gray-400">
                    <i class="fas fa-box-open text-4xl mb-3"></i>
                    <p>Belum ada produk di toko ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: INFORMASI -->
        <div id="tab-info" class="tab-content hidden">
            <div class="bg-white p-6 rounded-xl border border-gray-100 max-w-2xl mx-auto shadow-sm">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Tentang Toko</h3>
                
                <div class="space-y-4 text-sm text-gray-600">
                    <div class="flex gap-3">
                        <i class="fas fa-map-marker-alt text-gray-400 w-5 mt-0.5"></i>
                        <div>
                            <span class="font-bold text-gray-800 block">Alamat</span>
                            <?php echo esc_html($toko->alamat_lengkap); ?>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <i class="fas fa-user text-gray-400 w-5 mt-0.5"></i>
                        <div>
                            <span class="font-bold text-gray-800 block">Pemilik</span>
                            <?php echo esc_html($toko->nama_pemilik); ?>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <i class="fas fa-calendar-check text-gray-400 w-5 mt-0.5"></i>
                        <div>
                            <span class="font-bold text-gray-800 block">Bergabung Sejak</span>
                            <?php echo date('d M Y', strtotime($toko->created_at)); ?>
                        </div>
                    </div>
                </div>

                <?php if($toko->url_gmaps): ?>
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <a href="<?php echo esc_url($toko->url_gmaps); ?>" target="_blank" class="text-primary font-bold hover:underline flex items-center gap-2">
                        <i class="fas fa-external-link-alt"></i> Lihat Lokasi di Peta
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('border-primary', 'text-primary');
        el.classList.add('border-transparent');
    });

    // Show active
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    // Highlight button (perlu logic target element yg benar jika pakai onclick inline sederhana, 
    // tapi untuk simpelnya update class manual via JS logic di button masing-masing lebih baik atau querySelector)
    event.target.classList.remove('border-transparent');
    event.target.classList.add('border-primary', 'text-primary');
}
</script>

<?php get_footer(); ?>