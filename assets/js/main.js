jQuery(document).ready(function($) {
    
    // 1. Mobile Menu Toggle
    $('#mobile-menu-btn').on('click', function() {
        $('#mobile-menu').slideToggle();
    });

    // 2. Handling Login AJAX
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

        // LANGKAH 1: Login ke API (Dapatkan Token JWT)
        $.ajax({
            type: 'POST',
            url: dwData.api_url + 'auth/login', 
            contentType: 'application/json',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            success: function(response) {
                // Token API didapat
                localStorage.setItem('dw_jwt_token', response.token);

                // LANGKAH 2: Login ke Sesi WordPress (Set Cookie)
                $.ajax({
                    type: 'POST',
                    url: dwData.ajax_url,
                    data: {
                        action: 'tema_dw_ajax_login', 
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
                            // API Sukses, tapi WP Gagal (Jarang terjadi, tapi perlu dihandle)
                            handleError('Gagal membuat sesi WordPress. Silakan coba lagi.');
                        }
                    },
                    error: function(xhr, status, error) {
                        handleError('Terjadi kesalahan pada server saat login sesi.');
                    }
                });
            },
            error: function(xhr) {
                // Login API Gagal (Password salah / User tidak ada)
                var msg = 'Login gagal. Periksa username dan password.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                handleError(msg);
            }
        });

        // Helper untuk handle error UI
        function handleError(message) {
            $alert.text(message).addClass('bg-red-500 block').removeClass('hidden');
            $btn.prop('disabled', false);
            $btnText.text('Masuk');
            $loader.addClass('hidden');
        }
    });

    // 3. Simple Add to Cart Animation
    $('.add-to-cart-btn').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalText = btn.html();
        
        btn.html('<i class="fas fa-check"></i> Masuk Keranjang');
        btn.removeClass('bg-primary').addClass('bg-green-700');
        
        var count = parseInt($('#header-cart-count').text()) || 0;
        $('#header-cart-count').text(count + 1).removeClass('hidden');

        setTimeout(function() {
            btn.html(originalText);
            btn.removeClass('bg-green-700').addClass('bg-primary');
        }, 2000);
    });

});