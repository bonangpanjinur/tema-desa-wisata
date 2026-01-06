<?php
/**
 * Extension: Point of Sale (POS) for Pedagang
 * Handles direct transaction insertion for offline sales.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_dw_ajax_pos_transaction', 'dw_ajax_pos_transaction_handler');

function dw_ajax_pos_transaction_handler() {
    global $wpdb;

    // 1. Security Check
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Sesi berakhir, silakan login kembali.']);
    }

    $current_user_id = get_current_user_id();
    
    // Get Pedagang Data
    $table_pedagang = $wpdb->prefix . 'dw_pedagang';
    $pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user_id));

    if (!$pedagang) {
        wp_send_json_error(['message' => 'Akses ditolak. Anda bukan pedagang terdaftar.']);
    }

    // 2. Get Data from Request
    $items         = isset($_POST['items']) ? $_POST['items'] : [];
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $table_no      = sanitize_text_field($_POST['table_no']);
    $payment_method = sanitize_text_field($_POST['payment_method']);
    $total_amount  = floatval($_POST['total_amount']);

    if (empty($items)) {
        wp_send_json_error(['message' => 'Keranjang kosong.']);
    }

    // 3. Handle Guest User
    $guest_user = get_user_by('login', 'guest_pos');
    if (!$guest_user) {
        $random_password = wp_generate_password();
        $guest_id = wp_create_user('guest_pos', $random_password, 'guest@pos.local');
        if (is_wp_error($guest_id)) {
            wp_send_json_error(['message' => 'Gagal membuat user guest: ' . $guest_id->get_error_message()]);
        }
        // Optional: Add to dw_pembeli if needed by your schema
    } else {
        $guest_id = $guest_user->ID;
    }

    // 4. Start Transaction Insertion
    $table_transaksi     = $wpdb->prefix . 'dw_transaksi';
    $table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub';
    $table_items         = $wpdb->prefix . 'dw_transaksi_items';
    $table_produk        = $wpdb->prefix . 'dw_produk';

    $timestamp = time();
    $kode_unik = 'POS-' . $timestamp . '-' . strtoupper(wp_generate_password(4, false));

    // A. Insert dw_transaksi
    $insert_main = $wpdb->insert($table_transaksi, [
        'kode_unik'          => $kode_unik,
        'id_pembeli'         => $guest_id,
        'nama_penerima'      => $customer_name ?: 'Pelanggan Offline',
        'alamat_lengkap'     => $table_no ? 'Dine In - ' . $table_no : 'Take Away / Offline',
        'metode_pembayaran'  => $payment_method ?: 'Tunai',
        'status_transaksi'   => 'selesai',
        'total_transaksi'    => $total_amount,
        'tanggal_transaksi'  => current_time('mysql'),
        'tanggal_pembayaran' => current_time('mysql'),
    ]);

    if (!$insert_main) {
        wp_send_json_error(['message' => 'Gagal menyimpan header transaksi.']);
    }

    $transaksi_id = $wpdb->insert_id;

    // B. Insert dw_transaksi_sub
    $insert_sub = $wpdb->insert($table_transaksi_sub, [
        'id_transaksi'        => $transaksi_id,
        'id_pedagang'         => $pedagang->id,
        'status_pesanan'      => 'selesai',
        'kurir_nama'          => $table_no ? 'DINE_IN' : 'OFFLINE',
        'total_pesanan_toko'  => $total_amount,
        'sub_total'           => $total_amount,
    ]);

    if (!$insert_sub) {
        wp_send_json_error(['message' => 'Gagal menyimpan sub-transaksi.']);
    }

    $sub_id = $wpdb->insert_id;

    // C. Insert Items & Update Stock
    foreach ($items as $item) {
        $product_id = intval($item['id']);
        $qty        = intval($item['qty']);
        $price      = floatval($item['harga']);

        $wpdb->insert($table_items, [
            'id_sub_transaksi' => $sub_id,
            'id_produk'        => $product_id,
            'harga_satuan'     => $price,
            'jumlah'           => $qty,
            'total_harga'      => $price * $qty,
        ]);

        // Update Stock
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_produk SET stok = stok - %d WHERE id = %d",
            $qty, $product_id
        ));
    }

    // D. Reduce Merchant Quota
    $wpdb->query($wpdb->prepare(
        "UPDATE $table_pedagang SET sisa_transaksi = sisa_transaksi - 1 WHERE id = %d",
        $pedagang->id
    ));

    wp_send_json_success([
        'message' => 'Transaksi berhasil disimpan!',
        'kode_unik' => $kode_unik
    ]);
}
