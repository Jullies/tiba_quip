<?php
$summary=product_review_summary((int)$p['id']);$reviewPage=max(1,(int)($_GET['reviews_page']??1));
$reviews=rows('SELECT author_name,rating,body,created_at FROM product_reviews WHERE product_id=? AND status="published" ORDER BY created_at DESC,id DESC LIMIT 10 OFFSET '.(($reviewPage-1)*10),[$p['id']]);
$ownReview=row('SELECT author_name,email,rating,body,status FROM product_reviews WHERE product_id=? AND reviewer_key=?',[$p['id'],review_identity()]);
?>
<section id="product-reviews-content" class="tq-reviews pt-60">
<h3>Customer reviews</h3><div class="tq-review-summary"><?= review_stars($summary['average']) ?><strong><?= $summary['count']?h(number_format($summary['average'],1)).' / 5':'Not rated yet' ?></strong><span><?= $summary['count'] ?> <?= $summary['count']===1?'review':'reviews' ?></span></div>
<?php if(!$summary['count']): ?><p>No reviews yet. Be the first to rate this product.</p><?php endif; ?>
<?php foreach($reviews as $review): ?><article class="tq-review"><div class="tq-review-meta"><strong><?= h($review['author_name']) ?></strong><time datetime="<?= h(date('Y-m-d',strtotime($review['created_at']))) ?>"><?= h(date('j M Y',strtotime($review['created_at']))) ?></time><span aria-label="<?= (int)$review['rating'] ?> out of 5 stars"><?= review_stars((float)$review['rating']) ?></span></div><?php if($review['body']!==''): ?><p><?= nl2br(h($review['body'])) ?></p><?php endif; ?></article><?php endforeach; ?>
<?php if($summary['count']>10): ?><nav class="tq-review-pages" aria-label="Review pages"><?php if($reviewPage>1): ?><a href="?slug=<?= h(rawurlencode($p['slug'])) ?>&amp;reviews_page=<?= $reviewPage-1 ?>#product-reviews-content">Previous reviews</a><?php endif; ?><?php if($reviewPage*10<$summary['count']): ?><a href="?slug=<?= h(rawurlencode($p['slug'])) ?>&amp;reviews_page=<?= $reviewPage+1 ?>#product-reviews-content">More reviews</a><?php endif; ?></nav><?php endif; ?>
<div class="tq-review-form"><h4><?= $ownReview?'Update your review':'Write a review' ?></h4><p>Your email address is optional and will not be displayed.</p>
<?php if($ownReview&&$ownReview['status']==='hidden'): ?><p>Your review is currently hidden by the store.</p><?php endif; ?>
<?php if($error&&($_POST['action']??'')==='review'): ?><p class="alert alert-danger" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="<?= url('shop-single.php?slug='.rawurlencode($p['slug']).'#product-reviews-content') ?>" class="store-form">
<?= csrf_field() ?><input type="hidden" name="action" value="review"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
<fieldset class="tq-rating-input"><legend>Your rating</legend><div><?php for($star=5;$star>=1;$star--): ?><input type="radio" id="review-star-<?= $star ?>" name="rating" value="<?= $star ?>" required <?= (int)($_POST['rating']??$ownReview['rating']??0)===$star?'checked':'' ?>><label for="review-star-<?= $star ?>" title="<?= $star ?> stars">★<span class="visually-hidden"><?= $star ?> <?= $star===1?'star':'stars' ?></span></label><?php endfor; ?></div></fieldset>
<div class="row"><div class="col-md-6"><label for="review-name">Name</label><input id="review-name" name="name" required maxlength="190" autocomplete="name" value="<?= h($_POST['name']??$ownReview['author_name']??user()['name']??'') ?>"></div><div class="col-md-6"><label for="review-email">Email (optional, private)</label><input id="review-email" name="email" type="email" maxlength="190" autocomplete="email" value="<?= h($_POST['email']??$ownReview['email']??user()['email']??'') ?>"></div></div>
<label for="review-body">Review (optional)</label><textarea id="review-body" name="review" maxlength="2000" rows="4" placeholder="What did you think of this product?"><?= h($_POST['review']??$ownReview['body']??'') ?></textarea>
<div class="tq-review-honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
<button class="theme-btn" type="submit"><?= $ownReview?'Update rating':'Submit rating' ?></button>
</form></div></section>
