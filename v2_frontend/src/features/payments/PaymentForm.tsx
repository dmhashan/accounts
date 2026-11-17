import { useEffect, useRef, useState, type FormEvent } from 'react';
import { X } from 'lucide-react';
import { requestApi } from '../../lib/api';
import { localDate, type PaymentMeta } from './types';

type Props = { kind: 'membership' | 'other'; onClose: () => void; onSaved: () => void };
type Errors = Record<string, string[]>;

export function PaymentForm({ kind, onClose, onSaved }: Props) {
  const dialogRef = useRef<HTMLElement>(null);
  const closeRef = useRef<HTMLButtonElement>(null);
  const alertRef = useRef<HTMLDivElement>(null);
  const [meta, setMeta] = useState<PaymentMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');
  const [errors, setErrors] = useState<Errors>({});
  const [saving, setSaving] = useState(false);
  const [member, setMember] = useState('');
  const [plan, setPlan] = useState('');
  const [amount, setAmount] = useState('');
  const [date, setDate] = useState(localDate(new Date()));
  const [start, setStart] = useState('');
  const [end, setEnd] = useState('');
  const [paid, setPaid] = useState(false);
  const [method, setMethod] = useState('');
  const [reference, setReference] = useState('');
  const [notes, setNotes] = useState('');

  useEffect(() => {
    const previous = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    closeRef.current?.focus();
    return () => { previous?.focus(); };
  }, []);

  useEffect(() => {
    if (message) alertRef.current?.focus();
  }, [message]);

  function handleKeys(event: React.KeyboardEvent<HTMLElement>) {
    if (event.key === 'Escape' && !saving) { event.preventDefault(); onClose(); return; }
    if (event.key !== 'Tab') return;
    const elements = Array.from(dialogRef.current?.querySelectorAll<HTMLElement>('button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled)') || []);
    const first = elements[0]; const last = elements[elements.length - 1];
    if (!first || !last) { event.preventDefault(); dialogRef.current?.focus(); return; }
    if (!elements.includes(document.activeElement as HTMLElement)) { event.preventDefault(); (event.shiftKey ? last : first).focus(); }
    else if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  }

  useEffect(() => {
    const controller = new AbortController();
    requestApi<PaymentMeta>('/api/payments/meta', { signal: controller.signal })
      .then(setMeta)
      .catch((error: unknown) => { if (!controller.signal.aborted) setMessage(error instanceof Error ? error.message : 'Could not load payment options.'); })
      .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, []);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (saving || !meta) return;
    setSaving(true); setMessage(''); setErrors({});
    const body = {
      member_id: member ? Number(member) : null,
      amount: Number(amount), payment_date: date, is_paid: paid,
      reference_number: reference || null, notes: notes || null,
      ...(kind === 'membership' ? { payment_plan_id: Number(plan), start_date: start, end_date: end || null } : {}),
      ...(paid ? method === 'wallet' ? { payment_method: 'member_wallet' } : { payment_method_id: Number(method) } : {}),
    };
    try {
      await requestApi('/api/payments', { method: 'POST', body });
      onSaved();
    } catch (error: unknown) {
      const details = error as { message?: string; errors?: Errors };
      setMessage(details?.message || 'Payment could not be recorded.');
      setErrors(details?.errors || {});
    } finally { setSaving(false); }
  }

  const fieldError = (key: string) => errors[key]?.length ? <span id={`payment-error-${key}`} className="pay-field-error">{errors[key].join(' ')}</span> : null;
  const fieldAttributes = (key: string) => ({ 'aria-invalid': Boolean(errors[key]?.length), 'aria-describedby': errors[key]?.length ? `payment-error-${key}` : undefined });
  return (
    <div className="pay-dialog-backdrop">
      <section ref={dialogRef} onKeyDown={handleKeys} className="pay-dialog" role="dialog" aria-modal="true" aria-labelledby="pay-form-title" tabIndex={-1}>
        <div className="pay-dialog-heading"><div><h2 id="pay-form-title">Add {kind} payment</h2></div><button ref={closeRef} type="button" className="pay-icon-button" onClick={onClose} disabled={saving} aria-label="Close payment form"><X size={20} /></button></div>
        {loading ? <p role="status">Loading payment options…</p> : null}
        {message && <div ref={alertRef} tabIndex={-1} className="pay-alert pay-alert-error" role="alert">{message}</div>}
        {meta && <form onSubmit={submit} className="pay-form">
          <label>Member {kind === 'other' && <span className="pay-optional">(optional)</span>}
            <select {...fieldAttributes('member_id')} value={member} onChange={(e) => setMember(e.target.value)} required={kind === 'membership' || (paid && method === 'wallet')}><option value="">Select member</option>{meta.members.map((item) => <option value={item.id} key={item.id}>{item.label}</option>)}</select>{fieldError('member_id')}
          </label>
          {kind === 'membership' && <label>Membership plan<select {...fieldAttributes('payment_plan_id')} value={plan} required onChange={(e) => { setPlan(e.target.value); const selected = meta.plans.find((item) => item.id === Number(e.target.value)); if (selected) setAmount(String(selected.price)); }}><option value="">Select plan</option>{meta.plans.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select>{fieldError('payment_plan_id')}</label>}
          <div className="pay-form-grid"><label>Amount (LKR)<input {...fieldAttributes('amount')} type="number" min="0.01" step="0.01" required value={amount} onChange={(e) => setAmount(e.target.value)} />{fieldError('amount')}</label><label>Payment date<input {...fieldAttributes('payment_date')} type="date" required value={date} onChange={(e) => setDate(e.target.value)} />{fieldError('payment_date')}</label></div>
          {kind === 'membership' && <div className="pay-form-grid"><label>Membership start<input {...fieldAttributes('start_date')} type="date" required value={start} onChange={(e) => setStart(e.target.value)} />{fieldError('start_date')}</label><label>Membership end <span className="pay-optional">(optional)</span><input {...fieldAttributes('end_date')} type="date" min={start || undefined} value={end} onChange={(e) => setEnd(e.target.value)} />{fieldError('end_date')}</label></div>}
          <label>Status<select {...fieldAttributes('is_paid')} value={paid ? 'paid' : 'outstanding'} onChange={(e) => setPaid(e.target.value === 'paid')}><option value="outstanding">Outstanding</option><option value="paid">Paid</option></select>{fieldError('is_paid')}</label>
          {paid && <label>Payment method<select aria-invalid={Boolean(errors.payment_method_id?.length || errors.payment_method?.length)} aria-describedby={[errors.payment_method_id?.length && 'payment-error-payment_method_id', errors.payment_method?.length && 'payment-error-payment_method'].filter(Boolean).join(' ') || undefined} required value={method} onChange={(e) => setMethod(e.target.value)}><option value="">Select method</option>{meta.payment_methods.map((item) => <option key={item.id} value={item.id}>{item.name}{item.account_name ? ` · ${item.account_name}` : ''}</option>)}<option value="wallet">Member wallet</option></select>{fieldError('payment_method_id')}{fieldError('payment_method')}</label>}
          <label>Reference <span className="pay-optional">(optional)</span><input {...fieldAttributes('reference_number')} maxLength={255} value={reference} onChange={(e) => setReference(e.target.value)} />{fieldError('reference_number')}</label>
          <label>Notes <span className="pay-optional">(optional)</span><textarea {...fieldAttributes('notes')} rows={3} maxLength={1000} value={notes} onChange={(e) => setNotes(e.target.value)} />{fieldError('notes')}</label>
          <div className="pay-dialog-actions"><button type="button" className="pay-button" onClick={onClose} disabled={saving}>Cancel</button><button type="submit" className="pay-button pay-primary" disabled={saving}>{saving ? 'Saving…' : 'Record payment'}</button></div>
        </form>}
      </section>
    </div>
  );
}