import { useEffect, useRef, useState, type FormEvent } from 'react';
import { AlertCircle, CheckCircle2, CreditCard, RefreshCw, ShoppingBag } from 'lucide-react';
import { OptionField, TextField } from '../../components/forms';
import { Alert, Button } from '../../components/ui';
import { ApiError, requestApi, type StaffAppContext } from '../../lib/api';
import { Catalog } from './Catalog';
import { Cart } from './SaleCart';
import { changeQuantity, estimatedTotal, money, salePayload, validateCheckout, type CartLine, type PaymentChoice, type SaleMeta, type SaleReceipt, type Tier } from './cart';
import './pos.css';

type WalletState = { memberId: number | null; loading: boolean; balance: number | null; error: string };
type Result = { id: number; names: Record<number, string> };
const emptyWallet: WalletState = { memberId: null, loading: false, balance: null, error: '' };
const message = (error: unknown) => error instanceof Error ? error.message : 'Something went wrong. Please try again.';

export default function POSPage({ context }: { context: StaffAppContext }) {
  const canCreate = context.permissions.salesCreate === true;
  const canMeta = canCreate || context.permissions.salesOutstanding === true || context.permissions.salesEdit === true;
  const canRead = context.permissions.sales === true || context.permissions.salesPaid === true || context.permissions.salesEdit === true;
  const [meta, setMeta] = useState<SaleMeta | null>(null);
  const [loading, setLoading] = useState(canMeta);
  const [loadError, setLoadError] = useState('');
  const [revision, setRevision] = useState(0);
  const [cart, setCart] = useState<CartLine[]>([]);
  const [tier, setTier] = useState<Tier>('local');
  const [search, setSearch] = useState('');
  const [memberId, setMemberId] = useState<number | null>(null);
  const [customerName, setCustomerName] = useState('');
  const [reference, setReference] = useState('');
  const [choice, setChoice] = useState<PaymentChoice>('full');
  const [method, setMethod] = useState('');
  const [wallet, setWallet] = useState<WalletState>(emptyWallet);
  const [walletRevision, setWalletRevision] = useState(0);
  const [submitting, setSubmitting] = useState(false);
  const submissionLock = useRef(false);
  const [submitError, setSubmitError] = useState('');
  const [uncertain, setUncertain] = useState(false);
  const [result, setResult] = useState<Result | null>(null);
  const [receipt, setReceipt] = useState<SaleReceipt | null>(null);
  const [receiptError, setReceiptError] = useState('');
  const [receiptLoading, setReceiptLoading] = useState(false);
  const [receiptRevision, setReceiptRevision] = useState(0);

  useEffect(() => {
    if (!submitting && !uncertain) return;
    const warnBeforeUnload = (event: BeforeUnloadEvent) => {
      event.preventDefault();
      event.returnValue = '';
    };
    window.addEventListener('beforeunload', warnBeforeUnload);
    return () => window.removeEventListener('beforeunload', warnBeforeUnload);
  }, [submitting, uncertain]);

  useEffect(() => {
    if (!canMeta) return;
    const controller = new AbortController();
    setLoading(true); setLoadError(''); setMeta(null);
    requestApi<SaleMeta>('/api/sales/meta', { signal: controller.signal }).then((data) => {
      if (!controller.signal.aborted) { setMeta(data); setLoading(false); }
    }).catch((error: unknown) => {
      if (!controller.signal.aborted) { setLoadError(message(error)); setLoading(false); }
    });
    return () => controller.abort();
  }, [canMeta, revision]);

  useEffect(() => {
    if (!canCreate || choice !== 'full' || method !== 'wallet' || !memberId) { setWallet(emptyWallet); return; }
    const controller = new AbortController();
    setWallet({ memberId, loading: true, balance: null, error: '' });
    requestApi<{ data: { member_id: number; current_balance: number } }>(`/api/sales/member-wallet/${memberId}`, { signal: controller.signal }).then(({ data }) => {
      if (!controller.signal.aborted) setWallet({ memberId: data.member_id, loading: false, balance: data.current_balance, error: '' });
    }).catch((error: unknown) => {
      if (!controller.signal.aborted) setWallet({ memberId, loading: false, balance: null, error: message(error) });
    });
    return () => controller.abort();
  }, [canCreate, choice, method, memberId, walletRevision]);

  useEffect(() => {
    setReceipt(null); setReceiptError('');
    if (!result || !canRead) { setReceiptLoading(false); return; }
    const controller = new AbortController();
    setReceiptLoading(true);
    requestApi<{ data: SaleReceipt }>(`/api/sales/${result.id}`, { signal: controller.signal }).then(({ data }) => {
      if (!controller.signal.aborted) { setReceipt(data); setReceiptLoading(false); }
    }).catch((error: unknown) => {
      if (!controller.signal.aborted) { setReceiptError(message(error)); setReceiptLoading(false); }
    });
    return () => controller.abort();
  }, [result, canRead, receiptRevision]);

  const walletReady = wallet.memberId === memberId && wallet.balance !== null && !wallet.loading && !wallet.error;
  const total = meta ? estimatedTotal(cart, meta.variations, tier) : 0;
  const validation = meta ? validateCheckout(cart, meta, choice, method, memberId, walletReady) : 'Reload the catalog before checkout.';

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (submissionLock.current || submitting || uncertain || !canCreate || !meta) return;
    const error = validateCheckout(cart, meta, choice, method, memberId, walletReady);
    if (error) { setSubmitError(error); return; }
    submissionLock.current = true; setSubmitting(true); setSubmitError('');
    try {
      const response = await requestApi<{ data: { id: number } }>('/api/sales', { method: 'POST', body: salePayload(cart, tier, choice, method, memberId, customerName, reference) });
      setResult({ id: response.data.id, names: Object.fromEntries(meta.variations.map((item) => [item.id, item.label])) });
      setCart([]); setCustomerName(''); setMemberId(null); setReference(''); setMethod(''); setChoice('full');
      setMeta(null); setLoading(true); setRevision((value) => value + 1);
    } catch (error: unknown) {
      const ambiguous = !(error instanceof ApiError) || !error.status || error.status >= 500 || error.status === 408;
      setUncertain(ambiguous);
      setSubmitError(message(error));
    } finally { submissionLock.current = false; setSubmitting(false); }
  }

  return <section className="pos-page" aria-labelledby="pos-title">
    <header className="pos-page-header"><div><h1 id="pos-title">Point of sale</h1></div><div className="pos-tier" role="group" aria-label="Customer pricing tier"><span>PRICE TIER</span>{(['local', 'foreign'] as const).map((value) => <button type="button" key={value} aria-pressed={tier === value} disabled={submitting} onClick={() => setTier(value)}>{value === 'local' ? 'Local' : 'Foreign'}</button>)}</div></header>
    {meta && <a className="pos-cart-shortcut" href="#pos-checkout" onClick={(event) => { event.preventDefault(); document.getElementById('pos-checkout')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); document.getElementById('pos-checkout-heading')?.focus(); }}><ShoppingBag size={17} aria-hidden="true" /> View sale <strong>{cart.reduce((sum, line) => sum + line.quantity, 0)}</strong><span>Estimate {money(total)}</span></a>}
    {!canMeta && <Alert tone="error" className="pos-notice" role="alert">You do not have permission to access the sales catalog.</Alert>}
    {result && <Alert tone="success" className="pos-result" aria-labelledby="pos-result-title" role="status"><div className="pos-result-heading"><CheckCircle2 size={22} /><div><h2 id="pos-result-title">Sale #{result.id} created</h2></div><button type="button" className="pos-text-button" onClick={() => setResult(null)}>Dismiss</button></div>
      {receiptLoading && <p>Loading the server receipt…</p>}
      {!canRead && <p>Receipt details and payment status require sales viewing permission. Refer to sale #{result.id} for verification.</p>}
      {receiptError && <p>Sale created, but the receipt could not be loaded: {receiptError} <button type="button" className="pos-text-button" onClick={() => setReceiptRevision((value) => value + 1)}>Retry receipt</button></p>}
      {receipt && <><div className="pos-receipt-totals"><span>Status <strong>{receipt.is_paid ? 'Paid' : 'Outstanding'}</strong></span><span>Final total <strong>{money(receipt.total_amount)}</strong></span><span>Paid <strong>{money(receipt.paid_amount)}</strong></span><span>Balance <strong>{money(receipt.balance)}</strong></span><span>Method <strong>{receipt.payment_method_name || 'Not specified'}</strong></span></div><ul className="pos-receipt-lines">{receipt.items.map((item) => <li key={item.id}><span>{result.names[item.product_variation_id] || `Product #${item.product_variation_id}`} × {item.quantity} @ {money(item.unit_price)}</span><strong>{money(item.subtotal)}</strong></li>)}</ul></>}
    </Alert>}
    {loading && <div className="pos-state" role="status">Loading products and payment options…</div>}
    {loadError && <Alert tone="error" className="pos-notice pos-error" role="alert"><AlertCircle size={18} /><div><strong>Could not load the sales catalog.</strong> {loadError} <button type="button" className="pos-text-button" onClick={() => setRevision((value) => value + 1)}>Retry</button></div></Alert>}
    {meta && <div className="pos-layout"><Catalog variations={meta.variations} cart={cart} tier={tier} search={search} onSearch={setSearch} disabled={submitting} onAdd={(item) => setCart((current) => changeQuantity(current, item, (current.find((line) => line.variationId === item.id)?.quantity ?? 0) + 1))} />
      <aside className="pos-order" id="pos-checkout" aria-label="Sale and checkout"><Cart cart={cart} variations={meta.variations} tier={tier} disabled={submitting} onClear={() => setCart([])} onQuantity={(item, quantity) => setCart((current) => changeQuantity(current, item, quantity))} />
        <form className="pos-checkout" onSubmit={submit}><div className="pos-checkout-heading"><CreditCard size={19} /><h2 id="pos-checkout-heading" tabIndex={-1}>Checkout</h2></div>
          <fieldset disabled={submitting} className="pos-fields"><OptionField id="pos-customer" label="Customer" name="customer_member_id" isDisabled={submitting} value={memberId === null ? '' : String(memberId)} onValueChange={(value) => { const selected = meta.members.find((member) => member.id === Number(value)); setMemberId(selected?.id ?? null); setCustomerName(selected?.customer_name ?? ''); }} options={[{ value: '', label: 'Walk-in / no member' }, ...meta.members.map((member) => ({ value: String(member.id), label: member.label }))]} />
            <TextField id="pos-customer-name" label="Customer name" optional value={customerName} maxLength={255} onChange={(event) => setCustomerName(event.target.value)} placeholder="Customer name" />
            <fieldset className="pos-payment-choice"><legend>Payment status</legend><label><input type="radio" name="pos-payment-status" checked={choice === 'full'} onChange={() => setChoice('full')} /> Full payment</label><label><input type="radio" name="pos-payment-status" checked={choice === 'outstanding'} onChange={() => setChoice('outstanding')} /> Outstanding</label></fieldset>
            {choice === 'full' ? <><OptionField id="pos-payment-method" label="Payment method" name="payment_method_id" isDisabled={submitting} value={method} onValueChange={setMethod} options={[{ value: '', label: 'Select payment method' }, ...meta.payment_methods.map((item) => ({ value: String(item.id), label: `${item.name}${item.account_name ? ` · ${item.account_name}` : ''}` })), ...(canCreate ? [{ value: 'wallet', label: 'Member wallet' }] : [])]} />{method === 'wallet' && <div className="pos-wallet">{!memberId ? 'Select a member to use their wallet.' : wallet.loading || wallet.memberId !== memberId ? 'Loading wallet balance…' : wallet.error ? <span>Wallet unavailable: {wallet.error} <button type="button" className="pos-text-button" onClick={() => setWalletRevision((value) => value + 1)}>Retry</button></span> : <>Wallet balance: <strong>{money(wallet.balance ?? 0)}</strong></>}</div>}</> : null}
            <TextField id="pos-reference" label="Reference number" optional value={reference} maxLength={255} onChange={(event) => setReference(event.target.value)} placeholder="Payment or sale reference" />
          </fieldset>
          <div className="pos-total"><div><span>ESTIMATED TOTAL</span><strong>{money(total)}</strong></div></div>
          {!canCreate && <p className="pos-help">You can view the catalog, but you do not have permission to create sales.</p>}
          {submitError && <Alert tone="error" className="pos-notice pos-error" role="alert"><AlertCircle size={18} /><div>{submitError}{uncertain && <><p>The sale may have been recorded despite the error. Verify sales records before attempting another submission to avoid a duplicate.</p><button type="button" className="pos-text-button" onClick={() => { setUncertain(false); setSubmitError(''); setRevision((value) => value + 1); }}>I verified; reload stock and allow another attempt</button></>}</div></Alert>}
          <Button tone="primary" type="submit" className="pos-submit" disabled={!canCreate || submitting || uncertain || Boolean(validation)}><ShoppingBag size={18} />{submitting ? 'Creating sale…' : choice === 'full' ? 'Confirm full payment & create sale' : 'Create outstanding sale'}</Button>
          {validation && cart.length > 0 && <p className="pos-help">{validation}</p>}
          <Button tone="ghost" type="button" className="pos-refresh" disabled={submitting} onClick={() => setRevision((value) => value + 1)}><RefreshCw size={15} /> Refresh stock & prices</Button>
        </form>
      </aside>
    </div>}
  </section>;
}