import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { money, type CartLine, type Tier, type Variation } from './cart';

export function Cart({ cart, variations, tier, onQuantity, onClear, disabled }: { cart: CartLine[]; variations: Variation[]; tier: Tier; onQuantity: (item: Variation, value: number) => void; onClear: () => void; disabled: boolean }) {
  const count = cart.reduce((total, line) => total + line.quantity, 0);
  return <section className="pos-cart-items" aria-labelledby="pos-cart-heading">
    <div className="pos-section-heading"><div><h2 id="pos-cart-heading">Active sale <span className="pos-cart-count">{count}</span></h2></div><button type="button" className="pos-text-button" onClick={onClear} disabled={disabled || !cart.length}><Trash2 size={16} /> Clear</button></div>
    {!cart.length ? <div className="pos-empty pos-cart-empty"><ShoppingBag size={24} /><strong>Your sale is empty</strong></div> : <ul className="pos-line-list">{cart.map((line) => {
      const item = variations.find((entry) => entry.id === line.variationId);
      if (!item) return <li className="pos-line" key={line.variationId}><div><strong>Product no longer available</strong><p>Remove this item before checkout.</p></div><button type="button" className="pos-text-button" disabled={disabled} onClick={() => onClear()}>Clear cart</button></li>;
      return <li className="pos-line" key={item.id}><div className="pos-line-detail"><strong>{item.product_name || 'Product'}</strong><p>{item.name} · {money(item.prices[tier])} each</p>{line.quantity > item.available_stock && <p className="pos-line-error">Only {item.available_stock} available. Adjust or remove.</p>}</div><div className="pos-line-controls"><strong>{money(Number(item.prices[tier]) * line.quantity)}</strong><div className="pos-stepper"><button type="button" aria-label={`Remove one ${item.label}`} disabled={disabled} onClick={() => onQuantity(item, line.quantity - 1)}><Minus size={15} /></button><span aria-label={`${line.quantity} selected`}>{line.quantity}</span><button type="button" aria-label={`Add one ${item.label}`} disabled={disabled || line.quantity >= item.available_stock} onClick={() => onQuantity(item, line.quantity + 1)}><Plus size={15} /></button></div></div></li>;
    })}</ul>}
  </section>;
}