import { Package, Plus, Search } from 'lucide-react';
import { money, type CartLine, type Tier, type Variation } from './cart';

export function Catalog({ variations, cart, tier, search, onSearch, onAdd, disabled }: { variations: Variation[]; cart: CartLine[]; tier: Tier; search: string; onSearch: (value: string) => void; onAdd: (item: Variation) => void; disabled: boolean }) {
  const filtered = variations.filter((item) => `${item.product_name ?? ''} ${item.name} ${item.label}`.toLocaleLowerCase().includes(search.trim().toLocaleLowerCase()));
  return <section className="pos-catalog" aria-labelledby="pos-catalog-heading">
    <div className="pos-section-heading"><div><h2 id="pos-catalog-heading">Product catalog</h2></div><span className="pos-count">{filtered.length} products</span></div>
    <label className="pos-search"><Search size={19} aria-hidden="true" /><span className="pos-sr-only">Search products</span><input type="search" value={search} onChange={(event) => onSearch(event.target.value)} placeholder="Search products or variations" /></label>
    {filtered.length === 0 ? <div className="pos-empty"><Package size={23} /><strong>{search.trim() ? 'No matching products' : 'No products available'}</strong></div> : <div className="pos-product-grid">{filtered.map((item) => {
      const selected = cart.find((line) => line.variationId === item.id)?.quantity ?? 0;
      const unavailable = item.available_stock <= 0;
      const atLimit = selected >= item.available_stock;
      return <button type="button" className="pos-product" key={item.id} disabled={disabled} aria-disabled={unavailable || atLimit} onClick={() => { if (!unavailable && !atLimit) onAdd(item); }} aria-label={`${item.label}, estimated ${money(item.prices[tier])} each, ${item.available_stock} in stock, ${selected} in sale${unavailable ? ', out of stock' : atLimit ? ', stock limit reached' : ', add to sale'}`}>
        <span className="pos-product-top"><span className="pos-product-icon"><Package size={19} /></span><span className={`pos-stock ${unavailable ? 'is-out' : ''}`}>{unavailable ? 'Out of stock' : `${item.available_stock} in stock`}</span></span>
        <span className="pos-product-name">{item.product_name || 'Product'}</span><span className="pos-product-variation">{item.name}</span>
        <span className="pos-product-bottom"><span><small>UNIT</small><strong>{money(item.prices[tier])}</strong></span><span className="pos-add-icon" aria-hidden="true"><Plus size={17} /></span></span>
        {selected > 0 && <span className="pos-in-cart">{selected} in sale{atLimit ? ' · stock limit' : ''}</span>}
      </button>;
    })}</div>}
  </section>;
}