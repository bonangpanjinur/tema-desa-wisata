/**
 * Main JS - Tema Desa Wisata
 * Menangani Cart, Login, Register, dan UI Interaktif
 */

jQuery(document).ready(function($) {
    
    // Debugging: Cek koneksi API
    console.log('DW Core JS Loaded. API Base:', dwData.api_url);

    /* =========================================
       1. GLOBAL CART LOGIC (LocalStorage)
       ========================================= */
    const CART_KEY = 'dw_cart_v1';
    
    // Helper: Ambil data keranjang
    function getCart() {
        try {
            return JSON.parse(localStorage.getItem(CART_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    // Helper: Simpan data keranjang
    function saveCart(cart) {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        updateCartCount();
    }

    // Helper: Update badge jumlah di header
    function updateCartCount() {
        const cart = getCart();
        const count = cart.reduce((acc, item) => acc + item.qty, 0);
        const $badge = $('#header-cart-count');
        const $badgeInner = $('#header-cart-count-inner'); // Untuk header inner page
        
        if (count > 0) {
            $badge.text(count).removeClass('hidden').addClass('flex');
            $badgeInner.text(count).removeClass('hidden').addClass('flex');
        } else {
            $badge.addClass('hidden').removeClass('flex');
            $badgeInner.addClass('hidden').removeClass('flex');
        }
    }

    // Init count saat halaman dimuat
    updateCartCount();

    /* =========================================
       2. ADD TO CART HANDLER
       ========================================= */
    $(document).on('click', '.add-to-cart-btn, #single-add-cart, #btn-add-to-cart', function(e) {
        e.preventDefault();
        const btn = $(this);
        
        // Ambil data produk dari atribut data-
        const id = parseInt(btn.data('id')) || 0;
        const title = btn.data('title');
        const price = parseInt(btn.data('price')) || 0;
        const thumb = btn.data('thumb');
        
        if (id === 0) {
            console.error('Product ID not found');
            return;
        }
        
        // Cek input qty (untuk halaman detail), default 1
        let qty = 1;
        if ($('#qty-input').length) {
            qty = parseInt($('#qty-input').val()) || 1;
        }

        // Logika tambah ke array
        let cart = getCart();
        const existingItem = cart.find(item => item.id === id);

        if (existingItem) {
            existingItem.qty += qty;
        } else {
            cart.push({ id, title, price, thumb, qty });
        }

        saveCart(cart);

        // Efek Visual Tombol
        const originalHTML = btn.html();
        
        // Ubah tampilan tombol sesaat
        btn.html('<i class="ph-bold ph-check"></i> Masuk')
           .removeClass('bg-primary bg-gray-900 text-primary border-primary') // Hapus kelas lama
           .addClass('bg-green-600 text-white border-transparent shadow-none'); // Tambah kelas sukses
        
        // Tampilkan notifikasi toast (jika ada elemennya)
        const $toast = $('#cart-message');
        if ($toast.length) {
            $toast.text('Produk berhasil ditambahkan').fadeIn().delay(2000).fadeOut();
        }
        
        setTimeout(() => {
            // Kembalikan tombol ke semula
            btn.html(originalHTML)
               .removeClass('bg-green-600 text-white border-transparent shadow-none');

            // Kembalikan class asli berdasarkan ID tombol (handling berbagai tombol di tema)
            if (btn.attr('id') === 'single-add-cart') {
                 // Tombol di single-dw_produk (versi lama)
                 btn.addClass('bg-white text-primary border-2 border-primary');
            } else if (btn.attr('id') === 'btn-add-to-cart') {
                 // Tombol di single-dw_produk (versi app style)
                 btn.addClass('bg-gray-900 text-white');
            } else {
                 // Tombol default di archive
                 btn.addClass('text-primary'); 
            }
        }, 1500);
    });

    /* =========================================
       3. CART PAGE RENDERER (Halaman Keranjang)
       ========================================= */
    // Fungsi global agar bisa dipanggil dari HTML (onclick)
    window.clearCart = function() {
        if(confirm('Yakin ingin mengosongkan keranjang?')) {
            localStorage.removeItem(CART_KEY);
            renderCartPage();
            updateCartCount();
        }
    }

    window.removeCartItem = function(id) {
        let cart = getCart();
        cart = cart.filter(item => item.id !== id);
        saveCart(cart);
        renderCartPage();
    }

    // Fungsi untuk mengubah qty di halaman cart (plus/minus)
    window.updateCartItemQty = function(id, change) {
        let cart = getCart();
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty += change;
            if (item.qty < 1) item.qty = 1;
            saveCart(cart);
            renderCartPage();
        }
    }

    function renderCartPage() {
        // Hanya jalankan jika elemen container keranjang ada
        if (!$('#cart-items-container').length) return;

        const cart = getCart();
        const $container = $('#cart-items-container');
        const $emptyState = $('#cart-empty');
        const $checkoutBar = $('.fixed.bottom-0'); // Checkout bar di page-cart.php

        if (cart.length === 0) {
            $container.html('').hide();
            $emptyState.removeClass('hidden').addClass('flex');
            $checkoutBar.hide(); // Sembunyikan checkout bar jika kosong
            return;
        } else {
            $container.show();
            $emptyState.addClass('hidden').removeClass('flex');
            $checkoutBar.show();
        }

        let html = '';
        let total = 0;
        let count = 0;

        cart.forEach(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            count += item.qty;

            // Format Rupiah
            const formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.price);

            html += `
            <div class="bg-white p-3 rounded-2xl shadow-soft border border-gray-50 flex gap-3 relative overflow-hidden group">
                <!-- Image -->
                <div class="w-20 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
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
                            <button onclick="updateCartItemQty(${item.id}, -1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-primary">-</button>
                            <input type="text" value="${item.qty}" class="w-8 h-6 text-center text-xs bg-transparent border-none p-0 focus:ring-0" readonly>
                            <button onclick="updateCartItemQty(${item.id}, 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-primary">+</button>
                        </div>
                    </div>
                </div>

                <!-- Delete Button (Top Right) -->
                <button onclick="removeCartItem(${item.id})" class="absolute top-2 right-2 text-gray-300 hover:text-red-500 p-1">
                    <i class="ph-bold ph-trash"></i>
                </button>
            </div>`;
        });

        $container.html(html);
        
        // Update Total di Checkout Bar
        $('.fixed.bottom-0 .text-lg.font-bold').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total));
        $('.fixed.bottom-0 a').text(`Checkout (${count})`);
    }

    // Jalankan render saat load jika di halaman cart
    if ($('#cart-items-container').length) {
        renderCartPage();
    }

    /* =========================================
       4. CHECKOUT PAGE LOGIC
       ========================================= */
    if ($('#checkout-items-container').length) {
        const cart = getCart();
        const $container = $('#checkout-items-container');
        const $subtotal = $('#checkout-subtotal');
        const $total = $('#checkout-total');
        const SERVICE_FEE = 2000;

        // Redirect jika keranjang kosong
        if (cart.length === 0) {
            window.location.href = dwData.home_url + 'keranjang';
        }

        let total = 0;
        let html = '';
        
        // Render ringkasan item di halaman checkout
        cart.forEach(item => {
            total += (item.price * item.qty);
            const formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.price * item.qty);
            
            html += `
            <div class="flex justify-between items-center text-sm border-b border-gray-50 last:border-0 pb-2 last:pb-0">
                <div class="flex items-center gap-3">
                    <img src="${item.thumb}" class="w-10 h-10 rounded object-cover border border-gray-200">
                    <div>
                        <div class="font-medium text-gray-800 line-clamp-1 w-40">${item.title}</div>
                        <div class="text-gray-500 text-xs">x${item.qty}</div>
                    </div>
                </div>
                <div class="font-medium">${formattedPrice}</div>
            </div>`;
        });

        $container.html(html);
        if ($subtotal.length) $subtotal.text('Rp ' + total.toLocaleString('id-ID'));
        if ($total.length) $total.text('Rp ' + (total + SERVICE_FEE).toLocaleString('id-ID'));

        // Handle Form Submit (Buat Pesanan)
        $('#checkout-form').on('submit', function(e) {
            e.preventDefault();
            
            const $btn = $('#btn-place-order');
            const $loader = $('#checkout-loader');
            
            $btn.prop('disabled', true);
            $loader.removeClass('hidden');

            // Ambil data form
            const formData = $(this).serializeArray();
            const addressData = {};
            formData.forEach(field => { addressData[field.name] = field.value });

            // Format data keranjang sesuai spesifikasi API Plugin
            const cartPayload = cart.map(item => ({
                product_id: item.id,
                qty: item.qty,
                note: '' // Optional note per item
            }));

            // Kirim ke API: /dw/v1/pembeli/orders
            $.ajax({
                type: 'POST',
                url: dwData.api_url + 'pembeli/orders',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('dw_jwt_token') // Wajib ada token
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    cart_items: cartPayload,
                    shipping_address_id: 0, // 0 jika pakai alamat manual
                    manual_address: addressData, // Kirim data alamat manual
                    seller_shipping_choices: {}, // Jika ada fitur ongkir, ini harus diisi
                    payment_method: 'manual_transfer'
                }),
                success: function(response) {
                    // Sukses
                    localStorage.removeItem(CART_KEY); // Hapus keranjang
                    $('#order-success-modal').removeClass('hidden'); // Tampilkan modal sukses
                },
                error: function(xhr) {
                    console.error('Checkout Error:', xhr);
                    
                    // Fallback simulasi (Untuk demo jika API belum 100% siap menerima struktur ini)
                    if (xhr.status === 404 || xhr.status === 500) {
                        alert('Mode Simulasi: Order berhasil dibuat! (Data API belum sinkron sepenuhnya)');
                        localStorage.removeItem(CART_KEY);
                        window.location.href = dwData.home_url + 'dashboard-toko';
                        return;
                    }

                    let msg = 'Gagal membuat pesanan.';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    alert(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $loader.addClass('hidden');
                }
            });
        });
    }

    /* =========================================
       5. GENERAL UI (Mobile Menu, Tabs, Carousel)
       ========================================= */
    $('#mobile-menu-btn').on('click', function() {
        $('#mobile-menu').slideToggle();
    });

    // Product Tabs
    $('.product-tabs .tab-nav a').on('click', function(e) {
        e.preventDefault();
        $('.product-tabs .tab-nav a').removeClass('active');
        $('.product-tabs .tab-pane').removeClass('active');
        $(this).addClass('active');
        var targetId = $(this).attr('href');
        $(targetId).addClass('active');
    });

    /* =========================================
       6. LOGIN FORM HANDLER (Login Page)
       ========================================= */
    $('#dw-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var username = $('#username').val();
        var password = $('#password').val();
        var $btn = $('#btn-submit');
        var $btnText = $('#btn-text');
        var $loader = $('#btn-loader');
        var $alert = $('#login-alert');

        // UI Loading State
        $btn.prop('disabled', true);
        $btnText.text('Memproses...');
        $loader.removeClass('hidden');
        $alert.addClass('hidden').removeClass('bg-red-500 bg-green-500');

        // URL Endpoint Login
        var loginEndpoint = dwData.api_url + 'auth/login';

        // LANGKAH 1: Login ke API (Dapatkan Token JWT)
        $.ajax({
            type: 'POST',
            url: loginEndpoint, 
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            success: function(response) {
                // Token API didapat
                localStorage.setItem('dw_jwt_token', response.token);

                // LANGKAH 2: Login ke Sesi WordPress (Set Cookie Auth)
                $.ajax({
                    type: 'POST',
                    url: dwData.ajax_url,
                    data: {
                        action: 'tema_dw_ajax_login', // Action name di functions.php
                        username: username,
                        password: password,
                        security: dwData.nonce
                    },
                    success: function(wpResponse) {
                        if ( wpResponse.success ) {
                            // KEDUANYA SUKSES -> Redirect
                            $alert.text('Login berhasil! Mengalihkan...').addClass('bg-green-500 block').removeClass('hidden');
                            window.location.href = wpResponse.data.redirect_url || (dwData.home_url + 'dashboard-toko');
                        } else {
                            handleError('Sesi WP Gagal: ' + (wpResponse.data.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        handleError('Terjadi kesalahan koneksi ke server WordPress.');
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('API Login Error:', xhr);
                
                var msg = 'Login gagal.';
                if (xhr.responseJSON && xhr.responseJSON.code === 'rest_no_route') {
                    msg = '<strong>Error Rute API:</strong> Jalur API tidak ditemukan. Mohon Admin masuk ke Settings > Permalinks dan klik "Save Changes".';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    msg = 'Error 404: Endpoint API tidak ditemukan (' + loginEndpoint + '). Pastikan Plugin Desa Wisata Core aktif.';
                }

                handleError(msg);
            }
        });

        // Helper UI Error Login
        function handleError(message) {
            $alert.html(message).addClass('bg-red-500 block').removeClass('hidden');
            $btn.prop('disabled', false);
            $btnText.text('Masuk');
            $loader.addClass('hidden');
        }
    });

    /* =========================================
       7. REGISTER HANDLER (UPDATED)
       ========================================= */
    $('#dw-register-form').on('submit', function(e) {
        e.preventDefault();
        
        var fullname = $('#fullname').val();
        var username = $('#reg_username').val();
        var email    = $('#email').val();
        var no_hp    = $('#no_hp').val();
        var password = $('#reg_password').val();
        var role     = $('#role-input').val(); // Ambil role (pembeli/pedagang)
        var nama_toko = $('#nama_toko').val(); // Ambil nama toko (jika pedagang)
        
        var $btn = $('#btn-reg-submit');
        var $btnText = $('#btn-reg-text');
        var $loader = $('#btn-reg-loader');
        var $alert = $('#register-alert');

        // UI Loading
        $btn.prop('disabled', true);
        $btnText.text('Mendaftarkan...');
        $loader.removeClass('hidden');
        $alert.addClass('hidden').removeClass('bg-red-50 text-red-700 bg-green-50 text-green-700');

        // Payload Data
        var payload = {
            username: username,
            email: email,
            password: password,
            fullname: fullname,
            no_hp: no_hp,
            role: role
        };

        // Jika pedagang, tambahkan nama toko
        if (role === 'pedagang') {
            payload.nama_toko = nama_toko;
        }

        // Step 1: Register via API Plugin
        $.ajax({
            type: 'POST',
            url: dwData.api_url + 'auth/register',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(response) {
                // Register Sukses
                $alert.html('<i class="ph-bold ph-check-circle text-lg"></i> Pendaftaran berhasil! Mengalihkan...').addClass('bg-green-50 text-green-700 flex').removeClass('hidden');
                
                if(response.token) {
                    localStorage.setItem('dw_jwt_token', response.token);
                }

                // Step 2: Auto Login
                $.ajax({
                    type: 'POST',
                    url: dwData.ajax_url,
                    data: {
                        action: 'tema_dw_ajax_login',
                        username: username,
                        password: password,
                        security: dwData.nonce
                    },
                    success: function(wpRes) {
                        // Redirect Berdasarkan Role
                        if (role === 'pedagang') {
                            window.location.href = dwData.home_url + 'dashboard-toko';
                        } else {
                            window.location.href = dwData.home_url + 'akun-saya';
                        }
                    },
                    error: function() {
                        window.location.href = dwData.home_url + 'login?registered=true';
                    }
                });
            },
            error: function(xhr) {
                console.error(xhr);
                var msg = 'Registrasi gagal.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $alert.html('<i class="ph-bold ph-warning-circle text-lg"></i> ' + msg).addClass('bg-red-50 text-red-700 flex').removeClass('hidden');
                
                $btn.prop('disabled', false);
                $btnText.text('Daftar Sekarang');
                $loader.addClass('hidden');
            }
        });
    });

});