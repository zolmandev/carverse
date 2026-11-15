import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, ChevronDown, Clock3, MapPin, Phone, Search, ShieldCheck, SlidersHorizontal, Wrench } from 'lucide-react';
import { useState } from 'react';
import './culture.css';
import './directory.css';
import PublicHeader from './public-header';
import { Organization } from './types';
import { Card, date } from './ui';
import './workspace.css';

function ProviderMark({ provider }: { provider: Organization }) {
    const [failed, setFailed] = useState(false);
    const initials = provider.name
        .replace(/\(Fictional\)/gi, '')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((word) => word[0])
        .join('');
    return (
        <div className={`cv-directory-logo ${provider.logo_url && !failed ? 'has-image' : ''}`} aria-label={`${provider.name} logo`}>
            {provider.logo_url && !failed ? (
                <img src={provider.logo_url} alt={`${provider.name} logo`} loading="lazy" onError={() => setFailed(true)} />
            ) : (
                <span aria-label="Logo not uploaded">{initials}</span>
            )}
        </div>
    );
}
const kinds: Record<string, string> = { garage: 'Garages', mechanic: 'Skilled mechanics', supplier: 'Parts suppliers' };
export default function Directory({
    providers,
    summaries,
}: {
    providers: Organization[];
    summaries: { public_summary: string; updated_at: string }[];
}) {
    const [search, setSearch] = useState('');
    const [kind, setKind] = useState('all');
    const visible = providers.filter(
        (provider) =>
            (kind === 'all' || provider.kind === kind) &&
            [provider.name, provider.location, ...(provider.specialties || [])]
                .join(' ')
                .toLocaleLowerCase()
                .includes(search.trim().toLocaleLowerCase()),
    );
    return (
        <div className="cv-public cv-culture cv-directory">
            <Head title="Find your specialist — Carverse KE">
                <meta
                    name="description"
                    content="Discover Nairobi garages, skilled mechanics and parts suppliers. Explore specialisms, customer reviews and application review details."
                />
            </Head>
            <PublicHeader />
            <main className="cv-directory-main">
                <section className="cv-directory-hero">
                    <div>
                        <p className="cv-culture-kicker">
                            <span /> THE PEOPLE BEHIND YOUR NEXT DRIVE
                        </p>
                        <h1>
                            Good cars deserve
                            <br />
                            <em>good specialists.</em>
                        </h1>
                        <p>Find the right hands for your car. Explore local workshops, independent mechanics and parts suppliers.</p>
                    </div>
                    <div className="cv-directory-hero-aside">
                        <span>
                            <Wrench size={26} />
                        </span>
                        <p>
                            Local knowledge.
                            <br />A little more confidence.
                        </p>
                        <Link href="/app/provider">
                            Run a garage? Get listed <ArrowUpRight size={14} />
                        </Link>
                    </div>
                </section>
                <section className="cv-directory-controls" aria-label="Find a provider">
                    <label className="cv-directory-search">
                        <Search size={18} aria-hidden="true" />
                        <span className="sr-only">Search specialists</span>
                        <input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search a name, location or specialism"
                        />
                    </label>
                    <span className="cv-directory-location">
                        <MapPin size={15} /> Nairobi pilot
                    </span>
                </section>
                <nav className="cv-directory-filters" aria-label="Provider type">
                    {[['all', 'All specialists'], ...Object.entries(kinds)].map(([value, label]) => (
                        <button
                            type="button"
                            key={value}
                            aria-pressed={kind === value}
                            className={kind === value ? 'active' : ''}
                            onClick={() => setKind(value)}
                        >
                            {label}
                        </button>
                    ))}
                </nav>
                <div className="cv-directory-results">
                    <p aria-live="polite">
                        <strong>{visible.length}</strong> {visible.length === 1 ? 'specialist' : 'specialists'}
                        {search.trim() ? ' matching your search' : ''}
                    </p>
                    <span>
                        <SlidersHorizontal size={13} /> Filter by expertise, find your fit
                    </span>
                </div>
                <div className="cv-directory-grid">
                    {visible.map((provider) => (
                        <article className="cv-directory-card" key={provider.id}>
                            {provider.photo_url && (
                                <img className="cv-directory-photo" src={provider.photo_url} alt={`${provider.name} workshop`} loading="lazy" />
                            )}
                            <div className="cv-directory-card-body">
                                <div className="cv-directory-card-head">
                                    <ProviderMark key={`${provider.id}-${provider.logo_url}`} provider={provider} />
                                    <div>
                                        <span className="cv-directory-kind">
                                            {provider.kind === 'mechanic'
                                                ? 'SKILLED MECHANIC'
                                                : provider.kind === 'supplier'
                                                  ? 'PARTS SUPPLIER'
                                                  : 'GARAGE'}
                                        </span>
                                        <h2>
                                            <Link href={`/directory/${provider.id}`}>{provider.name}</Link>
                                        </h2>
                                        <p>
                                            <MapPin size={13} />
                                            {provider.location}
                                        </p>
                                    </div>
                                </div>
                                <p className="cv-directory-description">{provider.description}</p>
                                {!!provider.specialties?.length && (
                                    <div className="cv-directory-specialties">
                                        {provider.specialties.slice(0, 4).map((specialty) => (
                                            <span key={specialty}>{specialty}</span>
                                        ))}
                                        {provider.specialties.length > 4 && <span>+{provider.specialties.length - 4} more</span>}
                                    </div>
                                )}
                                <section className="cv-directory-ratings" aria-label="Transaction-linked ratings">
                                    <div className="cv-directory-review-heading">
                                        <span>Customer experience</span>
                                        <small>
                                            {provider.review_count || 0} {provider.review_count === 1 ? 'review' : 'reviews'}
                                        </small>
                                    </div>
                                    {provider.review_count ? (
                                        <dl>
                                            {[
                                                ['quality', provider.kind === 'supplier' ? 'Product quality' : 'Workmanship'],
                                                ['communication', 'Communication'],
                                                ['timeliness', 'Timeliness'],
                                                ['value', 'Value'],
                                            ].map(([key, label]) => (
                                                <div key={key}>
                                                    <dt>{label}</dt>
                                                    <dd>
                                                        {provider.ratings?.[key] == null ? '—' : Number(provider.ratings[key]).toFixed(1)}
                                                        <span> / 5</span>
                                                    </dd>
                                                </div>
                                            ))}
                                        </dl>
                                    ) : (
                                        <p>No completed-transaction reviews yet.</p>
                                    )}
                                </section>
                                {provider.opening_hours && (
                                    <p className="cv-directory-hours">
                                        <Clock3 size={14} />
                                        {provider.opening_hours}
                                    </p>
                                )}
                                <details className="cv-directory-checks">
                                    <summary>
                                        <ShieldCheck size={14} />
                                        <span>Application approved · view checks</span>
                                        <ChevronDown size={13} />
                                    </summary>
                                    <div>
                                        {provider.verified_at && <p>Reviewed {date(provider.verified_at)}</p>}
                                        <p>{provider.verification_scope || 'Check scope has not been provided.'}</p>
                                        <p>Approval covers the stated checks. It does not certify part authenticity or guarantee workmanship.</p>
                                    </div>
                                </details>
                                <div className="cv-directory-card-actions">
                                    {provider.phone && (
                                        <a className="cv-button" href={`tel:${provider.phone.replace(/[^+\d]/g, '')}`}>
                                            <Phone size={14} />
                                            Contact {provider.kind === 'supplier' ? 'supplier' : provider.kind === 'mechanic' ? 'mechanic' : 'garage'}
                                        </a>
                                    )}
                                    <Link
                                        className="cv-directory-workspace-link"
                                        href={`/directory/${provider.id}`}
                                        aria-label={`View ${provider.name} details and reviews`}
                                    >
                                        <ArrowUpRight size={19} />
                                    </Link>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
                {!visible.length && (
                    <div className="cv-directory-empty">
                        <Search size={30} />
                        <h2>No specialists found</h2>
                        <p>Try a different name, location or service.</p>
                        <button
                            className="cv-button light"
                            onClick={() => {
                                setSearch('');
                                setKind('all');
                            }}
                        >
                            Clear filters
                        </button>
                    </div>
                )}
                <p className="cv-directory-trust">
                    <ShieldCheck size={17} />
                    <span>
                        Know what was checked. Open a provider’s approval details to see the scope and date. Reviews are linked to completed
                        transactions.
                    </span>
                </p>
                {summaries.length > 0 && (
                    <section className="cv-directory-complaints">
                        <h2>Reviewed complaint summaries</h2>
                        <p>Intentionally published summaries, with private details removed.</p>
                        {summaries.map((summary, i) => (
                            <Card key={i}>
                                <p>{summary.public_summary}</p>
                                <small>Last updated {date(summary.updated_at)}</small>
                            </Card>
                        ))}
                    </section>
                )}
                <section className="cv-directory-provider-cta">
                    <div>
                        <p className="cv-culture-kicker">PART OF THE AUTOMOTIVE SCENE?</p>
                        <h2>Your expertise belongs here.</h2>
                        <p>Apply to list your garage, specialist services or parts business.</p>
                    </div>
                    <div className="cv-provider-join-actions">
                        <Link href="/app/provider/apply/supplier" className="cv-button">
                            Become a Parts Supplier <ArrowRight size={16} />
                        </Link>
                        <Link href="/app/provider/apply/garage" className="cv-button">
                            List your Garage <ArrowRight size={16} />
                        </Link>
                        <Link href="/app/provider/apply/mechanic" className="cv-button">
                            List as specialist <ArrowRight size={16} />
                        </Link>
                    </div>
                </section>
            </main>
        </div>
    );
}
