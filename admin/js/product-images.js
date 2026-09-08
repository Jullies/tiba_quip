(function () {
    'use strict';
    var root = new URL('../', window.location.href);
    var fallback = new URL('admin/images/product-placeholder.svg', root).href;
    window.jdAdminImageUrl = function (value) {
        var path = String(value || '').trim().replace(/\\/g, '/');
        if (!path) return fallback;
        if (/^https?:\/\//i.test(path)) return path;
        if (/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i.test(path)) return fallback;
        if (path.indexOf(root.pathname) === 0) return new URL(path, root.origin).href;
        path = path.replace(/^(?:\.\.\/)+/, '').replace(/^\/+/, '');
        var url = new URL(path, root);
        return url.origin === root.origin && url.pathname.indexOf(root.pathname) === 0 ? url.href : fallback;
    };
    document.addEventListener('error', function (event) {
        var img = event.target;
        if (img.tagName === 'IMG' && img.hasAttribute('data-product-image') && img.src !== fallback) {
            img.src = fallback;
        }
    }, true);
})();
