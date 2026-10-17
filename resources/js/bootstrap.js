import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const tokenMeta = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]') : null;
if (tokenMeta && tokenMeta.content) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.content;
}

// Global Safe Fetch Interceptor: Otomatis menyisipkan X-CSRF-TOKEN dan X-Requested-With pada setiap request mutasi internal
if (typeof window !== 'undefined' && window.fetch) {
    const originalFetch = window.fetch;
    window.fetch = async function (resource, options = {}) {
        const opts = { ...options };
        const method = (opts.method || (resource instanceof Request ? resource.method : 'GET')).toUpperCase();

        const url = typeof resource === 'string'
            ? resource
            : (resource instanceof Request ? resource.url : (resource?.href || ''));
        
        const isSameOrigin = !url.startsWith('http://') && !url.startsWith('https://') 
            || url.startsWith(window.location.origin);

        if (isSameOrigin) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta?.getAttribute('content') || '';

            if (opts.headers instanceof Headers) {
                if (!opts.headers.has('X-Requested-With')) {
                    opts.headers.set('X-Requested-With', 'XMLHttpRequest');
                }
                if (csrfToken && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && !opts.headers.has('X-CSRF-TOKEN')) {
                    opts.headers.set('X-CSRF-TOKEN', csrfToken);
                }
            } else if (Array.isArray(opts.headers)) {
                opts.headers.push(['X-Requested-With', 'XMLHttpRequest']);
                if (csrfToken && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
                    opts.headers.push(['X-CSRF-TOKEN', csrfToken]);
                }
            } else {
                opts.headers = {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...(opts.headers || {}),
                };
            }
        }

        return originalFetch(resource, opts);
    };
}

