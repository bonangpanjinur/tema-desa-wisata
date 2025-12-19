<?php
/**
 * Template Name: Halaman Keranjang (Cart) Integrated
 * Description: Menampilkan item di keranjang dengan desain modern & grouped by Merchant.
 * Update: Kompatibilitas Database activation.php (Auto-detect column names).
 */

// 1. Pastikan Session Dimulai (Wajib Paling Atas)
if ( ! session_id() ) {
    session_start();
}

get_header(); 

// --- LOGIKA HAPUS ITEM ---
if ( isset( $_GET['remove_item'] ) && isset( $_SESSION['dw_cart'] ) ) {
    $remove_id = intval( $_GET['remove_item'] );
    
    // Loop untuk mencari dan menghapus
    foreach ( $_SESSION['dw_cart'] as $key => $item ) {
        if ( isset($item['product_id']) && $item['product_id'] == $remove_id ) {
            unset( $_SESSION['dw_cart'][$key] );
            break; 
        }
    }
    
    // Reset index array agar rapi kembali
    $_SESSION['dw_cart'] = array_values( $_SESSION['dw_cart'] );
    
    // Redirect balik ke cart untuk refresh
    wp_redirect( home_url( '/cart' ) );
    exit;
}

// --- LOGIKA AMBIL DATA ---
$cart_items = isset( $_SESSION['dw_cart'] ) ? $_SESSION['dw_cart'] : [];
$grouped_items = []; 
$total_belanja = 0;

// Validasi jika cart_items bukan array (mencegah error)
if ( ! is_array( $cart_items ) ) {
    $cart_items = [];
}

if ( ! empty( $cart_items ) ) {
    global $wpdb;
    $tbl_produk   = $wpdb->prefix . 'dw_produk';
    $tbl_wisata   = $wpdb->prefix . 'dw_wisata';
    $tbl_pedagang = $wpdb->prefix . 'dw_pedagang';
    $tbl_desa     = $wpdb->prefix . 'dw_desa';

    foreach ( $cart_items as $key => $item ) {
        // Ambil data dasar dari Session (Fallback jika DB gagal)
        $pid   = isset($item['product_id']) ? intval($item['product_id']) : 0;
        $qty   = isset($item['quantity']) ? intval($item['quantity']) : 1;
        
        $nama_produk  = isset($item['name']) ? $item['name'] : 'Produk Tanpa Nama';
        $harga        = isset($item['price']) ? floatval($item['price']) : 0;
        $foto         = isset($item['image']) ? $item['image'] : '';
        $nama_penjual = 'Desa Wisata'; // Default

        if ( $pid > 0 ) {
            // Cek detail ke DB untuk memastikan data Toko/Penjual
            // 1. Cek di tabel Produk (Barang)
            $prod_db = $wpdb->get_row( $wpdb->prepare("
                SELECT p.*, pd.nama_toko 
                FROM $tbl_produk p 
                LEFT JOIN $tbl_pedagang pd ON p.id_pedagang = pd.id 
                WHERE p.id = %d
            ", $pid) );

            if ( $prod_db ) {
                $nama_penjual = !empty($prod_db->nama_toko) ? $prod_db->nama_toko : 'Produk Desa';
                
                // Update data terbaru agar sinkron dengan DB
                if ( isset($prod_db->nama_produk) ) $nama_produk = $prod_db->nama_produk;
                if ( isset($prod_db->harga) ) $harga = $prod_db->harga;
                
                // Cek variasi nama kolom foto (Kompatibilitas activation.php)
                if ( isset( $prod_db->foto_utama ) && !empty( $prod_db->foto_utama ) ) {
                    $foto = $prod_db->foto_utama;
                } elseif ( isset( $prod_db->foto ) && !empty( $prod_db->foto ) ) {
                    $foto = $prod_db->foto;
                } elseif ( isset( $prod_db->gambar ) && !empty( $prod_db->gambar ) ) {
                    $foto = $prod_db->gambar;
                }

            } else {
                // 2. Cek di tabel Wisata (Tiket)
                // Gunakan LEFT JOIN ke tabel Desa untuk ambil nama desa
                $wisata_db = $wpdb->get_row( $wpdb->prepare("
                    SELECT w.*, d.nama_desa 
                    FROM $tbl_wisata w 
                    LEFT JOIN $tbl_desa d ON w.id_desa = d.id 
                    WHERE w.id = %d
                ", $pid) );
                
                if ( $wisata_db ) {
                    $nama_penjual = 'Wisata: ' . (isset($wisata_db->nama_desa) ? $wisata_db->nama_desa : 'Desa');
                    
                    if ( isset($wisata_db->nama_wisata) ) $nama_produk = $wisata_db->nama_wisata;
                    
                    // Cek variasi nama kolom Harga (harga_tiket vs harga)
                    if ( isset( $wisata_db->harga_tiket ) ) {
                        $harga = $wisata_db->harga_tiket;
                    } elseif ( isset( $wisata_db->harga ) ) {
                        $harga = $wisata_db->harga;
                    }

                    // Cek variasi nama kolom Foto
                    if ( isset( $wisata_db->foto_utama ) && !empty( $wisata_db->foto_utama ) ) {
                        $foto = $wisata_db->foto_utama;
                    } elseif ( isset( $wisata_db->foto ) && !empty( $wisata_db->foto ) ) {
                        $foto = $wisata_db->foto;
                    }
                }
            }
        }

        $subtotal = $harga * $qty;
        $total_belanja += $subtotal;

        // Masukkan ke grouping array
        $grouped_items[$nama_penjual][] = [
            'product_id' => $pid,
            'name'       => $nama_produk,
            'price'      => $harga,
            'image'      => $foto,
            'quantity'   => $qty,
            'subtotal'   => $subtotal,
            'cart_key'   => $key
        ];
    }
}
?>

<div class="min-h-screen bg-gray-50 py-8 lg:py-12 font-sans">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900">Keranjang Belanja</h1>
            <p class="text-gray-500 mt-2">Periksa kembali item sebelum checkout.</p>
        </div>

        <?php if ( empty( $grouped_items ) ) : ?>
            
            <!-- EMPTY STATE -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shopping-basket text-4xl text-orange-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Keranjang Kosong</h3>
                <p class="text-gray-500 mb-8">Wah, sepertinya Anda belum memilih produk menarik desa kami.</p>
                <a href="<?php echo home_url('/produk'); ?>" class="inline-flex items-center justify-center px-8 py-3 bg-orange-600 text-white font-bold rounded-xl hover:bg-orange-700 transition-colors shadow-lg shadow-orange-200">
                    <i class="fas fa-search mr-2"></i> Mulai Belanja
                </a>
            </div>

        <?php else : ?>

            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- LIST ITEM (LEFT) -->
                <div class="flex-1 space-y-6">
                    <?php foreach ( $grouped_items as $penjual => $items ) : ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <!-- Header Penjual -->
                            <div class="bg-gray-50 px-6 py-3 border-b border-gray-100 flex items-center gap-2">
                                <i class="fas fa-store text-orange-600"></i>
                                <span class="font-bold text-gray-800"><?php echo esc_html( $penjual ); ?></span>
                            </div>

                            <!-- List Produk -->
                            <div class="p-6 space-y-6">
                                <?php foreach ( $items as $item ) : ?>
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                        <!-- Gambar -->
                                        <div class="w-20 h-20 flex-shrink-0 rounded-lg bg-gray-100 overflow-hidden border border-gray-200 relative">
                                            <?php if ( !empty($item['image']) ) : ?>
                                                <img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" class="w-full h-full object-cover">
                                            <?php else : ?>
                                                <div class="w-full h-full flex items-center justify-center bg-gray-50 text-gray-300">
                                                    <i class="fas fa-image text-2xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Detail -->
                                        <div class="flex-1 w-full">
                                            <div class="flex justify-between items-start mb-1">
                                                <h4 class="font-bold text-gray-900 line-clamp-2"><?php echo esc_html( $item['name'] ); ?></h4>
                                                <a href="?remove_item=<?php echo $item['product_id']; ?>" class="text-gray-400 hover:text-red-500 transition-colors ml-2 p-1" title="Hapus Item">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                            <p class="text-orange-600 font-bold mb-3">Rp <?php echo number_format( $item['price'], 0, ',', '.' ); ?></p>
                                            
                                            <div class="inline-flex items-center bg-gray-50 rounded-lg border border-gray-200">
                                                <span class="px-3 py-1 text-xs text-gray-500 font-bold">Qty: <?php echo $item['quantity']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Divider antar item -->
                                    <?php if ( $item !== end( $items ) ) echo '<div class="border-b border-gray-100 my-4"></div>'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- SUMMARY (RIGHT) -->
                <div class="w-full lg:w-96 flex-shrink-0">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Ringkasan Belanja</h3>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Total Harga (<?php echo count( $cart_items ); ?> barang)</span>
                                <span>Rp <?php echo number_format( $total_belanja, 0, ',', '.' ); ?></span>
                            </div>
                            <div class="flex justify-between text-green-600 text-sm">
                                <span>Diskon</span>
                                <span>-</span>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-end">
                                <span class="font-bold text-gray-800">Total Belanja</span>
                                <span class="text-2xl font-bold text-orange-600">Rp <?php echo number_format( $total_belanja, 0, ',', '.' ); ?></span>
                            </div>
                        </div>

                        <a href="<?php echo home_url('/checkout'); ?>" class="block w-full py-4 bg-gray-900 hover:bg-orange-600 text-white text-center font-bold rounded-xl shadow-lg hover:shadow-orange-200 transition-all duration-300 transform hover:-translate-y-1">
                            Checkout Sekarang <i class="fas fa-arrow-right ml-2"></i>
                        </a>

                        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-400 bg-gray-50 py-2 rounded-lg">
                            <i class="fas fa-shield-alt text-green-500"></i> Pembayaran Aman & Terpercaya
                        </div>
                    </div>
                </div>

            </div>

        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>