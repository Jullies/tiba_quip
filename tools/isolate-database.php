<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../control/core/database/connect.php';
if($config['user']==='tiba_quip_runtime'){echo "Runtime is already isolated.\n";exit;}
$account='tiba_quip_runtime';$password=bin2hex(random_bytes(32));$database=$config['database'];if(!preg_match('/^[a-zA-Z0-9_]+$/',$database))throw new RuntimeException('Invalid database name.');
$conn->query("CREATE USER IF NOT EXISTS '$account'@'localhost' IDENTIFIED BY '$password'");
$conn->query("ALTER USER '$account'@'localhost' IDENTIFIED BY '$password'");
$conn->query("GRANT SELECT, INSERT, UPDATE, DELETE ON `$database`.* TO '$account'@'localhost'");
file_put_contents(__DIR__.'/../app/migration.local.php',"<?php\nreturn ".var_export($config,true).";\n");
$config['user']=$account;$config['password']=$password;
file_put_contents(__DIR__.'/../app/config.local.php',"<?php\nreturn ".var_export($config,true).";\n");
$native=new mysqli($config['host'],$account,$password,$database);$native->query('SELECT COUNT(*) FROM products');
try{$native->query('SELECT COUNT(*) FROM tiba_old.wpd4_posts');throw new RuntimeException('Unexpected source database access.');}catch(mysqli_sql_exception $e){echo "Runtime account can access native tables and is denied access to WordPress tables.\n";}
