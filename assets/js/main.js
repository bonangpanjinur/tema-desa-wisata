jQuery(document).ready(function($) {
    
    // 1. Mobile Menu Toggle
    $('#mobile-menu-btn').on('click', function() {
        $('#mobile-menu').slideToggle();
    });

    // 2. Handling Login AJAX (Untuk page-login.php)
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

        $.ajax({
            type: 'POST',
            url: dwData.api_url + 'auth/login', // Menggunakan endpoint API plugin
            contentType: 'application/json',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            success: function(response) {
                // Login Berhasil di level API (Dapat Token)
                $alert.text('Login berhasil! Mengalihkan...').addClass('bg-green-500 block').removeClass('hidden');
                
                // Simpan token (opsional, tergantung kebutuhan API selanjutnya)
                localStorage.setItem('dw_jwt_token', response.token);

                // KITA PERLU LOGIN JUGA KE WORDPRESS SESSION (COOKIE)
                // Karena ini theme WP, bukan pure headless app.
                // Kita kirim kredensial ke admin-ajax WP untuk set cookie auth.
                $.ajax({
                    type: 'POST',
                    url: dwData.ajax_url,
                    data: {
                        action: 'dw_ajax_login', // Perlu dibuat handler ini di functions.php nanti jika belum ada
                        username: username,
                        password: password,
                        security: dwData.nonce // Sebaiknya gunakan nonce khusus login
                    },
                    success: function() {
                        window.location.href = dwData.home_url + 'dashboard-toko'; // Redirect default
                    },
                    error: function() {
                        // Fallback jika ajax login wp gagal tapi api berhasil (Jarang terjadi)
                         window.location.href = dwData.home_url;
                    }
                });
            },
            error: function(xhr) {
                var msg = 'Login gagal. Periksa username dan password.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $alert.text(msg).addClass('bg-red-500 block').removeClass('hidden');
                
                // Reset UI
                $btn.prop('disabled', false);
                $btnText.text('Masuk');
                $loader.addClass('hidden');
            }
        });
    });

    // 3. Simple Add to Cart Animation (Visual Feedback)
    $('.add-to-cart-btn').on('click', function(e) {
        e.preventDefault();
        // Disini nanti logika AJAX add to cart
        var btn = $(this);
        var originalText = btn.html();
        
        btn.html('<i class="fas fa-check"></i> Masuk Keranjang');
        btn.removeClass('bg-primary').addClass('bg-green-700');
        
        // Update badge count simulasi
        var count = parseInt($('#header-cart-count').text()) || 0;
        $('#header-cart-count').text(count + 1).removeClass('hidden');

        setTimeout(function() {
            btn.html(originalText);
            btn.removeClass('bg-green-700').addClass('bg-primary');
        }, 2000);
    });

});