<?php
/**
 * Template Name: Form Pendaftaran Desa Khusus
 * Description: Hidden registration page specifically for Village Admins.
 */

// Hanya guest yang bisa akses
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/dashboard-desa') );
    exit;
}

$success_message = '';
$error_message = '';

if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_reg_desa_nonce']) && wp_verify_nonce($_POST['dw_reg_desa_nonce'], 'dw_reg_desa_action') ) {
    global $wpdb;
    
    // 1. Ambil Data
    $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']); // Nama Admin Desa
    $email        = sanitize_email($_POST['email']);
    $username     = sanitize_user($_POST['username']);
    $password     = $_POST['password'];
    $nama_desa    = sanitize_text_field($_POST['nama_desa']);
    $kabupaten    = sanitize_text_field($_POST['kabupaten']);
    
    // 2. Validasi
    if ( username_exists($username) || email_exists($email) ) {
        $error_message = 'Username atau Email sudah terdaftar.';
    } else {
        // 3. Buat User WordPress (Role: admin_desa)
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'first_name' => $nama_lengkap,
            'role'       => 'admin_desa' 
        ]);

        if ( ! is_wp_error( $user_id ) ) {
            // 4. Insert Data ke Tabel dw_desa
            $slug_desa = sanitize_title($nama_desa . '-' . $kabupaten);
            
            $wpdb->insert(
                "{$wpdb->prefix}dw_desa",
                [
                    'id_user_desa' => $user_id,
                    'nama_desa'    => $nama_desa,
                    'slug_desa'    => $slug_desa,
                    'kabupaten'    => $kabupaten,
                    'status'       => 'pending' // Default pending sampai di-approve admin pusat
                ],
                ['%d', '%s', '%s', '%s', '%s']
            );

            $success_message = 'Pendaftaran Desa Berhasil! Silakan login dan lengkapi profil desa Anda.';
        } else {
            $error_message = $user_id->get_error_message();
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-blue-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-xl w-full bg-white p-8 rounded-2xl shadow-xl border border-blue-100">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-landmark"></i>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900">Pendaftaran Desa Wisata</h2>
            <p class="mt-2 text-sm text-gray-500">Formulir Khusus Admin Desa</p>
        </div>

        <?php if ( !empty($error_message) ) : ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <p class="text-sm text-red-700 font-bold"><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ( !empty($success_message) ) : ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded text-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                <h3 class="text-lg font-bold text-green-800">Pendaftaran Berhasil!</h3>
                <p class="text-green-700 mt-2 mb-6"><?php echo esc_html($success_message); ?></p>
                <a href="<?php echo home_url('/login'); ?>" class="inline-block bg-green-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition shadow-lg">
                    Masuk ke Dashboard Desa
                </a>
            </div>
        <?php else : ?>

        <form action="" method="POST" class="space-y-6">
            <?php wp_nonce_field('dw_reg_desa_action', 'dw_reg_desa_nonce'); ?>
            
            <!-- Data Akun -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <h4 class="text-sm font-bold text-gray-500 uppercase mb-4 border-b pb-2">Informasi Akun Admin</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Admin</label>
                        <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Aktif</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Data Desa -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <h4 class="text-sm font-bold text-gray-500 uppercase mb-4 border-b pb-2">Informasi Desa</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Desa Wisata</label>
                        <input type="text" name="nama_desa" placeholder="Contoh: Desa Wisata Penglipuran" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kabupaten / Kota</label>
                        <input type="text" name="kabupaten" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg transform hover:-translate-y-0.5">
                Daftarkan Desa
            </button>
        </form>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>