<?php
/**
 * Template Name: Halaman Akun Saya Custom
 * Description: Dashboard user lengkap. Sinkronisasi Foto Profil & Data ke Tabel Custom (Desa, Pedagang, Verifikator, Pembeli).
 * UPDATE: Support Full Sync untuk semua role sesuai skema database activation.php.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
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

// --- LOGIC: HANDLE POST REQUESTS ---
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && is_user_logged_in() ) {
    
    // A. UPDATE PROFIL, PASSWORD & FOTO
    if ( isset($_POST['dw_action']) && $_POST['dw_action'] == 'update_profile' ) {
        if ( isset($_POST['dw_profile_nonce']) && wp_verify_nonce($_POST['dw_profile_nonce'], 'dw_save_profile') ) {
            $error = false;
            
            // 1. Update Data Dasar WP User (Nama & Email)
            $args = [
                'ID'           => $user_id,
                'user_email'   => sanitize_email($_POST['user_email']),
                'first_name'   => sanitize_text_field($_POST['first_name']),
                'last_name'    => sanitize_text_field($_POST['last_name']),
                'display_name' => sanitize_text_field($_POST['first_name'] . ' ' . $_POST['last_name'])
            ];

            // 2. Cek Password (Opsional)
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
                    // 3. Update Meta & Sinkronisasi No HP
                    $phone = sanitize_text_field($_POST['billing_phone']);
                    update_user_meta($user_id, 'billing_phone', $phone);

                    // Sinkronisasi No HP ke Tabel Custom (Sesuai kolom di activation.php)
                    if ( in_array('pedagang', $roles) ) {
                        $wpdb->update($wpdb->prefix . 'dw_pedagang', ['nomor_wa' => $phone], ['id_user' => $user_id]);
                    } elseif ( in_array('verifikator_umkm', $roles) ) { 
                        $wpdb->update($wpdb->prefix . 'dw_verifikator', ['nomor_wa' => $phone], ['id_user' => $user_id]);
                    } elseif ( !in_array('admin_desa', $roles) && !in_array('administrator', $roles) ) {
                         // Default: Pembeli/Member
                         $table_pembeli = $wpdb->prefix . 'dw_pembeli';
                         $cek = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_pembeli WHERE id_user = %d", $user_id));
                         if($cek) {
                             $wpdb->update($table_pembeli, ['no_hp' => $phone], ['id_user' => $user_id]);
                         }
                    }
                    
                    // --- 4. HANDLE UPLOAD FOTO PROFIL ---
                    if ( ! empty($_FILES['profile_pic']['name']) ) {
                        $uploadedfile = $_FILES['profile_pic'];
                        $upload_overrides = array( 'test_form' => false );
                        
                        // Upload ke wp-content/uploads
                        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

                        if ( $movefile && ! isset( $movefile['error'] ) ) {
                            $foto_url = $movefile['url']; 
                            
                            // A. Simpan ke User Meta (Kunci agar tersambung ke Akun WP via filter)
                            update_user_meta($user_id, 'dw_custom_avatar_url', $foto_url);

                            // B. Sinkronisasi ke Tabel Custom (Sesuai Role & Skema Database)
                            if ( in_array('pedagang', $roles) ) {
                                // Tabel dw_pedagang -> kolom 'foto_admin'
                                $wpdb->update($wpdb->prefix . 'dw_pedagang', ['foto_admin' => $foto_url], ['id_user' => $user_id]);
                            } elseif ( in_array('admin_desa', $roles) ) {
                                // Tabel dw_desa -> kolom 'foto_admin'
                                $wpdb->update($wpdb->prefix . 'dw_desa', ['foto_admin' => $foto_url], ['id_user_desa' => $user_id]);
                            } elseif ( in_array('verifikator_umkm', $roles) ) { 
                                // Tabel dw_verifikator -> kolom 'foto_profil'
                                $wpdb->update($wpdb->prefix . 'dw_verifikator', ['foto_profil' => $foto_url], ['id_user' => $user_id]);
                            } else {
                                // Default: Pembeli / Member Biasa
                                // Tabel dw_pembeli -> kolom 'foto_profil'
                                $table_pembeli = $wpdb->prefix . 'dw_pembeli';
                                $cek_pembeli = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_pembeli WHERE id_user = %d", $user_id));
                                
                                if ($cek_pembeli) {
                                    $wpdb->update($table_pembeli, ['foto_profil' => $foto_url], ['id_user' => $user_id]);
                                }
                            }
                            
                        } else {
                            $msg = 'Gagal upload foto: ' . $movefile['error'];
                            $msg_type = 'error';
                        }
                    }

                    if(empty($msg_type)) {
                        $msg = 'Profil akun berhasil diperbarui.';
                        $msg_type = 'success';
                        $current_user = wp_get_current_user(); // Refresh object user
                    }
                }
            }
        }
    }

    // B. UPDATE ALAMAT (SINKRONISASI KE TABEL CUSTOM)
    if ( isset($_POST['dw_action']) && $_POST['dw_action'] == 'update_address' ) {
        if ( isset($_POST['dw_address_nonce']) && wp_verify_nonce($_POST['dw_address_nonce'], 'dw_save_address') ) {
            
            // 1. Tangkap Data Input
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

            // 2. Selalu Update User Meta (Standar WP/WooCommerce)
            update_user_meta($user_id, 'billing_address_1', $addr_input['alamat']);
            update_user_meta($user_id, 'billing_state', $addr_input['prov_name']); 
            update_user_meta($user_id, 'billing_city', $addr_input['city_name']);
            update_user_meta($user_id, 'billing_kecamatan', $addr_input['kec_name']);
            update_user_meta($user_id, 'billing_kelurahan', $addr_input['kel_name']);
            update_user_meta($user_id, 'billing_postcode', $addr_input['postcode']);

            // Simpan ID API untuk Auto-fill dropdown
            update_user_meta($user_id, 'api_provinsi_id', $addr_input['prov_id']);
            update_user_meta($user_id, 'api_kabupaten_id', $addr_input['city_id']);
            update_user_meta($user_id, 'api_kecamatan_id', $addr_input['kec_id']);
            update_user_meta($user_id, 'api_kelurahan_id', $addr_input['kel_id']);

            $msg = 'Alamat pengiriman berhasil disimpan.';

            // 3. Sinkronisasi Data Wilayah ke Tabel Custom
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

            if ( in_array('pedagang', $roles) ) {
                // Tabel Pedagang menggunakan suffix '_nama' untuk wilayah
                $wpdb->update($wpdb->prefix . 'dw_pedagang', $data_wilayah, ['id_user' => $user_id]);
            } 
            elseif ( in_array('admin_desa', $roles) ) {
                // Tabel Desa kolomnya: provinsi, kabupaten, kecamatan, kelurahan (tanpa _nama)
                $data_desa = $data_wilayah;
                $data_desa['provinsi']  = $data_wilayah['provinsi_nama']; unset($data_desa['provinsi_nama']);
                $data_desa['kabupaten'] = $data_wilayah['kabupaten_nama']; unset($data_desa['kabupaten_nama']);
                $data_desa['kecamatan'] = $data_wilayah['kecamatan_nama']; unset($data_desa['kecamatan_nama']);
                $data_desa['kelurahan'] = $data_wilayah['kelurahan_nama']; unset($data_desa['kelurahan_nama']);
                
                $wpdb->update($wpdb->prefix . 'dw_desa', $data_desa, ['id_user_desa' => $user_id]);
            }
            elseif ( in_array('verifikator_umkm', $roles) ) { 
                // Tabel Verifikator strukturnya mirip desa (tanpa _nama) & Punya kode_pos
                $data_verif = $data_wilayah;
                $data_verif['provinsi']  = $data_wilayah['provinsi_nama']; unset($data_verif['provinsi_nama']);
                $data_verif['kabupaten'] = $data_wilayah['kabupaten_nama']; unset($data_verif['kabupaten_nama']);
                $data_verif['kecamatan'] = $data_wilayah['kecamatan_nama']; unset($data_verif['kecamatan_nama']);
                $data_verif['kelurahan'] = $data_wilayah['kelurahan_nama']; unset($data_verif['kelurahan_nama']);
                
                $wpdb->update($wpdb->prefix . 'dw_verifikator', $data_verif, ['id_user' => $user_id]);
                $msg = 'Alamat Verifikator berhasil diperbarui.';
            }
            else {
                // Tabel Pembeli kolomnya juga mirip desa/verifikator (tanpa _nama)
                $table_pembeli = $wpdb->prefix . 'dw_pembeli';
                $cek_pembeli = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_pembeli WHERE id_user = %d", $user_id));
                
                if ( $cek_pembeli ) {
                    $data_pembeli = $data_wilayah;
                    $data_pembeli['provinsi']  = $data_wilayah['provinsi_nama']; unset($data_pembeli['provinsi_nama']);
                    $data_pembeli['kabupaten'] = $data_wilayah['kabupaten_nama']; unset($data_pembeli['kabupaten_nama']);
                    $data_pembeli['kecamatan'] = $data_wilayah['kecamatan_nama']; unset($data_pembeli['kecamatan_nama']);
                    $data_pembeli['kelurahan'] = $data_wilayah['kelurahan_nama']; unset($data_pembeli['kelurahan_nama']);

                    $wpdb->update($table_pembeli, $data_pembeli, ['id_user' => $user_id]);
                    $msg = 'Alamat Profil Member berhasil diperbarui.';
                }
            }
            
            $msg_type = 'success';
        }
    }
}

// --- PERSIAPAN DATA TAMPILAN (Pre-fill Form) ---

// 1. Data User Meta
$user_phone    = get_user_meta($user_id, 'billing_phone', true);
$user_address  = get_user_meta($user_id, 'billing_address_1', true);
$user_postcode = get_user_meta($user_id, 'billing_postcode', true);

// 2. Data Avatar Custom
$custom_avatar = get_user_meta($user_id, 'dw_custom_avatar_url', true);
$display_avatar = $custom_avatar ? $custom_avatar : get_avatar_url($user_id, ['size' => 200]);

// Data Wilayah
$saved_prov_id = get_user_meta($user_id, 'api_provinsi_id', true);
$saved_kota_id = get_user_meta($user_id, 'api_kabupaten_id', true);
$saved_kec_id  = get_user_meta($user_id, 'api_kecamatan_id', true);
$saved_kel_id  = get_user_meta($user_id, 'api_kelurahan_id', true);

$saved_prov_name = get_user_meta($user_id, 'billing_state', true);
$saved_kota_name = get_user_meta($user_id, 'billing_city', true);
$saved_kec_name  = get_user_meta($user_id, 'billing_kecamatan', true);
$saved_kel_name  = get_user_meta($user_id, 'billing_kelurahan', true);

// Override Data jika Role Khusus (Ambil dari tabel custom jika meta kosong)
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
        
        // Prioritaskan custom avatar dari usermeta, fallback ke foto_admin di tabel
        if(empty($custom_avatar) && !empty($pedagang_data->foto_admin)) $display_avatar = $pedagang_data->foto_admin;
    }
} 
elseif ( in_array('admin_desa', $roles) ) {
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
elseif ( in_array('verifikator_umkm', $roles) ) {
    $verif_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_verifikator WHERE id_user = %d", $user_id));
    if ($verif_data) {
        $user_address    = $verif_data->alamat_lengkap;
        $saved_prov_id   = $verif_data->api_provinsi_id;
        $saved_kota_id   = $verif_data->api_kabupaten_id;
        $saved_kec_id    = $verif_data->api_kecamatan_id;
        $saved_kel_id    = $verif_data->api_kelurahan_id;
        $saved_prov_name = $verif_data->provinsi;
        $saved_kota_name = $verif_data->kabupaten;
        $saved_kec_name  = $verif_data->kecamatan;
        $saved_kel_name  = $verif_data->kelurahan;
        
        if (!empty($verif_data->kode_pos)) {
            $user_postcode = $verif_data->kode_pos;
        }
        
        if(empty($user_phone)) $user_phone = $verif_data->nomor_wa;

        if(empty($custom_avatar) && !empty($verif_data->foto_profil)) $display_avatar = $verif_data->foto_profil;
    }
}
elseif ( !in_array('pedagang', $roles) && !in_array('admin_desa', $roles) && !in_array('administrator', $roles) ) {
    // Pembeli / Member
    $pembeli_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pembeli WHERE id_user = %d", $user_id));
    if ($pembeli_data) {
        $user_address    = $pembeli_data->alamat_lengkap;
        $saved_prov_id   = $pembeli_data->api_provinsi_id;
        $saved_kota_id   = $pembeli_data->api_kabupaten_id;
        $saved_kec_id    = $pembeli_data->api_kecamatan_id;
        $saved_kel_id    = $pembeli_data->api_kelurahan_id;
        $saved_prov_name = $pembeli_data->provinsi;
        $saved_kota_name = $pembeli_data->kabupaten;
        $saved_kec_name  = $pembeli_data->kecamatan;
        $saved_kel_name  = $pembeli_data->kelurahan;
        if (!empty($pembeli_data->kode_pos)) $user_postcode = $pembeli_data->kode_pos;
        if(empty($user_phone)) $user_phone = $pembeli_data->no_hp;

        if(empty($custom_avatar) && !empty($pembeli_data->foto_profil)) $display_avatar = $pembeli_data->foto_profil;
    }
}

// Setup Label Role & Style
$role_label = 'Member';
$role_badge_color = 'bg-gray-100 text-gray-600';
$role_icon = 'fa-user';

if ( in_array('administrator', $roles) || in_array('admin_kabupaten', $roles) ) {
    $role_label = 'Administrator'; $role_badge_color = 'bg-red-100 text-red-600'; $role_icon = 'fa-shield-alt';
} elseif ( in_array('admin_desa', $roles) ) {
    $role_label = 'Admin Desa'; $role_badge_color = 'bg-green-100 text-green-600'; $role_icon = 'fa-landmark';
} elseif ( in_array('pedagang', $roles) ) {
    $role_label = 'Mitra UMKM'; $role_badge_color = 'bg-purple-100 text-purple-600'; $role_icon = 'fa-store';
} elseif ( in_array('verifikator_umkm', $roles) ) { 
    $role_label = 'Verifikator'; $role_badge_color = 'bg-orange-100 text-orange-600'; $role_icon = 'fa-user-check';
}

get_header();
?>

<!-- Tailwind CSS & Font Awesome -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .tab-content { display: none; animation: fadeIn 0.4s ease-out; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .nav-link { transition: all 0.2s ease; }
    .nav-link.active { background-color: #f0f9ff; color: #0284c7; border-right: 3px solid #0284c7; font-weight: 600; }
    .nav-link:hover:not(.active) { background-color: #f8fafc; color: #334155; }
    .form-input { transition: all 0.2s; }
    .form-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
</style>

<div class="bg-gray-50 min-h-screen font-sans text-slate-800 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Notifikasi Global -->
        <?php if($msg): ?>
            <div class="mb-8 p-4 rounded-xl shadow-sm border-l-4 <?php echo ($msg_type == 'error') ? 'bg-red-50 text-red-700 border-red-500' : 'bg-green-50 text-green-700 border-green-500'; ?> flex items-start gap-3 animate-fade-in">
                <div class="mt-0.5"><i class="fas <?php echo ($msg_type == 'error') ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i></div>
                <div><p class="font-medium"><?php echo esc_html($msg); ?></p></div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- LEFT SIDEBAR -->
            <aside class="w-full lg:w-1/4 flex-shrink-0">
                <!-- User Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-6 relative group">
                    <div class="h-24 bg-gradient-to-r from-blue-600 to-indigo-600 relative">
                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-20 transition"></div>
                    </div>
                    <div class="px-6 pb-6 text-center relative">
                        <div class="-mt-12 mb-4 inline-block relative">
                            <div class="p-1.5 bg-white rounded-full shadow-md">
                                <img src="<?php echo esc_url($display_avatar); ?>" class="w-24 h-24 rounded-full object-cover border-2 border-gray-50">
                            </div>
                            <div class="absolute bottom-1 right-1 w-6 h-6 bg-green-500 border-2 border-white rounded-full" title="Online"></div>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 leading-tight"><?php echo esc_html($current_user->display_name); ?></h2>
                        <p class="text-sm text-gray-500 mb-3"><?php echo esc_html($current_user->user_email); ?></p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?php echo $role_badge_color; ?>">
                            <i class="fas <?php echo $role_icon; ?>"></i> <?php echo $role_label; ?>
                        </span>
                    </div>
                </div>

                <!-- Navigation Menu -->
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
                        <a href="<?php echo home_url('/keranjang'); ?>" class="nav-link w-full text-left px-6 py-3.5 flex items-center gap-3 text-sm text-gray-600 hover:text-blue-600">
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
                
                <!-- 1. DASHBOARD TAB -->
                <div id="tab-dashboard" class="tab-content active">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-6">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2">Selamat Datang!</h1>
                        <p class="text-gray-500 mb-8">Ini adalah dashboard akun Anda. Anda dapat melihat aktivitas terbaru dan mengelola informasi akun Anda di sini.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php if ( in_array('pedagang', $roles) || in_array('administrator', $roles) ) : ?>
                            <a href="<?php echo home_url('/dashboard-toko'); ?>" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-700 text-white p-6 shadow-lg transform hover:-translate-y-1 transition duration-300 group">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 group-hover:scale-110 transition"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-4"><i class="fas fa-store text-2xl"></i></div>
                                    <h3 class="text-xl font-bold mb-1">Dashboard Toko</h3>
                                    <p class="text-purple-100 text-sm">Kelola produk, pesanan toko, dan laporan penjualan.</p>
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

                             <?php if ( in_array('verifikator_umkm', $roles) ) : ?>
                            <a href="<?php echo home_url('/dashboard-verifikator'); ?>" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 text-white p-6 shadow-lg transform hover:-translate-y-1 transition duration-300 group">
                                <div class="absolute right-0 top-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-10 -mt-10 group-hover:scale-110 transition"></div>
                                <div class="relative z-10">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-4"><i class="fas fa-user-check text-2xl"></i></div>
                                    <h3 class="text-xl font-bold mb-1">Dashboard Verifikator</h3>
                                    <p class="text-orange-100 text-sm">Verifikasi data UMKM di lapangan.</p>
                                </div>
                            </a>
                            <?php endif; ?>

                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:border-gray-200 transition group cursor-pointer" onclick="document.querySelector('button[onclick*=\'orders\']').click()">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-blue-600 group-hover:text-blue-700"><i class="fas fa-shopping-bag text-xl"></i></div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">Pesanan Saya</h4>
                                        <p class="text-sm text-gray-500">Lacak status belanjaan Anda.</p>
                                    </div>
                                    <div class="ml-auto text-gray-300 group-hover:text-blue-600 transition"><i class="fas fa-arrow-right"></i></div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:border-gray-200 transition group cursor-pointer" onclick="document.querySelector('button[onclick*=\'account\']').click()">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-orange-500 group-hover:text-orange-600"><i class="fas fa-user-circle text-xl"></i></div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">Profil Akun</h4>
                                        <p class="text-sm text-gray-500">Perbarui biodata dan password.</p>
                                    </div>
                                    <div class="ml-auto text-gray-300 group-hover:text-orange-500 transition"><i class="fas fa-arrow-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. ORDERS TAB -->
                <div id="tab-orders" class="tab-content">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-10 text-center">
                        <div class="inline-block p-6 rounded-full bg-blue-50 text-blue-600 mb-6">
                            <i class="fas fa-receipt text-4xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">Halaman Pesanan</h2>
                        <p class="text-gray-500 mb-8 max-w-md mx-auto">Lihat riwayat belanja, lacak pengiriman, dan kelola semua transaksi Anda di halaman khusus.</p>
                        <a href="<?php echo home_url('/transaksi-saya'); ?>" class="inline-flex items-center justify-center px-8 py-4 bg-gray-900 text-white font-bold rounded-xl hover:bg-black transition shadow-lg gap-2">
                            <span>Buka Riwayat Pesanan</span> <i class="fas fa-external-link-alt text-sm"></i>
                        </a>
                    </div>
                </div>

                <!-- 3. ADDRESS TAB -->
                <div id="tab-address" class="tab-content">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-6 md:p-8 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Alamat Pengiriman</h2>
                                <p class="text-sm text-gray-500">Alamat ini akan digunakan untuk checkout dan perhitungan ongkir.</p>
                            </div>
                            
                            <?php if(in_array('pedagang', $roles)): ?>
                                <div class="bg-blue-100 text-blue-800 text-xs px-4 py-2 rounded-lg font-bold flex items-center gap-2 border border-blue-200">
                                    <i class="fas fa-store"></i> Tersinkron dengan Toko
                                </div>
                            <?php elseif(in_array('admin_desa', $roles)): ?>
                                <div class="bg-green-100 text-green-800 text-xs px-4 py-2 rounded-lg font-bold flex items-center gap-2 border border-green-200">
                                    <i class="fas fa-landmark"></i> Tersinkron dengan Desa
                                </div>
                            <?php elseif(in_array('verifikator_umkm', $roles)): ?>
                                <div class="bg-orange-100 text-orange-800 text-xs px-4 py-2 rounded-lg font-bold flex items-center gap-2 border border-orange-200">
                                    <i class="fas fa-user-check"></i> Tersinkron dengan Profil Verifikator
                                </div>
                            <?php else: ?>
                                <div class="bg-gray-100 text-gray-800 text-xs px-4 py-2 rounded-lg font-bold flex items-center gap-2 border border-gray-200">
                                    <i class="fas fa-user"></i> Tersinkron dengan Profil Member
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6 md:p-8">
                            <form method="post" action="">
                                <?php wp_nonce_field('dw_save_address', 'dw_address_nonce'); ?>
                                <input type="hidden" name="dw_action" value="update_address">

                                <!-- Hidden Fields for Text Values -->
                                <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo esc_attr($saved_prov_name); ?>">
                                <input type="hidden" name="kota_nama" id="input_kota_nama" value="<?php echo esc_attr($saved_kota_name); ?>">
                                <input type="hidden" name="kecamatan_nama" id="input_kecamatan_nama" value="<?php echo esc_attr($saved_kec_name); ?>">
                                <input type="hidden" name="kelurahan_nama" id="input_kelurahan_nama" value="<?php echo esc_attr($saved_kel_name); ?>">
                                
                                <div id="region-data" 
                                    data-prov="<?php echo esc_attr($saved_prov_id); ?>" 
                                    data-kota="<?php echo esc_attr($saved_kota_id); ?>" 
                                    data-kec="<?php echo esc_attr($saved_kec_id); ?>" 
                                    data-kel="<?php echo esc_attr($saved_kel_id); ?>">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Provinsi</label>
                                        <div class="relative">
                                            <select name="api_provinsi_id" id="dw_provinsi" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none">
                                                <option value="">Memuat...</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400"><i class="fas fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Kota / Kabupaten</label>
                                        <div class="relative">
                                            <select name="api_kabupaten_id" id="dw_kota" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none disabled:opacity-50" disabled>
                                                <option value="">Pilih Kota/Kab...</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400"><i class="fas fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Kecamatan</label>
                                        <div class="relative">
                                            <select name="api_kecamatan_id" id="dw_kecamatan" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none disabled:opacity-50" disabled>
                                                <option value="">Pilih Kecamatan...</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400"><i class="fas fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Kelurahan / Desa</label>
                                        <div class="relative">
                                            <select name="api_kelurahan_id" id="dw_kelurahan" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none appearance-none disabled:opacity-50" disabled>
                                                <option value="">Pilih Kelurahan...</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400"><i class="fas fa-chevron-down text-xs"></i></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Detail Alamat</label>
                                        <textarea name="alamat_lengkap" rows="2" class="form-input w-full border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Nama Jalan, No. Rumah, RT/RW..."><?php echo esc_textarea($user_address); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Kode Pos</label>
                                        <div class="relative">
                                            <i class="fas fa-mail-bulk absolute left-4 top-3.5 text-gray-400"></i>
                                            <input type="text" name="kode_pos" value="<?php echo esc_attr($user_postcode); ?>" class="form-input w-full border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none font-mono" placeholder="Contoh: 12345">
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-4 border-t border-gray-100">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-xl font-bold shadow-lg shadow-blue-600/20 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                                        <i class="fas fa-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 4. ACCOUNT DETAILS TAB -->
                <div id="tab-account" class="tab-content">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden p-6 md:p-8">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-800 mb-1">Detail Profil</h2>
                            <p class="text-sm text-gray-500">Perbarui informasi pribadi dan foto profil Anda.</p>
                        </div>
                        
                        <!-- UPDATE: Tambahkan enctype agar bisa upload file -->
                        <form method="post" action="" enctype="multipart/form-data">
                            <?php wp_nonce_field('dw_save_profile', 'dw_profile_nonce'); ?>
                            <input type="hidden" name="dw_action" value="update_profile">

                            <!-- FOTO PROFIL SECTION -->
                            <div class="mb-8 p-4 bg-gray-50 rounded-xl border border-gray-100 flex items-center gap-6">
                                <div class="relative">
                                    <img src="<?php echo esc_url($display_avatar); ?>" id="avatar-preview" class="w-20 h-20 rounded-full object-cover border-2 border-white shadow-md">
                                    <div class="absolute bottom-0 right-0 w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs border border-white">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Ganti Foto Profil</label>
                                    <input type="file" name="profile_pic" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" onchange="previewImage(this)">
                                    <p class="text-xs text-gray-400 mt-1">Format: JPG/PNG, Maks 2MB. Gambar ini akan digunakan sebagai Avatar akun Anda.</p>
                                </div>
                            </div>

                            <!-- Personal Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Nama Depan</label>
                                    <div class="relative">
                                        <i class="fas fa-user absolute left-4 top-3.5 text-gray-400"></i>
                                        <input type="text" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" class="form-input w-full border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Nama Belakang</label>
                                    <div class="relative">
                                        <i class="fas fa-user absolute left-4 top-3.5 text-gray-400"></i>
                                        <input type="text" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" class="form-input w-full border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Alamat Email</label>
                                    <div class="relative">
                                        <i class="fas fa-envelope absolute left-4 top-3.5 text-gray-400"></i>
                                        <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" class="form-input w-full border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">No. Handphone / WA</label>
                                    <div class="relative">
                                        <i class="fas fa-phone absolute left-4 top-3.5 text-gray-400"></i>
                                        <input type="text" name="billing_phone" value="<?php echo esc_attr($user_phone); ?>" class="form-input w-full border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm bg-gray-50 focus:bg-white outline-none" placeholder="08xxx">
                                    </div>
                                </div>
                            </div>

                            <!-- Change Password Section -->
                            <div class="bg-yellow-50/50 rounded-2xl p-6 border border-yellow-100 mb-8">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center"><i class="fas fa-lock text-xs"></i></div>
                                    <h3 class="font-bold text-gray-800 text-sm">Ganti Kata Sandi</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1.5">Kata Sandi Baru</label>
                                        <input type="password" name="pass1" class="form-input w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-yellow-400 outline-none bg-white" autocomplete="new-password">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1.5">Ulangi Kata Sandi</label>
                                        <input type="password" name="pass2" class="form-input w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-yellow-400 outline-none bg-white" autocomplete="new-password">
                                    </div>
                                </div>
                                <p class="text-xs text-yellow-700 mt-3 flex items-center gap-1"><i class="fas fa-info-circle"></i> Biarkan kosong jika tidak ingin mengubah password.</p>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="bg-gray-900 hover:bg-black text-white px-8 py-3.5 rounded-xl font-bold shadow-lg transition transform hover:-translate-y-0.5 flex items-center gap-2">
                                    <i class="fas fa-check"></i> Simpan Profil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // AJAX URL dari WordPress
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

    // Tab Switching Logic
    function switchTab(tabId, btn) {
        $('.tab-content').removeClass('active');
        $('#tab-' + tabId).addClass('active');
        
        $('.nav-link').removeClass('active');
        $(btn).addClass('active');
    }
    
    // Auto open tab from URL hash
    if(window.location.hash) {
        const hash = window.location.hash.substring(1);
        const btn = document.querySelector(`button[onclick*='${hash}']`);
        if(btn) btn.click();
    }

    // Preview Image Logic
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#avatar-preview').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // --- LOGIC ADDRESS API (AJAX CHAINING) ---
    jQuery(document).ready(function($) {
        var els = { 
            prov: $('#dw_provinsi'), 
            kota: $('#dw_kota'), 
            kec: $('#dw_kecamatan'), 
            kel: $('#dw_kelurahan') 
        };
        var data = $('#region-data').data(); // Data ID tersimpan (dari PHP)

        // Fungsi Load Data AJAX
        function loadR(act, pid, el, sel, cb) {
            el.html('<option>Memuat data...</option>').prop('disabled', true);
            var p = { action: act };
            if(act=='dw_fetch_regencies') p.province_id = pid;
            if(act=='dw_fetch_districts') p.regency_id = pid;
            if(act=='dw_fetch_villages') p.district_id = pid;
            
            $.get(ajaxurl, p, function(res){
                if(res.success) {
                    var o = '<option value="">-- Pilih --</option>';
                    $.each(res.data.data||res.data, function(i,v){ 
                        var id = v.id || v.code; 
                        var name = v.name || v.nama;
                        o+='<option value="'+id+'" '+(id==sel?'selected':'')+'>'+name+'</option>'; 
                    });
                    el.html(o).prop('disabled', false);
                    if(cb) cb();
                } else {
                    el.html('<option>Gagal memuat data</option>');
                }
            });
        }
        
        // Fungsi Set Text ke Hidden Input
        function setText(el, target) { 
            var txt = $(el).find('option:selected').text();
            if(txt && txt !== 'Memuat data...' && txt !== '-- Pilih --') {
                $(target).val(txt); 
            }
        }

        // 1. Initial Load (Provinsi)
        loadR('dw_fetch_provinces', null, els.prov, data.prov, function(){
            // Chain Load jika ada data tersimpan
            if(data.prov) {
                loadR('dw_fetch_regencies', data.prov, els.kota, data.kota, function(){
                    if(data.kota) {
                        loadR('dw_fetch_districts', data.kota, els.kec, data.kec, function(){
                            if(data.kec) {
                                loadR('dw_fetch_villages', data.kec, els.kel, data.kel);
                            }
                        });
                    }
                });
            }
        });

        // 2. Event Listeners (Change)
        els.prov.change(function(){ 
            setText(this, '#input_provinsi_nama'); 
            loadR('dw_fetch_regencies', $(this).val(), els.kota, null); 
            els.kota.val(''); els.kec.empty().prop('disabled',true); els.kel.empty().prop('disabled',true); 
        });

        els.kota.change(function(){ 
            setText(this, '#input_kota_nama'); 
            loadR('dw_fetch_districts', $(this).val(), els.kec, null); 
            els.kec.val(''); els.kel.empty().prop('disabled',true); 
        });

        els.kec.change(function(){ 
            setText(this, '#input_kecamatan_nama'); 
            loadR('dw_fetch_villages', $(this).val(), els.kel, null);
            els.kel.val('');
        });

        els.kel.change(function(){ 
            setText(this, '#input_kelurahan_nama'); 
        });
    });
</script>

<?php get_footer(); ?>