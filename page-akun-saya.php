<?php
/**
 * Template Name: Akun Saya (Fixed & Improved)
 * Description: Halaman profil terpusat dengan navigasi cerdas ke dashboard khusus role dan Alamat API.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( wp_login_url( get_permalink() ) );
    exit;
}

// Load library upload WP jika belum ada
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

global $wpdb;
$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$roles        = (array) $current_user->roles;
$msg          = '';
$msg_type     = '';

// --- LOGIC: TENTUKAN DASHBOARD UTAMA (FIX NAVIGASI) ---
$primary_dashboard_url = '';
$primary_dashboard_label = '';

if ( in_array('administrator', $roles) ) {
    $primary_dashboard_url = admin_url();
    $primary_dashboard_label = 'WP Admin';
} elseif ( in_array('pedagang', $roles) ) {
    $primary_dashboard_url = home_url('/dashboard-toko');
    $primary_dashboard_label = 'Dashboard Toko';
} elseif ( in_array('admin_desa', $roles) ) {
    $primary_dashboard_url = home_url('/dashboard-desa');
    $primary_dashboard_label = 'Dashboard Desa';
} elseif ( in_array('dw_ojek', $roles) ) {
    $primary_dashboard_url = home_url('/dashboard-ojek');
    $primary_dashboard_label = 'Dashboard Ojek';
} elseif ( in_array('verifikator_umkm', $roles) ) {
    $primary_dashboard_url = home_url('/dashboard-verifikator');
    $primary_dashboard_label = 'Dashboard Verifikator';
} elseif ( in_array('admin_kabupaten', $roles) ) {
    $primary_dashboard_url = home_url('/dashboard-kabupaten');
    $primary_dashboard_label = 'Dashboard Kab.';
}

// --- HELPER: FORMAT STATUS BADGE ---
if (!function_exists('dw_get_status_badge')) {
    function dw_get_status_badge($status) {
        $status_config = [
            'menunggu_pembayaran'     => ['c' => 'bg-amber-50 text-amber-600 border-amber-200', 'l' => 'Menunggu Bayar', 'i' => 'fa-wallet'],
            'pembayaran_dikonfirmasi' => ['c' => 'bg-emerald-50 text-emerald-600 border-emerald-200', 'l' => 'Sudah Bayar', 'i' => 'fa-check-circle'],
            'pembayaran_gagal'        => ['c' => 'bg-rose-50 text-rose-600 border-rose-200', 'l' => 'Gagal Bayar', 'i' => 'fa-times-circle'],
            'menunggu_konfirmasi'     => ['c' => 'bg-orange-50 text-orange-600 border-orange-100', 'l' => 'Konfirmasi Toko', 'i' => 'fa-store-alt'],
            'diproses'                => ['c' => 'bg-blue-50 text-blue-600 border-blue-200', 'l' => 'Sedang Diproses', 'i' => 'fa-box-open'],
            'menunggu_driver'         => ['c' => 'bg-fuchsia-50 text-fuchsia-600 border-fuchsia-200', 'l' => 'Mencari Kurir', 'i' => 'fa-user-clock'],
            'dalam_perjalanan'        => ['c' => 'bg-teal-50 text-teal-600 border-teal-200', 'l' => 'Kurir Di Jalan', 'i' => 'fa-motorcycle'],
            'dikirim'                 => ['c' => 'bg-indigo-50 text-indigo-600 border-indigo-200', 'l' => 'Pesanan Dikirim', 'i' => 'fa-shipping-fast'],
            'selesai'                 => ['c' => 'bg-green-600 text-white border-green-700', 'l' => 'Selesai', 'i' => 'fa-check-double'],
            'dibatalkan'              => ['c' => 'bg-gray-100 text-gray-500 border-gray-200', 'l' => 'Dibatalkan', 'i' => 'fa-ban'],
            'refunded'                => ['c' => 'bg-pink-100 text-pink-700 border-pink-200', 'l' => 'Refund', 'i' => 'fa-undo'],
        ];

        $cfg = isset($status_config[$status]) ? $status_config[$status] : ['c' => 'bg-gray-50 text-gray-600 border-gray-200', 'l' => ucfirst(str_replace('_', ' ', $status)), 'i' => 'fa-info-circle'];
        
        return "<span class='px-3 py-1.5 rounded-full text-[9px] font-black border flex items-center w-fit uppercase tracking-tighter shadow-sm {$cfg['c']}'><i class='fas {$cfg['i']} mr-1.5'></i>{$cfg['l']}</span>";
    }
}

// --- LOGIC: HANDLE POST REQUESTS ---
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && is_user_logged_in() ) {
    
    // A. UPDATE PROFIL, PASSWORD & FOTO
    if ( isset($_POST['dw_action']) && $_POST['dw_action'] == 'update_profile' ) {
        if ( isset($_POST['dw_profile_nonce']) && wp_verify_nonce($_POST['dw_profile_nonce'], 'dw_save_profile') ) {
            $error = false;
            
            $args = [
                'ID'           => $user_id,
                'user_email'   => sanitize_email($_POST['user_email']),
                'first_name'   => sanitize_text_field($_POST['first_name']),
                'last_name'    => sanitize_text_field($_POST['last_name']),
                'display_name' => sanitize_text_field($_POST['first_name'] . ' ' . $_POST['last_name'])
            ];

            if ( ! empty($_POST['pass1']) ) {
                if ( $_POST['pass1'] === $_POST['pass2'] ) {
                    $args['user_pass'] = $_POST['pass1'];
                } else {
                    $msg = 'Konfirmasi kata sandi tidak cocok.';
                    $msg_type = 'error';
                    $error = true;
                }
            }

            if ( ! $error ) {
                $update = wp_update_user( $args );
                
                if ( is_wp_error( $update ) ) {
                    $msg = 'Gagal: ' . $update->get_error_message();
                    $msg_type = 'error';
                } else {
                    $phone = sanitize_text_field($_POST['billing_phone']);
                    update_user_meta($user_id, 'billing_phone', $phone);

                    // Sinkronisasi data ke tabel custom berdasarkan Role
                    if ( in_array('pedagang', $roles) ) {
                        $wpdb->update($wpdb->prefix . 'dw_pedagang', ['nomor_wa' => $phone], ['id_user' => $user_id]);
                    } elseif ( in_array('admin_desa', $roles) ) { 
                        $wpdb->update($wpdb->prefix . 'dw_desa', ['nomor_wa' => $phone], ['id_user_desa' => $user_id]);
                    } elseif ( in_array('verifikator_umkm', $roles) ) {
                        $wpdb->update($wpdb->prefix . 'dw_verifikator', ['nomor_wa' => $phone], ['id_user' => $user_id]);
                    }
                    
                    if ( ! empty($_FILES['profile_pic']['name']) ) {
                        $uploadedfile = $_FILES['profile_pic'];
                        $upload_overrides = array( 'test_form' => false );
                        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

                        if ( $movefile && ! isset( $movefile['error'] ) ) {
                            $foto_url = $movefile['url']; 
                            update_user_meta($user_id, 'dw_custom_avatar_url', $foto_url);

                            if ( in_array('pedagang', $roles) ) {
                                $wpdb->update($wpdb->prefix . 'dw_pedagang', ['foto_admin' => $foto_url], ['id_user' => $user_id]);
                            } elseif ( in_array('admin_desa', $roles) ) {
                                $wpdb->update($wpdb->prefix . 'dw_desa', ['foto_admin' => $foto_url], ['id_user_desa' => $user_id]);
                            } elseif ( in_array('verifikator_umkm', $roles) ) {
                                $wpdb->update($wpdb->prefix . 'dw_verifikator', ['foto_profil' => $foto_url], ['id_user' => $user_id]);
                            }
                        } else {
                            $msg = 'Gagal upload foto: ' . $movefile['error'];
                            $msg_type = 'error';
                        }
                    }

                    if(empty($msg_type)) {
                        $msg = 'Profil akun berhasil diperbarui.';
                        $msg_type = 'success';
                        $current_user = wp_get_current_user();
                    }
                }
            }
        }
    }

    // B. UPDATE ALAMAT
    if ( isset($_POST['dw_action']) && $_POST['dw_action'] == 'update_address' ) {
        if ( isset($_POST['dw_address_nonce']) && wp_verify_nonce($_POST['dw_address_nonce'], 'dw_save_address') ) {
            $addr_input = [
                'alamat'    => sanitize_textarea_field($_POST['alamat_lengkap']),
                'prov_name' => sanitize_text_field($_POST['provinsi_nama']),
                'city_name' => sanitize_text_field($_POST['kota_nama']),
                'kec_name'  => sanitize_text_field($_POST['kecamatan_nama']),
                'kel_name'  => sanitize_text_field($_POST['kelurahan_nama']),
                'postcode'  => sanitize_text_field($_POST['kode_pos']),
                'prov_id'   => sanitize_text_field($_POST['api_provinsi_id']),
                'city_id'   => sanitize_text_field($_POST['api_kabupaten_id']),
                'kec_id'    => sanitize_text_field($_POST['api_kecamatan_id']),
                'kel_id'    => sanitize_text_field($_POST['api_kelurahan_id']),
            ];

            update_user_meta($user_id, 'billing_address_1', $addr_input['alamat']);
            update_user_meta($user_id, 'billing_state', $addr_input['prov_name']); 
            update_user_meta($user_id, 'billing_city', $addr_input['city_name']);
            update_user_meta($user_id, 'billing_kecamatan', $addr_input['kec_name']);
            update_user_meta($user_id, 'billing_kelurahan', $addr_input['kel_name']);
            update_user_meta($user_id, 'billing_postcode', $addr_input['postcode']);
            update_user_meta($user_id, 'api_provinsi_id', $addr_input['prov_id']);
            update_user_meta($user_id, 'api_kabupaten_id', $addr_input['city_id']);
            update_user_meta($user_id, 'api_kecamatan_id', $addr_input['kec_id']);
            update_user_meta($user_id, 'api_kelurahan_id', $addr_input['kel_id']);

            $data_wilayah = [
                'alamat_lengkap'   => $addr_input['alamat'],
                'provinsi_nama'    => $addr_input['prov_name'], 
                'kabupaten_nama'   => $addr_input['city_name'],
                'kecamatan_nama'   => $addr_input['kec_name'],
                'kelurahan_nama'   => $addr_input['kel_name'],
                'kode_pos'         => $addr_input['postcode'],
                'api_provinsi_id'  => $addr_input['prov_id'],
                'api_kabupaten_id' => $addr_input['city_id'],
                'api_kecamatan_id' => $addr_input['kec_id'],
                'api_kelurahan_id' => $addr_input['kel_id'],
                'updated_at'       => current_time('mysql')
            ];

            // Sinkronisasi Alamat ke Tabel Role
            if ( in_array('pedagang', $roles) ) {
                $wpdb->update($wpdb->prefix . 'dw_pedagang', $data_wilayah, ['id_user' => $user_id]);
            } elseif ( in_array('admin_desa', $roles) ) {
                $data_desa = $data_wilayah;
                $data_desa['provinsi']  = $data_wilayah['provinsi_nama']; unset($data_desa['provinsi_nama']);
                $data_desa['kabupaten'] = $data_wilayah['kabupaten_nama']; unset($data_desa['kabupaten_nama']);
                $data_desa['kecamatan'] = $data_wilayah['kecamatan_nama']; unset($data_desa['kecamatan_nama']);
                $data_desa['kelurahan'] = $data_wilayah['kelurahan_nama']; unset($data_desa['kelurahan_nama']);
                $wpdb->update($wpdb->prefix . 'dw_desa', $data_desa, ['id_user_desa' => $user_id]);
            } elseif ( in_array('verifikator_umkm', $roles) ) {
                $data_verif = $data_wilayah;
                // Mapping field names for verifikator table if different, usually same logic
                $data_verif['provinsi']  = $data_wilayah['provinsi_nama']; unset($data_verif['provinsi_nama']);
                $data_verif['kabupaten'] = $data_wilayah['kabupaten_nama']; unset($data_verif['kabupaten_nama']);
                $data_verif['kecamatan'] = $data_wilayah['kecamatan_nama']; unset($data_verif['kecamatan_nama']);
                $data_verif['kelurahan'] = $data_wilayah['kelurahan_nama']; unset($data_verif['kelurahan_nama']);
                $wpdb->update($wpdb->prefix . 'dw_verifikator', $data_verif, ['id_user' => $user_id]);
            }

            $msg = 'Alamat pengiriman berhasil disimpan.';
            $msg_type = 'success';
        }
    }
}

// --- PERSIAPAN DATA TAMPILAN ---
$user_phone    = get_user_meta($user_id, 'billing_phone', true);
$user_address  = get_user_meta($user_id, 'billing_address_1', true);
$user_postcode = get_user_meta($user_id, 'billing_postcode', true);
$custom_avatar = get_user_meta($user_id, 'dw_custom_avatar_url', true);
$display_avatar = $custom_avatar ? $custom_avatar : get_avatar_url($user_id, ['size' => 200]);

$saved_prov_id = get_user_meta($user_id, 'api_provinsi_id', true);
$saved_kota_id = get_user_meta($user_id, 'api_kabupaten_id', true);
$saved_kec_id  = get_user_meta($user_id, 'api_kecamatan_id', true);
$saved_kel_id  = get_user_meta($user_id, 'api_kelurahan_id', true);

$saved_prov_name = get_user_meta($user_id, 'billing_state', true);
$saved_kota_name = get_user_meta($user_id, 'billing_city', true);
$saved_kec_name  = get_user_meta($user_id, 'billing_kecamatan', true);
$saved_kel_name  = get_user_meta($user_id, 'billing_kelurahan', true);

// Override Data jika Role Khusus (Ambil dari tabel custom jika ada)
if ( in_array('pedagang', $roles) ) {
    $pedagang_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pedagang WHERE id_user = %d", $user_id));
    if ($pedagang_data) {
        $user_address    = $pedagang_data->alamat_lengkap;
        $saved_prov_id   = $pedagang_data->api_provinsi_id;
        $saved_kota_id   = $pedagang_data->api_kabupaten_id;
        $saved_kec_id    = $pedagang_data->api_kecamatan_id;
        $saved_kel_id    = $pedagang_data->api_kelurahan_id;
        $saved_prov_name = $pedagang_data->provinsi_nama;
        $saved_kota_name = $pedagang_data->kabupaten_nama;
        $saved_kec_name  = $pedagang_data->kecamatan_nama;
        $saved_kel_name  = $pedagang_data->kelurahan_nama;
        if (!empty($pedagang_data->kode_pos)) $user_postcode = $pedagang_data->kode_pos;
        if(empty($user_phone)) $user_phone = $pedagang_data->nomor_wa;
        if(empty($custom_avatar) && !empty($pedagang_data->foto_admin)) $display_avatar = $pedagang_data->foto_admin;
    }
} elseif ( in_array('admin_desa', $roles) ) {
    $desa_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_desa WHERE id_user_desa = %d", $user_id));
    if ($desa_data) {
        $user_address    = $desa_data->alamat_lengkap;
        $saved_prov_id   = $desa_data->api_provinsi_id;
        $saved_kota_id   = $desa_data->api_kabupaten_id;
        $saved_kec_id    = $desa_data->api_kecamatan_id;
        $saved_kel_id    = $desa_data->api_kelurahan_id;
        $saved_prov_name = $desa_data->provinsi;
        $saved_kota_name = $desa_data->kabupaten;
        $saved_kec_name  = $desa_data->kecamatan;
        $saved_kel_name  = $desa_data->kelurahan;
        if (!empty($desa_data->kode_pos)) $user_postcode = $desa_data->kode_pos;
        if(empty($custom_avatar) && !empty($desa_data->foto_admin)) $display_avatar = $desa_data->foto_admin;
    }
}

// --- SET LABEL ROLE & WARNA BADGE ---
$role_label       = 'Wisatawan'; 
$role_badge_color = 'bg-gray-100 text-gray-600'; 
$role_icon        = 'fa-user';

if ( in_array('administrator', $roles) ) {
    $role_label       = 'Administrator'; 
    $role_badge_color = 'bg-red-100 text-red-600 border border-red-200'; 
    $role_icon        = 'fa-shield-alt';
} elseif ( in_array('admin_kabupaten', $roles) ) {
    $role_label       = 'Admin Kabupaten'; 
    $role_badge_color = 'bg-indigo-100 text-indigo-600 border border-indigo-200'; 
    $role_icon        = 'fa-city';
} elseif ( in_array('admin_desa', $roles) ) {
    $role_label       = 'Admin Desa'; 
    $role_badge_color = 'bg-blue-100 text-blue-600 border border-blue-200'; 
    $role_icon        = 'fa-landmark';
} elseif ( in_array('pedagang', $roles) ) {
    $role_label       = 'Pedagang / UMKM'; 
    $role_badge_color = 'bg-purple-100 text-purple-600 border border-purple-200'; 
    $role_icon        = 'fa-store';
} elseif ( in_array('dw_ojek', $roles) ) {
    $role_label       = 'Ojek Desa'; 
    $role_badge_color = 'bg-orange-100 text-orange-600 border border-orange-200'; 
    $role_icon        = 'fa-motorcycle';
} elseif ( in_array('verifikator_umkm', $roles) ) {
    $role_label       = 'Verifikator UMKM'; 
    $role_badge_color = 'bg-emerald-100 text-emerald-600 border border-emerald-200'; 
    $role_icon        = 'fa-user-check';
}

get_header();
?>

<style>
    .tab-content { display: none; animation: fadeIn 0.4s ease-out; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .nav-link { transition: all 0.2s ease; }
    .nav-link.active { background-color: #f0fdf4; color: #16a34a; border-right: 3px solid #16a34a; font-weight: 600; }
    .nav-link:hover:not(.active) { background-color: #f8fafc; color: #334155; }
    .form-input { transition: all 0.2s; }
    .form-input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15); }
    .dw-modal { transition: opacity 0.3s ease-in-out; opacity: 0; pointer-events: none; }
    .dw-modal.open { opacity: 1; pointer-events: auto; }
    .dw-modal-content { transform: scale(0.95); transition: transform 0.3s ease-in-out; }
    .dw-modal.open .dw-modal-content { transform: scale(1); }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
</style>

<div class="bg-gray-50 min-h-screen font-sans text-slate-800 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if($msg): ?>
            <div class="mb-8 p-4 rounded-xl shadow-sm border-l-4 <?php echo ($msg_type == 'error') ? 'bg-red-50 text-red-700 border-red-500' : 'bg-green-50 text-green-700 border-green-500'; ?> flex items-start gap-3 animate-fade-in">
                <div class="mt-0.5"><i class="fas <?php echo ($msg_type == 'error') ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i></div>
                <div><p class="font-medium text-sm font-bold"><?php echo esc_html($msg); ?></p></div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- LEFT SIDEBAR -->
            <aside class="w-full lg:w-1/4 flex-shrink-0">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-6 relative group">
                    <div class="h-24 bg-green-600 relative">
                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-20 transition"></div>
                    </div>
                    <div class="px-6 pb-6 text-center relative">
                        <div class="-mt-12 mb-4 inline-block relative">
                            <div class="p-1.5 bg-white rounded-full shadow-md">
                                <img src="<?php echo esc_url($display_avatar); ?>" class="w-24 h-24 rounded-full object-cover border-2 border-gray-50">
                            </div>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 leading-tight"><?php echo esc_html($current_user->display_name); ?></h2>
                        <p class="text-sm text-gray-500 mb-3"><?php echo esc_html($current_user->user_email); ?></p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border border-transparent shadow-sm <?php echo $role_badge_color; ?>">
                            <i class="fas <?php echo $role_icon; ?>"></i> <?php echo $role_label; ?>
                        </span>

                        <!-- IMPROVED: TOMBOL AKSES CEPAT DASHBOARD -->
                        <?php if ( !empty($primary_dashboard_url) ) : ?>
                            <div class="mt-5 pt-4 border-t border-gray-100">
                                <a href="<?php echo esc_url($primary_dashboard_url); ?>" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-gray-900 text-white text-xs font-bold rounded-xl hover:bg-black transition shadow-lg shadow-gray-200 transform hover:-translate-y-0.5">
                                    <i class="fas fa-rocket text-green-400"></i> <?php echo esc_html($primary_dashboard_label); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <nav class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden py-2">
                    <button onclick="switchTab('dashboard', this)" class="nav-link active w-full text-left px-6 py-3.5 flex items-center gap-3 text-sm text-gray-600">
                        <i class="fas fa-home w-5 text-center text-lg opacity-70"></i> Dashboard
                    </button>
                    <button onclick="switchTab('orders', this)" class="nav-link w-full text-left px-6 py-3.5 flex items-center gap-3 text-sm text-gray-600">
                        <i class="fas fa-shopping-bag w-5 text-center text-lg opacity-70"></i> Pesanan Saya
                    </button>
                    <button onclick="switchTab('address', this)" class="nav-link w-full text-left px-6 py-3.5 flex items-center gap-3 text-sm text-gray-600">
                        <i class="fas fa-map-marker-alt w-5 text-center text-lg opacity-70"></i> Alamat Pengiriman
                    </button>
                    <button onclick="switchTab('account', this)" class="nav-link w-full text-left px-6 py-3.5 flex items-center gap-3 text-sm text-gray-600">
                        <i class="fas fa-user-cog w-5 text-center text-lg opacity-70"></i> Edit Profil
                    </button>
                    <div class="border-t border-gray-100 my-2 pt-2">
                        <a href="<?php echo home_url('/keranjang'); ?>" class="nav-link w-full text-left px-6 py-3.5 flex items-center gap-3 text-sm text-gray-600 hover:text-green-600">
                            <i class="fas fa-shopping-cart w-5 text-center text-lg opacity-70"></i> Keranjang Belanja
                        </a>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-link w-full text-left px-6 py-3.5 flex items-center gap-3 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt w-5 text-center text-lg opacity-70"></i> Keluar
                        </a>
                    </div>
                </nav>
            </aside>

            <!-- RIGHT CONTENT -->
            <main class="w-full lg:w-3/4">
                
                <!-- 1. DASHBOARD OVERVIEW -->
                <div id="tab-dashboard" class="tab-content active">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-6">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2 font-black italic">Selamat Datang!</h1>
                        <p class="text-gray-500 mb-8">Ini adalah pusat kontrol akun Anda. Akses riwayat belanja dan dashboard pengelolaan Anda di sini.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- SHORTCUT KHUSUS ROLE -->
                            <?php if ( in_array('pedagang', $roles) || in_array('administrator', $roles) ) : ?>
                            <a href="<?php echo home_url('/dashboard-toko'); ?>" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-600 to-emerald-700 text-white p-6 shadow-lg transform hover:-translate-y-1 transition duration-300 group">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 group-hover:scale-110 transition"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-4"><i class="fas fa-store text-2xl"></i></div>
                                    <h3 class="text-xl font-bold mb-1">Dashboard Toko</h3>
                                    <p class="text-green-100 text-sm">Kelola produk, pesanan toko, dan laporan penjualan.</p>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if ( in_array('admin_desa', $roles) || in_array('administrator', $roles) ) : ?>
                            <a href="<?php echo home_url('/dashboard-desa'); ?>" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-600 text-white p-6 shadow-lg transform hover:-translate-y-1 transition duration-300 group">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 group-hover:scale-110 transition"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-4"><i class="fas fa-landmark text-2xl"></i></div>
                                    <h3 class="text-xl font-bold mb-1">Admin Desa</h3>
                                    <p class="text-blue-100 text-sm">Verifikasi pedagang dan kelola data desa wisata.</p>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if ( in_array('dw_ojek', $roles) || in_array('administrator', $roles) ) : ?>
                            <a href="<?php echo home_url('/dashboard-ojek'); ?>" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 text-white p-6 shadow-lg transform hover:-translate-y-1 transition duration-300 group">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 group-hover:scale-110 transition"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-4"><i class="fas fa-motorcycle text-2xl"></i></div>
                                    <h3 class="text-xl font-bold mb-1">Dashboard Ojek</h3>
                                    <p class="text-orange-100 text-sm">Kelola pesanan ojek dan pengantaran.</p>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if ( in_array('verifikator_umkm', $roles) || in_array('administrator', $roles) ) : ?>
                            <a href="<?php echo home_url('/dashboard-verifikator'); ?>" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-6 shadow-lg transform hover:-translate-y-1 transition duration-300 group">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 group-hover:scale-110 transition"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-4"><i class="fas fa-user-check text-2xl"></i></div>
                                    <h3 class="text-xl font-bold mb-1">Verifikator UMKM</h3>
                                    <p class="text-emerald-100 text-sm">Verifikasi pedagang binaan dan laporan komisi.</p>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if ( in_array('admin_kabupaten', $roles) || in_array('administrator', $roles) ) : ?>
                            <a href="<?php echo home_url('/dashboard-kabupaten'); ?>" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 text-white p-6 shadow-lg transform hover:-translate-y-1 transition duration-300 group">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 group-hover:scale-110 transition"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-4"><i class="fas fa-city text-2xl"></i></div>
                                    <h3 class="text-xl font-bold mb-1">Admin Kabupaten</h3>
                                    <p class="text-indigo-100 text-sm">Pengawasan tingkat kabupaten dan moderasi.</p>
                                </div>
                            </a>
                            <?php endif; ?>

                            <!-- SHORTCUT UMUM PEMBELI -->
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:border-gray-200 transition group cursor-pointer" onclick="document.querySelector('button[onclick*=\'orders\']').click()">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-green-600 group-hover:text-green-700"><i class="fas fa-shopping-bag text-xl"></i></div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">Pesanan Saya</h4>
                                        <p class="text-sm text-gray-500">Lacak status belanjaan Anda.</p>
                                    </div>
                                    <div class="ml-auto text-gray-300 group-hover:text-green-600 transition"><i class="fas fa-arrow-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. TAB PESANAN -->
                <div id="tab-orders" class="tab-content">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-8">Riwayat Pesanan</h2>
                        <?php
                        $table_trx = $wpdb->prefix . 'dw_transaksi';
                        $orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_trx WHERE id_pembeli = %d ORDER BY created_at DESC LIMIT 20", $user_id));
                        
                        if ( $orders ) : ?>
                            <div class="space-y-4">
                                <?php foreach ( $orders as $order ) : ?>
                                    <div class="p-5 rounded-2xl border border-gray-100 hover:border-green-200 transition-all flex flex-col md:flex-row justify-between items-center gap-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400"><i class="fas fa-receipt text-xl"></i></div>
                                            <div>
                                                <p class="text-[10px] font-mono text-gray-400 uppercase tracking-widest">#<?php echo esc_html($order->kode_unik); ?></p>
                                                <h4 class="font-bold text-gray-800"><?php echo esc_html(date('d M Y', strtotime($order->created_at))); ?></h4>
                                                <p class="text-xs font-black text-green-600"><?php echo esc_html(tema_dw_format_rupiah($order->total_transaksi)); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <?php echo dw_get_status_badge($order->status_transaksi); ?>
                                            <button onclick='openDetailTrx(<?php echo json_encode(["master" => $order, "subs" => []]); ?>)' class="px-4 py-2 rounded-xl bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest hover:bg-black transition">Detail</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="text-center py-20">
                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-200"><i class="fas fa-shopping-cart text-3xl"></i></div>
                                <p class="text-gray-400 font-medium">Belum ada riwayat pesanan.</p>
                                <a href="<?php echo home_url('/produk'); ?>" class="text-green-600 font-bold text-sm mt-2 inline-block hover:underline">Mulai Belanja Sekarang</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. ADDRESS TAB -->
                <div id="tab-address" class="tab-content">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-8">Alamat Pengiriman</h2>
                        <form method="post" action="">
                            <?php wp_nonce_field('dw_save_address', 'dw_address_nonce'); ?>
                            <input type="hidden" name="dw_action" value="update_address">
                            
                            <!-- Hidden inputs for names -->
                            <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo esc_attr($saved_prov_name); ?>">
                            <input type="hidden" name="kota_nama" id="input_kota_nama" value="<?php echo esc_attr($saved_kota_name); ?>">
                            <input type="hidden" name="kecamatan_nama" id="input_kecamatan_nama" value="<?php echo esc_attr($saved_kec_name); ?>">
                            <input type="hidden" name="kelurahan_nama" id="input_kelurahan_nama" value="<?php echo esc_attr($saved_kel_name); ?>">
                            
                            <!-- Data Container for JS -->
                            <div id="region-data" data-prov="<?php echo esc_attr($saved_prov_id); ?>" data-kota="<?php echo esc_attr($saved_kota_id); ?>" data-kec="<?php echo esc_attr($saved_kec_id); ?>" data-kel="<?php echo esc_attr($saved_kel_id); ?>"></div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Provinsi</label>
                                    <select name="api_provinsi_id" id="dw_provinsi" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none font-bold">
                                        <option value="">Memuat...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kota/Kabupaten</label>
                                    <select name="api_kabupaten_id" id="dw_kota" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none disabled:opacity-50 font-bold" disabled>
                                        <option value="">Pilih Provinsi Dulu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kecamatan</label>
                                    <select name="api_kecamatan_id" id="dw_kecamatan" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none disabled:opacity-50 font-bold" disabled>
                                        <option value="">Pilih Kota Dulu</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kelurahan</label>
                                    <select name="api_kelurahan_id" id="dw_kelurahan" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none disabled:opacity-50 font-bold" disabled>
                                        <option value="">Pilih Kecamatan Dulu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Detail Alamat</label>
                                    <textarea name="alamat_lengkap" rows="2" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500 outline-none font-bold" placeholder="Nama Jalan, No. Rumah..."><?php echo esc_textarea($user_address); ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kode Pos</label>
                                    <input type="text" name="kode_pos" value="<?php echo esc_attr($user_postcode); ?>" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm outline-none font-bold">
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3.5 rounded-xl font-black shadow-xl shadow-green-100 transition transform hover:-translate-y-0.5">Simpan Alamat Pengiriman</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 4. ACCOUNT DETAILS TAB -->
                <div id="tab-account" class="tab-content">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-8">Detail Profil Akun</h2>
                        <form method="post" action="" enctype="multipart/form-data">
                            <?php wp_nonce_field('dw_save_profile', 'dw_profile_nonce'); ?>
                            <input type="hidden" name="dw_action" value="update_profile">
                            <div class="mb-10 p-6 bg-gray-50/50 rounded-2xl border border-gray-100 flex flex-col md:flex-row items-center gap-8">
                                <div class="relative">
                                    <img src="<?php echo esc_url($display_avatar); ?>" id="avatar-preview" class="w-28 h-28 rounded-full object-cover border-4 border-white shadow-xl">
                                    <label class="absolute bottom-1 right-1 w-9 h-9 bg-green-600 rounded-full flex items-center justify-center text-white text-xs border-2 border-white cursor-pointer hover:scale-110 transition shadow-lg"><i class="fas fa-camera"></i><input type="file" name="profile_pic" class="hidden" accept="image/*" onchange="previewImage(this)"></label>
                                </div>
                                <div class="flex-1 text-center md:text-left">
                                    <label class="block text-base font-black text-gray-800 mb-1">Foto Profil Anda</label>
                                    <p class="text-xs text-gray-400 mb-3 italic">Gunakan foto asli agar mitra desa mengenali Anda.</p>
                                    <p class="text-[10px] font-black text-green-600 uppercase tracking-widest">Avatar ini tersinkron dengan profil toko/desa Anda.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div><label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nama Depan</label><input type="text" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm outline-none font-bold" required></div>
                                <div><label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nama Belakang</label><input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm outline-none font-bold"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                                <div><label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Alamat Email</label><input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm outline-none font-bold" required></div>
                                <div><label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">No. WhatsApp</label><input type="text" name="billing_phone" value="<?php echo esc_attr($user_phone); ?>" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm outline-none font-bold"></div>
                            </div>
                            <div class="bg-amber-50 p-8 rounded-2xl border border-amber-100 mb-8">
                                <h3 class="font-black text-amber-800 text-sm mb-6 flex items-center gap-3"><i class="fas fa-shield-alt"></i> Keamanan Kata Sandi</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div><label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Kata Sandi Baru</label><input type="password" name="pass1" class="form-input w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none bg-white font-bold" autocomplete="new-password"></div>
                                    <div><label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Ulangi Kata Sandi</label><input type="password" name="pass2" class="form-input w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none bg-white font-bold" autocomplete="new-password"></div>
                                </div>
                                <p class="text-[10px] text-amber-600 mt-5 italic">* Biarkan kosong jika Anda tidak ingin mengubah kata sandi.</p>
                            </div>
                            <div class="flex justify-end"><button type="submit" class="bg-gray-900 hover:bg-black text-white px-12 py-4 rounded-xl font-black shadow-2xl shadow-gray-300 transition transform hover:-translate-y-1">Update Informasi Profil</button></div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>

<!-- MODAL DETAIL PESANAN -->
<div id="trx-modal" class="dw-modal fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-md hidden p-4 sm:p-6">
    <div class="dw-modal-content bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden relative flex flex-col max-h-[95vh] sm:max-h-[90vh]">
        <!-- Header -->
        <div class="bg-gray-50 px-6 sm:px-10 py-5 sm:py-6 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="text-lg sm:text-xl font-black text-gray-800 tracking-tight leading-none mb-1">Rincian Transaksi</h3>
                <p class="text-[9px] font-mono text-gray-400 uppercase tracking-widest" id="modal-trx-id">#TRX-XXXX</p>
            </div>
            <button onclick="closeTrxModal()" class="w-10 h-10 rounded-2xl bg-white shadow-sm flex items-center justify-center hover:bg-red-50 hover:text-red-500 hover:rotate-90 transition-all duration-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6 sm:p-10 overflow-y-auto custom-scrollbar flex-grow space-y-10">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 sm:mb-12">
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Status Bayar</p>
                    <div id="modal-status-global"></div>
                </div>
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Pembayaran</p>
                    <p class="text-[10px] font-black text-gray-800 uppercase italic tracking-tighter" id="modal-pembayaran">-</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-[1.5rem] border border-gray-100 col-span-2 flex flex-col justify-center text-center">
                    <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Total Tagihan Akhir</p>
                    <p class="text-xl sm:text-2xl font-black text-green-600 leading-none tracking-tighter" id="modal-total-bayar">Rp 0</p>
                </div>
            </div>

            <div class="space-y-10" id="modal-subs-container"></div>

            <div id="modal-bukti-section" class="hidden mt-10 pt-8 border-t border-dashed border-gray-200 text-center">
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6 flex items-center gap-3">
                    Lampiran Bukti Transfer <span class="h-px bg-gray-100 flex-grow"></span>
                </h4>
                <div class="bg-gray-100 p-2 rounded-3xl border border-gray-100 max-w-xs mx-auto shadow-inner overflow-hidden cursor-zoom-in group" onclick="window.open(document.getElementById('modal-bukti-img').src)">
                    <img id="modal-bukti-img" src="" class="w-full h-auto rounded-2xl group-hover:scale-105 transition-transform duration-500">
                </div>
            </div>

            <div class="mt-10 pt-8 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-8 sm:gap-10">
                <div>
                    <h4 class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3">Penerima</h4>
                    <p class="text-sm font-black text-gray-800" id="modal-penerima">-</p>
                    <p class="text-xs text-gray-500 font-bold mt-1" id="modal-no-hp">-</p>
                </div>
                <div>
                    <h4 class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3">Tujuan Pengiriman</h4>
                    <p class="text-[11px] text-gray-600 font-medium italic leading-relaxed" id="modal-alamat">-</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 sm:px-10 py-5 sm:py-6 border-t border-gray-100 flex justify-end gap-3 shrink-0">
            <button onclick="closeTrxModal()" class="px-6 sm:px-8 py-3 rounded-xl text-xs font-black bg-white border border-gray-300 text-gray-500 hover:bg-gray-100 transition shadow-sm">Tutup</button>
            <a id="modal-invoice-btn" href="#" class="px-6 sm:px-8 py-3 rounded-xl text-xs font-black bg-green-600 text-white hover:bg-green-700 transition shadow-lg shadow-green-100 flex items-center gap-2">
                <i class="fas fa-file-download"></i> Download Invoice
            </a>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

    function switchTab(tabId, btn) {
        $('.tab-content').removeClass('active');
        $('#tab-' + tabId).addClass('active');
        $('.nav-link').removeClass('active');
        $(btn).addClass('active');
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) { $('#avatar-preview').attr('src', e.target.result); }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openDetailTrx(payload) {
        const master = payload.master;
        const subs = payload.subs;

        document.getElementById('modal-trx-id').innerText = '#' + master.kode_unik;
        document.getElementById('modal-pembayaran').innerText = master.metode_pembayaran.replace('_', ' ');
        document.getElementById('modal-total-bayar').innerText = formatRupiah(master.total_transaksi);
        document.getElementById('modal-status-global').innerHTML = getBadge(master.status_transaksi);
        document.getElementById('modal-penerima').innerText = master.nama_penerima;
        document.getElementById('modal-no-hp').innerText = master.no_hp;
        document.getElementById('modal-alamat').innerText = `${master.alamat_lengkap}, ${master.kelurahan}, ${master.kecamatan}, ${master.kabupaten}, ${master.provinsi} ${master.kode_pos}`;
        
        document.getElementById('modal-invoice-btn').href = '<?php echo home_url('/pembayaran?id='); ?>' + master.kode_unik + '&invoice=1';

        const modal = document.getElementById('trx-modal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.add('open'), 10);
    }

    function closeTrxModal() {
        const modal = document.getElementById('trx-modal');
        modal.classList.remove('open');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function formatRupiah(angka) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
    }

    function getBadge(status) {
        const config = {
            'menunggu_pembayaran': 'bg-amber-50 text-amber-600 border-amber-200',
            'pembayaran_dikonfirmasi': 'bg-emerald-50 text-emerald-600 border-emerald-200',
            'selesai': 'bg-green-600 text-white border-green-700',
            'dibatalkan': 'bg-gray-100 text-gray-500 border-gray-200'
        };
        const cls = config[status] || 'bg-gray-50 text-gray-600 border-gray-200';
        return `<span class="px-2 py-1 rounded-full text-[8px] font-black border uppercase ${cls}">${status.replace('_', ' ')}</span>`;
    }

    // --- REGION API (AJAX HANDLING) ---
    jQuery(document).ready(function($) {
        var els = { prov: $('#dw_provinsi'), kota: $('#dw_kota'), kec: $('#dw_kecamatan'), kel: $('#dw_kelurahan') };
        var data = $('#region-data').data();

        function loadR(act, pid, el, sel, cb) {
            el.html('<option>Memuat...</option>').prop('disabled', true);
            $.get(ajaxurl, { action: act, province_id: pid, regency_id: pid, district_id: pid }, function(res){
                if(res.success) {
                    var o = '<option value="">-- Pilih --</option>';
                    $.each(res.data, function(i,v){ 
                        // Handle variasi nama field id/code dan nama/name dari API
                        var val = v.id || v.code;
                        var txt = v.name || v.nama;
                        o+='<option value="'+val+'" '+(val==sel?'selected':'')+'>'+txt+'</option>'; 
                    });
                    el.html(o).prop('disabled', false); 
                    if(cb) cb();
                } else {
                    el.html('<option value="">Gagal memuat</option>');
                }
            });
        }
        
        function setH() {
            $('#input_provinsi_nama').val(els.prov.find('option:selected').text());
            $('#input_kota_nama').val(els.kota.find('option:selected').text());
            $('#input_kecamatan_nama').val(els.kec.find('option:selected').text());
            $('#input_kelurahan_nama').val(els.kel.find('option:selected').text());
        }

        // Initial Load: Provinsi -> Kota -> Kecamatan -> Kelurahan
        loadR('dw_fetch_provinces', null, els.prov, data.prov, function(){
            if(data.prov) loadR('dw_fetch_regencies', data.prov, els.kota, data.kota, function(){
                if(data.kota) loadR('dw_fetch_districts', data.kota, els.kec, data.kec, function(){
                    if(data.kec) loadR('dw_fetch_villages', data.kec, els.kel, data.kel, setH);
                });
            });
        });

        // Event Listeners for Chained Dropdowns
        els.prov.change(function(){ 
            setH(); 
            loadR('dw_fetch_regencies', $(this).val(), els.kota, null); 
            els.kec.html('<option value="">Pilih Kota Dulu</option>').prop('disabled',true); 
            els.kel.html('<option value="">Pilih Kecamatan Dulu</option>').prop('disabled',true); 
        });
        els.kota.change(function(){ 
            setH(); 
            loadR('dw_fetch_districts', $(this).val(), els.kec, null); 
            els.kel.html('<option value="">Pilih Kecamatan Dulu</option>').prop('disabled',true); 
        });
        els.kec.change(function(){ 
            loadR('dw_fetch_villages', $(this).val(), els.kel, null); 
        });
        els.kel.change(setH);
    });
</script>

<?php get_footer(); ?>