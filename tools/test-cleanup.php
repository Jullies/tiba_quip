<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$f=json_decode(file_get_contents(__DIR__.'/../storage/test-fixtures.json'),true);$conn->begin_transaction();
try{
foreach($f['orders']??[] as $id){db('DELETE FROM audit_logs WHERE resource="orders" AND resource_id=?',[$id]);db('DELETE FROM orders WHERE id=? AND source_id IS NULL AND customer_name LIKE "__verification%"',[$id]);}
foreach($f['products']??[] as $id){db('DELETE FROM audit_logs WHERE resource="products" AND resource_id=?',[$id]);db('DELETE FROM products WHERE id=? AND source_id IS NULL AND sku LIKE "TIBA-VERIFY-%"',[$id]);}
foreach($f['categories']??[] as $id){db('DELETE FROM audit_logs WHERE resource="categories" AND resource_id=?',[$id]);db('DELETE FROM categories WHERE id=? AND source_id IS NULL AND slug LIKE "verification-%"',[$id]);}
foreach($f['posts']??[] as $id){db('DELETE FROM audit_logs WHERE resource="posts" AND resource_id=?',[$id]);db('DELETE FROM content WHERE id=? AND source_id IS NULL AND slug LIKE "verification-%"',[$id]);}
foreach($f['emails']??[] as $email)if(str_starts_with($email,'verification-')&&str_ends_with($email,'@example.invalid'))db('DELETE FROM users WHERE email=? AND source_id IS NULL AND role="customer"',[$email]);
if(!empty($f['cookies']['tiba_cart']))db('DELETE FROM carts WHERE token=?',[$f['cookies']['tiba_cart']]);
if(!empty($f['cookies']['tiba_visitor'])){$v=$f['cookies']['tiba_visitor'];db('DELETE e FROM analytics_events e JOIN analytics_sessions s ON s.id=e.session_id WHERE s.visitor_id=?',[$v]);db('DELETE FROM analytics_sessions WHERE visitor_id=?',[$v]);db('DELETE FROM analytics_visitors WHERE id=?',[$v]);}
$conn->commit();
}catch(Throwable $e){$conn->rollback();throw $e;}
foreach($f['uploads']??[] as $path){$full=realpath(__DIR__.'/../'.$path);$allowed=realpath(__DIR__.'/../uploads/media');if($full&&$allowed&&str_starts_with($full,$allowed.DIRECTORY_SEPARATOR)&&preg_match('/^[a-f0-9]{32}\.(png|jpg|gif|webp)$/',basename($full)))unlink($full);}
echo "Verification fixtures removed.\n";
