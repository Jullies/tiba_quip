<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
redirect('admin/analytics.php' . (isset($_GET['id']) ? '?id=' . (int)$_GET['id'] : ''));
