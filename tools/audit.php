<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function source_config() {
 $text=file_get_contents('C:/xampp/htdocs/tiba_wordpress/wp-config.php'); $c=[];
 foreach(['DB_NAME','DB_USER','DB_PASSWORD','DB_HOST'] as $key){preg_match('/define\(\s*[\'\"]'.$key.'[\'\"]\s*,\s*[\'\"]([^\'\"]*)[\'\"]\s*\)/',$text,$m);$c[$key]=$m[1]??'';}
 preg_match('/\$table_prefix\s*=\s*[\'\"]([^\'\"]+)/',$text,$m);$c['prefix']=$m[1];return $c;
}
$c=source_config();$wp=new mysqli($c['DB_HOST'],$c['DB_USER'],$c['DB_PASSWORD'],$c['DB_NAME']);$wp->set_charset('utf8mb4');
require __DIR__.'/../control/core/database/connect.php';
echo 'SOURCE DATABASE: '.$c['DB_NAME'].' PREFIX: '.$c['prefix'].PHP_EOL;
foreach($wp->query("SELECT post_type,post_status,COUNT(*) count FROM {$c['prefix']}posts GROUP BY post_type,post_status") as $r)echo json_encode($r).PHP_EOL;
echo 'SOURCE TAXONOMIES'.PHP_EOL;foreach($wp->query("SELECT taxonomy,COUNT(*) count FROM {$c['prefix']}term_taxonomy GROUP BY taxonomy") as $r)echo json_encode($r).PHP_EOL;
echo 'DESTINATION TABLES'.PHP_EOL;
foreach($conn->query('SHOW TABLES') as $r){$t=array_values($r)[0];echo $t.' '.$conn->query("SELECT COUNT(*) FROM `$t`")->fetch_row()[0].PHP_EOL; foreach($conn->query("SHOW COLUMNS FROM `$t`") as $col)echo '  '.$col['Field'].' '.$col['Type'].PHP_EOL;}
echo 'SITE OPTIONS'.PHP_EOL;
foreach($wp->query("SELECT option_name,option_value FROM {$c['prefix']}options WHERE option_name IN ('blogname','blogdescription','woocommerce_currency','woocommerce_store_address','woocommerce_store_city','woocommerce_store_postcode','woocommerce_default_country','site_icon','template','stylesheet')") as $r)echo json_encode($r).PHP_EOL;
