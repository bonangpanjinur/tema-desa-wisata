<?php
/**
 * Template Name: Dashboard Desa
 * Description: Panel untuk pengelola desa wisata.
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}
get_header(); 
?>

<div class="bg-gray-50 min-h-screen font-sans flex">
    
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl shadow-lg shadow-blue-500/30">
                <i class="fas fa-landmark"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 leading-tight">Admin Desa</h2>
                <p class="text-[10px] text-gray-400">Pengelola Wisata</p>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <button onclick="switchTab('ringkasan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition active-tab" id="nav-ringkasan">
                <i class="fas fa-chart-line w-5 text-center"></i> Ringkasan
            </button>
            <button onclick="switchTab('wisata')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-wisata">
                <i class="fas fa-map-marked-alt w-5 text-center"></i> Kelola Wisata
            </button>
            <button onclick="switchTab('profil')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-profil">
                <i class="fas fa-user-edit w-5 text-center"></i> Profil Desa
            </button>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition w-full">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <span class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-landmark text-blue-600"></i> Admin Desa</span>
        <button onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('flex');" class="text-gray-600 p-2">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen">
        
        <!-- TAB: RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Statistik Desa</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-map"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Wisata</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="stat-wisata">0</h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-star"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Rata-rata Rating</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="stat-rating">0.0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: KELOLA WISATA -->
        <div id="view-wisata" class="tab-content hidden animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Daftar Objek Wisata</h1>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-blue-500/20">
                    <i class="fas fa-plus"></i> Tambah Wisata
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="desa-wisata-list">
                <!-- List Wisata akan diload via JS/AJAX di sini -->
                <div class="col-span-full py-12 text-center text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
                    <p>Belum ada data wisata.</p>
                </div>
            </div>
        </div>

        <!-- TAB: PROFIL DESA -->
        <div id="view-profil" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Profil Desa Wisata</h1>
            <div class="max-w-3xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <form>
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Desa</label>
                        <input type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none">
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Lengkap</label>
                        <textarea rows="5" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Provinsi</label>
                            <input type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Kabupaten</label>
                            <input type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                        </div>
                    </div>
                    <button class="w-full bg-gray-900 text-white font-bold py-3.5 rounded-xl hover:bg-gray-800 transition">Simpan Profil</button>
                </form>
            </div>
        </div>

    </main>
</div>

<style>
.active-tab { background-color: #eff6ff; color: #2563eb; border-right: 3px solid #2563eb; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    document.getElementById('view-' + tabName).classList.remove('hidden');
    document.getElementById('nav-' + tabName).classList.add('active-tab');
}
</script>

<?php wp_footer(); ?>