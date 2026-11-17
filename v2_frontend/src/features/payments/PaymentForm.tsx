import { useEffect, useRef, useState, type FormEvent } from 'react';
import { DateField, NumberField, OptionField, TextareaField, TextField } from '../../components/forms';
import { Alert, Button, Modal } from '../../components/ui';
import { requestApi } from '../../lib/api';
import { localDate, type PaymentMeta } from './types';

type Props = { kind: 'membership' | 'other'; onClose: () => void; onSaved: () => void };
type Errors = Record<string, string[]>;

export function PaymentForm({ kind, onClose, onSaved }: Props) {
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
    if (message) alertRef.current?.focus();
  }, [message]);

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
    const requiredErrors: Errors = {};
    if ((kind === 'membership' || (paid && method === 'wallet')) && !member) requiredErrors.member_id = ['Select a member.'];
    if (kind === 'membership' && !plan) requiredErrors.payment_plan_id = ['Select a membership plan.'];
    if (paid && !method) requiredErrors.payment_method_id = ['Select a payment method.'];
    if (Object.keys(requiredErrors).length) {
      setMessage('Please complete the required selections.');
      setErrors(requiredErrors);
      return;
    }
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

  const fieldAttributes = (key: string) => ({ 'aria-invalid': Boolean(errors[key]?.length), 'aria-describedby': errors[key]?.length ? `payment-error-${key}` : undefined });
  return (
    <Modal open onClose={() => { if (!saving) onClose(); }} title={`Add ${kind} payment`} titleId="pay-form-title" size="2xl" dismissible={!saving} className="pay-dialog">
        {loading ? <p role="status">Loading payment options…</p> : null}
        {message && <Alert ref={alertRef} tone="error" tabIndex={-1} className="pay-alert pay-alert-error" role="alert">{message}</Alert>}
        {meta && <form onSubmit={submit} className="pay-form">
          <OptionField id="payment-member" name="member_id" label={<>Member {kind === 'other' && <span className="pay-optional">(optional)</span>}</>} {...fieldAttributes('member_id')} value={member} onValueChange={setMember} required={kind === 'membership' || (paid && method === 'wallet')} options={[{ value: '', label: 'Select member' }, ...meta.members.map((item) => ({ value: String(item.id), label: item.label }))]} error={errors.member_id?.join(' ')} errorId="payment-error-member_id" />
          {kind === 'membership' && <OptionField id="payment-plan" name="payment_plan_id" label="Membership plan" {...fieldAttributes('payment_plan_id')} value={plan} required onValueChange={(value) => { setPlan(value); const selected = meta.plans.find((item) => item.id === Number(value)); if (selected) setAmount(String(selected.price)); }} options={[{ value: '', label: 'Select plan' }, ...meta.plans.map((item) => ({ value: String(item.id), label: item.name }))]} error={errors.payment_plan_id?.join(' ')} errorId="payment-error-payment_plan_id" />}
          <div className="pay-form-grid"><NumberField id="payment-amount" name="amount" label="Amount (LKR)" {...fieldAttributes('amount')} min="0.01" step="0.01" required value={amount} onChange={(e) => setAmount(e.target.value)} error={errors.amount?.join(' ')} errorId="payment-error-amount" /><DateField id="payment-date" name="payment_date" label="Payment date" {...fieldAttributes('payment_date')} required value={date} onChange={(e) => setDate(e.target.value)} error={errors.payment_date?.join(' ')} errorId="payment-error-payment_date" /></div>
          {kind === 'membership' && <div className="pay-form-grid"><DateField id="payment-start-date" name="start_date" label="Membership start" {...fieldAttributes('start_date')} required value={start} onChange={(e) => setStart(e.target.value)} error={errors.start_date?.join(' ')} errorId="payment-error-start_date" /><DateField id="payment-end-date" name="end_date" label="Membership end" {...fieldAttributes('end_date')} optional min={start || undefined} value={end} onChange={(e) => setEnd(e.target.value)} error={errors.end_date?.join(' ')} errorId="payment-error-end_date" /></div>}
          <OptionField id="payment-status" name="is_paid" label="Status" {...fieldAttributes('is_paid')} value={paid ? 'paid' : 'outstanding'} onValueChange={(value) => setPaid(value === 'paid')} options={[{ value: 'outstanding', label: 'Outstanding' }, { value: 'paid', label: 'Paid' }]} error={errors.is_paid?.join(' ')} errorId="payment-error-is_paid" />
          {paid && <OptionField id="payment-method" name={method === 'wallet' ? 'payment_method' : 'payment_method_id'} label="Payment method" aria-invalid={Boolean(errors.payment_method_id?.length || errors.payment_method?.length)} aria-describedby={[errors.payment_method_id?.length && 'payment-error-payment_method_id', errors.payment_method?.length && 'payment-error-payment_method'].filter(Boolean).join(' ') || undefined} required value={method} onValueChange={setMethod} options={[{ value: '', label: 'Select method' }, ...meta.payment_methods.map((item) => ({ value: String(item.id), label: `${item.name}${item.account_name ? ` · ${item.account_name}` : ''}` })), { value: 'wallet', label: 'Member wallet' }]} error={[errors.payment_method_id?.join(' '), errors.payment_method?.join(' ')].filter(Boolean).join(' ')} errorId={errors.payment_method_id?.length ? 'payment-error-payment_method_id' : 'payment-error-payment_method'} />}
          <TextField id="payment-reference" name="reference_number" label="Reference" {...fieldAttributes('reference_number')} optional maxLength={255} value={reference} onChange={(e) => setReference(e.target.value)} error={errors.reference_number?.join(' ')} errorId="payment-error-reference_number" />
          <TextareaField id="payment-notes" name="notes" label="Notes" {...fieldAttributes('notes')} optional rows={3} maxLength={1000} value={notes} onChange={(e) => setNotes(e.target.value)} error={errors.notes?.join(' ')} errorId="payment-error-notes" />
          <div className="pay-dialog-actions"><Button tone="secondary" type="button" onClick={onClose} disabled={saving}>Cancel</Button><Button tone="primary" type="submit" disabled={saving}>{saving ? 'Saving…' : 'Record payment'}</Button></div>
        </form>}
    </Modal>
  );
}