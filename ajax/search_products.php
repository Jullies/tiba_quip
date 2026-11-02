<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);echo json_encode(['products'=>[]]);exit;}

$query=mb_substr(trim((string)($_GET['q']??'')),0,150);
$category=filter_var($_GET['category']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(mb_strlen($query)<2){echo json_encode(['products'=>[]]);exit;}

$rankedMatches=smart_product_search($query);
$matches=array_column($rankedMatches,'id');
if(!$matches){echo json_encode(['products'=>[]]);exit;}
$scores=array_column($rankedMatches,'score','id');
$where=['p.status="publish"','p.id IN ('.implode(',',array_map('intval',$matches)).')'];
$params=[];
if($category!==false&&$category!==null){
    $categories=rows('SELECT id,parent_id FROM categories WHERE active=1');$ids=[(int)$category];
    do{$before=count($ids);foreach($categories as $item)if(in_array((int)$item['parent_id'],$ids,true)&&!in_array((int)$item['id'],$ids,true))$ids[]=(int)$item['id'];}while(count($ids)>$before);
    $where[]='EXISTS(SELECT 1 FROM product_categories filter_pc WHERE filter_pc.product_id=p.id AND filter_pc.category_id IN ('.implode(',',$ids).'))';
}
$sql="SELECT p.id,p.name,p.slug,p.image,p.price,GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') category_name FROM products p LEFT JOIN product_categories pc ON pc.product_id=p.id LEFT JOIN categories c ON c.id=pc.category_id AND c.active=1 WHERE ".implode(' AND ',$where)." GROUP BY p.id ORDER BY FIELD(p.id,".implode(',',array_map('intval',$matches)).") LIMIT 24";
$products=rows($sql,$params);
$payload=array_map(static function(array $product)use($scores):array{return ['name'=>$product['name'],'price'=>money($product['price']),'category'=>$product['category_name']??'','image'=>url($product['image']?:'assets/img/logo/favicon.png'),'url'=>url('shop-single.php?slug='.rawurlencode($product['slug'])),'score'=>(int)($scores[$product['id']]??0)];},$products);
echo json_encode(['products'=>$payload],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
