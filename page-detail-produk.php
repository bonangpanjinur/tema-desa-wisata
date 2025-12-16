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

// 3. Query Data (AMAN)
if (!empty($slug)) {
    $produk = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, ped.nama_toko, ped.alamat_lengkap as alamat_toko, ped.nomor_wa 
        FROM $table_produk p
        LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id
        WHERE p.slug = %s AND p.status = 'aktif'
    ", $slug));
} elseif ($id_param > 0) {
    $produk = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, ped.nama_toko, ped.alamat_lengkap as alamat_toko, ped.nomor_wa 
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
                <div class="w-full md:w-1/2 lg:w-2/5 bg-white p-4">
                    <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden relative border border-gray-100">
                        <img src="<?php echo esc_url($img_main); ?>" class="w-full h-full object-cover">
                        <?php if($produk->kondisi == 'bekas'): ?>
                            <span class="absolute top-2 right-2 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded">Preloved</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="w-full md:w-1/2 lg:w-3/5 p-6 md:p-8">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2 leading-tight"><?php echo esc_html($produk->nama_produk); ?></h1>
                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-4 pb-4 border-b border-gray-100">
                        <span class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i> <?php echo ($produk->rating_avg > 0) ? $produk->rating_avg : 'Baru'; ?>
                        </span>
                        <span class="w-px h-4 bg-gray-300"></span>
                        <span>Terjual <?php echo $terjual; ?></span>
                        <span class="w-px h-4 bg-gray-300"></span>
                        <span class="text-primary font-medium"><?php echo esc_html($kategori); ?></span>
                    </div>
                    <div class="text-3xl font-bold text-primary mb-6">
                        Rp <?php echo number_format($harga, 0, ',', '.'); ?>
                    </div>
                    <div class="mb-6">
                        <h3 class="font-bold text-gray-800 mb-2 text-sm">Detail Produk</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><span class="w-24 inline-block text-gray-400">Kondisi:</span> <?php echo ucfirst($produk->kondisi); ?></p>
                            <p><span class="w-24 inline-block text-gray-400">Berat:</span> <?php echo $produk->berat_gram; ?> gram</p>
                            <p><span class="w-24 inline-block text-gray-400">Stok:</span> <?php echo $stok; ?> pcs</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100 mb-6">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-primary text-xl border border-gray-200">
                            <i class="fas fa-store"></i>
                        </div>
                        <div>
                            <div class="font-bold text-gray-800 text-sm"><?php echo esc_html($nama_toko); ?></div>
                            <div class="text-xs text-gray-500 truncate max-w-[200px]"><?php echo esc_html($produk->alamat_toko ?: 'Lokasi Toko'); ?></div>
                        </div>
                    </div>
                    <div class="mb-8">
                        <h3 class="font-bold text-gray-800 mb-2 text-sm">Deskripsi</h3>
                        <div class="prose text-sm text-gray-600 max-w-none">
                            <?php echo wpautop(esc_html($produk->deskripsi)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg z-50 md:hidden">
    <div class="flex gap-3">
        <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', $produk->nomor_wa); ?>" class="flex-1 border border-green-600 text-green-600 font-bold py-3 rounded-xl text-center text-sm flex items-center justify-center gap-2">
            <i class="fab fa-whatsapp"></i> Chat
        </a>
        <button class="flex-1 bg-primary text-white font-bold py-3 rounded-xl text-center text-sm shadow-md hover:bg-green-700 btn-add-cart-single" data-id="<?php echo $produk->id; ?>">
            + Keranjang
        </button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.btn-add-cart-single').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const id = btn.data('id');
        btn.prop('disabled', true).text('Memproses...');
        $.post(dw_ajax.ajax_url, {
            action: 'dw_theme_add_to_cart',
            nonce: dw_ajax.nonce,
            product_id: id,
            quantity: 1,
            is_custom_db: 1 
        }, function(res) {
            if(res.success) {
                alert('Berhasil masuk keranjang!');
                btn.text('Berhasil');
                setTimeout(() => btn.prop('disabled', false).text('+ Keranjang'), 2000);
            } else {
                alert('Gagal: ' + (res.data.message || 'Error'));
                btn.prop('disabled', false).text('+ Keranjang');
            }
        });
    });
});
</script>

<?php get_footer(); ?>