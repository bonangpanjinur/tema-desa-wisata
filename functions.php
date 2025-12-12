<?php
/**
 * Functions and definitions
 * @package TemaDesaWisata
 */

// 1. Setup Theme
function dw_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'dw_theme_setup');

// 2. Enqueue Scripts & Styles
function dw_theme_enqueue_scripts() {
    // Tailwind CSS (CDN)
    wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com', [], null, false);
    
    // Config Tailwind
    wp_add_inline_script('tailwind', "
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['\"Plus Jakarta Sans\"', 'sans-serif'] },
                    colors: {
                        primary: '#0F766E', 
                        secondary: '#F59E0B', 
                        surface: '#F8FAFC',
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    ");

    // Fonts & Icons
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap', [], null);
    wp_enqueue_script('phosphor-icons', 'https://unpkg.com/@phosphor-icons/web', [], null, false);

    // Main Styles & JS
    wp_enqueue_style('dw-style', get_stylesheet_uri(), [], '1.0.1');
    wp_enqueue_script('dw-main-js', get_template_directory_uri() . '/assets/js/main.js', ['jquery'], '1.0.1', true);
    
    // AJAX Cart Logic
    wp_enqueue_script('dw-ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', ['jquery'], '1.0.1', true);
    wp_localize_script('dw-ajax-cart', 'dw_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw-cart-nonce'),
        'home_url' => home_url()
    ]);
}
add_action('wp_enqueue_scripts', 'dw_theme_enqueue_scripts');

// 3. Helper: Format Rupiah (Moved here to ensure visibility)
if ( ! function_exists( 'dw_format_price' ) ) {
    function dw_format_price($price) {
        return 'Rp ' . number_format((float)$price, 0, ',', '.');
    }
}

// 4. Helper: Cek Apakah Halaman Auth (Login/Register)
function dw_is_auth_page() {
    return is_page('login') || is_page('register') || is_page('lupa-password');
}

// 5. Redirect User yang sudah login dari halaman Login/Register
function dw_redirect_logged_in_user() {
    if (is_user_logged_in() && dw_is_auth_page()) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('template_redirect', 'dw_redirect_logged_in_user');

// 6. Handle Login via AJAX (Untuk experience aplikasi yang mulus)
function dw_ajax_login_handler() {
    check_ajax_referer('dw-cart-nonce', 'nonce');
    
    $info = array();
    $info['user_login'] = sanitize_user($_POST['username']);
    $info['user_password'] = $_POST['password'];
    $info['remember'] = true;

    $user_signon = wp_signon($info, false);

    if (is_wp_error($user_signon)) {
        wp_send_json_error(['message' => 'Username atau password salah.']);
    } else {
        wp_send_json_success(['redirect' => home_url()]);
    }
}
add_action('wp_ajax_nopriv_dw_ajax_login', 'dw_ajax_login_handler');
?>