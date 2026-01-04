# Rencana Perbaikan Performa Tema Desa Wisata (Final)

Dokumen ini merangkum implementasi **Fase 1** dan **Fase 2** pada kode tema, serta panduan langkah demi langkah untuk melanjutkan ke **Fase 3** dan **Fase 4** yang melibatkan konfigurasi server dan plugin.

---

## Fase 1: Optimasi Aset & Script (Prioritas Tertinggi)

**Tujuan:** Mengurangi ukuran halaman (page size) dan mempercepat loading awal (Time to Interactive).

### 1.1. Backup File functions.php

**Status:** **SELESAI.** Salinan file `functions.php` yang asli telah disimpan sebagai `functions.php.bak` di direktori tema.

### 1.2. Implementasi functions-optimized.php

Fungsi `wp_enqueue_scripts` yang lama (`tema_dw_scripts`) telah diganti dengan fungsi yang dioptimasi (`tema_dw_scripts_optimized`) di `functions.php`.

**Perubahan Kunci:**
1.  **Conditional Loading:** Semua script yang berkaitan dengan dashboard (`dw-verifikator.js`, `dw-pedagang.js`, `dw-ojek.js`, `dw-checkout.js`) dan filter arsip kini hanya dimuat pada halaman yang benar-benar membutuhkannya.
2.  **Load di Footer:** Script utama tema (`tema-dw-main.js`) dan script AJAX Cart (`dw-ajax-cart.js`) dipindahkan untuk dimuat di footer (parameter `true` di `wp_enqueue_script`), sehingga tidak memblokir rendering halaman.

### 1.3. Daftarkan Ukuran Gambar Baru

Kode untuk mendaftarkan ukuran gambar baru telah ditambahkan ke fungsi `tema_dw_setup()` di `functions.php`.

```php
// functions.php - di dalam tema_dw_setup()
add_image_size( 'dw-card-thumb', 400, 300, true );
```

Ukuran ini akan digunakan untuk semua gambar pada card produk dan wisata, memastikan browser tidak perlu memuat gambar berukuran penuh untuk tampilan kecil.

---

## Fase 2: Optimasi Tampilan & Gambar

**Tujuan:** Memastikan gambar tidak memberatkan browser, terutama di perangkat seluler (HP).

### 2.1. Update Template Card

File `template-parts/card-wisata.php` dan `template-parts/card-produk.php` telah diubah untuk menggunakan fungsi `get_the_post_thumbnail()` WordPress yang sudah terintegrasi dengan ukuran gambar baru (`dw-card-thumb`) dan atribut `loading="lazy"`.

**Logika Implementasi:**
Karena data card diambil dari *custom database query* (bukan `WP_Query` standar), kami menambahkan logika untuk memeriksa apakah ID Post (`$wisata->id_post` atau `$produk->id_post`) tersedia dan memiliki *featured image*. Jika ya, maka akan menggunakan `get_the_post_thumbnail()` yang optimal. Jika tidak, akan kembali menggunakan URL gambar langsung (`<img>` tag) dengan atribut `loading="lazy"` sebagai *fallback*.

### 2.2. Regenerate Thumbnails (Tindakan Pengguna)

Langkah ini **wajib** dilakukan agar gambar-gambar lama Anda memiliki versi `dw-card-thumb` yang baru.

| Langkah | Deskripsi |
| :--- | :--- |
| **1. Instalasi Plugin** | Install plugin **"Regenerate Thumbnails"** dari WordPress Repository. |
| **2. Eksekusi** | Masuk ke menu **Tools > Regenerate Thumbnails**. |
| **3. Proses** | Klik tombol untuk memproses semua gambar. Tunggu hingga proses selesai. |

### 2.3. Update Query Homepage (Catatan Teknis)

File `front-page.php` menggunakan `$wpdb->get_results()` untuk mengambil data dari tabel kustom. Parameter optimasi `no_found_rows` dan `update_post_meta_cache` hanya berlaku untuk `WP_Query`.

**Rekomendasi:**
Jika memungkinkan, pertimbangkan untuk memindahkan data Wisata dan Produk ke Custom Post Type (CPT) standar WordPress dan menggunakan `WP_Query` dengan parameter berikut untuk optimasi:

```php
$args = array(
    'post_type' => 'dw_wisata', // atau dw_produk
    'posts_per_page' => 4,
    'orderby' => 'meta_value_num', // Jika ingin mengurutkan berdasarkan rating
    'meta_key' => 'rating_avg',
    'no_found_rows' => true, // Sangat penting untuk performa
    'update_post_meta_cache' => false, // Hindari caching meta yang tidak perlu
    'update_post_term_cache' => false, // Hindari caching term yang tidak perlu
);
$query = new WP_Query( $args );
```

---

## Fase 3: Optimasi Server & Caching (Plugin)

**Tujuan:** Mempercepat respons server (Time to First Byte/TTFB).

### 3.1. Install Plugin Cache (Tindakan Pengguna)

Pilih salah satu plugin caching yang direkomendasikan:

| Plugin | Keterangan |
| :--- | :--- |
| **WP Rocket** | Berbayar, konfigurasi termudah, hasil terbaik. |
| **LiteSpeed Cache** | Gratis, hasil sangat baik, **hanya jika server Anda menggunakan LiteSpeed Web Server.** |
| **W3 Total Cache** | Gratis, standar, konfigurasi lebih kompleks. |

**Konfigurasi Dasar yang Harus Diaktifkan:**

| Fitur | Deskripsi | Catatan Penting |
| :--- | :--- | :--- |
| **Page Caching** | Menyimpan salinan HTML halaman yang sudah jadi. | **WAJIB** diaktifkan. |
| **Minify CSS/JS** | Menggabungkan dan memperkecil ukuran file CSS dan JavaScript. | **Hati-hati:** Cek tampilan website setelah mengaktifkan. Jika ada kerusakan, matikan fitur "Combine" atau "Minify" satu per satu. |
| **Lazy Load** | Menunda pemuatan gambar dan iFrames hingga pengguna menggulir ke bawah. | Aktifkan untuk Images dan iFrames. |

### 3.2. Install Redis Object Cache (Tindakan Pengguna)

Object Caching sangat penting untuk tema yang banyak berinteraksi dengan database kustom seperti tema ini. Redis akan menyimpan hasil query database yang sering diakses, mempercepat *backend* (terutama Dashboard Desa/Toko).

| Langkah | Deskripsi |
| :--- | :--- |
| **1. Instalasi Plugin** | Install plugin **"Redis Object Cache"** dari WordPress Repository. |
| **2. Konfigurasi Server** | Pastikan server hosting Anda telah menginstal dan mengaktifkan layanan Redis. (Hubungi penyedia hosting jika tidak yakin). |
| **3. Aktivasi** | Masuk ke menu **Settings > Redis**. Klik tombol **"Enable Object Cache"**. |
| **4. Verifikasi** | Pastikan statusnya menunjukkan **"Connected"**. |

---

## Fase 4: Monitoring & Pengujian

### 4.1. Cek Error JavaScript (Tindakan Pengguna)

Setelah semua perubahan di Fase 1 dan 2, penting untuk memastikan tidak ada konflik script.

1.  Buka website Anda di browser (Chrome/Firefox).
2.  Klik kanan > **Inspect Element** > Tab **Console**.
3.  Pastikan tidak ada pesan error berwarna merah yang muncul, terutama setelah berinteraksi dengan tombol *Add to Cart* atau *Favorite*.

### 4.2. Uji Kecepatan (Tindakan Pengguna)

Gunakan alat pengujian performa untuk memvalidasi hasil optimasi.

1.  Buka **Google PageSpeed Insights** atau **GTmetrix**.
2.  Uji halaman-halaman utama:
    *   Halaman Depan (`front-page.php`)
    *   Halaman Detail Wisata/Produk (`single-dw_wisata.php`, `single-dw_produk.php`)
    *   Halaman Dashboard (jika dapat diakses publik atau melalui login test).
3.  **Target Kinerja:**
    *   Skor Mobile: **di atas 80**
    *   Skor Desktop: **di atas 90**

---

## Ringkasan Perubahan Kode

Semua perubahan kode telah diterapkan di direktori `tema-desa-wisata/`.

| File | Perubahan | Tujuan |
| :--- | :--- | :--- |
| `functions.php` | 1. Backup ke `functions.php.bak`. 2. Menambahkan `add_image_size('dw-card-thumb', 400, 300, true)`. 3. Mengganti `tema_dw_scripts` dengan `tema_dw_scripts_optimized` (conditional loading & footer load). | Optimasi aset dan script loading. |
| `template-parts/card-wisata.php` | Mengganti tag `<img>` statis dengan `get_the_post_thumbnail()` menggunakan ukuran `dw-card-thumb` dan `loading="lazy"`. | Optimasi gambar dan Lazy Load. |
| `template-parts/card-produk.php` | Mengganti tag `<img>` statis dengan `get_the_post_thumbnail()` menggunakan ukuran `dw-card-thumb` dan `loading="lazy"`. | Optimasi gambar dan Lazy Load. |
| `front-page.php` | Menambahkan komentar teknis tentang potensi optimasi `WP_Query` (meskipun saat ini menggunakan `$wpdb`). | Panduan untuk optimasi query lanjutan. |

Anda dapat meninjau perubahan ini di repositori yang telah di-clone. Silakan lanjutkan dengan Fase 2.2, Fase 3, dan Fase 4.
