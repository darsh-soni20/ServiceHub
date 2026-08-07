(function() {
    // 1. Auto-dismiss stuck preloader across all pages
    function hidePreloader() {
        const p = document.getElementById('preloader-active');
        if (p) {
            p.style.display = 'none';
        }
        if (document.body) {
            document.body.style.overflow = 'visible';
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        hidePreloader();
    } else {
        document.addEventListener('DOMContentLoaded', hidePreloader);
    }
    window.addEventListener('load', hidePreloader);
    setTimeout(hidePreloader, 200);
    setTimeout(hidePreloader, 600);

    // 2. Save token if present in URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const urlToken = urlParams.get('token');
    if (urlToken) {
        sessionStorage.setItem('auth_token', urlToken);
    }

    // 3. Override global fetch to automatically attach X-Auth-Token
    const originalFetch = window.fetch;
    window.fetch = function(resource, init) {
        init = init || {};
        init.headers = init.headers || {};

        const token = sessionStorage.getItem('auth_token');
        if (token) {
            if (init.headers instanceof Headers) {
                if (!init.headers.has('X-Auth-Token')) {
                    init.headers.append('X-Auth-Token', token);
                }
            } else if (Array.isArray(init.headers)) {
                let hasHeader = false;
                for (let i = 0; i < init.headers.length; i++) {
                    if (init.headers[i][0] === 'X-Auth-Token') {
                        hasHeader = true;
                        break;
                    }
                }
                if (!hasHeader) {
                    init.headers.push(['X-Auth-Token', token]);
                }
            } else {
                if (!init.headers['X-Auth-Token']) {
                    init.headers['X-Auth-Token'] = token;
                }
            }
        }
        return originalFetch.call(this, resource, init);
    };
})();
