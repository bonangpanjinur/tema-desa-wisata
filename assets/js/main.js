/**
 * Main JS - Tema Desa Wisata
 * Menangani Cart, Auth, dan Dashboard Toko (Merchant).
 */

jQuery(document).ready(function($) {
    
    const API_BASE = dwData.api_url;
    const JWT_TOKEN = localStorage.getItem('dw_jwt_token');

    // Headers untuk AJAX yang butuh Auth
    const authHeaders = {};
    if(JWT_TOKEN) {
        authHeaders['Authorization'] = 'Bearer ' + JWT_TOKEN;
    }

    /* =========================================
       1. DASHBOARD MERCHANT FUNCTIONS
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
                $('#stat-products').text(res.produk_habis || 0); // Atau total produk active
                
                // Update badge di sidebar jika ada pesanan baru
                if(res.pesanan_baru > 0) {
                    $('#sidebar-order-badge').text(res.pesanan_baru).removeClass('hidden');
                }
                
                // Load tabel ringkasan pesanan juga
                loadMerchantOrders('', 5, true); 
            }
        });
    }

    // B. LOAD PRODUK
    window.loadMerchantProducts = function() {
        const $container = $('#merchant-product-list');
        $container.html('<div class="col-span-full py-12 text-center text-gray-400"><i class="ph-duotone ph-spinner animate-spin text-2xl"></i><p>Memuat produk...</p></div>');

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
                    
                    // Kita simpan data produk dalam attribut data-json untuk keperluan edit
                    const jsonString = JSON.stringify(p).replace(/"/g, '&quot;');

                    html += `
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col group">
                        <div class="relative h-40 bg-gray-100 overflow-hidden">
                            <img src="${img}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                <button onclick='editProduct(${jsonString})' class="w-8 h-8 bg-white rounded-full text-blue-600 shadow flex items-center justify-center hover:bg-blue-50"><i class="ph-bold ph-pencil-simple"></i></button>
                                <button onclick="deleteProduct(${p.id})" class="w-8 h-8 bg-white rounded-full text-red-600 shadow flex items-center justify-center hover:bg-red-50"><i class="ph-bold ph-trash"></i></button>
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

    // Helper Global untuk Edit (dipanggil dari onclick HTML)
    window.editProduct = function(data) {
        openProductModal(data);
    }

    // C. SUBMIT PRODUK (ADD/EDIT)
    $('#form-product').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btnText = $('#btn-save-text');
        const $loader = $('#btn-save-loader');
        const id = $('#prod_id').val();
        
        // Buat FormData untuk handle file upload
        let formData = new FormData(this);
        
        // Endpoint dinamis (Create or Update)
        let url = API_BASE + 'pedagang/produk';
        if(id) {
            url += '/' + id; // Append ID for update
            // Untuk update via FormData di beberapa server setup, method kadang harus POST tapi dengan _method=PUT atau logic khusus.
            // Di sini kita asumsikan Endpoint menerima POST untuk update jika ID ada di URL.
        }

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
                closeProductModal();
                loadMerchantProducts();
                loadMerchantSummary(); // Refresh stats
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

    // D. HAPUS PRODUK
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

    // E. LOAD PESANAN
    window.loadMerchantOrders = function(status = '', limit = 20, isSummary = false) {
        const $container = isSummary ? $('#recent-orders-body') : $('#merchant-order-list');
        const loadingHtml = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400"><i class="ph-duotone ph-spinner animate-spin text-2xl"></i></td></tr>';
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
                    
                    // Badge Warna Status
                    let badgeClass = 'bg-gray-100 text-gray-600';
                    if(o.status_pesanan === 'menunggu_konfirmasi') badgeClass = 'bg-yellow-100 text-yellow-700';
                    if(o.status_pesanan === 'diproses') badgeClass = 'bg-blue-100 text-blue-700';
                    if(o.status_pesanan === 'dikirim_ekspedisi') badgeClass = 'bg-purple-100 text-purple-700';
                    if(o.status_pesanan === 'selesai') badgeClass = 'bg-green-100 text-green-700';
                    if(o.status_pesanan === 'dibatalkan') badgeClass = 'bg-red-100 text-red-700';

                    // Tombol Aksi (Hanya muncul di Tab Pesanan utama, bukan ringkasan)
                    let actions = '';
                    if(!isSummary) {
                        if(o.status_pesanan === 'menunggu_konfirmasi') {
                            actions = `
                                <button onclick="updateOrderStatus(${o.sub_order_id}, 'diproses')" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">Terima</button>
                                <button onclick="updateOrderStatus(${o.sub_order_id}, 'dibatalkan')" class="text-xs bg-white border border-red-200 text-red-600 px-3 py-1.5 rounded hover:bg-red-50 ml-1">Tolak</button>
                            `;
                        } else if (o.status_pesanan === 'diproses') {
                            actions = `
                                <button onclick="promptResi(${o.sub_order_id})" class="text-xs bg-purple-600 text-white px-3 py-1.5 rounded hover:bg-purple-700">Kirim Resi</button>
                            `;
                        } else {
                            actions = `<a href="#" class="text-xs text-gray-500 underline">Lihat Detail</a>`;
                        }
                    }

                    // Kolom berbeda untuk ringkasan vs full list
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
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">${o.nama_pembeli}</div>
                            </td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-bold ${badgeClass}">${o.status_label}</span></td>
                            <td class="px-6 py-4 font-bold text-gray-800">Rp ${total}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">${actions}</div>
                            </td>
                        </tr>`;
                    }
                });
                $container.html(html);
            }
        });
    }

    // F. UPDATE STATUS PESANAN
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
        if(resi) {
            updateOrderStatus(id, 'dikirim_ekspedisi', resi);
        }
    }

    // G. LOAD PROFIL TOKO
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

    // H. SAVE PROFIL TOKO
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
       2. LOGIKA KERANJANG (CART)
       (Kode Cart yang sudah ada tetap dipertahankan di sini)
       ========================================= */
    const CART_KEY = 'dw_cart_v1';
    
    function getCart() { try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch (e) { return []; } }
    function saveCart(cart) { localStorage.setItem(CART_KEY, JSON.stringify(cart)); updateCartCount(); }
    
    function updateCartCount() {
        const cart = getCart();
        const count = cart.reduce((acc, item) => acc + item.qty, 0);
        const $badge = $('#header-cart-count');
        const $badgeInner = $('#header-cart-count-inner'); // Untuk header halaman dalam
        
        if (count > 0) {
            $badge.text(count).removeClass('hidden scale-0').addClass('flex scale-100');
            $badgeInner.text(count).removeClass('hidden scale-0').addClass('flex scale-100');
        } else {
            $badge.addClass('hidden scale-0').removeClass('flex scale-100');
            $badgeInner.addClass('hidden scale-0').removeClass('flex scale-100');
        }
    }
    updateCartCount();

    // ADD TO CART HANDLER
    $(document).on('click', '.add-to-cart-btn, #single-add-cart, #btn-add-to-cart', function(e) {
        e.preventDefault();
        const btn = $(this);
        const id = parseInt(btn.data('id')) || 0;
        
        if(id === 0) return;

        // Cek input qty (khusus halaman detail produk), default 1
        let qty = 1;
        if ($('#qty-input').length) {
            qty = parseInt($('#qty-input').val()) || 1;
        }

        let cart = getCart();
        const existingItem = cart.find(item => item.id === id);

        if (existingItem) {
            existingItem.qty += qty;
        } else {
            cart.push({
                id: id,
                title: btn.data('title'),
                price: parseInt(btn.data('price')) || 0,
                thumb: btn.data('thumb'),
                qty: qty
            });
        }
        saveCart(cart);
        
        // Visual Feedback
        const originalHTML = btn.html();
        
        // Ubah tampilan tombol sesaat menjadi hijau/sukses
        btn.html('<i class="ph-bold ph-check"></i> Masuk')
           .removeClass('bg-primary bg-gray-900 text-primary border-primary') // Hapus kelas lama
           .addClass('bg-green-600 text-white border-transparent shadow-none'); // Tambah kelas sukses
        
        // Tampilkan notifikasi toast jika elemennya ada (opsional)
        const $toast = $('#cart-message');
        if ($toast.length) {
            $toast.text('Produk berhasil ditambahkan ke keranjang').fadeIn().delay(2000).fadeOut();
        }
        
        setTimeout(() => {
            btn.html(originalHTML).removeClass('bg-green-600 text-white border-transparent shadow-none');
            
            // Kembalikan class asli berdasarkan ID tombol agar style tidak rusak
            if (btn.attr('id') === 'single-add-cart') {
                 // Tombol di single produk (versi lama/desktop)
                 btn.addClass('bg-white text-primary border-2 border-primary');
            } else if (btn.attr('id') === 'btn-add-to-cart') {
                 // Tombol di single produk (versi app style/mobile)
                 btn.addClass('bg-gray-900 text-white');
            } else {
                 // Tombol default di grid produk (archive)
                 btn.addClass('text-primary'); 
            }
        }, 1500);
    });

    // FUNGSI CART TAMBAHAN (CLEAR, REMOVE, UPDATE QTY, RENDER)
    
    // Fungsi Global: Kosongkan keranjang
    window.clearCart = function() {
        if(confirm('Yakin ingin mengosongkan keranjang?')) {
            localStorage.removeItem(CART_KEY);
            renderCartPage();
            updateCartCount();
        }
    }

    // Fungsi Global: Hapus item spesifik
    window.removeCartItem = function(id) {
        let cart = getCart();
        cart = cart.filter(item => item.id !== id);
        saveCart(cart);
        renderCartPage();
    }

    // Fungsi Global: Update qty plus/minus di halaman cart
    window.updateCartItemQty = function(id, change) {
        let cart = getCart();
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty += change;
            if (item.qty < 1) item.qty = 1; // Minimal 1
            saveCart(cart);
            renderCartPage();
        }
    }

    // Fungsi Render Utama Halaman Cart
    function renderCartPage() {
        // Hanya jalankan jika elemen container keranjang ada di halaman
        if (!$('#cart-items-container').length) return;

        const cart = getCart();
        const $container = $('#cart-items-container');
        const $emptyState = $('#cart-empty');
        const $checkoutBar = $('.fixed.bottom-0'); // Checkout bar melayang di bawah

        if (cart.length === 0) {
            $container.html('').hide();
            // Jika ada elemen empty state khusus
            if ($emptyState.length) {
                $emptyState.removeClass('hidden').addClass('flex');
            } else {
                // Fallback jika tidak ada elemen empty state terpisah
                $container.html('<div class="p-8 text-center text-gray-500"><i class="fas fa-shopping-basket text-4xl mb-4 text-gray-300"></i><p>Keranjang belanja Anda kosong.</p><a href="' + dwData.home_url + '/produk" class="text-primary font-semibold hover:underline mt-2 inline-block">Mulai Belanja</a></div>').show();
            }
            $checkoutBar.hide(); // Sembunyikan tombol checkout jika kosong
            // Update summary di sidebar jika ada (versi desktop)
            if ($('#summary-count').length) $('#summary-count').text('0 barang');
            if ($('#summary-total').length) $('#summary-total').text('Rp 0');
            return;
        } else {
            $container.show();
            if ($emptyState.length) $emptyState.addClass('hidden').removeClass('flex');
            $checkoutBar.show();
        }

        let html = '';
        let total = 0;
        let count = 0;

        cart.forEach(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            count += item.qty;

            // Format Rupiah JS
            const formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.price);

            html += `
            <div class="bg-white p-3 rounded-2xl shadow-soft border border-gray-50 flex gap-3 relative overflow-hidden group mb-3">
                <!-- Image -->
                <div class="w-20 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 border border-gray-200">
                    <img src="${item.thumb}" class="w-full h-full object-cover">
                </div>
                
                <!-- Details -->
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 line-clamp-1">${item.title}</h3>
                        <p class="text-[10px] text-gray-500">ID: ${item.id}</p>
                    </div>
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-bold text-secondary">${formattedPrice}</span>
                        
                        <!-- Qty Control -->
                        <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200">
                            <button onclick="updateCartItemQty(${item.id}, -1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-primary active:bg-gray-200 rounded-l-lg">-</button>
                            <input type="text" value="${item.qty}" class="w-8 h-8 text-center text-xs bg-transparent border-none p-0 focus:ring-0 font-bold" readonly>
                            <button onclick="updateCartItemQty(${item.id}, 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-primary active:bg-gray-200 rounded-r-lg">+</button>
                        </div>
                    </div>
                </div>

                <!-- Delete Button (Top Right) -->
                <button onclick="removeCartItem(${item.id})" class="absolute top-2 right-2 text-gray-300 hover:text-red-500 p-2">
                    <i class="ph-bold ph-trash text-lg"></i>
                </button>
            </div>`;
        });

        $container.html(html);
        
        // Update Total di Checkout Bar Bawah (Mobile App Style)
        const formattedTotal = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total);
        $('.fixed.bottom-0 .text-lg.font-bold').text(formattedTotal);
        $('.fixed.bottom-0 a').text(`Checkout (${count})`);

        // Update Summary di Sidebar (Desktop Style)
        if ($('#summary-count').length) $('#summary-count').text(count + ' barang');
        if ($('#summary-total').length) $('#summary-total').text(formattedTotal);
    }

    // Jalankan render saat load jika kita berada di halaman cart
    if ($('#cart-items-container').length || $('#cart-container').length) {
        // Fallback selector jika ID berbeda di template page-cart.php
        if (!$('#cart-items-container').length && $('#cart-container').length) {
             // Jika menggunakan template page-cart.php yang lama, tambahkan container ID yang sesuai
             // atau sesuaikan renderCartPage untuk menargetkan #cart-container
             // Di sini kita asumsikan #cart-container adalah wrapper utama
             if (!$('#cart-items-container').length) {
                 $('#cart-container').append('<div id="cart-items-container"></div>');
             }
        }
        renderCartPage();
    }

});