import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { ArrowLeft, ChevronLeft, ChevronRight, Plus, Search, UserRound } from 'lucide-react';
import { requestApi, type StaffAppContext } from '../../lib/api';
import { formatDate, formatDateTime } from '../../lib/dateTime';
import type { MemberDetail, MemberDetailResponse, MemberForm, MemberListResponse, PaymentPlan } from './types';
import './members.css';

type Props = { context: StaffAppContext; path: string; navigate: (path: string) => void };
type Errors = Record<string, string[]>;

const messageOf = (error: unknown) => error instanceof Error ? error.message : 'Something went wrong. Please try again.';
const errorsOf = (error: unknown): Errors => {
  if (typeof error !== 'object' || error === null || !('errors' in error)) return {};
  const errors = error.errors;
  return typeof errors === 'object' && errors !== null && !Array.isArray(errors) ? errors as Errors : {};
};
const today = () => {
  const date = new Date();
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
};
const text = (value: string | number | null | undefined) => value === null || value === undefined || value === '' ? '—' : String(value);
const money = (value: number | string | null) => value === null || value === '' ? '—' : Number(value).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = (value: string | null, context: StaffAppContext) => value ? formatDate(value, context.settings.dateFormat) : '—';
const permittedType = (member: { is_temp: boolean }, context: StaffAppContext) => member.is_temp ? !!context.permissions.membersTemp : !!context.permissions.membersList;

function Button({ children, onClick, secondary = false, disabled = false, type = 'button' }: { children: ReactNode; onClick?: () => void; secondary?: boolean; disabled?: boolean; type?: 'button' | 'submit' }) {
  return <button type={type} onClick={onClick} disabled={disabled} className={`members-button${secondary ? ' members-button--secondary' : ''}`}>{children}</button>;
}

function Avatar({ name, photo }: { name: string; photo: string | null }) {
  return photo ? <img className="members-avatar" src={photo} alt="" /> : <span className="members-avatar members-avatar--placeholder" aria-hidden="true">{name.trim().charAt(0).toUpperCase() || <UserRound size={18} />}</span>;
}

function Status({ member }: { member: { is_active: boolean; is_verified: boolean; is_temp: boolean } }) {
  return <span className="members-statuses"><span className={`members-badge ${member.is_active ? 'members-badge--active' : ''}`}>{member.is_active ? 'Active' : 'Inactive'}</span><span className="members-badge">{member.is_verified ? 'Verified' : 'Unverified'}</span>{member.is_temp && <span className="members-badge">Temporary</span>}</span>;
}

function State({ children, error = false }: { children: ReactNode; error?: boolean }) {
  return <div className={`members-state${error ? ' members-state--error' : ''}`} role={error ? 'alert' : 'status'}>{children}</div>;
}

function MemberList({ context, navigate }: Props) {
  const canList = !!context.permissions.membersList;
  const canTemp = !!context.permissions.membersTemp;
  const [tab, setTab] = useState<'regular' | 'temp'>(canList ? 'regular' : 'temp');
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [active, setActive] = useState('');
  const [page, setPage] = useState(1);
  const [data, setData] = useState<MemberListResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [retry, setRetry] = useState(0);

  useEffect(() => {
    const timer = window.setTimeout(() => { setPage(1); setQuery(search.trim()); }, 300);
    return () => window.clearTimeout(timer);
  }, [search]);

  useEffect(() => {
    if (!canList && !canTemp) { setLoading(false); return; }
    const controller = new AbortController();
    setLoading(true); setError(''); setData(null);
    const params = new URLSearchParams({ is_temp: tab === 'temp' ? '1' : '0', page: String(page), per_page: '15' });
    if (query) params.set('search', query);
    if (active) params.set('active', active);
    requestApi<MemberListResponse>(`/api/members?${params}`, { signal: controller.signal }).then((result) => {
      if (!controller.signal.aborted) setData(result);
    }).catch((err: unknown) => { if (!controller.signal.aborted) setError(messageOf(err)); }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [tab, query, active, page, retry, canList, canTemp]);

  if (!canList && !canTemp) return <section className="members-page"><State error>You do not have permission to view members.</State></section>;
  const selectTab = (next: 'regular' | 'temp') => { setTab(next); setPage(1); };
  const canCreate = canList && !!context.permissions.membersCreate && !!data?.permissions.create;
  return <section className="members-page">
    <header className="members-heading"><div><p className="members-eyebrow">PEOPLE</p><h1>Members</h1><p>Manage your members and keep track of their activity.</p></div>{canCreate && <Button onClick={() => navigate('/members/new')}><Plus size={16} /> Onboard Member</Button>}</header>
    <div className="members-panel">
      <div className="members-toolbar"><div className="members-tabs" aria-label="Member type">{canList && <button type="button" className={tab === 'regular' ? 'is-selected' : ''} aria-pressed={tab === 'regular'} onClick={() => selectTab('regular')}>Members</button>}{canTemp && <button type="button" className={tab === 'temp' ? 'is-selected' : ''} aria-pressed={tab === 'temp'} onClick={() => selectTab('temp')}>Temporary members</button>}</div>
        <div className="members-filters"><label className="members-search"><Search size={16} aria-hidden="true" /><span className="members-sr-only">Search members</span><input type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search name, ID, email or phone" /></label><label><span className="members-sr-only">Filter by status</span><select value={active} onChange={(event) => { setPage(1); setActive(event.target.value); }}><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label></div>
      </div>
      {loading ? <State>Loading members…</State> : error ? <State error>{error} <button type="button" className="members-text-button" onClick={() => setRetry(retry + 1)}>Try again</button></State> : data && data.data.length === 0 ? <State>No members found. Try a different search or filter.</State> : data && <>
        <p className="members-scroll-hint" id="members-scroll-hint">Scroll horizontally to see all columns.</p>
        <div className="members-table-wrap" role="region" aria-label="Members table" aria-describedby="members-scroll-hint" tabIndex={0}><table className="members-table" aria-label={tab === 'temp' ? 'Temporary members' : 'Members'}><thead><tr><th scope="col">ID</th><th scope="col">Name</th><th scope="col">Tags</th><th scope="col">Plan</th><th scope="col">Expiry date</th><th scope="col">Last visit date</th><th scope="col">Outstanding</th></tr></thead><tbody>{data.data.map((member) => <tr key={member.id}><td>{text(member.biometric_member_id)}</td><td><button className="members-name-button" type="button" onClick={() => navigate(`/members/${member.id}`)}><Avatar name={member.name} photo={member.profile_photo_url} /><span>{member.name}</span><span className="members-sr-only">, view member details</span></button></td><td><Status member={member} /></td><td>{text(member.plan_name)}</td><td>{date(member.membership_expiry_date, context)}</td><td>{date(member.last_attendance_date, context)}</td><td>{money(member.total_outstanding_amount)}</td></tr>)}</tbody></table></div>
        <footer className="members-pagination"><span>{data.meta.total === 0 ? '0' : `${(data.meta.current_page - 1) * data.meta.per_page + 1}–${Math.min(data.meta.current_page * data.meta.per_page, data.meta.total)}`} of {data.meta.total} members</span><div><Button secondary disabled={page <= 1} onClick={() => setPage(page - 1)}><ChevronLeft size={16} /> Previous</Button><span>Page {data.meta.current_page} of {data.meta.last_page}</span><Button secondary disabled={page >= data.meta.last_page} onClick={() => setPage(page + 1)}>Next <ChevronRight size={16} /></Button></div></footer>
      </>}
    </div>
  </section>;
}

function Detail({ context, navigate, id }: Props & { id: string }) {
  const [result, setResult] = useState<MemberDetailResponse | null>(null);
  const [error, setError] = useState('');
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    const controller = new AbortController(); setResult(null); setError('');
    requestApi<MemberDetailResponse>(`/api/members/${id}`, { signal: controller.signal }).then((data) => { if (!controller.signal.aborted) setResult(data); }).catch((err: unknown) => { if (!controller.signal.aborted) setError(messageOf(err)); });
    return () => controller.abort();
  }, [id, retry]);
  return <section className="members-page"><button className="members-back" type="button" onClick={() => navigate('/members')}><ArrowLeft size={16} /> Members</button>
    {error ? <State error>{error} <button className="members-text-button" type="button" onClick={() => setRetry(retry + 1)}>Try again</button></State> : !result ? <State>Loading member…</State> : !permittedType(result.data, context) ? <State error>You do not have permission to view this member.</State> : <MemberProfile context={context} navigate={navigate} result={result} />}
  </section>;
}

function MemberProfile({ context, navigate, result }: { context: StaffAppContext; navigate: Props['navigate']; result: MemberDetailResponse }) {
  const m = result.data;
  const groups: { title: string; fields: [string, string][] }[] = [
    { title: 'Personal details', fields: [['Gender', text(m.gender)], ['Date of birth', date(m.date_of_birth, context)], ['NIC', text(m.nic)], ['Address', text(m.address)]] },
    { title: 'Contact details', fields: [['Phone', text(m.phone_number)], ['Email', text(m.email)], ['SMS updates', m.allow_sms ? 'Allowed' : 'Not allowed'], ['WhatsApp updates', m.allow_whatsapp ? 'Allowed' : 'Not allowed'], ['WhatsApp number', text(m.whatsapp_number)]] },
    { title: 'Membership', fields: [['Payment plan', text(m.payment_plan)], ['Plan price', money(m.price)], ['Admission fee', money(m.admission_fee)], ['Wallet balance', money(m.current_balance)], ['Joined', date(m.joined_date, context)]] },
    { title: 'Additional information', fields: [['Registration source', text(m.registration_source)], ['Campaign', text(m.campaign?.title)], ['Campaign status', text(m.campaign?.status)], ['Created', date(m.created_at, context)], ['Last biometric sync', m.biometric_last_synced_at ? formatDateTime(m.biometric_last_synced_at, context.settings.dateFormat, context.settings.timeFormat) : '—'], ['Notes', text(m.comment)]] },
  ];
  return <><header className="members-heading members-profile-heading"><div className="members-profile-title"><Avatar name={m.name} photo={m.profile_photo_url} /><div><p className="members-eyebrow">MEMBER PROFILE</p><h1>{m.name}</h1><p>Member ID: {text(m.biometric_member_id)}</p><Status member={m} /></div></div>{context.permissions.membersEdit && result.permissions.edit && <Button secondary onClick={() => navigate(`/members/${m.id}/edit`)}>Edit member</Button>}</header><div className="members-detail-grid">{groups.map((group) => <section className="members-detail-section" key={group.title}><h2>{group.title}</h2><dl>{group.fields.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}</dl></section>)}</div></>;
}

const emptyForm = (): MemberForm => ({ name: '', gender: '', email: '', phone_number: '', allow_sms: true, allow_whatsapp: true, whatsapp_number: '', nic: '', date_of_birth: '', address: '', admission_fee: '', payment_plan_id: '', joined_date: today(), comment: '' });
function formFromMember(m: MemberDetail): MemberForm {
  return { name: m.name, gender: m.gender || '', email: m.email || '', phone_number: m.phone_number || '', allow_sms: m.allow_sms, allow_whatsapp: m.allow_whatsapp, whatsapp_number: m.whatsapp_number || '', nic: m.nic || '', date_of_birth: m.date_of_birth || '', address: m.address || '', admission_fee: m.admission_fee == null ? '' : String(m.admission_fee), payment_plan_id: m.payment_plan_id == null ? '' : String(m.payment_plan_id), joined_date: m.joined_date || '', comment: m.comment || '' };
}

function MemberEditor({ context, navigate, id }: Props & { id?: string }) {
  const editing = !!id;
  const [form, setForm] = useState<MemberForm>(emptyForm);
  const [plans, setPlans] = useState<PaymentPlan[]>([]);
  const [generatedId, setGeneratedId] = useState('');
  const [allowed, setAllowed] = useState(!editing && !!context.permissions.membersCreate && !!context.permissions.membersList);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState('');
  const [submitError, setSubmitError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Errors>({});
  const [saving, setSaving] = useState(false);
  const [retry, setRetry] = useState(0);

  useEffect(() => {
    if ((!editing && (!context.permissions.membersCreate || !context.permissions.membersList)) || (editing && !context.permissions.membersEdit)) { setLoading(false); setAllowed(false); return; }
    const controller = new AbortController(); setLoading(true); setLoadError('');
    const load = async () => {
      const [planResult, memberResult, meta] = await Promise.all([
        requestApi<{ data: PaymentPlan[] }>('/api/members/form/payment-plans', { signal: controller.signal }),
        id ? requestApi<MemberDetailResponse>(`/api/members/${id}`, { signal: controller.signal }) : Promise.resolve(null),
        id ? Promise.resolve(null) : requestApi<{ generated_member_id: string }>('/api/members/meta', { signal: controller.signal }),
      ]);
      if (controller.signal.aborted) return;
      setPlans(planResult.data); setGeneratedId(meta?.generated_member_id || '');
      if (memberResult) { setAllowed(!!context.permissions.membersEdit && memberResult.permissions.edit && permittedType(memberResult.data, context)); setForm(formFromMember(memberResult.data)); }
      else setAllowed(!!context.permissions.membersCreate && !!context.permissions.membersList);
    };
    load().catch((err: unknown) => { if (!controller.signal.aborted) setLoadError(messageOf(err)); }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [id, editing, retry, context]);

  function change<K extends keyof MemberForm>(key: K, value: MemberForm[K]) {
    setForm((current) => ({ ...current, [key]: value }));
    setFieldErrors((current) => { const next = { ...current }; delete next[key]; return next; });
  }
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); if (saving || !allowed) return;
    setSaving(true); setSubmitError(''); setFieldErrors({});
    const body = { ...form, name: form.name.trim(), email: form.email.trim() || null, whatsapp_number: form.whatsapp_number.trim() || null, nic: form.nic.trim() || null, address: form.address.trim() || null, comment: form.comment.trim() || null, admission_fee: form.admission_fee === '' ? null : Number(form.admission_fee), payment_plan_id: Number(form.payment_plan_id) };
    try {
      if (id) { await requestApi(`/api/members/${id}`, { method: 'PUT', body }); navigate(`/members/${id}`); }
      else { const result = await requestApi<{ data: { id: number } }>('/api/members', { method: 'POST', body }); navigate(`/members/${result.data.id}`); }
    } catch (err: unknown) { setSubmitError(messageOf(err)); setFieldErrors(errorsOf(err)); }
    finally { setSaving(false); }
  }
  const field = (key: keyof MemberForm, label: string, input: ReactNode, required = false) => <div className="members-field"><label htmlFor={`member-${key}`}>{label}{required && <span aria-hidden="true"> *</span>}</label>{input}{fieldErrors[key]?.map((error, index) => <small className="members-field-error" key={index}>{error}</small>)}</div>;
  const attrs = (key: keyof MemberForm) => ({ id: `member-${key}`, 'aria-invalid': !!fieldErrors[key], 'aria-describedby': fieldErrors[key] ? 'member-error-summary' : undefined });
  const input = (key: Exclude<keyof MemberForm, 'allow_sms' | 'allow_whatsapp'>, type = 'text', extra: { required?: boolean; maxLength?: number; max?: string; min?: string; step?: string } = {}) => <input {...attrs(key)} type={type} value={form[key]} onChange={(event) => change(key, event.target.value)} {...extra} />;
  const availablePlans = plans.filter((plan) => plan.is_active || String(plan.id) === form.payment_plan_id);
  const selectedPlan = plans.find((plan) => String(plan.id) === form.payment_plan_id);
  return <section className="members-page"><button className="members-back" type="button" onClick={() => navigate(id ? `/members/${id}` : '/members')}><ArrowLeft size={16} /> {id ? 'Member profile' : 'Members'}</button>
    {loading ? <State>Loading form…</State> : loadError ? <State error>{loadError} <button className="members-text-button" type="button" onClick={() => setRetry(retry + 1)}>Try again</button></State> : !allowed ? <State error>You do not have permission to {editing ? 'edit this member' : 'onboard members'}.</State> : <>
      <header className="members-heading"><div><p className="members-eyebrow">MEMBERS</p><h1>{editing ? 'Edit member' : 'Onboard Member'}</h1><p>{editing ? 'Update member information and preferences.' : 'Add a new member to your workspace.'}</p></div></header>
      <form className="members-form" onSubmit={submit}>
        {submitError && <div className="members-form-error" role="alert" id="member-error-summary"><strong>Could not save member</strong><span>{submitError}</span>{Object.entries(fieldErrors).map(([key, messages]) => messages.map((message, index) => <span key={`${key}-${index}`}>{message}</span>))}</div>}
        <section className="members-form-section"><div className="members-section-heading"><h2>Personal details</h2><p>Basic information about the member.</p></div><div className="members-form-grid">{!editing && generatedId && <div className="members-field"><span className="members-field-label">Member ID</span><span className="members-readonly">{generatedId} <small>(assigned on save)</small></span></div>}{field('name', 'Full name', input('name', 'text', { required: true, maxLength: 200 }), true)}{field('nic', 'NIC', input('nic', 'text', { maxLength: 50 }))}{field('gender', 'Gender', <select {...attrs('gender')} value={form.gender} required onChange={(event) => change('gender', event.target.value)}><option value="">Select gender</option><option value="male">Male</option><option value="female">Female</option></select>, true)}{field('date_of_birth', 'Date of birth', input('date_of_birth', 'date', { required: true, max: today() }), true)}</div></section>
        <section className="members-form-section"><div className="members-section-heading"><h2>Contact details</h2><p>How to reach this member.</p></div><div className="members-form-grid">{field('phone_number', 'Phone number', input('phone_number', 'tel', { required: true, maxLength: 20 }), true)}{field('email', 'Email address', input('email', 'email'))}{field('whatsapp_number', 'WhatsApp number', input('whatsapp_number', 'tel', { maxLength: 20 }))}{field('address', 'Address', <textarea {...attrs('address')} value={form.address} maxLength={1000} rows={2} onChange={(event) => change('address', event.target.value)} />)}<div className="members-checks"><label><input type="checkbox" checked={form.allow_sms} onChange={(event) => change('allow_sms', event.target.checked)} /> Allow SMS updates</label><label><input type="checkbox" checked={form.allow_whatsapp} onChange={(event) => change('allow_whatsapp', event.target.checked)} /> Allow WhatsApp updates</label></div></div></section>
        <section className="members-form-section"><div className="members-section-heading"><h2>Membership details</h2><p>Choose the member’s default plan.</p></div><div className="members-form-grid">{field('payment_plan_id', 'Payment plan', <select {...attrs('payment_plan_id')} value={form.payment_plan_id} required onChange={(event) => change('payment_plan_id', event.target.value)}><option value="">Select a plan</option>{availablePlans.map((plan) => <option key={plan.id} value={plan.id}>{plan.name}{plan.is_active ? '' : ' (inactive)'}</option>)}</select>, true)}<div className="members-field"><span className="members-field-label">Plan price</span><span className="members-readonly">{selectedPlan ? money(selectedPlan.price) : '—'}</span></div>{field('admission_fee', 'Admission fee', input('admission_fee', 'number', { min: '0', step: 'any' }))}{field('joined_date', 'Joined date', input('joined_date', 'date', { required: true }), true)}{field('comment', 'Notes', <textarea {...attrs('comment')} value={form.comment} maxLength={2000} rows={3} onChange={(event) => change('comment', event.target.value)} />)}</div>{availablePlans.length === 0 && <p className="members-hint">No active payment plans are available. A plan is required to save this member.</p>}</section>
        <div className="members-form-actions"><span>Fields marked * are required.</span><div><Button secondary disabled={saving} onClick={() => navigate(id ? `/members/${id}` : '/members')}>Cancel</Button><Button type="submit" disabled={saving || availablePlans.length === 0}>{saving ? 'Saving…' : editing ? 'Save changes' : 'Onboard Member'}</Button></div></div>
      </form>
    </>}
  </section>;
}

export default function MembersPage(props: Props) {
  const path = props.path.replace(/\/+$/, '') || '/members';
  if (!props.context.permissions.membersList && !props.context.permissions.membersTemp) return <section className="members-page"><State error>You do not have permission to view members.</State></section>;
  if (path === '/members') return <MemberList key="list" {...props} />;
  if (path === '/members/new') return <MemberEditor key="new" {...props} />;
  const match = /^\/members\/(\d+)(\/edit)?$/.exec(path);
  if (match) return match[2] ? <MemberEditor key={`edit-${match[1]}`} {...props} id={match[1]} /> : <Detail key={match[1]} {...props} id={match[1]} />;
  return <section className="members-page"><State error>Member page not found.</State><Button secondary onClick={() => props.navigate('/members')}>Back to members</Button></section>;
}