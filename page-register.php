<?php
/**
 * Template Name: Halaman Register Custom
 * Description: Registration for Buyer, Merchant, and Ojek.
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
    $role       = sanitize_text_field($_POST['role_type']); // 'buyer', 'pedagang', 'ojek'
    $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']);
    
    // Validasi Dasar
    if ( username_exists($username) || email_exists($email) ) {
        $error_message = 'Username atau Email sudah terdaftar.';
    } else {
        // Tentukan Role WordPress
        $wp_role = 'subscriber'; // Default pembeli
        if ($role === 'pedagang') {
            $wp_role = 'pedagang';
        } elseif ($role === 'ojek') {
            $wp_role = 'ojek';
        }

        // Buat User WP
        $userdata = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'first_name' => $nama_lengkap,
            'role'       => $wp_role,
        );
    
        $user_id = wp_insert_user( $userdata );
    
        if ( ! is_wp_error( $user_id ) ) {
            // --- LOGIKA KHUSUS ROLE ---
            
            // 1. Logika Pedagang
            if ( $role === 'pedagang' ) {
                $nama_toko = sanitize_text_field($_POST['nama_toko']);
                $no_wa     = sanitize_text_field($_POST['no_wa']);
                
                update_user_meta( $user_id, 'nama_toko', $nama_toko );
                update_user_meta( $user_id, 'no_wa', $no_wa );
                update_user_meta( $user_id, 'status_verifikasi', 'pending' ); // Butuh verifikasi admin desa
            } 
            // 2. Logika Ojek (BARU)
            elseif ( $role === 'ojek' ) {
                $plat_nomor = sanitize_text_field($_POST['plat_nomor']);
                $no_wa      = sanitize_text_field($_POST['no_wa']); // Ojek juga butuh WA untuk koordinasi

                update_user_meta( $user_id, 'plat_nomor', $plat_nomor );
                update_user_meta( $user_id, 'no_wa', $no_wa );
                update_user_meta( $user_id, 'status_ojek', 'pending' ); // Ojek mungkin butuh verifikasi juga
                update_user_meta( $user_id, 'status_ketersediaan', 'offline' ); // Default offline
            }
            // 3. Logika Pembeli
            else {
                // Pembeli otomatis aktif
            }

            // Auto Login setelah register (Opsional, bisa dimatikan jika butuh verifikasi email)
            $creds = array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => true
            );
            $signon = wp_signon( $creds, false );
            
            // Redirect sesuai Role
            if ( $role === 'pedagang' ) {
                wp_redirect( home_url('/dashboard-toko') );
            } elseif ( $role === 'ojek' ) {
                wp_redirect( home_url('/dashboard-ojek') ); // Redirect ke dashboard ojek
            } else {
                wp_redirect( home_url('/akun-saya') );
            }
            exit;

        } else {
            $error_message = $user_id->get_error_message();
        }
    }
}
?>

<?php get_header(); ?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Bergabung dengan Desa Wisata
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Satu akun untuk jelajahi, belanja, berjualan, atau mengantar.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            <?php if($error_message): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4" role="alert">
                    <p class="text-sm text-red-700"><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST" x-data="{ role: 'buyer' }">
                <?php wp_nonce_field('dw_register_action', 'dw_register_nonce'); ?>
                
                <!-- Pilihan Role dengan UI Grid 3 Kolom -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Saya ingin mendaftar sebagai:</label>
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Pilihan Pembeli -->
                        <div @click="role = 'buyer'" 
                             :class="{ 'border-blue-500 bg-blue-50 text-blue-700': role === 'buyer', 'border-gray-200 hover:border-gray-300': role !== 'buyer' }"
                             class="cursor-pointer border rounded-lg p-3 text-center transition-all">
                            <i class="fas fa-user text-xl mb-1 block"></i>
                            <span class="text-xs font-bold block">Wisatawan</span>
                        </div>
                        
                        <!-- Pilihan Pedagang -->
                        <div @click="role = 'pedagang'" 
                             :class="{ 'border-orange-500 bg-orange-50 text-orange-700': role === 'pedagang', 'border-gray-200 hover:border-gray-300': role !== 'pedagang' }"
                             class="cursor-pointer border rounded-lg p-3 text-center transition-all">
                            <i class="fas fa-store text-xl mb-1 block"></i>
                            <span class="text-xs font-bold block">Pedagang</span>
                        </div>

                        <!-- Pilihan Ojek (BARU) -->
                        <div @click="role = 'ojek'" 
                             :class="{ 'border-green-500 bg-green-50 text-green-700': role === 'ojek', 'border-gray-200 hover:border-gray-300': role !== 'ojek' }"
                             class="cursor-pointer border rounded-lg p-3 text-center transition-all">
                            <i class="fas fa-motorcycle text-xl mb-1 block"></i>
                            <span class="text-xs font-bold block">Ojek</span>
                        </div>
                    </div>
                    <input type="hidden" name="role_type" :value="role" id="role_input">
                </div>

                <!-- Field Umum (Semua Role) -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" id="username" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                    <input type="email" name="email" id="email" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Field Khusus Pedagang -->
                <div x-show="role === 'pedagang'" x-transition class="bg-orange-50 p-4 rounded-md border border-orange-200 space-y-4">
                    <h4 class="text-sm font-bold text-orange-800 border-b border-orange-200 pb-2">Informasi Toko</h4>
                    <div>
                        <label for="nama_toko" class="block text-sm font-medium text-gray-700">Nama Toko / Usaha</label>
                        <input type="text" name="nama_toko" id="input_nama_toko" 
                               :required="role === 'pedagang'"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                    </div>
                </div>

                <!-- Field Khusus Ojek (BARU) -->
                <div x-show="role === 'ojek'" x-transition class="bg-green-50 p-4 rounded-md border border-green-200 space-y-4" style="display: none;">
                    <h4 class="text-sm font-bold text-green-800 border-b border-green-200 pb-2">Informasi Kendaraan</h4>
                    <div>
                        <label for="plat_nomor" class="block text-sm font-medium text-gray-700">Plat Nomor Kendaraan</label>
                        <input type="text" name="plat_nomor" id="input_plat_nomor" placeholder="Contoh: AB 1234 XY"
                               :required="role === 'ojek'"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        <p class="text-xs text-gray-500 mt-1">Pastikan plat nomor sesuai dengan kendaraan yang digunakan.</p>
                    </div>
                </div>

                <!-- Field Kontak (Dibagikan Pedagang & Ojek) -->
                <div x-show="role === 'pedagang' || role === 'ojek'" x-transition class="bg-gray-50 p-4 rounded-md border border-gray-200 space-y-4" style="display: none;">
                     <div>
                        <label for="no_wa" class="block text-sm font-medium text-gray-700">Nomor WhatsApp</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                +62
                            </span>
                            <input type="text" name="no_wa" id="input_no_wa" placeholder="81234567890"
                                   :required="role === 'pedagang' || role === 'ojek'"
                                   class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Nomor ini akan digunakan untuk notifikasi pesanan.</p>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Daftar Sekarang
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Sudah punya akun?
                        </span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="<?php echo home_url('/login'); ?>" class="font-medium text-blue-600 hover:text-blue-500">
                        Masuk disini
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js untuk Interaktivitas (Pastikan diload) -->
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<?php get_footer(); ?>