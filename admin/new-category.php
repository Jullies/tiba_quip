<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
redirect('admin/manage.php?resource=categories&edit=1' . (isset($_GET['id']) ? '&id=' . (int)$_GET['id'] : ''));
