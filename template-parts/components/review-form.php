<?php
/**
 * Template Part: Form Review
 */

if (!is_user_logged_in()) {
    echo '<div class="alert alert-warning">Silakan <a href="' . home_url('/login') . '">login</a> untuk menulis ulasan.</div>';
    return;
}
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Tulis Ulasan</h5>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST">
            <input type="hidden" name="action" value="dw_submit_review">
            <input type="hidden" name="post_id" value="<?php the_ID(); ?>">
            <?php wp_nonce_field('dw_review_action', 'dw_review_nonce'); ?>

            <div class="mb-3">
                <label class="form-label">Rating</label>
                <div class="rating-input">
                    <!-- Simple Radio Input for Stars -->
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="rating" id="star5" value="5" checked>
                        <label class="form-check-label text-warning" for="star5"><i class="fas fa-star"></i> 5</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="rating" id="star4" value="4">
                        <label class="form-check-label text-warning" for="star4"><i class="fas fa-star"></i> 4</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="rating" id="star3" value="3">
                        <label class="form-check-label text-warning" for="star3"><i class="fas fa-star"></i> 3</label>
                    </div>
                    <!-- Bisa dilanjutkan sampai 1 -->
                </div>
            </div>

            <div class="mb-3">
                <label for="review_content" class="form-label">Ulasan Anda</label>
                <textarea class="form-control" id="review_content" name="review_content" rows="3" required placeholder="Ceritakan pengalaman Anda..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
        </form>
    </div>
</div>