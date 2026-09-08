<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
redirect('admin/manage.php?resource=products' . (isset($_GET['id']) ? '&id=' . (int)$_GET['id'] : ''));
