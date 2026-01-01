<?php
/**
 * Template Name: Halaman Register Custom
 * Description: Pendaftaran khusus Wisatawan & Pedagang. 
 * Fix: Insert data ke tabel dw_referral_reward saat register sukses.
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

$success_message = '';
$error_message = '';
global $wpdb;

// --- LOGIKA ANALISIS KODE REFERRAL (LOCKING) ---
$ref_code_url = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
$locked_role = ''; 
$lock_reason = '';

if ( !empty($ref_code_url) ) {
    $is_desa = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}dw_desa WHERE kode_referral = %s", $ref_code_url));
    $is_verif = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}dw_verifikator WHERE kode_referral = %s", $ref_code_url));
    $is_pedagang = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}dw_pedagang WHERE kode_referral_saya = %s", $ref_code_url));

    if ( $is_desa || $is_verif ) {
        $locked_role = 'pedagang';
        $lock_reason = 'Kode undangan khusus Mitra Pedagang.';
    } elseif ( $is_pedagang ) {
        $locked_role = 'buyer';
        $lock_reason = 'Kode undangan khusus Wisatawan.';
    }
}

if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_register_nonce']) && wp_verify_nonce($_POST['dw_register_nonce'], 'dw_register_action') ) {
    
    $username     = sanitize_user($_POST['username']);
    $email        = sanitize_email($_POST['email']);
    $password     = $_POST['password'];
    $role_input   = sanitize_text_field($_POST['role_type']); 
    $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']);
    $no_hp        = sanitize_text_field($_POST['no_hp']);
    $referral_code_used = isset($_POST['kode_referral']) ? strtoupper(sanitize_text_field($_POST['kode_referral'])) : '';

    if ( !empty($locked_role) ) { $role_input = $locked_role; }

    if ( username_exists($username) || email_exists($email) ) {
        $error_message = 'Username atau Email sudah terdaftar.';
    } elseif ( empty($username) || empty($email) || empty($password) || empty($nama_lengkap) ) {
        $error_message = 'Mohon lengkapi semua kolom wajib.';
    } else {
        
        $user_id = wp_create_user( $username, $password, $email );
        
        if ( is_wp_error( $user_id ) ) {
            $error_message = $user_id->get_error_message();
        } else {
            wp_update_user([ 'ID' => $user_id, 'display_name' => $nama_lengkap, 'first_name' => $nama_lengkap, 'role' => ($role_input == 'pedagang') ? 'pedagang' : 'subscriber' ]);
            update_user_meta( $user_id, 'billing_phone', $no_hp );
            if ( !empty($referral_code_used) ) update_user_meta( $user_id, 'dw_referral_used', $referral_code_used );

            // --- INSERT TABEL CUSTOM ---
            
            if ( $role_input == 'pedagang' ) {
                $wpdb->insert($wpdb->prefix . 'dw_pedagang', [
                    'id_user' => $user_id,
                    'nama_pemilik' => $nama_lengkap,
                    'nama_toko' => $username . ' Store',
                    'slug_toko' => sanitize_title($username . '-' . time()),
                    'nomor_wa' => $no_hp,
                    'terdaftar_melalui_kode' => $referral_code_used, 
                    'status_pendaftaran' => 'menunggu_desa', 
                    'status_akun' => 'nonaktif',
                    'created_at' => current_time('mysql')
                ]);
            } else {
                // PEMBELI / WISATAWAN
                $referrer_id = 0; $referrer_type = NULL;
                
                if ( !empty($referral_code_used) ) {
                    $table_pedagang_ref = $wpdb->prefix . 'dw_pedagang';
                    $pedagang_referrer = $wpdb->get_row( $wpdb->prepare("SELECT id, sisa_transaksi FROM $table_pedagang_ref WHERE kode_referral_saya = %s", $referral_code_used) );
                    
                    if ( $pedagang_referrer ) {
                        $referrer_id = $pedagang_referrer->id;
                        $referrer_type = 'pedagang';

                        // 1. Update Counter Total di Tabel Pedagang
                        $wpdb->query( $wpdb->prepare("UPDATE $table_pedagang_ref SET total_referral_pembeli = total_referral_pembeli + 1 WHERE id = %d", $referrer_id) );

                        // --- [FIX] 2. INPUT KE TABEL REWARD ---
                        // Misal: Bonus 5 Transaksi Gratis per referral
                        $bonus_quota = 5; 
                        
                        $wpdb->insert($wpdb->prefix . 'dw_referral_reward', [
                            'id_pedagang' => $referrer_id,
                            'id_user_baru' => $user_id,
                            'kode_referral_used' => $referral_code_used,
                            'bonus_quota_diberikan' => $bonus_quota,
                            'status' => 'verified', // Langsung verified karena register sukses
                            'created_at' => current_time('mysql')
                        ]);

                        // 3. Tambahkan Kuota ke Pedagang (Eksekusi Reward)
                        $wpdb->query( $wpdb->prepare("UPDATE $table_pedagang_ref SET sisa_transaksi = sisa_transaksi + %d WHERE id = %d", $bonus_quota, $referrer_id) );
                    }
                }

                $wpdb->insert($wpdb->prefix . 'dw_pembeli', [
                    'id_user' => $user_id,
                    'nama_lengkap' => $nama_lengkap,
                    'no_hp' => $no_hp,
                    'terdaftar_melalui_kode' => $referral_code_used,
                    'referrer_id' => $referrer_id, 
                    'referrer_type' => $referrer_type,
                    'created_at' => current_time('mysql')
                ]);
            }

            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            if ( $role_input == 'pedagang' ) wp_redirect( home_url('/dashboard-toko') );
            else wp_redirect( home_url('/akun-saya') );
            exit;
        }
    }
}
get_header(); 
// ... (Sisa HTML Form Register sama seperti sebelumnya) ...
?>
<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans relative overflow-hidden">
    
    <!-- Background Decoration -->
    <div class="absolute top-0 left-0 w-full h-[600px] bg-gradient-to-b from-orange-50 to-transparent -z-10"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-yellow-100 rounded-full blur-3xl opacity-40 -z-10"></div>
    <div class="absolute top-1/2 -left-24 w-72 h-72 bg-orange-100 rounded-full blur-3xl opacity-40 -z-10"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-xl">
        <div class="text-center mb-8">
            <a href="<?php echo home_url(); ?>" class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-lg shadow-orange-200 mb-6 transform hover:scale-105 transition-transform duration-300">
                <i class="fas fa-user-plus text-3xl"></i>
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Bergabung Sekarang
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Buat akun baru untuk mulai menjelajah atau berbisnis
            </p>
        </div>
    </div>

    <div class="mt-2 sm:mx-auto sm:w-full sm:max-w-xl relative z-10">
        <div class="bg-white py-8 px-4 shadow-2xl shadow-gray-100 sm:rounded-2xl sm:px-10 border border-gray-100">
            
            <?php if($error_message): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg animate-pulse flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <h3 class="text-sm font-bold text-red-800">Registrasi Gagal</h3>
                        <p class="text-sm text-red-700 mt-1"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($lock_reason): ?>
                <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div>
                        <h3 class="text-sm font-bold text-blue-800">Mode Pendaftaran Terkunci</h3>
                        <p class="text-sm text-blue-700 mt-1"><?php echo $lock_reason; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST" x-data="{ role: '<?php echo $locked_role ?: 'buyer'; ?>' }">
                <?php wp_nonce_field('dw_register_action', 'dw_register_nonce'); ?>
                
                <!-- Pilihan Role (Locked Aware) -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3 text-center uppercase tracking-wider text-xs text-gray-400">Pilih Tipe Akun</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Wisatawan -->
                        <div @click="<?php echo ($locked_role === 'pedagang') ? '' : "role = 'buyer'"; ?>" 
                             :class="{ 'ring-2 ring-blue-500 bg-blue-50': role === 'buyer', 'border-gray-200 hover:border-blue-300 hover:bg-gray-50': role !== 'buyer' }"
                             class="cursor-pointer border rounded-xl p-4 text-center transition-all duration-200 relative group overflow-hidden <?php echo ($locked_role === 'pedagang') ? 'opacity-40 cursor-not-allowed grayscale' : ''; ?>">
                            <div class="mb-2">
                                <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center transition-colors" :class="role === 'buyer' ? 'bg-blue-200 text-blue-700' : 'bg-gray-100 text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-600'">
                                    <i class="fas fa-user text-xl"></i>
                                </div>
                            </div>
                            <span class="text-sm font-bold block" :class="role === 'buyer' ? 'text-blue-800' : 'text-gray-600'">Wisatawan</span>
                            <span class="text-xs text-gray-400 mt-1 block">Untuk belanja & wisata</span>
                            <div x-show="role === 'buyer'" class="absolute top-2 right-2 text-blue-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                        
                        <!-- Pedagang -->
                        <div @click="<?php echo ($locked_role === 'buyer') ? '' : "role = 'pedagang'"; ?>" 
                             :class="{ 'ring-2 ring-orange-500 bg-orange-50': role === 'pedagang', 'border-gray-200 hover:border-orange-300 hover:bg-gray-50': role !== 'pedagang' }"
                             class="cursor-pointer border rounded-xl p-4 text-center transition-all duration-200 relative group overflow-hidden <?php echo ($locked_role === 'buyer') ? 'opacity-40 cursor-not-allowed grayscale' : ''; ?>">
                            <div class="mb-2">
                                <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center transition-colors" :class="role === 'pedagang' ? 'bg-orange-200 text-orange-700' : 'bg-gray-100 text-gray-500 group-hover:bg-orange-100 group-hover:text-orange-600'">
                                    <i class="fas fa-store text-xl"></i>
                                </div>
                            </div>
                            <span class="text-sm font-bold block" :class="role === 'pedagang' ? 'text-orange-800' : 'text-gray-600'">Pedagang UMKM</span>
                            <span class="text-xs text-gray-400 mt-1 block">Jual produk & jasa</span>
                            <div x-show="role === 'pedagang'" class="absolute top-2 right-2 text-orange-500"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                    <input type="hidden" name="role_type" :value="role">
                </div>

                <div class="border-t border-gray-100 pt-4"></div>

                <!-- Form Fields -->
                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" required 
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                               placeholder="Nama sesuai KTP">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" id="username" required 
                                   class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                                   placeholder="Tanpa spasi">
                        </div>
                        <div>
                            <label for="no_hp" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                            <input type="text" name="no_hp" id="no_hp" required 
                                   class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                                   placeholder="0812...">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                        <input type="email" name="email" id="email" required 
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                               placeholder="email@contoh.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" required 
                               class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white"
                               placeholder="Minimal 8 karakter">
                    </div>

                    <!-- Kode Referral Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="role === 'pedagang' ? 'Kode Referral (Dari Desa/Verifikator)' : 'Kode Referral (Dari Toko/Teman)'"></label>
                        <div class="relative">
                            <input type="text" name="kode_referral" 
                                   value="<?php echo esc_attr($ref_code_url); ?>" 
                                   <?php echo !empty($ref_code_url) ? 'readonly' : ''; ?>
                                   class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 sm:text-sm transition-all bg-gray-50 focus:bg-white <?php echo !empty($ref_code_url) ? 'bg-yellow-50 text-yellow-700 font-bold cursor-not-allowed' : ''; ?>"
                                   :placeholder="role === 'pedagang' ? 'Contoh: DESA-XXX' : 'Contoh: TOKO-XXX'">
                            
                            <?php if(!empty($ref_code_url)): ?>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-xs text-green-600 font-bold bg-green-100 px-2 py-0.5 rounded-full">Terpasang</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-1 italic">
                            <?php echo !empty($ref_code_url) ? 'Kode otomatis terpasang dari link undangan.' : 'Opsional. Masukkan jika ada.'; ?>
                        </p>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-300 transform hover:-translate-y-0.5">
                        <i class="fas fa-paper-plane mr-2 mt-0.5"></i> Daftar Sekarang
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500 font-medium">Sudah punya akun?</span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="<?php echo home_url('/login'); ?>" class="text-base font-bold text-orange-600 hover:text-orange-500 transition-colors">
                        Masuk disini <i class="fas fa-arrow-right ml-1 text-sm"></i>
                    </a>
                </div>
                
                <div class="mt-6 text-center" x-show="role === 'pedagang'">
                    <div class="bg-blue-50 p-3 rounded-lg text-xs text-blue-700 border border-blue-100">
                        <i class="fas fa-info-circle mr-1"></i> 
                        <br>Pendaftaran <strong>Desa Wisata</strong> dilakukan oleh Admin.
                    </div>
                </div>
            </div>
        </div>
        
        <p class="mt-8 text-center text-xs text-gray-400">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Hak Cipta Dilindungi.
        </p>
    </div>
</div>

<script>
    // Dinamis mengubah label referral berdasarkan pilihan (Hanya jika tidak di-lock)
    function toggleReferralLabel(role) {
        const lbl = document.getElementById('lbl-referral');
        const hint = document.getElementById('hint-referral');
        const input = document.getElementById('kode_referral');
        
        // Jangan ubah teks/placeholder jika sudah ada isinya dari URL (readonly)
        if(input.hasAttribute('readonly')) return;

        if (role === 'pedagang') {
            lbl.innerText = 'Kode Referral (Dari Desa/Verifikator)';
            input.placeholder = 'Contoh: DESA-XXX atau VER-XXX';
            hint.innerText = 'Masukkan kode dari Admin Desa atau Verifikator Anda.';
        } else {
            lbl.innerText = 'Kode Referral (Dari Toko/Teman)';
            input.placeholder = 'Kode Toko...';
            hint.innerText = 'Masukkan kode toko untuk mendapatkan poin reward.';
        }
    }

    // Init state saat halaman load
    document.addEventListener("DOMContentLoaded", () => {
        // Cek input mana yang terpilih (checked)
        const selected = document.querySelector('input[name="role_type"]:checked');
        if(selected) toggleReferralLabel(selected.value);
    });
</script>

<?php get_footer(); ?>