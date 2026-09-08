<?php foreach(['jquery-3.7.1.min','modernizr.min','bootstrap.bundle.min','imagesloaded.pkgd.min','jquery.magnific-popup.min','isotope.pkgd.min','jquery.appear.min','jquery.easing.min','owl.carousel.min','counter-up','jquery-ui.min','jquery.nice-select.min','countdown.min','wow.min','main'] as $script): ?><script src="<?= url('assets/js/'.$script.'.js') ?>"></script><?php endforeach; ?>
<script src="<?= url('assets/js/store.js') ?>"></script>
<script src="<?= url('assets/js/shopping.js') ?>"></script>
<?php require __DIR__.'/whatsapp.php'; ?>
</body></html>
