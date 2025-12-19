<?php
/**
 * Template Name: Halaman Lupa Password Custom
 * Description: Halaman request reset password dengan desain centered (tengah).
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

get_header();

$message = '';
$msg_type = '';

if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_lostpass_nonce']) ) {
    if ( ! wp_verify_nonce( $_POST['dw_lostpass_nonce'], 'dw_lostpass_action' ) ) {
        $message = 'Sesi kadaluarsa, silakan muat ulang halaman.';
        $msg_type = 'error';
    } else {
        $user_input = trim( $_POST['user_login'] );
        if ( empty( $user_input ) ) {
            $message = 'Silakan masukkan Email atau Username Anda.';
            $msg_type = 'error';
        } else {
            if ( is_email( $user_input ) ) {
                $user_data = get_user_by( 'email', $user_input );
            } else {
                $user_data = get_user_by( 'login', $user_input );
            }

            if ( ! $user_data ) {
                $message = 'Akun tidak ditemukan. Periksa kembali data Anda.';
                $msg_type = 'error';
            } else {
                $errors = retrieve_password();
                if ( is_wp_error( $errors ) ) {
                    $message = $errors->get_error_message();
                    $msg_type = 'error';
                } else {
                    $message = 'Link reset password telah dikirim ke email Anda.';
                    $msg_type = 'success';
                }
            }
        }
    }
}
?>

<div class="min-h-screen bg-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans">
    
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Icon / Logo Kecil -->
        <div class="mx-auto h-16 w-16 bg-orange-100 rounded-full flex items-center justify-center mb-6">
            <i class="fas fa-unlock-alt text-2xl text-orange-600"></i>
        </div>
        <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
            Lupa Password?
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Masukkan email/username, kami akan kirimkan link pemulihan.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-xl shadow-gray-200/50 sm:rounded-xl sm:px-10 border border-gray-100">
            
            <!-- Notifikasi -->
            <?php if ( ! empty( $message ) ) : ?>
                <div class="mb-6 p-4 rounded-lg flex items-start gap-3 <?php echo ($msg_type === 'success') ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                    <i class="fas <?php echo ($msg_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                    <span class="text-sm font-medium"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form class="space-y-6" action="" method="POST">
                <?php wp_nonce_field( 'dw_lostpass_action', 'dw_lostpass_nonce' ); ?>
                
                <div>
                    <label for="user_login" class="block text-sm font-bold text-gray-700">
                        Email atau Username
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="user_login" name="user_login" type="text" required 
                               class="focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 py-3 sm:text-sm border-gray-300 rounded-lg placeholder-gray-400 bg-gray-50 focus:bg-white transition-colors" 
                               placeholder="contoh@email.com">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-gray-900 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all transform hover:-translate-y-0.5">
                        Kirim Link Reset
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Sudah ingat password?
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="<?php echo home_url('/login'); ?>" 
                       class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                        Login Disini
                    </a>
                </div>
            </div>

        </div>
        
        <div class="mt-6 text-center">
            <a href="<?php echo home_url('/'); ?>" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>