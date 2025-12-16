<?php
/**
 * Template Name: Halaman Register
 */

// Redirect jika sudah login
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if (in_array('pedagang', $current_user->roles)) {
        wp_redirect(home_url('/dashboard-toko'));
    } else {
        wp_redirect(home_url('/akun-saya'));
    }
    exit;
}

// Proses Form Submission
$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_register_nonce']) && wp_verify_nonce($_POST['dw_register_nonce'], 'dw_register_action')) {
    
    global $wpdb;
    
    // Sanitasi Input
    $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']);
    $email        = sanitize_email($_POST['email']);
    $password     = $_POST['password'];
    $no_wa        = sanitize_text_field($_POST['no_wa']);
    
    // Data Toko
    $nama_toko    = sanitize_text_field($_POST['nama_toko']);
    $alamat_toko  = sanitize_textarea_field($_POST['alamat_toko']); // New Field
    
    // Validasi Dasar
    if (empty($nama_lengkap) || empty($email) || empty($password) || empty($no_wa) || empty($nama_toko) || empty($alamat_toko)) {
        $error_msg = 'Semua field wajib diisi.';
    } elseif (email_exists($email)) {
        $error_msg = 'Email sudah terdaftar. Silakan login.';
    } else {
        // 1. Buat User WordPress
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            $error_msg = $user_id->get_error_message();
        } else {
            // Update User Meta
            wp_update_user([
                'ID' => $user_id, 
                'display_name' => $nama_lengkap,
                'role' => 'pedagang' // Set role pedagang
            ]);
            
            // Handle Upload Foto Profil
            $foto_profil_url = '';
            if (!empty($_FILES['foto_profil']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_id = media_handle_upload('foto_profil', 0);
                if (!is_wp_error($attachment_id)) {
                    $foto_profil_url = wp_get_attachment_url($attachment_id);
                }
            }

            // Handle Upload Foto Sampul (New Feature)
            $foto_sampul_url = '';
            if (!empty($_FILES['foto_sampul']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_id_sampul = media_handle_upload('foto_sampul', 0);
                if (!is_wp_error($attachment_id_sampul)) {
                    $foto_sampul_url = wp_get_attachment_url($attachment_id_sampul);
                }
            }

            // 2. Simpan ke Tabel dw_pedagang
            $table_pedagang = $wpdb->prefix . 'dw_pedagang';
            $slug_toko = sanitize_title($nama_toko);
            
            // Cek duplikat slug
            if ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table_pedagang WHERE slug_toko = %s", $slug_toko))) {
                $slug_toko .= '-' . time();
            }

            $wpdb->insert($table_pedagang, [
                'id_user'      => $user_id,
                'nama_toko'    => $nama_toko,
                'slug_toko'    => $slug_toko,
                'nama_pemilik' => $nama_lengkap,
                'nomor_wa'     => $no_wa,
                'alamat_lengkap'=> $alamat_toko, // Simpan Alamat
                'foto_profil'  => $foto_profil_url,
                'foto_sampul'  => $foto_sampul_url, // Simpan Foto Sampul (Pastikan kolom ada di DB)
                'status_akun'  => 'aktif', // Auto aktif atau 'menunggu' tergantung kebijakan
                'created_at'   => current_time('mysql')
            ]);

            // Auto Login
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            wp_redirect(home_url('/dashboard-toko'));
            exit;
        }
    }
}

get_header();
?>

<div class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
        <div class="text-center">
            <h2 class="mt-2 text-3xl font-extrabold text-gray-900">Daftar Toko Baru</h2>
            <p class="mt-2 text-sm text-gray-600">
                Mulai berjualan dan kembangkan UMKM desamu.
            </p>
        </div>

        <?php if ($error_msg): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded text-sm" role="alert">
                <p><?php echo $error_msg; ?></p>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="" method="POST" enctype="multipart/form-data">
            <?php wp_nonce_field('dw_register_action', 'dw_register_nonce'); ?>
            
            <div class="space-y-4">
                <!-- Data Diri -->
                <div>
                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap Pemilik</label>
                    <input id="nama_lengkap" name="nama_lengkap" type="text" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm mt-1" placeholder="Contoh: Budi Santoso">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                    <input id="email" name="email" type="email" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm mt-1" placeholder="nama@email.com">
                </div>

                <div>
                    <label for="no_wa" class="block text-sm font-medium text-gray-700">Nomor WhatsApp</label>
                    <input id="no_wa" name="no_wa" type="text" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm mt-1" placeholder="081234567890">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm mt-1">
                </div>

                <div class="border-t border-gray-200 my-4"></div>

                <!-- Data Toko -->
                <div>
                    <label for="nama_toko" class="block text-sm font-medium text-gray-700">Nama Toko</label>
                    <input id="nama_toko" name="nama_toko" type="text" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm mt-1" placeholder="Contoh: Toko Keripik Bu Ani">
                </div>

                <!-- ADDED: Alamat Lengkap -->
                <div>
                    <label for="alamat_toko" class="block text-sm font-medium text-gray-700">Alamat Lengkap Toko</label>
                    <textarea id="alamat_toko" name="alamat_toko" rows="3" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm mt-1" placeholder="Jl. Desa No. 123, RT 01/RW 02..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Alamat lengkap fisik toko Anda (mandiri).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="foto_profil" class="block text-sm font-medium text-gray-700">Foto Profil Toko</label>
                        <input id="foto_profil" name="foto_profil" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    </div>
                    
                    <!-- ADDED: Foto Sampul -->
                    <div>
                        <label for="foto_sampul" class="block text-sm font-medium text-gray-700">Foto Sampul Toko</label>
                        <input id="foto_sampul" name="foto_sampul" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        <p class="text-xs text-gray-500 mt-1">Disarankan ukuran landscape (misal: 1200x400px).</p>
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-lg transition transform hover:-translate-y-0.5">
                    Daftar Sekarang
                </button>
            </div>
            
            <div class="text-center text-sm">
                <span class="text-gray-600">Sudah punya akun? </span>
                <a href="<?php echo home_url('/login'); ?>" class="font-medium text-green-600 hover:text-green-500">
                    Masuk di sini
                </a>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>