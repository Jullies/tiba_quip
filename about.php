<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$currentPage = 'about.php';
$pageTitle = 'About ' . setting('site_name', 'Tiba Quip Africa');
$metaDescription = setting('tagline', 'Medical equipment suppliers in East Africa');
$catalogueCategories = rows('SELECT name,slug,image FROM categories WHERE active=1 AND parent_id IS NULL ORDER BY sort_order,name LIMIT 8');
$productCount = (int) scalar('SELECT COUNT(*) FROM products WHERE status="publish"');
$categoryCount = (int) scalar('SELECT COUNT(*) FROM categories WHERE active=1');
$phone = setting('phone');
$alternatePhone = setting('phone_alt');
$phoneHref = preg_replace('/[^0-9+]/', '', $phone);
$alternatePhoneHref = preg_replace('/[^0-9+]/', '', $alternatePhone);

track('page_view');

require __DIR__ . '/overall/head.php';
require __DIR__ . '/overall/header.php';
?>
<main class="main tq-about-page">
    <section class="site-breadcrumb">
        <div class="site-breadcrumb-bg" style="background-image:url(<?= h(url('assets/img/breadcrumb/01.jpg')) ?>)"></div>
        <div class="container">
            <div class="site-breadcrumb-wrap">
                <h1 class="breadcrumb-title">About <?= h(setting('site_name', 'Tiba Quip Africa')) ?></h1>
                <ul class="breadcrumb-menu">
                    <li><a href="<?= h(url()) ?>"><i class="far fa-home"></i> Home</a></li>
                    <li class="active">About us</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="py-90">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="text-uppercase fw-semibold" style="color:#00a7a0;letter-spacing:.08em">Tiba Quip Africa</span>
                    <h2 class="mt-3 mb-4">Medical equipment and supplies for customers across East Africa.</h2>
                    <p class="mb-3"><?= h(setting('site_name', 'Tiba Quip Africa')) ?> is based in Nairobi and provides a focused catalogue of medical equipment, laboratory items and related supplies.</p>
                    <p class="mb-4">We make it easier to browse products online, compare available options and contact our team directly when you need help with an enquiry.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a class="theme-btn" href="<?= h(url('shop.php')) ?>">Browse products <i class="far fa-arrow-right"></i></a>
                        <a class="theme-btn theme-btn2" href="<?= h(url('contact.php')) ?>">Talk to our team</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="border rounded-4 bg-white p-4 p-md-5 shadow-sm text-center">
                        <img src="<?= h(url(setting('logo', 'assets/img/logo/logo.png'))) ?>" alt="<?= h(setting('site_name', 'Tiba Quip Africa')) ?>" style="max-width:240px;max-height:110px;object-fit:contain" class="mb-4">
                        <p class="lead mb-0"><?= h(setting('tagline', 'Medical equipment suppliers in East Africa')) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-90">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-6 col-lg-3"><div class="border rounded-4 h-100 p-4 bg-white"><strong class="d-block fs-2" style="color:#00a7a0"><?= number_format($productCount) ?></strong><span>Products in the catalogue</span></div></div>
                <div class="col-6 col-lg-3"><div class="border rounded-4 h-100 p-4 bg-white"><strong class="d-block fs-2" style="color:#00a7a0"><?= number_format($categoryCount) ?></strong><span>Active product categories</span></div></div>
                <div class="col-6 col-lg-3"><div class="border rounded-4 h-100 p-4 bg-white"><strong class="d-block fs-2" style="color:#00a7a0">Nairobi</strong><span>Our business location</span></div></div>
                <div class="col-6 col-lg-3"><div class="border rounded-4 h-100 p-4 bg-white"><strong class="d-block fs-2" style="color:#00a7a0">Mon–Sat</strong><span><?= h(setting('business_hours')) ?></span></div></div>
            </div>
        </div>
    </section>

    <?php if ($catalogueCategories): ?>
        <section class="bg-light py-90">
            <div class="container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5">
                    <div><span class="text-uppercase fw-semibold" style="color:#00a7a0;letter-spacing:.08em">Our catalogue</span><h2 class="mt-2 mb-0">Browse by category</h2></div>
                    <a class="theme-btn" href="<?= h(url('shop.php')) ?>">View all products <i class="far fa-arrow-right"></i></a>
                </div>
                <div class="row g-4">
                    <?php foreach ($catalogueCategories as $category): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a class="d-block h-100 border rounded-4 bg-white p-3 text-center text-decoration-none" href="<?= h(url('shop.php?category=' . rawurlencode($category['slug']))) ?>">
                                <?php if (!empty($category['image'])): ?><img loading="lazy" src="<?= h(url($category['image'])) ?>" alt="" style="height:100px;width:100%;object-fit:contain" class="mb-3"><?php endif; ?>
                                <strong><?= h($category['name']) ?></strong>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="py-90">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6"><div class="border rounded-4 h-100 p-4 p-md-5"><h3 class="mb-4">Visit or contact us</h3><p class="mb-3"><i class="far fa-map-marker-alt me-2" style="color:#00a7a0"></i><?= h(setting('address')) ?></p><p class="mb-3"><i class="far fa-envelope me-2" style="color:#00a7a0"></i><a href="mailto:<?= h(setting('email')) ?>"><?= h(setting('email')) ?></a></p><p class="mb-0"><i class="far fa-clock me-2" style="color:#00a7a0"></i><?= h(setting('business_hours')) ?></p></div></div>
                <div class="col-lg-6"><div class="border rounded-4 h-100 p-4 p-md-5"><h3 class="mb-4">Speak with Tiba Quip Africa</h3><p class="mb-4">For product availability, quotations or general enquiries, contact our team by phone or send us a message.</p><div class="d-flex flex-wrap gap-3"><a class="theme-btn" href="tel:<?= h($phoneHref) ?>"><i class="far fa-phone"></i> <?= h($phone) ?></a><?php if ($alternatePhone): ?><a class="btn" href="tel:<?= h($alternatePhoneHref) ?>"><?= h($alternatePhone) ?></a><?php endif; ?><a class="btn" href="<?= h(url('contact.php')) ?>">Send an enquiry</a></div></div></div>
            </div>
        </div>
    </section>
</main>
<?php
require __DIR__ . '/overall/footer.php';
require __DIR__ . '/overall/scripts.php';
