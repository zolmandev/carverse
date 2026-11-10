import { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowDownToLine,
    ArrowRight,
    Bell,
    BookOpen,
    Car,
    Check,
    ChevronRight,
    ClipboardList,
    ExternalLink,
    Fuel,
    Gauge,
    LayoutDashboard,
    MapPin,
    Plus,
    Search,
    Settings,
    ShieldCheck,
    ShoppingBag,
    Users,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import { Complaint, Transaction, Vehicle, WorkspaceProps } from './types';
import { ActionForm, Badge, Card, date, Details, Empty, Field, money, Pager } from './ui';
import './workspace.css';
const options = (a: string[]) => a.map((x) => ({ value: x, label: x }));
const titles: Record<string, [string, string]> = {
    overview: ['Your car. In good hands.', 'Everything you need to keep your car moving, in one place.'],
    garage: ['My garage', 'A little context makes every recommendation better.'],
    parts: ['The right part starts here.', 'Find parts, check compatibility, and ask the supplier before you buy.'],
    providers: ['Find your specialist', 'Explore approved garages and parts suppliers around Nairobi.'],
    requests: ['Requests & bookings', 'From the first quote to the final check, follow every step.'],
    passport: ['Your vehicle’s story', 'Keep the work, the receipts, and the details that matter.'],
    reminders: ['Stay one service ahead.', 'Maintenance based on the dates and mileage you record.'],
    community: ['A little knowledge. A better drive.', 'Practical guides and communities for the road ahead.'],
    provider: ['Provider workspace', 'Respond to requests, manage parts, and keep customers informed.'],
    admin: ['Pilot operations', 'Review providers, resolve complaints, and understand the pilot.'],
    preferences: ['Your preferences', 'Manage reminders, profile details, and your data.'],
};
function VehicleFields({ vehicle }: { vehicle?: Vehicle }) {
    return (
        <>
            <Field name="photo" label="Vehicle photo (optional, JPEG / PNG, up to 5 MB)" type="file" required={false} />
            <Field name="make" label="Make" value={vehicle?.make} />
            <Field name="model" label="Model" value={vehicle?.model} />
            <Field name="year" label="Year" type="number" min={1950} max={new Date().getFullYear() + 1} value={vehicle?.year ?? 2016} />
            <Field name="variant" label="Variant (optional)" required={false} value={vehicle?.variant} />
            <Field name="engine_code" label="Engine code (optional)" required={false} value={vehicle?.engine_code} />
            <Field
                name="fuel_type"
                label="Fuel"
                value={vehicle?.fuel_type ?? 'Petrol'}
                options={options(['Petrol', 'Diesel', 'Hybrid', 'Electric'])}
            />
            <Field
                name="transmission"
                label="Transmission"
                value={vehicle?.transmission ?? 'Automatic'}
                options={options(['Automatic', 'Manual', 'CVT'])}
            />
            <Field name="registration" label="Registration (private)" required={false} value={vehicle?.registration} />
            <Field name="identifier" label="VIN / chassis / frame number (private)" required={false} value={vehicle?.identifier} />
            <Field name="mileage" label="Odometer (km)" type="number" min={vehicle?.mileage ?? 0} value={vehicle?.mileage ?? 0} />
            <Field
                name="mileage_date"
                label="Reading date"
                type="date"
                value={vehicle?.mileage_date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10)}
            />
        </>
    );
}
export default function Workspace(p: WorkspaceProps) {
    const { auth } = usePage<SharedData>().props;
    const [adding, setAdding] = useState(false);
    const [mobile, setMobile] = useState(false);
    const user = auth.user;
    const active = p.activeVehicle;
    const vehicleOptions = p.vehicles.map((v) => ({ value: v.id, label: `${v.make} ${v.model} · ${v.registration || v.year}` }));
    const orgOptions = p.organizations.filter((o) => o.status === 'approved').map((o) => ({ value: o.id, label: o.name }));
    const isProvider = orgOptions.length > 0;
    const title = titles[p.section] ?? titles.overview;
    const open = p.requests.data.filter((r) => !['completed', 'cancelled'].includes(r.status));
    const nav = [
        ['overview', 'Overview', LayoutDashboard],
        ['garage', 'My garage', Car],
        ['parts', 'Find parts', ShoppingBag],
        ['providers', 'Find specialists', Wrench],
        ['requests', 'Requests & bookings', ClipboardList],
        ['passport', 'Vehicle passport', BookOpen],
        ['reminders', 'Maintenance', Bell],
        ['community', 'Guides & community', Users],
    ] as const;
    function requestForm(partId?: number, orgId?: number) {
        return p.vehicles.length ? (
            <ActionForm action="/requests" label="Send quote request" wide>
                <Field name="vehicle_id" label="Vehicle" options={vehicleOptions} value={active?.id} />
                <Field
                    name="kind"
                    label="Request type"
                    options={options(['service', 'part'])}
                    value={partId || p.providers.find((o) => o.id === orgId)?.kind === 'supplier' ? 'part' : 'service'}
                />
                {partId && <input type="hidden" name="part_id" value={partId} />}
                <Field
                    name="organization_id"
                    label="Preferred provider (optional)"
                    required={false}
                    options={p.providers.map((o) => ({ value: o.id, label: `${o.name} (${o.kind})` }))}
                    value={orgId ?? ''}
                />
                <Field name="title" label="What do you need?" />
                <Field name="notes" label="Describe the work, symptoms, or part details" type="textarea" />
                <Field name="evidence" label="Photo or PDF (optional, up to 5 MB)" type="file" required={false} />
            </ActionForm>
        ) : (
            <Empty>Add your first vehicle in My garage to request a quote.</Empty>
        );
    }
    function complaint(c: Complaint, owner: boolean) {
        return (
            <Details key={c.id} title={`Complaint #${c.id} · ${c.status}`}>
                <p>{c.body}</p>
                {c.attachments.map((a) => (
                    <a key={a.id} href={`/attachments/${a.id}`} className="cv-link">
                        {a.name}
                    </a>
                ))}
                {c.provider_response && <p>Provider response: {c.provider_response}</p>}
                {c.decision && <p>Decision: {c.decision}</p>}
                {c.appeal && <p>Appeal: {c.appeal}</p>}
                {!owner && (
                    <ActionForm action={`/complaints/${c.id}`} label="Send response">
                        <input type="hidden" name="action" value="respond" />
                        <Field name="provider_response" label="Private provider response" type="textarea" />
                    </ActionForm>
                )}
                {['resolved', 'dismissed'].includes(c.status) && (
                    <ActionForm action={`/complaints/${c.id}`} label="Submit appeal">
                        <input type="hidden" name="action" value="appeal" />
                        <Field name="appeal" label="Appeal and supporting reason" type="textarea" />
                    </ActionForm>
                )}
            </Details>
        );
    }
    function transaction(t: Transaction) {
        const owner = t.user_id === user.id;
        return (
            <Card key={t.id}>
                <div className="cv-row">
                    <span className="cv-eyebrow">
                        {t.request.kind === 'part' ? 'Parts order' : 'Service booking'} #{t.id}
                    </span>
                    <Badge tone={t.status === 'completed' ? 'green' : ''}>{t.status.replaceAll('_', ' ')}</Badge>
                </div>
                <h3>{t.request.title}</h3>
                <p>
                    {t.organization.name} · {t.request.vehicle.make} {t.request.vehicle.model}
                </p>
                <div className="cv-price-row">
                    <strong>{money(t.total_minor)}</strong>
                    <Badge tone={t.payment_status === 'paid' ? 'green' : 'amber'}>{t.payment_status}</Badge>
                </div>
                <Details title="Agreed quote">
                    <ul>
                        {t.quote_snapshot.items.map((item, i) => (
                            <li key={i}>
                                {item.description} — {money(item.amount_minor)}
                            </li>
                        ))}
                    </ul>
                    <p>Expected duration: {t.quote_snapshot.duration}</p>
                </Details>
                <div className="cv-actions">
                    {owner && t.payment_status !== 'paid' && !['cancelled', 'disputed'].includes(t.status) && (
                        <Details title={p.paymentMode === 'demo' ? 'Pay in demo mode' : 'Pay with sandbox M-Pesa'}>
                            <p>
                                {p.paymentMode === 'demo'
                                    ? 'Demo only. No money is moved.'
                                    : 'Safaricom sandbox. Status changes only after server verification.'}
                            </p>
                            <ActionForm
                                action={`/transactions/${t.id}/pay`}
                                label={p.paymentMode === 'demo' ? 'Record demo payment' : 'Send sandbox request'}
                            >
                                <Field name="phone" label="M-Pesa phone" value={String(user.phone ?? '')} type="tel" />
                            </ActionForm>
                        </Details>
                    )}
                    {!owner && t.status === 'accepted' && (
                        <ActionForm action={`/transactions/${t.id}/status`} label="Start work">
                            <input type="hidden" name="status" value="in_progress" />
                        </ActionForm>
                    )}
                    {!owner && t.status === 'in_progress' && (
                        <ActionForm action={`/transactions/${t.id}/status`} label="Ready for owner confirmation">
                            <input type="hidden" name="status" value="awaiting_confirmation" />
                        </ActionForm>
                    )}
                    {owner && t.status === 'awaiting_confirmation' && (
                        <ActionForm action={`/transactions/${t.id}/status`} label="Confirm completion">
                            <input type="hidden" name="status" value="completed" />
                        </ActionForm>
                    )}
                    {['accepted', 'in_progress', 'awaiting_confirmation'].includes(t.status) && (
                        <ActionForm action={`/transactions/${t.id}/status`} label="Raise fulfilment dispute">
                            <input type="hidden" name="status" value="disputed" />
                        </ActionForm>
                    )}
                    {t.status === 'accepted' && (
                        <ActionForm action={`/transactions/${t.id}/status`} label="Cancel">
                            <input type="hidden" name="status" value="cancelled" />
                        </ActionForm>
                    )}
                </div>
                {owner && t.status === 'completed' && !t.review && (
                    <Details title="Review this completed transaction">
                        <ActionForm action={`/transactions/${t.id}/reviews`} label="Publish review" wide>
                            {['quality', 'communication', 'timeliness', 'value'].map((k) => (
                                <Field
                                    key={k}
                                    name={k}
                                    label={k === 'quality' ? 'Workmanship / product quality' : k}
                                    options={[1, 2, 3, 4, 5].map((v) => ({ value: v, label: `${v} / 5` }))}
                                    value={5}
                                />
                            ))}
                            <Field name="body" label="Your experience" type="textarea" />
                        </ActionForm>
                    </Details>
                )}
                {t.review && (
                    <div className="cv-note">
                        <strong>Transaction-linked review · {t.review.quality}/5 quality</strong>
                        <p>{t.review.body}</p>
                        {t.review.response && <p>Provider: {t.review.response}</p>}
                        {!owner && (
                            <ActionForm action={`/reviews/${t.review.id}/response`} label="Reply to review">
                                <Field name="response" label="Public provider response" type="textarea" />
                            </ActionForm>
                        )}
                    </div>
                )}
                {owner && (
                    <Details title="Open a private complaint">
                        <ActionForm action={`/transactions/${t.id}/complaints`} label="Submit private complaint">
                            <Field name="body" label="What happened?" type="textarea" />
                            <Field name="evidence" label="Supporting evidence (PDF / image, up to 5 MB)" type="file" />
                        </ActionForm>
                    </Details>
                )}
                {t.complaints.map((c) => complaint(c, owner))}
            </Card>
        );
    }
    return (
        <div className="cv-app">
            <Head title={title[0]} />
            <aside className={`cv-sidebar ${mobile ? 'show' : ''}`}>
                <Link href="/app" className="cv-logo">
                    <span className="cv-logo-mark">
                        <Car size={23} />
                    </span>
                    carverse<span className="cv-ke">KE</span>
                </Link>
                <span className="cv-pilot">NAIROBI PILOT</span>
                <nav>
                    {nav.map(([key, label, Icon]) => (
                        <Link key={key} href={`/app/${key}`} className={p.section === key ? 'selected' : ''} onClick={() => setMobile(false)}>
                            <Icon size={19} />
                            {label}
                            {key === 'requests' && open.length > 0 && <span className="cv-count">{open.length}</span>}
                        </Link>
                    ))}
                </nav>
                <div className="cv-sidebar-bottom">
                    <Link href="/app/provider">
                        <Wrench size={18} />
                        {isProvider ? 'Provider workspace' : 'Become a provider'}
                    </Link>
                    {(p.admin || p.moderation) && (
                        <Link href="/app/admin">
                            <ShieldCheck size={18} />
                            Pilot operations
                        </Link>
                    )}
                    <Link href="/app/preferences">
                        <Settings size={18} />
                        Preferences
                    </Link>
                    <div className="cv-person">
                        <span>{user.name.slice(0, 1)}</span>
                        <div>
                            <strong>{user.name}</strong>
                            <small>Vehicle owner{isProvider ? ' & provider' : ''}</small>
                        </div>
                        <button onClick={() => router.post('/logout')} aria-label="Sign out">
                            ↗
                        </button>
                    </div>
                </div>
            </aside>
            <div className="cv-main">
                <header className="cv-topbar">
                    <button className="cv-mobile-toggle" aria-label="Toggle menu" onClick={() => setMobile(!mobile)}>
                        ☰
                    </button>
                    <div className="cv-breadcrumb">
                        My workspace <ChevronRight size={14} /> <strong>{p.section === 'overview' ? 'Overview' : title[0]}</strong>
                    </div>
                    <div className="cv-top-actions">
                        <MapPin size={15} />
                        <span>Nairobi, Kenya</span>
                        <Link href="/app/reminders" aria-label="Maintenance notifications" className="cv-bell">
                            <Bell size={19} />
                            {p.notifications.length > 0 && <i />}
                        </Link>
                        <span className="cv-avatar">{user.name.slice(0, 1)}</span>
                    </div>
                </header>
                <main className="cv-content">
                    <div className="cv-page-heading">
                        <div>
                            <p className="cv-eyebrow">
                                {p.section === 'overview' ? `WELCOME BACK, ${user.name.split(' ')[0].toUpperCase()}` : 'CARVERSE KE / YOUR WORKSPACE'}
                            </p>
                            <h1>{title[0]}</h1>
                            <p>{title[1]}</p>
                        </div>
                        <button className="cv-button" onClick={() => setAdding(!adding)}>
                            <Plus size={17} />
                            {adding ? 'Close request' : 'New request'}
                        </button>
                    </div>
                    {p.demoData && (
                        <div className="cv-demo-label">FICTIONAL DEMO DATA · Provider names, listings, transactions, and reviews are examples.</div>
                    )}
                    {p.flash.message && (
                        <div role="status" className="cv-success">
                            <Check size={17} />
                            {p.flash.message}
                        </div>
                    )}
                    {p.flash.share_url && (
                        <div className="cv-note">
                            <a href={p.flash.share_url}>{p.flash.share_url}</a>
                        </div>
                    )}
                    {adding && <Card title="Request a part or service">{requestForm()}</Card>}
                    {p.section === 'overview' && !p.vehicles.length && (
                        <Card title="Start with your first vehicle">
                            <p>Add your car once, and bring the right context to every part and service request.</p>
                            <ActionForm action="/vehicles" label="Add my first vehicle" wide>
                                <VehicleFields />
                            </ActionForm>
                        </Card>
                    )}
                    {active && (
                        <div className="cv-context">
                            <Car size={18} />
                            <span>YOUR ACTIVE VEHICLE</span>
                            <select
                                aria-label="Active vehicle"
                                value={active.id}
                                onChange={(e) => router.post(`/vehicles/${e.target.value}/active`, {}, { preserveScroll: true })}
                            >
                                {vehicleOptions.map((v) => (
                                    <option key={v.value} value={v.value}>
                                        {v.label}
                                    </option>
                                ))}
                            </select>
                            <span className="cv-context-mileage">{active.mileage.toLocaleString()} km recorded</span>
                            <Link href="/app/garage">
                                Manage <ArrowRight size={14} />
                            </Link>
                        </div>
                    )}
                    {p.section === 'overview' && (
                        <>
                            <div className="cv-overview-grid">
                                <section className="cv-hero">
                                    <div>
                                        <Badge tone="hero">YOUR GARAGE, CONNECTED</Badge>
                                        <h2>
                                            More confidence.
                                            <br />
                                            Every kilometre.
                                        </h2>
                                        <p>Find the right parts and trusted specialists for your car—and keep proof of every job.</p>
                                        <Link href="/app/providers" className="cv-button light">
                                            Find a specialist <ArrowRight size={16} />
                                        </Link>
                                    </div>
                                    <div className="cv-car-art" aria-hidden="true">
                                        <svg viewBox="0 0 500 260">
                                            <ellipse cx="260" cy="223" rx="210" ry="14" fill="#000" opacity=".22" />
                                            <path
                                                d="M35 165L60 125L145 110L205 53Q220 43 270 45L350 53L412 112L456 130L475 180L462 205H40Z"
                                                fill="#f1f2f3"
                                            />
                                            <path d="M161 108L214 60L267 58L269 108Z" fill="#373f49" />
                                            <path d="M281 60L345 66L391 108H282Z" fill="#414b56" />
                                            <path d="M45 165H469L461 193H40Z" fill="#bdc3c9" />
                                            <path d="M50 130L112 125L99 145H43Z" fill="#fff" />
                                            <path d="M420 121L452 137L459 151H424Z" fill="#d32f2f" />
                                            <path d="M129 117H388" stroke="#a2aab1" strokeWidth="3" />
                                            <path d="M275 118V180M158 117L149 174M394 118L414 171" stroke="#b0b7bd" strokeWidth="2" />
                                            <circle cx="126" cy="195" r="43" fill="#171d24" />
                                            <circle cx="126" cy="195" r="26" fill="#838d98" />
                                            <circle cx="126" cy="195" r="13" fill="#ced3d9" />
                                            <circle cx="395" cy="195" r="43" fill="#171d24" />
                                            <circle cx="395" cy="195" r="26" fill="#838d98" />
                                            <circle cx="395" cy="195" r="13" fill="#ced3d9" />
                                        </svg>
                                        <span>{active ? `${active.make.toUpperCase()} ${active.model.toUpperCase()}` : 'BUILT AROUND YOUR CAR'}</span>
                                    </div>
                                </section>
                                <Card className="cv-next-service">
                                    <div className="cv-row">
                                        <span className="cv-icon-bg">
                                            <Bell size={21} />
                                        </span>
                                        <Badge tone="amber">PLAN AHEAD</Badge>
                                    </div>
                                    <h2>Your next service</h2>
                                    {p.reminders[0] ? (
                                        <>
                                            <h3>{p.reminders[0].title}</h3>
                                            <p>
                                                {p.reminders[0].due_date
                                                    ? date(p.reminders[0].due_date)
                                                    : `At ${p.reminders[0].due_mileage?.toLocaleString()} km`}
                                            </p>
                                            <small>{p.reminders[0].source}</small>
                                        </>
                                    ) : (
                                        <>
                                            <h3>A little care goes a long way.</h3>
                                            <p>Add a reminder based on your last service.</p>
                                        </>
                                    )}
                                    <Link className="cv-link" href="/app/reminders">
                                        View maintenance <ArrowRight size={15} />
                                    </Link>
                                </Card>
                            </div>
                            <div className="cv-stat-grid">
                                {[
                                    [Car, 'Vehicles in your garage', p.vehicles.length, 'Your cars, one place'],
                                    [ClipboardList, 'Open requests', open.length, 'Quotes and jobs in progress'],
                                    [BookOpen, 'Service records', p.records.length, 'A history you can refer to'],
                                    [
                                        ShieldCheck,
                                        'Completed transactions',
                                        p.transactions.data.filter((t) => t.status === 'completed').length,
                                        'On this page of your history',
                                    ],
                                ].map(([Icon, label, value, sub], i) => {
                                    const I = Icon as typeof Car;
                                    return (
                                        <div className="cv-stat" key={i}>
                                            <I size={20} />
                                            <span>{String(label)}</span>
                                            <strong>{String(value).padStart(2, '0')}</strong>
                                            <small>{String(sub)}</small>
                                        </div>
                                    );
                                })}
                            </div>
                            <div className="cv-section-heading">
                                <h2>What does your car need?</h2>
                                <span>TAKE THE NEXT STEP</span>
                            </div>
                            <div className="cv-three-grid">
                                {[
                                    [
                                        ShoppingBag,
                                        'Find the right parts',
                                        'Search parts and ask a supplier to confirm fitment.',
                                        'Explore parts',
                                        'parts',
                                    ],
                                    [
                                        Wrench,
                                        'Book a trusted specialist',
                                        'Find a garage with the expertise your car needs.',
                                        'Find a garage',
                                        'providers',
                                    ],
                                    [
                                        BookOpen,
                                        'Keep proof of every job',
                                        'Save service details, invoices, and your vehicle’s story.',
                                        'Open passport',
                                        'passport',
                                    ],
                                ].map(([Icon, t, d, l, url]) => {
                                    const I = Icon as typeof Car;
                                    return (
                                        <Link href={`/app/${url}`} className="cv-quick-card" key={String(t)}>
                                            <span className="cv-icon-bg">
                                                <I size={22} />
                                            </span>
                                            <h3>{String(t)}</h3>
                                            <p>{String(d)}</p>
                                            <span className="cv-link">
                                                {String(l)} <ArrowRight size={15} />
                                            </span>
                                        </Link>
                                    );
                                })}
                            </div>
                            <div className="cv-section-heading">
                                <h2>Recent requests</h2>
                                <Link href="/app/requests" className="cv-link">
                                    View all <ArrowRight size={15} />
                                </Link>
                            </div>
                            <Card>
                                {p.requests.data.length ? (
                                    p.requests.data.slice(0, 4).map((r) => (
                                        <Link href="/app/requests" className="cv-request-row" key={r.id}>
                                            <span className="cv-icon-bg">{r.kind === 'part' ? <ShoppingBag size={20} /> : <Wrench size={20} />}</span>
                                            <div>
                                                <strong>{r.title}</strong>
                                                <small>
                                                    {r.vehicle.make} {r.vehicle.model} · Request #{r.id}
                                                </small>
                                            </div>
                                            <Badge tone={r.status === 'quoted' ? 'green' : ''}>{r.status.replaceAll('_', ' ')}</Badge>
                                            <ChevronRight size={16} />
                                        </Link>
                                    ))
                                ) : (
                                    <Empty>Your first request starts here. Add a vehicle and tell us what it needs.</Empty>
                                )}
                            </Card>
                            <div className="cv-trust-note">
                                <ShieldCheck size={19} />
                                <p>
                                    <strong>Clarity builds trust.</strong> Provider identity checks, part fitment, and service evidence are shown
                                    separately, so you know exactly what has been checked.
                                </p>
                            </div>
                        </>
                    )}
                    {p.section === 'garage' && (
                        <>
                            <div className="cv-two-grid">
                                {p.vehicles.map((v) => (
                                    <Card key={v.id}>
                                        <div className="cv-row">
                                            <Car size={30} />
                                            {active?.id === v.id && <Badge tone="green">Active vehicle</Badge>}
                                        </div>
                                        <h2>
                                            {v.make} {v.model}
                                        </h2>
                                        {v.photo_url && <img className="cv-uploaded-photo" src={v.photo_url} alt={`${v.make} ${v.model}`} />}
                                        <p>
                                            {v.year} · {v.variant} · {v.registration}
                                        </p>
                                        <div className="cv-vehicle-facts">
                                            <span>
                                                <Gauge size={16} />
                                                {v.mileage.toLocaleString()} km
                                            </span>
                                            <span>
                                                <Fuel size={16} />
                                                {v.fuel_type}
                                            </span>
                                            <span>{v.transmission}</span>
                                        </div>
                                        <small>
                                            Odometer recorded {date(v.mileage_date)}.{' '}
                                            {new Date(v.mileage_date) < new Date(Date.now() - 30 * 86400000)
                                                ? 'Please update your mileage; this reading is over 30 days old.'
                                                : ''}
                                        </small>
                                        <Details title="Edit vehicle / update mileage">
                                            <ActionForm action={`/vehicles/${v.id}`} method="PUT" wide>
                                                <VehicleFields vehicle={v} />
                                            </ActionForm>
                                        </Details>
                                        <ActionForm action={`/vehicles/${v.id}/active`} label="Set active vehicle" />
                                    </Card>
                                ))}
                            </div>
                            <Card title="Add a vehicle">
                                <p>Imported frame and chassis numbers are welcome. A 17-character VIN is not required.</p>
                                <ActionForm action="/vehicles" label="Add to my garage" wide>
                                    <VehicleFields />
                                </ActionForm>
                            </Card>
                        </>
                    )}
                    {p.section === 'parts' && (
                        <>
                            <form method="get" className="cv-search">
                                <Search size={19} />
                                <input name="q" defaultValue={p.filters.q} placeholder="Search parts or OEM reference" aria-label="Search parts" />
                                <input name="category" defaultValue={p.filters.category} placeholder="Category" aria-label="Part category" />
                                <button className="cv-button">Search</button>
                            </form>
                            <div className="cv-note">
                                Confirmed fitment requires an explicit assessment for your vehicle. Supplier identity checks do not certify part
                                authenticity.
                            </div>
                            <div className="cv-three-grid">
                                {p.parts.data.map((part) => (
                                    <Card key={part.id}>
                                        <div className="cv-part-image">
                                            {part.photo_url ? (
                                                <img src={part.photo_url} alt={part.title} />
                                            ) : (
                                                <ShoppingBag size={53} strokeWidth={1} />
                                            )}
                                            <span>{part.category}</span>
                                        </div>
                                        <div className="cv-row">
                                            <span className="cv-eyebrow">{part.brand}</span>
                                            <Badge>{part.condition}</Badge>
                                        </div>
                                        <h3>{part.title}</h3>
                                        <p>{part.description}</p>
                                        <small>OEM / reference: {part.oem_reference || 'Not supplied'}</small>
                                        <p>
                                            {part.organization.name} · {part.stock}
                                        </p>
                                        <Badge
                                            tone={
                                                part.fitment_status === 'confirmed'
                                                    ? 'green'
                                                    : part.fitment_status === 'incompatible'
                                                      ? 'red'
                                                      : 'amber'
                                            }
                                        >
                                            {
                                                {
                                                    confirmed: 'Confirmed for this vehicle',
                                                    potential: 'Potential match—confirmation required',
                                                    unknown: 'Compatibility unknown',
                                                    incompatible: 'Not compatible',
                                                }[part.fitment_status]
                                            }
                                        </Badge>
                                        {part.fitment_evidence && (
                                            <small className="cv-evidence">
                                                Assessment source: {part.fitment_evidence}
                                                {part.fitment_assessor ? ` · ${part.fitment_assessor}` : ''}
                                                {part.fitment_assessed_at ? ` · ${date(part.fitment_assessed_at)}` : ''}
                                            </small>
                                        )}
                                        <div className="cv-price-row">
                                            <strong>{part.price_minor ? money(part.price_minor) : 'Quote on request'}</strong>
                                        </div>
                                        <Details title="Request a quote">{requestForm(part.id, part.organization.id)}</Details>
                                    </Card>
                                ))}
                            </div>
                            {!p.parts.data.length && <Empty>No parts match your search. Try an OEM reference or a broader search.</Empty>}
                            <Pager links={p.parts.links} />
                        </>
                    )}
                    {p.section === 'providers' && (
                        <div className="cv-two-grid">
                            {p.providers.map((o) => (
                                <Card key={o.id}>
                                    <div className="cv-row">
                                        <span className="cv-icon-bg">
                                            <Wrench size={24} />
                                        </span>
                                        <Badge tone="green">Identity checked</Badge>
                                    </div>
                                    <span className="cv-eyebrow">{o.kind === 'garage' ? 'SPECIALIST GARAGE' : 'PARTS SUPPLIER'}</span>
                                    <h2>{o.name}</h2>
                                    {o.photo_url && <img className="cv-uploaded-photo" src={o.photo_url} alt={o.name} />}
                                    <p>
                                        <MapPin size={14} className="cv-inline" /> {o.location}
                                    </p>
                                    <p>{o.description}</p>
                                    <div className="cv-tags">{o.specialties?.map((s) => <Badge key={s}>{s}</Badge>)}</div>
                                    <p>
                                        {o.opening_hours} · {o.phone}
                                    </p>
                                    <small>
                                        Checked {o.verified_at ? date(o.verified_at) : 'date unavailable'}: {o.verification_scope}
                                    </small>
                                    <Details title={`Transaction-linked reviews (${o.review_count ?? 0})`}>
                                        <div className="cv-tags">
                                            {Object.entries(o.ratings ?? {}).map(([k, v]) => (
                                                <Badge key={k}>
                                                    {k}: {v ? Number(v).toFixed(1) : '—'} / 5
                                                </Badge>
                                            ))}
                                        </div>
                                        {o.public_reviews?.map((v, i) => (
                                            <p key={i}>
                                                {v.body}
                                                {v.response && <small>Provider: {v.response}</small>}
                                            </p>
                                        ))}
                                        {!o.review_count && <p>No completed-transaction reviews yet.</p>}
                                    </Details>
                                    <Details title="Request a quote from this provider">{requestForm(undefined, o.id)}</Details>
                                </Card>
                            ))}
                        </div>
                    )}
                    {(p.section === 'requests' || p.section === 'provider') && (
                        <>
                            <div className="cv-section-heading">
                                <h2>Quote requests</h2>
                                <span>{p.requests.data.length} ON THIS PAGE</span>
                            </div>
                            {p.requests.data.map((r) => (
                                <Card key={r.id}>
                                    <div className="cv-row">
                                        <span className="cv-eyebrow">
                                            REQUEST #{r.id} · {r.kind}
                                        </span>
                                        <Badge>{r.status.replaceAll('_', ' ')}</Badge>
                                    </div>
                                    <h2>{r.title}</h2>
                                    <p>
                                        {r.vehicle.make} {r.vehicle.model} · {r.vehicle.year} · {r.vehicle.variant} · {r.vehicle.engine_code} ·{' '}
                                        {r.vehicle.fuel_type}
                                    </p>
                                    <p>{r.notes}</p>
                                    {r.attachments.map((a) => (
                                        <a className="cv-link" key={a.id} href={`/attachments/${a.id}`}>
                                            {a.name}
                                        </a>
                                    ))}
                                    {r.user_id === user.id && ['requested', 'quoted'].includes(r.status) && (
                                        <ActionForm action={`/requests/${r.id}/cancel`} label="Cancel request" />
                                    )}
                                    <div className="cv-two-grid">
                                        {r.quotes.map((q) => (
                                            <div className="cv-quote" key={q.id}>
                                                <strong>{q.organization.name}</strong>
                                                {q.items.map((i, j) => (
                                                    <div className="cv-row" key={j}>
                                                        <span>{i.description}</span>
                                                        <span>{money(i.amount_minor)}</span>
                                                    </div>
                                                ))}
                                                <h3>{money(q.total_minor)}</h3>
                                                <small>
                                                    Valid until {date(q.valid_until)} · Expected {q.duration}
                                                </small>
                                                {r.user_id === user.id && !r.transaction && (
                                                    <ActionForm action={`/quotes/${q.id}/accept`} label="Accept this quote" />
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                    {isProvider && r.user_id !== user.id && ['requested', 'quoted'].includes(r.status) && (
                                        <Details title="Prepare an itemized quote">
                                            <ActionForm action={`/requests/${r.id}/quotes`} label="Send quote" wide>
                                                <Field name="organization_id" label="Your organization" options={orgOptions} />
                                                <Field name="items[0][description]" label="Labor / main item description" />
                                                <Field name="items[0][amount_kes]" label="Amount (KES)" type="number" min={1} />
                                                <Field name="items[1][description]" label="Parts / additional item (optional)" required={false} />
                                                <Field name="items[1][amount_kes]" required={false} label="Amount (KES)" type="number" min={1} />
                                                <Field name="duration" label="Expected duration" />
                                                <Field name="valid_until" label="Valid until" type="date" />
                                            </ActionForm>
                                        </Details>
                                    )}
                                </Card>
                            ))}
                            {!p.requests.data.length && <Empty>No requests yet. Use New request to get started.</Empty>}
                            <Pager links={p.requests.links} />
                            <div className="cv-section-heading">
                                <h2>Orders & service bookings</h2>
                                <Badge>{p.paymentMode === 'demo' ? 'Demo payments · no money moved' : 'M-Pesa sandbox'}</Badge>
                            </div>
                            <div className="cv-two-grid">{p.transactions.data.map(transaction)}</div>
                            <Pager links={p.transactions.links} />
                        </>
                    )}
                    {p.section === 'passport' && (
                        <>
                            <div className="cv-note">
                                Source labels describe who submitted a record and what evidence was reviewed. They do not guarantee a complete or
                                accurate vehicle history.
                            </div>
                            <Card title="Add a service entry">
                                <ActionForm action="/records" label="Save service entry" wide>
                                    <Field name="vehicle_id" label="Vehicle" options={vehicleOptions} value={active?.id} />
                                    <Field name="service_date" label="Service date" type="date" />
                                    <Field name="mileage" label="Mileage at service (km)" type="number" min={0} />
                                    <Field name="work" label="Work performed" type="textarea" />
                                    <Field name="parts_used" label="Parts used (optional)" required={false} />
                                    <Field
                                        name="evidence"
                                        label="Invoice / evidence (optional, PDF or image, up to 5 MB)"
                                        type="file"
                                        required={false}
                                    />
                                </ActionForm>
                            </Card>
                            <div className="cv-timeline">
                                {p.records.map((rec) => (
                                    <Card key={rec.id}>
                                        <div className="cv-row">
                                            <span className="cv-eyebrow">
                                                {date(rec.service_date)} · {rec.mileage.toLocaleString()} KM
                                            </span>
                                            <Badge tone={rec.provenance === 'supported by reviewed evidence' ? 'green' : ''}>{rec.provenance}</Badge>
                                        </div>
                                        <h2>{rec.work}</h2>
                                        <p>{rec.parts_used}</p>
                                        {rec.provider_name && <p>Provider: {rec.provider_name}</p>}
                                        <small>
                                            {p.vehicles.find((v) => v.id === rec.vehicle_id)?.make}{' '}
                                            {p.vehicles.find((v) => v.id === rec.vehicle_id)?.model} · Version {rec.version}
                                        </small>
                                        {rec.attachments.map((a) => (
                                            <a className="cv-link" key={a.id} href={`/attachments/${a.id}`}>
                                                <ArrowDownToLine size={15} />
                                                {a.name}
                                            </a>
                                        ))}
                                        <Details title="Correct this entry">
                                            <ActionForm action={`/records/${rec.id}/correct`} label="Save auditable correction">
                                                <Field
                                                    name="service_date"
                                                    label="Corrected service date"
                                                    type="date"
                                                    value={rec.service_date.slice(0, 10)}
                                                />
                                                <Field name="mileage" label="Corrected service mileage" type="number" min={0} value={rec.mileage} />
                                                <Field
                                                    name="parts_used"
                                                    label="Corrected parts used (optional)"
                                                    type="textarea"
                                                    value={rec.parts_used ?? ''}
                                                    required={false}
                                                />
                                                <Field name="work" label="Corrected work description" type="textarea" value={rec.work} />
                                                <Field
                                                    name="evidence"
                                                    label="Additional evidence (optional, up to 5 MB)"
                                                    type="file"
                                                    required={false}
                                                />
                                                <Field name="reason" label="Reason for correction" type="textarea" />
                                            </ActionForm>
                                            {rec.revisions.map((rev) => (
                                                <div className="cv-note" key={rev.id}>
                                                    <strong>
                                                        Version {rev.previous.version} · {date(rev.created_at)}
                                                    </strong>
                                                    <p>{rev.previous.work}</p>
                                                    <small>Correction reason: {rev.reason}</small>
                                                </div>
                                            ))}
                                        </Details>
                                    </Card>
                                ))}
                            </div>
                            {!p.records.length && <Empty>Your vehicle’s service story starts with its first entry.</Empty>}
                            {active && (
                                <Card title="Share selected records">
                                    <p>Read-only links exclude contact details, full vehicle identifiers, and all private attachments.</p>
                                    <ActionForm action={`/vehicles/${active.id}/shares`} label="Create sharing link">
                                        <Field name="days" label="Expires in days (1–30)" type="number" min={1} max={30} value={7} />
                                        {p.records
                                            .filter((rec) => rec.vehicle_id === active.id)
                                            .map((rec) => (
                                                <label className="cv-check" key={rec.id}>
                                                    <input type="checkbox" name="record_ids[]" value={rec.id} />
                                                    {date(rec.service_date)} · {rec.work}
                                                </label>
                                            ))}
                                    </ActionForm>
                                    {p.shares.map((s) => (
                                        <div className="cv-row" key={s.id}>
                                            <small>
                                                Link #{s.id} · Expires {date(s.expires_at)} · {s.revoked_at ? 'Revoked' : 'Active until expiry'}
                                            </small>
                                            {!s.revoked_at && <ActionForm action={`/shares/${s.id}`} method="DELETE" label="Revoke" />}
                                        </div>
                                    ))}
                                </Card>
                            )}
                        </>
                    )}
                    {p.section === 'reminders' && (
                        <>
                            <div className="cv-two-grid">
                                {p.reminders.map((rem) => (
                                    <Card key={rem.id}>
                                        <div className="cv-row">
                                            <Bell size={23} />
                                            <Badge tone={rem.notified_at ? 'green' : 'amber'}>
                                                {rem.notified_at ? 'Notification sent' : 'Scheduled'}
                                            </Badge>
                                        </div>
                                        <h2>{rem.title}</h2>
                                        <p>
                                            {rem.vehicle.make} {rem.vehicle.model}
                                        </p>
                                        <h3>
                                            {rem.due_date ? date(rem.due_date) : ''}
                                            {rem.due_date && rem.due_mileage ? ' or ' : ''}
                                            {rem.due_mileage ? `${rem.due_mileage.toLocaleString()} km` : ''}
                                        </h3>
                                        <small>Source: {rem.source}</small>
                                        <p>
                                            Latest recorded mileage: {rem.vehicle.mileage.toLocaleString()} km on {date(rem.vehicle.mileage_date)}.
                                        </p>
                                        {new Date(rem.vehicle.mileage_date) < new Date(Date.now() - 30 * 86400000) && (
                                            <p className="cv-warning">
                                                Mileage is over 30 days old. Update it in My garage to check mileage-based reminders.
                                            </p>
                                        )}
                                    </Card>
                                ))}
                            </div>
                            <Card title="Plan maintenance">
                                <p>Set a date, a mileage threshold, or both. These are your recorded intervals, not live telemetry or a diagnosis.</p>
                                <ActionForm action="/reminders" label="Add reminder" wide>
                                    <Field name="vehicle_id" label="Vehicle" options={vehicleOptions} value={active?.id} />
                                    <Field name="title" label="Maintenance item (oil, brakes, fluids, or your own)" />
                                    <Field
                                        name="record_id"
                                        label="Calculate from a service record (optional)"
                                        required={false}
                                        options={p.records.map((rec) => ({
                                            value: rec.id,
                                            label: `#${rec.id} · ${date(rec.service_date)} · ${rec.work}`,
                                        }))}
                                    />
                                    <Field
                                        name="interval_months"
                                        label="Months after selected service (optional)"
                                        type="number"
                                        required={false}
                                        min={1}
                                        max={120}
                                    />
                                    <Field
                                        name="interval_km"
                                        label="Kilometres after selected service (optional)"
                                        type="number"
                                        required={false}
                                        min={100}
                                    />
                                    <Field name="due_date" label="Due date (optional if mileage set)" type="date" required={false} />
                                    <Field name="due_mileage" label="Due mileage (optional if date set)" type="number" min={0} required={false} />
                                </ActionForm>
                            </Card>
                            {p.notifications.length > 0 && (
                                <Card title="Your notifications">
                                    {p.notifications.map((n) => (
                                        <div className="cv-note" key={n.id}>
                                            <strong>
                                                {n.data.title} · {n.data.vehicle}
                                            </strong>
                                            <p>
                                                {n.data.source}
                                                {n.data.mileage_stale ? ' Update your odometer; your recorded mileage is stale.' : ''}
                                            </p>
                                        </div>
                                    ))}
                                </Card>
                            )}
                        </>
                    )}
                    {p.section === 'community' && (
                        <>
                            <div className="cv-two-grid">
                                {p.articles.map((a) => (
                                    <Card key={a.id}>
                                        <span className="cv-eyebrow">MAINTENANCE JOURNAL</span>
                                        <h2>{a.title}</h2>
                                        <p className="cv-article">{a.body}</p>
                                        {a.video_url && (
                                            <a href={a.video_url} target="_blank" rel="noopener noreferrer" className="cv-link">
                                                Watch video <ExternalLink size={15} />
                                            </a>
                                        )}
                                        <small>
                                            By {a.author} · Published {date(a.published_at)} · Last reviewed {date(a.reviewed_at)}
                                        </small>
                                        <p>Sources: {a.sources}</p>
                                    </Card>
                                ))}
                            </div>
                            <Card title="Find your community">
                                <p>Explore independently managed WhatsApp groups. You choose whether to join; Carverse does not enroll you.</p>
                                {p.communities.map((c) => (
                                    <a className="cv-request-row" key={c.id} href={c.url} target="_blank" rel="noopener noreferrer">
                                        <Users size={22} />
                                        <div>
                                            <strong>{c.name}</strong>
                                            <small>{c.model}</small>
                                        </div>
                                        <span className="cv-link">
                                            Open WhatsApp <ExternalLink size={15} />
                                        </span>
                                    </a>
                                ))}
                                {!p.communities.length && <Empty>Community links will appear after review by the pilot team.</Empty>}
                            </Card>
                        </>
                    )}
                    {p.section === 'provider' && (
                        <>
                            <Card title="Your organizations">
                                {p.organizations.map((o) => (
                                    <div className="cv-note" key={o.id}>
                                        <strong>{o.name}</strong> <Badge>{o.status}</Badge>
                                        <p>{o.verification_scope ?? 'Application awaiting review.'}</p>
                                    </div>
                                ))}
                            </Card>
                            {isProvider && (
                                <>
                                    <Card title="Add a supplier part">
                                        <ActionForm action="/parts" label="Publish part" wide>
                                            <Field
                                                name="organization_id"
                                                label="Supplier organization"
                                                options={p.organizations
                                                    .filter((o) => o.status === 'approved' && o.kind === 'supplier')
                                                    .map((o) => ({ value: o.id, label: `${o.name} (${o.kind})` }))}
                                            />
                                            <Field name="photo" label="Part photo (optional, JPEG / PNG, up to 5 MB)" type="file" required={false} />
                                            <Field name="title" label="Part title" />
                                            <Field name="brand" label="Brand" />
                                            <Field name="category" label="Category" />
                                            <Field name="description" label="Description" type="textarea" />
                                            <Field name="oem_reference" label="OEM reference (optional)" required={false} />
                                            <Field name="condition" label="Condition" options={options(['New', 'Used', 'Reconditioned'])} />
                                            <Field
                                                name="price_kes"
                                                label="Price (KES), leave blank for quote-only"
                                                type="number"
                                                required={false}
                                                min={1}
                                            />
                                            <Field name="stock" label="Availability" options={options(['In stock', 'On request', 'Out of stock'])} />
                                        </ActionForm>
                                    </Card>
                                    <Card title="Manage your parts">
                                        {p.providerParts.data.map((part) => (
                                            <Details key={part.id} title={part.title}>
                                                <ActionForm action={`/parts/${part.id}`} method="PUT" label="Save part changes" wide>
                                                    <Field
                                                        name="organization_id"
                                                        value={part.organization.id}
                                                        label="Supplier organization"
                                                        options={p.organizations
                                                            .filter((o) => o.status === 'approved' && o.kind === 'supplier')
                                                            .map((o) => ({ value: o.id, label: `${o.name} (${o.kind})` }))}
                                                    />
                                                    <Field
                                                        name="photo"
                                                        label="Part photo (optional, JPEG / PNG, up to 5 MB)"
                                                        type="file"
                                                        required={false}
                                                    />
                                                    <Field name="title" value={part.title} label="Part title" />
                                                    <Field name="brand" value={part.brand} label="Brand" />
                                                    <Field name="category" value={part.category} label="Category" />
                                                    <Field name="description" value={part.description} label="Description" type="textarea" />
                                                    <Field
                                                        name="oem_reference"
                                                        value={part.oem_reference}
                                                        label="OEM reference (optional)"
                                                        required={false}
                                                    />
                                                    <Field
                                                        name="condition"
                                                        value={part.condition}
                                                        label="Condition"
                                                        options={options(['New', 'Used', 'Reconditioned'])}
                                                    />
                                                    <Field
                                                        name="price_kes"
                                                        value={part.price_minor === null ? '' : part.price_minor / 100}
                                                        label="Price (KES), leave blank for quote-only"
                                                        type="number"
                                                        required={false}
                                                        min={1}
                                                    />
                                                    <Field
                                                        name="stock"
                                                        value={part.stock}
                                                        label="Availability"
                                                        options={options(['In stock', 'On request', 'Out of stock'])}
                                                    />
                                                </ActionForm>
                                            </Details>
                                        ))}
                                    </Card>
                                    <Pager links={p.providerParts.links} />
                                    <Card title="Record a fitment assessment">
                                        <p>
                                            Use the vehicle from a request assigned to your organization. Record the reference and the relevant
                                            vehicle details checked.
                                        </p>
                                        {p.providerParts.data.map((part) => (
                                            <Details key={part.id} title={part.title}>
                                                <ActionForm action={`/parts/${part.id}/fitment`} label="Save assessment">
                                                    <Field
                                                        name="vehicle_id"
                                                        label="Vehicle from assigned request"
                                                        options={p.requests.data
                                                            .filter((r) => r.organization_id === part.organization.id)
                                                            .map((r) => ({
                                                                value: r.vehicle.id,
                                                                label: `${r.vehicle.make} ${r.vehicle.model} (${r.vehicle.year}) · Request #${r.id}`,
                                                            }))}
                                                    />
                                                    <Field
                                                        name="status"
                                                        label="Assessment result"
                                                        options={options(['confirmed', 'potential', 'unknown', 'incompatible'])}
                                                    />
                                                    <Field name="evidence" label="Evidence/reference and vehicle details checked" type="textarea" />
                                                </ActionForm>
                                            </Details>
                                        ))}
                                    </Card>
                                </>
                            )}
                            <Card title="Apply as a garage or supplier">
                                <ActionForm action="/providers/apply" label="Submit application" wide>
                                    <Field name="photo" label="Provider photo (optional, JPEG / PNG, up to 5 MB)" type="file" required={false} />
                                    <Field name="name" label="Organization name" />
                                    <Field
                                        name="kind"
                                        label="Provider type"
                                        options={[
                                            { value: 'garage', label: 'Garage' },
                                            { value: 'supplier', label: 'Parts supplier' },
                                        ]}
                                    />
                                    <Field name="location" label="Location" />
                                    <Field name="phone" label="Kenyan phone number" type="tel" />
                                    <Field name="description" label="About your organization" type="textarea" />
                                    <Field name="specialties[]" label="Supported make (optional)" required={false} />
                                    <Field name="specialties[]" label="Specialist service (optional)" required={false} />
                                    <Field name="opening_hours" label="Opening hours" />
                                    <Field name="evidence" label="Identity/business evidence, private (up to 5 MB)" type="file" />
                                </ActionForm>
                            </Card>
                        </>
                    )}
                    {p.section === 'admin' && (
                        <>
                            {!p.admin && !p.moderation && <Empty>Your account does not have access to pilot operations.</Empty>}
                            {p.admin && (
                                <>
                                    <Card title="Pilot metrics">
                                        <div className="cv-metrics">
                                            {Object.entries(p.admin.metrics).map(([k, v]) => (
                                                <div key={k}>
                                                    <small>{k.replaceAll('_', ' ')}</small>
                                                    <strong>
                                                        {v === null
                                                            ? 'Not enough data'
                                                            : typeof v === 'boolean'
                                                              ? v
                                                                  ? 'Incomplete'
                                                                  : 'Complete'
                                                              : k.endsWith('_minor')
                                                                ? money(v)
                                                                : v}
                                                    </strong>
                                                </div>
                                            ))}
                                        </div>
                                        <p>
                                            Counts cover all recorded pilot data. Completed means owner-confirmed. Fulfilment hours run from quote
                                            acceptance to completion. Repeat customers have 2+ completed transactions. Revenue is recorded commission
                                            on completed transactions. Contribution is commission minus recorded variable costs; it is withheld when
                                            cost data is incomplete.
                                        </p>
                                    </Card>
                                    <Card title="Provider applications & verification">
                                        {p.admin.organizations.map((o) => (
                                            <Details key={o.id} title={`${o.name} · ${o.status}`}>
                                                <p>
                                                    {o.description} · {o.location} · {o.phone}
                                                </p>
                                                <div className="cv-tags">
                                                    {p.admin?.attachments
                                                        .filter((a) => a.organization_id === o.id)
                                                        .map((a) => (
                                                            <a key={a.id} className="cv-link" href={`/attachments/${a.id}`}>
                                                                {a.name}
                                                            </a>
                                                        ))}
                                                </div>
                                                <ActionForm action={`/providers/${o.id}/members`} label="Add organization staff">
                                                    <Field name="email" label="Existing user email" type="email" />
                                                </ActionForm>
                                                <ActionForm action={`/providers/${o.id}/review`} label="Save verification decision">
                                                    <Field
                                                        name="status"
                                                        label="Decision"
                                                        value={o.status}
                                                        options={options(['approved', 'pending', 'rejected'])}
                                                    />
                                                    <Field
                                                        name="verification_scope"
                                                        label="Exactly what was checked and limitations"
                                                        type="textarea"
                                                        value={o.verification_scope ?? ''}
                                                    />
                                                </ActionForm>
                                            </Details>
                                        ))}
                                    </Card>
                                    <Card title="Users">
                                        <div className="cv-table-scroll">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {p.admin.users.data.map((u) => (
                                                        <tr key={u.id}>
                                                            <td>{u.name}</td>
                                                            <td>{u.email}</td>
                                                            <td>
                                                                <ActionForm action={`/users/${u.id}/role`} label="Update role">
                                                                    <Field
                                                                        name="role"
                                                                        label="Access role"
                                                                        value={u.role}
                                                                        options={options(['owner', 'moderator', 'admin'])}
                                                                    />
                                                                </ActionForm>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </Card>
                                    <Card title="Publish a guide">
                                        <p>Content is rendered as plain text. HTML, scripts, and embeds are not executed.</p>
                                        <ActionForm action="/articles" label="Save guide" wide>
                                            <Field name="title" label="Title" />
                                            <Field name="author" label="Author" />
                                            <Field name="body" label="Article body" type="textarea" />
                                            <Field name="sources" label="Sources and references" type="textarea" />
                                            <Field name="video_url" label="Optional YouTube / Vimeo video link" type="url" required={false} />
                                            <Field name="status" label="Publication status" options={options(['draft', 'published'])} />
                                            <Field name="reviewed_at" label="Last review date" type="date" />
                                        </ActionForm>
                                        {p.admin.articles.map((a) => (
                                            <Details title={`${a.title} · ${a.status}`} key={a.id}>
                                                <ActionForm action={`/articles/${a.id}`} method="PUT" label="Save guide changes" wide>
                                                    <Field name="title" label="Title" value={a.title} />
                                                    <Field name="author" label="Author" value={a.author} />
                                                    <Field name="body" label="Body" type="textarea" value={a.body} />
                                                    <Field name="sources" label="Sources" type="textarea" value={a.sources} />
                                                    <Field
                                                        name="video_url"
                                                        label="Video URL (optional)"
                                                        type="url"
                                                        required={false}
                                                        value={a.video_url ?? ''}
                                                    />
                                                    <Field
                                                        name="status"
                                                        label="Publication status"
                                                        options={options(['draft', 'published'])}
                                                        value={a.status}
                                                    />
                                                    <Field name="reviewed_at" label="Last reviewed" type="date" value={a.reviewed_at} />
                                                </ActionForm>
                                                <ActionForm action={`/articles/${a.id}/publish`} label="Update publication">
                                                    <Field name="status" label="Status" options={options(['draft', 'published'])} value={a.status} />
                                                </ActionForm>
                                            </Details>
                                        ))}
                                    </Card>
                                    <Card title="Community links">
                                        {p.communities.map((c) => (
                                            <Details key={c.id} title={c.name}>
                                                <p>
                                                    {c.model} · {c.url}
                                                </p>
                                                <ActionForm action={`/communities/${c.id}`} method="DELETE" label="Remove invitation link" />
                                            </Details>
                                        ))}
                                        <ActionForm action="/communities" label="Add approved WhatsApp group" wide>
                                            <Field name="name" label="Community name" />
                                            <Field name="model" label="Vehicle model / community" />
                                            <Field name="url" label="WhatsApp invitation URL" type="url" />
                                        </ActionForm>
                                    </Card>
                                    <Card title="Parts & compatibility assessments">
                                        {p.admin.parts.map((part) => (
                                            <Details key={part.id} title={`${part.title} · ${part.organization.name}`}>
                                                <p>
                                                    {part.oem_reference} · {part.stock}
                                                </p>
                                                {part.fitments?.map((f) => (
                                                    <div className="cv-note" key={f.id}>
                                                        <strong>
                                                            Vehicle #{f.vehicle_id} · {f.status}
                                                        </strong>
                                                        <p>{f.evidence}</p>
                                                        <small>
                                                            {f.assessor.name} · {date(f.assessed_at)}
                                                        </small>
                                                    </div>
                                                ))}
                                            </Details>
                                        ))}
                                    </Card>
                                    <Card title="Pilot requests & quotes">
                                        {p.admin.pilot_requests.data.map((r) => (
                                            <Details key={r.id} title={`#${r.id} · ${r.title} · ${r.status}`}>
                                                <p>
                                                    {r.vehicle.make} {r.vehicle.model}
                                                </p>
                                                {r.quotes.map((q) => (
                                                    <p key={q.id}>
                                                        {q.organization.name} · {money(q.total_minor)} · Valid {date(q.valid_until)}
                                                    </p>
                                                ))}
                                            </Details>
                                        ))}
                                    </Card>
                                    <Card title="Service evidence review">
                                        {p.admin.records.map((rec) => (
                                            <Details key={rec.id} title={`#${rec.id} · ${rec.work} · ${rec.provenance}`}>
                                                {rec.attachments.map((a) => (
                                                    <a key={a.id} className="cv-link" href={`/attachments/${a.id}`}>
                                                        {a.name}
                                                    </a>
                                                ))}
                                                <ActionForm action={`/records/${rec.id}/evidence-review`} label="Record evidence review">
                                                    <Field name="reason" label="What evidence was reviewed and limitations" type="textarea" />
                                                </ActionForm>
                                            </Details>
                                        ))}
                                    </Card>
                                    <Card title="Recorded transaction costs">
                                        {p.admin.transactions.data.map((t) => (
                                            <Details key={t.id} title={`#${t.id} · ${t.organization.name} · ${t.status}`}>
                                                {t.status === 'disputed' && (
                                                    <ActionForm action={`/transactions/${t.id}/status`} label="Resolve fulfilment dispute">
                                                        <Field name="status" label="Resolution" options={options(['cancelled', 'in_progress'])} />
                                                    </ActionForm>
                                                )}
                                                <ActionForm action={`/transactions/${t.id}/finance`} label="Save recorded financials" wide>
                                                    <Field
                                                        name="commission_kes"
                                                        label="Platform commission (KES)"
                                                        type="number"
                                                        min={0}
                                                        value={(t.commission_minor ?? 0) / 100}
                                                    />
                                                    <Field
                                                        name="variable_cost_kes"
                                                        label="Variable cost (KES, blank if incomplete)"
                                                        type="number"
                                                        min={0}
                                                        required={false}
                                                        value={t.variable_cost_minor === null ? '' : (t.variable_cost_minor ?? 0) / 100}
                                                    />
                                                    <Field
                                                        name="fitment_return"
                                                        label="Fitment-related return"
                                                        value={t.fitment_return ? '1' : '0'}
                                                        options={[
                                                            { value: '0', label: 'No' },
                                                            { value: '1', label: 'Yes' },
                                                        ]}
                                                    />
                                                </ActionForm>
                                            </Details>
                                        ))}
                                    </Card>
                                    <Card title="Deletion & retention reviews">
                                        {p.admin.deletions.map((d) => (
                                            <Details key={d.id} title={`${d.user.name} · ${d.status}`}>
                                                <p>{d.user.email}</p>
                                                <ActionForm action={`/deletions/${d.id}`} label="Record retention review">
                                                    <Field
                                                        name="status"
                                                        label="Review status"
                                                        value={d.status}
                                                        options={options(['requested', 'retention_review', 'closed'])}
                                                    />
                                                    <Field
                                                        name="retention_note"
                                                        label="Redaction actions / retained records and reason / follow-up"
                                                        type="textarea"
                                                        value={d.retention_note ?? ''}
                                                    />
                                                </ActionForm>
                                            </Details>
                                        ))}
                                    </Card>
                                    <Card title="Audit history">
                                        {p.admin.audits.map((a) => (
                                            <div className="cv-audit-row" key={a.id}>
                                                <Activity size={15} />
                                                <strong>{a.action}</strong>
                                                <span>
                                                    {a.subject_type} #{a.subject_id}
                                                </span>
                                                <small>{date(a.created_at)}</small>
                                            </div>
                                        ))}
                                    </Card>
                                </>
                            )}
                            {p.disputes.length > 0 && (
                                <Card title="Fulfilment disputes">
                                    {p.disputes.map((t) => (
                                        <Details key={t.id} title={`Transaction #${t.id} · ${t.organization.name}`}>
                                            <ActionForm action={`/transactions/${t.id}/status`} label="Record permitted resolution">
                                                <Field name="status" label="Resolution" options={options(['in_progress', 'cancelled'])} />
                                            </ActionForm>
                                        </Details>
                                    ))}
                                </Card>
                            )}
                            {p.moderation && (
                                <Card title="Private complaints & appeals">
                                    {p.moderation.data.map((c) => (
                                        <Details key={c.id} title={`Complaint #${c.id} · ${c.status}`}>
                                            <p>{c.body}</p>
                                            <p>Provider: {c.provider_response ?? 'Awaiting response'}</p>
                                            <p>Appeal: {c.appeal ?? 'None'}</p>
                                            {c.attachments.map((a) => (
                                                <a className="cv-link" key={a.id} href={`/attachments/${a.id}`}>
                                                    {a.name}
                                                </a>
                                            ))}
                                            <ActionForm action={`/complaints/${c.id}`} label="Record decision">
                                                <input type="hidden" name="action" value="decide" />
                                                <Field name="status" label="Resolution" options={options(['resolved', 'dismissed'])} />
                                                <Field name="decision" label="Decision and supporting evidence considered" type="textarea" />
                                            </ActionForm>
                                            {['resolved', 'dismissed'].includes(c.status) && (
                                                <ActionForm action={`/complaints/${c.id}`} label="Intentionally publish redacted summary">
                                                    <input type="hidden" name="action" value="publish" />
                                                    <Field name="public_summary" label="Reviewed, redacted summary (public)" type="textarea" />
                                                    <p>Exclude names, vehicle identifiers, contacts, and unsubstantiated allegations.</p>
                                                </ActionForm>
                                            )}
                                        </Details>
                                    ))}
                                </Card>
                            )}
                        </>
                    )}
                    {p.section === 'preferences' && (
                        <>
                            <Card title="Profile & notifications">
                                <ActionForm action="/preferences" label="Save preferences" wide>
                                    <Field
                                        name="phone"
                                        label="Kenyan phone (optional)"
                                        type="tel"
                                        required={false}
                                        value={String(user.phone ?? '')}
                                    />
                                    <Field name="location" label="Location" value={String(user.location ?? 'Nairobi')} />
                                    <Field
                                        name="reminder_notifications"
                                        label="Maintenance notifications"
                                        options={[
                                            { value: '1', label: 'Enabled' },
                                            { value: '0', label: 'Disabled' },
                                        ]}
                                        value={user.reminder_notifications ? '1' : '0'}
                                    />
                                </ActionForm>
                                <Link className="cv-link" href="/settings/profile">
                                    Manage name, email, and password <ArrowRight size={15} />
                                </Link>
                            </Card>
                            <Card title="Your data">
                                <a href="/account/export" className="cv-button">
                                    <ArrowDownToLine size={17} />
                                    Download account data
                                </a>
                                <p>
                                    Exports include your profile, vehicles, requests, records, reviews, and complaints. Private evidence files remain
                                    available through their authorized download links.
                                </p>
                                <p>
                                    Request a documented deletion review below. Transaction and complaint evidence may require retention while a
                                    dispute is unresolved. The administrator must review, redact personal details, and document retained records and
                                    the reason before closing the account.
                                </p>
                                <ActionForm action="/account/deletion" label="Request deletion review">
                                    <Field name="password" label="Confirm your password" type="password" />
                                </ActionForm>
                            </Card>
                        </>
                    )}
                    <footer className="cv-footer">
                        <span>
                            CARVERSE KE <i /> Built around your car.
                        </span>
                        <span>Nairobi pilot · {p.paymentMode === 'demo' ? 'Demo payments' : 'Sandbox payments'}</span>
                    </footer>
                </main>
            </div>
        </div>
    );
}
