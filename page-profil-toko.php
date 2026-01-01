<?php
/**
 * Template Name: Profil Toko Custom (Smart Dine-In System) - UI/UX Refined
 * Description: Halaman toko dengan UI/UX Modern, App-like experience, dan Smooth Interactions.
 */

get_header();

global $wpdb;
// Definisi Tabel
$table_pedagang      = $wpdb->prefix . 'dw_pedagang';
$table_produk        = $wpdb->prefix . 'dw_produk';
$table_desa          = $wpdb->prefix . 'dw_desa';
$table_transaksi     = $wpdb->prefix . 'dw_transaksi';
$table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub';
$table_items         = $wpdb->prefix . 'dw_transaksi_items';

// 1. Inisialisasi & Validasi Toko
$slug_toko = get_query_var('dw_slug_toko');
$toko = $wpdb->get_row($wpdb->prepare("
    SELECT p.*, d.nama_desa 
    FROM $table_pedagang p
    LEFT JOIN $table_desa d ON p.id_desa = d.id
    WHERE p.slug_toko = %s AND p.status_akun = 'aktif'
", $slug_toko));

// Error Page: Toko Tidak Ditemukan (UI Improved)
if (!$toko) {
    echo '<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-8 text-center font-sans">
        <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center mb-6 shadow-xl shadow-gray-200">
            <i class="fas fa-store-slash text-gray-300 text-5xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Toko Tidak Ditemukan</h2>
        <p class="text-gray-500 mb-8 max-w-xs mx-auto text-sm leading-relaxed">Toko yang Anda cari mungkin sudah tutup atau tautan tidak valid.</p>
        <a href="'.home_url('/').'" class="px-8 py-3 bg-gray-900 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-black transition-transform transform active:scale-95">Kembali ke Beranda</a>
    </div>';
    get_footer(); exit;
}

// 2. Logic QR Code (Meja)
$param_meja = isset($_GET['meja']) ? intval($_GET['meja']) : 0;
$is_scan_mode = ($param_meja > 0);

// =========================================================
// 3. HANDLER: PROSES PESANAN (Back-end logic tetap sama)
// =========================================================
$order_success = false;
$new_order_id = 0;
$order_error = '';

if (isset($_POST['dw_action']) && $_POST['dw_action'] === 'submit_dine_in') {
    if (wp_verify_nonce($_POST['dw_dinein_nonce'], 'dw_submit_dinein')) {
        
        $nama_pemesan = sanitize_text_field($_POST['nama_pemesan']);
        $nomor_meja   = sanitize_text_field($_POST['nomor_meja']);
        $metode_bayar = sanitize_text_field($_POST['metode_bayar']);
        $catatan      = sanitize_textarea_field($_POST['catatan']);
        $cart_data    = json_decode(stripslashes($_POST['cart_data']), true);

        if (empty($cart_data)) {
            $order_error = "Keranjang belanja kosong.";
        } else {
            $total_belanja = 0;
            $valid_items = [];
            
            foreach ($cart_data as $pid => $qty) {
                $prod = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_produk WHERE id = %d AND id_pedagang = %d", $pid, $toko->id));
                if ($prod && $prod->stok >= $qty) {
                    $subtotal = $prod->harga * $qty;
                    $total_belanja += $subtotal;
                    $valid_items[] = [
                        'id' => $prod->id,
                        'nama' => $prod->nama_produk,
                        'harga' => $prod->harga,
                        'qty' => $qty,
                        'subtotal' => $subtotal
                    ];
                }
            }

            if (count($valid_items) > 0) {
                // VALIDASI BUKTI BAYAR (Back-end Check)
                if ($metode_bayar === 'transfer' && empty($_FILES['bukti_bayar']['name'])) {
                    $order_error = "Mohon upload bukti pembayaran untuk metode Transfer/QRIS.";
                } else {
                    // Upload Bukti Bayar
                    $url_bukti = '';
                    $status_trx = ($metode_bayar === 'cash') ? 'menunggu_konfirmasi' : 'menunggu_pembayaran';
                    
                    if ($metode_bayar === 'transfer' && !empty($_FILES['bukti_bayar']['name'])) {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $upload = wp_handle_upload($_FILES['bukti_bayar'], ['test_form' => false]);
                        if (isset($upload['url']) && !isset($upload['error'])) {
                            $url_bukti = $upload['url'];
                            $status_trx = 'menunggu_konfirmasi'; 
                        }
                    }

                    // Insert Transaksi
                    $kode_unik = 'ORD-' . date('dmy') . '-' . rand(1000, 9999);
                    $wpdb->insert($table_transaksi, [
                        'kode_unik' => $kode_unik,
                        'id_pembeli' => 0, 
                        'total_produk' => $total_belanja,
                        'total_ongkir' => 0,
                        'biaya_layanan' => 0,
                        'total_transaksi' => $total_belanja,
                        'nama_penerima' => $nama_pemesan . " (Meja $nomor_meja)",
                        'alamat_lengkap' => "Dine In - Meja " . $nomor_meja,
                        'metode_pembayaran' => $metode_bayar,
                        'status_transaksi' => $status_trx,
                        'url_bukti_bayar' => $url_bukti,
                        'catatan_pembeli' => $catatan,
                        'created_at' => current_time('mysql')
                    ]);
                    $main_trx_id = $wpdb->insert_id;

                    $wpdb->insert($table_transaksi_sub, [
                        'id_transaksi' => $main_trx_id,
                        'id_pedagang' => $toko->id,
                        'nama_toko' => $toko->nama_toko,
                        'sub_total' => $total_belanja,
                        'ongkir' => 0,
                        'total_pesanan_toko' => $total_belanja,
                        'metode_pengiriman' => 'dine_in',
                        'kurir_nama' => 'Dine In',
                        'status_pesanan' => 'menunggu_konfirmasi',
                        'created_at' => current_time('mysql')
                    ]);
                    $sub_trx_id = $wpdb->insert_id;

                    foreach ($valid_items as $item) {
                        $wpdb->insert($table_items, [
                            'id_sub_transaksi' => $sub_trx_id,
                            'id_produk' => $item['id'],
                            'nama_produk' => $item['nama'],
                            'harga_satuan' => $item['harga'],
                            'jumlah' => $item['qty'],
                            'total_harga' => $item['subtotal']
                        ]);
                        // Update Stok
                        $wpdb->query($wpdb->prepare("UPDATE $table_produk SET stok = stok - %d, terjual = terjual + %d WHERE id = %d", $item['qty'], $item['qty'], $item['id']));
                    }
                    $order_success = true;
                    $new_order_id = $kode_unik;
                }
            } else {
                $order_error = "Stok produk habis.";
            }
        }
    }
}

// 4. Fetch Produk & Logic Data
$products = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_produk WHERE id_pedagang = %d AND status = 'aktif' ORDER BY created_at DESC", $toko->id));

// Grouping Kategori
$categories = [];
foreach ($products as $p) {
    if (!empty($p->kategori)) $categories[$p->kategori] = true;
}
$categories = array_keys($categories);

// Assets Fallback
$avatar_url = !empty($toko->foto_profil) ? $toko->foto_profil : 'https://ui-avatars.com/api/?name='.urlencode($toko->nama_toko).'&background=random&color=fff&size=200';
$banner_url = !empty($toko->foto_sampul) ? $toko->foto_sampul : 'https://source.unsplash.com/random/1200x600/?restaurant,food,cafe';

?>

<!-- INJECT FONTS & STYLES -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary: #10b981; /* Emerald 500 */
        --primary-dark: #059669;
        --dark: #111827;
        --light-bg: #F3F4F6;
        --font-main: 'Plus Jakarta Sans', sans-serif;
    }
    body { font-family: var(--font-main); background-color: var(--light-bg); -webkit-tap-highlight-color: transparent; }
    
    /* Utilities */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* Glassmorphism */
    .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.3); }
    .glass-modal { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
    
    /* Animations */
    .fade-up { animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
    @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
    
    .bounce-in { animation: bounceIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    @keyframes bounceIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

    /* Bottom Sheet Transition */
    .sheet-transition { transition: transform 0.3s cubic-bezier(0.33, 1, 0.68, 1); }
    
    /* Safe Area for iOS */
    .pb-safe { padding-bottom: env(safe-area-inset-bottom, 20px); }
    
    /* Search Input Focus */
    .search-focus:focus-within { box-shadow: 0 4px 20px -2px rgba(16, 185, 129, 0.2); border-color: var(--primary); transform: translateY(-1px); }
</style>

<!-- TOAST NOTIFICATION -->
<div id="toast-container" class="fixed top-4 left-0 right-0 z-[1100] flex justify-center px-4 pointer-events-none transition-all duration-300"></div>

<!-- QRIS ZOOM MODAL (New Feature) -->
<div id="qris-zoom-modal" class="fixed inset-0 z-[120] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/90 backdrop-blur-md transition-opacity" onclick="closeQrisZoom()"></div>
    <div class="relative bg-white p-6 rounded-3xl shadow-2xl max-w-sm w-full animate-bounce-in flex flex-col items-center">
        <!-- Close Button -->
        <button type="button" onclick="closeQrisZoom()" class="absolute -top-4 -right-4 w-10 h-10 bg-white text-gray-900 rounded-full flex items-center justify-center shadow-lg font-bold z-10 hover:bg-gray-100 transition"><i class="fas fa-times"></i></button>
        
        <!-- Modal Content -->
        <div class="text-center mb-5">
            <h3 class="font-bold text-xl text-gray-900">Scan QRIS</h3>
            <p class="text-xs text-gray-500 font-medium"><?php echo esc_html($toko->nama_toko); ?></p>
        </div>
        
        <div class="bg-white p-3 rounded-2xl border-2 border-gray-100 shadow-inner w-full flex justify-center mb-4">
            <?php if($toko->qris_image_url): ?>
            <img src="<?php echo esc_url($toko->qris_image_url); ?>" class="w-full h-auto max-h-[60vh] object-contain rounded-xl">
            <?php endif; ?>
        </div>
        
        <p class="text-center text-xs text-gray-400 leading-relaxed max-w-[80%]">
            <i class="fas fa-info-circle text-emerald-500 mr-1"></i> Screenshot atau arahkan kamera HP lain untuk membayar.
        </p>
    </div>
</div>

<!-- ========================================= -->
<!-- HALAMAN SUKSES (E-RECEIPT) -->
<!-- ========================================= -->
<?php if($order_success): ?>
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Decor bg -->
    <div class="absolute top-0 left-0 w-full h-64 bg-emerald-600 rounded-b-[3rem]"></div>
    
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden fade-up" style="animation-delay: 0.1s;">
        <!-- Ticket Hanger -->
        <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-gray-900 rounded-full z-10 border-2 border-white"></div>
        
        <div class="p-8 text-center border-b border-dashed border-gray-200">
            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6 text-emerald-600 shadow-sm animate-pulse">
                <i class="fas fa-check text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Pesanan Berhasil!</h2>
            <p class="text-gray-400 text-sm">Harap tunggu, pesanan Anda sedang disiapkan.</p>
        </div>

        <div class="p-6 bg-gray-50/50">
            <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-6 shadow-sm">
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest text-center mb-1">Kode Pesanan</p>
                <h1 class="text-3xl font-mono font-bold text-gray-800 text-center tracking-wider text-emerald-600"><?php echo esc_html($new_order_id); ?></h1>
            </div>

            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex justify-between">
                    <span>Pemesan</span>
                    <span class="font-bold text-gray-900"><?php echo esc_html($nama_pemesan); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Meja</span>
                    <span class="font-bold text-gray-900"><?php echo esc_html($nomor_meja); ?></span>
                </div>
                <div class="border-t border-dashed border-gray-200 my-2"></div>
                <div class="flex justify-between text-lg">
                    <span class="font-bold text-gray-900">Total</span>
                    <span class="font-bold text-emerald-600">Rp <?php echo number_format($total_belanja,0,',','.'); ?></span>
                </div>
            </div>
        </div>

        <div class="p-6">
            <a href="<?php echo esc_url(add_query_arg(['meja' => $nomor_meja], get_permalink())); ?>" class="block w-full bg-gray-900 text-white font-bold py-4 rounded-2xl hover:bg-black transition text-center shadow-lg transform active:scale-95">
                Pesan Lagi
            </a>
        </div>
        
        <!-- Zigzag bottom -->
        <div class="w-full h-4 bg-white relative" style="background-image: linear-gradient(135deg, white 25%, transparent 25%), linear-gradient(225deg, white 25%, transparent 25%); background-size: 20px 20px;"></div>
    </div>
</div>
<?php get_footer(); exit; endif; ?>


<!-- ========================================= -->
<!-- HALAMAN UTAMA -->
<!-- ========================================= -->

<div class="min-h-screen pb-32">
    
    <!-- HEADER HERO -->
    <header class="relative h-72 lg:h-80 bg-gray-900 overflow-hidden rounded-b-[2.5rem] shadow-2xl z-0">
        <img src="<?php echo esc_url($banner_url); ?>" class="w-full h-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/90 via-gray-900/40 to-transparent"></div>
        
        <!-- Top Nav -->
        <div class="absolute top-0 left-0 w-full p-4 flex justify-between items-center z-20">
            <a href="<?php echo home_url('/'); ?>" class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/30 transition border border-white/10">
                <i class="fas fa-arrow-left"></i>
            </a>
            <!-- Optional: Share Button -->
            <button onclick="navigator.share({title: document.title, url: window.location.href})" class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white hover:bg-white/30 transition border border-white/10">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        <!-- Store Info (Floating) -->
        <div class="absolute bottom-0 left-0 w-full px-5 pb-8 z-10 translate-y-2">
            <div class="flex items-end gap-4">
                <div class="relative">
                    <img src="<?php echo esc_url($avatar_url); ?>" class="w-24 h-24 rounded-2xl border-4 border-white shadow-lg object-cover bg-white">
                    <?php if($is_scan_mode): ?>
                    <div class="absolute -bottom-2 -right-2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg border-2 border-white shadow-sm flex items-center gap-1 animate-bounce">
                        <i class="fas fa-qrcode"></i> Dine In
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex-1 mb-1 text-white">
                    <h1 class="text-2xl font-bold leading-tight drop-shadow-sm mb-1 line-clamp-1"><?php echo esc_html($toko->nama_toko); ?></h1>
                    <div class="flex items-center gap-3 text-xs font-medium text-gray-300">
                        <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($toko->nama_desa); ?></span>
                        <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> <?php echo number_format($toko->rating_toko, 1); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="px-4 -mt-4 relative z-10">
        
        <!-- STATUS CARD -->
        <div class="bg-white rounded-2xl p-4 shadow-lg shadow-gray-200/50 border border-gray-100 mb-6 flex items-center justify-between fade-up" style="animation-delay: 0.1s;">
            <?php if($is_scan_mode): ?>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Lokasi Anda</p>
                        <p class="font-bold text-gray-800">Meja Nomor <span class="text-emerald-600 text-lg"><?php echo $param_meja; ?></span></p>
                    </div>
                </div>
                <div class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></div>
            <?php else: ?>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Mode Pesanan</p>
                        <p class="font-bold text-gray-800">Online / Delivery</p>
                    </div>
                </div>
                <button onclick="document.getElementById('mode-hint').classList.toggle('hidden')" class="text-xs text-gray-400 border border-gray-200 rounded-lg px-2 py-1">Ubah?</button>
            <?php endif; ?>
        </div>
        <div id="mode-hint" class="hidden mb-4 bg-yellow-50 text-yellow-800 text-xs p-3 rounded-xl border border-yellow-200">
            <i class="fas fa-info-circle mr-1"></i> Untuk makan di tempat, silakan scan QR Code yang tersedia di meja.
        </div>

        <!-- STICKY SEARCH & FILTER -->
        <div class="sticky top-0 z-40 bg-[#F3F4F6]/95 backdrop-blur-sm -mx-4 px-4 py-3 space-y-3 transition-all duration-300" id="sticky-nav">
            <!-- Search -->
            <div class="relative group search-focus rounded-2xl transition-all duration-300 bg-white border border-gray-200">
                <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition-colors"></i>
                <input type="text" id="product-search" placeholder="Cari menu lezat..." class="w-full bg-transparent border-0 rounded-2xl pl-11 pr-4 py-3 text-sm focus:ring-0 text-gray-800 placeholder-gray-400" onkeyup="searchProducts()">
            </div>

            <!-- Categories -->
            <?php if(!empty($categories)): ?>
            <div class="flex space-x-2 overflow-x-auto hide-scrollbar pb-1 snap-x">
                <button onclick="filterCategory('all')" class="cat-btn snap-start active bg-gray-900 text-white px-5 py-2.5 rounded-full text-xs font-bold shadow-md transition-all whitespace-nowrap border border-transparent" data-cat="all">
                    Semua
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button onclick="filterCategory('<?php echo esc_js($cat); ?>')" class="cat-btn snap-start bg-white text-gray-500 border border-gray-200 px-5 py-2.5 rounded-full text-xs font-bold shadow-sm transition-all whitespace-nowrap hover:bg-gray-50" data-cat="<?php echo esc_attr($cat); ?>">
                        <?php echo esc_html(ucfirst($cat)); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- PRODUCT GRID -->
        <div class="grid grid-cols-1 gap-4 mt-2 pb-8" id="product-container">
            <?php if ($products): ?>
                <?php foreach ($products as $index => $prod): 
                    $img = !empty($prod->foto_utama) ? $prod->foto_utama : 'https://source.unsplash.com/random/200x200/?food,dish&sig=' . $prod->id;
                    $harga = number_format($prod->harga, 0, ',', '.');
                    $stok = $prod->stok;
                    $id = $prod->id;
                    $kategori = !empty($prod->kategori) ? $prod->kategori : 'all';
                    $delay = ($index % 10) * 0.05; // Staggered animation
                ?>
                <div class="product-card bg-white rounded-2xl p-3 shadow-sm border border-gray-100 flex gap-4 fade-up" style="animation-delay: <?php echo $delay; ?>s" data-category="<?php echo esc_attr($kategori); ?>" data-name="<?php echo strtolower(esc_attr($prod->nama_produk)); ?>">
                    
                    <!-- Image -->
                    <div class="w-24 h-24 sm:w-28 sm:h-28 flex-shrink-0 rounded-xl overflow-hidden relative bg-gray-100">
                        <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
                        <?php if($stok < 1): ?>
                            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-[1px] flex items-center justify-center">
                                <span class="text-white text-[10px] font-bold border border-white px-2 py-0.5 rounded uppercase tracking-wider">Habis</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 flex flex-col justify-between py-1">
                        <div>
                            <h3 class="font-bold text-gray-800 leading-snug mb-1 line-clamp-2 text-[15px]"><?php echo esc_html($prod->nama_produk); ?></h3>
                            <p class="text-emerald-600 font-extrabold text-base">Rp <?php echo $harga; ?></p>
                        </div>
                        
                        <div class="flex justify-between items-end">
                            <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-1 rounded-md font-medium"><?php echo esc_html(ucfirst($kategori)); ?></span>
                            
                            <?php if($stok > 0): ?>
                                <?php if($is_scan_mode): ?>
                                    <!-- Dine In Controls -->
                                    <div class="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-100 gap-1 h-9" id="ctrl-<?php echo $id; ?>">
                                        <button onclick="updateCart(<?php echo $id; ?>, -1, <?php echo $prod->harga; ?>, '<?php echo esc_js($prod->nama_produk); ?>')" class="w-7 h-full rounded-md bg-white text-gray-400 shadow-sm flex items-center justify-center hover:bg-gray-100 disabled:opacity-50 transition active:scale-90">
                                            <i class="fas fa-minus text-[10px]"></i>
                                        </button>
                                        <span class="w-6 text-center font-bold text-gray-800 text-sm" id="qty-<?php echo $id; ?>">0</span>
                                        <button onclick="updateCart(<?php echo $id; ?>, 1, <?php echo $prod->harga; ?>, '<?php echo esc_js($prod->nama_produk); ?>')" class="w-7 h-full rounded-md bg-emerald-500 text-white shadow-lg shadow-emerald-200 flex items-center justify-center hover:bg-emerald-600 transition active:scale-90">
                                            <i class="fas fa-plus text-[10px]"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <!-- Online Cart Btn (FIXED: Solid Color for better visibility) -->
                                    <button class="btn-online w-9 h-9 rounded-full bg-emerald-500 text-white border border-emerald-600 flex items-center justify-center shadow-md active:scale-90 active:bg-emerald-600 transition-all hover:bg-emerald-600" data-id="<?php echo $id; ?>">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-xs text-gray-300 italic">Stok habis</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-20 fade-up">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" class="w-40 mx-auto opacity-50 grayscale mb-4">
                    <h3 class="text-gray-800 font-bold text-lg">Menu Kosong</h3>
                    <p class="text-gray-400 text-sm">Belum ada menu yang tersedia saat ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- No Results State -->
        <div id="no-results" class="hidden py-20 text-center fade-up">
             <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                <i class="fas fa-search text-2xl"></i>
             </div>
             <p class="text-gray-500 text-sm font-medium">Menu tidak ditemukan.</p>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- BOTTOM FLOAT BAR (FIXED VISIBILITY) -->
<!-- ========================================= -->
<div id="bottom-cart-bar" class="fixed bottom-4 left-4 right-4 z-[90] transform translate-y-[150%] transition-transform duration-500 cubic-bezier(0.175, 0.885, 0.32, 1.275) md:bottom-6 md:left-auto md:right-6 md:w-auto">
    <div class="bg-gray-900 text-white rounded-2xl shadow-[0_8px_30px_rgba(0,0,0,0.4)] p-4 flex items-center justify-between cursor-pointer border border-gray-700/50 backdrop-blur-md md:min-w-[350px]" onclick="openCheckout()">
        <div class="flex flex-col pl-2">
            <span class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-0.5">Total Pesanan</span>
            <div class="flex items-baseline gap-2">
                <span class="text-lg font-bold" id="bar-total">Rp 0</span>
                <span class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" id="bar-count">0 item</span>
            </div>
        </div>
        <button class="bg-white text-gray-900 px-6 py-3 rounded-xl font-bold text-sm hover:bg-gray-100 transition flex items-center gap-2 group shadow-lg">
            Lihat Pesanan <i class="fas fa-chevron-right group-hover:translate-x-1 transition-transform"></i>
        </button>
    </div>
</div>

<!-- ========================================= -->
<!-- CHECKOUT BOTTOM SHEET (MODAL) -->
<!-- ========================================= -->
<div id="checkout-modal" class="fixed inset-0 z-[100] hidden">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-300 opacity-0" id="modal-overlay" onclick="closeCheckout()"></div>
    
    <!-- Sheet Panel -->
    <div class="absolute bottom-0 left-0 w-full bg-white rounded-t-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.1)] transform translate-y-full transition-transform duration-300 md:max-w-md md:left-1/2 md:-translate-x-1/2 md:rounded-2xl md:bottom-4 md:h-[90vh]" id="modal-panel">
        
        <!-- Drag Handle (Visual) -->
        <div class="w-full flex justify-center pt-3 pb-1 cursor-pointer" onclick="closeCheckout()">
            <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="checkout-form" class="flex flex-col h-[85vh] md:h-full">
            <!-- Header -->
            <div class="px-6 pb-4 border-b border-gray-100">
                <h3 class="font-bold text-xl text-gray-900">Konfirmasi Pesanan</h3>
                <p class="text-xs text-gray-400 mt-0.5">Pastikan pesanan Anda sudah benar.</p>
            </div>

            <!-- Scroll Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scroll">
                
                <!-- Cart Summary -->
                <div class="bg-orange-50/50 border border-orange-100 rounded-2xl p-4">
                    <h4 class="text-[11px] font-bold text-orange-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-receipt"></i> Ringkasan Menu
                    </h4>
                    <ul id="checkout-list" class="space-y-3 mb-4"></ul>
                    <div class="flex justify-between items-center pt-3 border-t border-dashed border-orange-200">
                        <span class="font-bold text-gray-600 text-sm">Total Bayar</span>
                        <span class="font-bold text-orange-600 text-lg" id="modal-total">Rp 0</span>
                    </div>
                </div>

                <!-- Input Fields -->
                <div class="space-y-4">
                    <div class="group">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Anda</label>
                        <div class="relative transition-all focus-within:transform focus-within:-translate-y-1">
                            <i class="far fa-user absolute left-4 top-3.5 text-gray-400"></i>
                            <input type="text" name="nama_pemesan" required class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3 text-sm font-medium focus:ring-2 focus:ring-emerald-500 focus:bg-white focus:border-emerald-500 outline-none transition-colors" placeholder="Masukkan nama...">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Meja</label>
                            <div class="relative">
                                <i class="fas fa-chair absolute left-4 top-3.5 text-gray-400"></i>
                                <input type="number" name="nomor_meja" value="<?php echo $is_scan_mode ? $param_meja : ''; ?>" <?php echo $is_scan_mode ? 'readonly' : ''; ?> class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-11 pr-4 py-3 text-sm font-bold text-gray-800 outline-none <?php echo $is_scan_mode ? 'bg-gray-100 cursor-not-allowed' : 'focus:ring-2 focus:ring-emerald-500 bg-white'; ?>" placeholder="0">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Pembayaran</label>
                            <div class="relative">
                                <select name="metode_bayar" id="pay-method" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium appearance-none outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-colors" onchange="toggleProof()">
                                    <option value="cash">Kasir (Tunai)</option>
                                    <option value="transfer">QRIS / Transfer</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-4 top-3.5 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Proof Section (REDESIGNED) -->
                    <div id="proof-section" class="hidden animate-fade-in">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Pembayaran & Bukti</label>
                        
                        <!-- QRIS CARD (Redesigned) -->
                        <?php if($toko->qris_image_url): ?>
                        <div class="bg-white border border-gray-200 rounded-2xl p-4 mb-4 shadow-sm text-center relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
                            
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Scan QRIS Toko</p>
                            
                            <!-- QR Thumbnail with Hover Effect -->
                            <div class="relative w-32 h-32 mx-auto bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center mb-3 cursor-zoom-in group-hover:border-emerald-400 transition" onclick="openQrisZoom()">
                                <img src="<?php echo esc_url($toko->qris_image_url); ?>" class="w-28 h-28 object-contain p-1 rounded-lg">
                                <div class="absolute inset-0 bg-black/5 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <span class="bg-white/90 backdrop-blur text-gray-800 text-[10px] font-bold px-3 py-1.5 rounded-full shadow-md flex items-center gap-1">
                                        <i class="fas fa-search-plus"></i> Perbesar
                                    </span>
                                </div>
                            </div>
                            
                            <button type="button" onclick="openQrisZoom()" class="text-emerald-600 text-xs font-bold hover:text-emerald-700 transition flex items-center justify-center gap-1 mx-auto bg-emerald-50 px-3 py-1.5 rounded-lg">
                                <i class="fas fa-expand-arrows-alt"></i> Buka Layar Penuh
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="relative w-full h-24 border-2 border-dashed border-emerald-300 bg-emerald-50 rounded-xl flex flex-col items-center justify-center text-emerald-600 hover:bg-emerald-100 transition cursor-pointer overflow-hidden">
                            <!-- ADDED REQUIRED ATTRIBUTE LOGIC IN JS -->
                            <input type="file" name="bukti_bayar" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewImage(this)">
                            <div id="upload-placeholder" class="text-center">
                                <i class="fas fa-cloud-upload-alt text-xl mb-1"></i>
                                <p class="text-[10px] font-bold">Upload Bukti Transfer</p>
                            </div>
                            <img id="upload-preview" class="absolute inset-0 w-full h-full object-cover hidden">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Catatan</label>
                        <textarea name="catatan" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-colors h-20 resize-none" placeholder="Contoh: Jangan terlalu pedas..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="p-5 border-t border-gray-100 bg-white pb-safe">
                <?php wp_nonce_field('dw_submit_dinein', 'dw_dinein_nonce'); ?>
                <input type="hidden" name="dw_action" value="submit_dine_in">
                <input type="hidden" name="cart_data" id="cart-data-field">
                <button type="submit" class="w-full bg-gray-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-gray-900/20 hover:bg-black transition transform active:scale-95 flex items-center justify-center gap-2">
                    <span>Kirim Pesanan</span>
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php if($order_error): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => alert("<?php echo esc_js($order_error); ?>"));
</script>
<?php endif; ?>
<script>
    // State
    let cart = {}; 
    const products = <?php 
        $js_data = [];
        foreach($products as $p) $js_data[$p->id] = ['nama' => $p->nama_produk, 'harga' => (int)$p->harga];
        echo json_encode($js_data); 
    ?>;
    const isScanMode = <?php echo $is_scan_mode ? 'true' : 'false'; ?>;

    // --- UX Functions ---

    function vibrate() {
        if (navigator.vibrate) navigator.vibrate(10);
    }

    function showToast(msg) {
        const toast = document.createElement('div');
        toast.className = 'bg-gray-900/90 backdrop-blur text-white px-6 py-3 rounded-full shadow-xl text-xs font-bold flex items-center gap-2 bounce-in mb-2';
        toast.innerHTML = `<i class="fas fa-check-circle text-emerald-400"></i> ${msg}`;
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }

    // --- QRIS Zoom Functions ---

    function openQrisZoom() {
        const modal = document.getElementById('qris-zoom-modal');
        modal.classList.remove('hidden');
    }

    function closeQrisZoom() {
        const modal = document.getElementById('qris-zoom-modal');
        modal.classList.add('hidden');
    }

    // --- Logic Functions ---

    function updateCart(id, change, price, name) {
        vibrate();
        if (!cart[id]) cart[id] = 0;
        
        cart[id] += change;
        if (cart[id] <= 0) delete cart[id];

        // Update UI Card
        const qtyEl = document.getElementById(`qty-${id}`);
        const ctrlEl = document.getElementById(`ctrl-${id}`);
        
        if (qtyEl) {
            qtyEl.innerText = cart[id] || 0;
            if (cart[id] > 0) {
                ctrlEl.classList.add('ring-1', 'ring-emerald-500', 'bg-emerald-50');
            } else {
                ctrlEl.classList.remove('ring-1', 'ring-emerald-500', 'bg-emerald-50');
            }
        }
        
        if (change > 0) showToast(`${name} ditambahkan`);
        updateBottomBar();
    }

    function updateBottomBar() {
        let totalQty = 0;
        let totalPrice = 0;
        
        for (const [id, qty] of Object.entries(cart)) {
            if(products[id]) {
                totalQty += qty;
                totalPrice += (qty * products[id].harga);
            }
        }

        const bar = document.getElementById('bottom-cart-bar');
        const barTotal = document.getElementById('bar-total');
        const barCount = document.getElementById('bar-count');

        if (totalQty > 0) {
            bar.style.transform = 'translateY(0)';
            barTotal.innerText = 'Rp ' + totalPrice.toLocaleString('id-ID');
            barCount.innerText = totalQty + ' item';
        } else {
            bar.style.transform = 'translateY(150%)';
        }
    }

    // --- Modal / Bottom Sheet ---

    function openCheckout() {
        const modal = document.getElementById('checkout-modal');
        const overlay = document.getElementById('modal-overlay');
        const panel = document.getElementById('modal-panel');
        const list = document.getElementById('checkout-list');
        const input = document.getElementById('cart-data-field');
        
        // Render List
        list.innerHTML = '';
        let totalPrice = 0;
        
        for (const [id, qty] of Object.entries(cart)) {
            if(products[id]) {
                const sub = qty * products[id].harga;
                totalPrice += sub;
                list.innerHTML += `
                    <li class="flex justify-between items-center text-sm py-1">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold bg-gray-100 text-gray-600 px-2 py-0.5 rounded border border-gray-200">${qty}x</span>
                            <span class="text-gray-700 font-medium line-clamp-1">${products[id].nama}</span>
                        </div>
                        <span class="font-bold text-gray-900">Rp ${sub.toLocaleString('id-ID')}</span>
                    </li>
                `;
            }
        }
        
        document.getElementById('modal-total').innerText = 'Rp ' + totalPrice.toLocaleString('id-ID');
        input.value = JSON.stringify(cart);

        // Show Modal Animation
        modal.classList.remove('hidden');
        setTimeout(() => {
            overlay.classList.remove('opacity-0');
            panel.classList.remove('translate-y-full');
        }, 10);
    }

    function closeCheckout() {
        const modal = document.getElementById('checkout-modal');
        const overlay = document.getElementById('modal-overlay');
        const panel = document.getElementById('modal-panel');

        overlay.classList.add('opacity-0');
        panel.classList.add('translate-y-full');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // --- Helpers ---

    function toggleProof() {
        const val = document.getElementById('pay-method').value;
        const div = document.getElementById('proof-section');
        const fileInput = document.querySelector('input[name="bukti_bayar"]');
        
        if(val === 'transfer') {
            div.classList.remove('hidden');
            fileInput.required = true; // FORCE HTML5 VALIDATION
        } else {
            div.classList.add('hidden');
            fileInput.required = false; // REMOVE VALIDATION
        }
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('upload-preview').src = e.target.result;
                document.getElementById('upload-preview').classList.remove('hidden');
                document.getElementById('upload-placeholder').classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function searchProducts() {
        const query = document.getElementById('product-search').value.toLowerCase();
        const items = document.querySelectorAll('.product-card');
        let visibleCount = 0;
        
        items.forEach(item => {
            const name = item.dataset.name;
            if(name.includes(query)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        const noRes = document.getElementById('no-results');
        if(visibleCount === 0) noRes.classList.remove('hidden');
        else noRes.classList.add('hidden');
    }

    function filterCategory(cat) {
        vibrate();
        // Update Buttons
        document.querySelectorAll('.cat-btn').forEach(btn => {
            if(btn.dataset.cat === cat) {
                btn.classList.remove('bg-white', 'text-gray-500', 'border-gray-200');
                btn.classList.add('bg-gray-900', 'text-white', 'border-transparent');
            } else {
                btn.classList.add('bg-white', 'text-gray-500', 'border-gray-200');
                btn.classList.remove('bg-gray-900', 'text-white', 'border-transparent');
            }
        });
        
        // Filter Items
        const items = document.querySelectorAll('.product-card');
        items.forEach(item => {
            if(cat === 'all' || item.dataset.category === cat) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // jQuery Handlers for Online Mode (Existing functionality preserved)
    jQuery(document).ready(function($) {
        if(!isScanMode) {
            $('body').on('click', '.btn-online', function(e) {
                e.preventDefault();
                vibrate();
                const btn = $(this);
                const pid = btn.data('id');
                const icon = btn.find('i');
                
                // Loading state
                btn.addClass('scale-90 bg-gray-100');
                icon.removeClass('fa-plus').addClass('fa-spinner fa-spin');
                
                // Simulate Ajax (Replace with real logic if dw_ajax exists)
                setTimeout(() => {
                   // Success State Animation
                   btn.removeClass('bg-emerald-500 text-white border-emerald-600')
                      .addClass('bg-gray-800 text-white border-transparent');
                   icon.removeClass('fa-spinner fa-spin').addClass('fa-check');
                   showToast('Ditambahkan ke keranjang');
                   
                   // Revert
                   setTimeout(() => {
                       btn.addClass('bg-emerald-500 text-white border-emerald-600')
                          .removeClass('bg-gray-800 text-white border-transparent scale-90 bg-gray-100');
                       icon.removeClass('fa-check').addClass('fa-plus');
                   }, 1500);
                }, 500);

                // ACTUAL AJAX CALL (Uncomment if needed)
                /*
                $.post(dw_ajax.ajax_url, {
                    action: 'dw_add_to_cart',
                    security: dw_ajax.nonce,
                    product_id: pid,
                    qty: 1
                }, function(res) {
                    // Handle success
                });
                */
            });
        }
    });
</script>

<?php get_footer(); ?>