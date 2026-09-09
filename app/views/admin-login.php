<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#073b4c">
<title>Admin login | <?= h(setting('site_name')) ?></title><link rel="icon" href="<?= h(url(setting('favicon'))) ?>">
<?php foreach(['css/bootstrap.css','css/style.css','css/jd-premium.css','font/fonts.css','icon/style.css','css/store-admin.css','css/tiba-premium.css'] as $f): ?><link rel="stylesheet" href="<?= url('admin/'.$f) ?>"><?php endforeach; ?>
</head>
<body class="body jd-login-premium">
<div class="wrap-login-page"><div class="flex-grow flex flex-column justify-center gap30">
<a href="<?= url() ?>" class="login-brand"><img src="<?= h(url(setting('logo'))) ?>" alt="<?= h(setting('site_name')) ?>"></a>
<div class="login-box"><span class="tq-eyebrow">Secure administration</span><h3>Welcome back</h3><p class="text-tiny mt-3">Sign in to manage <?= h(setting('site_name')) ?>.</p>
<?php if($error): ?><div class="alert alert-danger mt-3" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="form-login flex flex-column gap24 mt-4"><?= csrf_field() ?>
<fieldset><label class="body-title" for="email">Email address</label><input name="email" id="email" type="email" autocomplete="username" required value="<?= h($_POST['email']??'') ?>"></fieldset>
<fieldset><label class="body-title" for="password">Password</label><input name="password" id="password" type="password" autocomplete="current-password" required></fieldset>
<button class="tf-button w-full">Sign in securely</button><a href="<?= url('forgot-password.php') ?>">Reset password</a>
</form></div></div></div>
</body></html>
