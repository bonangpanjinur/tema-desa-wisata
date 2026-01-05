<?php
/**
 * Template Name: Offline Page
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anda Sedang Offline - Desa Wisata</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; color: #333; background-color: #f8fafc; }
        h1 { font-size: 24px; margin-bottom: 20px; color: #2c3e50; }
        p { font-size: 16px; color: #666; line-height: 1.6; }
        .btn { display: inline-block; padding: 12px 24px; background: #16a34a; color: #fff; text-decoration: none; border-radius: 50px; margin-top: 20px; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #15803d; }
        .icon { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="icon">📡</div>
    <h1>Koneksi Terputus</h1>
    <p>Sepertinya Anda tidak terhubung ke internet.</p>
    <p>Halaman yang Anda minta tidak tersedia secara offline, namun Anda masih bisa mengakses halaman yang pernah Anda buka sebelumnya.</p>
    <a href="<?php echo home_url(); ?>" class="btn">Coba Muat Ulang</a>
</body>
</html>
