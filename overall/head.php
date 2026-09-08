<?php require_once __DIR__.'/bootstrap.php'; ?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h(($pageTitle??setting('site_name')).' | '.setting('site_name')) ?></title>
<meta name="description" content="<?= h($metaDescription??setting('meta_description',setting('tagline'))) ?>">
<link rel="canonical" href="<?= h($canonical??absolute_url($currentPage)) ?>">
<meta property="og:title" content="<?= h($pageTitle??setting('site_name')) ?>"><meta property="og:description" content="<?= h($metaDescription??setting('tagline')) ?>"><meta property="og:type" content="website">
<?php if(!empty($ogImage)): ?><meta property="og:image" content="<?= h(absolute_url($ogImage)) ?>"><?php endif; ?>
<link rel="icon" href="<?= h(url(setting('favicon','assets/img/logo/favicon.png'))) ?>">
<?php foreach(['bootstrap.min','all-fontawesome.min','animate.min','magnific-popup.min','owl.carousel.min','jquery-ui.min','nice-select.min','style','store','shopping','reviews'] as $css): ?><link rel="stylesheet" href="<?= h(url('assets/css/'.$css.'.css')) ?>"><?php endforeach; ?>
</head><body class="<?= h($bodyClass??'') ?>">
