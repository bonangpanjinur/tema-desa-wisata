<?php
/**
 * Template Name: Halaman Register Custom
 * Description: Registration for Buyer and Merchant.
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

$success_message = '';
$error_message = '';

if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_register_nonce']) && wp_verify_nonce($_POST['dw_register_nonce'], 'dw_register_action') ) {
    
    global $wpdb;
    $username   = sanitize_user($_POST['username']);
    $email      = sanitize_email($_POST['email']);
    $password   = $_POST['password'];
    $role       = sanitize_text_field($_POST['role_type']); // 'buyer' or 'pedagang'
    $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']);
    
    // Validasi
    if ( username_exists($username) || email_exists($email) ) {
        $error_message = 'Username atau Email sudah terdaftar.';
    } else {
        // Create WP User
        $userdata = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'first_name' => $nama_lengkap,
            'role'       => ($role === 'pedagang') ? 'pedagang' : 'subscriber'
        );

        $user_id = wp_insert_user( $userdata );

        if ( ! is_wp_error( $user_id ) ) {
            
            // Logic khusus Pedagang: Insert ke tabel dw_pedagang
            if ( $role === 'pedagang' ) {
                $nama_toko = sanitize_text_field($_POST['nama_toko']);
                $no_wa     = sanitize_text_field($_POST['no_wa']);
                
                // Buat slug toko dasar
                $slug_toko = sanitize_title($nama_toko);
                // Cek unik slug (sederhana)
                if($wpdb->get_var("SELECT id FROM {$wpdb->prefix}dw_pedagang WHERE slug_toko = '$slug_toko'")) {
                    $slug_toko .= '-' . time();
                }

                $wpdb->insert(
                    "{$wpdb->prefix}dw_pedagang",
                    array(
                        'id_user'            => $user_id,
                        'nama_toko'          => $nama_toko,
                        'slug_toko'          => $slug_toko,
                        'nama_pemilik'       => $nama_lengkap,
                        'nomor_wa'           => $no_wa,
                        'status_pendaftaran' => 'menunggu_desa', // Default pending
                        'status_akun'        => 'nonaktif' 
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
                );
            }

            // Auto Login setelah register (opsional, disini kita redirect ke login biar aman)
            $success_message = 'Pendaftaran berhasil! Silakan login.';
        } else {
            $error_message = $user_id->get_error_message();
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute top-0 right-0 w-full h-[500px] bg-gradient-to-b from-green-50 to-transparent -z-10"></div>
    <div class="absolute top-20 left-10 w-64 h-64 bg-green-100 rounded-full blur-3xl opacity-40 -z-10"></div>

    <div class="max-w-xl w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-gray-100 z-10">
        
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">Buat Akun Baru</h2>
            <p class="mt-2 text-sm text-gray-600">
                Bergabunglah dengan komunitas Desa Wisata
            </p>
        </div>

        <?php if ( !empty($error_message) ) : ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md mb-4">
                <p class="text-sm text-red-700 font-bold"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ( !empty($success_message) ) : ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-md mb-4 text-center">
                <p class="text-lg text-green-700 font-bold mb-2"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></p>
                <a href="<?php echo home_url('/login'); ?>" class="inline-block bg-green-600 text-white px-6 py-2 rounded-full font-bold hover:bg-green-700 transition">Ke Halaman Login</a>
            </div>
            <?php // Stop rendering form if success ?> 
            </div></div><?php get_footer(); return; ?>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex p-1 space-x-1 bg-gray-100 rounded-xl" x-data="{ role: 'buyer' }">
            <button @click="role = 'buyer'; document.getElementById('role_input').value = 'buyer'" 
                :class="role === 'buyer' ? 'bg-white text-gray-900 shadow' : 'text-gray-500 hover:text-gray-700'"
                class="w-full py-2.5 text-sm font-bold leading-5 rounded-lg transition duration-200">
                <i class="fas fa-shopping-bag mr-2"></i> Pembeli
            </button>
            <button @click="role = 'pedagang'; document.getElementById('role_input').value = 'pedagang'" 
                :class="role === 'pedagang' ? 'bg-white text-orange-600 shadow' : 'text-gray-500 hover:text-gray-700'"
                class="w-full py-2.5 text-sm font-bold leading-5 rounded-lg transition duration-200">
                <i class="fas fa-store mr-2"></i> Pedagang
            </button>
        </div>

        <!-- Info Desa -->
        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-500 mt-1"></i>
            <p class="text-xs text-blue-700">
                Ingin mendaftarkan akun untuk <strong>Pemerintah Desa</strong>? Silakan hubungi Admin Kabupaten untuk proses verifikasi dan pendaftaran.
            </p>
        </div>

        <form class="mt-8 space-y-6" action="" method="POST" id="registerForm">
            <?php wp_nonce_field('dw_register_action', 'dw_register_nonce'); ?>
            <input type="hidden" name="role_type" id="role_input" value="buyer">

            <div class="space-y-4">
                <!-- Field Umum -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                    <input name="nama_lengkap" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50 focus:bg-white transition">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Username</label>
                        <input name="username" type="text" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input name="email" type="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Kata Sandi</label>
                    <input name="password" type="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50 focus:bg-white transition">
                </div>

                <!-- Field Khusus Pedagang (JS Logic handled by x-data but simpler here with plain CSS/JS toggle) -->
                <div id="pedagang-fields" class="hidden space-y-4 border-t border-gray-200 pt-4 mt-4">
                    <h4 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Data Toko</h4>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Toko</label>
                        <input name="nama_toko" id="input_nama_toko" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nomor WhatsApp</label>
                        <input name="no_wa" id="input_no_wa" type="text" placeholder="08..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50 focus:bg-white transition">
                    </div>
                    <p class="text-xs text-gray-500">*Data lengkap lainnya (KTP, Rekening, Alamat) dapat dilengkapi di Dashboard Toko setelah mendaftar.</p>
                </div>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    Daftar Sekarang
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Sudah punya akun? 
                <a href="<?php echo home_url('/login'); ?>" class="font-bold text-orange-600 hover:text-orange-500 transition">
                    Masuk disini
                </a>
            </p>
        </div>
    </div>
</div>

<!-- Simple Script to Toggle Fields -->
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleInput = document.getElementById('role_input');
    const pedagangFields = document.getElementById('pedagang-fields');
    const inputNamaToko = document.getElementById('input_nama_toko');
    const inputNoWa = document.getElementById('input_no_wa');

    // Monitor changes (Alpine handles the UI state, we just ensure visibility matches)
    // Actually, let's just use a MutationObserver or a setInterval to check class or use Alpine completely.
    // Simpler approach compatible with the buttons above:
    
    window.setInterval(() => {
        if(roleInput.value === 'pedagang') {
            pedagangFields.classList.remove('hidden');
            inputNamaToko.setAttribute('required', 'required');
            inputNoWa.setAttribute('required', 'required');
        } else {
            pedagangFields.classList.add('hidden');
            inputNamaToko.removeAttribute('required');
            inputNoWa.removeAttribute('required');
        }
    }, 200);
});
</script>

<?php get_footer(); ?>