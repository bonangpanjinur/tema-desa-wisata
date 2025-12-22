<?php
$is_edit = (isset($_GET['action']) && $_GET['action'] == 'edit');
$produk_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$judul = '';
$deskripsi = '';
$harga = '';
$stok = '';
$kategori_selected = [];
$gambar_url = '';

// Jika Edit, ambil data lama
if ($is_edit && $produk_id > 0) {
    $post = get_post($produk_id);
    // Pastikan milik user sendiri
    if ($post->post_author != get_current_user_id()) {
        echo '<div class="alert alert-danger">Anda tidak memiliki akses ke produk ini.</div>';
        return;
    }
    
    $judul = $post->post_title;
    $deskripsi = $post->post_content;
    $harga = get_post_meta($produk_id, '_dw_produk_price', true);
    $stok = get_post_meta($produk_id, '_dw_produk_stock', true);
    $kategori_terms = wp_get_post_terms($produk_id, 'kategori_produk', ['fields' => 'ids']);
    $kategori_selected = !is_wp_error($kategori_terms) ? $kategori_terms : [];
    $gambar_url = get_the_post_thumbnail_url($produk_id, 'thumbnail');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?php echo $is_edit ? 'Edit Produk' : 'Tambah Produk Baru'; ?></h4>
    <a href="?tab=produk" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<form id="form-produk-pedagang" class="needs-validation" novalidate>
    <input type="hidden" name="produk_id" value="<?php echo esc_attr($produk_id); ?>">
    <input type="hidden" name="action_type" value="<?php echo $is_edit ? 'update' : 'create'; ?>">

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="judul" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" value="<?php echo esc_attr($judul); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5"><?php echo esc_textarea($deskripsi); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Data Produk</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga" name="harga" value="<?php echo esc_attr($harga); ?>" required min="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok" name="stok" value="<?php echo esc_attr($stok); ?>" required min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">Gambar Produk</div>
                <div class="card-body text-center">
                    <div id="image-preview" class="mb-3 border rounded bg-light d-flex align-items-center justify-content-center" style="height: 200px; overflow:hidden;">
                        <?php if ($gambar_url) : ?>
                            <img src="<?php echo esc_url($gambar_url); ?>" class="img-fluid" style="max-height: 100%;">
                        <?php else : ?>
                            <span class="text-muted"><i class="fas fa-image fa-2x"></i><br>Belum ada gambar</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control form-control-sm" id="gambar_produk" name="gambar_produk" accept="image/*">
                    <small class="text-muted d-block mt-2">Maksimal 2MB (JPG/PNG)</small>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Kategori</div>
                <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                    <?php
                    $categories = get_terms(['taxonomy' => 'kategori_produk', 'hide_empty' => false]);
                    if (!empty($categories) && !is_wp_error($categories)) {
                        foreach ($categories as $cat) {
                            $checked = in_array($cat->term_id, $kategori_selected) ? 'checked' : '';
                            echo '<div class="form-check">';
                            echo '<input class="form-check-input" type="checkbox" name="kategori[]" value="' . esc_attr($cat->term_id) . '" id="cat-' . esc_attr($cat->term_id) . '" ' . $checked . '>';
                            echo '<label class="form-check-label" for="cat-' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</label>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="small text-muted">Belum ada kategori.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="btn-save-produk">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <i class="fas fa-save"></i> Simpan Produk
                </button>
            </div>
        </div>
    </div>
</form>