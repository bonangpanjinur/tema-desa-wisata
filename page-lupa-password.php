<?php
/* Template Name: Halaman Lupa Password */

$error = '';
$success = '';

if (isset($_POST['user_login']) && isset($_POST['dw_lostpass_nonce']) && wp_verify_nonce($_POST['dw_lostpass_nonce'], 'dw_lostpass_action')) {
    
    $user_login = sanitize_text_field($_POST['user_login']);
    
    if (empty($user_login)) {
        $error = 'Masukkan email atau username Anda.';
    } else {
        // Menggunakan fungsi bawaan WP untuk menghandle reset password
        $errors = retrieve_password();
        
        if (is_wp_error($errors)) {
            $error = $errors->get_error_message();
        } else {
            $success = 'Link reset password telah dikirim ke email Anda. Silakan cek Inbox atau folder Spam.';
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 px-4">
    
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden border border-gray-100 relative">
        
        <!-- Back Button Absolute -->
        <a href="<?php echo home_url('/login'); ?>" class="absolute top-4 left-4 text-gray-400 hover:text-emerald-600 transition z-20">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>

        <!-- Header -->
        <div class="pt-10 pb-6 px-6 text-center">
            <div class="w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center text-orange-500 mx-auto mb-4 border-4 border-white shadow-md">
                <i class="fas fa-lock-open text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Lupa Kata Sandi?</h2>
            <p class="text-gray-500 text-sm mt-2 max-w-xs mx-auto">Jangan khawatir. Masukkan email Anda dan kami akan mengirimkan instruksi reset password.</p>
        </div>

        <div class="px-8 pb-10">
            
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-xs mb-6 flex items-start gap-2 border border-red-100">
                    <i class="fas fa-exclamation-triangle mt-0.5"></i> 
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 text-green-700 p-5 rounded-xl text-center border border-green-100">
                    <i class="fas fa-paper-plane text-4xl text-green-500 mb-3 block"></i>
                    <h3 class="font-bold text-lg mb-1">Email Terkirim!</h3>
                    <p class="text-sm opacity-90 mb-4"><?php echo $success; ?></p>
                    <a href="<?php echo home_url('/login'); ?>" class="text-green-700 font-bold text-sm underline hover:text-green-800">Kembali ke Halaman Login</a>
                </div>
            <?php else: ?>

                <form action="" method="post" class="space-y-5">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 ml-1">Email atau Username</label>
                        <div class="relative group">
                            <i class="fas fa-at absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition"></i>
                            <input type="text" name="user_login" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 focus:bg-white transition text-sm text-gray-700 placeholder-gray-400" placeholder="Masukkan email terdaftar" required>
                        </div>
                    </div>

                    <div class="pt-2">
                        <?php wp_nonce_field('dw_lostpass_action', 'dw_lostpass_nonce'); ?>
                        <button type="submit" class="w-full bg-emerald-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 hover:shadow-xl transition transform hover:-translate-y-0.5">
                            Kirim Link Reset
                        </button>
                    </div>

                </form>

            <?php endif; ?>

        </div>
        
        <!-- Footer Decoration -->
        <div class="bg-gray-50 p-4 text-center border-t border-gray-100">
            <p class="text-xs text-gray-400">Masih mengalami kendala? <a href="#" class="text-emerald-600 font-medium">Hubungi Admin</a></p>
        </div>

    </div>
</div>

<?php get_footer(); ?>