<?php
/**
 * Template Name: Halaman Riwayat Transaksi
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

get_header();
$user_id = get_current_user_id();
?>

<div class="bg-gray-50 min-h-screen pb-20">
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex items-center gap-4">
            <a href="<?php echo home_url('/akun-saya'); ?>" class="text-gray-500 hover:text-gray-900"><i class="ph-bold ph-arrow-left text-xl"></i></a>
            <h1 class="font-bold text-lg text-gray-800">Daftar Transaksi</h1>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 max-w-2xl">
        
        <!-- Filter Status -->
        <div class="flex gap-2 overflow-x-auto no-scrollbar mb-6 pb-2">
            <button class="filter-btn active px-4 py-2 bg-gray-900 text-white rounded-full text-xs font-bold whitespace-nowrap" data-status="">Semua</button>
            <button class="filter-btn px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-xs font-bold whitespace-nowrap hover:bg-gray-50" data-status="menunggu_pembayaran">Belum Bayar</button>
            <button class="filter-btn px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-xs font-bold whitespace-nowrap hover:bg-gray-50" data-status="diproses">Diproses</button>
            <button class="filter-btn px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-xs font-bold whitespace-nowrap hover:bg-gray-50" data-status="dikirim_ekspedisi">Dikirim</button>
            <button class="filter-btn px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-full text-xs font-bold whitespace-nowrap hover:bg-gray-50" data-status="selesai">Selesai</button>
        </div>

        <!-- List Transaksi Container -->
        <div id="transaction-list" class="space-y-4">
            <!-- Loading Skeleton -->
            <div class="animate-pulse bg-white p-6 rounded-xl shadow-sm space-y-3">
                <div class="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                <div class="h-16 bg-gray-100 rounded mb-4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/3"></div>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const API_BASE = dwData.api_url;
    const JWT_TOKEN = localStorage.getItem('dw_jwt_token');

    function loadTransactions(status = '') {
        const $list = $('#transaction-list');
        $list.html('<div class="text-center py-10 text-gray-400"><i class="ph-duotone ph-spinner animate-spin text-2xl"></i><p class="text-xs mt-2">Memuat transaksi...</p></div>');

        // Panggil API Pembeli Orders
        $.ajax({
            url: API_BASE + 'pembeli/orders',
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + JWT_TOKEN },
            data: { status: status },
            success: function(response) {
                if(response.length === 0) {
                    $list.html(`
                        <div class="text-center py-12">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                                <i class="ph-duotone ph-receipt text-3xl"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800">Belum ada transaksi</h3>
                            <p class="text-xs text-gray-500 mt-1">Yuk mulai belanja produk desa!</p>
                            <a href="${dwData.home_url}/produk" class="mt-4 inline-block px-6 py-2 bg-primary text-white text-xs font-bold rounded-lg">Mulai Belanja</a>
                        </div>
                    `);
                    return;
                }

                let html = '';
                response.forEach(order => {
                    // Mapping warna status
                    let statusClass = 'bg-gray-100 text-gray-600';
                    let statusLabel = order.status_transaksi;
                    
                    if(statusLabel === 'menunggu_pembayaran') { statusClass = 'bg-orange-100 text-orange-700'; statusLabel = 'Belum Bayar'; }
                    else if(statusLabel === 'diproses') { statusClass = 'bg-blue-100 text-blue-700'; statusLabel = 'Diproses'; }
                    else if(statusLabel === 'dikirim_ekspedisi') { statusClass = 'bg-purple-100 text-purple-700'; statusLabel = 'Dikirim'; }
                    else if(statusLabel === 'selesai') { statusClass = 'bg-green-100 text-green-700'; statusLabel = 'Selesai'; }

                    const total = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(order.total_transaksi);
                    const date = new Date(order.tanggal_transaksi).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

                    html += `
                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3 border-b border-gray-50 pb-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <i class="ph-fill ph-storefront text-gray-400"></i>
                                    <span class="text-xs font-bold text-gray-800">Pesanan #${order.kode_unik}</span>
                                </div>
                                <span class="text-[10px] text-gray-400">${date}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider ${statusClass}">${statusLabel}</span>
                        </div>
                        
                        <div class="flex justify-between items-end">
                            <div>
                                <span class="text-xs text-gray-500">Total Belanja</span>
                                <div class="text-sm font-bold text-gray-900">${total}</div>
                            </div>
                            <a href="<?php echo home_url('/detail-transaksi?id='); ?>${order.id}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50 transition">
                                Lihat Detail
                            </a>
                        </div>
                    </div>`;
                });
                $list.html(html);
            },
            error: function() {
                $list.html('<div class="text-center text-red-500 text-xs py-4">Gagal memuat data. Periksa koneksi Anda.</div>');
            }
        });
    }

    // Init Load
    loadTransactions();

    // Filter Click
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('bg-gray-900 text-white').addClass('bg-white text-gray-600 border border-gray-200');
        $(this).removeClass('bg-white text-gray-600 border border-gray-200').addClass('bg-gray-900 text-white');
        loadTransactions($(this).data('status'));
    });
});
</script>

<?php get_footer(); ?>