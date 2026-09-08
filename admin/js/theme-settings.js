/* Header theme control kept independently of the removed settings drawer. */
;(function ($) {
    'use strict';

    $(function () {
        var body = $('body');
        var button = $('.button-dark-light');
        var desktopLogo = $('#logo_header');
        var mobileLogo = $('#logo_header_mobile');
        var lightLogo = desktopLogo.data('light') || mobileLogo.data('light');
        var darkLogo = desktopLogo.data('dark') || mobileLogo.data('dark');

        function applyTheme(mode) {
            var dark = mode === 'dark-theme';
            body.toggleClass('dark-theme', dark);
            if (dark) {
                if (darkLogo) { desktopLogo.attr('src', darkLogo); mobileLogo.attr('src', darkLogo); }
            } else if (lightLogo) {
                desktopLogo.attr('src', lightLogo);
                mobileLogo.attr('src', lightLogo);
            }
        }

        var storedTheme = '';
        try { storedTheme = window.localStorage.getItem('toggled') || ''; } catch (e) { /* storage unavailable */ }
        applyTheme(storedTheme === 'dark-theme' ? 'dark-theme' : 'light-theme');

        button.on('click', function () {
            var nextTheme = body.hasClass('dark-theme') ? 'light-theme' : 'dark-theme';
            applyTheme(nextTheme);
            try { window.localStorage.setItem('toggled', nextTheme); } catch (e) { /* storage unavailable */ }
        });
    });
})(jQuery);
