<?php
/**
 * Template Name: Halaman Login Custom
 * Description: Login page with enhanced UI/UX for Merchants, Villages, and Buyers.
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    if ( in_array( 'admin_desa', (array) $current_user->roles ) ) {
        wp_redirect( home_url('/dashboard-desa') );
    } elseif ( in_array( 'pedagang', (array) $current_user->roles ) ) {
        wp_redirect( home_url('/dashboard-toko') );
    } elseif ( in_array( 'administrator', (array) $current_user->roles ) ) {
        wp_redirect( admin_url() );
    } else {
        wp_redirect( home_url('/akun-saya') );
    }
    exit;
}

$error_message = '';

// Proses Login
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_login_nonce']) && wp_verify_nonce($_POST['dw_login_nonce'], 'dw_login_action') ) {
    $creds = array(
        'user_login'    => sanitize_text_field($_POST['log']),
        'user_password' => $_POST['pwd'],
        'remember'      => isset($_POST['rememberme']),
    );

    $user = wp_signon( $creds, false );

    if ( is_wp_error( $user ) ) {
        // Pesan error yang lebih user-friendly
        if (strpos($user->get_error_message(), 'password') !== false) {
            $error_message = 'Kata sandi yang Anda masukkan salah. Silakan coba lagi.';
        } elseif (strpos($user->get_error_message(), 'username') !== false) {
            $error_message = 'Username atau email tidak ditemukan.';
        } else {
            $error_message = strip_tags($user->get_error_message());
        }
    } else {
        // Sukses Login, Cek Role untuk Redirect
        $current_user = wp_get_current_user();
        if ( in_array( 'admin_desa', (array) $current_user->roles ) ) {
            wp_redirect( home_url('/dashboard-desa') );
        } elseif ( in_array( 'pedagang', (array) $current_user->roles ) ) {
            wp_redirect( home_url('/dashboard-toko') );
        } elseif ( in_array( 'administrator', (array) $current_user->roles ) ) {
            wp_redirect( admin_url() );
        } else {
            if ( isset($_GET['redirect_to']) ) {
                wp_redirect( $_GET['redirect_to'] );
            } else {
                wp_redirect( home_url('/akun-saya') );
            }
        }
        exit;
    }
}

// Nomor WhatsApp Admin (Untuk pendaftaran desa)
$admin_wa = '6281234567890'; 
$pesan_wa = urlencode('Halo Admin, saya perwakilan dari Desa ingin mendaftarkan desa wisata kami. Mohon infonya.');

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans relative overflow-hidden">
    
    <!-- Background Decoration -->
    <div class="absolute top-0 left-0 w-full h-[600px] bg-gradient-to-b from-orange-50 to-transparent -z-10"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-yellow-100 rounded-full blur-3xl opacity-40 -z-10"></div>
    <div class="absolute top-1/2 -left-24 w-72 h-72 bg-orange-100 rounded-full blur-3xl opacity-40 -z-10"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo / Icon Header -->
        <div class="text-center">
            <a href="<?php echo home_url(); ?>" class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-lg shadow-orange-200 mb-6 transform hover:scale-105 transition-transform duration-300">
                <i class="fas fa-sign-in-alt text-3xl"></i>
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Masuk ke Akun
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Akses dashboard untuk Pedagang, Desa, atau Pembeli
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10">
        <div class="bg-white py-8 px-4 shadow-2xl shadow-gray-100 sm:rounded-2xl sm:px-10 border border-gray-100">
            
            <!-- Error Notification -->
            <?php if ( !empty($error_message) ) : ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg animate-pulse flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <h3 class="text-sm font-bold text-red-800">Login Gagal</h3>
                        <p class="text-sm text-red-700 mt-1"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST">
                <?php wp_nonce_field('dw_login_action', 'dw_login_nonce'); ?>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email atau Username</label>
                    <div class="mt-1 relative rounded-md shadow-sm group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                        </div>
                        <input id="email" name="log" type="text" autocomplete="email" required 
                            class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                            placeholder="Masukkan email/username">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                    <div class="mt-1 relative rounded-md shadow-sm group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                        </div>
                        <input id="password" name="pwd" type="password" autocomplete="current-password" required 
                            class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                            placeholder="Masukkan kata sandi">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="rememberme" type="checkbox" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded cursor-pointer">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                            Ingat saya
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="<?php echo home_url('/lupa-password'); ?>" class="font-medium text-orange-600 hover:text-orange-500 transition-colors">
                            Lupa kata sandi?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-300 transform hover:-translate-y-0.5">
                        Masuk Sekarang
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500 font-medium">Belum punya akun?</span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3">
                    <a href="<?php echo home_url('/register'); ?>" class="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-xl shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-orange-600 hover:border-orange-200 transition-all">
                        <span class="sr-only">Daftar sebagai Pembeli/Pedagang</span>
                        <i class="fas fa-user-plus mr-2 mt-0.5"></i> Daftar Baru
                    </a>
                </div>
            </div>
        </div>

        <!-- Section Khusus Pendaftaran Desa -->
        <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-5 text-center shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 -mr-4 -mt-4 w-16 h-16 bg-blue-100 rounded-full blur-xl opacity-50 group-hover:opacity-70 transition-opacity"></div>
            
            <h3 class="text-sm font-bold text-blue-900 mb-1 relative z-10">Pendaftaran Desa Wisata</h3>
            <p class="text-xs text-blue-600 mb-3 relative z-10">Khusus untuk Admin Desa yang ingin bergabung.</p>
            
            <a href="https://wa.me/<?php echo $admin_wa; ?>?text=<?php echo $pesan_wa; ?>" target="_blank" class="inline-flex items-center justify-center px-4 py-2 bg-white text-blue-600 text-xs font-bold rounded-lg border border-blue-200 hover:bg-blue-600 hover:text-white hover:border-transparent transition-all shadow-sm group-hover:shadow relative z-10">
                <i class="fab fa-whatsapp text-sm mr-1.5"></i> Hubungi Admin
            </a>
        </div>

        <!-- Footer Kecil -->
        <p class="mt-8 text-center text-xs text-gray-400">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Hak Cipta Dilindungi.
        </p>
    </div>
</div>

<?php get_footer(); ?>