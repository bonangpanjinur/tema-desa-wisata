<?php
/**
 * Template Name: Detail Produk Custom
 */

get_header();

global $wpdb;
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

$produk = null;

// 1. Tangkap Slug (Rewrite Rule)
$slug = get_query_var('dw_slug');

// 2. Tangkap ID (Fallback)
$id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 3. Query Data (Update: Select ped.id as pedagang_id)
if (!empty($slug)) {
    $produk = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, ped.id as pedagang_id, ped.nama_toko, ped.alamat_lengkap as alamat_toko, ped.nomor_wa, ped.foto_profil as foto_toko
        FROM $table_produk p
        LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id
        WHERE p.slug = %s AND p.status = 'aktif'
    ", $slug));
} elseif ($id_param > 0) {
    $produk = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, ped.id as pedagang_id, ped.nama_toko, ped.alamat_lengkap as alamat_toko, ped.nomor_wa, ped.foto_profil as foto_toko
        FROM $table_produk p
        LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id
        WHERE p.id = %d AND p.status = 'aktif'
    ", $id_param));
}

// 3. Not Found Handler
if (!$produk) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="text-6xl text-gray-200 mb-4"><i class="fas fa-box-open"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Produk Tidak Ditemukan</h1>';
    echo '<a href="'.home_url('/').'" class="text-primary font-bold hover:underline">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// Data Formatting
$harga = $produk->harga;
$stok = $produk->stok;
$terjual = $produk->terjual;
$img_main = !empty($produk->foto_utama) ? $produk->foto_utama : 'https://via.placeholder.com/500';
$kategori = $produk->kategori ?: 'Umum';
$nama_toko = $produk->nama_toko ?: 'Toko Desa';
$foto_toko = !empty($produk->foto_toko) ? $produk->foto_toko : 'https://via.placeholder.com/100?text=Toko';

// Link Profil Toko
$link_toko = home_url('/profil-toko/?id=' . $produk->pedagang_id);

// === LOGIKA GALERI FOTO ===
$gallery_images = [];
if (!empty($img_main)) {
    $gallery_images[] = $img_main;
}
if (!empty($produk->galeri)) {
    $decoded_gallery = json_decode($produk->galeri);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_gallery)) {
        foreach($decoded_gallery as $g_img) {
            if ($g_img != $img_main) {
                $gallery_images[] = $g_img;
            }
        }
    }
}
?>

<div class="bg-gray-50 min-h-screen py-4 md:py-8 pb-24">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb -->
        <div class="text-xs text-gray-500 mb-4 flex items-center gap-2">
            <a href="<?php echo home_url('/'); ?>" class="hover:text-primary">Beranda</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-800 font-bold truncate max-w-[200px]"><?php echo esc_html($produk->nama_produk); ?></span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex flex-col md:flex-row">
                
                <!-- KOLOM KIRI: GALERI FOTO -->
                <div class="w-full md:w-1/2 lg:w-5/12 bg-white p-4 lg:p-6">
                    <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden relative border border-gray-100 mb-4 group cursor-zoom-in">
                        <img id="main-product-image" src="<?php echo esc_url($img_main); ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="Foto Utama">
                        <?php if($produk->kondisi == 'bekas'): ?>
                            <span class="absolute top-2 right-2 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">Preloved</span>
                        <?php endif; ?>
                    </div>

                    <?php if (count($gallery_images) > 1): ?>
                    <div class="flex gap-2 overflow-x-auto hide-scroll pb-2 snap-x">
                        <?php foreach($gallery_images as $index => $img_url): ?>
                        <button onclick="changeMainImage('<?php echo esc_url($img_url); ?>', this)" 
                                class="w-16 h-16 md:w-20 md:h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 <?php echo ($index === 0) ? 'border-primary' : 'border-transparent'; ?> hover:border-primary focus:border-primary transition p-0.5 bg-white snap-start thumbnail-btn">
                            <img src="<?php echo esc_url($img_url); ?>" class="w-full h-full object-cover rounded-md" alt="Thumbnail">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- KOLOM KANAN: INFO PRODUK -->
                <div class="w-full md:w-1/2 lg:w-7/12 p-6 md:p-8 lg:pl-0">
                    
                    <div class="mb-4">
                        <span class="text-xs font-bold text-primary bg-primary-light px-2 py-1 rounded-md mb-2 inline-block uppercase tracking-wider">
                            <?php echo esc_html($kategori); ?>
                        </span>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight"><?php echo esc_html($produk->nama_produk); ?></h1>
                    </div>
                    
                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-6 pb-6 border-b border-gray-100">
                        <span class="flex items-center gap-1 text-yellow-500 font-bold">
                            <i class="fas fa-star"></i> <?php echo ($produk->rating_avg > 0) ? $produk->rating_avg : 'Baru'; ?>
                        </span>
                        <span class="w-px h-4 bg-gray-300"></span>
                        <span>Terjual <strong class="text-gray-800"><?php echo $terjual; ?></strong></span>
                        <span class="w-px h-4 bg-gray-300"></span>
                        <span>Stok <strong class="text-gray-800"><?php echo $stok; ?></strong></span>
                    </div>

                    <div class="text-3xl font-bold text-primary mb-6">
                        Rp <?php echo number_format($harga, 0, ',', '.'); ?>
                    </div>

                    <!-- Info Penjual (LINK PROFIL TOKO) -->
                    <a href="<?php echo esc_url($link_toko); ?>" class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100 mb-6 transition hover:border-primary hover:bg-white hover:shadow-md cursor-pointer group">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-primary text-xl border border-gray-200 group-hover:border-primary transition overflow-hidden">
                            <img src="<?php echo esc_url($foto_toko); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-gray-800 text-sm flex items-center gap-2 group-hover:text-primary transition">
                                <?php echo esc_html($nama_toko); ?>
                                <i class="fas fa-check-circle text-blue-500 text-xs" title="Terverifikasi"></i>
                            </div>
                            <div class="text-xs text-gray-500 truncate flex items-center gap-1">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo esc_html($produk->alamat_toko ?: 'Lokasi Toko'); ?>
                            </div>
                        </div>
                        <div class="text-gray-400 group-hover:text-primary">
                            <span class="text-xs font-bold mr-1">Kunjungi Toko</span>
                            <i class="fas fa-chevron-right text-xs"></i>
                        </div>
                    </a>

                    <!-- Detail Spesifikasi -->
                    <div class="mb-6">
                        <h3 class="font-bold text-gray-800 mb-3 text-sm border-l-4 border-primary pl-3">Spesifikasi Produk</h3>
                        <div class="grid grid-cols-2 gap-y-2 text-sm text-gray-600 bg-white p-4 rounded-xl border border-gray-100">
                            <p><span class="text-gray-400 block text-xs mb-0.5">Kondisi</span> <?php echo ucfirst($produk->kondisi); ?></p>
                            <p><span class="text-gray-400 block text-xs mb-0.5">Berat Satuan</span> <?php echo $produk->berat_gram; ?> gram</p>
                            <p><span class="text-gray-400 block text-xs mb-0.5">Kategori</span> <?php echo $kategori; ?></p>
                            <p><span class="text-gray-400 block text-xs mb-0.5">Etalase</span> Utama</p>
                        </div>
                    </div>

                    <!-- Deskripsi Lengkap -->
                    <div class="mb-8">
                        <h3 class="font-bold text-gray-800 mb-3 text-sm border-l-4 border-primary pl-3">Deskripsi Lengkap</h3>
                        <div class="prose prose-sm text-gray-600 max-w-none leading-relaxed">
                            <?php echo wpautop(esc_html($produk->deskripsi)); ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- FIXED BOTTOM BAR (Mobile Action) -->
<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-50 md:hidden">
    <div class="flex gap-3">
        <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', $produk->nomor_wa); ?>?text=Halo%2C%20saya%20tertarik%20dengan%20produk%20<?php echo urlencode($produk->nama_produk); ?>" class="flex-1 border border-green-600 text-green-600 font-bold py-3 rounded-xl text-center text-sm flex items-center justify-center gap-2 active:bg-green-50">
            <i class="fab fa-whatsapp text-lg"></i> Chat
        </a>
        <button class="flex-1 bg-primary text-white font-bold py-3 rounded-xl text-center text-sm shadow-md hover:bg-green-700 active:scale-95 transition btn-add-cart-single" data-id="<?php echo $produk->id; ?>">
            <i class="fas fa-plus mr-1"></i> Keranjang
        </button>
    </div>
</div>

<script>
function changeMainImage(url, btn) {
    const mainImg = document.getElementById('main-product-image');
    mainImg.style.opacity = '0.5';
    setTimeout(() => {
        mainImg.src = url;
        mainImg.style.opacity = '1';
    }, 150);
    document.querySelectorAll('.thumbnail-btn').forEach(b => {
        b.classList.remove('border-primary');
        b.classList.add('border-transparent');
    });
    btn.classList.remove('border-transparent');
    btn.classList.add('border-primary');
}

jQuery(document).ready(function($) {
    $('.btn-add-cart-single').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const id = btn.data('id');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        
        $.post(dw_ajax.ajax_url, {
            action: 'dw_theme_add_to_cart',
            nonce: dw_ajax.nonce,
            product_id: id,
            quantity: 1,
            is_custom_db: 1 
        }, function(res) {
            if(res.success) {
                btn.removeClass('bg-primary').addClass('bg-green-600').html('<i class="fas fa-check"></i> Masuk Keranjang');
                if(res.data.count) {
                    $('#header-cart-count, #header-cart-count-mobile').text(res.data.count).removeClass('hidden').addClass('flex');
                }
                setTimeout(() => {
                    btn.prop('disabled', false).removeClass('bg-green-600').addClass('bg-primary').html(originalText);
                }, 2000);
            } else {
                alert('Gagal: ' + (res.data.message || 'Error'));
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<?php get_footer(); ?>