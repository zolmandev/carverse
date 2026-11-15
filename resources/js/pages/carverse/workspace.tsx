import ThemeSwitcher from '@/components/theme-switcher';
import AppLogoIcon from '@/components/app-logo-icon';
import { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowDownToLine,
    ArrowRight,
    CalendarDays,
    Car,
    Check,
    ChevronRight,
    ExternalLink,
    Gauge,
    LayoutDashboard,
    MapPin,
    Search,
    Settings,
    ShieldCheck,
    ShoppingBag,
    Users,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import { CatalogAttribution } from './car-catalog';
import './culture.css';
import EventsWorkspace from './events-workspace';
import './landing-glass.css';
import PublicHeader from './public-header';
import { WorkspaceProps } from './types';
import { ActionForm, Badge, Card, date, Details, Empty, Field, money, Pager } from './ui';
import './workspace.css';
import './overview.css';
const options = (a: string[]) => a.map((x) => ({ value: x, label: x }));
const titles: Record<string, [string, string]> = {
    overview: ['Your car. In good hands.', 'Everything you need to keep your car moving, in one place.'],
    parts: ['The right part starts here.', 'Find parts, check compatibility, and ask the supplier before you buy.'],
    providers: ['Find your specialist', 'Explore approved garages and parts suppliers around Nairobi.'],
    events: ['Motoring events', 'Explore approved events and submit yours for review.'],
    community: ['A little knowledge. A better drive.', 'Practical guides and communities for the road ahead.'],
    provider: ['Provider workspace', 'Manage your organization, parts, and compatibility assessments.'],
    admin: ['Pilot operations', 'Review providers, resolve complaints, and understand the pilot.'],
    preferences: ['Your preferences', 'Manage profile details and your data.'],
};
export default function Workspace(p: WorkspaceProps) {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const [mobile, setMobile] = useState(false);
    const user = auth.user;
    const active = p.activeVehicle;
    const vehicleOptions = p.vehicles.map((v) => ({ value: v.id, label: `${v.make} ${v.model} · ${v.year}` }));
    const orgOptions = p.organizations.filter((o) => o.status === 'approved').map((o) => ({ value: o.id, label: o.name }));
    const isProvider = orgOptions.length > 0;
    const isSupplier = p.organizations.some((o) => o.status === 'approved' && o.kind === 'supplier');
    const enthusiastShell = p.section !== 'admin';
    const title = titles[p.section] ?? titles.overview;
    const nav = [
        ['overview', 'Overview', LayoutDashboard],
        ['parts', 'Find parts', ShoppingBag],
        ['providers', 'Find specialists', Wrench],
        ['community', 'Guides & community', Users],
        ['events', 'Events', CalendarDays],
    ] as const;
    return (
        <div className={enthusiastShell ? 'cv-app cv-public cv-culture cv-enthusiast-landing cv-landing-glass' : 'cv-app'}>
            <Head title={title[0]} />
            {enthusiastShell && <PublicHeader />}
            {!enthusiastShell && (
                <aside className={`cv-sidebar ${mobile ? 'show' : ''}`}>
                    <Link href="/app" className="cv-logo">
                        <AppLogoIcon className="cv-logo-mark" aria-hidden="true" />
                        carverse<span className="cv-ke">KE</span>
                    </Link>
                    <span className="cv-pilot">NAIROBI PILOT</span>
                    <nav>
                        {nav.map(([key, label, Icon]) => (
                            <Link key={key} href={`/app/${key}`} className={p.section === key ? 'selected' : ''} onClick={() => setMobile(false)}>
                                <Icon size={19} />
                                {label}
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
                                <small>{user.role === 'seller' ? 'Seller' : 'Enthusiast'}{isProvider ? ' & provider' : ''}</small>
                            </div>
                            <button onClick={() => router.post('/logout')} aria-label="Sign out">
                                ↗
                            </button>
                        </div>
                    </div>
                </aside>
            )}
            <div className={enthusiastShell ? 'cv-main cv-main-landing' : 'cv-main'}>
                {!enthusiastShell && (
                    <header className="cv-topbar">
                        <button className="cv-mobile-toggle" aria-label="Toggle menu" onClick={() => setMobile(!mobile)}>
                            ☰
                        </button>
                        <div className="cv-breadcrumb">
                            My workspace <ChevronRight size={14} /> <strong>{title[0]}</strong>
                        </div>
                        <ThemeSwitcher />
                        <div className="cv-top-actions">
                            <MapPin size={15} />
                            <span>Nairobi, Kenya</span>
                            <span className="cv-avatar">{user.name.slice(0, 1)}</span>
                        </div>
                    </header>
                )}
                <main className={enthusiastShell ? 'cv-content cv-content-landing' : 'cv-content'}>
                    {p.section !== 'overview' && (
                        <div className="cv-page-heading">
                            <div>
                                <p className="cv-eyebrow">CARVERSE KE / YOUR WORKSPACE</p>
                                <h1>{title[0]}</h1>
                                <p>{title[1]}</p>
                            </div>
                        </div>
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
                    {active && p.section !== 'overview' && (
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
                            <Link href="/profile#garage">
                                Manage <ArrowRight size={14} />
                            </Link>
                        </div>
                    )}
                    {p.section === 'overview' && (
                        <>
                            <div className="cv-overview-intro">
                                <div>
                                    <p className="cv-culture-kicker">YOUR CARVERSE SPACE</p>
                                    <h1>Welcome back, {user.name.split(' ')[0]}.</h1>
                                    <p>Everything for your car, in one place.</p>
                                </div>
                            </div>
                            <div className="cv-overview-feature-grid">
                                <section className="cv-overview-feature">
                                    <span className="cv-overview-feature-label"><span /> MADE FOR THE ROAD AHEAD</span>
                                    <h2>Care for your car.<br /><em>Enjoy the drive.</em></h2>
                                    <p>Find the right parts, connect with specialists and keep your vehicle details close.</p>
                                    <div className="cv-overview-feature-actions">
                                        <Link href="/app/providers" className="cv-button">Find a specialist <ArrowRight size={16} /></Link>
                                        <Link href="/app/parts" className="cv-overview-ghost-link">Explore parts <ArrowRight size={16} /></Link>
                                    </div>
                                </section>
                                <aside className="cv-overview-vehicle" aria-label="Your active vehicle">
                                    <div className="cv-overview-vehicle-top">
                                        <span className="cv-overview-vehicle-icon"><Car size={22} /></span>
                                        <span>YOUR GARAGE</span>
                                    </div>
                                    {active ? (
                                        <>
                                            <h2>{active.make} {active.model}</h2>
                                            <p>{active.year}{active.variant ? ` · ${active.variant}` : ''}</p>
                                            <div className="cv-overview-vehicle-stats">
                                                <span><Gauge size={16} /> {active.mileage.toLocaleString()} km</span>
                                            </div>
                                            {p.vehicles.length > 1 && (
                                                <select aria-label="Active vehicle" value={active.id} onChange={(e) => router.post(`/vehicles/${e.target.value}/active`, {}, { preserveScroll: true })}>
                                                    {vehicleOptions.map((v) => <option key={v.value} value={v.value}>{v.label}</option>)}
                                                </select>
                                            )}
                                        </>
                                    ) : (
                                        <>
                                            <h2>Add your first car</h2>
                                            <p>Save your vehicle details to make browsing more useful.</p>
                                        </>
                                    )}
                                    <Link href="/profile#garage" className="cv-overview-vehicle-link">
                                        {active ? 'Manage my garage' : 'Add a vehicle'} <ArrowRight size={16} />
                                    </Link>
                                </aside>
                            </div>
                            <div className="cv-overview-section-title">
                                <div><p className="cv-culture-kicker">EXPLORE CARVERSE</p><h2>What would you like to do?</h2></div>
                            </div>
                            <div className="cv-overview-destinations">
                                <Link href="/app/parts" className="cv-overview-destination">
                                    <ShoppingBag size={23} /><span>01 / PARTS</span><h3>Find the right part</h3><p>Search available parts and check what fits your car.</p><ArrowRight className="cv-overview-destination-arrow" size={18} />
                                </Link>
                                <Link href="/app/providers" className="cv-overview-destination">
                                    <Wrench size={23} /><span>02 / SPECIALISTS</span><h3>Find a specialist</h3><p>Browse trusted local garages and skilled professionals.</p><ArrowRight className="cv-overview-destination-arrow" size={18} />
                                </Link>
                                <Link href="/events" className="cv-overview-destination">
                                    <CalendarDays size={23} /><span>03 / COMMUNITY</span><h3>Explore events</h3><p>See upcoming meets, shows and workshops near you.</p><ArrowRight className="cv-overview-destination-arrow" size={18} />
                                </Link>
                            </div>
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
                                        <Badge tone="green">Application approved</Badge>
                                    </div>
                                    <span className="cv-eyebrow">
                                        {o.kind === 'garage' ? 'SPECIALIST GARAGE' : o.kind === 'mechanic' ? 'MECHANIC' : 'PARTS SUPPLIER'}
                                    </span>
                                    <div className="cv-provider-heading">
                                        {o.logo_url && <img className="cv-provider-logo" src={o.logo_url} alt={`${o.name} logo`} />}
                                        <h2>{o.name}</h2>
                                    </div>
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
                                </Card>
                            ))}
                        </div>
                    )}
                    {p.section === 'events' && p.events && <EventsWorkspace data={p.events} />}
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
                                        <div className="cv-provider-heading">
                                            {o.logo_url && <img className="cv-provider-logo" src={o.logo_url} alt={`${o.name} logo`} />}
                                            <strong>{o.name}</strong> <Badge>{o.status}</Badge>
                                        </div>
                                        <p>{o.verification_scope ?? 'Application awaiting review.'}</p>
                                        {o.review_note && <p>Review notes: {o.review_note}</p>}
                                        <Details title="Add or update your logo">
                                            <ActionForm action={`/providers/${o.id}/logo`} label="Save logo">
                                                <Field name="logo" label="Logo (PNG, JPEG or WebP, up to 2 MB)" type="file" />
                                            </ActionForm>
                                        </Details>
                                    </div>
                                ))}
                            </Card>
                            {isSupplier && (
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
                                        <p>Select one of your vehicles. Record the reference and the relevant vehicle details checked.</p>
                                        {p.providerParts.data.map((part) => (
                                            <Details key={part.id} title={part.title}>
                                                <ActionForm action={`/parts/${part.id}/fitment`} label="Save assessment">
                                                    <Field name="vehicle_id" label="Your vehicle" options={vehicleOptions} />
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
                            <Card title="Apply as a garage, mechanic or supplier">
                                <p>Choose the right application for your work. Your details are reviewed before any public listing appears.</p>
                                <div className="cv-provider-application-links">
                                    <Link href="/app/provider/apply/supplier" className="cv-button">
                                        Become a Parts Supplier
                                    </Link>
                                    <Link href="/app/provider/apply/garage" className="cv-button">
                                        List your Garage
                                    </Link>
                                    <Link href="/app/provider/apply/mechanic" className="cv-button">
                                        List as specialist
                                    </Link>
                                </div>
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
                                                                        options={options(['enthusiast', 'seller', 'admin'])}
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
                                    <Card title="Recorded transaction costs">
                                        {p.admin.transactions.data.map((t) => (
                                            <Details key={t.id} title={`#${t.id} · ${t.organization.name} · ${t.status}`}>
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
                            <Card title="Profile details">
                                <ActionForm action="/preferences" label="Save preferences" wide>
                                    <Field
                                        name="phone"
                                        label="Kenyan phone (optional)"
                                        type="tel"
                                        required={false}
                                        value={String(user.phone ?? '')}
                                    />
                                    <Field name="location" label="Location" value={String(user.location ?? 'Nairobi')} />
                                    <input type="hidden" name="reminder_notifications" value="0" />
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
                                    Exports include your profile, vehicles, and any retained historical records. Private evidence files remain
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
                    <CatalogAttribution />
                    {!enthusiastShell && (
                        <footer className="cv-footer">
                            <span>
                                CARVERSE KE <i /> Built around your car.
                            </span>
                            <span>Nairobi pilot · Vehicle directory & parts</span>
                        </footer>
                    )}
                </main>
            </div>
        </div>
    );
}
