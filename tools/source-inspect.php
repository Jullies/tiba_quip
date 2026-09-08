<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/bootstrap.php';
$text=file_get_contents('C:/xampp/htdocs/tiba_wordpress/wp-config.php');$c=[];foreach(['DB_NAME','DB_USER','DB_PASSWORD','DB_HOST'] as $k){preg_match('/define\(\s*[\'\"]'.$k.'[\'\"]\s*,\s*[\'\"]([^\'\"]*)/',$text,$m);$c[$k]=$m[1];}$wp=new mysqli($c['DB_HOST'],$c['DB_USER'],$c['DB_PASSWORD'],$c['DB_NAME']);
foreach($wp->query("SELECT ID,post_title,post_name,LEFT(post_content,350) content FROM wpd4_posts WHERE post_type='page' AND post_status='publish'") as $r)echo json_encode($r)."\n";
foreach($wp->query("SELECT option_name, LEFT(option_value,1200) value FROM wpd4_options WHERE option_name LIKE 'theme_mods_%' OR option_name LIKE '%medimall%' OR option_name='sidebars_widgets'") as $r)echo json_encode($r)."\n";
foreach($wp->query("SELECT meta_key, COUNT(*) n FROM wpd4_postmeta pm JOIN wpd4_posts p ON p.ID=pm.post_id WHERE p.post_type='product' GROUP BY meta_key ORDER BY n DESC LIMIT 50") as $r)echo json_encode($r)."\n";
