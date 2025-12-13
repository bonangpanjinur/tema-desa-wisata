<?php
/**
 * Theme Functions
 */

function dw_theme_scripts() {
    // Load Main CSS
    wp_enqueue_style('dw-style', get_stylesheet_uri());
    
    // Load Tailwind CSS (CDN for Development/Prototyping)
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', [], '3.4.0', false);
    
    // Load FontAwesome
    wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', [], '6.0.0');

    // Load Main JS
    wp_enqueue_script('dw-main-js', get_template_directory_uri() . '/assets/js/main.js', ['jquery'], '1.0', true);

    // Localize Script untuk AJAX URL
    wp_localize_script('dw-main-js', 'dw_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_ajax_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'dw_theme_scripts');

// Support Theme Features
function dw_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'dw_theme_setup');

// Menambahkan custom style untuk menyembunyikan scrollbar tapi tetap bisa scroll
function dw_add_header_styles() {
    ?>
    <style>
        /* Custom scrollbar hide for horizontal scrolling */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #e5e7eb; /* Gray-200 equivalent */
        }
        /* Fix for admin bar overlap on mobile if logged in */
        .admin-bar header.sticky {
            top: 32px;
        }
        @media screen and (max-width: 782px) {
            .admin-bar header.sticky {
                top: 46px;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'dw_add_header_styles');