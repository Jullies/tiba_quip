<?php
require_once __DIR__.'/app/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if(!hash_equals(csrf(),(string)($_POST['csrf']??''))){http_response_code(403);throw new RuntimeException('Your session expired. Reload the page and try again.');}
        $action=$_POST['action']??'';
        if($action==='add') {
            $quantity=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT);
            if($quantity===false||$quantity<1)throw new RuntimeException('Enter a valid quantity.');
            cart_add((int)($_POST['product_id']??0),(int)($_POST['variant_id']??0),$quantity);
        } elseif($action==='update') {
            $quantity=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT);
            if($quantity===false||$quantity<0||$quantity>99)throw new RuntimeException('Enter a quantity between 0 and 99.');
            $item=null;foreach(cart_items() as $entry)if($entry['id']==(int)($_POST['id']??0))$item=$entry;
            if(!$item)throw new RuntimeException('This cart item is no longer available.');
            if($quantity===0){db('DELETE FROM cart_items WHERE id=? AND cart_id=?',[$item['id'],cart_id()]);track('remove_from_cart',(int)$item['product_id']);}
            else {if(!available($item,$quantity)||($item['variant_id']&&!$item['variant_active']))throw new RuntimeException('The requested quantity is unavailable.');db('UPDATE cart_items SET quantity=? WHERE id=? AND cart_id=?',[$quantity,$item['id'],cart_id()]);}
        } else throw new RuntimeException('Unknown cart action.');
    } elseif($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);throw new RuntimeException('Method not allowed.');}
    $items=cart_items();$count=0;$result=[];
    foreach($items as $i){$count+=(int)$i['quantity'];$result[]=['id'=>(int)$i['id'],'product_id'=>(int)$i['product_id'],'name'=>$i['name'],'quantity'=>(int)$i['quantity'],'price'=>money($i['price']),'total'=>money($i['price']*$i['quantity']),'image'=>url($i['image']?:'assets/img/logo/favicon.png'),'link'=>url('shop-single.php?slug='.rawurlencode($i['slug'])),'options'=>$i['options']?json_decode($i['options'],true):null];}
    echo json_encode(['ok'=>true,'items'=>$result,'count'=>$count,'subtotal'=>money(totals($items)['subtotal'])]);
} catch(mysqli_sql_exception $e){error_log((string)$e);http_response_code(500);echo json_encode(['ok'=>false,'message'=>'Unable to update your cart. Please try again.']);}
catch(RuntimeException $e){if(http_response_code()<400)http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
