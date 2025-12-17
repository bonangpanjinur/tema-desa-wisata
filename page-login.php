<?php
/**
 * Template Name: Halaman Login Custom
 * Description: Login page with role-based redirection.
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
        $error_message = $user->get_error_message();
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
            // Cek apakah ada redirect url sebelumnya (misal dari cart)
            if ( isset($_GET['redirect_to']) ) {
                wp_redirect( $_GET['redirect_to'] );
            } else {
                wp_redirect( home_url('/akun-saya') );
            }
        }
        exit;
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-orange-50 to-transparent -z-10"></div>
    <div class="absolute -top-20 -right-20 w-96 h-96 bg-yellow-100 rounded-full blur-3xl opacity-50 -z-10"></div>
    <div class="absolute top-40 -left-20 w-72 h-72 bg-orange-100 rounded-full blur-3xl opacity-50 -z-10"></div>

    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-gray-100 relative z-10">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 mb-4">
                <i class="fas fa-sign-in-alt text-xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900">Selamat Datang Kembali</h2>
            <p class="mt-2 text-sm text-gray-600">
                Masuk ke akun Desa Wisata Anda
            </p>
        </div>

        <?php if ( !empty($error_message) ) : ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo $error_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="" method="POST">
            <?php wp_nonce_field('dw_login_action', 'dw_login_nonce'); ?>
            <div class="rounded-md shadow-sm -space-y-px">
                <div class="mb-4">
                    <label for="email-address" class="block text-sm font-medium text-gray-700 mb-1">Email atau Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-user"></i>
                        </div>
                        <input id="email-address" name="log" type="text" required class="appearance-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm transition duration-300" placeholder="Masukkan email/username">
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input id="password" name="pwd" type="password" required class="appearance-none relative block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm transition duration-300" placeholder="Masukkan kata sandi">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="rememberme" type="checkbox" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Ingat saya
                    </label>
                </div>

                <div class="text-sm">
                    <a href="<?php echo home_url('/lupa-password'); ?>" class="font-medium text-orange-600 hover:text-orange-500">
                        Lupa kata sandi?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 shadow-lg shadow-orange-200 transition-all duration-300 transform hover:-translate-y-1">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-sign-in-alt group-hover:text-orange-200 transition"></i>
                    </span>
                    Masuk Sekarang
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Belum punya akun? 
                <a href="<?php echo home_url('/register'); ?>" class="font-bold text-orange-600 hover:text-orange-500 transition">
                    Daftar disini
                </a>
            </p>
        </div>
    </div>
</div>

<?php get_footer(); ?>