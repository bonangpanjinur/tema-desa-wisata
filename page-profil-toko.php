<?php
/**
 * Template Name: Profil Toko Custom (Modern)
 * URL akses: /profil/toko/slug-toko
 */

get_header();

global $wpdb;
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_desa     = $wpdb->prefix . 'dw_desa';

// 1. Tangkap Slug/ID
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

// Jika toko tidak ditemukan
if (!$toko) {
    echo '<div class="container mx-auto py-20 text-center">';
    echo '<h2 class="text-2xl font-bold text-gray-800 mb-4">Toko tidak ditemukan</h2>';
    echo '<a href="'.home_url('/').'" class="bg-green-600 text-white px-6 py-2 rounded-full hover:bg-green-700">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 3. Query Produk Toko
$products = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM $table_produk 
    WHERE id_pedagang = %d AND status_produk = 'aktif'
    ORDER BY created_at DESC
", $toko->id));

// Kelompokkan Kategori untuk Filter
$categories = [];
foreach ($products as $p) {
    if (!empty($p->kategori)) {
        $categories[$p->kategori] = true;
    }
}
$categories = array_keys($categories);

// Format jam operasional (dummy logic jika kolom belum ada, sesuaikan dgn DB Anda)
$jam_buka = isset($toko->jam_buka) ? $toko->jam_buka : '08:00';
$jam_tutup = isset($toko->jam_tutup) ? $toko->jam_tutup : '21:00';
$is_open = true; // Logic cek jam sekarang vs jam buka bisa ditambahkan

// Avatar & Banner Dummy Fallback
$avatar_url = !empty($toko->foto_profil) ? $toko->foto_profil : 'https://ui-avatars.com/api/?name='.urlencode($toko->nama_toko).'&background=16a34a&color=fff&size=128';
$banner_url = !empty($toko->foto_banner) ? $toko->foto_banner : 'https://source.unsplash.com/random/1200x400/?food,store,village';

?>

<!-- CSS Custom untuk Halaman Ini -->
<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    .mode-active { background-color: #16a34a; color: white; border-color: #16a34a; }
    .mode-inactive { background-color: white; color: #4b5563; border-color: #e5e7eb; }
</style>

<div class="bg-gray-50 min-h-screen pb-24">
    
    <!-- HERO SECTION / HEADER TOKO -->
    <div class="relative w-full h-48 md:h-64 bg-gray-300 overflow-hidden">
        <img src="<?php echo esc_url($banner_url); ?>" class="w-full h-full object-cover" alt="Banner Toko">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
    </div>

    <!-- INFO TOKO CARD -->
    <div class="container mx-auto px-4 -mt-16 relative z-10">
        <div class="bg-white rounded-xl shadow-lg p-5 md:p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-start gap-4">
                <!-- Avatar -->
                <div class="w-20 h-20 md:w-24 md:h-24 rounded-full border-4 border-white shadow-md overflow-hidden flex-shrink-0 -mt-12 md:-mt-10 bg-white">
                    <img src="<?php echo esc_url($avatar_url); ?>" class="w-full h-full object-cover">
                </div>
                
                <!-- Info Text -->
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 leading-tight mb-1"><?php echo esc_html($toko->nama_toko); ?></h1>
                            <p class="text-sm text-gray-500 flex items-center gap-1">
                                <i class="fas fa-map-marker-alt text-red-500"></i> 
                                Desa <?php echo esc_html($toko->nama_desa); ?>
                            </p>
                        </div>
                        <div class="text-right">
                             <?php if($is_open): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Buka
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Tutup
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i> 
                            <span class="font-bold text-gray-900">4.8</span> (50+ Ulasan)
                        </div>
                        <div class="flex items-center gap-1">
                            <i class="far fa-clock"></i> <?php echo esc_html($jam_buka . ' - ' . $jam_tutup); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODE SWITCHER -->
            <div class="mt-6 border-t pt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Pilih Mode Pesanan:</p>
                <div class="flex bg-gray-100 p-1 rounded-lg">
                    <button onclick="switchMode('online')" id="btn-mode-online" class="flex-1 py-2 text-sm font-medium rounded-md transition-all duration-200 shadow-sm mode-active">
                        <i class="fas fa-truck-fast mr-1"></i> Delivery / Online
                    </button>
                    <button onclick="switchMode('offline')" id="btn-mode-offline" class="flex-1 py-2 text-sm font-medium rounded-md transition-all duration-200 mode-inactive">
                        <i class="fas fa-utensils mr-1"></i> Dine In / Offline
                    </button>
                </div>
                <div id="offline-hint" class="hidden mt-2 p-2 bg-yellow-50 text-yellow-800 text-xs rounded border border-yellow-200">
                    <i class="fas fa-info-circle"></i> Mode ini untuk pesan makan di tempat atau pesan langsung ke kasir tanpa checkout sistem.
                </div>
            </div>
        </div>

        <!-- CATEGORY TABS (Sticky) -->
        <div class="sticky top-0 z-20 bg-gray-50 pt-2 pb-4 -mx-4 px-4 overflow-x-auto hide-scrollbar">
            <div class="flex space-x-2">
                <button onclick="filterCategory('all')" class="cat-btn bg-green-600 text-white px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap shadow-sm transition-colors active-cat" data-cat="all">
                    Semua
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button onclick="filterCategory('<?php echo esc_js($cat); ?>')" class="cat-btn bg-white text-gray-600 border border-gray-200 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap shadow-sm hover:bg-gray-50 transition-colors" data-cat="<?php echo esc_attr($cat); ?>">
                        <?php echo esc_html(ucfirst($cat)); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PRODUCT GRID -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-2" id="product-container">
            <?php if ($products): ?>
                <?php foreach ($products as $prod): 
                    $img = !empty($prod->foto_produk) ? $prod->foto_produk : get_template_directory_uri() . '/assets/img/placeholder-product.jpg';
                    $harga = number_format($prod->harga, 0, ',', '.');
                    $stok = $prod->stok;
                    $id = $prod->id;
                    $kategori = !empty($prod->kategori) ? $prod->kategori : 'all';
                ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-shadow product-card" data-category="<?php echo esc_attr($kategori); ?>">
                    <div class="relative h-40 md:h-48">
                        <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
                        <?php if($stok < 1): ?>
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <span class="bg-red-600 text-white text-xs px-2 py-1 rounded">Habis</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm md:text-base font-bold text-gray-900 line-clamp-2 mb-1 h-10 md:h-12 leading-snug">
                            <?php echo esc_html($prod->nama_produk); ?>
                        </h3>
                        <p class="text-xs text-gray-500 mb-2"><?php echo esc_html(ucfirst($kategori)); ?></p>
                        
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-green-600 font-bold text-sm md:text-base">Rp <?php echo $harga; ?></span>
                            
                            <?php if($stok > 0): ?>
                                <!-- Tombol Mode Online (Add to Cart) -->
                                <button class="btn-online btn-add-cart-mini w-8 h-8 rounded-full bg-gray-100 text-green-700 flex items-center justify-center hover:bg-green-600 hover:text-white transition-colors"
                                        data-product-id="<?php echo $id; ?>" 
                                        data-price="<?php echo $prod->harga; ?>"
                                        data-name="<?php echo esc_attr($prod->nama_produk); ?>">
                                    <i class="fas fa-plus"></i>
                                </button>

                                <!-- Tombol Mode Offline (Add to List) -->
                                <div class="btn-offline hidden flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                    <button onclick="updateOfflineQty(<?php echo $id; ?>, -1)" class="px-2 py-1 bg-gray-50 text-gray-600 hover:bg-gray-200">-</button>
                                    <input type="text" id="qty-<?php echo $id; ?>" value="0" readonly class="w-8 text-center text-xs font-bold border-none p-0 focus:ring-0">
                                    <button onclick="updateOfflineQty(<?php echo $id; ?>, 1, '<?php echo esc_js($prod->nama_produk); ?>', <?php echo $prod->harga; ?>)" class="px-2 py-1 bg-green-50 text-green-600 hover:bg-green-100">+</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-10 text-gray-500">
                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                    <p>Belum ada produk di toko ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- FLOATING CART / ACTION BAR (OFFLINE MODE) -->
<div id="offline-action-bar" class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-2xl p-4 z-50 transform translate-y-full transition-transform duration-300">
    <div class="container mx-auto max-w-2xl">
        <div class="flex justify-between items-center mb-3">
            <div>
                <p class="text-xs text-gray-500">Total Pesanan Offline</p>
                <h4 class="font-bold text-lg text-green-700" id="offline-total-price">Rp 0</h4>
            </div>
            <span class="text-sm font-medium bg-green-100 text-green-800 px-3 py-1 rounded-full" id="offline-total-items">0 Item</span>
        </div>
        
        <div class="grid grid-cols-2 gap-3">
            <button onclick="processOfflineOrder('dine_in')" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg flex items-center justify-center gap-2">
                <i class="fas fa-chair"></i> Pesan di Tempat
            </button>
            <button onclick="processOfflineOrder('takeaway')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg flex items-center justify-center gap-2">
                <i class="fab fa-whatsapp"></i> Pesan (WA)
            </button>
        </div>
    </div>
</div>

<!-- MODAL NO MEJA -->
<div id="modal-meja" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModalMeja()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-2xl p-6 w-11/12 max-w-sm shadow-2xl">
        <h3 class="text-lg font-bold mb-4 text-center">Nomor Meja Berapa?</h3>
        <input type="number" id="input-no-meja" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-center text-xl font-bold mb-4 focus:border-green-500 focus:ring-green-500" placeholder="Contoh: 12">
        <button onclick="sendWhatsappOrder(true)" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700">
            Lanjut ke WhatsApp
        </button>
        <button onclick="closeModalMeja()" class="w-full mt-3 text-gray-500 text-sm font-medium">Batal</button>
    </div>
</div>

<script>
    // State
    let currentMode = 'online'; // online or offline
    let offlineCart = {}; 
    const phoneNumber = '<?php echo isset($toko->no_telepon) ? esc_js($toko->no_telepon) : ""; ?>';

    // 1. Switch Mode Logic
    function switchMode(mode) {
        currentMode = mode;
        const btnOnline = document.getElementById('btn-mode-online');
        const btnOffline = document.getElementById('btn-mode-offline');
        const hint = document.getElementById('offline-hint');
        const actionBar = document.getElementById('offline-action-bar');

        if(mode === 'online') {
            btnOnline.className = "flex-1 py-2 text-sm font-medium rounded-md transition-all duration-200 shadow-sm mode-active";
            btnOffline.className = "flex-1 py-2 text-sm font-medium rounded-md transition-all duration-200 mode-inactive";
            hint.classList.add('hidden');
            
            // Show/Hide Buttons
            document.querySelectorAll('.btn-online').forEach(el => el.classList.remove('hidden'));
            document.querySelectorAll('.btn-offline').forEach(el => el.classList.add('hidden'));
            
            // Hide Offline Bar
            actionBar.classList.add('translate-y-full');
        } else {
            btnOffline.className = "flex-1 py-2 text-sm font-medium rounded-md transition-all duration-200 shadow-sm bg-orange-500 text-white border-orange-500";
            btnOnline.className = "flex-1 py-2 text-sm font-medium rounded-md transition-all duration-200 mode-inactive";
            hint.classList.remove('hidden');
            hint.innerHTML = '<i class="fas fa-utensils"></i> Mode Pesan di Tempat / Offline. Pilih menu lalu klik tombol di bawah.';

            // Show/Hide Buttons
            document.querySelectorAll('.btn-online').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.btn-offline').forEach(el => el.classList.remove('hidden', 'flex'));
            document.querySelectorAll('.btn-offline').forEach(el => el.classList.add('flex'));

            // Check if cart has items to show bar
            checkOfflineCartVisibility();
        }
    }

    // 2. Filter Category Logic
    function filterCategory(cat) {
        // UI Active State
        document.querySelectorAll('.cat-btn').forEach(btn => {
            if(btn.dataset.cat === cat) {
                btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-200');
                btn.classList.add('bg-green-600', 'text-white');
            } else {
                btn.classList.add('bg-white', 'text-gray-600', 'border-gray-200');
                btn.classList.remove('bg-green-600', 'text-white');
            }
        });

        // Filter Grid
        document.querySelectorAll('.product-card').forEach(card => {
            if(cat === 'all' || card.dataset.category === cat) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // 3. Offline Cart Logic
    function updateOfflineQty(id, change, name = '', price = 0) {
        if (!offlineCart[id]) offlineCart[id] = { qty: 0, name: name, price: price };
        
        offlineCart[id].qty += change;
        if (offlineCart[id].qty < 0) offlineCart[id].qty = 0;

        // Update UI Input
        const input = document.getElementById(`qty-${id}`);
        if(input) input.value = offlineCart[id].qty;

        updateOfflineTotal();
    }

    function updateOfflineTotal() {
        let totalItems = 0;
        let totalPrice = 0;

        for (const [key, item] of Object.entries(offlineCart)) {
            if (item.qty > 0) {
                totalItems += item.qty;
                totalPrice += (item.qty * item.price);
            }
        }

        document.getElementById('offline-total-items').innerText = totalItems + ' Item';
        document.getElementById('offline-total-price').innerText = 'Rp ' + totalPrice.toLocaleString('id-ID');

        checkOfflineCartVisibility(totalItems);
    }

    function checkOfflineCartVisibility(count = null) {
        if(currentMode !== 'offline') return;
        
        if(count === null) {
            // Recalculate if not provided
            let c = 0;
            for (const [key, item] of Object.entries(offlineCart)) c += item.qty;
            count = c;
        }

        const bar = document.getElementById('offline-action-bar');
        if (count > 0) {
            bar.classList.remove('translate-y-full');
        } else {
            bar.classList.add('translate-y-full');
        }
    }

    // 4. Order Processing (WhatsApp Generator)
    function processOfflineOrder(type) {
        if (type === 'dine_in') {
            document.getElementById('modal-meja').classList.remove('hidden');
        } else {
            sendWhatsappOrder(false);
        }
    }

    function closeModalMeja() {
        document.getElementById('modal-meja').classList.add('hidden');
    }

    function sendWhatsappOrder(isDineIn) {
        let message = `Halo Kak *<?php echo esc_js($toko->nama_toko); ?>*, saya mau pesan:\n\n`;
        let total = 0;
        let hasItems = false;

        if (isDineIn) {
            const meja = document.getElementById('input-no-meja').value;
            if(!meja) { alert("Mohon isi nomor meja"); return; }
            message += `🍽️ *MAKAN DI TEMPAT (Meja: ${meja})*\n`;
        } else {
            message += `🛍️ *TAKE AWAY / OFFLINE*\n`;
        }

        message += `--------------------------------\n`;

        for (const [key, item] of Object.entries(offlineCart)) {
            if (item.qty > 0) {
                const subtotal = item.qty * item.price;
                message += `${item.qty}x ${item.name} = Rp ${subtotal.toLocaleString('id-ID')}\n`;
                total += subtotal;
                hasItems = true;
            }
        }

        if(!hasItems) return;

        message += `--------------------------------\n`;
        message += `*Total: Rp ${total.toLocaleString('id-ID')}*\n\n`;
        message += `Mohon diproses ya, terima kasih!`;

        // Format Nomor HP (pastikan 62)
        let phone = phoneNumber.trim();
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        }

        if (!phone) {
            alert('Nomor HP Toko belum diatur. Tunjukkan layar ini ke kasir.');
            return;
        }

        const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
        window.open(url, '_blank');
        closeModalMeja();
    }

    // Initialize
    jQuery(document).ready(function($) {
        // Trigger AJAX Add to Cart for Online Mode (using existing script logic)
        $('.btn-add-cart-mini').on('click', function(e) {
            // Re-use logic from dw-ajax-cart if needed or simple trigger
            // This assumes dw-ajax-cart.js is listening to .btn-add-cart-mini or we replicate minimal logic here
            const btn = $(this);
            // Tambahkan animasi loading simple
            btn.html('<i class="fas fa-spinner fa-spin"></i>');
            
            $.post(dw_ajax.ajax_url, {
                action: 'dw_theme_add_to_cart',
                nonce: dw_ajax.nonce,
                product_id: btn.data('product-id'),
                quantity: 1,
                is_custom_db: 1 
            }, function(res) {
                if(res.success) {
                    btn.html('<i class="fas fa-check"></i>').removeClass('bg-gray-100 text-green-700').addClass('bg-green-600 text-white');
                    // Update header count if element exists
                    if(res.data.count) {
                        $('#header-cart-count, #header-cart-count-mobile').text(res.data.count).removeClass('hidden').addClass('flex');
                    }
                    setTimeout(() => {
                        btn.html('<i class="fas fa-plus"></i>').addClass('bg-gray-100 text-green-700').removeClass('bg-green-600 text-white');
                    }, 2000);
                }
            });
        });
    });
</script>

<?php get_footer(); ?>