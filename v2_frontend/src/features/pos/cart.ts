export type Tier = 'local' | 'foreign';
export type Variation = { id: number; name: string; product_name: string | null; label: string; available_stock: number; prices: Record<Tier, number> };
export type Member = { id: number; label: string; customer_name: string; phone_number: string };
export type PaymentMethod = { id: number; name: string; account_name: string | null };
export type SaleMeta = { variations: Variation[]; members: Member[]; payment_methods: PaymentMethod[] };
export type CartLine = { variationId: number; quantity: number };
export type PaymentChoice = 'full' | 'outstanding';
export type SaleReceipt = { id: number; total_amount: number; paid_amount: number; balance: number; is_paid: boolean; payment_method_name: string | null; items: { id: number; product_variation_id: number; quantity: number; unit_price: number; subtotal: number }[] };

export function changeQuantity(cart: CartLine[], variation: Variation, quantity: number): CartLine[] {
  const previous = cart.find((line) => line.variationId === variation.id)?.quantity ?? 0;
  if (!Number.isSafeInteger(quantity) || quantity < 0 || (quantity > variation.available_stock && quantity >= previous)) return cart;
  const remaining = cart.filter((line) => line.variationId !== variation.id);
  if (quantity === 0) return remaining;
  const existing = cart.findIndex((line) => line.variationId === variation.id);
  if (existing < 0) return [...cart, { variationId: variation.id, quantity }];
  return cart.map((line) => line.variationId === variation.id ? { ...line, quantity } : line);
}

export function estimatedTotal(cart: CartLine[], variations: Variation[], tier: Tier): number {
  return cart.reduce((total, line) => {
    const item = variations.find((variation) => variation.id === line.variationId);
    return total + (item ? Number(item.prices[tier]) * line.quantity : 0);
  }, 0);
}

export function validateCheckout(cart: CartLine[], meta: SaleMeta, choice: PaymentChoice, method: string, memberId: number | null, walletReady: boolean): string | null {
  if (!cart.length) return 'Add at least one product to the sale.';
  const seen = new Set<number>();
  for (const line of cart) {
    const item = meta.variations.find((variation) => variation.id === line.variationId);
    if (!item || seen.has(line.variationId) || !Number.isSafeInteger(line.quantity) || line.quantity < 1 || line.quantity > item.available_stock) return 'Check cart quantities against the latest available stock.';
    seen.add(line.variationId);
  }
  if (choice === 'full') {
    if (method === 'wallet') {
      if (!memberId || !meta.members.some((member) => member.id === memberId)) return 'Select a member to use their wallet.';
      if (!walletReady) return 'Load the member wallet balance before continuing.';
    } else if (!meta.payment_methods.some((item) => String(item.id) === method)) return 'Select a payment method.';
  }
  if (memberId && !meta.members.some((member) => member.id === memberId)) return 'Select a valid member.';
  return null;
}

export function salePayload(cart: CartLine[], tier: Tier, choice: PaymentChoice, method: string, memberId: number | null, customerName: string, reference: string) {
  return {
    customer_name: customerName.trim() || null,
    customer_member_id: memberId,
    customer_type: tier,
    reference_number: reference.trim() || null,
    paid_amount: 0,
    is_paid: choice === 'full',
    ...(choice === 'full' ? method === 'wallet' ? { payment_method: 'member_wallet' } : { payment_method_id: Number(method) } : {}),
    items: cart.map((line) => ({ product_variation_id: line.variationId, quantity: line.quantity })),
  };
}

export const money = (value: number) => `LKR ${Number(value).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;