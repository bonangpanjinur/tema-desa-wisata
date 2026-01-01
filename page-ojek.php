<?php
/**
 * Template Name: Halaman Ojek Desa
 * * Halaman sementara - Fitur sedang dikembangkan
 */

get_header(); 
?>

<div class="maintenance-wrapper">
    <div class="maintenance-card">
        <!-- Icon Motor (SVG) -->
        <div class="icon-container">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" fill="currentColor">
                <!-- FontAwesome Motorcycle Icon -->
                <path d="M280 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V64H128c-35.3 0-64 28.7-64 64v96c0 17.7 14.3 32 32 32H272c17.7 0 32-14.3 32-32V32zM0 432c0 44.2 35.8 80 80 80s80-35.8 80-80s-35.8-80-80-80S0 387.8 0 432zM48 432c0-17.7 14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32s-32-14.3-32-32zm392-80c-44.2 0-80 35.8-80 80s35.8 80 80 80s80-35.8 80-80s-35.8-80-80-80zm0 112c-17.7 0-32-14.3-32-32s14.3-32 32-32s32 14.3 32 32s-14.3 32-32 32zM378 170c-10.2-12-28.1-13.8-40.4-4.1l-68.9 54.5c-4 3.2-9.4 3.8-14 1.7l-70.6-32.9c-12.8-6-28 1.4-31 15.3s3.3 28.2 17 32l52.1 14.5c11.6 3.2 24.1 1.7 34.6-4.2l20.4-11.4L332 284c7.9 4.3 17.5 2.5 23.4-4.5l68.1-80.1 57.3 25.4c14.2 6.3 30.8 .9 38.3-12.5s2.7-30.9-10.9-38.3l-81-35.9c-17-7.5-36.8-4.4-50.6 7.9L378 170zM576 400c25.4 0 47.9 14.3 60 35.9c7-20 10.9-41.5 10.9-63.9c0-67.6-36.6-126.9-91.3-159.2c-10.1-6-23.2-2.3-28.7 8.2s-1.8 23.8 8.4 29.5C575.4 272.2 600 309.8 600 352c0 14.4-2.7 28.2-7.5 41.1c-2.3-2.9-4.8-5.7-7.6-8.3c-27.4-25.7-63.5-41.5-103.3-42.3c-1.3-.1-2.5-.1-3.8-.1c-12 0-23.4-1-34.5-2.9l-26.1-4.5c-15.1-2.6-29.6 7.4-32.2 22.5s7.4 29.6 22.5 32.2l25.6 4.4c13.7 2.4 28 3.5 42.6 3.4c5.1 .1 10.1 1.5 14.7 4c2.1 1.2 4.1 2.5 6 3.9c13.2 9.6 23.4 23.4 28.4 39.7c.4 1.2 .7 2.4 1 3.6c2.4 9.1 11.9 14.4 21 12s14.4-11.9 12-21c-5.7-21.7-18.4-40.4-35.2-53.5c16.2 5.1 33.6 8.2 52 8.5z"/>
            </svg>
        </div>
        
        <h2 class="maintenance-title">Fitur ini sedang dikembangkan</h2>
        <p class="maintenance-desc">
            Layanan Ojek Desa akan segera hadir untuk memudahkan mobilitas wisatawan dan warga. 
            Silakan cek kembali nanti.
        </p>

        <a href="<?php echo home_url(); ?>" class="btn-back">
            &larr; Kembali ke Beranda
        </a>
    </div>
</div>

<style>
    /* Inline CSS untuk halaman maintenance ini saja */
    .maintenance-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 70vh; /* Mengambil sebagian besar tinggi layar */
        background-color: #f4f6f9;
        padding: 20px;
    }

    .maintenance-card {
        background: white;
        padding: 50px 30px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        text-align: center;
        max-width: 500px;
        width: 100%;
    }

    .icon-container {
        color: #1976D2; /* Warna biru tema */
        width: 100px;
        margin: 0 auto 25px;
        opacity: 0.9;
    }

    .maintenance-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 15px;
    }

    .maintenance-desc {
        color: #7f8c8d;
        line-height: 1.6;
        margin-bottom: 35px;
        font-size: 1rem;
    }

    .btn-back {
        display: inline-block;
        padding: 12px 30px;
        background-color: #1976D2;
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(25, 118, 210, 0.2);
    }

    .btn-back:hover {
        background-color: #1565c0;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(25, 118, 210, 0.3);
        color: white;
    }

    /* Responsive adjustment */
    @media (max-width: 576px) {
        .maintenance-card {
            padding: 30px 20px;
        }
        .maintenance-title {
            font-size: 1.5rem;
        }
    }
</style>

<?php get_footer(); ?>