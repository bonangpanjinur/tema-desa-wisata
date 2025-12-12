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

        $.ajax({
            type: 'POST',
            url: dwData.api_url + 'auth/login', // API Plugin (Login JWT)
            contentType: 'application/json',
            data: JSON.stringify({
                username: username,
                password: password
            }),
            success: function(response) {
                // Login API Berhasil
                $alert.text('Login berhasil! Mengalihkan...').addClass('bg-green-500 block').removeClass('hidden');
                
                // Simpan token
                localStorage.setItem('dw_jwt_token', response.token);

                // Login Sesi WordPress (Cookie)
                $.ajax({
                    type: 'POST',
                    url: dwData.ajax_url,
                    data: {
                        action: 'tema_dw_ajax_login', // UPDATE: Action name baru agar tidak bentrok
                        username: username,
                        password: password,
                        security: dwData.nonce
                    },
                    success: function() {
                        window.location.href = dwData.home_url + 'dashboard-toko'; 
                    },
                    error: function() {
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