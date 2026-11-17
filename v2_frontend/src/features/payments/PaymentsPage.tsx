import { useEffect, useState } from 'react';
import { BadgeCheck, CircleDollarSign, CreditCard, Info, Plus, ShoppingBag, Ticket } from 'lucide-react';
import { requestApi, type StaffAppContext } from '../../lib/api';
import { parseDateTime } from '../../lib/dateTime';
import { PaymentDetailDialog, type PaymentDetail } from './PaymentDetailDialog';
import { PaymentForm } from './PaymentForm';
import { dateRange, localDate, parseSaleDate, type Page, type Payment, type Preset, type Sale } from './types';
import './payments.css';

type Tab = 'all' | 'membership' | 'admission' | 'sales' | 'other';
type Status = 'all' | 'paid' | 'outstanding';
type Load<T> = { data: Page<T> | null; loading: boolean; error: string };
const emptyLoad = <T,>(): Load<T> => ({ data: null, loading: false, error: '' });
const money = (value: number) => `LKR ${Number(value).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const tabs: { value: Tab; label: string; icon: typeof CreditCard }[] = [
  { value: 'all', label: 'All', icon: CircleDollarSign }, { value: 'membership', label: 'Membership', icon: BadgeCheck },
  { value: 'admission', label: 'Admission', icon: Ticket }, { value: 'sales', label: 'Sales', icon: ShoppingBag }, { value: 'other', label: 'Other', icon: CreditCard },
];

function Pagination({ meta, page, setPage }: { meta: Page<unknown>['meta']; page: number; setPage: (page: number) => void }) {
  return <div className="pay-pagination"><span>Page {meta.current_page} of {meta.last_page}</span><div><button className="pay-button" disabled={page <= 1} onClick={() => setPage(page - 1)}>Previous</button><button className="pay-button" disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Next</button></div></div>;
}

function RecordsTable({ records, kind, onSelect }: { records: Payment[]; kind: 'payment'; onSelect: (detail: PaymentDetail) => void } | { records: Sale[]; kind: 'sale'; onSelect: (detail: PaymentDetail) => void }) {
  const label = kind === 'payment' ? 'Payments' : 'Sales';
  const hintId = `pay-${kind}-scroll-hint`;
  return <><p className="pay-scroll-hint" id={hintId}>Scroll horizontally to see all columns. Select an ID for details.</p>
    <div className="pay-table-scroll" role="region" aria-label={`${label} table, horizontally scrollable`} aria-describedby={hintId} tabIndex={0}>
      <table className="pay-table" aria-label={`${label} records`}><thead><tr><th scope="col">ID</th><th scope="col">Type</th><th scope="col">Member</th><th scope="col">Amount</th><th scope="col">Method</th><th scope="col">Notes</th></tr></thead>
        <tbody>{kind === 'payment' ? records.map((payment) => <tr key={payment.id}>
          <td><button type="button" className="pay-record-link" aria-label={`View payment ${payment.id} details`} onClick={() => onSelect({ kind: 'payment', record: payment })}>#{payment.id}</button></td>
          <td><span className="pay-category"><span className={`pay-category-icon ${payment.type}`} aria-hidden="true">{payment.type === 'membership' ? <BadgeCheck size={17} /> : <CreditCard size={17} />}</span>{payment.type === 'membership' ? 'Membership' : 'Other'}</span></td>
          <td>{payment.member_name || 'Not specified'}</td><td className="pay-amount">{money(payment.amount)}</td><td>{payment.payment_method_name || '—'}</td><td className="pay-table-notes"><span title={payment.notes || undefined}>{payment.notes || '—'}</span></td>
        </tr>) : records.map((sale) => <tr key={sale.id}>
          <td><button type="button" className="pay-record-link" aria-label={`View sale ${sale.id} details`} onClick={() => onSelect({ kind: 'sale', record: sale })}>#{sale.id}</button></td>
          <td><span className="pay-category"><span className="pay-category-icon sales" aria-hidden="true"><ShoppingBag size={17} /></span>Sale</span></td>
          <td>{sale.customer_name || 'Customer not specified'}</td><td className="pay-amount">{money(sale.total_amount)}</td><td>{sale.payment_method_name || '—'}</td><td className="pay-table-notes">Not reported</td>
        </tr>)}</tbody>
      </table>
    </div>
  </>;
}

export function PaymentsPage({ context }: { context: StaffAppContext }) {
  const canPayments = context.permissions.paymentsManage === true;
  const canSales = context.permissions.sales === true;
  const [tab, setTab] = useState<Tab>('all');
  const [status, setStatus] = useState<Status>('all');
  const [preset, setPreset] = useState<Preset>('month');
  const [from, setFrom] = useState(''); const [to, setTo] = useState('');
  const [paymentPage, setPaymentPage] = useState(1); const [salesPage, setSalesPage] = useState(1);
  const [payments, setPayments] = useState<Load<Payment>>(emptyLoad);
  const [sales, setSales] = useState<Load<Sale>>(emptyLoad);
  const [form, setForm] = useState<'membership' | 'other' | null>(null);
  const [detail, setDetail] = useState<PaymentDetail | null>(null);
  const [revision, setRevision] = useState(0);
  const [success, setSuccess] = useState('');
  const showPayments = canPayments && (tab === 'all' || tab === 'membership' || tab === 'other');
  const showSales = canSales && (tab === 'all' || tab === 'sales');

  useEffect(() => {
    if (!showPayments) return;
    const controller = new AbortController();
    setPayments({ data: null, loading: true, error: '' });
    const params = new URLSearchParams({ per_page: '20', page: String(paymentPage), status, type: tab === 'membership' || tab === 'other' ? tab : 'all' });
    requestApi<Page<Payment>>(`/api/payments?${params}`, { signal: controller.signal }).then((data: Page<Payment>) => { if (!controller.signal.aborted) setPayments({ data, loading: false, error: '' }); }).catch((error: unknown) => { if (!controller.signal.aborted) setPayments({ data: null, loading: false, error: error instanceof Error ? error.message : 'Could not load payments.' }); });
    return () => controller.abort();
  }, [showPayments, paymentPage, status, tab, revision]);

  useEffect(() => {
    if (!showSales) return;
    const controller = new AbortController();
    setSales({ data: null, loading: true, error: '' });
    const params = new URLSearchParams({ per_page: '20', page: String(salesPage), status });
    requestApi<Page<Sale>>(`/api/sales?${params}`, { signal: controller.signal }).then((data: Page<Sale>) => { if (!controller.signal.aborted) setSales({ data, loading: false, error: '' }); }).catch((error: unknown) => { if (!controller.signal.aborted) setSales({ data: null, loading: false, error: error instanceof Error ? error.message : 'Could not load sales.' }); });
    return () => controller.abort();
  }, [showSales, salesPage, status, revision]);

  const [rangeStart, rangeEnd] = dateRange(preset, from, to);
  const validRange = Boolean(rangeStart && rangeEnd && rangeStart <= rangeEnd);
  const inRange = (value: string | null) => !validRange || !value || (value >= rangeStart && value <= rangeEnd);
  const visiblePayments = payments.data?.data.filter((item) => { const date = item.payment_date && /^\d{4}-\d{2}-\d{2}$/.test(item.payment_date) ? parseDateTime(item.payment_date) : null; return inRange(date ? localDate(date) : null); }) ?? [];
  const visibleSales = sales.data?.data.filter((item) => { const date = parseSaleDate(item.created_at); return inRange(date ? localDate(date) : null); }) ?? [];
  const changeTab = (next: Tab) => { setTab(next); setPaymentPage(1); setSalesPage(1); };

  return <section className="payments-page" aria-labelledby="payments-title">
    <header className="pay-page-heading"><div><span className="pay-eyebrow">FINANCE / OVERVIEW</span><h1 id="payments-title">Payments</h1><p>Review payment records and sales activity.</p></div><div className="pay-add-actions"><button className="pay-button pay-primary" disabled={!canPayments} onClick={() => { setSuccess(''); setForm('membership'); }}><Plus size={16} /> Add membership</button><button className="pay-button" disabled={!canPayments} onClick={() => { setSuccess(''); setForm('other'); }}><Plus size={16} /> Add other</button><button className="pay-button" disabled title="Admission payments are not a supported category"><Plus size={16} /> Add admission</button><button className="pay-button" disabled title="Create sales through the POS flow"><Plus size={16} /> Add sale</button></div></header>
    <p className="pay-action-note">Admission payments are not supported by the backend; a member’s admission fee is not proof of payment. Sale creation requires the POS flow.</p>
    {success && <div className="pay-alert pay-alert-success" role="status">{success}</div>}
    <div className="pay-tabs" aria-label="Payment categories">{tabs.map(({ value, label, icon: Icon }) => <button key={value} type="button" className={`pay-tab ${tab === value ? 'active' : ''} ${value}`} aria-pressed={tab === value} onClick={() => changeTab(value)}><Icon size={16} />{label}</button>)}</div>
    <section className="pay-content" aria-label="Payment records">
      <div className="pay-filters"><div><span className="pay-eyebrow">RECORDS</span><h2>{tabs.find((item) => item.value === tab)?.label} activity</h2></div><div className="pay-filter-controls"><label>Status<select value={status} onChange={(e) => { setStatus(e.target.value as Status); setPaymentPage(1); setSalesPage(1); }}><option value="all">All statuses</option><option value="paid">Paid</option><option value="outstanding">Outstanding</option></select></label><label>Date range<select value={preset} onChange={(e) => setPreset(e.target.value as Preset)}><option value="month">Current month</option><option value="last-month">Last month</option><option value="three-months">Last 3 months</option><option value="today">Today</option><option value="yesterday">Yesterday</option><option value="week">Current week</option><option value="last-week">Last week</option><option value="custom">Custom</option></select></label>{preset === 'custom' && <><label>From<input type="date" value={from} onChange={(e) => setFrom(e.target.value)} /></label><label>To<input type="date" value={to} min={from || undefined} onChange={(e) => setTo(e.target.value)} /></label></>}</div></div>
      <div className="pay-alert pay-alert-info"><Info size={18} aria-hidden="true" /><div><strong>Date filtering covers only each currently fetched page.</strong> Results are incomplete across pages; displayed counts are page counts, not totals for the selected period. Records with an unrecognizable date remain visible. {validRange ? `Range: ${rangeStart} to ${rangeEnd}.` : 'Choose a valid start and end date to filter; showing the unfiltered page.'}</div></div>
      {tab === 'admission' && <div className="pay-empty"><Ticket size={24} /><h3>Admission payments unavailable</h3><p>Admission is not a payment category in the current API. Other payments and member admission fees are not treated as admission payments.</p></div>}
      {(tab === 'all' || tab === 'membership' || tab === 'other') && !canPayments && <div className="pay-empty"><h3>Payments unavailable</h3><p>Your account does not have permission to view payment records.</p></div>}
      {showPayments && <section className="pay-section" aria-labelledby="pay-list-title"><div className="pay-section-title"><div><h3 id="pay-list-title">Payments</h3><p>Membership and other payment records</p></div>{payments.data && <span>{visiblePayments.length} shown on this page</span>}</div>{payments.loading && <p className="pay-state" role="status">Loading payments…</p>}{payments.error && <div className="pay-alert pay-alert-error" role="alert">{payments.error} <button className="pay-link" onClick={() => setRevision((value) => value + 1)}>Retry</button></div>}{payments.data && (visiblePayments.length ? <RecordsTable kind="payment" records={visiblePayments} onSelect={setDetail} /> : <p className="pay-state">No payments match on this page. Other pages may contain matching records.</p>)}{payments.data && <Pagination meta={payments.data.meta} page={paymentPage} setPage={setPaymentPage} />}</section>}
      {(tab === 'all' || tab === 'sales') && !canSales && <div className="pay-empty"><h3>Sales unavailable</h3><p>Your account does not have permission to view sales records.</p></div>}
      {showSales && <section className="pay-section" aria-labelledby="sales-list-title"><div className="pay-section-title"><div><h3 id="sales-list-title">Sales</h3><p>Sales are separate records and may not be fully paid.</p></div>{sales.data && <span>{visibleSales.length} shown on this page</span>}</div>{sales.loading && <p className="pay-state" role="status">Loading sales…</p>}{sales.error && <div className="pay-alert pay-alert-error" role="alert">{sales.error} <button className="pay-link" onClick={() => setRevision((value) => value + 1)}>Retry</button></div>}{sales.data && (visibleSales.length ? <RecordsTable kind="sale" records={visibleSales} onSelect={setDetail} /> : <p className="pay-state">No sales match on this page. Other pages may contain matching records.</p>)}{sales.data && <Pagination meta={sales.data.meta} page={salesPage} setPage={setSalesPage} />}</section>}
    </section>
    {form && <PaymentForm kind={form} onClose={() => setForm(null)} onSaved={() => { setForm(null); setSuccess('Payment recorded successfully.'); setPaymentPage(1); setRevision((value) => value + 1); }} />}
    {detail && <PaymentDetailDialog detail={detail} context={context} onClose={() => setDetail(null)} />}
  </section>;
}