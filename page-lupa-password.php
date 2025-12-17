<?php
/**
 * Template Name: Halaman Lupa Password Custom
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url() );
    exit;
}

$error_message = '';
$success_message = '';

if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_login']) ) {
    $login = trim($_POST['user_login']);
    $errors = retrieve_password(); // Fungsi core WP

    if ( is_wp_error($errors) ) {
        $error_message = $errors->get_error_message();
    } else {
        $success_message = 'Instruksi reset password telah dikirim ke email Anda.';
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative">
    <!-- Background Decor -->
    <div class="absolute inset-0 overflow-hidden -z-10">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-100 rounded-full blur-3xl opacity-40"></div>
        <div class="absolute top-40 -left-20 w-72 h-72 bg-blue-100 rounded-full blur-3xl opacity-40"></div>
    </div>

    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 mb-4">
                <i class="fas fa-key text-xl"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900">Lupa Kata Sandi?</h2>
            <p class="mt-2 text-sm text-gray-600">
                Jangan khawatir. Masukkan email atau username Anda di bawah ini untuk mereset kata sandi.
            </p>
        </div>

        <?php if ( !empty($error_message) ) : ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                <p class="text-sm text-red-700"><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ( !empty($success_message) ) : ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-md">
                <p class="text-sm text-green-700 font-bold"><?php echo $success_message; ?></p>
            </div>
        <?php else : ?>

        <form class="mt-8 space-y-6" action="" method="POST">
            <div>
                <label for="user_login" class="block text-sm font-medium text-gray-700 mb-1">Email atau Username</label>
                <input id="user_login" name="user_login" type="text" required class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm" placeholder="Masukkan email atau username terdaftar">
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-300">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-paper-plane group-hover:text-gray-300"></i>
                    </span>
                    Kirim Link Reset
                </button>
            </div>
        </form>
        <?php endif; ?>

        <div class="mt-6 text-center space-y-2">
            <div>
                <a href="<?php echo home_url('/login'); ?>" class="text-sm font-medium text-orange-600 hover:text-orange-500 flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> Kembali ke Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>