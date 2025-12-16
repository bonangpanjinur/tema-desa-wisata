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

// 1. Tangkap Slug dari URL
$slug_toko = get_query_var('dw_slug_toko');
$toko_id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

$toko = null;

// 2. Query Data Toko
if (!empty($slug_toko)) {
    $toko = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, d.nama_desa 
        FROM $table_pedagang p
        LEFT JOIN $table_desa d ON p.id_desa = d.id
        WHERE p.slug_toko = %s AND p.status_akun = 'aktif'
    ", $slug_toko));
} elseif ($toko_id_param > 0) {
    $toko = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, d.nama_desa 
        FROM $table_pedagang p
        LEFT JOIN $table_desa d ON p.id_desa = d.id
        WHERE p.id = %d AND p.status_akun = 'aktif'
    ", $toko_id_param));
}

// 3. Not Found Handler
if (!$toko) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4"><i class="fas fa-store-slash text-4xl text-gray-300"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Toko Tidak Ditemukan</h1>';
    echo '<p class="text-gray-500 mb-6">Toko yang Anda cari mungkin telah tutup atau tautan rusak.</p>';
    echo '<a href="'.home_url('/').'" class="bg-primary text-white px-6 py-2 rounded-full font-bold hover:bg-green-700 transition shadow-lg shadow-primary/30">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 4. Ambil Produk Toko
$produk_list = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM $table_produk 
    WHERE id_pedagang = %d AND status = 'aktif' 
    ORDER BY created_at DESC
", $toko->id));

// Kategori Filter
$kategori_toko = [];
foreach ($produk_list as $prod) {
    if (!empty($prod->kategori)) {
        $kategori_toko[$prod->kategori] = $prod->kategori;
    }
}

// Data Pendukung
$foto_profil = !empty($toko->foto_profil) ? $toko->foto_profil : 'https://via.placeholder.com/150';
$foto_sampul = !empty($toko->foto_sampul) ? $toko->foto_sampul : 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=80';
$wa_link = 'https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $toko->nomor_wa));
$rating_toko = isset($toko->rating_toko) ? $toko->rating_toko : 0;
?>

<div class="bg-gray-50 min-h-screen pb-10">
    
    <!-- === HERO HEADER === -->
    <div class="relative">
        <!-- Cover Image -->
        <div class="h-48 md:h-64 lg:h-80 w-full overflow-hidden relative group">
            <img src="<?php echo esc_url($foto_sampul); ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
        </div>

        <!-- Toko Info Container -->
        <div class="container mx-auto px-4 relative -mt-16 md:-mt-24 z-10">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-4 md:p-6 flex flex-col md:flex-row items-start md:items-end gap-6">
                
                <!-- Avatar -->
                <div class="relative flex-shrink-0 mx-auto md:mx-0 -mt-16 md:-mt-0">
                    <div class="w-28 h-28 md:w-32 md:h-32 rounded-2xl bg-white p-1 shadow-lg overflow-hidden border border-gray-100">
                        <img src="<?php echo esc_url($foto_profil); ?>" class="w-full h-full object-cover rounded-xl">
                    </div>
                    <?php if($toko->status_akun == 'aktif'): ?>
                    <div class="absolute -bottom-2 -right-2 bg-blue-500 text-white w-8 h-8 rounded-full flex items-center justify-center border-2 border-white shadow-sm" title="Terverifikasi">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Detail Text -->
                <div class="flex-1 text-center md:text-left w-full">
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-1 flex flex-col md:flex-row items-center md:items-end gap-2">
                        <?php echo esc_html($toko->nama_toko); ?>
                        <span class="text-xs font-normal bg-green-100 text-green-700 px-2 py-0.5 rounded-md border border-green-200">Online</span>
                    </h1>
                    
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-sm text-gray-500 mb-4 md:mb-0">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-map-marker-alt text-red-500"></i>
                            <?php echo esc_html($toko->nama_desa ?: 'Desa Wisata'); ?>
                        </span>
                        <span class="hidden md:inline text-gray-300">|</span>
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-star text-yellow-400"></i> 
                            <span class="font-bold text-gray-800"><?php echo number_format($rating_toko, 1); ?></span> Rating
                        </span>
                        <span class="hidden md:inline text-gray-300">|</span>
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-box text-primary"></i> 
                            <span class="font-bold text-gray-800"><?php echo count($produk_list); ?></span> Produk
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto mt-2 md:mt-0">
                    <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link toko disalin!');" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition flex items-center justify-center gap-2">
                        <i class="fas fa-share-alt"></i> <span class="md:hidden">Bagikan</span>
                    </button>
                    <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="px-6 py-2.5 rounded-xl bg-green-600 text-white font-bold hover:bg-green-700 shadow-lg shadow-green-200 transition flex items-center justify-center gap-2">
                        <i class="fab fa-whatsapp text-lg"></i> Hubungi Penjual
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- === TABS & CONTENT === -->
    <div class="container mx-auto px-4 mt-8">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Kiri (Info Sticky) -->
            <div class="w-full lg:w-1/4 hidden lg:block">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4 pb-4 border-b border-gray-100">Tentang Toko</h3>
                    
                    <div class="space-y-4 text-sm text-gray-600">
                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Pemilik</span>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-user text-xs"></i></div>
                                <span class="font-medium text-gray-800"><?php echo esc_html($toko->nama_pemilik); ?></span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Bergabung</span>
                            <div class="flex items-center gap-2">
                                <i class="far fa-calendar-alt text-gray-400"></i>
                                <span><?php echo date('d M Y', strtotime($toko->created_at)); ?></span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase mb-1">Alamat</span>
                            <p class="leading-relaxed"><?php echo esc_html($toko->alamat_lengkap); ?></p>
                        </div>
                    </div>

                    <?php if($toko->url_gmaps): ?>
                    <a href="<?php echo esc_url($toko->url_gmaps); ?>" target="_blank" class="mt-6 flex items-center justify-center w-full py-2.5 rounded-lg border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 text-xs gap-2 transition">
                        <i class="fas fa-map-marked-alt text-red-500"></i> Lihat di Peta
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Konten Utama (Produk) -->
            <div class="w-full lg:w-3/4">
                
                <!-- Mobile Tabs -->
                <div class="lg:hidden flex gap-4 overflow-x-auto hide-scroll mb-6 border-b border-gray-200 pb-1">
                    <button class="px-4 py-2 text-sm font-bold border-b-2 border-primary text-primary whitespace-nowrap">Produk</button>
                    <button class="px-4 py-2 text-sm font-bold border-b-2 border-transparent text-gray-500 whitespace-nowrap" onclick="alert('Info lengkap toko tersedia di versi desktop atau scroll ke bawah.')">Info Toko</button>
                </div>

                <!-- Filter Kategori -->
                <?php if (!empty($kategori_toko)) : ?>
                <div class="flex gap-2 overflow-x-auto hide-scroll mb-6 pb-2 snap-x">
                    <button class="snap-start px-4 py-1.5 rounded-full bg-primary text-white text-xs font-bold shadow-sm whitespace-nowrap filter-btn transition" onclick="filterProduk('all', this)">Semua Produk</button>
                    <?php foreach ($kategori_toko as $kat) : ?>
                    <button class="snap-start px-4 py-1.5 rounded-full bg-white text-gray-600 border border-gray-200 text-xs font-bold whitespace-nowrap hover:border-primary hover:text-primary transition filter-btn" onclick="filterProduk('<?php echo esc_attr(sanitize_title($kat)); ?>', this)">
                        <?php echo esc_html($kat); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Produk Grid -->
                <?php if ($produk_list) : ?>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($produk_list as $prod) : 
                        $img = !empty($prod->foto_utama) ? $prod->foto_utama : 'https://via.placeholder.com/300';
                        $link = home_url('/produk/detail/' . $prod->slug);
                        $cat_slug = sanitize_title($prod->kategori);
                        $is_habis = $prod->stok < 1;
                    ?>
                    <div class="product-card-item bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition duration-300 group flex flex-col relative" data-category="<?php echo esc_attr($cat_slug); ?>">
                        
                        <a href="<?php echo esc_url($link); ?>" class="block relative aspect-square bg-gray-100 overflow-hidden">
                            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                            <?php if($is_habis): ?>
                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                    <span class="text-white font-bold text-xs border border-white px-2 py-1 rounded">HABIS</span>
                                </div>
                            <?php else: ?>
                                <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded-lg text-[10px] font-bold shadow-sm text-gray-600 opacity-0 group-hover:opacity-100 transition transform translate-y-2 group-hover:translate-y-0">
                                    Stok: <?php echo $prod->stok; ?>
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="p-3 flex flex-col flex-1">
                            <div class="text-[10px] text-gray-400 mb-1 uppercase tracking-wider font-semibold"><?php echo esc_html($prod->kategori ?: 'Umum'); ?></div>
                            <h3 class="text-sm font-bold text-gray-800 line-clamp-2 mb-2 group-hover:text-primary transition min-h-[2.5em]">
                                <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($prod->nama_produk); ?></a>
                            </h3>
                            
                            <div class="mt-auto flex items-end justify-between">
                                <div>
                                    <div class="text-primary font-bold">Rp <?php echo number_format($prod->harga, 0, ',', '.'); ?></div>
                                    <div class="text-[10px] text-gray-400 mt-0.5"><i class="fas fa-star text-yellow-400"></i> <?php echo ($prod->rating_avg > 0) ? $prod->rating_avg : '4.5'; ?> | Terjual <?php echo $prod->terjual; ?></div>
                                </div>
                                
                                <?php if(!$is_habis): ?>
                                <button class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-primary hover:text-white transition shadow-sm active:scale-95 btn-add-cart-mini" data-product-id="<?php echo $prod->id; ?>">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                    <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl border border-dashed border-gray-200">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-box-open text-4xl text-gray-300"></i>
                        </div>
                        <h3 class="font-bold text-gray-800">Belum Ada Produk</h3>
                        <p class="text-gray-500 text-sm">Toko ini belum menambahkan produk apapun.</p>
                    </div>
                <?php endif; ?>
            
            </div>
        </div>
    </div>
</div>

<script>
// Filter Script
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
            item.style.display = 'flex'; // Grid item usually display flex/block
        } else {
            item.style.display = 'none';
        }
    });
}

// Simple Add to Cart for Mini Button
jQuery(document).ready(function($) {
    $('.btn-add-cart-mini').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin text-xs"></i>');
        
        $.post(dw_ajax.ajax_url, {
            action: 'dw_theme_add_to_cart',
            nonce: dw_ajax.nonce,
            product_id: btn.data('product-id'),
            quantity: 1,
            is_custom_db: 1 
        }, function(res) {
            if(res.success) {
                btn.removeClass('bg-gray-100 text-gray-600').addClass('bg-green-600 text-white').html('<i class="fas fa-check text-xs"></i>');
                if(res.data.count) {
                    $('#header-cart-count, #header-cart-count-mobile').text(res.data.count).removeClass('hidden').addClass('flex');
                }
                setTimeout(() => {
                    btn.prop('disabled', false).removeClass('bg-green-600 text-white').addClass('bg-gray-100 text-gray-600').html(originalHtml);
                }, 1500);
            } else {
                alert('Gagal: ' + (res.data.message || 'Error'));
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>

<?php get_footer(); ?>