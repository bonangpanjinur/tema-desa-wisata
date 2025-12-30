<?php
/**
 * Template Name: Halaman Register Custom
 * Description: Pendaftaran khusus Wisatawan & Pedagang (Ojek & Verifikator via Admin).
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
    $role_input = sanitize_text_field($_POST['role_type']); // 'buyer' atau 'pedagang'
    $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']);
    
    // Validasi Role (Hanya izinkan buyer atau pedagang)
    $allowed_roles = ['buyer', 'pedagang'];
    if (!in_array($role_input, $allowed_roles)) {
        $role_input = 'buyer'; // Fallback ke buyer jika di-tamper
    }

    // Validasi Dasar
    if ( username_exists($username) || email_exists($email) ) {
        $error_message = 'Username atau Email sudah terdaftar. Silakan login atau gunakan email lain.';
    } else {
        // Tentukan Role WordPress
        $wp_role = 'subscriber'; // Default pembeli/wisatawan
        if ($role_input === 'pedagang') {
            $wp_role = 'pedagang';
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
            if ( $role_input === 'pedagang' ) {
                $nama_toko = sanitize_text_field($_POST['nama_toko']);
                $no_wa     = sanitize_text_field($_POST['no_wa']);
                
                update_user_meta( $user_id, 'nama_toko', $nama_toko );
                update_user_meta( $user_id, 'no_wa', $no_wa );
                // Status verifikasi default pending, menunggu verifikasi Admin/Verifikator
                update_user_meta( $user_id, 'status_verifikasi', 'pending' ); 
            } 
            
            // Auto Login setelah daftar
            $creds = array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => true
            );
            $signon = wp_signon( $creds, false );
            
            // Redirect sesuai Role
            if ( $role_input === 'pedagang' ) {
                wp_redirect( home_url('/dashboard-toko') ); // atau /dashboard sesuai router
            } else {
                wp_redirect( home_url('/akun-saya') );
            }
            exit;

        } else {
            $error_message = $user_id->get_error_message();
        }
    }
}

get_header(); 
?>

<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans relative overflow-hidden">
    
    <!-- Background Decoration -->
    <div class="absolute top-0 left-0 w-full h-[600px] bg-gradient-to-b from-orange-50 to-transparent -z-10"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-yellow-100 rounded-full blur-3xl opacity-40 -z-10"></div>
    <div class="absolute top-1/2 -left-24 w-72 h-72 bg-orange-100 rounded-full blur-3xl opacity-40 -z-10"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-xl">
        <div class="text-center mb-8">
            <a href="<?php echo home_url(); ?>" class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-lg shadow-orange-200 mb-6 transform hover:scale-105 transition-transform duration-300">
                <i class="fas fa-user-plus text-3xl"></i>
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Bergabung Sekarang
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Buat akun baru untuk mulai menjelajah atau berbisnis
            </p>
        </div>
    </div>

    <div class="mt-2 sm:mx-auto sm:w-full sm:max-w-xl relative z-10">
        <div class="bg-white py-8 px-4 shadow-2xl shadow-gray-100 sm:rounded-2xl sm:px-10 border border-gray-100">
            
            <?php if($error_message): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg animate-pulse flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <h3 class="text-sm font-bold text-red-800">Registrasi Gagal</h3>
                        <p class="text-sm text-red-700 mt-1"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST" x-data="{ role: 'buyer' }">
                <?php wp_nonce_field('dw_register_action', 'dw_register_nonce'); ?>
                
                <!-- Pilihan Role: 2 Grid (Wisatawan & Pedagang) -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3 text-center uppercase tracking-wider text-xs text-gray-400">Pilih Tipe Akun</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Wisatawan -->
                        <div @click="role = 'buyer'" 
                             :class="{ 'ring-2 ring-blue-500 bg-blue-50': role === 'buyer', 'border-gray-200 hover:border-blue-300 hover:bg-gray-50': role !== 'buyer' }"
                             class="cursor-pointer border rounded-xl p-4 text-center transition-all duration-200 relative group overflow-hidden">
                            <div class="mb-2">
                                <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center transition-colors" :class="role === 'buyer' ? 'bg-blue-200 text-blue-700' : 'bg-gray-100 text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-600'">
                                    <i class="fas fa-user text-xl"></i>
                                </div>
                            </div>
                            <span class="text-sm font-bold block" :class="role === 'buyer' ? 'text-blue-800' : 'text-gray-600'">Wisatawan</span>
                            <span class="text-xs text-gray-400 mt-1 block">Untuk belanja & wisata</span>
                            <div x-show="role === 'buyer'" class="absolute top-2 right-2 text-blue-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                        
                        <!-- Pedagang -->
                        <div @click="role = 'pedagang'" 
                             :class="{ 'ring-2 ring-orange-500 bg-orange-50': role === 'pedagang', 'border-gray-200 hover:border-orange-300 hover:bg-gray-50': role !== 'pedagang' }"
                             class="cursor-pointer border rounded-xl p-4 text-center transition-all duration-200 relative group overflow-hidden">
                            <div class="mb-2">
                                <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center transition-colors" :class="role === 'pedagang' ? 'bg-orange-200 text-orange-700' : 'bg-gray-100 text-gray-500 group-hover:bg-orange-100 group-hover:text-orange-600'">
                                    <i class="fas fa-store text-xl"></i>
                                </div>
                            </div>
                            <span class="text-sm font-bold block" :class="role === 'pedagang' ? 'text-orange-800' : 'text-gray-600'">Pedagang UMKM</span>
                            <span class="text-xs text-gray-400 mt-1 block">Jual produk & jasa</span>
                            <div x-show="role === 'pedagang'" class="absolute top-2 right-2 text-orange-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                    <input type="hidden" name="role_type" :value="role" id="role_input">
                </div>

                <div class="border-t border-gray-100 pt-4"></div>

                <!-- Field Umum (Semua Role) -->
                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" required 
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                               placeholder="Nama sesuai KTP">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" id="username" required 
                                   class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                                   placeholder="Tanpa spasi">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                            <input type="email" name="email" id="email" required 
                                   class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                                   placeholder="email@contoh.com">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" required 
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                               placeholder="Minimal 8 karakter">
                    </div>
                </div>

                <!-- Field Khusus Pedagang -->
                <div x-show="role === 'pedagang'" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="bg-orange-50 p-5 rounded-2xl border border-orange-100 space-y-4 shadow-sm mt-4">
                    <div class="flex items-center gap-2 mb-2 border-b border-orange-200 pb-2">
                        <i class="fas fa-store text-orange-500"></i>
                        <h4 class="text-sm font-bold text-orange-800">Detail Usaha</h4>
                    </div>
                    
                    <div>
                        <label for="nama_toko" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko / Usaha</label>
                        <input type="text" name="nama_toko" id="input_nama_toko" 
                               :required="role === 'pedagang'"
                               class="block w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-orange-500 focus:border-orange-500 sm:text-sm bg-white"
                               placeholder="Contoh: Keripik Pisang Bu Ani">
                    </div>

                    <div>
                        <label for="no_wa" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                        <div class="mt-1 flex rounded-xl shadow-sm">
                            <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-gray-300 bg-gray-100 text-gray-500 font-bold text-sm">
                                +62
                            </span>
                            <input type="text" name="no_wa" id="input_no_wa" 
                                   :required="role === 'pedagang'"
                                   class="flex-1 min-w-0 block w-full px-4 py-3 rounded-none rounded-r-xl border border-gray-300 focus:ring-orange-500 focus:border-orange-500 sm:text-sm"
                                   placeholder="81234567890">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Nomor aktif untuk notifikasi pesanan.</p>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-300 transform hover:-translate-y-0.5">
                        <i class="fas fa-paper-plane mr-2 mt-0.5"></i> Daftar Sekarang
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500 font-medium">Sudah punya akun?</span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="<?php echo home_url('/login'); ?>" class="text-base font-bold text-orange-600 hover:text-orange-500 transition-colors">
                        Masuk disini <i class="fas fa-arrow-right ml-1 text-sm"></i>
                    </a>
                </div>
                
                <div class="mt-6 text-center">
                    <div class="bg-blue-50 p-3 rounded-lg text-xs text-blue-700 border border-blue-100">
                        <i class="fas fa-info-circle mr-1"></i> 
                        <br>Untuk mendaftar <strong>Desa Wisata</strong>, silakan hubungi Admin.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Kecil -->
        <p class="mt-8 text-center text-xs text-gray-400">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Hak Cipta Dilindungi.
        </p>
    </div>
</div>

<?php get_footer(); ?>