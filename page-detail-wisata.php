<?php
/**
 * Template Name: Detail Wisata Custom
 * Description: Halaman detail wisata lengkap dengan Galeri, Peta, dan Rekomendasi Wisata Lain.
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

// ============================================================================
// 1. LOGIKA UTAMA: AMBIL DATA WISATA
// ============================================================================
$slug = get_query_var('dw_slug');
$id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

$wisata = null;

if (!empty($slug)) {
    $wisata = $wpdb->get_row($wpdb->prepare("
        SELECT w.*, d.nama_desa, d.kabupaten, d.slug_desa, d.id as id_desa
        FROM $table_wisata w
        LEFT JOIN $table_desa d ON w.id_desa = d.id
        WHERE w.slug = %s AND w.status = 'aktif'
    ", $slug));
} elseif ($id_param > 0) {
    $wisata = $wpdb->get_row($wpdb->prepare("
        SELECT w.*, d.nama_desa, d.kabupaten, d.slug_desa, d.id as id_desa
        FROM $table_wisata w
        LEFT JOIN $table_desa d ON w.id_desa = d.id
        WHERE w.id = %d AND w.status = 'aktif'
    ", $id_param));
}

// Handler Jika Tidak Ditemukan
if (!$wisata) {
    echo '<div class="min-h-[70vh] flex flex-col items-center justify-center text-center p-6 bg-gray-50">';
    echo '<div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mb-4"><i class="far fa-sad-tear text-3xl text-gray-400"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Wisata Tidak Ditemukan</h1>';
    echo '<p class="text-gray-500 mb-6">Mungkin tautan rusak atau wisata ini sudah tidak aktif.</p>';
    echo '<a href="'.home_url('/').'" class="bg-primary text-white px-8 py-3 rounded-full font-bold hover:bg-green-700 transition shadow-lg">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// ============================================================================
// 2. LOGIKA TAMBAHAN: GALERI & RELATED
// ============================================================================

// A. Parse Galeri Foto
$gallery = [];
if (!empty($wisata->foto_galeri)) {
    $decoded = json_decode($wisata->foto_galeri, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $gallery = $decoded;
    } else {
        // Fallback jika format comma separated
        $gallery = array_filter(explode(',', $wisata->foto_galeri));
    }
}

// B. Ambil Wisata Lain di Desa yang Sama (Related)
$related_wisata = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_wisata WHERE id_desa = %d AND id != %d AND status = 'aktif' ORDER BY RAND() LIMIT 3",
    $wisata->id_desa,
    $wisata->id
));

// C. Link Profil Desa
$link_desa = '#';
if (!empty($wisata->slug_desa)) {
    $link_desa = home_url('/@' . $wisata->slug_desa);
} elseif (!empty($wisata->id_desa)) {
    $link_desa = home_url('/profil-desa/?id=' . $wisata->id_desa);
}

// Variabel Tampilan
$img_hero = !empty($wisata->foto_utama) ? $wisata->foto_utama : 'https://via.placeholder.com/1200x600?text=Wisata+Desa';
$rating = $wisata->rating_avg > 0 ? $wisata->rating_avg : 'Baru';
?>

<!-- === HERO IMAGE & HEADER === -->
<div class="bg-white pb-10">
    <!-- Hero Image Full Width dengan Overlay -->
    <div class="relative h-[300px] md:h-[500px] w-full group overflow-hidden">
        <img src="<?php echo esc_url($img_hero); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="<?php echo esc_attr($wisata->nama_wisata); ?>">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
        
        <div class="absolute bottom-0 left-0 w-full p-4 md:p-10">
            <div class="container mx-auto">
                <div class="flex flex-col md:flex-row items-end justify-between gap-4">
                    <div class="text-white max-w-3xl">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="bg-primary px-3 py-1 rounded-full text-xs font-bold shadow-lg">
                                <i class="fas fa-check-circle mr-1"></i> Terverifikasi
                            </span>
                            <span class="bg-black/30 backdrop-blur px-3 py-1 rounded-full text-xs font-bold border border-white/20">
                                <i class="fas fa-star text-yellow-400 mr-1"></i> <?php echo $rating; ?>
                            </span>
                        </div>
                        <h1 class="text-3xl md:text-5xl font-extrabold mb-2 leading-tight"><?php echo esc_html($wisata->nama_wisata); ?></h1>
                        <p class="text-gray-200 flex items-center gap-2 text-sm md:text-base">
                            <i class="fas fa-map-marker-alt text-red-500"></i>
                            <?php echo esc_html($wisata->kabupaten); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb & Navigasi -->
    <div class="border-b border-gray-100 bg-white sticky top-0 z-40 shadow-sm">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-gray-500 overflow-x-auto whitespace-nowrap">
                <a href="<?php echo home_url('/'); ?>" class="hover:text-primary">Beranda</a>
                <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
                <a href="<?php echo home_url('/wisata'); ?>" class="hover:text-primary">Wisata</a>
                <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
                <span class="text-gray-800 font-medium truncate"><?php echo esc_html($wisata->nama_wisata); ?></span>
            </div>
            
            <div class="hidden md:flex gap-4 text-sm font-bold text-gray-600">
                <a href="#deskripsi" class="hover:text-primary transition">Deskripsi</a>
                <a href="#galeri" class="hover:text-primary transition">Galeri</a>
                <a href="#lokasi" class="hover:text-primary transition">Lokasi</a>
                <a href="#related" class="hover:text-primary transition">Lainnya</a>
            </div>
        </div>
    </div>

    <!-- === MAIN CONTENT === -->
    <div class="container mx-auto px-4 py-10">
        <div class="flex flex-col lg:flex-row gap-10">
            
            <!-- KOLOM KIRI (KONTEN UTAMA) -->
            <div class="w-full lg:w-2/3 space-y-10">
                
                <!-- Section Deskripsi -->
                <div id="deskripsi" class="bg-white rounded-2xl p-6 md:p-8 border border-gray-100 shadow-sm">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="fas fa-align-left"></i></div>
                        Tentang Wisata
                    </h2>
                    <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed text-justify">
                        <?php echo wpautop(esc_html($wisata->deskripsi)); ?>
                    </div>

                    <!-- Fasilitas (Mockup/Simulasi jika belum ada kolom spesifik) -->
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <h3 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wide">Fasilitas Umum</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="flex items-center gap-2 text-gray-600 text-sm p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-parking text-primary w-5"></i> Parkir Luas
                            </div>
                            <div class="flex items-center gap-2 text-gray-600 text-sm p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-mosque text-primary w-5"></i> Musholla
                            </div>
                            <div class="flex items-center gap-2 text-gray-600 text-sm p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-toilet text-primary w-5"></i> Toilet
                            </div>
                            <div class="flex items-center gap-2 text-gray-600 text-sm p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-utensils text-primary w-5"></i> Kantin
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Galeri -->
                <?php if (!empty($gallery)): ?>
                <div id="galeri">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm"><i class="far fa-images"></i></div>
                        Galeri Foto
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <!-- Foto Utama (Zoomable) -->
                        <a href="<?php echo esc_url($img_hero); ?>" target="_blank" class="block col-span-2 row-span-2 relative rounded-xl overflow-hidden group h-[300px] md:h-[400px]">
                            <img src="<?php echo esc_url($img_hero); ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition"></div>
                        </a>
                        <!-- Foto Tambahan -->
                        <?php foreach($gallery as $img): ?>
                        <a href="<?php echo esc_url($img); ?>" target="_blank" class="block relative rounded-xl overflow-hidden group h-[145px] md:h-[195px]">
                            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- KOLOM KANAN (SIDEBAR) -->
            <div class="w-full lg:w-1/3">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Kartu Pemesanan / Info -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 p-6 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-green-300"></div>
                        
                        <div class="mb-6">
                            <span class="block text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Harga Tiket Masuk</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-extrabold text-primary">
                                    <?php echo ($wisata->harga_tiket > 0) ? 'Rp '.number_format($wisata->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                                </span>
                                <?php if($wisata->harga_tiket > 0): ?><span class="text-gray-400 text-sm">/ orang</span><?php endif; ?>
                            </div>
                        </div>

                        <div class="space-y-4 mb-6">
                            <div class="flex items-start gap-3 text-sm">
                                <i class="far fa-clock text-gray-400 mt-1 w-5 text-center"></i>
                                <div>
                                    <span class="font-bold text-gray-700 block">Jam Operasional</span>
                                    <span class="text-gray-500"><?php echo esc_html($wisata->jam_buka ?: 'Setiap Hari (08:00 - 17:00)'); ?></span>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 text-sm">
                                <i class="fas fa-map-pin text-gray-400 mt-1 w-5 text-center"></i>
                                <div>
                                    <span class="font-bold text-gray-700 block">Lokasi</span>
                                    <span class="text-gray-500"><?php echo esc_html($wisata->kabupaten); ?>, <?php echo esc_html($wisata->provinsi ?: 'Jawa Tengah'); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="space-y-3">
                            <button onclick="dw_add_to_cart(<?php echo $wisata->id; ?>, 'tiket')" class="w-full bg-primary text-white font-bold py-3.5 rounded-xl hover:bg-green-700 transition shadow-lg shadow-green-200 active:scale-95 transform duration-150 flex items-center justify-center gap-2">
                                <i class="fas fa-ticket-alt"></i> Pesan Tiket Sekarang
                            </button>
                            
                            <a href="https://wa.me/?text=Halo,%20saya%20tertarik%20dengan%20wisata%20<?php echo urlencode($wisata->nama_wisata); ?>" target="_blank" class="w-full bg-white text-gray-700 font-bold py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition flex items-center justify-center gap-2">
                                <i class="fab fa-whatsapp text-green-500 text-lg"></i> Tanya Pengelola
                            </a>
                        </div>
                    </div>

                    <!-- Widget Profil Desa -->
                    <div class="bg-gray-900 rounded-2xl p-6 text-white relative overflow-hidden group">
                        <!-- Dekorasi -->
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500 rounded-full blur-[60px] opacity-20 group-hover:opacity-30 transition"></div>
                        
                        <h3 class="font-bold text-sm text-gray-400 uppercase tracking-widest mb-4 relative z-10">Dikelola Oleh</h3>
                        <div class="flex items-center gap-4 mb-6 relative z-10">
                            <div class="w-14 h-14 bg-white/10 rounded-full flex items-center justify-center text-2xl backdrop-blur-sm border border-white/10">
                                <i class="fas fa-landmark text-blue-300"></i>
                            </div>
                            <div>
                                <p class="font-bold text-lg leading-tight">Desa <?php echo esc_html($wisata->nama_desa); ?></p>
                                <a href="<?php echo esc_url($link_desa); ?>" class="text-blue-300 text-xs hover:text-white transition flex items-center gap-1 mt-1">
                                    Kunjungi Profil <i class="fas fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Peta Lokasi -->
                    <?php if($wisata->kabupaten): ?>
                    <div id="lokasi" class="bg-white p-2 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="rounded-xl overflow-hidden h-64 relative bg-gray-100">
                             <iframe 
                                width="100%" 
                                height="100%" 
                                frameborder="0" 
                                style="border:0" 
                                src="https://maps.google.com/maps?q=<?php echo urlencode($wisata->nama_wisata . ' ' . $wisata->kabupaten); ?>&output=embed" 
                                allowfullscreen 
                                loading="lazy">
                            </iframe>
                            <a href="https://maps.google.com/?q=<?php echo urlencode($wisata->nama_wisata . ' ' . $wisata->kabupaten); ?>" target="_blank" class="absolute bottom-3 right-3 bg-white text-gray-800 text-xs font-bold px-3 py-1.5 rounded-lg shadow-md hover:bg-gray-50">
                                <i class="fas fa-external-link-alt mr-1"></i> Buka Maps
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- === RELATED WISATA (WISATA LAINNYA) === -->
        <?php if($related_wisata): ?>
        <div id="related" class="mt-16 pt-10 border-t border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Wisata Lainnya di Desa <?php echo esc_html($wisata->nama_desa); ?></h2>
                    <p class="text-gray-500 text-sm mt-1">Jelajahi keindahan lain di sekitar lokasi ini.</p>
                </div>
                <a href="<?php echo esc_url($link_desa); ?>" class="text-primary font-bold text-sm hover:underline">Lihat Semua <i class="fas fa-arrow-right ml-1"></i></a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach($related_wisata as $rw): 
                    $link_rw = !empty($rw->slug) ? home_url('/wisata/detail/' . $rw->slug) : home_url('/detail-wisata/?id=' . $rw->id);
                    $img_rw = !empty($rw->foto_utama) ? $rw->foto_utama : 'https://via.placeholder.com/600x400';
                ?>
                <a href="<?php echo esc_url($link_rw); ?>" class="group bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-xl transition duration-300">
                    <div class="h-48 overflow-hidden relative">
                        <img src="<?php echo esc_url($img_rw); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded text-xs font-bold text-gray-800">
                            <i class="fas fa-star text-yellow-400"></i> <?php echo ($rw->rating_avg > 0) ? $rw->rating_avg : 'Baru'; ?>
                        </div>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 mb-2 truncate group-hover:text-primary transition"><?php echo esc_html($rw->nama_wisata); ?></h3>
                        <div class="flex items-center justify-between text-sm mt-3 pt-3 border-t border-gray-50">
                            <span class="text-primary font-bold"><?php echo ($rw->harga_tiket > 0) ? 'Rp '.number_format($rw->harga_tiket) : 'Gratis'; ?></span>
                            <span class="text-gray-400 text-xs">Detail <i class="fas fa-chevron-right text-[10px]"></i></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Fungsi Sederhana Add To Cart
function dw_add_to_cart(id, jenis) {
    if(!window.dw_ajax) {
        alert('Maaf, sistem sedang memuat. Coba refresh halaman.');
        return;
    }

    // Efek loading tombol (opsional, bisa dikembangkan)
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    btn.disabled = true;

    jQuery.ajax({
        url: dw_ajax.ajax_url,
        type: 'post',
        data: {
            action: 'dw_add_to_cart',
            security: dw_ajax.nonce,
            item_id: id,
            jenis: jenis, 
            qty: 1
        },
        success: function(response) {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            if(response.success) {
                // Tampilkan notifikasi sukses yang lebih bagus (misal SweetAlert jika ada, atau alert biasa)
                alert('Berhasil! Tiket telah ditambahkan ke keranjang.');
                
                // Update counter keranjang di header
                if(jQuery('.cart-count').length) {
                    jQuery('.cart-count').text(response.data.cart_count);
                    jQuery('.cart-count').addClass('animate-bounce'); // Efek visual
                }
            } else {
                alert(response.data.message);
                if(response.data.message.includes('login')) {
                    window.location.href = '<?php echo home_url("/login"); ?>';
                }
            }
        },
        error: function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Terjadi kesalahan koneksi. Periksa internet Anda.');
        }
    });
}
</script>

<?php get_footer(); ?>