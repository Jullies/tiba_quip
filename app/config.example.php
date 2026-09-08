<?php
// Copy to config.local.php and supply destination credentials; deny HTTP access.
return [
    'host' => 'localhost',
    'user' => 'YOUR_DESTINATION_DATABASE_USER',
    'password' => 'YOUR_DESTINATION_DATABASE_PASSWORD',
    'database' => 'tiba_new',
    'base_path' => '/tiba_quip',
    'environment' => 'local',
];
