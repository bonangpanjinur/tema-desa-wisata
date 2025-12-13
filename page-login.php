<?php
/* Template Name: Halaman Login API */
get_header(); 
?>

<div class="flex flex-col h-full bg-white px-6 py-10 min-h-screen">
    
    <div class="mb-8 text-center mt-10">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 mx-auto mb-4 shadow-sm">
            <i class="fas fa-leaf text-4xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Selamat Datang</h2>
        <p class="text-gray-500 text-sm">Masuk untuk mengelola pesanan & toko</p>
    </div>

    <!-- Alert Box -->
    <div id="login-alert" class="hidden mb-4 p-4 rounded-lg text-sm"></div>

    <!-- Login Form -->
    <form id="api-login-form" class="space-y-5">
        
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1 ml-1 uppercase tracking-wide">Username / Email</label>
            <div class="relative group">
                <i class="fas fa-user absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition"></i>
                <input type="text" id="username" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 focus:bg-white transition text-sm text-gray-800 placeholder-gray-400 shadow-sm" placeholder="Masukkan username" required>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1 ml-1 uppercase tracking-wide">Password</label>
            <div class="relative group">
                <i class="fas fa-lock absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition"></i>
                <input type="password" id="password" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 focus:bg-white transition text-sm text-gray-800 placeholder-gray-400 shadow-sm" placeholder="Masukkan password" required>
            </div>
            <div class="text-right mt-2">
                <a href="<?php echo home_url('/lupa-password'); ?>" class="text-xs text-emerald-600 font-bold hover:underline">Lupa Password?</a>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" id="btn-login" class="w-full bg-emerald-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 transform active:scale-95 transition flex justify-center items-center gap-2">
                <span>Masuk Sekarang</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>

    </form>

    <div class="mt-8 text-center">
        <p class="text-gray-500 text-sm">Belum punya akun?</p>
        <a href="<?php echo home_url('/register'); ?>" class="text-emerald-600 font-bold text-sm hover:underline">Daftar Akun Baru</a>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Ambil Base URL API dari dwData (dilocalize di functions.php) atau fallback manual
    // Pastikan functions.php melocalize script dengan handle 'dw-api-js' atau sejenisnya
    const API_BASE = (typeof dwData !== 'undefined') ? dwData.api_url : '<?php echo home_url("/wp-json/dw/v1/"); ?>';

    $('#api-login-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#btn-login');
        const $alert = $('#login-alert');
        const username = $('#username').val().trim();
        const password = $('#password').val();

        // UI Loading
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        $alert.addClass('hidden').removeClass('bg-red-50 text-red-600 bg-green-50 text-green-600');

        $.ajax({
            url: API_BASE + 'auth/login',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            success: function(response) {
                // 1. Simpan Token ke LocalStorage (PENTING untuk fitur Pedagang)
                if (response.token) {
                    localStorage.setItem('dw_jwt_token', response.token);
                    localStorage.setItem('dw_user_data', JSON.stringify(response.user));
                    
                    // Simpan Refresh Token jika ada
                    if (response.refresh_token) {
                        localStorage.setItem('dw_refresh_token', response.refresh_token);
                    }

                    $alert.html('<i class="fas fa-check-circle"></i> Login berhasil! Mengalihkan...').addClass('bg-green-50 text-green-600 block').removeClass('hidden');
                    
                    // 2. Redirect ke Akun Saya
                    setTimeout(function() {
                        window.location.href = '<?php echo home_url("/akun-saya"); ?>';
                    }, 1000);
                } else {
                    $btn.prop('disabled', false).html('<span>Masuk Sekarang</span> <i class="fas fa-arrow-right"></i>');
                    $alert.html('<i class="fas fa-exclamation-circle"></i> Token tidak diterima dari server.').addClass('bg-red-50 text-red-600 block').removeClass('hidden');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<span>Masuk Sekarang</span> <i class="fas fa-arrow-right"></i>');
                
                let errorMsg = 'Terjadi kesalahan jaringan.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.code) {
                    errorMsg = 'Error: ' + xhr.responseJSON.code;
                }
                
                $alert.html('<i class="fas fa-exclamation-triangle"></i> ' + errorMsg).addClass('bg-red-50 text-red-600 block').removeClass('hidden');
            }
        });
    });
});
</script>

<?php get_footer(); ?>