import test from 'node:test';
import assert from 'node:assert/strict';
import { orderProgress } from '../../resources/js/utils/orderProgress.js';

test('payment and fulfillment are separate steps', () => {
    assert.equal(orderProgress({ order_status: 'PENDING', payment_status: 'pending' }).step, 0);
    assert.equal(orderProgress({ order_status: 'pending', payment_status: 'paid' }).step, 1);
    assert.equal(orderProgress({ order_status: 'PROCESSING', payment_status: 'paid' }).terminal, false);
    assert.equal(orderProgress({ order_status: 'COMPLETED', payment_status: 'paid' }).step, 2);
});
test('failed, expired and refunded orders never appear complete', () => {
    for (const status of ['failed', 'expired', 'refunded', 'cancelled']) {
        const progress = orderProgress({ order_status: status, payment_status: 'paid' });
        assert.ok(progress.exception);
        assert.equal(progress.terminal, true);
        assert.notEqual(progress.step, 2);
    }
    assert.ok(orderProgress({ order_status: 'completed', payment_status: 'refunded' }).exception);
});
