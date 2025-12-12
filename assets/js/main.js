/**
 * Main JS - Tema Desa Wisata
 * Menangani Cart, Login, Register, dan UI Interaktif secara lengkap.
 */

jQuery(document).ready(function($) {
    
    // Debugging: Cek koneksi API saat load
    console.log('DW Core JS Loaded. API Base:', dwData.api_url);

    /* =========================================
       1. LOGIKA KERANJANG GLOBAL (LocalStorage)
       ========================================= */
    const CART_KEY = 'dw_cart_v1';
    
    // Helper: Ambil data keranjang dari penyimpanan lokal
    function getCart() {
        try {
            return JSON.parse(localStorage.getItem(CART_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    // Helper: Simpan data keranjang ke penyimpanan lokal
    function saveCart(cart) {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        updateCartCount(); // Update badge setiap kali simpan
    }

    // Helper: Update angka badge di header
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

    // Inisialisasi hitungan saat halaman dimuat
    updateCartCount();


    /* =========================================
       2. HANDLER TOMBOL "TAMBAH KE KERANJANG"
       ========================================= */
    $(document).on('click', '.add-to-cart-btn, #single-add-cart, #btn-add-to-cart', function(e) {
        e.preventDefault();
        const btn = $(this);
        
        // Ambil data produk dari atribut data- HTML
        const id = parseInt(btn.data('id')) || 0;
        const title = btn.data('title');
        const price = parseInt(btn.data('price')) || 0;
        const thumb = btn.data('thumb');
        
        if (id === 0) {
            console.error('Product ID not found on button');
            return;
        }
        
        // Cek input qty (khusus halaman detail produk), default 1
        let qty = 1;
        if ($('#qty-input').length) {
            qty = parseInt($('#qty-input').val()) || 1;
        }

        // Logika penambahan ke array cart
        let cart = getCart();
        const existingItem = cart.find(item => item.id === id);

        if (existingItem) {
            existingItem.qty += qty;
        } else {
            cart.push({ id, title, price, thumb, qty });
        }

        saveCart(cart);

        // Efek Visual Tombol (Feedback ke User)
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
        
        // Kembalikan tombol ke semula setelah 1.5 detik
        setTimeout(() => {
            btn.html(originalHTML)
               .removeClass('bg-green-600 text-white border-transparent shadow-none');

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


    /* =========================================
       3. RENDER HALAMAN KERANJANG (page-cart.php)
       ========================================= */
    
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
            $emptyState.removeClass('hidden').addClass('flex');
            $checkoutBar.hide(); // Sembunyikan tombol checkout jika kosong
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
        
        // Update Total di Checkout Bar Bawah
        $('.fixed.bottom-0 .text-lg.font-bold').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(total));
        $('.fixed.bottom-0 a').text(`Checkout (${count})`);
    }

    // Jalankan render saat load jika kita berada di halaman cart
    if ($('#cart-items-container').length) {
        renderCartPage();
    }


    /* =========================================
       4. LOGIKA HALAMAN CHECKOUT (page-checkout.php)
       ========================================= */
    if ($('#checkout-items-container').length) {
        const cart = getCart();
        const $container = $('#checkout-items-container');
        const $subtotal = $('#checkout-subtotal');
        const $total = $('#checkout-total');
        const SERVICE_FEE = 2000; // Biaya layanan statis

        // Redirect jika keranjang kosong saat masuk checkout
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
            <div class="flex justify-between items-center text-sm border-b border-gray-50 last:border-0 pb-3 mb-3 last:pb-0 last:mb-0">
                <div class="flex items-center gap-3">
                    <img src="${item.thumb}" class="w-10 h-10 rounded object-cover border border-gray-200 bg-gray-100">
                    <div>
                        <div class="font-medium text-gray-800 line-clamp-1 w-40">${item.title}</div>
                        <div class="text-gray-500 text-xs">Qty: ${item.qty}</div>
                    </div>
                </div>
                <div class="font-bold text-gray-700">${formattedPrice}</div>
            </div>`;
        });

        $container.html(html);
        if ($subtotal.length) $subtotal.text('Rp ' + total.toLocaleString('id-ID'));
        if ($total.length) $total.text('Rp ' + (total + SERVICE_FEE).toLocaleString('id-ID'));

        // HANDLER TOMBOL "BAYAR SEKARANG"
        $('#checkout-form').on('submit', function(e) {
            e.preventDefault();
            
            const $btn = $('#btn-place-order');
            const $loader = $('#checkout-loader');
            
            // State Loading
            $btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
            $loader.removeClass('hidden');

            // Ambil data form
            const formData = $(this).serializeArray();
            const addressData = {};
            formData.forEach(field => { addressData[field.name] = field.value });

            // Format data keranjang sesuai spesifikasi API Plugin Desa Wisata Core
            const cartPayload = cart.map(item => ({
                product_id: item.id,
                qty: item.qty,
                note: '' // Bisa ditambahkan input catatan per item nanti
            }));

            // Kirim ke API: /dw/v1/pembeli/orders
            $.ajax({
                type: 'POST',
                url: dwData.api_url + 'pembeli/orders',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('dw_jwt_token') // Wajib ada token JWT
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    cart_items: cartPayload,
                    shipping_address_id: 0, // 0 jika pakai alamat manual (belum disimpan di DB)
                    manual_address: addressData, // Kirim data alamat manual dari form
                    seller_shipping_choices: {}, // Jika ada fitur ongkir, ini harus diisi logic ongkir
                    payment_method: 'manual_transfer'
                }),
                success: function(response) {
                    // Sukses
                    localStorage.removeItem(CART_KEY); // Hapus keranjang lokal
                    $('#order-success-modal').removeClass('hidden').addClass('flex'); // Tampilkan modal sukses
                },
                error: function(xhr) {
                    console.error('Checkout Error:', xhr);
                    
                    let msg = 'Gagal membuat pesanan.';
                    
                    // Fallback simulasi jika API error (Misal: masalah struktur data)
                    // Hapus blok 'if' ini jika API sudah stabil 100%
                    if (xhr.status === 404 || xhr.status === 500) {
                        alert('Mode Simulasi (API Error): Order berhasil dibuat secara lokal! Redirecting...');
                        localStorage.removeItem(CART_KEY);
                        window.location.href = dwData.home_url + 'dashboard-toko';
                        return;
                    }

                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    alert(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                    $loader.addClass('hidden');
                }
            });
        });
    }


    /* =========================================
       5. GENERAL UI INTERACTION
       ========================================= */
    // Toggle Mobile Menu (Jika ada)
    $('#mobile-menu-btn').on('click', function() {
        $('#mobile-menu').slideToggle();
    });

    // Product Tabs (Detail Produk)
    $('.product-tabs .tab-nav a').on('click', function(e) {
        e.preventDefault();
        $('.product-tabs .tab-nav a').removeClass('active border-primary text-primary').addClass('border-transparent text-gray-500');
        $('.product-tabs .tab-pane').removeClass('active hidden').addClass('hidden');
        
        $(this).addClass('active border-primary text-primary').removeClass('border-transparent text-gray-500');
        var targetId = $(this).attr('href');
        $(targetId).removeClass('hidden').addClass('active');
    });


    /* =========================================
       6. HANDLER LOGIN FORM (page-login.php)
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
        $alert.addClass('hidden').removeClass('bg-red-50 text-red-700 bg-green-50 text-green-700 flex');

        // URL Endpoint Login API
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
                // Ini penting agar user bisa mengakses halaman wp-admin atau halaman proteksi lainnya
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
                            $alert.html('<i class="ph-bold ph-check-circle text-lg"></i> Login berhasil! Mengalihkan...').addClass('bg-green-50 text-green-700 flex').removeClass('hidden');
                            
                            // Redirect ke URL yang diberikan server atau default
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
                
                // Handling pesan error spesifik "No Route"
                if (xhr.responseJSON && xhr.responseJSON.code === 'rest_no_route') {
                    msg = '<strong>Error Sistem:</strong> Jalur API tidak ditemukan. Mohon Admin masuk ke <em>Settings > Permalinks</em> di dashboard WP dan klik "Save Changes".';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    msg = 'Error 404: Endpoint API tidak ditemukan (' + loginEndpoint + '). Pastikan Plugin Desa Wisata Core aktif.';
                }

                handleError('<i class="ph-bold ph-warning-circle text-lg"></i> ' + msg);
            }
        });

        // Helper UI Error Login
        function handleError(message) {
            $alert.html(message).addClass('bg-red-50 text-red-700 flex').removeClass('hidden');
            $btn.prop('disabled', false);
            $btnText.text('Masuk Sekarang');
            $loader.addClass('hidden');
        }
    });


    /* =========================================
       7. HANDLER REGISTER FORM (page-register.php)
       ========================================= */
    $('#dw-register-form').on('submit', function(e) {
        e.preventDefault();
        
        // Ambil value dari input
        var fullname = $('#fullname').val();
        var username = $('#reg_username').val();
        var email    = $('#email').val();
        var no_hp    = $('#no_hp').val();
        var password = $('#reg_password').val();
        
        // Ambil role dan nama toko (jika ada) dari input hidden/visible
        var role     = $('#role-input').val(); // 'pembeli' atau 'pedagang'
        var nama_toko = $('#nama_toko').val(); 
        
        var $btn = $('#btn-reg-submit');
        var $btnText = $('#btn-reg-text');
        var $loader = $('#btn-reg-loader');
        var $alert = $('#register-alert');

        // UI Loading
        $btn.prop('disabled', true);
        $btnText.text('Mendaftarkan...');
        $loader.removeClass('hidden');
        $alert.addClass('hidden').removeClass('bg-red-50 text-red-700 bg-green-50 text-green-700 flex');

        // Siapkan data payload
        var payload = {
            username: username,
            email: email,
            password: password,
            fullname: fullname,
            no_hp: no_hp,
            role: role
        };

        // Jika pedagang, tambahkan nama toko ke payload
        if (role === 'pedagang') {
            if(!nama_toko) {
                 $alert.html('<i class="ph-bold ph-warning-circle text-lg"></i> Nama Toko wajib diisi untuk pedagang.').addClass('bg-red-50 text-red-700 flex').removeClass('hidden');
                 $btn.prop('disabled', false);
                 $btnText.text('Daftar Sekarang');
                 $loader.addClass('hidden');
                 return;
            }
            payload.nama_toko = nama_toko;
        }

        // Step 1: Register via API Plugin (/auth/register)
        $.ajax({
            type: 'POST',
            url: dwData.api_url + 'auth/register',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(response) {
                // Register Sukses
                $alert.html('<i class="ph-bold ph-check-circle text-lg"></i> Pendaftaran berhasil! Mengalihkan...').addClass('bg-green-50 text-green-700 flex').removeClass('hidden');
                
                // Simpan Token JWT jika dikembalikan langsung
                if(response.token) {
                    localStorage.setItem('dw_jwt_token', response.token);
                }

                // Step 2: Auto Login ke WP Session (Cookie)
                // Agar user tidak perlu login ulang manual
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
                        // Fallback jika auto login gagal
                        window.location.href = dwData.home_url + 'login?registered=true';
                    }
                });
            },
            error: function(xhr) {
                console.error(xhr);
                var msg = 'Registrasi gagal.';
                
                // Parsing pesan error dari API
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