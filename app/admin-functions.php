<?php
require_once __DIR__.'/bootstrap.php';
function admin_fields():array{return [
'products'=>['label'=>'Products','table'=>'products','fields'=>['name'=>'text','slug'=>'text','sku'=>'text','description'=>'html','short_description'=>'html','regular_price'=>'money','sale_price'=>'optional_money','stock'=>'number','manage_stock'=>'bool','stock_status'=>['instock','outofstock','onbackorder'],'status'=>['draft','publish','archived'],'type'=>['simple','variable'],'featured'=>'bool','image'=>'image','attributes'=>'json','tags'=>'text','weight'=>'text','dimensions'=>'json','seo_title'=>'text','meta_description'=>'textarea']],
'categories'=>['label'=>'Categories','table'=>'categories','fields'=>['name'=>'text','slug'=>'text','parent_id'=>'parent','description'=>'textarea','image'=>'image','sort_order'=>'number','active'=>'bool']],
'posts'=>['label'=>'Blog posts','table'=>'content','type'=>'post','fields'=>['title'=>'text','slug'=>'text','excerpt'=>'textarea','body'=>'html','image'=>'image','category_id'=>'blog_category','tags'=>'text','author'=>'text','status'=>['draft','publish','archived'],'published_at'=>'datetime','seo_title'=>'text','meta_description'=>'textarea']],
'pages'=>['label'=>'Pages','table'=>'content','type'=>'page','fields'=>['title'=>'text','slug'=>'text','body'=>'html','image'=>'image','status'=>['draft','publish','archived'],'seo_title'=>'text','meta_description'=>'textarea']],
'blog-categories'=>['label'=>'Blog categories','table'=>'content_categories','fields'=>['name'=>'text','slug'=>'text']],
'banners'=>['label'=>'Banners','table'=>'banners','fields'=>['title'=>'text','description'=>'textarea','image'=>'image','link'=>'text','button_text'=>'text','sort_order'=>'number','active'=>'bool']]
];}
function admin_save(string $resource,int $id):int{
 global $conn;$def=admin_fields()[$resource]??null;if(!$def)throw new RuntimeException('Unknown resource.');$table=$def['table'];$old=$id?row("SELECT * FROM `$table` WHERE id=?".(!empty($def['type'])?' AND type=?':''),!empty($def['type'])?[$id,$def['type']]:[$id]):null;if($id&&!$old)throw new RuntimeException('Record not found.');$data=[];
 foreach($def['fields'] as $k=>$kind){$value=$_POST[$k]??'';if(is_array($kind)){if(!in_array($value,$kind))throw new RuntimeException('Choose a valid '.str_replace('_',' ',$k).'.');}
 elseif($kind==='bool')$value=isset($_POST[$k])?1:0;
 elseif($kind==='image'){$value=upload($k)??($old[$k]??null);if(isset($_POST['remove_'.$k]))$value=null;}
 elseif(in_array($kind,['number','money','optional_money'])){if($kind==='optional_money'&&$value==='')$value=null;else{if(!is_numeric($value)||$value<0)throw new RuntimeException(ucfirst(str_replace('_',' ',$k)).' must be zero or greater.');$value=(float)$value;}}
 elseif($kind==='json'){if(trim($value)==='')$value='[]';json_decode($value,true);if(json_last_error()!==JSON_ERROR_NONE)throw new RuntimeException('Enter valid JSON for '.$k.'.');}
 elseif($kind==='html')$value=clean_html($value);
 elseif(in_array($kind,['parent','blog_category']))$value=(int)$value?:null;
 elseif($kind==='datetime')$value=$value?date('Y-m-d H:i:s',strtotime($value)):date('Y-m-d H:i:s');
 else $value=trim($value);
 $data[$k]=$value;
 }
 $title=$data['name']??$data['title']??'';if($title==='')throw new RuntimeException('A name or title is required.');if(isset($data['slug']))$data['slug']=slug($data['slug']?:$title);
 if($resource==='products'){$data['sku']=$data['sku']?:null;if($data['sale_price']!==null&&$data['sale_price']>$data['regular_price'])throw new RuntimeException('Sale price cannot exceed regular price.');$data['price']=$data['sale_price']??$data['regular_price'];}
 if($resource==='categories'&&$data['parent_id']){$parent=$data['parent_id'];$seen=[];while($parent){if($parent===$id||in_array($parent,$seen))throw new RuntimeException('A category cannot be its own ancestor.');$seen[]=$parent;$parent=(int)scalar('SELECT parent_id FROM categories WHERE id=?',[$parent]);}}
 if($resource==='banners'&&safe_link($data['link'])==='#')throw new RuntimeException('Enter a safe banner link.');
 if(isset($def['type']))$data['type']=$def['type'];
 $conn->begin_transaction();try{
 if($id){db("UPDATE `$table` SET ".implode(',',array_map(fn($k)=>"`$k`=?",array_keys($data))).' WHERE id=?',array_merge(array_values($data),[$id]));}else{db("INSERT INTO `$table` (`".implode('`,`',array_keys($data)).'`) VALUES ('.implode(',',array_fill(0,count($data),'?')).')',array_values($data));$id=(int)$conn->insert_id;}
 if($resource==='products'){
 db('DELETE FROM product_categories WHERE product_id=?',[$id]);foreach($_POST['categories']??[] as $cat){if(!scalar('SELECT id FROM categories WHERE id=?',[(int)$cat]))throw new RuntimeException('Invalid category.');db('INSERT IGNORE INTO product_categories VALUES (?,?)',[$id,(int)$cat]);}
 foreach($_POST['remove_gallery']??[] as $imageId)db('DELETE FROM product_images WHERE id=? AND product_id=?',[(int)$imageId,$id]);
 if(!empty($_FILES['gallery']['name'][0])){foreach($_FILES['gallery']['name'] as $i=>$name){foreach(['name','type','tmp_name','error','size'] as $key)$_FILES['gallery_one'][$key]=$_FILES['gallery'][$key][$i];$path=upload('gallery_one');if($path)db('INSERT INTO product_images(product_id,path,sort_order) VALUES (?,?,?)',[$id,$path,$i]);}}
 $variants=json_decode($_POST['variants']??'[]',true);if(!is_array($variants))throw new RuntimeException('Variants must be a JSON list.');$kept=[];foreach($variants as $v){if(!is_array($v)||!isset($v['price'])||!is_numeric($v['price'])||$v['price']<0||($v['stock']??0)<0)throw new RuntimeException('Each variant needs non-negative price and stock.');$vid=(int)($v['id']??0);$values=[($v['sku']??'')?:null,json_encode(is_string($v['attributes']??null)?(json_decode($v['attributes'],true)?:[]):($v['attributes']??[])),(float)$v['price'],(float)($v['regular_price']??$v['price']),(float)($v['stock']??0),!empty($v['manage_stock'])?1:0,in_array($v['stock_status']??'instock',['instock','outofstock','onbackorder'])?($v['stock_status']??'instock'):'instock',isset($v['active'])?(int)(bool)$v['active']:1];if($vid){if(!scalar('SELECT id FROM product_variants WHERE id=? AND product_id=?',[$vid,$id]))throw new RuntimeException('Invalid variant ID.');db('UPDATE product_variants SET sku=?,attributes=?,price=?,regular_price=?,stock=?,manage_stock=?,stock_status=?,active=? WHERE id=? AND product_id=?',array_merge($values,[$vid,$id]));}else{db('INSERT INTO product_variants(sku,attributes,price,regular_price,stock,manage_stock,stock_status,active,product_id) VALUES (?,?,?,?,?,?,?,?,?)',array_merge($values,[$id]));$vid=$conn->insert_id;}$kept[]=(int)$vid;}db('UPDATE product_variants SET active=0 WHERE product_id=?'.($kept?' AND id NOT IN ('.implode(',',$kept).')':''),[$id]);
 }
 audit($old?'updated':'created',$resource,$id);$conn->commit();return $id;
 }catch(Throwable $e){$conn->rollback();throw $e;}
}
function admin_archive(string $resource,int $id):void {
 global $conn;$d=admin_fields()[$resource]??null;if(!$d)throw new RuntimeException('Invalid resource.');$table=$d['table'];$conn->begin_transaction();try{
 if($resource==='categories'){if(scalar('SELECT COUNT(*) FROM product_categories WHERE category_id=?',[$id])||scalar('SELECT COUNT(*) FROM categories WHERE parent_id=?',[$id]))throw new RuntimeException('Reassign products and child categories before deleting this category.');db('DELETE FROM categories WHERE id=?',[$id]);}
 elseif($resource==='blog-categories'){if(scalar('SELECT COUNT(*) FROM content WHERE category_id=?',[$id]))throw new RuntimeException('Reassign posts before deleting this category.');db('DELETE FROM content_categories WHERE id=?',[$id]);}
 elseif($resource==='banners')db('UPDATE banners SET active=0 WHERE id=?',[$id]);
 else db("UPDATE `$table` SET status='archived' WHERE id=?".(!empty($d['type'])?' AND type=?':''),!empty($d['type'])?[$id,$d['type']]:[$id]);audit('archived',$resource,$id);$conn->commit();}catch(Throwable $e){$conn->rollback();throw $e;}
}
function update_order(int $id):void{
 global $conn;$status=$_POST['status']??'';$pay=$_POST['payment_status']??'';$statuses=['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];if(!in_array($status,$statuses)||!in_array($pay,['unpaid','paid','refunded']))throw new RuntimeException('Invalid order state.');$conn->begin_transaction();try{$o=row('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);if(!$o)throw new RuntimeException('Order not found.');if(in_array($o['status'],['cancelled','refunded'])&&$o['status']!==$status)throw new RuntimeException('A closed order cannot be reopened. Create a new order instead.');
 if($status==='cancelled'&&$o['status']!=='cancelled'&&!$o['source_id'])foreach(rows('SELECT * FROM order_items WHERE order_id=?',[$id]) as $i){if($i['variant_id'])db('UPDATE product_variants SET stock=stock+? WHERE id=? AND manage_stock=1',[$i['quantity'],$i['variant_id']]);elseif($i['product_id'])db('UPDATE products SET stock=stock+? WHERE id=? AND manage_stock=1',[$i['quantity'],$i['product_id']]);}
 db('UPDATE orders SET status=?,payment_status=?,notes=? WHERE id=?',[$status,$pay,mb_substr($_POST['notes']??'',0,5000),$id]);db('UPDATE payments SET status=? WHERE order_id=?',[$pay==='unpaid'?'pending':$pay,$id]);db('INSERT INTO order_history(order_id,status,note,admin_id) VALUES (?,?,?,?)',[$id,$status,mb_substr($_POST['note']??'',0,2000),user()['id']]);audit('status_changed','orders',$id,$o['status'].' -> '.$status);$conn->commit();}catch(Throwable $e){$conn->rollback();throw $e;}
}
