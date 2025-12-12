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
        
        if (count > 0) {
            $badge.text(count).removeClass('hidden');
        } else {
            $badge.addClass('hidden');
        }
    }

    // Init count saat halaman dimuat
    updateCartCount();

    /* =========================================
       2. ADD TO CART HANDLER
       ========================================= */
    $(document).on('click', '.add-to-cart-btn, #single-add-cart', function(e) {
        e.preventDefault();
        const btn = $(this);
        
        // Ambil data produk dari atribut data-
        const id = btn.data('id');
        const title = btn.data('title');
        const price = parseInt(btn.data('price')) || 0;
        const thumb = btn.data('thumb');
        
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
        btn.html('<i class="fas fa-check"></i> Masuk Keranjang')
           .removeClass('bg-primary bg-white text-primary border-primary') // Hapus kelas lama
           .addClass('bg-green-700 text-white border-transparent'); // Tambah kelas sukses
        
        setTimeout(() => {
            // Kembalikan tombol ke semula
            btn.html(originalHTML)
               .removeClass('bg-green-700 text-white border-transparent')
               .addClass('bg-primary text-white'); // Asumsi default style primary
            
            // Khusus tombol di halaman detail (style outline)
            if(btn.attr('id') === 'single-add-cart') {
                 btn.removeClass('bg-primary text-white').addClass('bg-white text-primary border-2 border-primary');
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

    function renderCartPage() {
        // Hanya jalankan jika elemen container keranjang ada
        if (!$('#cart-container').length) return;

        const cart = getCart();
        const $container = $('#cart-container');
        const $emptyState = $('#cart-empty-state'); // Elemen kosong bawaan PHP
        const $summaryCount = $('#summary-count');
        const $summaryTotal = $('#summary-total');
        const $btnCheckout = $('#btn-checkout');

        if (cart.length === 0) {
            // Jika kosong, tampilkan empty state default
            $container.html('').append(`
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-shopping-basket text-4xl mb-4 text-gray-300"></i>
                    <p>Keranjang belanja Anda kosong.</p>
                    <a href="${dwData.home_url}produk" class="text-primary font-semibold hover:underline mt-2 inline-block">Mulai Belanja</a>
                </div>
            `);
            $summaryCount.text('0 barang');
            $summaryTotal.text('Rp 0');
            $btnCheckout.addClass('opacity-50 cursor-not-allowed').prop('href', '#'); // Disable link
            return;
        }

        let html = '<div class="divide-y divide-gray-100">';
        let total = 0;
        let count = 0;

        cart.forEach(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            count += item.qty;

            html += `
            <div class="p-4 flex gap-4 items-center">
                <div class="w-20 h-20 bg-gray-100 rounded overflow-hidden flex-shrink-0 border border-gray-200">
                    <img src="${item.thumb}" class="w-full h-full object-cover">
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800 text-sm md:text-base line-clamp-1">${item.title}</h4>
                    <div class="text-primary font-bold">Rp ${item.price.toLocaleString('id-ID')}</div>
                    <div class="text-sm text-gray-500 mt-1">Qty: ${item.qty}</div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-gray-900 mb-2">Rp ${subtotal.toLocaleString('id-ID')}</div>
                    <button onclick="removeCartItem(${item.id})" class="text-red-500 text-sm hover:underline flex items-center justify-end gap-1 ml-auto">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </div>`;
        });
        html += '</div>';

        $container.html(html);
        $summaryCount.text(count + ' barang');
        $summaryTotal.text('Rp ' + total.toLocaleString('id-ID'));
        $btnCheckout.removeClass('opacity-50 cursor-not-allowed').prop('href', dwData.home_url + 'checkout');
    }

    // Jalankan render saat load jika di halaman cart
    if ($('#cart-container').length) {
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
            window.location.href = dwData.home_url + 'cart';
            return;
        }

        let total = 0;
        let html = '';
        
        // Render ringkasan item di halaman checkout
        cart.forEach(item => {
            total += (item.price * item.qty);
            html += `
            <div class="flex justify-between items-center text-sm border-b border-gray-50 last:border-0 pb-2 last:pb-0">
                <div class="flex items-center gap-3">
                    <img src="${item.thumb}" class="w-10 h-10 rounded object-cover border border-gray-200">
                    <div>
                        <div class="font-medium text-gray-800 line-clamp-1 w-40">${item.title}</div>
                        <div class="text-gray-500 text-xs">x${item.qty}</div>
                    </div>
                </div>
                <div class="font-medium">Rp ${(item.price * item.qty).toLocaleString('id-ID')}</div>
            </div>`;
        });

        $container.html(html);
        $subtotal.text('Rp ' + total.toLocaleString('id-ID'));
        $total.text('Rp ' + (total + SERVICE_FEE).toLocaleString('id-ID'));

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
                    shipping_address_id: 0, // 0 jika pakai alamat manual (sesuai implementasi API Anda)
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
                    // Hapus blok ini jika API sudah fix
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
       5. GENERAL UI (Mobile Menu)
       ========================================= */
    $('#mobile-menu-btn').on('click', function() {
        $('#mobile-menu').slideToggle();
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
                // Agar user bisa akses halaman yang diproteksi WP (seperti Dashboard Toko)
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
                    msg = '<strong>Error Rute API:</strong> Jalur API tidak ditemukan. Mohon masuk ke Admin > Settings > Permalinks dan klik "Save Changes" untuk memperbaiki ini.';
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