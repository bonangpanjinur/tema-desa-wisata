<?php
/**
 * Template Name: Login Page
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/dashboard-toko') ); // Atau halaman akun
    exit;
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Masuk ke Akun Anda
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Atau
            <a href="<?php echo home_url('/register'); ?>" class="font-medium text-primary hover:text-green-500">
                daftar akun baru
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            <!-- Alert Container (JS will populate this) -->
            <div id="login-alert" class="hidden mb-4 p-4 rounded text-sm text-white"></div>

            <form id="dw-login-form" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700"> Username / Email </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="text" autocomplete="username" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700"> Password </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900"> Ingat saya </label>
                    </div>

                    <div class="text-sm">
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="font-medium text-primary hover:text-green-500">
                            Lupa password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" id="btn-submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <span id="btn-text">Masuk</span>
                        <i id="btn-loader" class="fas fa-spinner fa-spin ml-2 hidden"></i>
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
                            Desa Wisata Core System
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>