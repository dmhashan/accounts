import { useEffect, useState } from 'react';
import { BadgeCheck, CreditCard, Plus, ShoppingBag } from 'lucide-react';
import { DateField, OptionField } from '../../components/forms';
import { Alert, Button, Pagination, Table } from '../../components/ui';
import { requestApi, type StaffAppContext } from '../../lib/api';
import { parseDateTime } from '../../lib/dateTime';
import { PaymentDetailDialog, type PaymentDetail } from './PaymentDetailDialog';
import { PaymentForm } from './PaymentForm';
import { dateRange, initialPaymentTab, localDate, parseSaleDate, type Page, type Payment, type PaymentTab, type Preset, type Sale } from './types';
import './payments.css';

type Status = 'all' | 'paid' | 'outstanding';
type Load<T> = { data: Page<T> | null; loading: boolean; error: string };
const emptyLoad = <T,>(): Load<T> => ({ data: null, loading: false, error: '' });
const money = (value: number) => `LKR ${Number(value).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const tabs: { value: PaymentTab; label: string; icon: typeof CreditCard }[] = [
  { value: 'membership', label: 'Membership', icon: BadgeCheck },
  { value: 'sales', label: 'Sales', icon: ShoppingBag }, { value: 'other', label: 'Other', icon: CreditCard },
];

function RecordsPagination({ meta, page, setPage }: { meta: Page<unknown>['meta']; page: number; setPage: (page: number) => void }) {
  return <div className="pay-pagination"><span>Page {meta.current_page} of {meta.last_page}</span><Pagination currentPage={page} totalPages={meta.last_page} onPageChange={setPage} ariaLabel="Payment records pagination" /></div>;
}

function RecordsTable({ records, kind, onSelect }: { records: Payment[]; kind: 'payment'; onSelect: (detail: PaymentDetail) => void } | { records: Sale[]; kind: 'sale'; onSelect: (detail: PaymentDetail) => void }) {
  const label = kind === 'payment' ? 'Payments' : 'Sales';
  return <>
    <div className="pay-table-scroll" role="region" aria-label={`${label} table, horizontally scrollable`} tabIndex={0}>
      <Table className="pay-table" hoverable aria-label={`${label} records`}><Table.Head><Table.Row><Table.HeadCell scope="col">ID</Table.HeadCell><Table.HeadCell scope="col">Type</Table.HeadCell><Table.HeadCell scope="col">Member</Table.HeadCell><Table.HeadCell scope="col">Amount</Table.HeadCell><Table.HeadCell scope="col">Method</Table.HeadCell><Table.HeadCell scope="col">Notes</Table.HeadCell></Table.Row></Table.Head>
        <Table.Body>{kind === 'payment' ? records.map((payment) => <Table.Row key={payment.id}>
          <Table.Cell><button type="button" className="pay-record-link" aria-label={`View payment ${payment.id} details`} onClick={() => onSelect({ kind: 'payment', record: payment })}>#{payment.id}</button></Table.Cell>
          <Table.Cell><span className="pay-category"><span className={`pay-category-icon ${payment.type}`} aria-hidden="true">{payment.type === 'membership' ? <BadgeCheck size={17} /> : <CreditCard size={17} />}</span>{payment.type === 'membership' ? 'Membership' : 'Other'}</span></Table.Cell>
          <Table.Cell>{payment.member_name || 'Not specified'}</Table.Cell><Table.Cell className="pay-amount">{money(payment.amount)}</Table.Cell><Table.Cell>{payment.payment_method_name || '—'}</Table.Cell><Table.Cell className="pay-table-notes"><span title={payment.notes || undefined}>{payment.notes || '—'}</span></Table.Cell>
        </Table.Row>) : records.map((sale) => <Table.Row key={sale.id}>
          <Table.Cell><button type="button" className="pay-record-link" aria-label={`View sale ${sale.id} details`} onClick={() => onSelect({ kind: 'sale', record: sale })}>#{sale.id}</button></Table.Cell>
          <Table.Cell><span className="pay-category"><span className="pay-category-icon sales" aria-hidden="true"><ShoppingBag size={17} /></span>Sale</span></Table.Cell>
          <Table.Cell>{sale.customer_name || 'Customer not specified'}</Table.Cell><Table.Cell className="pay-amount">{money(sale.total_amount)}</Table.Cell><Table.Cell>{sale.payment_method_name || '—'}</Table.Cell><Table.Cell className="pay-table-notes">Not reported</Table.Cell>
        </Table.Row>)}</Table.Body>
      </Table>
    </div>
  </>;
}

export function PaymentsPage({ context, navigate }: { context: StaffAppContext; navigate: (path: string) => void }) {
  const canPayments = context.permissions.paymentsManage === true;
  const canSales = context.permissions.sales === true;
  const [tab, setTab] = useState<PaymentTab>(() => initialPaymentTab(canPayments, canSales));
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
  const showPayments = canPayments && (tab === 'membership' || tab === 'other');
  const showSales = canSales && tab === 'sales';

  useEffect(() => {
    if (!showPayments) return;
    const controller = new AbortController();
    setPayments({ data: null, loading: true, error: '' });
    const params = new URLSearchParams({ per_page: '20', page: String(paymentPage), status, type: tab });
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
  const changeTab = (next: PaymentTab) => { setTab(next); setPaymentPage(1); setSalesPage(1); };

  return <section className="payments-page" aria-labelledby="payments-title">
    <header className="pay-page-heading"><div><h1 id="payments-title">Payments</h1></div><div className="pay-add-actions"><Button tone="primary" className="pay-button pay-primary" disabled={!canPayments} onClick={() => { setSuccess(''); setForm('membership'); }}><Plus size={16} /> Add membership</Button><Button tone="secondary" className="pay-button" disabled={!canPayments} onClick={() => { setSuccess(''); setForm('other'); }}><Plus size={16} /> Add other</Button><Button tone="secondary" className="pay-button" disabled={!context.permissions.salesCreate} onClick={() => navigate('/pos')}><Plus size={16} /> Add sale</Button></div></header>
    {success && <Alert tone="success" className="pay-alert pay-alert-success" role="status">{success}</Alert>}
    <div className="pay-tabs" aria-label="Payment categories">{tabs.map(({ value, label, icon: Icon }) => <button key={value} type="button" className={`pay-tab ${tab === value ? 'active' : ''} ${value}`} aria-pressed={tab === value} onClick={() => changeTab(value)}><Icon size={16} />{label}</button>)}</div>
    <section className="pay-content" aria-label="Payment records">
      <div className="pay-filters"><div><h2>{tabs.find((item) => item.value === tab)?.label} activity</h2></div><div className="pay-filter-controls"><OptionField<Status> id="payment-filter-status" label="Status" value={status} onValueChange={(value) => { setStatus(value); setPaymentPage(1); setSalesPage(1); }} options={[{ value: 'all', label: 'All statuses' }, { value: 'paid', label: 'Paid' }, { value: 'outstanding', label: 'Outstanding' }]} /><OptionField<Preset> id="payment-filter-date" label="Page date" value={preset} onValueChange={setPreset} options={[{ value: 'month', label: 'Current month' }, { value: 'last-month', label: 'Last month' }, { value: 'three-months', label: 'Last 3 months' }, { value: 'today', label: 'Today' }, { value: 'yesterday', label: 'Yesterday' }, { value: 'week', label: 'Current week' }, { value: 'last-week', label: 'Last week' }, { value: 'custom', label: 'Custom' }]} />{preset === 'custom' && <><DateField id="payment-filter-from" label="From" value={from} onChange={(e) => setFrom(e.target.value)} /><DateField id="payment-filter-to" label="To" value={to} min={from || undefined} onChange={(e) => setTo(e.target.value)} /></>}</div></div>
      <div className="pay-date-scope"><strong>Page date: {validRange ? `${rangeStart} to ${rangeEnd}` : 'not applied'}</strong><span>shown on this page</span></div>
      {tab === 'membership' && !canPayments && <div className="pay-empty"><h3>Payments unavailable</h3><p>Your account does not have permission to view payment records.</p></div>}
      {showPayments && <section className="pay-section" aria-labelledby="pay-list-title"><div className="pay-section-title"><div><h3 id="pay-list-title">Payments</h3></div>{payments.data && <span>{visiblePayments.length} shown on this page</span>}</div>{payments.loading && <p className="pay-state" role="status">Loading payments…</p>}{payments.error && <Alert tone="error" className="pay-alert pay-alert-error" role="alert">{payments.error} <Button tone="ghost" compact className="pay-link" onClick={() => setRevision((value) => value + 1)}>Retry</Button></Alert>}{payments.data && (visiblePayments.length ? <RecordsTable kind="payment" records={visiblePayments} onSelect={setDetail} /> : <p className="pay-state">No payments match on this page. Other pages may contain matching records.</p>)}{payments.data && <RecordsPagination meta={payments.data.meta} page={paymentPage} setPage={setPaymentPage} />}</section>}
      {tab === 'sales' && !canSales && <div className="pay-empty"><h3>Sales unavailable</h3><p>Your account does not have permission to view sales records.</p></div>}
      {showSales && <section className="pay-section" aria-labelledby="sales-list-title"><div className="pay-section-title"><div><h3 id="sales-list-title">Sales</h3></div>{sales.data && <span>{visibleSales.length} shown on this page</span>}</div>{sales.loading && <p className="pay-state" role="status">Loading sales…</p>}{sales.error && <Alert tone="error" className="pay-alert pay-alert-error" role="alert">{sales.error} <Button tone="ghost" compact className="pay-link" onClick={() => setRevision((value) => value + 1)}>Retry</Button></Alert>}{sales.data && (visibleSales.length ? <RecordsTable kind="sale" records={visibleSales} onSelect={setDetail} /> : <p className="pay-state">No sales match on this page. Other pages may contain matching records.</p>)}{sales.data && <RecordsPagination meta={sales.data.meta} page={salesPage} setPage={setSalesPage} />}</section>}
    </section>
    {form && <PaymentForm kind={form} onClose={() => setForm(null)} onSaved={() => { setForm(null); setSuccess('Payment recorded successfully.'); setPaymentPage(1); setRevision((value) => value + 1); }} />}
    {detail && <PaymentDetailDialog detail={detail} context={context} onClose={() => setDetail(null)} />}
  </section>;
}