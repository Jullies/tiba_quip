<?php
function track(string $event,?int $resource=null,?string $detail=null):void {
    if(($_SERVER['HTTP_X_TIBA_LOAD']??'')==='products'||PHP_SAPI==='cli'||($_SERVER['REQUEST_METHOD']??'GET')==='HEAD'||(user()['role']??'')==='admin'||setting('analytics_enabled','1')!=='1'||($_SERVER['HTTP_DNT']??'')==='1')return;
    $ua=$_SERVER['HTTP_USER_AGENT']??'';if(preg_match('/bot|crawl|spider|preview/i',$ua))return;
    $page=basename($_SERVER['SCRIPT_NAME']??'index.php');if(strpos($_SERVER['SCRIPT_NAME']??'','/admin/')!==false)return;
    $visitor=$_COOKIE['tiba_visitor']??'';if(!preg_match('/^[a-f0-9]{64}$/',$visitor)){$visitor=bin2hex(random_bytes(32));setcookie('tiba_visitor',$visitor,['expires'=>time()+86400*180,'path'=>url(),'httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);$_COOKIE['tiba_visitor']=$visitor;}
    db('INSERT INTO analytics_visitors(id) VALUES (?) ON DUPLICATE KEY UPDATE last_seen=NOW()',[$visitor]);
    if(empty($_SESSION['analytics_session'])||($_SESSION['analytics_seen']??0)<time()-1800){$_SESSION['analytics_session']=bin2hex(random_bytes(32));$device=preg_match('/ipad|tablet/i',$ua)?'Tablet':(preg_match('/mobile|android/i',$ua)?'Mobile':'Desktop');$browser=preg_match('/Edg/i',$ua)?'Edge':(preg_match('/Firefox/i',$ua)?'Firefox':(preg_match('/Chrome/i',$ua)?'Chrome':(preg_match('/Safari/i',$ua)?'Safari':'Other')));$os=preg_match('/Windows/i',$ua)?'Windows':(preg_match('/Android/i',$ua)?'Android':(preg_match('/iPhone|iPad/i',$ua)?'iOS':(preg_match('/Mac/i',$ua)?'macOS':(preg_match('/Linux/i',$ua)?'Linux':'Other'))));
        $ref=substr(parse_url($_SERVER['HTTP_REFERER']??'',PHP_URL_HOST)??'',0,190);if($ref===($_SERVER['HTTP_HOST']??''))$ref='';
        db('INSERT INTO analytics_sessions(id,visitor_id,user_id,entry_page,exit_page,referrer,source,medium,campaign,device,browser,os) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',[$_SESSION['analytics_session'],$visitor,user()['id']??null,$page,$page,$ref,substr($_GET['utm_source']??'',0,120),substr($_GET['utm_medium']??'',0,120),substr($_GET['utm_campaign']??'',0,120),$device,$browser,$os]);}
    $_SESSION['analytics_seen']=time();db('UPDATE analytics_sessions SET last_seen=NOW(),exit_page=?,user_id=? WHERE id=?',[$page,user()['id']??null,$_SESSION['analytics_session']]);
    db('INSERT INTO analytics_events(session_id,event,page,resource_id,detail) VALUES (?,?,?,?,?)',[$_SESSION['analytics_session'],$event,$page,$resource,$detail===null?null:mb_substr($detail,0,190)]);
}
