<?php
/**
 * Template Name: Halaman Lupa Password
 */

// Jika user sudah login, tidak perlu reset password
if ( is_user_logged_in() ) {
    wp_redirect( home_url() );
    exit;
}

// Handle Form Submission (PHP Standard)
$message = '';
$message_type = ''; // success or error

if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_login']) ) {
    $user_login = sanitize_text_field( $_POST['user_login'] );
    
    // Fungsi bawaan WP untuk menghandle lost password
    $errors = retrieve_password();
    
    if ( is_wp_error( $errors ) ) {
        $message = $errors->get_error_message();
        $message_type = 'bg-red-500';
    } else {
        $message = 'Silakan cek email Anda untuk tautan reset password.';
        $message_type = 'bg-green-500';
    }
}

get_header(); 
?>

<div class="min-h-[80vh] bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Reset Kata Sandi
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Masukkan email atau username Anda, kami akan mengirimkan tautan reset.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            <?php if ( ! empty( $message ) ) : ?>
                <div class="mb-4 p-4 rounded text-sm text-white <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <div>
                    <label for="user_login" class="block text-sm font-medium text-gray-700"> Username atau Email </label>
                    <div class="mt-1">
                        <input id="user_login" name="user_login" type="text" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Kirim Tautan Reset
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <a href="<?php echo home_url('/login'); ?>" class="text-sm font-medium text-primary hover:text-green-500">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>