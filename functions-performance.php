<?php
/**
 * Advanced Performance Optimizations for Tema Desa Wisata
 */

if (!defined('ABSPATH')) exit;

/**
 * 1. CLEAN UP WP HEADER
 * Menghapus script dan tag yang tidak diperlukan di header untuk mengurangi HTTP requests.
 */
function tema_dw_cleanup_header() {
    // Hapus Emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    
    // Hapus link generator, wlwmanifest, rsd
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Hapus REST API link dari header (karena user tidak menggunakan REST API)
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
}
add_action('init', 'tema_dw_cleanup_header');

/**
 * 2. PRECONNECT & DNS PREFETCH
 * Mempercepat koneksi ke domain eksternal (Google Fonts, CDN).
 */
function tema_dw_resource_hints($urls, $relation_type) {
    if ('preconnect' === $relation_type) {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = 'https://fonts.gstatic.com';
        $urls[] = 'https://cdnjs.cloudflare.com';
        $urls[] = 'https://cdn.jsdelivr.net';
    }
    return $urls;
}
add_filter('wp_resource_hints', 'tema_dw_resource_hints', 10, 2);

/**
 * 3. DEFER SCRIPTS
 * Menambahkan atribut 'defer' pada script non-kritis agar tidak memblokir rendering.
 */
function tema_dw_defer_scripts($tag, $handle, $src) {
    // Daftar script yang akan di-defer
    $defer_scripts = [
        'tema-dw-main',
        'tema-dw-filter',
        'dw-verifikator',
        'dw-pedagang',
        'dw-ojek',
        'dw-checkout',
        'dw-ajax-cart',
        'sweetalert2',
        'font-awesome'
    ];

    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'tema_dw_defer_scripts', 10, 3);

/**
 * 4. OPTIMASI LOADING GOOGLE FONTS
 * Menggunakan &display=swap untuk menghindari FOIT.
 */
function tema_dw_optimized_fonts($tag, $handle, $src) {
    if ('google-fonts' === $handle) {
        return str_replace("href='", "rel='stylesheet' preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\" href='", $tag);
    }
    return $tag;
}
// add_filter('style_loader_tag', 'tema_dw_optimized_fonts', 10, 3);

/**
 * 5. DISABLE XML-RPC
 * Mengurangi beban server dan meningkatkan keamanan.
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * 6. FRAGMENT CACHING (Placeholder)
 * Untuk bagian yang berat seperti sidebar atau footer.
 */
function tema_dw_get_cached_part($slug, $name = null) {
    $cache_key = 'dw_part_' . $slug . '_' . $name;
    $output = get_transient($cache_key);
    
    if (false === $output) {
        ob_start();
        get_template_part($slug, $name);
        $output = ob_get_clean();
        set_transient($cache_key, $output, HOUR_IN_SECONDS);
    }
    echo $output;
}
