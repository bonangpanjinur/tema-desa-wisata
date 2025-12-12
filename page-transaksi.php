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

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Riwayat Transaksi</h1>

        <!-- Tab Status (Opsional) -->
        <div class="flex gap-4 border-b border-gray-200 mb-6 overflow-x-auto">
            <button class="pb-3 border-b-2 border-primary text-primary font-medium whitespace-nowrap">Semua</button>
            <button class="pb-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">Belum Bayar</button>
            <button class="pb-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">Dikirim</button>
        </div>

        <!-- List Transaksi Container -->
        <div id="transaction-list" class="space-y-4">
            <!-- Loading State -->
            <div class="animate-pulse bg-white p-6 rounded-xl shadow-sm space-y-3">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load Transaksi via API
    $.ajax({
        url: dwData.api_url + 'pembeli/orders', // Pastikan endpoint ini ada di plugin Core
        type: 'GET',
        beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + localStorage.getItem('dw_jwt_token'));
        },
        success: function(response) {
            const $container = $('#transaction-list');
            $container.empty();

            if(response.length === 0) {
                $container.html('<div class="text-center py-10 text-gray-500">Belum ada transaksi.</div>');
                return;
            }

            response.forEach(order => {
                // Logic render status warna
                let statusColor = 'bg-gray-100 text-gray-600';
                if(order.status === 'completed') statusColor = 'bg-green-100 text-green-700';
                if(order.status === 'pending') statusColor = 'bg-yellow-100 text-yellow-700';

                const html = `
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-bold text-gray-800">#${order.order_id}</span>
                                <span class="text-xs px-2 py-1 rounded-full font-bold ${statusColor}">${order.status_label}</span>
                                <span class="text-xs text-gray-400">${order.date}</span>
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-1">${order.items_summary}</p>
                        </div>
                        <div class="flex items-center justify-between md:flex-col md:items-end gap-2">
                            <span class="font-bold text-primary">Rp ${new Intl.NumberFormat('id-ID').format(order.total)}</span>
                            <a href="${dwData.home_url}/transaksi/detail/?id=${order.order_id}" class="px-4 py-2 bg-white border border-gray-200 text-sm font-bold rounded-lg hover:bg-gray-50">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                `;
                $container.append(html);
            });
        },
        error: function() {
            $('#transaction-list').html('<div class="text-red-500 p-4">Gagal memuat riwayat transaksi. Silakan login ulang.</div>');
        }
    });
});
</script>

<?php get_footer(); ?>