<?php
require_once __DIR__.'/app/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
$base=site_url();
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach(['','shop.php','category.php','blog.php','about.php','contact.php'] as $path)echo '<url><loc>'.h($base.$path).'</loc></url>';
foreach(['products'=>['shop-single.php','status="publish"'],'content'=>['','status="publish"']] as $table=>$spec){$last=0;do{$batch=rows("SELECT * FROM `$table` WHERE {$spec[1]} AND id>? ORDER BY id LIMIT 200",[$last]);foreach($batch as $r){$last=$r['id'];$path=$table==='products'?'shop-single.php':($r['type']==='post'?'blog-single.php':'page.php');echo '<url><loc>'.h($base.$path.'?slug='.rawurlencode($r['slug'])).'</loc></url>';}}while(count($batch)===200);}
echo '</urlset>';
