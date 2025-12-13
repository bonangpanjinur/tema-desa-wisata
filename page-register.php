<?php
/* Template Name: Halaman Register */

// Proses Registrasi
$error = '';
$success = '';

if (isset($_POST['dw_register_submit']) && isset($_POST['dw_register_nonce']) && wp_verify_nonce($_POST['dw_register_nonce'], 'dw_register_action')) {
    
    $fullname   = sanitize_text_field($_POST['fullname']);
    $username   = sanitize_user($_POST['username']);
    $email      = sanitize_email($_POST['email']);
    $password   = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (username_exists($username)) {
        $error = 'Username sudah digunakan.';
    } elseif (email_exists($email)) {
        $error = 'Email sudah terdaftar.';
    } else {
        $user_id = wp_create_user($username, $password, $email);
        if (!is_wp_error($user_id)) {
            // Update Nama Depan (Opsional)
            wp_update_user(['ID' => $user_id, 'display_name' => $fullname]);
            
            // Auto Login (Opsional, atau redirect ke login)
            $success = 'Registrasi berhasil! Silakan login.';
        } else {
            $error = $user_id->get_error_message();
        }
    }
}

get_header(); 
?>

<div class="min-h-screen bg-gray-100 flex items-center justify-center py-10 px-4">
    
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        <!-- Header Ilustrasi -->
        <div class="bg-emerald-600 p-6 text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full bg-white opacity-10" style="background-image: radial-gradient(circle, #000 1px, transparent 1px); background-size: 20px 20px;"></div>
            <i class="fas fa-user-plus text-5xl text-emerald-100 mb-2 relative z-10"></i>
            <h2 class="text-2xl font-bold text-white relative z-10">Buat Akun Baru</h2>
            <p class="text-emerald-100 text-xs relative z-10">Gabung dan nikmati kemudahan berwisata</p>
        </div>

        <div class="p-8">
            
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-xs mb-4 flex items-center gap-2 border border-red-100">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 text-green-600 p-4 rounded-lg text-center mb-6 border border-green-100">
                    <i class="fas fa-check-circle text-3xl mb-2 block"></i>
                    <p class="font-bold">Akun Berhasil Dibuat!</p>
                    <a href="<?php echo home_url('/login'); ?>" class="mt-3 inline-block bg-green-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-md hover:bg-green-700 transition">Masuk Sekarang</a>
                </div>
            <?php else: ?>

                <form action="" method="post" class="space-y-4">
                    
                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 ml-1">Nama Lengkap</label>
                        <div class="relative group">
                            <i class="fas fa-id-card absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition"></i>
                            <input type="text" name="fullname" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 focus:bg-white transition text-sm text-gray-700 placeholder-gray-400" placeholder="Contoh: Budi Santoso" required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 ml-1">Alamat Email</label>
                        <div class="relative group">
                            <i class="fas fa-envelope absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition"></i>
                            <input type="email" name="email" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 focus:bg-white transition text-sm text-gray-700 placeholder-gray-400" placeholder="nama@email.com" required>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 ml-1">Username</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition"></i>
                            <input type="text" name="username" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 focus:bg-white transition text-sm text-gray-700 placeholder-gray-400" placeholder="Tanpa spasi, cth: budi123" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 ml-1">Password</label>
                        <div class="relative group">
                            <i class="fas fa-lock absolute left-4 top-3.5 text-gray-400 group-focus-within:text-emerald-500 transition"></i>
                            <input type="password" name="password" class="w-full bg-gray-50 border border-gray-200 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:border-emerald-500 focus:bg-white transition text-sm text-gray-700 placeholder-gray-400" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>

                    <!-- Terms Checkbox -->
                    <div class="flex items-start gap-2 mt-2">
                        <input type="checkbox" required class="mt-1 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300">
                        <span class="text-xs text-gray-500 leading-snug">Saya setuju dengan <a href="#" class="text-emerald-600 hover:underline">Syarat & Ketentuan</a> serta Kebijakan Privasi yang berlaku.</span>
                    </div>

                    <div class="pt-2">
                        <?php wp_nonce_field('dw_register_action', 'dw_register_nonce'); ?>
                        <button type="submit" name="dw_register_submit" class="w-full bg-emerald-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-emerald-200 hover:bg-emerald-700 hover:shadow-xl transition transform hover:-translate-y-0.5">
                            Daftar Sekarang
                        </button>
                    </div>

                </form>

            <?php endif; ?>

            <!-- Footer Link -->
            <div class="mt-8 text-center border-t border-gray-100 pt-6">
                <p class="text-gray-500 text-sm">Sudah punya akun?</p>
                <a href="<?php echo home_url('/login'); ?>" class="text-emerald-600 font-bold text-sm hover:text-emerald-700 transition">Masuk ke Akun Saya</a>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>