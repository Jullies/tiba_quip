<?php
require_once __DIR__ . '/../control/core/database/connect.php';
date_default_timezone_set('Africa/Nairobi');
if (PHP_SAPI !== 'cli') {
    ob_start();
    ini_set('display_errors','0'); ini_set('log_errors','1');
    ini_set('session.use_strict_mode','1');
    session_name('tiba_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>$config['base_path'].'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
    if(session_status()===PHP_SESSION_NONE) session_start();
    header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin'); header('X-Frame-Options: SAMEORIGIN');
    set_exception_handler(function(Throwable $e){ error_log((string)$e); while(ob_get_level()) ob_end_clean(); http_response_code(500); echo 'Unable to complete this request. Please try again.'; });
}
function db(string $sql,array $params=[]): mysqli_stmt { global $conn; $s=$conn->prepare($sql); if($params) $s->bind_param(str_repeat('s',count($params)),...$params); $s->execute();return $s; }
function rows(string $sql,array $params=[]): array { return db($sql,$params)->get_result()->fetch_all(MYSQLI_ASSOC); }
function row(string $sql,array $params=[]): ?array { return db($sql,$params)->get_result()->fetch_assoc(); }
function scalar(string $sql,array $params=[]){$r=row($sql,$params);return $r ? reset($r):null;}
function h($s): string{return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function url(string $path=''):string{global $config;return $config['base_path'].'/'.ltrim($path,'/');}
function redirect(string $path):void{header('Location: '.url($path));exit;}
function setting(string $key,string $default=''):string{static $values=null;if($values===null)$values=array_column(rows('SELECT * FROM settings'),'value','key');return $values[$key]??$default;}
function set_setting(string $k,string $v):void{db('INSERT INTO settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$k,$v]);}
function money($n):string{return setting('currency','KES').' '.number_format((float)$n,2);}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function csrf_field():string{return '<input type="hidden" name="csrf" value="'.h(csrf()).'">';}
function check_csrf():void{if(!hash_equals(csrf(),(string)($_POST['csrf']??''))){http_response_code(403);exit('Your form expired. Reload the page and try again.');}}
function user():?array{static $done=false,$u=null;if(!$done){$u=empty($_SESSION['user_id'])?null:row('SELECT id,name,email,phone,role,active,created_at,session_version FROM users WHERE id=? AND active=1',[$_SESSION['user_id']]);$done=true;if($u&&(int)$u['session_version']!==(int)($_SESSION['session_version']??0)){$u=null;unset($_SESSION['user_id']);}}return $u;}
function require_user():array{$u=user();if(!$u)redirect('login.php');return $u;}
function require_admin():array{$u=user();if(!$u||$u['role']!=='admin')redirect('admin/login.php');return $u;}
function audit(string $action,string $resource,?int $id=null,string $context=''):void{db('INSERT INTO audit_logs(admin_id,action,resource,resource_id,context) VALUES (?,?,?,?,?)',[user()['id']??null,$action,$resource,$id,$context]);}
function flash(string $message):void{$_SESSION['flash']=$message;}
function safe_link(string $v):string{
    $v=trim($v);
    if($v===''||preg_match('/[\x00-\x20\x7f]/',$v))return '#';
    if(preg_match('/^([a-z][a-z0-9+.-]*):/i',$v,$m)&&!in_array(strtolower($m[1]),['http','https','mailto','tel']))return '#';
    return $v;
}
function absolute_url(string $path=''):string{return rtrim(setting('site_url','http://localhost'.url()),'/').'/'.ltrim($path,'/');}
function clean_html(string $html):string {
    if(trim($html)==='')return '';
    $d=new DOMDocument();libxml_use_internal_errors(true);$d->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();
    $allowed=['div','p','br','strong','b','em','i','u','ul','ol','li','h2','h3','h4','h5','h6','blockquote','a','img','table','thead','tbody','tr','td','th','span','figure','figcaption','hr'];
    $nodes=[];foreach($d->getElementsByTagName('*') as $n)$nodes[]=$n;
    foreach(array_reverse($nodes) as $n){if(!in_array(strtolower($n->nodeName),$allowed)){if(in_array(strtolower($n->nodeName),['script','style','iframe','object','form','input','button'])){$n->parentNode?->removeChild($n);}else{while($n->firstChild)$n->parentNode->insertBefore($n->firstChild,$n);$n->parentNode?->removeChild($n);}continue;}
        foreach(iterator_to_array($n->attributes) as $a){if(!in_array($a->name,['href','src','alt','title','colspan','rowspan'])||(($a->name==='href'||$a->name==='src')&&safe_link($a->value)==='#'))$n->removeAttribute($a->name);}
        if($n->nodeName==='img'&&!$n->getAttribute('src')){$n->parentNode?->removeChild($n);continue;}
        if($n->nodeName==='img'){$n->setAttribute('loading','lazy');$n->setAttribute('style','max-width:100%;height:auto');}
    }
    return preg_replace('/<\?xml[^>]+>/','',$d->saveHTML());
}
function slug(string $value):string{$value=strtolower(trim(preg_replace('/[^\pL\pN]+/u','-',$value),'-'));return mb_substr($value?:bin2hex(random_bytes(5)),0,175);}
function upload(string $field):?string {
    if(empty($_FILES[$field])||$_FILES[$field]['error']===UPLOAD_ERR_NO_FILE)return null;
    $f=$_FILES[$field];if($f['error']!==UPLOAD_ERR_OK||$f['size']>10*1024*1024)throw new RuntimeException('Image upload failed or exceeds 10 MB.');
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])]??null;
    if(!$ext||!getimagesize($f['tmp_name']))throw new RuntimeException('Upload a valid JPEG, PNG, WebP or GIF image.');
    $path='uploads/media/'.bin2hex(random_bytes(16)).'.'.$ext;if(!is_dir(__DIR__.'/../uploads/media'))mkdir(__DIR__.'/../uploads/media',0775,true);
    if(!move_uploaded_file($f['tmp_name'],__DIR__.'/../'.$path))throw new RuntimeException('Unable to save the image.');return $path;
}
function login_user(string $email,string $password,bool $admin=false):bool {
    $identity=hash('sha256',strtolower($email).'|'.($_SERVER['REMOTE_ADDR']??'cli'));
    if((int)scalar('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$identity])>=8)throw new RuntimeException('Too many attempts. Try again in 15 minutes.');
    db('INSERT INTO login_attempts(identity_hash) VALUES (?)',[$identity]);$u=row('SELECT * FROM users WHERE email=? AND active=1',[$email]);
    if(!$u||!password_verify($password,$u['password_hash']??'')||($admin&&$u['role']!=='admin'))return false;
    session_regenerate_id(true);$_SESSION['user_id']=$u['id'];$_SESSION['session_version']=(int)$u['session_version'];$_SESSION['csrf']=bin2hex(random_bytes(32));db('UPDATE users SET last_login=NOW() WHERE id=?',[$u['id']]);db('DELETE FROM login_attempts WHERE identity_hash=?',[$identity]);
    if($admin)db('INSERT INTO audit_logs(admin_id,action,resource) VALUES (?, ?, ?)',[$u['id'],'login','auth']);return true;
}
require_once __DIR__.'/commerce.php';
require_once __DIR__.'/analytics.php';
