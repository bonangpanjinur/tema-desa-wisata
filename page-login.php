<?php
/**
 * Template Name: Halaman Login Custom
 */

// Redirect Pintar jika sudah login
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    
    // Cek Role & Redirect
    if ( in_array( 'pedagang', (array) $current_user->roles ) ) {
        wp_redirect( home_url( '/dashboard-toko/' ) );
    } elseif ( in_array( 'pembeli', (array) $current_user->roles ) ) {
        // Cek apakah ada parameter redirect (misal dari halaman checkout)
        if ( isset($_GET['redirect_to']) ) {
            wp_redirect( esc_url($_GET['redirect_to']) );
        } else {
            wp_redirect( home_url( '/akun-saya/' ) );
        }
    } else {
        wp_redirect( admin_url() ); // Admin ke WP-Admin
    }
    exit;
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo Text -->
        <h2 class="mt-6 text-center text-3xl font-extrabold text-primary">
            Masuk Akun
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Selamat datang kembali di Desa Wisata
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-soft rounded-2xl border border-gray-100 sm:px-10">
            
            <!-- Alert Container -->
            <div id="login-alert" class="hidden mb-4 p-4 rounded-lg text-sm flex items-center gap-2"></div>

            <form id="dw-login-form" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1"> Username / Email </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph-bold ph-user text-gray-400"></i>
                        </div>
                        <input id="username" name="username" type="text" autocomplete="username" required class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="Masukkan username">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1"> Password </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph-bold ph-lock-key text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="Masukkan password">
                         <button type="button" onclick="togglePasswordVisibility('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="ph-bold ph-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900"> Ingat saya </label>
                    </div>

                    <div class="text-sm">
                        <a href="<?php echo home_url('/lupa-password'); ?>" class="font-medium text-primary hover:text-emerald-600 transition">
                            Lupa password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" id="btn-submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all transform active:scale-95">
                        <span id="btn-text">Masuk Sekarang</span>
                        <i id="btn-loader" class="ph-bold ph-spinner animate-spin ml-2 hidden text-lg"></i>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500"> Belum punya akun? </span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="<?php echo home_url('/register'); ?>" class="font-bold text-primary hover:text-emerald-600 transition">
                        Daftar Gratis
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('ph-eye', 'ph-eye-slash');
        } else {
            input.type = "password";
            icon.classList.replace('ph-eye-slash', 'ph-eye');
        }
    }
</script>

<?php get_footer(); ?>