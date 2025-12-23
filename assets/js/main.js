/**
 * Main JS - Tema Desa Wisata
 * Versi Lengkap: Menggabungkan UI Global, Cart AJAX (Database), dan Dashboard Merchant.
 */

jQuery(document).ready(function($) {
    'use strict';

    /* =========================================
       0. SETUP GLOBAL VARIABLES & AUTH
       ========================================= */
    
    // Mengambil variabel dari wp_localize_script di functions.php
    const AJAX_URL = (typeof dw_ajax !== 'undefined') ? dw_ajax.ajax_url : '';
    const NONCE = (typeof dw_ajax !== 'undefined') ? dw_ajax.nonce : '';
    
    // API Setup untuk Merchant (Opsional jika pakai REST API terpisah)
    const API_BASE = (typeof dwData !== 'undefined') ? dwData.api_url : '/wp-json/dw/v1/'; 
    const JWT_TOKEN = localStorage.getItem('dw_jwt_token');

    // Headers untuk request API yang butuh Auth
    const authHeaders = {};
    if(JWT_TOKEN) {
        authHeaders['Authorization'] = 'Bearer ' + JWT_TOKEN;
    }

    /* =========================================
       1. UI GLOBAL HANDLERS (Menu & Header)
       ========================================= */
    
    // Toggle Mobile Menu
    $('#mobile-menu-btn').on('click', function() {
        $('#mobile-menu').toggleClass('hidden');
    });

    // Close Mobile Menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#mobile-menu, #mobile-menu-btn').length) {
            $('#mobile-menu').addClass('hidden');
        }
    });

    // Sticky Header Effect
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 50) {
            $('header').addClass('shadow-md bg-white/95 backdrop-blur');
        } else {
            $('header').removeClass('shadow-md bg-white/95 backdrop-blur');
        }
    });

    /* =========================================
       2. FITUR KERANJANG BELANJA (AJAX / DATABASE)
       Digunakan di: Single Produk, Card Produk, Halaman Cart
       ========================================= */

    // Helper: Toast Notification
    function showToast(msg, type = 'info') {
        let $toast = $('#cart-notification');
        
        // Buat elemen toast jika belum ada
        if ($toast.length === 0) {
            $('body').append(`
                <div id="cart-notification" class="hidden fixed top-24 right-4 z-[9999] bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 transition-all">
                    <i class="fas fa-info-circle icon"></i> <span class="msg"></span>
                </div>
            `);
            $toast = $('#cart-notification');
        }

        const $text = $toast.find('.msg');
        const $icon = $toast.find('.icon');
        
        $text.text(msg);
        $toast.removeClass('hidden bg-red-600 bg-gray-900 bg-green-600');
        
        if(type === 'error') {
            $toast.addClass('bg-red-600');
            $icon.attr('class', 'fas fa-exclamation-circle icon');
        } else if(type === 'success') {
            $toast.addClass('bg-green-600');
            $icon.attr('class', 'fas fa-check-circle icon');
        } else {
            $toast.addClass('bg-gray-900');
            $icon.attr('class', 'fas fa-info-circle icon');
        }

        $toast.fadeIn().css('display', 'flex');
        
        setTimeout(() => {
            $toast.fadeOut();
        }, 3000);
    }

    // A. ADD TO CART (Card & Single Page)
    $(document).on('click', '.js-add-to-cart', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var productId = $btn.data('id');
        var qty = 1;
        
        // Cek jika ada input quantity (biasanya di single product page)
        var $qtyInput = $('#qtyInput'); 
        if($qtyInput.length && $qtyInput.is(':visible')) {
            qty = parseInt($qtyInput.val()) || 1;
        }

        var originalHtml = $btn.html();

        // Validasi
        if (!AJAX_URL) {
            console.error('AJAX URL tidak ditemukan. Pastikan functions.php meload script dengan benar.');
            return;
        }

        // State Loading
        $btn.addClass('cursor-wait opacity-75').prop('disabled', true);
        // Simpan icon asli, ganti spinner
        $btn.html('<i class="fas fa-spinner fa-spin"></i>');

        // Kirim Request AJAX
        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'dw_add_to_cart',
                product_id: productId,
                qty: qty,
                security: NONCE
            },
            success: function(response) {
                if (response.success) {
                    // Animasi Sukses
                    $btn.removeClass('bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white bg-primary')
                        .addClass('bg-green-500 text-white border-green-500');
                    $btn.html('<i class="fas fa-check"></i>');

                    // Update Badge Keranjang di Header
                    if (response.data.cart_count !== undefined) {
                        $('.cart-count').text(response.data.cart_count).removeClass('hidden');
                    }
                    
                    showToast('Produk masuk keranjang', 'success');

                    // Reset Tombol setelah 2 detik
                    setTimeout(function() {
                        $btn.removeClass('bg-green-500 text-white border-green-500')
                            .addClass('bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white');
                        // Restore HTML asli (icon cart)
                        
                        // Khusus tombol text panjang (seperti di single product)
                        if($btn.text().trim().length > 0) {
                             $btn.html(originalHtml); // Kembalikan text asli
                        } else {
                             // Kembalikan icon default untuk card
                             $btn.html('<i class="fas fa-cart-plus text-xs pointer-events-none"></i>'); 
                        }
                        
                        $btn.removeClass('cursor-wait opacity-75').prop('disabled', false);
                    }, 2000);

                } else {
                    showToast(response.data.message || 'Gagal menambahkan', 'error');
                    $btn.html(originalHtml);
                    $btn.removeClass('cursor-wait opacity-75').prop('disabled', false);
                }
            },
            error: function() {
                showToast('Koneksi terputus', 'error');
                $btn.html(originalHtml);
                $btn.removeClass('cursor-wait opacity-75').prop('disabled', false);
            }
        });
    });

    // B. UPDATE QUANTITY (+ / -) di Halaman Cart
    $(document).on('click', '.js-update-qty', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const action = $btn.data('action');
        const cartId = $btn.data('cart-id');
        const $input = $('#qty-' + cartId);
        const $loader = $('#loader-' + cartId);
        
        let currentQty = parseInt($input.val());
        let newQty = (action === 'increase') ? currentQty + 1 : currentQty - 1;

        if (newQty < 1) return; // Minimal 1, jika mau hapus pakai tombol hapus

        // UI Feedback
        $btn.prop('disabled', true).addClass('opacity-50');
        $loader.removeClass('hidden');

        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'dw_update_cart_qty',
                cart_id: cartId,
                qty: newQty,
                security: NONCE
            },
            success: function(res) {
                if(res.success) {
                    // Update Input
                    $input.val(res.data.new_qty);
                    
                    // Update Totals di Sidebar
                    $('#summary-grand-total').text(res.data.grand_total_fmt);
                    $('#summary-items-count').text(res.data.total_items + ' Barang');
                    $('#btn-buy-count').text(res.data.total_items);
                    
                    // Update Badge di Header
                    $('.cart-count').text(res.data.total_items).removeClass('hidden');

                } else {
                    showToast(res.data.message || 'Gagal update', 'error');
                    if(res.data.current_qty) {
                        $input.val(res.data.current_qty); // Kembalikan ke qty valid
                    }
                }
            },
            error: function() {
                showToast('Koneksi terputus', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('opacity-50');
                $loader.addClass('hidden');
            }
        });
    });

    // C. REMOVE CART ITEM (Hapus)
    $(document).on('click', '.js-remove-cart-item', function(e) {
        e.preventDefault();
        
        if(!confirm('Yakin ingin menghapus produk ini dari keranjang?')) return;

        const $btn = $(this);
        const cartId = $btn.data('cart-id');
        const $row = $('#row-' + cartId);

        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: {
                action: 'dw_remove_cart_item',
                cart_id: cartId,
                security: NONCE
            },
            success: function(res) {
                if(res.success) {
                    // Hapus Baris dengan animasi fade out
                    $row.fadeOut(300, function() { 
                        $(this).remove(); 
                        // Reload halaman jika cart kosong agar layout rapi (opsional)
                        if($('.cart-item-row').length === 0) {
                            location.reload(); 
                        }
                    });

                    // Update Totals
                    $('#summary-grand-total').text(res.data.grand_total_fmt);
                    $('#summary-items-count').text(res.data.total_items + ' Barang');
                    $('#btn-buy-count').text(res.data.total_items);
                    $('.cart-count').text(res.data.total_items);
                    
                    showToast('Produk dihapus', 'success');
                } else {
                    showToast('Gagal menghapus item', 'error');
                }
            }
        });
    });

    /* =========================================
       3. DASHBOARD MERCHANT FUNCTIONS (AJAX)
       Mengelola Produk, Pesanan, dan Profil Toko via AJAX
       ========================================= */

    // A. LOAD RINGKASAN (STATS)
    window.loadMerchantSummary = function() {
        if(!$('#view-ringkasan').length) return;

        $.post(AJAX_URL, { action: 'dw_merchant_stats', security: NONCE }, function(res) {
            if(res.success) {
                $('#stat-sales').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(res.data.sales || 0));
                $('#stat-orders').text(res.data.orders || 0);
                $('#stat-products').text(res.data.products_empty || 0);
                
                if(res.data.orders > 0) {
                    $('#sidebar-order-badge').text(res.data.orders).removeClass('hidden');
                }
                
                loadMerchantOrders(5, true); 
            }
        });
    }

    // B. LOAD PRODUK LIST
    window.loadMerchantProducts = function() {
        const $container = $('#merchant-product-list');
        if(!$container.length) return;
        
        $container.html('<div class="col-span-full py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i><p>Memuat produk...</p></div>');

        $.post(AJAX_URL, { action: 'dw_merchant_get_products', security: NONCE }, function(res) {
            if(!res.success || !res.data || res.data.length === 0) {
                $container.html('<div class="col-span-full py-12 text-center bg-white rounded-xl border border-dashed border-gray-300"><p class="text-gray-500 mb-2">Belum ada produk.</p><button onclick="openProductModal()" class="text-primary font-bold text-sm hover:underline">Tambah Produk Pertama</button></div>');
                return;
            }

            let html = '';
            res.data.forEach(p => {
                const img = (p.foto_utama) ? p.foto_utama : 'https://via.placeholder.com/150';
                const price = new Intl.NumberFormat('id-ID').format(p.harga);
                const jsonString = JSON.stringify(p).replace(/"/g, '&quot;');

                html += `
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col group">
                    <div class="relative h-40 bg-gray-100 overflow-hidden">
                        <img src="${img}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                            <button onclick='editProduct(${jsonString})' class="w-8 h-8 bg-white rounded-full text-blue-600 shadow flex items-center justify-center hover:bg-blue-50"><i class="fas fa-pencil-alt"></i></button>
                            <button onclick="deleteProduct(${p.id})" class="w-8 h-8 bg-white rounded-full text-red-600 shadow flex items-center justify-center hover:bg-red-50"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h4 class="font-bold text-gray-800 line-clamp-2 mb-1">${p.nama_produk}</h4>
                        <div class="mt-auto">
                            <p class="text-primary font-bold">Rp ${price}</p>
                            <p class="text-xs text-gray-400 mt-1">Stok: ${p.stok}</p>
                        </div>
                    </div>
                </div>`;
            });
            $container.html(html);
        });
    }

    // C. LOAD PESANAN
    window.loadMerchantOrders = function(limit = 20, isSummary = false) {
        const $container = isSummary ? $('#recent-orders-body') : $('#merchant-order-list');
        if(!$container.length) return;

        // Colspan disesuaikan dengan header tabel di page-dashboard-toko.php (7 kolom untuk full, 4 untuk summary)
        const colspan = isSummary ? 4 : 7;
        const loadingHtml = `<tr><td colspan="${colspan}" class="px-6 py-8 text-center text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></td></tr>`;
        $container.html(loadingHtml);

        $.post(AJAX_URL, {
            action: 'dw_merchant_get_orders',
            limit: limit,
            security: NONCE
        }, function(res) {
            if(!res.success || !res.data || res.data.length === 0) {
                $container.html(`<tr><td colspan="${colspan}" class="px-6 py-8 text-center text-gray-500">Tidak ada pesanan.</td></tr>`);
                return;
            }

            let html = '';
            res.data.forEach(o => {
                let badgeClass = 'bg-gray-100 text-gray-600';
                if(o.status_pesanan === 'menunggu_konfirmasi') badgeClass = 'bg-yellow-100 text-yellow-700';
                else if(o.status_pesanan === 'diproses') badgeClass = 'bg-blue-100 text-blue-700';
                else if(o.status_pesanan === 'dikirim_ekspedisi') badgeClass = 'bg-purple-100 text-purple-700';
                else if(o.status_pesanan === 'selesai') badgeClass = 'bg-green-100 text-green-700';
                else if(o.status_pesanan === 'dibatalkan') badgeClass = 'bg-red-100 text-red-700';

                // BUKTI BAYAR LOGIC
                let buktiHtml = '<span class="text-xs text-gray-400 italic">Tidak ada</span>';
                if(o.bukti_pembayaran) {
                    // Tombol ini memanggil fungsi viewProof() di bawah
                    buktiHtml = `<button type="button" onclick="viewProof('${o.bukti_pembayaran}')" class="flex items-center gap-1 text-blue-600 hover:bg-blue-50 px-2 py-1.5 rounded-lg border border-blue-100 text-xs font-bold transition group"><i class="fas fa-eye group-hover:scale-110 transition-transform"></i> Lihat Bukti</button>`;
                } else {
                    buktiHtml = '<span class="text-xs text-red-400 bg-red-50 px-2 py-1 rounded border border-red-100">Belum Upload</span>';
                }

                if(isSummary) {
                    // Tampilan Ringkas (Dashboard Home)
                    html += `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 font-medium text-gray-900">#${o.id}</td>
                        <td class="px-6 py-3 text-sm text-gray-500">${o.formatted_date}</td>
                        <td class="px-6 py-3"><span class="px-2 py-1 rounded text-xs font-bold ${badgeClass}">${o.status_label}</span></td>
                        <td class="px-6 py-3 text-right font-bold text-gray-800">${o.formatted_total}</td>
                    </tr>`;
                } else {
                    // Tampilan Lengkap (Tab Pesanan)
                    let actions = `<span class="text-xs text-gray-400">Menunggu</span>`;
                    
                    if(o.status_pesanan === 'menunggu_konfirmasi') {
                        // Jika bukti ada, tombol proses aktif. Jika tidak, peringatan.
                        if(o.bukti_pembayaran) {
                            actions = `
                                <button onclick="updateOrderStatus(${o.id}, 'diproses')" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 mr-1 shadow-sm transition">Proses</button>
                                <button onclick="updateOrderStatus(${o.id}, 'dibatalkan')" class="text-xs bg-white border border-red-200 text-red-600 px-3 py-1.5 rounded hover:bg-red-50 transition">Tolak</button>
                            `;
                        } else {
                            actions = `<span class="text-xs text-yellow-600 bg-yellow-50 px-2 py-1 rounded border border-yellow-200">Cek Pembayaran</span>`;
                        }
                    } else if (o.status_pesanan === 'diproses') {
                        actions = `<button onclick="promptResi(${o.id})" class="text-xs bg-purple-600 text-white px-3 py-1.5 rounded hover:bg-purple-700 shadow-sm transition">Kirim Resi</button>`;
                    } else if (o.status_pesanan === 'dikirim_ekspedisi') {
                        actions = `<span class="text-xs text-green-600 font-bold">Dalam Pengiriman</span>`;
                    } else {
                        actions = `<span class="text-xs text-gray-400">Selesai</span>`;
                    }

                    html += `
                    <tr class="hover:bg-gray-50 border-b border-gray-100 last:border-0 transition">
                        <td class="px-6 py-4 font-bold text-gray-900">#${o.id}</td>
                        <td class="px-6 py-4 text-xs font-mono text-gray-500 bg-gray-50 px-1 rounded">${o.kode_unik || '-'}</td>
                        <td class="px-6 py-4"><div class="text-sm font-bold text-gray-900">${o.nama_pembeli}</div><div class="text-xs text-gray-400">${o.formatted_date}</div></td>
                        <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-bold ${badgeClass}">${o.status_label}</span></td>
                        <td class="px-6 py-4">${buktiHtml}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">${o.formatted_total}</td>
                        <td class="px-6 py-4 text-center">${actions}</td>
                    </tr>`;
                }
            });
            $container.html(html);
        });
    }

    // --- FUNGSI MODAL BUKTI BAYAR ---
    window.viewProof = function(url) {
        const modal = document.getElementById('modal-bukti');
        if(!modal) return console.error('Modal bukti tidak ditemukan di HTML');
        
        const content = document.getElementById('modal-bukti-content');
        const img = document.getElementById('img-bukti-bayar');
        const link = document.getElementById('link-download-bukti');

        img.src = url;
        link.href = url;
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    window.closeProofModal = function() {
        const modal = document.getElementById('modal-bukti');
        if(!modal) return;
        const content = document.getElementById('modal-bukti-content');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('img-bukti-bayar').src = '';
        }, 300);
    }

    // D. FORM ACTIONS (Save Product & Profile)
    $('#form-product').on('submit', function(e) {
        e.preventDefault();
        const $btnText = $('#btn-save-text');
        const $loader = $('#btn-save-loader');
        
        let formData = new FormData(this);
        formData.append('action', 'dw_merchant_save_product');
        formData.append('security', NONCE);

        $btnText.text('Menyimpan...');
        $loader.removeClass('hidden');

        $.ajax({
            url: AJAX_URL,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                alert('Produk berhasil disimpan!');
                if(typeof closeProductModal === 'function') closeProductModal();
                loadMerchantProducts();
                loadMerchantSummary();
            },
            error: function(xhr) {
                alert('Gagal: ' + (xhr.responseJSON?.message || 'Terjadi kesalahan'));
            },
            complete: function() {
                $btnText.text('Simpan Produk');
                $loader.addClass('hidden');
            }
        });
    });

    $('#form-settings-toko').on('submit', function(e) {
        e.preventDefault();
        let data = $(this).serializeArray();
        data.push({name: 'action', value: 'dw_merchant_save_profile'});
        data.push({name: 'security', value: NONCE});

        $.post(AJAX_URL, data, function(res) {
            alert('Profil toko tersimpan!');
        });
    });

    // Helper functions global
    window.editProduct = function(data) { if(typeof openProductModal === 'function') openProductModal(data); }
    window.deleteProduct = function(id) {
        if(!confirm('Hapus produk?')) return;
        $.post(AJAX_URL, { action: 'dw_merchant_delete_product', product_id: id, security: NONCE }, function() {
            loadMerchantProducts();
        });
    }
    window.updateOrderStatus = function(id, status, resi = '') {
        if(status === 'dibatalkan' && !confirm('Tolak pesanan?')) return;
        $.post(AJAX_URL, { action: 'dw_merchant_update_status', order_id: id, status: status, resi: resi, security: NONCE }, function() {
            loadMerchantOrders();
            loadMerchantSummary();
        });
    }
    window.promptResi = function(id) {
        const resi = prompt("Masukkan Nomor Resi Pengiriman:");
        if(resi) updateOrderStatus(id, 'dikirim_ekspedisi', resi);
    }
    window.loadMerchantProfile = function() {
        $.post(AJAX_URL, { action: 'dw_merchant_get_profile', security: NONCE }, function(res) {
            if(res.success) {
                $('#set_nama_toko').val(res.data.nama_toko);
                $('#set_deskripsi_toko').val(res.data.deskripsi_toko);
                $('#set_no_rekening').val(res.data.no_rekening);
                $('#set_nama_bank').val(res.data.nama_bank);
                $('#set_atas_nama').val(res.data.atas_nama_rekening);
            }
        });
    }

    // Wishlist Toggle (Legacy)
    $('.btn-wishlist').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        
        if (!AJAX_URL) return;

        $.post(AJAX_URL, {
            action: 'dw_toggle_wishlist',
            item_id: btn.data('id'),
            item_type: 'wisata',
            security: NONCE
        }, function(response) {
            if(response.success) {
                var icon = btn.find('i');
                if(response.data.status === 'added') {
                    icon.removeClass('far').addClass('fas text-red-500');
                } else {
                    icon.removeClass('fas text-red-500').addClass('far');
                }
            } else {
                if(response.data.code === 'not_logged_in') {
                    alert('Silakan login untuk menyimpan favorit.');
                    window.location.href = '/login'; 
                }
            }
        });
    });

    // Jalankan Fungsi Awal jika di halaman dashboard
    if($('#view-ringkasan').length) {
        loadMerchantSummary();
    }
});