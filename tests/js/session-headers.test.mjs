import test from 'node:test';
import assert from 'node:assert/strict';
globalThis.window = { location: { href: 'https://store.test/checkout', origin: 'https://store.test' } };
globalThis.document = { cookie: 'XSRF-TOKEN=before-login', querySelector: () => ({ content: 'stale-meta' }) };
const { withSessionHeaders } = await import('../../resources/js/bootstrap.js');

test('uses the current session cookie after login instead of a stale explicit token', () => {
    document.cookie = 'XSRF-TOKEN=after-login%3D';
    const { headers } = withSessionHeaders('/checkout/process', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': 'stale-meta', 'Content-Type': 'application/json' },
    });
    assert.equal(headers.get('X-CSRF-TOKEN'), null);
    assert.equal(headers.get('X-XSRF-TOKEN'), 'after-login=');
    assert.equal(headers.get('Content-Type'), 'application/json');
});

test('does not attach session headers to external or lookalike origins', () => {
    for (const url of ['https://store.test.attacker.test/pay', '//external.test/pay']) {
        assert.equal(withSessionHeaders(url, { method: 'POST' }).headers, undefined);
    }
});

test('preserves Request headers and uses its method', () => {
    const request = new Request('https://store.test/pay', { method: 'POST', headers: { Accept: 'application/json' } });
    const { headers } = withSessionHeaders(request);
    assert.equal(headers.get('Accept'), 'application/json');
    assert.equal(headers.get('X-XSRF-TOKEN'), 'after-login=');
});
