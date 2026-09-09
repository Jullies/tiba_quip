<?php
$whatsappLink = trim(setting('social_whatsapp'));
if (!preg_match('~^https://(?:wa\.me|(?:api|web)\.whatsapp\.com)/~i', $whatsappLink)) {
    $whatsappPhone = preg_replace('/\D/', '', setting('phone'));
    if (preg_match('/^0[17]\d{8}$/', $whatsappPhone)) {
        $whatsappPhone = '254' . substr($whatsappPhone, 1);
    } elseif (preg_match('/^[17]\d{8}$/', $whatsappPhone)) {
        $whatsappPhone = '254' . $whatsappPhone;
    } elseif (strpos($whatsappPhone, '00') === 0) {
        $whatsappPhone = substr($whatsappPhone, 2);
    }
    $whatsappLink = preg_match('/^[1-9]\d{7,14}$/', $whatsappPhone)
        ? 'https://wa.me/' . $whatsappPhone . '?text=' . rawurlencode('Hello ' . setting('site_name') . ', I would like to enquire about your products.')
        : '';
}
?>
<?php if ($whatsappLink !== ''): ?>
<a class="tq-whatsapp-float" href="<?= h($whatsappLink) ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp" title="Chat with us on WhatsApp"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
<?php endif; ?>
