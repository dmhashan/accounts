import { type ReactNode } from 'react';
import { Modal } from '../../components/ui';
import type { StaffAppContext } from '../../lib/api';
import { formatDate, formatDateTime } from '../../lib/dateTime';
import { parseSaleDate, type Payment, type Sale } from './types';

export type PaymentDetail = { kind: 'payment'; record: Payment } | { kind: 'sale'; record: Sale };

const money = (value: number) => `LKR ${Number(value).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

function Detail({ label, children }: { label: string; children: ReactNode }) {
  return <div className="pay-detail"><dt>{label}</dt><dd>{children || '—'}</dd></div>;
}

export function PaymentDetailDialog({ detail, context, onClose }: { detail: PaymentDetail; context: StaffAppContext; onClose: () => void }) {

  const title = `${detail.kind === 'payment' ? 'Payment' : 'Sale'} #${detail.record.id}`;
  const date = (value: string | null) => value ? formatDate(value, context.settings.dateFormat) : '—';
  const saleDate = detail.kind === 'sale' ? parseSaleDate(detail.record.created_at) : null;

  return <Modal open onClose={onClose} title={title} titleId="pay-detail-title" size="3xl" className="pay-dialog pay-detail-dialog">
    <p className="pay-detail-customer">{detail.kind === 'payment' ? detail.record.member_name : detail.record.customer_name || 'Customer not specified'}</p>
    <div className={`pay-status ${detail.record.is_paid ? 'paid' : 'outstanding'}`}>{detail.record.is_paid ? 'Paid' : 'Outstanding'}</div>
    {detail.kind === 'payment' ? <dl className="pay-record-details">
      <Detail label="Type">{detail.record.type === 'membership' ? 'Membership' : 'Other'}</Detail>
      <Detail label="Payment date">{date(detail.record.payment_date)}</Detail><Detail label="Amount">{money(detail.record.amount)}</Detail><Detail label="Paid">{money(detail.record.paid_amount)}</Detail><Detail label="Balance">{money(detail.record.balance)}</Detail><Detail label="Method">{detail.record.payment_method_name}</Detail><Detail label="Account">{detail.record.account_name}</Detail><Detail label="Reference">{detail.record.reference_number}</Detail><Detail label="Phone">{detail.record.member_phone}</Detail>
      {detail.record.type === 'membership' && <><Detail label="Plan">{detail.record.payment_plan_name}</Detail><Detail label="Membership start">{date(detail.record.start_date)}</Detail><Detail label="Membership end">{date(detail.record.end_date)}</Detail></>}
      <Detail label="Settlement">{detail.record.settlement_status}</Detail>
      <Detail label="Settlement gross">{detail.record.settlement_gross_amount == null ? null : money(detail.record.settlement_gross_amount)}</Detail>
      <Detail label="Settlement deduction">{detail.record.settlement_deduction_amount == null ? null : money(detail.record.settlement_deduction_amount)}</Detail>
      <Detail label="Settlement net">{detail.record.settlement_net_amount == null ? null : money(detail.record.settlement_net_amount)}</Detail>
      <Detail label="Notes">{detail.record.notes}</Detail>
    </dl> : <>
      <dl className="pay-record-details"><Detail label="Type">Sale</Detail><Detail label="Sale date">{saleDate ? formatDateTime(saleDate, context.settings.dateFormat, context.settings.timeFormat) : `${detail.record.created_at || 'Unknown'} (date not filterable)`}</Detail><Detail label="Sale amount">{money(detail.record.total_amount)}</Detail><Detail label="Paid">{money(detail.record.paid_amount)}</Detail><Detail label="Balance">{money(detail.record.balance)}</Detail><Detail label="Customer type">{detail.record.customer_type}</Detail><Detail label="Method">{detail.record.payment_method_name}</Detail><Detail label="Account ID">{detail.record.account_id == null ? null : String(detail.record.account_id)}</Detail><Detail label="Reference">{detail.record.reference_number}</Detail><Detail label="Settlement">{detail.record.settlement_status}</Detail></dl>
      <div className="pay-items"><strong>Items</strong>{detail.record.items.length ? <ul>{detail.record.items.map((item, index) => <li key={index}>{item.product_name || 'Product'}{item.variation_name ? ` · ${item.variation_name}` : ''} — {item.quantity} × {money(item.unit_price)} = {money(item.subtotal)}</li>)}</ul> : <span> No items listed</span>}</div>
    </>}
  </Modal>;
}