<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/bootstrap.php';
foreach(['products'=>['description','short_description'],'content'=>['body']] as $table=>$fields)foreach($fields as $field)foreach(rows("SELECT id,`$field` body FROM `$table`") as $r){preg_match_all('~.{0,70}(?:tiba_wordpress|wp-content/|wp-json/).{0,100}~i',$r['body']??'',$matches);foreach($matches[0] as $v)echo $table.' '.$r['id'].' '.$v."\n";}
