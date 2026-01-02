<?php
/**
 * Template Name: Terima Kasih Order Premium
 * Description: Desain eksklusif untuk konfirmasi pesanan dengan instruksi dinamis + Fitur Download PDF High Quality.
 */

get_header();

// Tangkap data dari URL
$kode_unik = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : 'TRX-' . strtoupper(wp_generate_password(6, false));

// Tangkap Metode Pembayaran
$method_param = isset($_GET['method']) ? sanitize_text_field($_GET['method']) : (isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '');

// Logika Tampilan Berdasarkan Metode
$is_tunai = ($method_param === 'tunai' || $method_param === 'cash');
$is_cod   = ($method_param === 'cod');
$is_offline = ($is_tunai || $is_cod);

// Warna Tema
$primary_color = $is_offline ? 'amber' : 'emerald';

// Judul & Subjudul Dinamis
if ($is_tunai) {
    $judul_utama = "Menunggu Pembayaran";
    $sub_judul   = "Silakan bayar di kasir toko.";
    $icon_main   = "fa-store";
} elseif ($is_cod) {
    $judul_utama = "Pesanan Diterima";
    $sub_judul   = "Siapkan uang pas saat kurir tiba.";
    $icon_main   = "fa-truck";
} else {
    $judul_utama = "Pesanan Berhasil";
    $sub_judul   = "Terima kasih telah berbelanja.";
    $icon_main   = "fa-check-circle";
}
?>

<!-- Import Resource -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Import html2pdf.js Versi Terbaru -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* Animasi UI Web */
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

    /* CSS Khusus Saat Print/PDF (Menghapus shadow agar hasil bersih) */
    .printing .shadow-xl, .printing .shadow-lg, .printing .shadow-sm {
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
    }
    .printing .animate-fade-up {
        animation: none !important;
        transform: none !important;
        opacity: 1 !important;
    }
</style>

<div class="min-h-screen bg-gradient-premium py-16 px-4 flex items-center justify-center font-sans antialiased text-slate-900">
    <div class="max-w-2xl w-full">
        
        <!-- Main Container (Target PDF) -->
        <!-- Tambahkan padding putih ekstra agar rapi di PDF -->
        <div id="receipt-container" class="animate-fade-up overflow-hidden p-6 bg-white md:bg-transparent rounded-xl">
            
            <!-- Top Status Bar -->
            <div class="flex justify-center mb-8">
                <div class="bg-white px-6 py-2 rounded-full shadow-sm border border-slate-200 flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-<?php echo $primary_color; ?>-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-<?php echo $primary_color; ?>-500"></span>
                    </span>
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-500">
                        <?php echo $is_offline ? 'Proses Pembayaran' : 'Selesai'; ?>
                    </span>
                </div>
            </div>

            <!-- Header Section -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-<?php echo $primary_color; ?>-600 rounded-3xl shadow-lg mb-6 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                    <i class="fas <?php echo $icon_main; ?> text-3xl text-white"></i>
                </div>
                <h1 class="text-4xl font-black text-slate-900 mb-3 tracking-tight"><?php echo esc_html($judul_utama); ?>!</h1>
                <p class="text-slate-500 text-lg max-w-sm mx-auto leading-relaxed">
                    <?php echo esc_html($sub_judul); ?>
                </p>
            </div>

            <!-- Digital Ticket / Order Info -->
            <div class="ticket-shape bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden mb-8">
                <!-- Ticket Top: The ID -->
                <div class="p-10 text-center border-b border-dashed border-slate-200 relative">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mb-4">Nomor Transaksi</p>
                    <div class="inline-flex items-center gap-4 bg-slate-50 px-8 py-4 rounded-2xl border border-slate-100">
                        <span id="order-id-display" class="text-4xl font-mono font-black text-slate-800 tracking-tighter">
                            <?php echo esc_html($kode_unik); ?>
                        </span>
                        <!-- Tombol copy diabaikan di PDF -->
                        <button onclick="copyID('<?php echo $kode_unik; ?>')" data-html2canvas-ignore="true" class="text-slate-300 hover:text-<?php echo $primary_color; ?>-600 transition-colors p-2">
                            <i class="far fa-copy text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Ticket Bottom: The Instructions -->
                <div class="p-10 bg-slate-50/50">
                    <?php if ($is_tunai): ?>
                        <div class="space-y-8">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="h-px flex-1 bg-slate-200"></div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Instruksi Kasir</span>
                                <div class="h-px flex-1 bg-slate-200"></div>
                            </div>
                            <!-- Grid 3 Kolom -->
                            <div class="grid grid-cols-3 gap-6">
                                <div class="text-center group">
                                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-mobile-alt text-amber-500"></i></div>
                                    <p class="text-xs font-bold text-slate-700">Tunjukkan Kode</p>
                                </div>
                                <div class="text-center group">
                                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-store text-amber-500"></i></div>
                                    <p class="text-xs font-bold text-slate-700">Datang ke Toko</p>
                                </div>
                                <div class="text-center group">
                                    <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-receipt text-amber-500"></i></div>
                                    <p class="text-xs font-bold text-slate-700">Bayar & Ambil</p>
                                </div>
                            </div>
                            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm mt-4 text-center">
                                <p class="text-sm text-slate-600 leading-relaxed italic">
                                    "Silakan tunjukkan nomor transaksi <strong class="text-slate-900"><?php echo esc_html($kode_unik); ?></strong> kepada petugas kasir."
                                </p>
                            </div>
                        </div>

                    <?php elseif ($is_cod): ?>
                        <div class="space-y-8">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="h-px flex-1 bg-slate-200"></div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Instruksi COD</span>
                                <div class="h-px flex-1 bg-slate-200"></div>
                            </div>
                            <div class="text-center">
                                <div class="inline-block p-4 bg-blue-50 rounded-full mb-4">
                                    <i class="fas fa-truck-fast text-2xl text-blue-500"></i>
                                </div>
                                <h3 class="font-bold text-slate-800 mb-2">Segera Dikirim</h3>
                                <p class="text-sm text-slate-500 max-w-xs mx-auto">
                                    Siapkan uang tunai pas untuk kurir.
                                </p>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-slate-500 font-medium">Pesanan Anda telah kami terima.</p>
                            <a href="<?php echo home_url('/akun/pesanan'); ?>" data-html2canvas-ignore="true" class="text-emerald-600 font-bold hover:underline mt-2 inline-block">Lihat Detail Pesanan</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer Buttons (IGNORED IN PDF) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" data-html2canvas-ignore="true">
                <a href="<?php echo home_url('/akun/pesanan'); ?>" class="flex items-center justify-center gap-3 bg-slate-900 hover:bg-black text-white font-bold py-5 rounded-[1.5rem] transition-all shadow-xl hover:-translate-y-1">
                    <i class="fas fa-list-ul text-sm opacity-50"></i>
                    <span>Cek Status</span>
                </a>
                
                <button onclick="downloadPDF()" id="btn-download" class="flex items-center justify-center gap-3 bg-white border-2 border-slate-200 hover:border-slate-400 text-slate-600 font-bold py-5 rounded-[1.5rem] transition-all group">
                    <i class="fas fa-download text-sm opacity-50 group-hover:text-blue-600"></i>
                    <span class="group-hover:text-blue-600">Download Bukti</span>
                </button>
            </div>

            <!-- Help Support (IGNORED IN PDF) -->
            <div class="mt-12 text-center" data-html2canvas-ignore="true">
                <p class="text-sm text-slate-400 font-medium mb-4">Butuh bantuan?</p>
                <div class="flex justify-center gap-8">
                    <a href="#" class="text-slate-400 hover:text-green-500 transition-colors flex items-center gap-2 text-xs font-bold uppercase tracking-widest">
                        <i class="fab fa-whatsapp text-lg"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <p class="text-center mt-12 text-slate-400 text-[10px] font-bold uppercase tracking-[0.2em]" data-html2canvas-ignore="true">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>
        </p>
    </div>
</div>

<script>
// Fungsi Download PDF High Quality
function downloadPDF() {
    const element = document.getElementById('receipt-container');
    const btn = document.getElementById('btn-download');
    const originalText = btn.innerHTML;
    
    // 1. Feedback UI Loading
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin text-blue-500"></i> <span class="text-blue-500">Memproses PDF...</span>';
    btn.disabled = true;

    // 2. Tambahkan class khusus agar CSS print aktif (hapus shadow dll)
    element.classList.add('printing');

    // 3. Konfigurasi Optimal untuk A4
    const opt = {
        margin:       [0.5, 0.5, 0.5, 0.5], // Margin 0.5 inch
        filename:     'Bukti-Pesanan-<?php echo $kode_unik; ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 3, // Resolusi tinggi (3x) agar tidak blur
            useCORS: true, // Izinkan gambar cross-origin
            windowWidth: 1200, // PAKSA RENDER MODE DESKTOP (Agar tidak layout HP)
            scrollY: 0
        },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    // 4. Generate
    html2pdf().set(opt).from(element).save().then(function(){
        // Reset tombol dan class
        btn.innerHTML = originalText;
        btn.disabled = false;
        element.classList.remove('printing');
    }).catch(function(err) {
        alert('Gagal membuat PDF. Silakan coba screenshot manual.');
        console.error(err);
        btn.innerHTML = originalText;
        btn.disabled = false;
        element.classList.remove('printing');
    });
}

function copyID(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Simple feedback
        const display = document.getElementById('order-id-display');
        const originalColor = display.className;
        display.classList.add('text-emerald-500');
        setTimeout(() => {
            display.classList.remove('text-emerald-500');
        }, 1000);
    });
}
</script>

<?php get_footer(); ?>