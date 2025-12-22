<?php
// Query Produk milik user saat ini
$args = [
    'post_type'      => 'dw_produk',
    'author'         => get_current_user_id(),
    'posts_per_page' => 10,
    'paged'          => get_query_var('paged') ? get_query_var('paged') : 1
];
$produk_query = new WP_Query($args);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Daftar Produk Anda</h4>
    <a href="?tab=produk&action=add" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Tambah Produk
    </a>
</div>

<?php if ($produk_query->have_posts()) : ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 80px;">Gambar</th>
                    <th scope="col">Nama Produk</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Stok</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($produk_query->have_posts()) : $produk_query->the_post(); 
                    $price = get_post_meta(get_the_ID(), '_dw_produk_price', true);
                    $stock = get_post_meta(get_the_ID(), '_dw_produk_stock', true);
                    $status = get_post_status();
                ?>
                <tr id="produk-row-<?php the_ID(); ?>">
                    <td>
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('thumbnail', ['class' => 'img-fluid rounded', 'style' => 'width: 60px; height: 60px; object-fit: cover;']); ?>
                        <?php else : ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 60px; height: 60px;">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php the_title(); ?></strong><br>
                        <small class="text-muted">Kategori: <?php echo get_the_term_list(get_the_ID(), 'kategori_produk', '', ', ', ''); ?></small>
                    </td>
                    <td>Rp <?php echo number_format($price, 0, ',', '.'); ?></td>
                    <td><?php echo esc_html($stock); ?></td>
                    <td>
                        <?php if($status == 'publish'): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Draft/Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="?tab=produk&action=edit&id=<?php the_ID(); ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger btn-delete-produk" data-id="<?php the_ID(); ?>" title="Hapus">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        <?php
        echo paginate_links([
            'total' => $produk_query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'type' => 'list',
            'before_page_number' => '',
            'mid_size' => 2
        ]);
        ?>
    </div>

<?php else : ?>
    <div class="text-center py-5">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/empty-box.png" alt="Kosong" class="mb-3" style="width: 100px; opacity: 0.5;">
        <p class="text-muted">Anda belum memiliki produk.</p>
        <a href="?tab=produk&action=add" class="btn btn-primary">Tambah Produk Pertama</a>
    </div>
<?php endif; wp_reset_postdata(); ?>