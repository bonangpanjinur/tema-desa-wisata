<?php
/**
 * Template Name: Halaman Lupa Password Custom
 * Description: Solusi Reset Password Smart (Auto-detect jika email gagal, langsung munculkan link).
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

$notice_type = ''; // 'error', 'success', 'warning'
$notice_msg = '';
$manual_link = '';

// PROSES FORM
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_lostpass_nonce']) && wp_verify_nonce($_POST['dw_lostpass_nonce'], 'dw_lostpass_action') ) {
    
    $user_input = trim($_POST['user_login']);

    if ( empty( $user_input ) ) {
        $notice_type = 'error';
        $notice_msg = 'Masukkan username atau email Anda.';
    } else {
        // 1. Cari User (Support Email atau Username)
        if ( strpos( $user_input, '@' ) ) {
            $user_data = get_user_by( 'email', $user_input );
        } else {
            $user_data = get_user_by( 'login', $user_input );
        }

        if ( ! $user_data ) {
            $notice_type = 'error';
            $notice_msg = 'Akun tidak ditemukan. Pastikan email atau username benar.';
        } else {
            // 2. Generate Key Reset Password (Native WP)
            $key = get_password_reset_key( $user_data );

            if ( is_wp_error( $key ) ) {
                $notice_type = 'error';
                $notice_msg = $key->get_error_message();
            } else {
                // 3. Siapkan Link & Email
                $login = $user_data->user_login;
                $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                
                // Link Reset Resmi WP
                $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($login), 'login');

                $message = __( 'Permintaan reset password untuk:' ) . "\r\n\r\n";
                $message .= sprintf( __( 'Username: %s' ), $login ) . "\r\n\r\n";
                $message .= __( 'Klik tautan di bawah ini untuk membuat password baru:' ) . "\r\n\r\n";
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
                    // Skenario 1: Email Terkirim (Server Live)
                    $notice_type = 'success';
                    $notice_msg = 'Instruksi reset password telah dikirim ke email: ' . 
                                  substr($user_data->user_email, 0, 3) . '***' . strstr($user_data->user_email, '@');
                } else {
                    // Skenario 2: Email Gagal (Localhost/No SMTP) -> TAMPILKAN LINK LANGSUNG
                    $notice_type = 'warning';
                    $notice_msg = 'Server email tidak merespon (Mode Developer). Gunakan tombol di bawah untuk reset:';
                    $manual_link = $reset_link;
                }
            }
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative font-sans overflow-hidden">
    
    <!-- Decor Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-orange-100 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute top-1/2 -left-24 w-72 h-72 bg-yellow-100 rounded-full blur-3xl opacity-50"></div>
    </div>

    <div class="max-w-md w-full bg-white p-8 rounded-3xl shadow-2xl border border-gray-100 relative z-10">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-orange-200 transform rotate-3 hover:rotate-0 transition-all duration-300">
                <i class="fas fa-key text-2xl"></i>
            </div>
            <h2 class="mt-6 text-2xl font-bold text-gray-900">Pemulihan Akun</h2>
            <p class="mt-2 text-sm text-gray-500">Masukkan identitas akun Anda untuk memulai.</p>
        </div>

        <!-- NOTIFIKASI AREA -->
        <?php if ( !empty($notice_msg) ) : ?>
            
            <!-- 1. JIKA SUKSES KIRIM EMAIL -->
            <?php if ($notice_type === 'success') : ?>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center mb-6 animate-fade-in-up">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h3 class="text-green-800 font-bold text-sm mb-1">Email Terkirim!</h3>
                    <p class="text-green-600 text-xs"><?php echo esc_html($notice_msg); ?></p>
                    <a href="<?php echo home_url('/login'); ?>" class="mt-4 inline-block w-full py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-bold transition shadow-md">
                        Kembali ke Login
                    </a>
                </div>

            <!-- 2. JIKA GAGAL EMAIL (TAMPILKAN LINK MANUAL) -->
            <?php elseif ($notice_type === 'warning') : ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 text-center mb-6 animate-fade-in-up">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <h3 class="text-yellow-800 font-bold text-sm mb-1">Mode Manual Aktif</h3>
                    <p class="text-yellow-700 text-xs mb-4"><?php echo esc_html($notice_msg); ?></p>
                    
                    <a href="<?php echo esc_url($manual_link); ?>" class="block w-full py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-yellow-200 transition transform hover:-translate-y-1">
                        <i class="fas fa-unlock mr-2"></i> Buat Password Baru Sekarang
                    </a>
                </div>

            <!-- 3. JIKA ERROR (User tidak ketemu dll) -->
            <?php else : ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                        <p class="text-sm text-red-700 font-medium"><?php echo esc_html($notice_msg); ?></p>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- FORMULIR (Sembunyikan jika sudah sukses/warning link ada) -->
        <?php if ( $notice_type !== 'success' && $notice_type !== 'warning' ) : ?>
        <form class="space-y-6" action="" method="POST">
            <?php wp_nonce_field('dw_lostpass_action', 'dw_lostpass_nonce'); ?>
            
            <div>
                <label for="user_login" class="block text-sm font-bold text-gray-700 mb-2">Username atau Email</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-user-circle text-gray-400 group-focus-within:text-orange-500 transition-colors"></i>
                    </div>
                    <input id="user_login" name="user_login" type="text" required 
                           class="block w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:bg-white transition-all text-sm font-medium" 
                           placeholder="Ketik email atau username Anda">
                </div>
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl text-sm font-bold text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5">
                Kirim Link Reset
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <a href="<?php echo home_url('/login'); ?>" class="text-sm font-bold text-gray-500 hover:text-orange-600 transition flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Login
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>