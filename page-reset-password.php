<?php
/**
 * Template Name: Custom Reset Password
 * Description: Halaman untuk input password baru setelah klik link email.
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
    } elseif ( strlen($pass1) < 6 ) { // Minimal 6 karakter (opsional)
        $msg_type = 'error';
        $message = 'Password terlalu pendek. Minimal 6 karakter.';
    } else {
        // Reset password user
        reset_password( $user, $pass1 );
        $msg_type = 'success';
        $message = 'Password berhasil diubah! Anda akan dialihkan ke halaman login...';
        
        // Auto redirect script
        echo '<script>setTimeout(function(){ window.location.href = "'.home_url('/login').'"; }, 3000);</script>';
    }
}
?>

<div class="min-h-screen bg-gray-50 flex flex-col lg:flex-row font-sans">
    
    <!-- BAGIAN KIRI: Gambar -->
    <div class="hidden lg:flex lg:w-1/2 bg-cover bg-center relative" 
         style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/img/login-bg.jpg');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
        <div class="absolute bottom-0 left-0 p-12 text-white z-10">
            <h2 class="text-4xl font-bold mb-4">Mulai Lembaran Baru</h2>
            <p class="text-lg text-gray-200">Buat password yang kuat untuk melindungi akun dan transaksi desa Anda.</p>
        </div>
    </div>

    <!-- BAGIAN KANAN: Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12 bg-white">
        <div class="w-full max-w-md">

            <!-- Header -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-orange-600 mb-6">
                    <i class="fas fa-key text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Password Baru</h1>
                <p class="text-gray-500">Silakan masukkan password baru untuk akun <strong><?php echo esc_html($login); ?></strong>.</p>
            </div>

            <!-- Notifikasi -->
            <?php if ( ! empty( $message ) ) : ?>
                <div class="mb-6 p-4 rounded-xl flex items-start gap-3 <?php echo ($msg_type === 'success') ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                    <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-1"></i>
                    <div>
                        <span class="text-sm font-medium block"><?php echo $message; ?></span>
                        <?php if ( $msg_type === 'error' && ( isset($error_code) || strpos($message, 'kadaluarsa') !== false ) ) : ?>
                            <a href="<?php echo home_url('/lupa-password'); ?>" class="text-xs font-bold underline mt-1 block hover:text-red-900">Minta Link Baru</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form hanya muncul jika Key Valid dan belum Sukses -->
            <?php if ( ! is_wp_error( $user ) && $user && $msg_type !== 'success' ) : ?>
                <form method="post" action="" class="space-y-6" autocomplete="off">
                    
                    <!-- Password Baru -->
                    <div class="space-y-2">
                        <label for="pass1" class="text-sm font-bold text-gray-700 block">Password Baru</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                            </div>
                            <input id="pass1" name="pass1" type="password" required 
                                   class="block w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-medium" 
                                   placeholder="Minimal 6 karakter">
                        </div>
                    </div>

                    <!-- Konfirmasi Password -->
                    <div class="space-y-2">
                        <label for="pass2" class="text-sm font-bold text-gray-700 block">Ulangi Password Baru</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-check-double text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                            </div>
                            <input id="pass2" name="pass2" type="password" required 
                                   class="block w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all font-medium" 
                                   placeholder="Ketik ulang password">
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full py-3.5 px-4 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold shadow-lg shadow-orange-500/30 transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2">
                        <span>Simpan Password</span>
                        <i class="fas fa-save text-sm"></i>
                    </button>

                </form>
            <?php endif; ?>

            <?php if ( $msg_type === 'success' ) : ?>
                <div class="mt-6 text-center">
                    <a href="<?php echo home_url('/login'); ?>" class="inline-block py-3 px-6 bg-gray-900 text-white rounded-lg font-bold hover:bg-gray-800 transition">
                        Login Sekarang
                    </a>
                </div>
            <?php endif; ?>

            <!-- Footer Links -->
            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                <a href="<?php echo home_url('/'); ?>" class="inline-block text-gray-400 text-sm hover:text-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda
                </a>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>