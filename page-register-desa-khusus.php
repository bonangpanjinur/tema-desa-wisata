<?php
/**
 * Template Name: Form Pendaftaran Desa Khusus
 * Description: Halaman registrasi khusus Admin Desa dengan Generator ID Wilayah Otomatis.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Hanya guest yang bisa akses
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/dashboard-desa') );
    exit;
}

$success_message = '';
$error_message = '';

if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_reg_desa_nonce']) && wp_verify_nonce($_POST['dw_reg_desa_nonce'], 'dw_reg_desa_action') ) {
    global $wpdb;
    
    // 1. Ambil Data User
    $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']);
    $email        = sanitize_email($_POST['email']);
    $username     = sanitize_user($_POST['username']);
    $password     = $_POST['password'];
    
    // 2. Ambil Data Wilayah (Hidden Inputs dari JS)
    $nama_desa    = sanitize_text_field($_POST['nama_desa']);
    
    // Data Text Wilayah (Penting untuk Generator ID)
    $provinsi_txt  = sanitize_text_field($_POST['provinsi_text']);
    $kabupaten_txt = sanitize_text_field($_POST['kabupaten_text']);
    $kecamatan_txt = sanitize_text_field($_POST['kecamatan_text']);
    $kelurahan_txt = sanitize_text_field($_POST['kelurahan_text']); // Ini nama desa administratif

    // Data ID API
    $api_prov_id = sanitize_text_field($_POST['api_provinsi_id']);
    $api_kab_id  = sanitize_text_field($_POST['api_kabupaten_id']);
    $api_kec_id  = sanitize_text_field($_POST['api_kecamatan_id']);
    $api_kel_id  = sanitize_text_field($_POST['api_kelurahan_id']);
    
    // 3. Validasi
    if ( username_exists($username) || email_exists($email) ) {
        $error_message = 'Username atau Email sudah terdaftar.';
    } elseif ( empty($provinsi_txt) || empty($kabupaten_txt) || empty($kelurahan_txt) ) {
        $error_message = 'Harap lengkapi data wilayah (Provinsi hingga Desa/Kelurahan).';
    } else {
        
        // --- LOGIKA GENERATOR ID (Backend Fallback) ---
        // Helper untuk ekstrak 3 huruf
        $get_code = function($text, $type = '') {
            if (empty($text)) return 'XXX';
            $clean = trim(strtolower($text));
            $clean = preg_replace('/^(provinsi|kabupaten|kota|desa|kelurahan)\s+/', '', $clean);
            
            // Mapping Khusus
            if ($type === 'province') {
                if ($clean == 'jawa barat') return 'JAB';
                if ($clean == 'jawa tengah') return 'JTG';
                if ($clean == 'jawa timur') return 'JTM';
                if (strpos($clean, 'jakarta') !== false) return 'DKI';
                if (strpos($clean, 'yogyakarta') !== false) return 'DIY';
            }
            
            $clean_no_space = str_replace(' ', '', $clean);
            return strtoupper(substr($clean_no_space, 0, 3));
        };

        $c_prov = $get_code($provinsi_txt, 'province');
        $c_kab  = $get_code($kabupaten_txt);
        $c_des  = $get_code($kelurahan_txt); // Menggunakan nama kelurahan administratif
        $rand   = rand(1000, 9999);

        $kode_referral_baru = "$c_prov-$c_kab-$c_des-$rand";
        
        // Cek Unik
        $table_desa = $wpdb->prefix . 'dw_desa';
        while( $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_desa WHERE kode_referral = %s", $kode_referral_baru)) ) {
            $rand = rand(1000, 9999);
            $kode_referral_baru = "$c_prov-$c_kab-$c_des-$rand";
        }
        // ----------------------------------------------

        // 4. Buat User WordPress
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $email,
            'display_name' => $nama_lengkap,
            'role'       => 'admin_desa'
        ]);

        if ( ! is_wp_error($user_id) ) {
            // 5. Insert ke Tabel Desa dengan ID Baru
            $wpdb->insert($table_desa, [
                'id_user_desa'   => $user_id,
                'nama_desa'      => $nama_desa, // Nama branding desa wisata
                'slug_desa'      => sanitize_title($nama_desa . '-' . rand(10,99)),
                'kode_referral'  => $kode_referral_baru, // <--- ID DISIMPAN DISINI
                'status'         => 'pending',
                
                // Simpan Data Wilayah Lengkap
                'api_provinsi_id' => $api_prov_id,
                'api_kabupaten_id'=> $api_kab_id,
                'api_kecamatan_id'=> $api_kec_id,
                'api_kelurahan_id'=> $api_kel_id,
                'provinsi'        => $provinsi_txt,
                'kabupaten'       => $kabupaten_txt,
                'kecamatan'       => $kecamatan_txt,
                'kelurahan'       => $kelurahan_txt,
                
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            ]);

            $success_message = 'Pendaftaran Berhasil! Kode Wilayah Anda: <strong>'.$kode_referral_baru.'</strong>. Silakan login.';
        } else {
            $error_message = $user_id->get_error_message();
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
        
        <div class="text-center">
            <h2 class="mt-2 text-3xl font-extrabold text-gray-900">Daftar Desa Wisata</h2>
            <p class="mt-2 text-sm text-gray-600">Buat akun untuk mengelola potensi desamu.</p>
        </div>

        <?php if($success_message): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0"><svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></div>
                    <div class="ml-3"><p class="text-sm text-green-700"><?php echo $success_message; ?></p>
                    <p class="mt-2"><a href="<?php echo home_url('/login'); ?>" class="font-bold underline">Klik disini untuk Login</a></p></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg></div>
                    <div class="ml-3"><p class="text-sm text-red-700"><?php echo $error_message; ?></p></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if(!$success_message): ?>
        <form class="mt-8 space-y-6" action="" method="POST">
            <?php wp_nonce_field('dw_reg_desa_action', 'dw_reg_desa_nonce'); ?>
            
            <!-- Akun -->
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Admin</label>
                    <input name="nama_lengkap" type="text" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Nama Admin">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input name="email" type="email" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="email@contoh.com">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input name="username" type="text" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="user_desa">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input name="password" type="password" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="******">
                    </div>
                </div>
            </div>

            <!-- Data Desa & Wilayah -->
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 space-y-4">
                <h3 class="text-blue-800 font-semibold text-sm uppercase tracking-wide border-b border-blue-200 pb-2">Lokasi & Nama Desa</h3>
                
                <div>
                    <label class="block text-xs font-semibold text-blue-700 uppercase mb-1">Provinsi</label>
                    <select id="reg_prov" name="api_provinsi_id" class="block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Memuat...</option>
                    </select>
                    <input type="hidden" name="provinsi_text" id="txt_prov">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-blue-700 uppercase mb-1">Kabupaten / Kota</label>
                    <select id="reg_kota" name="api_kabupaten_id" class="block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" disabled>
                        <option value="">Pilih Provinsi Dahulu</option>
                    </select>
                    <input type="hidden" name="kabupaten_text" id="txt_kota">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-blue-700 uppercase mb-1">Kecamatan</label>
                        <select id="reg_kec" name="api_kecamatan_id" class="block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" disabled>
                            <option value="">-</option>
                        </select>
                        <input type="hidden" name="kecamatan_text" id="txt_kec">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-blue-700 uppercase mb-1">Desa/Kel</label>
                        <select id="reg_desa" name="api_kelurahan_id" class="block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" disabled>
                            <option value="">-</option>
                        </select>
                        <input type="hidden" name="kelurahan_text" id="txt_desa">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Branding Desa Wisata</label>
                    <input name="nama_desa" type="text" required class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Contoh: Desa Wisata Penglipuran">
                    <p class="text-xs text-gray-500 mt-1">Nama yang akan tampil di halaman depan.</p>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-lg hover:shadow-xl">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    Daftar Sekarang
                </button>
            </div>
            
            <div class="text-center text-sm text-gray-500">
                Sudah punya akun? <a href="<?php echo home_url('/login'); ?>" class="font-medium text-blue-600 hover:text-blue-500">Login</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(document).ready(function($){
    // URL Ajax WordPress
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    var nonce = "<?php echo wp_create_nonce('dw_region_nonce'); ?>";

    function loadRegion(action, id, target, nextTarget) {
        $.get(ajaxurl, { action: action, province_id: id, regency_id: id, district_id: id, nonce: nonce }, function(res) {
            if(res.success) {
                target.empty().append('<option value="">Pilih...</option>');
                $.each(res.data, function(i, v){
                    target.append('<option value="'+v.id+'">'+v.name+'</option>');
                });
                target.prop('disabled', false);
            }
        });
    }

    // Set Text input hidden saat dropdown berubah
    function setText(el, targetId) {
        $(targetId).val($(el).find('option:selected').text());
    }

    // Load Provinsi Awal
    loadRegion('dw_fetch_provinces', null, $('#reg_prov'));

    $('#reg_prov').change(function(){
        setText(this, '#txt_prov');
        $('#reg_kota').empty().append('<option>Loading...</option>');
        $('#reg_kec, #reg_desa').empty().prop('disabled', true);
        loadRegion('dw_fetch_regencies', $(this).val(), $('#reg_kota'));
    });

    $('#reg_kota').change(function(){
        setText(this, '#txt_kota');
        $('#reg_kec').empty().append('<option>Loading...</option>');
        $('#reg_desa').empty().prop('disabled', true);
        loadRegion('dw_fetch_districts', $(this).val(), $('#reg_kec'));
    });

    $('#reg_kec').change(function(){
        setText(this, '#txt_kec');
        $('#reg_desa').empty().append('<option>Loading...</option>');
        loadRegion('dw_fetch_villages', $(this).val(), $('#reg_desa'));
    });

    $('#reg_desa').change(function(){
        setText(this, '#txt_desa');
    });
});
</script>

<?php get_footer(); ?>