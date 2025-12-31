<?php
/**
 * Template Name: Dashboard Verifikator UMKM
 * Description: Dashboard khusus untuk user dengan role Verifikator UMKM.
 */

if (!is_user_logged_in()) {
    wp_redirect(dw_get_login_url());
    exit;
}

$current_user = wp_get_current_user();
global $wpdb;

// --- 1. AMBIL DATA VERIFIKATOR ---
$verif_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}dw_verifikator WHERE id_user = %d", 
    $current_user->ID
));

// Data dasar Verifikator
$verifikator_name = $verif_data ? $verif_data->nama_lengkap : $current_user->display_name;
$verifikator_code = $verif_data ? $verif_data->kode_referral : ''; 
$verifikator_id   = $verif_data ? $verif_data->id : 0;

// --- 2. AMBIL DATA DESA (INFORMASI WILAYAH) ---
$desa_id = 0;
$nama_desa = 'Wilayah Belum Ditentukan';

if (function_exists('dw_get_desa_id')) {
    $desa_id = dw_get_desa_id();
} 
if (!$desa_id && $verif_data) {
    // Fallback: Cari desa berdasarkan kode wilayah API
    $desa_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}dw_desa WHERE api_kelurahan_id = %s LIMIT 1", 
        $verif_data->api_kelurahan_id
    ));
}
if ($desa_id) {
    $nama_desa = $wpdb->get_var($wpdb->prepare("SELECT nama_desa FROM {$wpdb->prefix}dw_desa WHERE id = %d", $desa_id));
}

// --- 3. FILTER DATA PEDAGANG (LOGIKA KETAT) ---
$toko_aktif = [];
$toko_pending = [];

if (!empty($verifikator_code)) {
    
    // A. LIST UMKM BINAAN (YANG SUDAH AKTIF/DISETUJUI)
    // Syarat: Kode Referral SAMA + Status Akun AKTIF
    $toko_aktif = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, u.user_email 
         FROM {$wpdb->prefix}dw_pedagang p
         LEFT JOIN {$wpdb->base_prefix}users u ON p.id_user = u.ID
         WHERE p.terdaftar_melalui_kode = %s 
         AND p.status_akun = 'aktif'
         ORDER BY p.nama_toko ASC",
        $verifikator_code
    ));

    // B. LIST VERIFIKASI PENDAFTARAN (YANG BELUM AKTIF)
    // Syarat: Kode Referral SAMA + Status Pendaftaran MENUNGGU (atau menunggu_desa)
    $toko_pending = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, u.user_email, u.display_name
         FROM {$wpdb->prefix}dw_pedagang p
         LEFT JOIN {$wpdb->base_prefix}users u ON p.id_user = u.ID
         WHERE p.terdaftar_melalui_kode = %s 
         AND p.status_pendaftaran IN ('menunggu', 'menunggu_desa') 
         ORDER BY p.created_at DESC",
        $verifikator_code
    ));
}

// Hitung Statistik
$count_aktif = count($toko_aktif);
$count_pending = count($toko_pending);

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex" id="app-verifikator">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md hidden md:block fixed h-full z-10">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-primary">Verifikator</h2>
            <div class="mt-2 px-3 py-1 bg-green-100 text-green-800 text-xs rounded-md inline-block font-medium">
                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo esc_html($nama_desa); ?>
            </div>
        </div>
        <nav class="mt-4 px-4 space-y-2">
            <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-700 bg-green-50 rounded-lg" data-target="panel-home">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>
            <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg" data-target="panel-verifikasi">
                <i class="fas fa-user-check w-6"></i> Verifikasi Pendaftaran
                <?php if($count_pending > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $count_pending; ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="nav-item flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg" data-target="panel-toko-binaan">
                <i class="fas fa-store w-6"></i> UMKM Binaan
                <span class="ml-auto bg-gray-200 text-gray-600 text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $count_aktif; ?></span>
            </a>
            <div class="border-t my-4"></div>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg">
                <i class="fas fa-sign-out-alt w-6"></i> Keluar
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 md:ml-64 overflow-y-auto min-h-screen">
        <!-- Mobile Header -->
        <header class="bg-white shadow-sm md:hidden flex items-center justify-between p-4 sticky top-0 z-20">
            <div>
                <h1 class="font-bold text-gray-800">Verifikator UMKM</h1>
                <p class="text-xs text-green-600"><?php echo esc_html($nama_desa); ?></p>
            </div>
            <button id="mobile-menu-toggle" class="text-gray-600"><i class="fas fa-bars"></i></button>
        </header>

        <div class="p-8">
            
            <!-- PANEL: HOME / STATS -->
            <div id="panel-home" class="panel-content space-y-6">
                <div class="flex justify-between items-end">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Selamat Datang, <?php echo esc_html($verifikator_name); ?></h1>
                        <p class="text-gray-600 mt-1">Anda bertugas di wilayah <strong><?php echo esc_html($nama_desa); ?></strong>.</p>
                        
                        <?php if(!empty($verifikator_code)): ?>
                            <div class="mt-3 inline-flex items-center px-4 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm border border-blue-200 shadow-sm">
                                <i class="fas fa-tag mr-2"></i> Kode Referral Anda: 
                                <strong class="ml-2 font-mono text-lg select-all"><?php echo esc_html($verifikator_code); ?></strong>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 italic">Berikan kode ini kepada pedagang agar terdaftar di bawah binaan Anda.</p>
                        <?php else: ?>
                            <div class="mt-2 bg-yellow-100 text-yellow-800 p-3 text-sm rounded border border-yellow-200 flex items-start">
                                <i class="fas fa-exclamation-triangle mt-1 mr-2"></i> 
                                <div>
                                    <strong>Akun belum lengkap!</strong><br>
                                    Anda belum memiliki Kode Referral. Hubungi Admin Desa untuk mengaktifkan akun Anda.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Pending Card -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-yellow-500 cursor-pointer hover:shadow-md transition" onclick="$('[data-target=panel-verifikasi]').trigger('click')">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Menunggu Verifikasi</p>
                                <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $count_pending; ?></h3>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full text-yellow-600">
                                <i class="fas fa-user-clock text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Active Card -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border-l-4 border-green-500 cursor-pointer hover:shadow-md transition" onclick="$('[data-target=panel-toko-binaan]').trigger('click')">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">UMKM Binaan</p>
                                <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $count_aktif; ?></h3>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full text-green-600">
                                <i class="fas fa-store text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL: VERIFIKASI LIST (Pending) -->
            <div id="panel-verifikasi" class="panel-content hidden space-y-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Daftar Pengajuan Pedagang Baru</h2>
                        <p class="text-sm text-gray-500">Pedagang yang mendaftar dengan kode: <strong><?php echo esc_html($verifikator_code); ?></strong></p>
                    </div>
                    <button onclick="location.reload()" class="text-sm text-gray-500 hover:text-primary"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
                
                <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Toko / Pemilik</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($toko_pending)) : ?>
                                    <?php foreach ($toko_pending as $item) : ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d M Y', strtotime($item->created_at)); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo esc_html($item->nama_toko ? $item->nama_toko : 'Belum set nama'); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo esc_html($item->nama_pemilik); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if($item->nomor_wa): ?>
                                                <a href="https://wa.me/<?php echo esc_attr($item->nomor_wa); ?>" target="_blank" class="text-green-600 hover:underline">
                                                    <i class="fab fa-whatsapp"></i> <?php echo esc_html($item->nomor_wa); ?>
                                                </a>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Menunggu Verifikasi
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <button onclick="verifyPedagang(<?php echo $item->id; ?>, 'approve')" class="text-white bg-green-600 hover:bg-green-700 px-3 py-1 rounded-md text-xs transition shadow-sm">
                                                <i class="fas fa-check mr-1"></i> ACC
                                            </button>
                                            <button onclick="verifyPedagang(<?php echo $item->id; ?>, 'reject')" class="text-white bg-red-600 hover:bg-red-700 px-3 py-1 rounded-md text-xs transition shadow-sm">
                                                <i class="fas fa-times mr-1"></i> Tolak
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 bg-gray-50">
                                            <div class="flex flex-col items-center justify-center p-4">
                                                <i class="fas fa-clipboard-check text-4xl text-gray-300 mb-2"></i>
                                                <p>Tidak ada pengajuan baru dengan kode referral <strong><?php echo esc_html($verifikator_code); ?></strong>.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL: TOKO BINAAN (Aktif) -->
            <div id="panel-toko-binaan" class="panel-content hidden space-y-6">
                <h2 class="text-xl font-bold text-gray-800">UMKM Binaan (Aktif)</h2>
                <p class="text-sm text-gray-500">Daftar toko yang terdaftar melalui kode referral Anda dan sudah aktif.</p>
                
                <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Toko</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemilik</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Gabung</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($toko_aktif)) : ?>
                                    <?php foreach ($toko_aktif as $toko) : ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <?php 
                                                    $img_url = $toko->foto_profil ? $toko->foto_profil : 'https://ui-avatars.com/api/?name='.urlencode($toko->nama_toko).'&background=random';
                                                    ?>
                                                    <img class="h-10 w-10 rounded-full object-cover" src="<?php echo esc_url($img_url); ?>" alt="">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo esc_html($toko->nama_toko); ?></div>
                                                    <div class="text-xs text-gray-500">ID: #<?php echo esc_html($toko->id); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo esc_html($toko->nama_pemilik); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo esc_html($toko->nik); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if($toko->nomor_wa): ?>
                                                <a href="https://wa.me/<?php echo esc_attr($toko->nomor_wa); ?>" target="_blank" class="text-green-600 hover:text-green-800">
                                                    <i class="fab fa-whatsapp"></i> <?php echo esc_html($toko->nomor_wa); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d M Y', strtotime($toko->created_at)); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Aktif
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 bg-gray-50">
                                            Belum ada toko aktif di bawah binaan Anda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
jQuery(document).ready(function($) {
    // Navigation Logic
    $('.nav-item').click(function(e) {
        e.preventDefault();
        $('.nav-item').removeClass('bg-green-50 text-gray-700').addClass('text-gray-600 hover:bg-gray-50');
        $(this).addClass('bg-green-50 text-gray-700').removeClass('text-gray-600 hover:bg-gray-50');
        
        var target = $(this).data('target');
        $('.panel-content').addClass('hidden');
        $('#' + target).removeClass('hidden');
    });

    // Mobile Menu
    $('#mobile-menu-toggle').click(function(){
        $('aside').toggleClass('hidden');
        $('aside').toggleClass('fixed inset-y-0 left-0 z-50 w-64 h-full');
    });

    // Global Action Function (AJAX)
    window.verifyPedagang = function(id, action) {
        var actionText = action === 'approve' ? 'menyetujui' : 'menolak';
        if(!confirm('Apakah Anda yakin ingin ' + actionText + ' pedagang ini?')) return;

        // Tampilkan loading cursor
        $('body').css('cursor', 'wait');

        $.post(dw_ajax.ajax_url, {
            action: 'dw_desa_verify_pedagang',
            pedagang_id: id,
            verifikasi_action: action,
            security: dw_ajax.nonce
        }, function(res) {
            $('body').css('cursor', 'default');
            
            if(res.success) {
                alert(res.data.message);
                location.reload(); // Reload page to update PHP lists
            } else {
                alert('Error: ' + res.data.message);
            }
        }).fail(function() {
            $('body').css('cursor', 'default');
            alert('Terjadi kesalahan koneksi server.');
        });
    };
});
</script>

<?php get_footer(); ?>