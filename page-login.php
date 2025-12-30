<?php
/**
 * Template Name: Halaman Login
 * Description: Halaman login kustom dengan desain UI/UX modern (Premium Style).
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    $roles = ( array ) $current_user->roles;

    if ( in_array( 'administrator', $roles ) || in_array( 'editor_desa', $roles ) || in_array( 'pedagang', $roles ) || in_array( 'dw_ojek', $roles ) || in_array( 'verifikator_umkm', $roles ) ) {
        wp_safe_redirect( home_url( '/dashboard' ) );
    } else {
        wp_safe_redirect( home_url( '/akun-saya' ) );
    }
    exit;
}

// Proses Login
$login_error = '';
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['dw_login_submit'] ) ) {
    
    $creds = array(
        'user_login'    => sanitize_text_field( $_POST['log'] ),
        'user_password' => $_POST['pwd'],
        'remember'      => isset( $_POST['rememberme'] ),
    );

    $user = wp_signon( $creds, false );

    if ( is_wp_error( $user ) ) {
        if ( $user->get_error_code() == 'invalid_username' || $user->get_error_code() == 'invalid_email' ) {
            $login_error = 'Username atau email tidak ditemukan.';
        } elseif ( $user->get_error_code() == 'incorrect_password' ) {
            $login_error = 'Password yang Anda masukkan salah.';
        } else {
            $login_error = $user->get_error_message();
        }
    } else {
        wp_set_current_user( $user->ID );
        $roles = ( array ) $user->roles;
        
        if ( in_array( 'administrator', $roles ) || in_array( 'editor_desa', $roles ) || in_array( 'pedagang', $roles ) || in_array( 'dw_ojek', $roles ) || in_array( 'verifikator_umkm', $roles ) ) {
            wp_safe_redirect( home_url( '/dashboard' ) );
        } else {
            wp_safe_redirect( home_url( '/akun-saya' ) );
        }
        exit;
    }
}

get_header(); 
?>

<!-- Alpine.js (Optional jika header sudah memuat, tapi aman jika di-include lagi dengan defer) -->
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans relative overflow-hidden">
    
    <!-- Background Decoration (Konsisten dengan Register) -->
    <div class="absolute top-0 left-0 w-full h-[600px] bg-gradient-to-b from-orange-50 to-transparent -z-10"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-yellow-100 rounded-full blur-3xl opacity-40 -z-10"></div>
    <div class="absolute top-1/2 -left-24 w-72 h-72 bg-orange-100 rounded-full blur-3xl opacity-40 -z-10"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center mb-8">
            <a href="<?php echo home_url(); ?>" class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-lg shadow-orange-200 mb-6 transform hover:scale-105 transition-transform duration-300">
                <i class="fas fa-sign-in-alt text-3xl"></i>
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Selamat Datang Kembali
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Masuk untuk mengakses dashboard & akun Anda
            </p>
        </div>
    </div>

    <div class="mt-2 sm:mx-auto sm:w-full sm:max-w-md relative z-10">
        <div class="bg-white py-8 px-4 shadow-2xl shadow-gray-100 sm:rounded-2xl sm:px-10 border border-gray-100">
            
            <?php if ( ! empty( $login_error ) ) : ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg flex items-start gap-3 animate-pulse">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <h3 class="text-sm font-bold text-red-800">Login Gagal</h3>
                        <p class="text-sm text-red-700 mt-1"><?php echo $login_error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST" id="loginform">
                
                <div>
                    <label for="user_login" class="block text-sm font-bold text-gray-700 mb-1">Username atau Email</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="log" id="user_login" required 
                               class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                               placeholder="Masukkan username atau email">
                    </div>
                </div>

                <div>
                    <label for="user_pass" class="block text-sm font-bold text-gray-700 mb-1">Password</label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="pwd" id="user_pass" required 
                               class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                               placeholder="Masukkan kata sandi">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="rememberme" name="rememberme" type="checkbox" value="forever" 
                               class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded cursor-pointer">
                        <label for="rememberme" class="ml-2 block text-sm text-gray-700 cursor-pointer select-none">
                            Ingat Saya
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="<?php echo home_url('/lupa-password'); ?>" class="font-medium text-orange-600 hover:text-orange-500 transition-colors">
                            Lupa Password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" name="dw_login_submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-300 transform hover:-translate-y-0.5">
                        Masuk Sekarang <i class="fas fa-arrow-right ml-2 mt-0.5"></i>
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

                <div class="mt-6">
                    <a href="<?php echo home_url('/register'); ?>" 
                       class="w-full flex justify-center py-3 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <i class="fas fa-store text-orange-500 mr-2 mt-0.5"></i> Daftar Baru (Pedagang / Wisatawan)
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