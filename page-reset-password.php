<?php
/**
 * Template Name: Custom Reset Password
 * Description: Halaman input password baru dengan desain centered (tengah).
 */

// Jika user sudah login, lempar ke dashboard
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

get_header();

// Ambil parameter dari URL
$key   = isset( $_GET['key'] ) ? $_GET['key'] : '';
$login = isset( $_GET['login'] ) ? $_GET['login'] : '';

$message = '';
$msg_type = ''; // success, error

// --- LOGIC VERIFIKASI KEY ---
$user = false;
$error_obj = null;

if ( empty( $key ) || empty( $login ) ) {
    $msg_type = 'error';
    $message = 'Link reset password tidak valid atau sudah kadaluarsa.';
} else {
    // Cek validitas key menggunakan fungsi WP
    $user = check_password_reset_key( $key, $login );

    if ( is_wp_error( $user ) ) {
        $msg_type = 'error';
        $error_code = $user->get_error_code();
        
        if ( $error_code === 'expired_key' ) {
            $message = 'Link reset password sudah kadaluarsa. Silakan request ulang.';
        } else {
            $message = 'Link tidak valid. Silakan request ulang.';
        }
    }
}

// --- LOGIC SIMPAN PASSWORD BARU ---
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && ! is_wp_error( $user ) && $user ) {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if ( empty( $pass1 ) || empty( $pass2 ) ) {
        $msg_type = 'error';
        $message = 'Silakan isi kedua kolom password.';
    } elseif ( $pass1 !== $pass2 ) {
        $msg_type = 'error';
        $message = 'Konfirmasi password tidak cocok.';
    } elseif ( strlen($pass1) < 6 ) { // Minimal 6 karakter
        $msg_type = 'error';
        $message = 'Password terlalu pendek. Minimal 6 karakter.';
    } else {
        // Reset password user
        reset_password( $user, $pass1 );
        $msg_type = 'success';
        $message = 'Password berhasil diubah! Mengalihkan...';
        
        // Auto redirect script
        echo '<script>setTimeout(function(){ window.location.href = "'.home_url('/login').'"; }, 2000);</script>';
    }
}
?>

<div class="min-h-screen bg-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
    
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Icon / Logo Kecil -->
        <div class="mx-auto h-16 w-16 bg-orange-100 rounded-full flex items-center justify-center mb-6">
            <i class="fas fa-key text-2xl text-orange-600"></i>
        </div>
        <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
            Buat Password Baru
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            untuk akun <strong><?php echo esc_html($login); ?></strong>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-xl shadow-gray-200/50 sm:rounded-xl sm:px-10 border border-gray-100">
            
            <!-- Notifikasi -->
            <?php if ( ! empty( $message ) ) : ?>
                <div class="mb-6 p-4 rounded-lg flex items-start gap-3 <?php echo ($msg_type === 'success') ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                    <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                    <div class="text-sm font-medium">
                        <?php echo $message; ?>
                        <?php if ( $msg_type === 'error' && ( isset($error_code) || strpos($message, 'kadaluarsa') !== false ) ) : ?>
                            <a href="<?php echo home_url('/lupa-password'); ?>" class="block mt-1 font-bold underline hover:text-red-900">Minta Link Baru</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <?php if ( ! is_wp_error( $user ) && $user && $msg_type !== 'success' ) : ?>
                <form class="space-y-6" action="" method="POST" autocomplete="off">
                    
                    <div>
                        <label for="pass1" class="block text-sm font-bold text-gray-700">
                            Password Baru
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="pass1" name="pass1" type="password" required 
                                   class="focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 py-3 sm:text-sm border-gray-300 rounded-lg placeholder-gray-400 bg-gray-50 focus:bg-white transition-colors" 
                                   placeholder="Minimal 6 karakter">
                        </div>
                    </div>

                    <div>
                        <label for="pass2" class="block text-sm font-bold text-gray-700">
                            Ulangi Password
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-check-double text-gray-400"></i>
                            </div>
                            <input id="pass2" name="pass2" type="password" required 
                                   class="focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 py-3 sm:text-sm border-gray-300 rounded-lg placeholder-gray-400 bg-gray-50 focus:bg-white transition-colors" 
                                   placeholder="Ketik ulang password">
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all transform hover:-translate-y-0.5">
                            Simpan Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ( $msg_type === 'success' ) : ?>
                <div class="mt-4">
                    <a href="<?php echo home_url('/login'); ?>" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-gray-900 hover:bg-gray-800 transition-all">
                        Login Sekarang
                    </a>
                </div>
            <?php endif; ?>

        </div>

        <div class="mt-6 text-center">
            <a href="<?php echo home_url('/'); ?>" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>