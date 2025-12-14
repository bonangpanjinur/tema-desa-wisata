<?php
/**
 * Template Name: Edit Profil
 */

if (!is_user_logged_in()) { wp_redirect(home_url('/login')); exit; }

$user = wp_get_current_user();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_update_profil'])) {
    if (!wp_verify_nonce($_POST['dw_profile_nonce'], 'dw_profile_action')) die('Security check');

    // 1. Update User WP Core
    wp_update_user([
        'ID' => $user->ID,
        'display_name' => sanitize_text_field($_POST['display_name'])
    ]);

    // 2. Jika Pedagang, Update Tabel dw_pedagang
    global $wpdb;
    if (in_array('pedagang', $user->roles)) {
        $tbl_pedagang = $wpdb->prefix . 'dw_pedagang';
        $wpdb->update($tbl_pedagang, [
            'nama_pemilik' => sanitize_text_field($_POST['display_name']),
            'nomor_wa' => sanitize_text_field($_POST['nomor_wa']),
            'nama_toko' => sanitize_text_field($_POST['nama_toko']),
            'alamat_lengkap' => sanitize_textarea_field($_POST['alamat'])
        ], ['id_user' => $user->ID]);
    }

    $msg = 'Profil berhasil diperbarui.';
}

// Ambil Data Existing (Pedagang)
global $wpdb;
$pedagang = null;
if (in_array('pedagang', $user->roles)) {
    $tbl_pedagang = $wpdb->prefix . 'dw_pedagang';
    $pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl_pedagang WHERE id_user = %d", $user->ID));
}

get_header();
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Profil</h1>

        <?php if($msg): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 border border-green-200">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-5">
            <?php wp_nonce_field('dw_profile_action', 'dw_profile_nonce'); ?>
            
            <!-- Foto Profil (Placeholder) -->
            <div class="flex items-center gap-4 mb-4">
                <img src="<?php echo get_avatar_url($user->ID); ?>" class="w-20 h-20 rounded-full border-4 border-gray-50">
                <div>
                    <p class="font-bold text-gray-800"><?php echo esc_html($user->user_login); ?></p>
                    <p class="text-sm text-gray-500">Role: <?php echo ucfirst($user->roles[0]); ?></p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
            </div>

            <?php if ($pedagang): ?>
            <div class="pt-4 border-t border-gray-100">
                <h3 class="font-bold text-primary mb-3">Info Toko (Pedagang)</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Toko</label>
                        <input type="text" name="nama_toko" value="<?php echo esc_attr($pedagang->nama_toko); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nomor WhatsApp</label>
                        <input type="text" name="nomor_wa" value="<?php echo esc_attr($pedagang->nomor_wa); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Toko</label>
                        <textarea name="alamat" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo esc_textarea($pedagang->alamat_lengkap); ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="pt-4">
                <button type="submit" name="dw_update_profil" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 rounded-xl transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>