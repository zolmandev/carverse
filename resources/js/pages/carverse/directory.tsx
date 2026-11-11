import { Head, Link } from '@inertiajs/react';
import { Car, MapPin } from 'lucide-react';
import { Organization } from './types';
import { Badge, Card, date } from './ui';
import './workspace.css';
export default function Directory({
    providers,
    summaries,
}: {
    providers: Organization[];
    summaries: { public_summary: string; updated_at: string }[];
}) {
    return (
        <div className="cv-public">
            <Head title="Nairobi specialists" />
            <header className="cv-public-header">
                <Link href="/" className="cv-logo">
                    <span className="cv-logo-mark">
                        <Car />
                    </span>
                    carverse<span className="cv-ke">KE</span>
                </Link>
                <Link href="/register" className="cv-button">
                    Add your car
                </Link>
            </header>
            <section>
                <h1 className="cv-public-heading">Find your Nairobi specialist.</h1>
                <p>Provider checks cover the stated scope. They do not certify part authenticity or guarantee workmanship.</p>
            </section>
            <div className="cv-two-grid">
                {providers.map((p) => (
                    <Card key={p.id}>
                        <Badge tone="green">Application approved · {p.kind}</Badge>
                        <div className="cv-provider-heading">
                            {p.logo_url && <img className="cv-provider-logo" src={p.logo_url} alt={`${p.name} logo`} />}
                            <h2>{p.name}</h2>
                        </div>
                        {p.photo_url && <img className="cv-uploaded-photo" src={p.photo_url} alt={p.name} />}
                        <p>
                            <MapPin className="cv-inline" size={15} /> {p.location}
                        </p>
                        <p>{p.description}</p>
                        <div className="cv-tags">
                            {Object.entries(p.ratings ?? {}).map(([k, v]) => (
                                <Badge key={k}>
                                    {k}: {v === null ? '—' : Number(v).toFixed(1)} / 5
                                </Badge>
                            ))}
                        </div>
                        <small>{p.review_count ?? 0} completed-transaction reviews</small>
                        <p>
                            {p.phone} · {p.opening_hours}
                        </p>
                        <small>
                            Reviewed {date(p.verified_at)}: {p.verification_scope}
                        </small>
                        <p>
                            <Link className="cv-link" href="/register">
                                Explore parts and specialists →
                            </Link>
                        </p>
                    </Card>
                ))}
            </div>
            {summaries.length > 0 && (
                <section>
                    <h2 className="cv-public-heading">Reviewed complaint summaries</h2>
                    {summaries.map((s, i) => (
                        <Card key={i}>
                            <p>{s.public_summary}</p>
                            <small>Last updated {date(s.updated_at)}</small>
                        </Card>
                    ))}
                </section>
            )}
        </div>
    );
}
