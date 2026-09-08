<?php
function cart_id():int {
    static $id=null;if($id)return $id;
    $token=$_COOKIE['tiba_cart']??'';
    if(!preg_match('/^[a-f0-9]{64}$/',$token)){$token=bin2hex(random_bytes(32));if(PHP_SAPI!=='cli')setcookie('tiba_cart',$token,['expires'=>time()+86400*30,'path'=>url(),'httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);$_COOKIE['tiba_cart']=$token;}
    $cart=row('SELECT * FROM carts WHERE token=?',[$token]);$u=user();
    if(!$cart){db('INSERT INTO carts(token,user_id) VALUES (?,?)',[$token,$u['id']??null]);$cart=row('SELECT * FROM carts WHERE token=?',[$token]);}
    if($u&&$cart['user_id']&&$cart['user_id']!=$u['id']){unset($_COOKIE['tiba_cart']);return cart_id();}
    if($u&&!$cart['user_id']){
        $old=row('SELECT id FROM carts WHERE user_id=? ORDER BY updated_at DESC LIMIT 1',[$u['id']]);
        if($old){db('INSERT INTO cart_items(cart_id,product_id,variant_id,quantity) SELECT ?,src.product_id,src.variant_id,src.quantity FROM cart_items src WHERE src.cart_id=? ON DUPLICATE KEY UPDATE quantity=LEAST(99,cart_items.quantity+VALUES(quantity))',[$cart['id'],$old['id']]);db('INSERT IGNORE INTO wishlist_items SELECT ?,product_id FROM wishlist_items WHERE cart_id=?',[$cart['id'],$old['id']]);db('DELETE FROM carts WHERE id=?',[$old['id']]);}
        db('UPDATE carts SET user_id=? WHERE id=?',[$u['id'],$cart['id']]);
    }
    db('UPDATE carts SET updated_at=NOW() WHERE id=?',[$cart['id']]);return $id=(int)$cart['id'];
}
function cart_items():array{return rows('SELECT ci.*,p.name,p.slug,p.image,p.status,p.type,p.sku,COALESCE(v.price,p.price) price,COALESCE(v.stock,p.stock) stock,COALESCE(v.manage_stock,p.manage_stock) manage_stock,COALESCE(v.stock_status,p.stock_status) stock_status,v.attributes options,v.active variant_active FROM cart_items ci JOIN products p ON p.id=ci.product_id LEFT JOIN product_variants v ON v.id=ci.variant_id AND v.product_id=p.id WHERE ci.cart_id=?',[cart_id()]);}
function available(array $p,int $quantity):bool{return $quantity>0&&$quantity<=99&&($p['status']??'publish')==='publish'&&$p['stock_status']!=='outofstock'&&(!$p['manage_stock']||(float)$p['stock']>=$quantity);}
function cart_add(int $product,int $variant,int $quantity):void {
    $p=row('SELECT * FROM products WHERE id=? AND status="publish"',[$product]);if(!$p)throw new RuntimeException('Product not found.');
    if($p['type']==='variable'){$v=row('SELECT * FROM product_variants WHERE id=? AND product_id=? AND active=1',[$variant,$product]);if(!$v)throw new RuntimeException('Choose an available variant.');$p=array_merge($p,array_intersect_key($v,array_flip(['price','stock','manage_stock','stock_status'])));}else{$variant=0;}
    $old=(int)scalar('SELECT quantity FROM cart_items WHERE cart_id=? AND product_id=? AND variant_id=?',[cart_id(),$product,$variant]);
    if(!available($p,$old+$quantity)||$quantity<1)throw new RuntimeException('The requested quantity is unavailable.');
    db('INSERT INTO cart_items(cart_id,product_id,variant_id,quantity) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity)',[cart_id(),$product,$variant,$quantity]);track('add_to_cart',$product);
}
function totals(array $items):array{$subtotal=0;foreach($items as $i)$subtotal+=(int)round($i['price']*100)*$i['quantity'];$shipping=$subtotal===0?0:(int)round((float)setting('shipping_fee','0')*100);$threshold=(int)round((float)setting('free_shipping_threshold','0')*100);if($threshold>0&&$subtotal>=$threshold)$shipping=0;$tax=(int)round($subtotal*(float)setting('tax_rate','0')/100);return ['subtotal'=>$subtotal/100,'shipping'=>$shipping/100,'tax'=>$tax/100,'total'=>($subtotal+$shipping+$tax)/100];}
function checkout(array $data):array {
    global $conn;$key=(string)($data['checkout_key']??'');if(!preg_match('/^[a-f0-9]{64}$/',$key)||!hash_equals($_SESSION['checkout_key']??'',$key))throw new RuntimeException('Reload checkout before placing the order.');
    foreach(['name','email','phone','address','city','country'] as $f)if(trim($data[$f]??'')==='')throw new RuntimeException('Complete all delivery details.');
    if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');
    $method=$data['payment_method']??'';if(!in_array($method,['cod','bank_transfer'])||setting('payment_'.$method,'0')!=='1')throw new RuntimeException('That payment method is not available.');
    $conn->begin_transaction();try{
        row('SELECT id FROM carts WHERE id=? FOR UPDATE',[cart_id()]);
        $existing=row('SELECT * FROM orders WHERE checkout_key=?',[$key]);if($existing){$conn->commit();return $existing;}
        $items=cart_items();if(!$items)throw new RuntimeException('Your cart is empty.');
        foreach($items as &$i){$p=row('SELECT * FROM products WHERE id=? FOR UPDATE',[$i['product_id']]);if($i['variant_id']){$v=row('SELECT * FROM product_variants WHERE id=? AND product_id=? AND active=1 FOR UPDATE',[$i['variant_id'],$i['product_id']]);if(!$v)throw new RuntimeException('A variant is no longer available.');$p=array_merge($p,array_intersect_key($v,array_flip(['price','stock','manage_stock','stock_status'])));}if(!$p||!available($p,(int)$i['quantity']))throw new RuntimeException('Insufficient stock for '.$i['name']);$i['price']=$p['price'];$i['manage_stock']=$p['manage_stock'];}unset($i);
        $t=totals($items);$token=bin2hex(random_bytes(32));
        db('INSERT INTO orders(user_id,public_token,checkout_key,customer_name,email,phone,address,city,country,subtotal,shipping,tax,total,currency,payment_method,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[user()['id']??null,$token,$key,mb_substr($data['name'],0,190),$data['email'],mb_substr($data['phone'],0,60),mb_substr($data['address'],0,2000),mb_substr($data['city'],0,100),mb_substr($data['country'],0,100),$t['subtotal'],$t['shipping'],$t['tax'],$t['total'],setting('currency','KES'),$method,mb_substr($data['notes']??'',0,2000)]);$id=$conn->insert_id;
        foreach($items as $i){db('INSERT INTO order_items(order_id,product_id,variant_id,name,sku,options,quantity,unit_price,total) VALUES (?,?,?,?,?,?,?,?,?)',[$id,$i['product_id'],$i['variant_id']?:null,$i['name'],$i['sku'],$i['options'],$i['quantity'],$i['price'],round($i['price']*$i['quantity'],2)]);if($i['manage_stock']){if($i['variant_id'])db('UPDATE product_variants SET stock=stock-? WHERE id=?',[$i['quantity'],$i['variant_id']]);else db('UPDATE products SET stock=stock-? WHERE id=?',[$i['quantity'],$i['product_id']]);}}
        db('INSERT INTO order_history(order_id,status,note) VALUES (?,"pending","Order placed")',[$id]);db('INSERT INTO payments(order_id,method,amount) VALUES (?,?,?)',[$id,$method,$t['total']]);db('DELETE FROM cart_items WHERE cart_id=?',[cart_id()]);track('purchase',$id);$conn->commit();return row('SELECT * FROM orders WHERE id=?',[$id]);
    }catch(Throwable $e){$conn->rollback();throw $e;}
}
function order_access(int $id,string $token=''):?array{$o=row('SELECT * FROM orders WHERE id=?',[$id]);if(!$o)return null;$u=user();return ($u&&($u['role']==='admin'||$o['user_id']===$u['id']))||($token!==''&&hash_equals($o['public_token'],$token))?$o:null;}
