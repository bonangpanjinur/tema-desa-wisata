<?php
/**
 * Template Name: Halaman Favorit
 * Description: Menampilkan daftar wishlist user.
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode($_SERVER['REQUEST_URI'])));
    exit;
}

get_header();

global $wpdb;
$user_id = get_current_user_id();
$table_wishlist = $wpdb->prefix . 'dw_favorites';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_desa     = $wpdb->prefix . 'dw_desa';

// Query Ambil Wishlist User (Khusus Wisata)
$wishlist_items = $wpdb->get_results($wpdb->prepare("
    SELECT w.*, d.nama_desa, d.kabupaten, wl.id as wishlist_id 
    FROM $table_wishlist wl
    JOIN $table_wisata w ON wl.object_id = w.id
    LEFT JOIN $table_desa d ON w.id_desa = d.id
    WHERE wl.user_id = %d AND wl.object_type = 'wisata'
    ORDER BY wl.created_at DESC
", $user_id));

// Helper warna kategori (Sama dengan arsip)
function get_cat_color_fav($cat) {
    $colors = [
        'Alam' => 'bg-green-100 text-green-700 border-green-200',
        'Budaya' => 'bg-orange-100 text-orange-700 border-orange-200',
        'Religi' => 'bg-purple-100 text-purple-700 border-purple-200',
        'Kuliner' => 'bg-red-100 text-red-700 border-red-200',
        'Edukasi' => 'bg-blue-100 text-blue-700 border-blue-200'
    ];
    return isset($colors[$cat]) ? $colors[$cat] : 'bg-gray-100 text-gray-700 border-gray-200';
}
?>

<div class="bg-[#FAFAFA] min-h-screen font-sans text-gray-800 pb-20">
    
    <!-- HEADER -->
    <div class="bg-white border-b border-gray-100 sticky top-0 z-40 shadow-sm/50">
        <div class="container mx-auto px-4 py-4 flex items-center gap-3">
            <a href="<?php echo home_url('/akun-saya'); ?>" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                <i class="fas fa-arrow-left text-gray-600"></i>
            </a>
            <h1 class="text-xl font-bold text-gray-900">Wisata Favorit Saya</h1>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="container mx-auto px-4 py-8">
        
        <?php if ($wishlist_items) : ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($wishlist_items as $w) : 
                    $link_w = !empty($w->slug) ? home_url('/wisata/detail/' . $w->slug) : home_url('/detail-wisata/?id=' . $w->id);
                    $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/600x400?text=Wisata';
                    $rating = ($w->rating_avg > 0) ? $w->rating_avg : 'Baru';
                    $lokasi = !empty($w->nama_desa) ? 'Desa ' . $w->nama_desa : $w->kabupaten;
                    $cat_class = get_cat_color_fav($w->kategori);
                ?>
                
                <!-- CARD (Sama dengan Arsip) -->
                <div class="group bg-white rounded-2xl overflow-hidden hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 flex flex-col h-full border border-gray-100 relative wishlist-item-<?php echo $w->id; ?>">
                    
                    <!-- Image -->
                    <div class="relative aspect-[4/3] overflow-hidden bg-gray-200">
                        <a href="<?php echo esc_url($link_w); ?>">
                            <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110" alt="<?php echo esc_attr($w->nama_wisata); ?>">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>
                        </a>

                        <!-- Remove Button -->
                        <button onclick="removeWishlist(<?php echo $w->id; ?>)" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 backdrop-blur-md flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition shadow-sm z-10" title="Hapus dari Favorit">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>

                        <?php if(isset($w->kategori)): ?>
                        <div class="absolute top-3 left-3 pointer-events-none">
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border shadow-sm <?php echo $cat_class; ?> bg-opacity-90 backdrop-blur-sm">
                                <?php echo esc_html($w->kategori); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content -->
                    <div class="p-4 flex flex-col flex-1">
                        <div class="flex items-center justify-between mb-2 text-xs">
                            <div class="flex items-center gap-1 text-yellow-500 font-bold bg-yellow-50 px-2 py-0.5 rounded-md">
                                <i class="fas fa-star"></i> <?php echo $rating; ?>
                            </div>
                            <div class="flex items-center gap-1 text-gray-500 font-medium truncate max-w-[50%]">
                                <i class="fas fa-map-marker-alt text-green-500"></i> 
                                <span class="truncate"><?php echo esc_html($lokasi); ?></span>
                            </div>
                        </div>

                        <a href="<?php echo esc_url($link_w); ?>" class="block">
                            <h3 class="font-bold text-gray-900 text-lg leading-snug mb-3 line-clamp-2 group-hover:text-green-600 transition">
                                <?php echo esc_html($w->nama_wisata); ?>
                            </h3>
                        </a>

                        <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex items-center justify-between">
                            <span class="text-green-600 font-bold text-base">
                                <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                            </span>
                            <a href="<?php echo esc_url($link_w); ?>" class="text-xs font-semibold text-gray-400 group-hover:text-green-600 transition flex items-center gap-1">
                                Detail <i class="fas fa-chevron-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-gray-100 text-center shadow-sm">
                <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mb-6">
                    <i class="far fa-heart text-4xl text-red-300"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada favorit</h3>
                <p class="text-gray-500 max-w-xs mx-auto mb-8 text-sm">Simpan wisata yang ingin Anda kunjungi di sini.</p>
                <a href="<?php echo home_url('/wisata'); ?>" class="px-8 py-3 bg-green-600 text-white font-bold rounded-full hover:bg-green-700 transition shadow-lg shadow-green-200 text-sm">
                    Jelajahi Wisata
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function removeWishlist(id) {
    if(!confirm('Hapus wisata ini dari favorit?')) return;

    jQuery.post(dw_ajax.ajax_url, {
        action: 'dw_toggle_favorite',
        object_id: id,
        type: 'wisata'
    }, function(response) {
        if(response.success) {
            // Hapus elemen card dari DOM dengan animasi
            const card = document.querySelector('.wishlist-item-' + id);
            if(card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    // Jika habis, reload untuk show empty state
                    if(document.querySelectorAll('[class*="wishlist-item-"]').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            alert('Gagal menghapus.');
        }
    });
}
</script>

<?php get_footer(); ?>