<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
putenv('TIBA_DB_MIGRATION=1');
require __DIR__.'/../control/core/database/connect.php';
$conn->multi_query(file_get_contents(__DIR__.'/../database/001_schema.sql'));
do{if($r=$conn->store_result())$r->free();}while($conn->more_results()&&$conn->next_result());
if(!$conn->query("SHOW COLUMNS FROM users LIKE 'session_version'")->num_rows)$conn->query('ALTER TABLE users ADD COLUMN session_version int unsigned NOT NULL DEFAULT 0');
if(!$conn->query("SHOW COLUMNS FROM addresses LIKE 'source_key'")->num_rows){$conn->query('ALTER TABLE addresses ADD COLUMN source_key varchar(40) NULL');$conn->query('ALTER TABLE addresses ADD UNIQUE KEY user_source_address(user_id,source_key)');}
echo "Native schema installed.\n";
