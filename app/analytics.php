<?php
function traffic_client_ip():string {
    foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $header){
        foreach(explode(',',(string)($_SERVER[$header]??'')) as $candidate){
            $ip=trim($candidate);
            if($ip!==''&&filter_var($ip,FILTER_VALIDATE_IP))return $ip;
        }
    }
    return '';
}
function traffic_ipapi_lookup(string $ip):array {
    global $config;
    $accessKey=trim((string)($config['ipapi_access_key']??''));
    if($ip===''||$accessKey==='')return [];
    // Match GreatRift's working ipapi integration. This intentionally uses
    // plaintext HTTP because the configured ipapi plan does not support HTTPS.
    $url='http://api.ipapi.com/'.rawurlencode($ip).'?access_key='.rawurlencode($accessKey);
    $context=stream_context_create(['http'=>['method'=>'GET','timeout'=>6,'ignore_errors'=>true]]);
    $response=@file_get_contents($url,false,$context);
    $geo=$response?json_decode($response,true):null;
    if(!is_array($geo)||isset($geo['error']))return [];
    $country=strtoupper(substr(trim((string)($geo['country_code']??'')),0,2));
    return preg_match('/^[A-Z]{2}$/',$country)?['country'=>$country,'country_name'=>mb_substr(trim((string)($geo['country_name']??'')),0,120)]:[];
}
function traffic_country_from_ip(string $ip):string {
    $cached=$_SESSION['analytics_country']??'';
    $checkedAt=(int)($_SESSION['analytics_country_checked_at']??0);
    if(is_string($cached)&&$cached!==''&&$checkedAt>=time()-604800)return $cached;
    $geo=traffic_ipapi_lookup($ip);
    if(!$geo)return '';
    $_SESSION['analytics_country']=$geo['country'];
    $_SESSION['analytics_country_checked_at']=time();
    return $geo['country'];
}
function traffic_is_automated(string $userAgent):bool {
    if(trim($userAgent)==='')return true;
    return (bool)preg_match('/(?:bot|crawler|spider|slurp|archiver|scraper|headlesschrome|phantomjs|selenium|playwright|puppeteer|lighthouse|pagespeed|curl\\/|wget\\/|python-requests|go-http-client|java\\/|facebookexternalhit|previewbot)/i',$userAgent);
}
function track(string $event,?int $resource=null,?string $detail=null):void {
    if(($_SERVER['HTTP_X_TIBA_LOAD']??'')==='products'||PHP_SAPI==='cli'||($_SERVER['REQUEST_METHOD']??'GET')==='HEAD'||(user()['role']??'')==='admin'||setting('analytics_enabled','1')!=='1'||($_SERVER['HTTP_DNT']??'')==='1')return;
    if(strtolower((string)($_SERVER['HTTP_PURPOSE']??$_SERVER['HTTP_SEC_PURPOSE']??''))==='prefetch')return;
    $ua=substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,1000);if(traffic_is_automated($ua))return;
    $page=basename($_SERVER['SCRIPT_NAME']??'index.php');if(strpos($_SERVER['SCRIPT_NAME']??'','/admin/')!==false)return;
    $visitor=$_COOKIE['tiba_visitor']??'';if(!preg_match('/^[a-f0-9]{64}$/',$visitor)){$visitor=bin2hex(random_bytes(32));setcookie('tiba_visitor',$visitor,['expires'=>time()+86400*180,'path'=>url(),'httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);$_COOKIE['tiba_visitor']=$visitor;}
    db('INSERT INTO analytics_visitors(id) VALUES (?) ON DUPLICATE KEY UPDATE last_seen=NOW()',[$visitor]);
    $country=traffic_country_from_ip(traffic_client_ip());
    if(empty($_SESSION['analytics_session'])||($_SESSION['analytics_seen']??0)<time()-1800){$_SESSION['analytics_session']=bin2hex(random_bytes(32));$device=preg_match('/ipad|tablet/i',$ua)?'Tablet':(preg_match('/mobile|android/i',$ua)?'Mobile':'Desktop');$browser=preg_match('/Edg/i',$ua)?'Edge':(preg_match('/Firefox/i',$ua)?'Firefox':(preg_match('/Chrome/i',$ua)?'Chrome':(preg_match('/Safari/i',$ua)?'Safari':'Other')));$os=preg_match('/Windows/i',$ua)?'Windows':(preg_match('/Android/i',$ua)?'Android':(preg_match('/iPhone|iPad/i',$ua)?'iOS':(preg_match('/Mac/i',$ua)?'macOS':(preg_match('/Linux/i',$ua)?'Linux':'Other'))));
        $ref=substr(parse_url($_SERVER['HTTP_REFERER']??'',PHP_URL_HOST)??'',0,190);if($ref===($_SERVER['HTTP_HOST']??''))$ref='';
        db('INSERT INTO analytics_sessions(id,visitor_id,user_id,entry_page,exit_page,referrer,source,medium,campaign,device,browser,os,country) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',[$_SESSION['analytics_session'],$visitor,user()['id']??null,$page,$page,$ref,substr($_GET['utm_source']??'',0,120),substr($_GET['utm_medium']??'',0,120),substr($_GET['utm_campaign']??'',0,120),$device,$browser,$os,$country?:null]);}
    $_SESSION['analytics_seen']=time();db('UPDATE analytics_sessions SET last_seen=NOW(),exit_page=?,user_id=?,country=COALESCE(NULLIF(country,""),NULLIF(?,"")) WHERE id=?',[$page,user()['id']??null,$country,$_SESSION['analytics_session']]);
    db('INSERT INTO analytics_events(session_id,event,page,resource_id,detail) VALUES (?,?,?,?,?)',[$_SESSION['analytics_session'],$event,$page,$resource,$detail===null?null:mb_substr($detail,0,190)]);
}
