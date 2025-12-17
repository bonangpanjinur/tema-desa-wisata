<?php
/**
 * Template Name: Halaman Lupa Password Custom
 * Description: Menggunakan native WordPress API dengan fallback link jika email server mati.
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

$error_message = '';
$success_message = '';
$manual_reset_link = ''; // Link cadangan jika email gagal

// PROSES FORM
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_lostpass_nonce']) && wp_verify_nonce($_POST['dw_lostpass_nonce'], 'dw_lostpass_action') ) {
    
    $user_input = trim($_POST['user_login']);

    if ( empty( $user_input ) ) {
        $error_message = 'Masukkan username atau email Anda.';
    } else {
        // 1. Cari User (Metode Standar WP)
        if ( strpos( $user_input, '@' ) ) {
            $user_data = get_user_by( 'email', $user_input );
        } else {
            $user_data = get_user_by( 'login', $user_input );
        }

        if ( ! $user_data ) {
            $error_message = 'Username atau email tidak ditemukan.';
        } else {
            // 2. Generate Key Reset Password (Fungsi Native WP)
            // Ini membuat kunci aman dan menyimpannya di database WordPress
            $key = get_password_reset_key( $user_data );

            if ( is_wp_error( $key ) ) {
                $error_message = $key->get_error_message();
            } else {
                // 3. Siapkan Email Standar WordPress
                $login = $user_data->user_login;
                $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

                // Link Reset yang mengarah ke form reset password bawaan WP (wp-login.php)
                // Ini memastikan proses ubah password ditangani langsung oleh WordPress
                $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($login), 'login');

                $message = __( 'Seseorang meminta pengaturan ulang kata sandi untuk akun berikut:' ) . "\r\n\r\n";
                $message .= sprintf( __( 'Nama Situs: %s' ), $site_name ) . "\r\n\r\n";
                $message .= sprintf( __( 'Nama Pengguna: %s' ), $login ) . "\r\n\r\n";
                $message .= __( 'Jika ini bukan Anda, abaikan email ini.' ) . "\r\n\r\n";
                $message .= __( 'Untuk mengatur ulang kata sandi, kunjungi alamat berikut:' ) . "\r\n\r\n";
                $message .= $reset_link . "\r\n";

                $title = sprintf( __( '[%s] Reset Kata Sandi' ), $site_name );

                // 4. Coba Kirim Email
                $mail_sent = false;
                try {
                    $mail_sent = wp_mail( $user_data->user_email, $title, $message );
                } catch (Exception $e) {
                    $mail_sent = false;
                }

                if ( $mail_sent ) {
                    $success_message = 'Link reset password telah dikirim ke email Anda.';
                } else {
                    // --- SOLUSI: Tampilkan Link di Layar jika Email Gagal ---
                    $error_message = 'Server tidak dapat mengirim email (biasanya terjadi di Localhost).';
                    $manual_reset_link = $reset_link; // Simpan link untuk ditampilkan
                }
            }
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
    
    <!-- Dekorasi Background -->
    <div class="absolute inset-0 overflow-hidden -z-10">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-100 rounded-full blur-3xl opacity-40"></div>
        <div class="absolute top-40 -left-20 w-72 h-72 bg-blue-100 rounded-full blur-3xl opacity-40"></div>
    </div>

    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-gray-100 relative z-10 transform transition-all">
        
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-gradient-to-tr from-orange-400 to-red-500 rounded-full flex items-center justify-center text-white mb-6 shadow-lg shadow-orange-200">
                <i class="fas fa-key text-2xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Lupa Kata Sandi?</h2>
            <p class="mt-3 text-sm text-gray-500 leading-relaxed">
                Masukkan email atau username akun WordPress Anda. Kami akan membantu memulihkannya.
            </p>
        </div>

        <!-- ERROR MESSAGE & MANUAL LINK -->
        <?php if ( !empty($error_message) ) : ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6 animate-pulse">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>
                    <div>
                        <p class="text-sm text-red-700 font-bold">Terjadi Kesalahan</p>
                        <p class="text-sm text-red-600 mt-1"><?php echo esc_html($error_message); ?></p>
                        
                        <!-- FITUR PENYELAMAT: Tampilkan Link Jika Email Gagal -->
                        <?php if ( !empty($manual_reset_link) ) : ?>
                            <div class="mt-4 pt-4 border-t border-red-200">
                                <p class="text-xs text-gray-600 mb-2">
                                    Karena email server tidak aktif, silakan klik link manual di bawah ini untuk mereset password Anda:
                                </p>
                                <a href="<?php echo esc_url($manual_reset_link); ?>" class="block w-full text-center bg-red-600 text-white text-xs font-bold py-2 px-4 rounded hover:bg-red-700 transition">
                                    Buka Halaman Reset Password &rarr;
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SUCCESS MESSAGE -->
        <?php if ( !empty($success_message) ) : ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg text-center">
                <div class="flex justify-center mb-2">
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-green-800">Email Terkirim!</h3>
                <p class="text-sm text-green-700 mt-1"><?php echo esc_html($success_message); ?></p>
                <div class="mt-6">
                    <a href="<?php echo home_url('/login'); ?>" class="inline-block bg-green-600 text-white px-6 py-2 rounded-full font-bold hover:bg-green-700 transition shadow-lg">
                        Kembali Login
                    </a>
                </div>
            </div>
        <?php else : ?>

        <!-- FORM INPUT -->
        <form class="mt-8 space-y-6" action="" method="POST">
            <?php wp_nonce_field('dw_lostpass_action', 'dw_lostpass_nonce'); ?>
            
            <div class="group">
                <label for="user_login" class="block text-sm font-bold text-gray-700 mb-2">Email atau Username</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                    </div>
                    <input id="user_login" name="user_login" type="text" required class="focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 py-3 sm:text-sm border-gray-300 rounded-xl transition-all duration-300 bg-gray-50 focus:bg-white" placeholder="contoh@email.com">
                </div>
            </div>

            <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5">
                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                    <i class="fas fa-paper-plane text-gray-400 group-hover:text-white transition-colors"></i>
                </span>
                Kirim Link Reset
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="<?php echo home_url('/login'); ?>" class="flex items-center justify-center gap-2 text-sm font-bold text-gray-600 hover:text-orange-600 transition-colors">
                <i class="fas fa-arrow-left"></i> Kembali ke Login
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>