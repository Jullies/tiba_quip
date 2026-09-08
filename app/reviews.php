<?php
function prime_product_reviews(array $ids):void {
    $ids=array_values(array_unique(array_filter(array_map('intval',$ids))));
    $missing=array_filter($ids,fn($id)=>!isset($GLOBALS['productReviewSummaries'][$id]));
    if(!$missing)return;
    foreach($missing as $id)$GLOBALS['productReviewSummaries'][$id]=['average'=>0,'count'=>0];
    foreach(rows('SELECT product_id,AVG(rating) average,COUNT(*) count FROM product_reviews WHERE status="published" AND product_id IN ('.implode(',',$missing).') GROUP BY product_id') as $r)$GLOBALS['productReviewSummaries'][(int)$r['product_id']]=['average'=>(float)$r['average'],'count'=>(int)$r['count']];
}
function product_review_summary(int $id):array {prime_product_reviews([$id]);return $GLOBALS['productReviewSummaries'][$id];}
function review_stars(float $average):string {
    $percent=max(0,min(100,$average*20));
    return '<span class="tq-review-stars" aria-hidden="true"><span style="width:'.number_format($percent,2,'.','').'%">★★★★★</span>★★★★★</span>';
}
function product_review_link(array $product):void {
    $r=product_review_summary((int)$product['id']);
    echo '<a class="tq-product-rating" href="'.h(url('shop-single.php?slug='.rawurlencode($product['slug']).'#product-reviews-content')).'" aria-label="'.h($r['count']?number_format($r['average'],1).' out of 5 from '.$r['count'].' reviews. Read or write a review.':'No reviews yet. Write a review.').'">'.review_stars($r['average']).'<span>('.$r['count'].')</span></a>';
}
function review_identity():string {return hash('sha256',user()?'user:'.user()['id']:'cart:'.cart_id());}
function submit_product_review(array $data):array {
    $product=row('SELECT id,slug FROM products WHERE id=? AND status="publish"',[(int)($data['product_id']??0)]);
    if(!$product)throw new RuntimeException('This product is not available.');
    $rating=filter_var($data['rating']??null,FILTER_VALIDATE_INT);
    $name=trim((string)($data['name']??''));$email=trim((string)($data['email']??''));$body=trim((string)($data['review']??''));
    if($rating===false||$rating<1||$rating>5)throw new RuntimeException('Choose a rating from 1 to 5 stars.');
    if($name===''||mb_strlen($name)>190)throw new RuntimeException('Enter your name (up to 190 characters).');
    if($email!==''&&(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190))throw new RuntimeException('Enter a valid email address or leave it empty.');
    if(mb_strlen($body)>2000)throw new RuntimeException('Keep your review within 2,000 characters.');
    if(!empty($data['website']))throw new RuntimeException('Unable to submit this review.');
    $identity=review_identity();$rate=hash('sha256','review:'.$identity);
    if((int)scalar('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$rate])>=8)throw new RuntimeException('You have submitted several reviews recently. Please try again later.');
    db('INSERT INTO login_attempts(identity_hash) VALUES (?)',[$rate]);
    db('INSERT INTO product_reviews(product_id,user_id,reviewer_key,author_name,email,rating,body) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE author_name=VALUES(author_name),email=VALUES(email),rating=VALUES(rating),body=VALUES(body)',[$product['id'],user()['id']??null,$identity,$name,$email?:null,$rating,$body]);
    return $product;
}
