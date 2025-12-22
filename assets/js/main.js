/**
 * Main JS - Tema Desa Wisata
 * Versi Lengkap: Menggabungkan UI Global, Cart AJAX (Database), dan Dashboard Merchant.
 */

jQuery(document).ready(function($) {
    'use strict';

    /* =========================================
       0. SETUP GLOBAL VARIABLES & AUTH
       ========================================= */
    
    // Cek objek data dari WordPress
    const AJAX_URL = (typeof dw_ajax !== 'undefined') ? dw_ajax.ajax_url : '';
    const NONCE = (typeof dw_ajax !== 'undefined') ? dw_ajax.nonce : '';
    
    // API Setup untuk Merchant (Jika menggunakan plugin core terpisah)
    // Fallback jika dwData tidak didefinisikan
    const API_BASE = (typeof dwData !== 'undefined') ? dwData.api_url : '/wp-json/dw/v1/'; 
    const JWT_TOKEN = localStorage.getItem('dw_jwt_token');

    // Headers untuk request API yang butuh Auth (Merchant)
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
                <div id="cart-notification" class="hidden fixed top-24 right-4 z-50 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 transition-all">
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
        
        // Cek jika ada input quantity (biasanya di single product)
        var $qtyInput = $('#qtyInput'); // ID spesifik di single product
        if($qtyInput.length && $qtyInput.is(':visible')) {
            qty = parseInt($qtyInput.val()) || 1;
        }

        var originalHtml = $btn.html();

        // Validasi
        if (!AJAX_URL) {
            console.error('AJAX URL tidak ditemukan.');
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

                    // Update Badge Keranjang
                    if (response.data.cart_count !== undefined) {
                        $('.cart-count').text(response.data.cart_count).removeClass('hidden');
                    }
                    
                    showToast('Produk masuk keranjang', 'success');

                    // Reset Tombol setelah 2 detik
                    setTimeout(function() {
                        $btn.removeClass('bg-green-500 text-white border-green-500')
                            .addClass('bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white');
                        // Restore HTML asli (icon cart)
                        $btn.html(originalHtml); 
                        
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
       3. DASHBOARD MERCHANT FUNCTIONS (RESTORED)
       Mengelola Produk, Pesanan, dan Profil Toko via AJAX API
       ========================================= */

    // A. LOAD RINGKASAN (STATS)
    window.loadMerchantSummary = function() {
        if(!$('#view-ringkasan').length) return;

        $.ajax({
            url: API_BASE + 'pedagang/dashboard/summary',
            type: 'GET',
            headers: authHeaders,
            success: function(res) {
                $('#stat-sales').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(res.penjualan_bulan_ini || 0));
                $('#stat-orders').text(res.pesanan_baru || 0);
                $('#stat-products').text(res.produk_habis || 0);
                
                if(res.pesanan_baru > 0) {
                    $('#sidebar-order-badge').text(res.pesanan_baru).removeClass('hidden');
                }
                
                // Load tabel ringkasan pesanan
                if(typeof loadMerchantOrders === 'function') {
                    loadMerchantOrders('', 5, true); 
                }
            }
        });
    }

    // B. LOAD PRODUK LIST
    window.loadMerchantProducts = function() {
        const $container = $('#merchant-product-list');
        if(!$container.length) return;
        
        $container.html('<div class="col-span-full py-12 text-center text-gray-400"><i class="fas fa-spinner animate-spin text-2xl"></i><p>Memuat produk...</p></div>');

        $.ajax({
            url: API_BASE + 'pedagang/produk',
            type: 'GET',
            headers: authHeaders,
            success: function(products) {
                if(!products || products.length === 0) {
                    $container.html('<div class="col-span-full py-12 text-center bg-white rounded-xl border border-dashed border-gray-300"><p class="text-gray-500 mb-2">Belum ada produk.</p><button onclick="openProductModal()" class="text-primary font-bold text-sm hover:underline">Tambah Produk Pertama</button></div>');
                    return;
                }

                let html = '';
                products.forEach(p => {
                    const img = (p.gambar_unggulan && p.gambar_unggulan.thumbnail) ? p.gambar_unggulan.thumbnail : 'https://via.placeholder.com/150';
                    const price = new Intl.NumberFormat('id-ID').format(p.harga_dasar);
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
            },
            error: function() {
                $container.html('<div class="col-span-full text-red-500 text-center">Gagal memuat produk.</div>');
            }
        });
    }

    // C. EDIT PRODUK TRIGGER
    window.editProduct = function(data) {
        if(typeof openProductModal === 'function') openProductModal(data);
    }

    // D. SUBMIT FORM PRODUK
    $('#form-product').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btnText = $('#btn-save-text');
        const $loader = $('#btn-save-loader');
        const id = $('#prod_id').val();
        
        let formData = new FormData(this);
        let url = API_BASE + 'pedagang/produk';
        if(id) url += '/' + id; 

        $btnText.text('Menyimpan...');
        $loader.removeClass('hidden');

        $.ajax({
            url: url,
            type: 'POST',
            headers: authHeaders,
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

    // E. HAPUS PRODUK
    window.deleteProduct = function(id) {
        if(!confirm('Yakin ingin menghapus produk ini?')) return;

        $.ajax({
            url: API_BASE + 'pedagang/produk/' + id,
            type: 'DELETE',
            headers: authHeaders,
            success: function() {
                loadMerchantProducts();
            },
            error: function() {
                alert('Gagal menghapus produk.');
            }
        });
    }

    // F. LOAD PESANAN
    window.loadMerchantOrders = function(status = '', limit = 20, isSummary = false) {
        const $container = isSummary ? $('#recent-orders-body') : $('#merchant-order-list');
        if(!$container.length) return;

        const loadingHtml = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400"><i class="fas fa-spinner animate-spin text-2xl"></i></td></tr>';
        $container.html(loadingHtml);

        let url = API_BASE + 'pedagang/orders?per_page=' + limit;
        if(status) url += '&status=' + status;

        $.ajax({
            url: url,
            type: 'GET',
            headers: authHeaders,
            success: function(orders) {
                if(!orders || orders.length === 0) {
                    $container.html('<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada pesanan.</td></tr>');
                    return;
                }

                let html = '';
                orders.forEach(o => {
                    const total = new Intl.NumberFormat('id-ID').format(o.total_pesanan_toko);
                    const date = new Date(o.tanggal_transaksi).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
                    
                    let badgeClass = 'bg-gray-100 text-gray-600';
                    if(o.status_pesanan === 'menunggu_konfirmasi') badgeClass = 'bg-yellow-100 text-yellow-700';
                    else if(o.status_pesanan === 'diproses') badgeClass = 'bg-blue-100 text-blue-700';
                    else if(o.status_pesanan === 'dikirim_ekspedisi') badgeClass = 'bg-purple-100 text-purple-700';
                    else if(o.status_pesanan === 'selesai') badgeClass = 'bg-green-100 text-green-700';
                    else if(o.status_pesanan === 'dibatalkan') badgeClass = 'bg-red-100 text-red-700';

                    let actions = '';
                    if(!isSummary) {
                        if(o.status_pesanan === 'menunggu_konfirmasi') {
                            actions = `<button onclick="updateOrderStatus(${o.sub_order_id}, 'diproses')" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">Terima</button>
                                       <button onclick="updateOrderStatus(${o.sub_order_id}, 'dibatalkan')" class="text-xs bg-white border border-red-200 text-red-600 px-3 py-1.5 rounded hover:bg-red-50 ml-1">Tolak</button>`;
                        } else if (o.status_pesanan === 'diproses') {
                            actions = `<button onclick="promptResi(${o.sub_order_id})" class="text-xs bg-purple-600 text-white px-3 py-1.5 rounded hover:bg-purple-700">Kirim Resi</button>`;
                        } else {
                            actions = `<a href="#" class="text-xs text-gray-500 underline">Lihat Detail</a>`;
                        }
                    }

                    if(isSummary) {
                        html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">#${o.sub_order_id}</td>
                            <td class="px-6 py-3">${date}</td>
                            <td class="px-6 py-3"><span class="px-2 py-1 rounded text-xs font-bold ${badgeClass}">${o.status_label}</span></td>
                            <td class="px-6 py-3 text-right font-bold text-gray-800">Rp ${total}</td>
                        </tr>`;
                    } else {
                        html += `
                        <tr class="hover:bg-gray-50 border-b border-gray-50 last:border-0">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">#${o.sub_order_id}</div>
                                <div class="text-xs text-gray-500 mt-0.5">Kode Utama: ${o.kode_unik}</div>
                            </td>
                            <td class="px-6 py-4"><div class="text-sm text-gray-900">${o.nama_pembeli}</div></td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-bold ${badgeClass}">${o.status_label}</span></td>
                            <td class="px-6 py-4 font-bold text-gray-800">Rp ${total}</td>
                            <td class="px-6 py-4 text-center"><div class="flex items-center justify-center gap-2">${actions}</div></td>
                        </tr>`;
                    }
                });
                $container.html(html);
            }
        });
    }

    // G. UPDATE STATUS PESANAN
    window.updateOrderStatus = function(id, status, resi = '') {
        if(status === 'dibatalkan' && !confirm('Yakin ingin menolak pesanan ini?')) return;

        const payload = { status: status };
        if(resi) payload.nomor_resi = resi;

        $.ajax({
            url: API_BASE + 'pedagang/orders/sub/' + id,
            type: 'POST',
            headers: authHeaders,
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function() {
                alert('Status pesanan berhasil diperbarui.');
                loadMerchantOrders();
                loadMerchantSummary();
            },
            error: function(xhr) {
                alert('Gagal update: ' + (xhr.responseJSON?.message || 'Error'));
            }
        });
    }

    window.promptResi = function(id) {
        const resi = prompt("Masukkan Nomor Resi Pengiriman:");
        if(resi) updateOrderStatus(id, 'dikirim_ekspedisi', resi);
    }

    // H. PROFIL TOKO & SAVE
    window.loadMerchantProfile = function() {
        $.ajax({
            url: API_BASE + 'pedagang/profile/me',
            type: 'GET',
            headers: authHeaders,
            success: function(data) {
                $('#set_nama_toko').val(data.nama_toko);
                $('#set_deskripsi_toko').val(data.deskripsi_toko);
                $('#set_no_rekening').val(data.no_rekening);
                $('#set_nama_bank').val(data.nama_bank);
                $('#set_atas_nama').val(data.atas_nama_rekening);
            }
        });
    }

    $('#form-settings-toko').on('submit', function(e) {
        e.preventDefault();
        const payload = {
            nama_toko: $('#set_nama_toko').val(),
            deskripsi_toko: $('#set_deskripsi_toko').val(),
            no_rekening: $('#set_no_rekening').val(),
            nama_bank: $('#set_nama_bank').val(),
            atas_nama_rekening: $('#set_atas_nama').val()
        };

        $.ajax({
            url: API_BASE + 'pedagang/profile/me',
            type: 'POST',
            headers: authHeaders,
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function() {
                alert('Pengaturan toko disimpan!');
            },
            error: function() {
                alert('Gagal menyimpan pengaturan.');
            }
        });
    });

    /* =========================================
       4. LEGACY FUNCTIONS (WISHLIST & FALLBACK CART)
       ========================================= */

    // Wishlist Toggle
    $('.btn-wishlist').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var item_id = btn.data('id');
        
        if (!AJAX_URL) return;

        $.post(AJAX_URL, {
            action: 'dw_toggle_wishlist',
            item_id: item_id,
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