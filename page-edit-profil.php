<?php
/**
 * Template Name: Halaman Edit Profil
 * Description: Form edit profil frontend untuk Pembeli, Pedagang, dan Desa.
 */

// 1. Redirect jika belum login
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$message = '';
$message_type = ''; // success or error

// 2. LOGIC: PROSES FORM SUBMIT
if (isset($_POST['dw_submit_profile']) && wp_verify_nonce($_POST['dw_profile_nonce'], 'dw_update_profile_action')) {
    
    // A. Update Info Dasar
    $update_data = array(
        'ID'           => $user_id,
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email'   => sanitize_email($_POST['user_email']),
    );

    // Ganti Password (Opsional)
    if (!empty($_POST['pass1']) && !empty($_POST['pass2'])) {
        if ($_POST['pass1'] === $_POST['pass2']) {
            $update_data['user_pass'] = $_POST['pass1'];
        } else {
            $message = 'Konfirmasi password tidak cocok.';
            $message_type = 'error';
        }
    }

    // Eksekusi Update User Core
    if (empty($message)) {
        $user_id = wp_update_user($update_data);
        
        if (is_wp_error($user_id)) {
            $message = 'Gagal memperbarui profil: ' . $user_id->get_error_message();
            $message_type = 'error';
        } else {
            // B. Update User Meta (Data Tambahan)
            update_user_meta($user_id, 'dw_no_hp', sanitize_text_field($_POST['dw_no_hp']));
            update_user_meta($user_id, 'dw_alamat', sanitize_textarea_field($_POST['dw_alamat']));

            // Data Khusus Pedagang/Desa
            if (current_user_can('editor') || current_user_can('administrator') || current_user_can('author')) {
                update_user_meta($user_id, 'dw_nama_toko', sanitize_text_field($_POST['dw_nama_toko']));
                update_user_meta($user_id, 'dw_deskripsi_toko', sanitize_textarea_field($_POST['dw_deskripsi_toko']));
            }

            // C. Handle Upload Foto Profil
            if (!empty($_FILES['profile_picture']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('profile_picture', 0);

                if (is_wp_error($attachment_id)) {
                    $message = 'Error upload foto: ' . $attachment_id->get_error_message();
                    $message_type = 'error';
                } else {
                    update_user_meta($user_id, 'dw_profile_picture_id', $attachment_id);
                }
            }

            if (empty($message)) {
                $message = 'Profil berhasil diperbarui!';
                $message_type = 'success';
                // Refresh data user
                $current_user = wp_get_current_user();
            }
        }
    }
}

// Ambil Data Meta Tersimpan
$no_hp = get_user_meta($user_id, 'dw_no_hp', true);
$alamat = get_user_meta($user_id, 'dw_alamat', true);
$nama_toko = get_user_meta($user_id, 'dw_nama_toko', true);
$deskripsi_toko = get_user_meta($user_id, 'dw_deskripsi_toko', true);
$profile_pic_id = get_user_meta($user_id, 'dw_profile_picture_id', true);
$profile_pic_url = $profile_pic_id ? wp_get_attachment_url($profile_pic_id) : get_avatar_url($user_id);

get_header(); 
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-4xl">
        
        <!-- Header Halaman -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Pengaturan Profil</h1>
                <p class="text-gray-500">Kelola informasi pribadi dan keamanan akun Anda.</p>
            </div>
            <a href="<?php echo home_url('/dashboard'); // Sesuaikan slug dashboard ?>" class="text-green-600 hover:text-green-700 font-medium flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <!-- Alert Message -->
        <?php if ($message) : ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'; ?> border px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-8">
            <?php wp_nonce_field('dw_update_profile_action', 'dw_profile_nonce'); ?>

            <!-- KOLOM KIRI: FOTO & RINGKASAN -->
            <div class="w-full md:w-1/3">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center sticky top-24">
                    <div class="relative w-32 h-32 mx-auto mb-4 group">
                        <img src="<?php echo esc_url($profile_pic_url); ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover border-4 border-green-50 shadow-md">
                        
                        <!-- Overlay Upload -->
                        <label for="profile_picture" class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white">
                            <i class="fas fa-camera mr-2"></i> Ubah
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" class="hidden" accept="image/*">
                    </div>
                    
                    <h2 class="text-xl font-bold text-gray-800"><?php echo esc_html($current_user->display_name); ?></h2>
                    <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full font-bold mt-2 uppercase">
                        <?php echo !empty($current_user->roles) ? $current_user->roles[0] : 'Member'; ?>
                    </span>
                    <p class="text-xs text-gray-400 mt-4">Diperbolehkan: JPG, PNG. Max 2MB.</p>
                </div>
            </div>

            <!-- KOLOM KANAN: FORM DATA -->
            <div class="w-full md:w-2/3 space-y-6">
                
                <!-- 1. INFORMASI DASAR -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                        <h3 class="font-bold text-gray-700"><i class="fas fa-user-circle mr-2 text-green-500"></i> Informasi Dasar</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 bg-gray-50" readonly>
                                <p class="text-xs text-gray-400 mt-1">Email tidak dapat diubah sembarangan.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp/HP</label>
                                <input type="text" name="dw_no_hp" value="<?php echo esc_attr($no_hp); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Contoh: 0812...">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                            <textarea name="dw_alamat" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Alamat pengiriman atau alamat rumah..."><?php echo esc_textarea($alamat); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 2. DATA KHUSUS PEDAGANG / DESA -->
                <?php 
                // Cek Role: Editor/Admin/Author diasumsikan sebagai Pedagang/Pengelola Desa
                if (array_intersect(['administrator', 'editor', 'author', 'shop_manager'], $current_user->roles)) : 
                ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-green-50 px-6 py-4 border-b border-green-100">
                        <h3 class="font-bold text-green-800"><i class="fas fa-store mr-2"></i> Informasi Toko / Usaha</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko / Usaha</label>
                            <input type="text" name="dw_nama_toko" value="<?php echo esc_attr($nama_toko); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Contoh: Keripik Pisang Bu Nanik">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                            <textarea name="dw_deskripsi_toko" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Jelaskan produk apa yang Anda jual..."><?php echo esc_textarea($deskripsi_toko); ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 3. KEAMANAN (GANTI PASSWORD) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                        <h3 class="font-bold text-gray-700"><i class="fas fa-lock mr-2 text-green-500"></i> Ganti Password</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <p class="text-sm text-gray-500 italic mb-2">Kosongkan jika tidak ingin mengubah password.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                                <input type="password" name="pass1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ulangi Password Baru</label>
                                <input type="password" name="pass2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="flex justify-end pt-4">
                    <button type="submit" name="dw_submit_profile" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 flex items-center gap-2">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>