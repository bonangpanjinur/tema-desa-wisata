<?php
/**
 * Template part for displaying Produk Card
 * Data Source: Object row dari tabel wp_dw_produk
 */

$produk = $args['data'] ?? null;
if ( ! $produk ) return;

// Setup Variabel
$slug_produk = !empty($produk->slug) ? $produk->slug : sanitize_title($produk->nama_produk);
$link_detail = home_url('/produk/' . $slug_produk);
$image_url   = !empty($produk->foto_utama) ? esc_url($produk->foto_utama) : 'https://via.placeholder.com/400x400?text=Produk';
$nama_produk = esc_html($produk->nama_produk);
$harga       = tema_dw_format_rupiah($produk->harga ?? 0);
$stok        = intval($produk->stok ?? 0);
$terjual     = intval($produk->terjual ?? 0);
$rating      = floatval($produk->rating_avg ?? 0);
$kategori    = !empty($produk->kategori) ? esc_html($produk->kategori) : 'Umum';

// Logika Lokasi
$lokasi = 'Desa Wisata'; 
if (!empty($produk->nama_desa)) $lokasi = $produk->nama_desa;
elseif (!empty($produk->nama_toko)) $lokasi = $produk->nama_toko;
elseif (!empty($produk->kabupaten_kota)) $lokasi = $produk->kabupaten_kota;

if (strlen($lokasi) > 20) $lokasi = substr($lokasi, 0, 18) . '...';
?>

<div class="group bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg hover:border-blue-300 transition-all duration-300 overflow-hidden flex flex-col h-full relative">
    
    <!-- Link Utama (Klik Card ke Detail) -->
    <a href="<?php echo esc_url($link_detail); ?>" class="absolute inset-0 z-10" aria-label="<?php echo $nama_produk; ?>"></a>

    <!-- Gambar -->
    <div class="relative w-full aspect-square bg-gray-100 overflow-hidden">
        <?php 
        // Gunakan thumbnail jika ID tersedia, jika tidak gunakan URL langsung
        if (isset($produk->id_post) && has_post_thumbnail($produk->id_post)) {
            echo get_the_post_thumbnail($produk->id_post, 'dw-card-thumb', array(
                'class' => 'w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500',
                'loading' => 'lazy',
                'alt' => $nama_produk
            ));
        } else {
            ?>
            <img src="<?php echo $image_url; ?>" 
                 alt="<?php echo $nama_produk; ?>" 
                 class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500"
                 loading="lazy">
            <?php
        }
        ?>
        
        <?php if ($stok < 1) : ?>
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px] flex items-center justify-center z-20">
                <span class="bg-red-600 text-white text-xs font-bold px-3 py-1.5 rounded-full uppercase">Stok Habis</span>
            </div>
        <?php endif; ?>
          <div class="absolute top-2 left-2 z-20">
            <span class="bg-white/90 backdrop-blur text-gray-700 text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                <?php echo $kategori; ?>
            </span>
        </div>

        <!-- Favorite Button -->
        <div class="absolute top-2 right-2 z-20">
            <?php 
            $is_fav = false;
            if (is_user_logged_in()) {
                if (!class_exists('DW_Favorites')) {
                    require_once WP_PLUGIN_DIR . '/desa-wisata-core/includes/class-dw-favorites.php';
                }
                $fav_obj = new DW_Favorites();
                $is_fav = $fav_obj->is_favorited(get_current_user_id(), $produk->id, 'produk');
            }
            ?>
            <button type="button" 
                    class="js-toggle-favorite w-8 h-8 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow-sm hover:bg-white transition-all"
                    data-id="<?php echo $produk->id; ?>"
                    data-type="produk">
                <i class="<?php echo $is_fav ? 'fas text-red-500' : 'far'; ?> fa-heart text-xs"></i>
            </button>
        </div></div>

    <!-- Konten -->
    <div class="p-3 flex flex-col flex-grow">
        <div class="flex items-center justify-between text-[10px] text-gray-500 mb-1">
            <div class="flex items-center gap-1">
                <i class="fas fa-map-marker-alt text-red-400"></i> 
                <span class="truncate max-w-[80px]"><?php echo esc_html($lokasi); ?></span>
            </div>
            <div class="flex items-center gap-1">
                <i class="fas fa-star text-yellow-400"></i>
                <span class="font-medium text-gray-700"><?php echo ($rating > 0) ? number_format($rating, 1) : '0'; ?></span>
            </div>
        </div>

        <h3 class="text-sm font-bold text-gray-800 leading-snug mb-1 line-clamp-2 min-h-[2.5em] group-hover:text-blue-600 transition-colors">
            <?php echo $nama_produk; ?>
        </h3>

        <div class="mt-auto"></div>

        <div class="flex items-end justify-between pt-2 border-t border-gray-50 mt-1">
            <div class="flex flex-col">
                <span class="text-[10px] text-gray-400">Harga</span>
                <div class="text-base font-bold text-primary leading-none"><?php echo esc_html($harga); ?></div>
            </div>

            <!-- TOMBOL ADD TO CART (DIPERBAIKI) -->
            <!-- Kita hapus onclick JS, ganti dengan class 'js-add-to-cart' dan data-id -->
            <?php if ($stok > 0) : ?>
            <button type="button" 
                    class="js-add-to-cart w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-all shadow-sm hover:shadow-md z-20 relative group/btn"
                    data-id="<?php echo $produk->id; ?>"
                    title="Tambah ke Keranjang">
                <i class="fas fa-cart-plus text-xs transform group-hover/btn:scale-110 transition-transform pointer-events-none"></i>
            </button>
            <?php else: ?>
            <button disabled class="w-8 h-8 rounded-full bg-gray-100 text-gray-300 cursor-not-allowed flex items-center justify-center z-20 relative">
                <i class="fas fa-ban text-xs"></i>
            </button>
            <?php endif; ?>
        </div>
        
        <div class="text-[10px] text-gray-400 text-right mt-1">
            <?php echo $terjual; ?> Terjual
        </div>
    </div>
</div>