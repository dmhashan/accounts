import { test } from 'node:test';
import assert from 'node:assert/strict';
import { changeQuantity, estimatedTotal, salePayload, validateCheckout, type SaleMeta, type Variation } from '../src/features/pos/cart.ts';

const product: Variation = { id: 4, name: 'Large', product_name: 'Bottle', label: 'Bottle - Large', available_stock: 3, prices: { local: 125, foreign: 200 } };
const meta: SaleMeta = { variations: [product], members: [{ id: 9, label: 'Alex (123)', customer_name: 'Alex', phone_number: '123' }], payment_methods: [{ id: 2, name: 'Cash', account_name: 'Till' }] };

test('cart quantities are immutable, integer, and limited by available stock', () => {
  const initial = [{ variationId: 4, quantity: 1 }];
  const updated = changeQuantity(initial, product, 3);
  assert.deepEqual(initial, [{ variationId: 4, quantity: 1 }]);
  assert.deepEqual(updated, [{ variationId: 4, quantity: 3 }]);
  assert.strictEqual(changeQuantity(updated, product, 4), updated);
  assert.strictEqual(changeQuantity(updated, product, 1.5), updated);
  assert.deepEqual(changeQuantity(updated, product, 0), []);
  assert.deepEqual(changeQuantity(updated, { ...product, available_stock: 1 }, 2), [{ variationId: 4, quantity: 2 }]);
});

test('estimates use the selected tier without converting currency', () => {
  const cart = [{ variationId: 4, quantity: 2 }];
  assert.equal(estimatedTotal(cart, [product], 'local'), 250);
  assert.equal(estimatedTotal(cart, [product], 'foreign'), 400);
});

test('checkout rejects stale stock, duplicates and invalid payment choices', () => {
  const cart = [{ variationId: 4, quantity: 2 }];
  assert.match(validateCheckout([], meta, 'full', '2', null, false)!, /at least one/);
  assert.match(validateCheckout([{ variationId: 4, quantity: 4 }], meta, 'outstanding', '', null, false)!, /quantities/);
  assert.match(validateCheckout([...cart, ...cart], meta, 'full', '2', null, false)!, /quantities/);
  assert.match(validateCheckout(cart, meta, 'full', '', null, false)!, /payment method/);
  assert.match(validateCheckout(cart, meta, 'full', 'wallet', null, false)!, /member/);
  assert.match(validateCheckout(cart, meta, 'full', 'wallet', 9, false)!, /wallet balance/);
  assert.equal(validateCheckout(cart, meta, 'full', 'wallet', 9, true), null);
  assert.equal(validateCheckout(cart, meta, 'outstanding', '', null, false), null);
});

test('payload selects the backend paid-status flow and never sends wallet for outstanding sales', () => {
  const cart = [{ variationId: 4, quantity: 2 }];
  assert.deepEqual(salePayload(cart, 'foreign', 'full', '2', null, ' Guest ', ' ref '), { customer_name: 'Guest', customer_member_id: null, customer_type: 'foreign', reference_number: 'ref', paid_amount: 0, is_paid: true, payment_method_id: 2, items: [{ product_variation_id: 4, quantity: 2 }] });
  assert.deepEqual(salePayload(cart, 'local', 'full', 'wallet', 9, 'Alex', ''), { customer_name: 'Alex', customer_member_id: 9, customer_type: 'local', reference_number: null, paid_amount: 0, is_paid: true, payment_method: 'member_wallet', items: [{ product_variation_id: 4, quantity: 2 }] });
  assert.deepEqual(salePayload(cart, 'local', 'outstanding', 'wallet', 9, '', ''), { customer_name: null, customer_member_id: 9, customer_type: 'local', reference_number: null, paid_amount: 0, is_paid: false, items: [{ product_variation_id: 4, quantity: 2 }] });
});