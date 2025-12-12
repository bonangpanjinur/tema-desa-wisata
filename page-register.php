<?php
/**
 * Template Name: Halaman Register
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    if ( in_array( 'pedagang', (array) $current_user->roles ) ) {
        wp_redirect( home_url( '/dashboard-toko/' ) );
    } else {
        wp_redirect( home_url( '/akun-saya/' ) );
    }
    exit;
}

get_header(); 
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo -->
        <div class="text-center mb-6">
            <h2 class="text-3xl font-extrabold text-gray-900">Bergabunglah Bersama Kami</h2>
            <p class="mt-2 text-sm text-gray-600">
                Pilih peran Anda untuk memulai pengalaman di Desa Wisata.
            </p>
        </div>

        <!-- Role Switcher (Tab) -->
        <div class="bg-white rounded-t-2xl shadow-sm border-b border-gray-100 flex overflow-hidden">
            <button onclick="switchTab('pembeli')" id="tab-pembeli" class="flex-1 py-4 text-center font-medium text-sm text-primary border-b-2 border-primary bg-green-50 transition-colors">
                <i class="ph-bold ph-user mr-2"></i> Pembeli
            </button>
            <button onclick="switchTab('pedagang')" id="tab-pedagang" class="flex-1 py-4 text-center font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="ph-bold ph-storefront mr-2"></i> Pedagang
            </button>
        </div>

        <div class="bg-white py-8 px-6 shadow sm:rounded-b-2xl sm:px-10">
            
            <!-- Alert Container -->
            <div id="register-alert" class="hidden mb-4 p-4 rounded-lg text-sm flex items-center gap-2"></div>

            <form id="dw-register-form" class="space-y-5">
                
                <!-- Hidden Input Role -->
                <input type="hidden" name="role" id="role-input" value="pembeli">

                <!-- Nama Lengkap -->
                <div>
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph-bold ph-identification-card text-gray-400"></i>
                        </div>
                        <input id="fullname" name="fullname" type="text" required class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="Sesuai KTP">
                    </div>
                </div>

                <!-- Username & Email Grid -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="reg_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input id="reg_username" name="username" type="text" required class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="user123">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input id="email" name="email" type="email" required class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="mail@contoh.com">
                    </div>
                </div>

                <!-- No HP -->
                <div>
                    <label for="no_hp" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph-bold ph-whatsapp-logo text-gray-400"></i>
                        </div>
                        <input id="no_hp" name="no_hp" type="text" required class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="0812...">
                    </div>
                </div>

                <!-- Field Khusus Pedagang (Hidden by Default) -->
                <div id="pedagang-fields" class="hidden space-y-5 border-t border-gray-100 pt-5 mt-5">
                    <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-3 text-xs text-yellow-700 flex gap-2">
                        <i class="ph-fill ph-info text-lg"></i>
                        <p>Akun pedagang memerlukan verifikasi dari Admin Desa sebelum bisa berjualan.</p>
                    </div>
                    <div>
                        <label for="nama_toko" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ph-bold ph-storefront text-gray-400"></i>
                            </div>
                            <input id="nama_toko" name="nama_toko" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="Contoh: Keripik Bu Ani">
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="reg_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ph-bold ph-lock-key text-gray-400"></i>
                        </div>
                        <input id="reg_password" name="password" type="password" required class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-xl focus:ring-primary focus:border-primary sm:text-sm transition" placeholder="Minimal 6 karakter">
                        <button type="button" onclick="togglePasswordVisibility('reg_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="ph-bold ph-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Terms -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="terms" name="terms" type="checkbox" required class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="font-medium text-gray-700">Saya setuju dengan <a href="#" class="text-primary hover:underline">Syarat & Ketentuan</a></label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" id="btn-reg-submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all transform active:scale-95">
                        <span id="btn-reg-text">Daftar Sekarang</span>
                        <i id="btn-reg-loader" class="ph-bold ph-spinner animate-spin ml-2 hidden text-lg"></i>
                    </button>
                </div>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Sudah punya akun? 
                    <a href="<?php echo home_url('/login'); ?>" class="font-medium text-primary hover:text-emerald-600 transition">
                        Masuk di sini
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab Switcher Logic
    function switchTab(role) {
        // Update UI Tabs
        const tabPembeli = document.getElementById('tab-pembeli');
        const tabPedagang = document.getElementById('tab-pedagang');
        const pedagangFields = document.getElementById('pedagang-fields');
        const roleInput = document.getElementById('role-input');
        const namaTokoInput = document.getElementById('nama_toko');

        if (role === 'pembeli') {
            tabPembeli.className = 'flex-1 py-4 text-center font-bold text-sm text-primary border-b-2 border-primary bg-green-50 transition-colors';
            tabPedagang.className = 'flex-1 py-4 text-center font-medium text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors';
            pedagangFields.classList.add('hidden');
            roleInput.value = 'pembeli';
            namaTokoInput.removeAttribute('required');
        } else {
            tabPedagang.className = 'flex-1 py-4 text-center font-bold text-sm text-primary border-b-2 border-primary bg-green-50 transition-colors';
            tabPembeli.className = 'flex-1 py-4 text-center font-medium text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors';
            pedagangFields.classList.remove('hidden');
            roleInput.value = 'pedagang';
            namaTokoInput.setAttribute('required', 'required');
        }
    }

    // Toggle Password
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('ph-eye', 'ph-eye-slash');
        } else {
            input.type = "password";
            icon.classList.replace('ph-eye-slash', 'ph-eye');
        }
    }
</script>

<?php get_footer(); ?>