(function () {
    document.addEventListener('DOMContentLoaded', function () {

        const images = document.querySelectorAll('img');

        images.forEach(function (img) {

            // ❌ Skip LCP / critical images
            if (
                img.classList.contains('no-lazy') ||
                img.classList.contains('lcp-image') ||
                img.hasAttribute('loading')
            ) {
                return;
            }

            // ✅ Lazy load everything else
            img.setAttribute('loading', 'lazy');
            img.setAttribute('decoding', 'async');
        });

    });
})();
