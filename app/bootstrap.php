<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    http_response_code(500);
    exit('Tiba Quip requires PHP 7.4 or newer. Select PHP 8.1 or newer for this domain in DirectAdmin.');
}
require_once __DIR__ . '/../control/core/database/connect.php';
date_default_timezone_set('Africa/Nairobi');
if (PHP_SAPI !== 'cli') {
    ob_start();
    ini_set('display_errors','0'); ini_set('log_errors','1');
    ini_set('session.use_strict_mode','1');
    session_name('tiba_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>app_base_path().'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
    if(session_status()===PHP_SESSION_NONE) session_start();
    header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin'); header('X-Frame-Options: SAMEORIGIN');
    set_exception_handler(function(Throwable $e){ error_log((string)$e); while(ob_get_level()) ob_end_clean(); http_response_code(500); echo 'Unable to complete this request. Please try again.'; });
}
function db(string $sql,array $params=[]): mysqli_stmt { global $conn; $s=$conn->prepare($sql); if($params) $s->bind_param(str_repeat('s',count($params)),...$params); $s->execute();return $s; }
function rows(string $sql,array $params=[]): array { return db($sql,$params)->get_result()->fetch_all(MYSQLI_ASSOC); }
function row(string $sql,array $params=[]): ?array { return db($sql,$params)->get_result()->fetch_assoc(); }
function scalar(string $sql,array $params=[]){$r=row($sql,$params);return $r ? reset($r):null;}
function h($s): string{return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function app_base_path():string{
    static $base=null;
    if($base!==null)return $base;
    $script='/'.ltrim(str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php'),'/');
    $base=preg_replace('~/(?:admin/|ajax/)?[^/]+$~','',$script)??'';
    return $base=rtrim($base,'/');
}
function url(string $path=''):string{return app_base_path().'/'.ltrim($path,'/');}
function redirect(string $path):void{header('Location: '.url($path));exit;}
function setting(string $key,string $default=''):string{static $values=null;if($values===null)$values=array_column(rows('SELECT * FROM settings'),'value','key');return $values[$key]??$default;}
function set_setting(string $k,string $v):void{db('INSERT INTO settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$k,$v]);}
function money($n):string{return setting('currency','KES').' '.number_format((float)$n,2);}
/**
 * Finds published products even when the search words contain small spelling errors.
 * Exact and partial matches are ranked before close spellings, so normal searches keep
 * their expected order while queries such as "weelchair" still find "wheelchair".
 */
function smart_product_search(string $query):array{
    $query=mb_strtolower(trim($query),'UTF-8');
    if($query==='')return [];
    $queryWords=preg_split('/[^\p{L}\p{N}]+/u',$query,-1,PREG_SPLIT_NO_EMPTY);
    if(!$queryWords)return [];
    $compactQuery=implode('',$queryWords);
    $products=rows('SELECT p.id,p.name,p.sku,p.tags,p.short_description,GROUP_CONCAT(DISTINCT c.name SEPARATOR " ") category_names FROM products p LEFT JOIN product_categories pc ON pc.product_id=p.id LEFT JOIN categories c ON c.id=pc.category_id AND c.active=1 WHERE p.status="publish" GROUP BY p.id');
    $ranked=[];
    foreach($products as $product){
        $name=mb_strtolower((string)$product['name'],'UTF-8');
        $text=mb_strtolower(implode(' ',[$product['name'],$product['sku'],$product['tags'],strip_tags((string)$product['short_description']),$product['category_names']??'']),'UTF-8');
        if(mb_strpos($name,$query,0,'UTF-8')!==false){$ranked[]=['id'=>(int)$product['id'],'score'=>100];continue;}
        if(mb_strpos($text,$query,0,'UTF-8')!==false){$ranked[]=['id'=>(int)$product['id'],'score'=>98];continue;}
        $words=preg_split('/[^\p{L}\p{N}]+/u',$text,-1,PREG_SPLIT_NO_EMPTY);
        $nameWords=preg_split('/[^\p{L}\p{N}]+/u',$name,-1,PREG_SPLIT_NO_EMPTY);
        // Treat spaces, hyphens and accidental word breaks as optional. This makes
        // "wheel chair", "weel chair" and "wheel-chair" comparable to "wheelchair".
        if(mb_strlen($compactQuery,'UTF-8')>=4){
            $compactCandidates=$words;$nameCandidates=$nameWords;
            for($i=0,$wordCount=count($words);$i<$wordCount;$i++){
                if(isset($words[$i+1]))$compactCandidates[]=$words[$i].$words[$i+1];
                if(isset($words[$i+2]))$compactCandidates[]=$words[$i].$words[$i+1].$words[$i+2];
            }
            for($i=0,$wordCount=count($nameWords);$i<$wordCount;$i++){
                if(isset($nameWords[$i+1]))$nameCandidates[]=$nameWords[$i].$nameWords[$i+1];
                if(isset($nameWords[$i+2]))$nameCandidates[]=$nameWords[$i].$nameWords[$i+1].$nameWords[$i+2];
            }
            $compactBest=0;$nameCompactBest=0;$queryLength=mb_strlen($compactQuery,'UTF-8');
            foreach($compactCandidates as $candidate){
                if($candidate===$compactQuery){$compactBest=100;break;}
                $candidateLength=mb_strlen($candidate,'UTF-8');$length=max($queryLength,$candidateLength);
                if($length>50||abs($queryLength-$candidateLength)>max(3,(int)ceil($length*.35)))continue;
                $distance=levenshtein($compactQuery,$candidate);
                $compactBest=max($compactBest,(int)round((1-($distance/$length))*100));
            }
            foreach($nameCandidates as $candidate){
                $candidateLength=mb_strlen($candidate,'UTF-8');$length=max($queryLength,$candidateLength);
                if(!$length||$length>50||abs($queryLength-$candidateLength)>max(3,(int)ceil($length*.35)))continue;
                $nameCompactBest=max($nameCompactBest,(int)round((1-(levenshtein($compactQuery,$candidate)/$length))*100));
            }
            if($compactBest>=70){$ranked[]=['id'=>(int)$product['id'],'score'=>min(97,max($compactBest,$nameCompactBest+3))];continue;}
        }
        $scores=[];
        foreach($queryWords as $needle){
            $best=0;$needleLength=mb_strlen($needle,'UTF-8');
            foreach($words as $word){
                if($word===$needle){$best=100;break;}
                if($needleLength>=3&&mb_strpos($word,$needle,0,'UTF-8')!==false){$best=max($best,92);continue;}
                $wordLength=mb_strlen($word,'UTF-8');$length=max($needleLength,$wordLength);
                if($length<3||$length>50||abs($needleLength-$wordLength)>max(3,(int)ceil($length*.4)))continue;
                $distance=levenshtein($needle,$word);
                $best=max($best,(int)round((1-($distance/$length))*100));
            }
            $scores[]=$best;
        }
        $score=array_sum($scores)/count($scores);
        $ranked[]=['id'=>(int)$product['id'],'score'=>(int)round($score)];
    }
    usort($ranked,static fn(array $a,array $b):int=>$b['score']<=>$a['score']?:$a['id']<=>$b['id']);
    $strong=array_values(array_filter($ranked,static fn(array $match):bool=>$match['score']>=65));
    return $strong?:array_slice($ranked,0,12);
}
function smart_product_search_ids(string $query):array{return array_column(smart_product_search($query),'id');}
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
function site_url():string{
    $secure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||((string)($_SERVER['SERVER_PORT']??'')==='443');
    $host=(string)($_SERVER['HTTP_HOST']??'localhost');
    if(!preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/i',$host))$host='localhost';
    return ($secure?'https':'http').'://'.$host.url();
}
function absolute_url(string $path=''):string{return rtrim(site_url(),'/').'/'.ltrim($path,'/');}
function clean_html(string $html):string {
    if(trim($html)==='')return '';
    $d=new DOMDocument();libxml_use_internal_errors(true);$d->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();
    $allowed=['div','p','br','strong','b','em','i','u','ul','ol','li','h2','h3','h4','h5','h6','blockquote','a','img','table','thead','tbody','tr','td','th','span','figure','figcaption','hr'];
    $nodes=[];foreach($d->getElementsByTagName('*') as $n)$nodes[]=$n;
    foreach(array_reverse($nodes) as $n){if(!in_array(strtolower($n->nodeName),$allowed)){if(in_array(strtolower($n->nodeName),['script','style','iframe','object','form','input','button'])){if($n->parentNode)$n->parentNode->removeChild($n);}else{while($n->firstChild)$n->parentNode->insertBefore($n->firstChild,$n);if($n->parentNode)$n->parentNode->removeChild($n);}continue;}
        foreach(iterator_to_array($n->attributes) as $a){if(!in_array($a->name,['href','src','alt','title','colspan','rowspan'])||(($a->name==='href'||$a->name==='src')&&safe_link($a->value)==='#'))$n->removeAttribute($a->name);}
        if($n->nodeName==='img'&&!$n->getAttribute('src')){if($n->parentNode)$n->parentNode->removeChild($n);continue;}
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

require_once __DIR__.'/reviews.php';
