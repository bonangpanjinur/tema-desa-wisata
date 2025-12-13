<?php
/* Template Name: Halaman Login */
get_header(); 
?>

<div class="flex flex-col h-full bg-white px-6 py-10">
    
    <div class="mb-8 text-center mt-10">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 mx-auto mb-4">
            <i class="fas fa-leaf text-4xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Selamat Datang</h2>
        <p class="text-gray-500 text-sm">Silakan masuk untuk melanjutkan</p>
    </div>

    <!-- Login Form -->
    <form name="loginform" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post" class="space-y-4">
        
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1 ml-1">Username / Email</label>
            <div class="relative">
                <i class="fas fa-user absolute left-4 top-3.5 text-gray-400"></i>
                <input type="text" name="log" id="user_login" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 transition text-sm text-gray-700" placeholder="Masukkan username">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1 ml-1">Password</label>
            <div class="relative">
                <i class="fas fa-lock absolute left-4 top-3.5 text-gray-400"></i>
                <input type="password" name="pwd" id="user_pass" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 transition text-sm text-gray-700" placeholder="Masukkan password">
            </div>
            <div class="text-right mt-2">
                <a href="<?php echo home_url('/lupa-password'); ?>" class="text-xs text-emerald-600 font-medium">Lupa Password?</a>
            </div>
        </div>

        <div class="pt-4">
            <button type="submit" name="wp-submit" id="wp-submit" class="w-full bg-emerald-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition">
                Masuk Sekarang
            </button>
            <input type="hidden" name="redirect_to" value="<?php echo home_url('/akun-saya'); ?>">
        </div>

    </form>

    <div class="mt-8 text-center">
        <p class="text-gray-500 text-sm">Belum punya akun?</p>
        <a href="<?php echo home_url('/register'); ?>" class="text-emerald-600 font-bold text-sm">Daftar Akun Baru</a>
    </div>

</div>

<?php get_footer(); ?>