<?php
$adminUser=user();
$adminInitial=mb_strtoupper(mb_substr($adminUser['name']??'A',0,1));
$adminSubtitles=[
 'dashboard'=>'A live view of store performance and customer activity',
 'analytics'=>'Explore sales, traffic and conversion performance',
 'manage'=>'Create, organize and maintain your store content',
 'orders'=>'Review purchases, payments and fulfillment',
 'customers'=>'Manage customer accounts and order history',
 'admins'=>'Manage administrator access and password resets',
 'settings'=>'Configure storefront, payment and business information',
 'reviews'=>'Moderate product and business feedback from clients',
 'messages'=>'Read enquiries sent from the storefront',
 'audit'=>'Review administrator actions and account activity'
];
$adminNav=[
 ['index.php','grid','Dashboard',$adminSection==='dashboard'],
 ['manage.php?resource=products','shopping-cart','Products',$adminSection==='manage'&&$resource==='products'],
 ['manage.php?resource=categories','layers','Categories',$adminSection==='manage'&&$resource==='categories'],
 ['orders.php','file-text','Orders',$adminSection==='orders'],
 ['customers.php','users','Customers',$adminSection==='customers'],
 ['admins.php','shield','Administrators',$adminSection==='admins'],
 ['manage.php?resource=posts','edit','Blog posts',$adminSection==='manage'&&$resource==='posts'],
 ['manage.php?resource=blog-categories','list','Blog categories',$adminSection==='manage'&&$resource==='blog-categories'],
 ['manage.php?resource=pages','file','Pages',$adminSection==='manage'&&$resource==='pages'],
 ['manage.php?resource=banners','image','Banners',$adminSection==='manage'&&$resource==='banners'],
 ['settings.php','settings','Settings',$adminSection==='settings'],
 ['reviews.php?type=company','star','Reviews',$adminSection==='reviews'],
 ['analytics.php','bar-chart','Analytics',$adminSection==='analytics'],
 ['messages.php','mail','Messages',$adminSection==='messages'],
 ['audit.php','clock','Activity log',$adminSection==='audit']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#073b4c">
<title><?= h($title.' | '.setting('site_name')) ?></title>
<link rel="icon" href="<?= h(url(setting('favicon'))) ?>">
<?php foreach(['css/bootstrap.css','css/animate.min.css','css/animation.css','css/style.css','css/jd-premium.css','font/fonts.css','icon/style.css','css/store-admin.css','css/tiba-premium.css'] as $f): ?><link rel="stylesheet" href="<?= url('admin/'.$f) ?>"><?php endforeach; ?>
</head>
<body class="body jd-admin-page tq-admin admin-section-<?= h($adminSection) ?>">
<div id="wrapper"><div id="page"><div class="layout-wrap">
<aside class="section-menu-left">
 <div class="box-logo"><a class="tq-admin-brand" href="<?= url('admin/index.php') ?>"><img src="<?= h(url(setting('logo'))) ?>" alt="<?= h(setting('site_name')) ?>"><span><strong><?= h(setting('site_name')) ?></strong><small>Store administration</small></span></a><div class="button-show-hide"><i class="icon-menu-left"></i></div></div>
 <div class="section-menu-left-wrap"><div class="center"><div class="center-item"><div class="center-heading">Store management</div><ul class="menu-list">
 <?php foreach($adminNav as [$route,$icon,$label,$active]): ?><li class="menu-item <?= $active?'active':'' ?>"><a class="<?= $active?'active':'' ?>" <?= $active?'aria-current="page"':'' ?> href="<?= url('admin/'.$route) ?>"><div class="icon"><i class="icon-<?= $icon ?>"></i></div><div class="text"><?= $label ?></div><?php if($active): ?><span class="tq-nav-indicator"></span><?php endif; ?></a></li><?php endforeach; ?>
 </ul></div></div></div>
</aside>
<div class="section-content-right">
 <header class="header-dashboard"><div class="wrap"><div class="header-left"><div class="button-show-hide"><i class="icon-menu-left"></i></div><a class="tq-store-link" href="<?= url() ?>" target="_blank" rel="noopener"><i class="icon-external-link"></i><span>Open storefront</span></a></div><div class="header-grid"><div class="tq-admin-user"><span class="tq-admin-avatar"><?= h($adminInitial) ?></span><span><strong><?= h($adminUser['name']) ?></strong><small>Administrator</small></span></div><form method="post" action="<?= url('admin/logout.php') ?>"><?= csrf_field() ?><button class="tq-logout" aria-label="Log out"><i class="icon-log-out"></i><span>Logout</span></button></form></div></div></header>
 <main class="main-content"><div class="main-content-inner"><div class="main-content-wrap">
 <div class="tq-page-heading"><div><span class="tq-eyebrow">Tiba Quip Admin</span><h3><?= h($title) ?></h3><p><?= h($adminSubtitles[$adminSection]??'Manage your ecommerce operations') ?></p></div><span class="tq-page-date"><i class="icon-calendar"></i><?= h(date('D, d M Y')) ?></span></div>
 <?php if($error): ?><div class="alert alert-danger tq-alert" role="alert"><i class="icon-alert-circle"></i><span><?= h($error) ?></span></div><?php endif; ?>
 <?php if(!empty($_SESSION['flash'])): ?><div class="alert alert-success tq-alert" role="status"><i class="icon-check-circle"></i><span><?= h($_SESSION['flash']) ?></span></div><?php unset($_SESSION['flash']);endif; ?>
