import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Axios reads the current XSRF cookie on every request, including after login.
// Fetch must use the same encrypted cookie with the X-XSRF-TOKEN header.
export function withSessionHeaders(resource, options = {}) {
    const opts = { ...options };
    const request = resource instanceof Request ? resource : null;
    const url = new URL(request ? request.url : resource, window.location.href);
    if (url.origin !== window.location.origin) return opts;

    const headers = new Headers(opts.headers ?? request?.headers);
    const method = (opts.method || request?.method || 'GET').toUpperCase();
    headers.set('X-Requested-With', 'XMLHttpRequest');
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        const cookie = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='));
        if (cookie) {
            // A stale explicit header takes precedence over the cookie in Laravel.
            headers.delete('X-CSRF-TOKEN');
            headers.set('X-XSRF-TOKEN', decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)));
        } else {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) headers.set('X-CSRF-TOKEN', token);
        }
    }
    opts.headers = headers;
    return opts;
}

if (typeof window !== 'undefined' && window.fetch) {
    const originalFetch = window.fetch.bind(window);
    window.fetch = (resource, options = {}) => originalFetch(resource, withSessionHeaders(resource, options));
}
