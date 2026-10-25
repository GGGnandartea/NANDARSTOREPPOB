import test from 'node:test';
import assert from 'node:assert/strict';
import { submitAuth, authErrors } from '../../resources/js/lib/authRequest.js';

test('refreshes a rejected session once and submits the new token', async () => {
    let gets = 0;
    let posts = 0;
    const transport = {
        get: async () => ({ data: { token: `token-${++gets}` } }),
        post: async (url, payload, options) => {
            posts++;
            assert.equal(url, '/login');
            assert.equal(options.headers['X-CSRF-TOKEN'], `token-${posts}`);
            assert.equal(payload._token, `token-${posts}`);
            if (posts === 1) throw { response: { status: 419 } };
            return { data: { redirect: '/' } };
        },
    };
    assert.deepEqual(await submitAuth('/login', {}, transport), { redirect: '/' });
    assert.equal(gets, 2);
    assert.equal(posts, 2);
});

test('does not loop when session cookies cannot be persisted', async () => {
    let posts = 0;
    const transport = {
        get: async () => ({ data: { token: 'token' } }),
        post: async () => { posts++; throw { response: { status: 419 } }; },
    };
    await assert.rejects(submitAuth('/register', {}, transport), error => error.response.status === 419);
    assert.equal(posts, 2);
});

test('does not replay registration on timeout, validation, rate limit, or server failure', async () => {
    for (const failure of [{ code: 'ECONNABORTED' }, ...[422, 429, 500].map(status => ({ response: { status } }))]) {
        let posts = 0;
        const transport = {
            get: async () => ({ data: { token: 'token' } }),
            post: async () => { posts++; throw failure; },
        };
        await assert.rejects(submitAuth('/register', {}, transport), error => error === failure);
        assert.equal(posts, 1);
        assert.ok(Object.keys(authErrors(failure)).length);
    }
});

test('rejects an HTML response instead of treating it as successful authentication', async () => {
    await assert.rejects(submitAuth('/login', {}, {
        get: async () => ({ data: { token: 'token' } }),
        post: async () => ({ data: '<html>Proxy error</html>' }),
    }), /Invalid authentication response/);
    assert.deepEqual(authErrors({ response: { status: 422, data: { errors: { email: ['Email salah.'] } } } }),
        { email: 'Email salah.' });
});
