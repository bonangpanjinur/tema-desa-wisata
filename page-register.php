<?php
/**
 * Template Name: Halaman Register
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url('/dashboard-toko') );
    exit;
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Daftar Akun Baru
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Sudah punya akun?
            <a href="<?php echo home_url('/login'); ?>" class="font-medium text-primary hover:text-green-500">
                Masuk di sini
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            <!-- Alert Container -->
            <div id="register-alert" class="hidden mb-4 p-4 rounded text-sm text-white"></div>

            <form id="dw-register-form" class="space-y-6">
                <!-- Nama Lengkap -->
                <div>
                    <label for="fullname" class="block text-sm font-medium text-gray-700"> Nama Lengkap </label>
                    <div class="mt-1">
                        <input id="fullname" name="fullname" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="reg_username" class="block text-sm font-medium text-gray-700"> Username </label>
                    <div class="mt-1">
                        <input id="reg_username" name="username" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>

                <!-- Email & No HP Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700"> Email </label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="no_hp" class="block text-sm font-medium text-gray-700"> No. WhatsApp </label>
                        <div class="mt-1">
                            <input id="no_hp" name="no_hp" type="text" required placeholder="0812..." class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="reg_password" class="block text-sm font-medium text-gray-700"> Password </label>
                    <div class="mt-1">
                        <input id="reg_password" name="password" type="password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="terms" class="ml-2 block text-sm text-gray-900">
                        Saya setuju dengan <a href="#" class="text-primary hover:underline">Syarat & Ketentuan</a>
                    </label>
                </div>

                <div>
                    <button type="submit" id="btn-reg-submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <span id="btn-reg-text">Daftar Sekarang</span>
                        <i id="btn-reg-loader" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>