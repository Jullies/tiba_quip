<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$email=$argv[1]??'';$name=$argv[2]??'Store administrator';if(!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Usage: php tools/create-admin.php email [name]\nSet TIBA_ADMIN_PASSWORD or read the generated password in protected storage/admin-access.txt.\n");exit(1);}
$password=getenv('TIBA_ADMIN_PASSWORD')?:bin2hex(random_bytes(12));if(strlen($password)<12)throw new RuntimeException('Use at least 12 characters.');
$u=row('SELECT id FROM users WHERE email=?',[$email]);if($u)db('UPDATE users SET name=?,role="admin",active=1,password_hash=?,session_version=session_version+1 WHERE id=?',[$name,password_hash($password,PASSWORD_DEFAULT),$u['id']]);else db('INSERT INTO users(name,email,role,password_hash) VALUES (?,?,"admin",?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
if(!getenv('TIBA_ADMIN_PASSWORD'))file_put_contents(__DIR__.'/../storage/admin-access.txt',"Local admin URL: http://localhost".url('admin/login.php')."\nEmail: ".$email."\nPassword: ".$password."\nChange the password after login through the customer account password page.\n");
echo "Admin account is ready. Generated credentials, if used, are in protected storage/admin-access.txt.\n";
