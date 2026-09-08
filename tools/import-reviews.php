<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$root=$argv[1]??'C:/xampp/htdocs/tiba_wordpress';
$text=file_get_contents($root.'/wp-config.php');$source=[];
foreach(['DB_NAME','DB_USER','DB_PASSWORD','DB_HOST'] as $key){preg_match('/define\(\s*[\'\"]'.$key.'[\'\"]\s*,\s*[\'\"]([^\'\"]*)/',$text,$m);$source[$key]=$m[1]??'';}
preg_match('/\$table_prefix\s*=\s*[\'\"]([a-zA-Z0-9_]+)/',$text,$m);$prefix=$m[1]??'';if(!$prefix)throw new RuntimeException('Source prefix not found.');
$wp=new mysqli($source['DB_HOST'],$source['DB_USER'],$source['DB_PASSWORD'],$source['DB_NAME']);$wp->set_charset('utf8mb4');$wp->query('START TRANSACTION READ ONLY');$count=0;
foreach($wp->query("SELECT c.*,MAX(CAST(m.meta_value AS UNSIGNED)) rating FROM {$prefix}comments c JOIN {$prefix}commentmeta m ON m.comment_id=c.comment_ID AND m.meta_key='rating' JOIN {$prefix}posts p ON p.ID=c.comment_post_ID AND p.post_type='product' WHERE c.comment_approved='1' GROUP BY c.comment_ID HAVING rating BETWEEN 1 AND 5") as $r){
    $product=scalar('SELECT id FROM products WHERE source_id=?',[$r['comment_post_ID']]);if(!$product)continue;
    $userId=$r['user_id']?scalar('SELECT id FROM users WHERE source_id=?',[$r['user_id']]):null;
    db('INSERT INTO product_reviews(source_id,product_id,user_id,reviewer_key,author_name,rating,body,created_at) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),body=VALUES(body),author_name=VALUES(author_name)',[$r['comment_ID'],$product,$userId,hash('sha256','wordpress-review:'.$r['comment_ID']),mb_substr($r['comment_author'],0,190),(int)$r['rating'],mb_substr(html_entity_decode(strip_tags($r['comment_content']),ENT_QUOTES,'UTF-8'),0,2000),$r['comment_date']]);$count++;
}
echo "Imported or updated $count approved source product reviews.\n";
