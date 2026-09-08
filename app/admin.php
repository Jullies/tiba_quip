<?php
require_once __DIR__.'/admin-functions.php';
$adminSection=$adminSection??'dashboard';$error='';
if($adminSection==='login'){
 if((user()['role']??'')==='admin')redirect('admin/index.php');
 if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();try{if(login_user(trim($_POST['email']??''),$_POST['password']??'',true))redirect('admin/index.php');$error='The supplied admin credentials are not valid.';}catch(RuntimeException $e){$error=$e->getMessage();}}
 require __DIR__.'/views/admin-login.php';exit;
}
require_admin();
if($adminSection==='logout'){
 if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Logout requires a submitted form.');}check_csrf();audit('logout','auth');$_SESSION=[];session_regenerate_id(true);redirect('admin/login.php');
}
$resource=$_GET['resource']??'products';if(!isset(admin_fields()[$resource]))$resource='products';$id=max(0,(int)($_GET['id']??$_POST['id']??0));
$settingFields=['site_name','tagline','email','phone','phone_alt','address','country','business_hours','currency','shipping_fee','free_shipping_threshold','tax_rate','payment_cod','payment_bank_transfer','bank_instructions','social_facebook','social_instagram','social_twitter','social_youtube','social_tiktok','social_whatsapp','social_linkedin','home_sections','analytics_enabled','meta_title','meta_description','site_url'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();try{
 $action=$_POST['action']??'';
 if($action==='moderate_review'){$status=$_POST['status']??'';if(!in_array($status,['published','hidden']))throw new RuntimeException('Invalid review status.');db('UPDATE product_reviews SET status=? WHERE id=?',[$status,$id]);audit('review_'.$status,'reviews',$id);flash('Review updated.');redirect('admin/reviews.php');}
 if($action==='save'){$id=admin_save($resource,$id);flash('Saved successfully.');redirect('admin/manage.php?resource='.urlencode($resource).'&id='.$id.'&edit=1');}
 if($action==='archive'){admin_archive($resource,$id);flash('Record archived or removed.');redirect('admin/manage.php?resource='.urlencode($resource));}
 if($action==='order'){update_order($id);flash('Order updated.');redirect('admin/orders.php?id='.$id);}
 if($action==='customer'){$active=isset($_POST['active'])?1:0;db('UPDATE users SET name=?,phone=?,active=? WHERE id=? AND role="customer"',[mb_substr(trim($_POST['name']??''),0,190),mb_substr($_POST['phone']??'',0,60),$active,$id]);audit('updated','customers',$id);flash('Customer updated.');redirect('admin/customers.php?id='.$id);}
 if($action==='settings'){$values=[];foreach($settingFields as $k){$v=trim($_POST[$k]??'');if(in_array($k,['payment_cod','payment_bank_transfer','analytics_enabled']))$v=isset($_POST[$k])?'1':'0';if(in_array($k,['shipping_fee','free_shipping_threshold','tax_rate'])&&(!is_numeric($v)||$v<0||($k==='tax_rate'&&$v>100)))throw new RuntimeException('Invalid '.$k);if($k==='email'&&!filter_var($v,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid store email.');if(str_starts_with($k,'social_')&&$v!==''&&!filter_var($v,FILTER_VALIDATE_URL))throw new RuntimeException('Social links must be complete URLs.');if($k==='currency'&&!preg_match('/^[A-Z]{3}$/',$v))throw new RuntimeException('Currency must be a three-letter code.');if($k==='site_url'&&(!filter_var($v,FILTER_VALIDATE_URL)||!preg_match('~^https?://~',$v)))throw new RuntimeException('Enter a valid absolute store URL ending in /.');$values[$k]=$k==='site_url'?rtrim($v,'/').'/':$v;}foreach(['logo','favicon'] as $field){$image=upload($field);if($image)$values[$field]=$image;}$conn->begin_transaction();try{foreach($values as $k=>$v)set_setting($k,$v);audit('updated','settings');$conn->commit();}catch(Throwable $e){$conn->rollback();throw $e;}flash('Settings saved.');redirect('admin/settings.php');}
 }catch(mysqli_sql_exception $e){error_log((string)$e);$error='Unable to save. Check for duplicate slugs/SKUs or invalid field values.';}catch(RuntimeException $e){$error=$e->getMessage();}
}
$title=['reviews'=>'Product reviews','dashboard'=>'Dashboard','analytics'=>'Traffic & ecommerce analytics','manage'=>admin_fields()[$resource]['label'],'orders'=>'Orders','customers'=>'Customers','settings'=>'Website settings','audit'=>'Admin activity','messages'=>'Contact messages'][$adminSection]??'Administration';
require __DIR__.'/views/admin-head.php';
if($adminSection==='reviews')require __DIR__.'/views/admin-reviews.php';
elseif($adminSection==='manage')require __DIR__.'/views/admin-manage.php';
elseif(in_array($adminSection,['dashboard','analytics']))require __DIR__.'/views/admin-analytics.php';
else require __DIR__.'/views/admin-records.php';
require __DIR__.'/views/admin-footer.php';
