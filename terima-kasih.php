<?php
/**
 * Template Name: Terima Kasih Order Premium
 * Description: Desain eksklusif untuk konfirmasi pesanan dengan instruksi kasir yang sangat jelas.
 */

get_header();

// Tangkap data dari URL
$kode_unik = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : 'TRX-' . strtoupper(wp_generate_password(6, false));
$status    = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$is_cash   = ($status === 'cash');

// Warna Tema
$primary_color = $is_cash ? 'amber' : 'emerald';
?>

<!-- Import Resource -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse-soft {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
    }
    .animate-fade-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-pulse-soft { animation: pulse-soft 3s ease-in-out infinite; }
    
    .bg-gradient-premium {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    
    .ticket-shape {
        position: relative;
        background: white;
    }
    .ticket-shape::before, .ticket-shape::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 24px;
        height: 24px;
        background: #f8fafc; /* Match background color */
        border-radius: 50%;
        transform: translateY(-50%);
        z-index: 10;
    }
    .ticket-shape::before { left: -12px; }
    .ticket-shape::after { right: -12px; }
</style>

<div class="min-h-screen bg-gradient-premium py-16 px-4 flex items-center justify-center font-sans antialiased text-slate-900">
    <div class="max-w-2xl w-full">
        
        <!-- Main Container -->
        <div class="animate-fade-up overflow-hidden">
            
            <!-- Top Status Bar -->
            <div class="flex justify-center mb-8">
                <div class="bg-white px-6 py-2 rounded-full shadow-sm border border-slate-200 flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-<?php echo $primary_color; ?>-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-<?php echo $primary_color; ?>-500"></span>
                    </span>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-500">Menunggu Pembayaran</span>
                </div>
            </div>

            <!-- Header Section -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-<?php echo $primary_color; ?>-600 rounded-3xl shadow-lg mb-6 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                    <i class="fas fa-receipt text-3xl text-white"></i>
                </div>
                <h1 class="text-4xl font-black text-slate-900 mb-3 tracking-tight">Pesanan Diterima!</h1>
                <p class="text-slate-500 text-lg max-w-sm mx-auto leading-relaxed">
                    Satu langkah lagi untuk menyelesaikan belanjaan Anda.
                </p>
            </div>

            <!-- Digital Ticket / Order Info -->
            <div class="ticket-shape bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden mb-8">
                <!-- Ticket Top: The ID -->
                <div class="p-10 text-center border-b border-dashed border-slate-200 relative">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mb-4">Nomor Transaksi Utama</p>
                    <div class="inline-flex items-center gap-4 bg-slate-50 px-8 py-4 rounded-2xl border border-slate-100">
                        <span id="order-id-display" class="text-4xl font-mono font-black text-slate-800 tracking-tighter">
                            <?php echo esc_html($kode_unik); ?>
                        </span>
                        <button onclick="copyID('<?php echo $kode_unik; ?>')" class="text-slate-300 hover:text-<?php echo $primary_color; ?>-600 transition-colors p-2">
                            <i class="far fa-copy text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Ticket Bottom: The Instructions -->
                <div class="p-10 bg-slate-50/50">
                    <?php if ($is_cash): ?>
                        <div class="space-y-8">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="h-px flex-1 bg-slate-200"></div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cara Pembayaran</span>
                                <div class="h-px flex-1 bg-slate-200"></div>
                            </div>

                            <!-- Step Icons -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="text-center group">
                                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-3 group-hover:border-amber-200 transition-colors">
                                        <i class="fas fa-mobile-alt text-amber-500"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-700">Tunjukkan Kode</p>
                                    <p class="text-[10px] text-slate-400 mt-1">Siapkan HP atau screenshot halaman ini</p>
                                </div>
                                <div class="text-center group">
                                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-3 group-hover:border-amber-200 transition-colors">
                                        <i class="fas fa-user-tie text-amber-500"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-700">Kunjungi Kasir</p>
                                    <p class="text-[10px] text-slate-400 mt-1">Sebutkan Anda ingin bayar pesanan marketplace</p>
                                </div>
                                <div class="text-center group">
                                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-3 group-hover:border-amber-200 transition-colors">
                                        <i class="fas fa-check-circle text-amber-500"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-700">Validasi Berhasil</p>
                                    <p class="text-[10px] text-slate-400 mt-1">Simpan struk sebagai bukti bayar sah</p>
                                </div>
                            </div>

                            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm mt-4">
                                <p class="text-sm text-slate-600 leading-relaxed text-center italic">
                                    "Silakan tunjukkan nomor transaksi <strong class="text-slate-900"><?php echo esc_html($kode_unik); ?></strong> kepada petugas kasir kami untuk memproses pembayaran Anda secara tunai."
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-slate-500 font-medium">Silakan lakukan transfer sesuai nominal yang tertera di halaman detail pesanan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="<?php echo home_url('/akun/pesanan'); ?>" class="flex items-center justify-center gap-3 bg-slate-900 hover:bg-black text-white font-bold py-5 rounded-[1.5rem] transition-all shadow-xl hover:-translate-y-1">
                    <i class="fas fa-list-ul text-sm opacity-50"></i>
                    <span>Cek Status Pesanan</span>
                </a>
                <button onclick="window.print()" class="flex items-center justify-center gap-3 bg-white border-2 border-slate-200 hover:border-slate-400 text-slate-600 font-bold py-5 rounded-[1.5rem] transition-all">
                    <i class="fas fa-print text-sm opacity-50"></i>
                    <span>Cetak Bukti</span>
                </button>
            </div>

            <!-- Help Support -->
            <div class="mt-12 text-center">
                <p class="text-sm text-slate-400 font-medium mb-4">Ada kendala? Hubungi tim support kami</p>
                <div class="flex justify-center gap-8">
                    <a href="https://wa.me/628123456789" class="text-slate-400 hover:text-green-500 transition-colors flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                        <i class="fab fa-whatsapp text-lg"></i> WhatsApp
                    </a>
                    <a href="mailto:support@domain.com" class="text-slate-400 hover:text-blue-500 transition-colors flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                        <i class="far fa-envelope text-lg"></i> Email CS
                    </a>
                </div>
            </div>
        </div>

        <p class="text-center mt-12 text-slate-400 text-[10px] font-bold uppercase tracking-[0.2em]">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?> Marketplace
        </p>
    </div>
</div>

<script>
function copyID(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.currentTarget;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-emerald-500"></i>';
        
        // Animasi feedback visual pada teks ID
        const display = document.getElementById('order-id-display');
        display.classList.add('text-emerald-500', 'scale-110');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            display.classList.remove('text-emerald-500', 'scale-110');
        }, 2000);
    });
}
</script>

<?php get_footer(); ?>