<?php
/**
 * Template Part: List Review
 */

$comments = get_comments([
    'post_id' => get_the_ID(),
    'status'  => 'approve'
]);
?>

<div class="review-list">
    <h5 class="mb-4">Ulasan Pembeli (<?php echo count($comments); ?>)</h5>

    <?php if ($comments) : ?>
        <ul class="list-unstyled">
            <?php foreach ($comments as $comment) : 
                $rating = get_comment_meta($comment->comment_ID, 'dw_rating', true);
            ?>
                <li class="media d-flex mb-4 pb-4 border-bottom">
                    <div class="flex-shrink-0 me-3">
                        <?php echo get_avatar($comment, 50, '', '', ['class' => 'rounded-circle']); ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mt-0 mb-0"><?php echo get_comment_author($comment); ?></h6>
                            <small class="text-muted"><?php echo get_comment_date('d M Y', $comment); ?></small>
                        </div>
                        
                        <?php if ($rating) : ?>
                            <div class="text-warning mb-2 small">
                                <?php for($i=1; $i<=5; $i++) {
                                    echo ($i <= $rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                } ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-0 text-secondary"><?php echo get_comment_text($comment); ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p class="text-muted">Belum ada ulasan. Jadilah yang pertama mengulas!</p>
    <?php endif; ?>
</div>