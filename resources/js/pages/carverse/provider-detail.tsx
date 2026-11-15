import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock3, MapPin, Phone, ShieldCheck, Wrench } from 'lucide-react';
import './culture.css';
import './directory.css';
import './provider-detail.css';
import PublicHeader from './public-header';
import type { Organization, Paginated } from './types';
import { Badge, date, Pager } from './ui';
import './workspace.css';

type PublicReview = {
    id: number;
    quality: number;
    communication: number;
    timeliness: number;
    value: number;
    body: string;
    response: string | null;
    created_at: string;
};
type PublishedCase = { id: number; public_summary: string; updated_at: string };

export default function ProviderDetail({
    provider,
    reviews,
    summaries,
}: {
    provider: Organization;
    reviews: Paginated<PublicReview>;
    summaries: Paginated<PublishedCase>;
}) {
    const kind = provider.kind === 'garage' ? 'Garage' : provider.kind === 'mechanic' ? 'Skilled mechanic' : 'Parts supplier';
    return (
        <div className="cv-public cv-culture cv-provider-detail">
            <Head title={`${provider.name} · Carverse KE`} />
            <PublicHeader />
            <main className="cv-provider-detail-shell">
                <Link href="/directory" className="cv-link cv-provider-back">
                    <ArrowLeft size={16} /> All specialists
                </Link>
                <section className="cv-provider-detail-hero">
                    {provider.photo_url && <img className="cv-provider-detail-photo" src={provider.photo_url} alt={`${provider.name} workshop`} />}
                    <div className="cv-provider-detail-heading">
                        <span className="cv-provider-detail-logo">
                            {provider.logo_url ? <img src={provider.logo_url} alt={`${provider.name} logo`} /> : <Wrench size={28} />}
                        </span>
                        <div>
                            <p className="cv-culture-kicker">{kind.toUpperCase()}</p>
                            <h1>{provider.name}</h1>
                            <p>
                                <MapPin size={16} /> {provider.location}
                            </p>
                        </div>
                    </div>
                    <p>{provider.description}</p>
                    {!!provider.specialties?.length && (
                        <div className="cv-tags">
                            {provider.specialties.map((specialty) => (
                                <Badge key={specialty}>{specialty}</Badge>
                            ))}
                        </div>
                    )}
                    <div className="cv-provider-detail-actions">
                        {provider.phone && (
                            <a className="cv-button" href={`tel:${provider.phone.replace(/[^+\d]/g, '')}`}>
                                <Phone size={16} /> Contact {kind.toLowerCase()}
                            </a>
                        )}
                        {provider.opening_hours && (
                            <span>
                                <Clock3 size={16} /> {provider.opening_hours}
                            </span>
                        )}
                    </div>
                </section>
                <section className="cv-provider-detail-section">
                    <h2>
                        Customer reviews <span>{provider.review_count ?? 0}</span>
                    </h2>
                    <p>Reviews linked to completed transactions and approved for publication.</p>
                    {provider.review_count ? (
                        <div className="cv-provider-rating-grid">
                            {(
                                [
                                    ['quality', provider.kind === 'supplier' ? 'Product quality' : 'Workmanship'],
                                    ['communication', 'Communication'],
                                    ['timeliness', 'Timeliness'],
                                    ['value', 'Value'],
                                ] as const
                            ).map(([key, label]) => (
                                <div key={key}>
                                    <small>{label}</small>
                                    <strong>
                                        {provider.ratings?.[key] == null ? '—' : Number(provider.ratings[key]).toFixed(1)} <span>/ 5</span>
                                    </strong>
                                </div>
                            ))}
                        </div>
                    ) : null}
                    <div className="cv-provider-review-list">
                        {reviews.data.map((review) => (
                            <article key={review.id} className="cv-provider-review-card">
                                <div>
                                    <strong>Verified customer review</strong>
                                    <time dateTime={review.created_at}>{date(review.created_at)}</time>
                                </div>
                                <p>{review.body}</p>
                                <small>
                                    Workmanship {review.quality}/5 · Communication {review.communication}/5 · Timeliness {review.timeliness}/5 · Value{' '}
                                    {review.value}/5
                                </small>
                                {review.response && (
                                    <blockquote>
                                        <strong>Provider response</strong>
                                        <p>{review.response}</p>
                                    </blockquote>
                                )}
                            </article>
                        ))}
                    </div>
                    {!reviews.data.length && <p>No published reviews yet.</p>}
                    <Pager links={reviews.links} />
                </section>
                <section className="cv-provider-detail-section">
                    <h2>Published case summaries</h2>
                    <p>
                        These are intentionally redacted summaries of resolved cases. Approval to review a case does not establish the allegation as
                        true.
                    </p>
                    {summaries.data.map((item) => (
                        <article key={item.id} className="cv-provider-review-card">
                            <p>{item.public_summary}</p>
                            <small>Updated {date(item.updated_at)}</small>
                        </article>
                    ))}
                    {!summaries.data.length && <p>No published case summaries.</p>}
                    <Pager links={summaries.links} />
                </section>
                <section className="cv-provider-detail-checks">
                    <ShieldCheck size={19} />
                    <div>
                        <strong>Application approved</strong>
                        <p>
                            {provider.verified_at ? `Reviewed ${date(provider.verified_at)}. ` : ''}
                            {provider.verification_scope || 'Check scope has not been provided.'}
                        </p>
                        <small>Approval covers the stated checks. It does not guarantee workmanship or part authenticity.</small>
                    </div>
                </section>
            </main>
        </div>
    );
}
