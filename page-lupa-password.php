<?php
/**
 * Template Name: Halaman Lupa Password Custom
 * Description: Functional custom lost password page using WP native reset API.
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

$error_message = '';
$success_message = '';

// PROSES SUBMIT FORM
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_lostpass_nonce']) && wp_verify_nonce($_POST['dw_lostpass_nonce'], 'dw_lostpass_action') ) {
    
    $login_input = trim($_POST['user_login']);

    if ( empty( $login_input ) ) {
        $error_message = 'Silakan masukkan username atau email.';
    } else {
        // 1. Cari User berdasarkan Username atau Email
        $user_data = get_user_by( 'login', $login_input );
        if ( ! $user_data ) {
            $user_data = get_user_by( 'email', $login_input );
        }

        // 2. Validasi User
        if ( ! $user_data ) {
            // Pesan error generic untuk keamanan (atau spesifik jika diinginkan)
            $error_message = 'Username atau email tidak ditemukan.';
        } else {
            // 3. Generate Reset Key
            // Fungsi ini membuat key valid dan menyimpannya di database WP
            $key = get_password_reset_key( $user_data );

            if ( is_wp_error( $key ) ) {
                $error_message = $key->get_error_message();
            } else {
                // 4. Siapkan Email
                $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                $user_login = $user_data->user_login;
                $user_email = $user_data->user_email;

                // Subject Email
                $subject = sprintf( __( '[%s] Permintaan Reset Password', 'desa-wisata' ), $site_name );

                // Isi Pesan Email (Format Standar WP)
                $message  = __( 'Seseorang telah meminta reset password untuk akun berikut:', 'desa-wisata' ) . "\r\n\r\n";
                $message .= sprintf( __( 'Nama Situs: %s', 'desa-wisata' ), $site_name ) . "\r\n\r\n";
                $message .= sprintf( __( 'Username: %s', 'desa-wisata' ), $user_login ) . "\r\n\r\n";
                $message .= __( 'Jika ini adalah kesalahan, abaikan saja email ini dan tidak akan ada yang terjadi.', 'desa-wisata' ) . "\r\n\r\n";
                $message .= __( 'Untuk mereset password Anda, kunjungi tautan berikut:', 'desa-wisata' ) . "\r\n\r\n";
                
                // Link menuju halaman reset password bawaan WP (wp-login.php?action=rp)
                // Ini cara paling aman karena WP yang menangani validasi key dan input password baru
                $link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
                
                $message .= '<' . $link . '>' . "\r\n";

                // Headers
                $headers = array('Content-Type: text/plain; charset=UTF-8');

                // 5. Kirim Email
                if ( wp_mail( $user_email, $subject, $message, $headers ) ) {
                    $success_message = 'Instruksi reset password telah dikirim ke email Anda.';
                } else {
                    $error_message = 'Gagal mengirim email. Silakan hubungi administrator.';
                }
            }
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
    
    <!-- Background Decor -->
    <div class="absolute inset-0 overflow-hidden -z-10">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-100 rounded-full blur-3xl opacity-40"></div>
        <div class="absolute top-40 -left-20 w-72 h-72 bg-blue-100 rounded-full blur-3xl opacity-40"></div>
        <div class="absolute bottom-0 w-full h-1/2 bg-gradient-to-t from-white to-transparent"></div>
    </div>

    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-gray-100 relative z-10 transform transition-all hover:shadow-2xl">
        
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-gradient-to-tr from-orange-400 to-red-500 rounded-full flex items-center justify-center text-white mb-6 shadow-lg shadow-orange-200">
                <i class="fas fa-unlock-alt text-2xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Lupa Kata Sandi?</h2>
            <p class="mt-3 text-sm text-gray-500 leading-relaxed">
                Jangan khawatir. Masukkan email atau username yang terdaftar, dan kami akan mengirimkan tautan untuk membuat kata sandi baru.
            </p>
        </div>

        <!-- Notifikasi -->
        <?php if ( !empty($error_message) ) : ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg animate-pulse">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-sm text-red-700 font-medium"><?php echo esc_html($error_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( !empty($success_message) ) : ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-sm text-green-700 font-medium"><?php echo esc_html($success_message); ?></p>
                </div>
            </div>
            <!-- Tombol Login Kembali -->
            <div class="mt-6">
                <a href="<?php echo home_url('/login'); ?>" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-green-600 hover:bg-green-700 shadow-lg transition-all duration-300">
                    Kembali ke Halaman Login
                </a>
            </div>
        <?php else : ?>

        <!-- Form Reset -->
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

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-paper-plane text-gray-400 group-hover:text-white transition-colors"></i>
                    </span>
                    Kirim Link Reset
                </button>
            </div>
        </form>

        <div class="mt-6 text-center space-y-4">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Atau</span>
                </div>
            </div>

            <div>
                <a href="<?php echo home_url('/login'); ?>" class="flex items-center justify-center gap-2 text-sm font-bold text-gray-600 hover:text-orange-600 transition-colors">
                    <i class="fas fa-arrow-left"></i> Kembali ke Login
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>