import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Paginated } from './types';
import { ActionForm, Badge, Card, date, Details, Empty, Field, Pager } from './ui';
type Moderated = {
    id: number;
    moderation_status: 'pending' | 'approved' | 'rejected';
    moderation_note: string | null;
    reviewed_at: string | null;
    reviewer: { name: string } | null;
    author: { name: string } | null;
    created_at: string;
    transaction: { id: number; status: string };
};
type Review = Moderated & {
    body: string;
    quality: number;
    communication: number;
    timeliness: number;
    value: number;
    response: string | null;
    response_status: string;
};
type Case = Moderated & {
    body: string;
    status: string;
    provider_response: string | null;
    appeal: string | null;
    decision: string | null;
    attachments: { id: number; name: string }[];
    public_summary: string | null;
    public_approved_at: string | null;
};
export type Feedback = {
    provider: { id: number; name: string; kind: string; status: string; logo_url: string | null };
    section: 'reviews' | 'cases';
    filter: string;
    reviews: Paginated<Review>;
    cases: Paginated<Case>;
    events: { id: number; actor_id: number | null; action: string; created_at: string }[];
};
const decisions = [
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approve' },
    { value: 'rejected', label: 'Reject' },
];
function ReviewDecision({ providerId, review }: { providerId: number; review: Review }) {
    const [decision, setDecision] = useState(review.moderation_status);
    return (
        <ActionForm action={`/providers/${providerId}/reviews/${review.id}/moderate`} label="Save review decision">
            <label className="cv-field">
                <span>Publication decision</span>
                <select name="moderation_status" value={decision} onChange={(e) => setDecision(e.target.value as Review['moderation_status'])}>
                    <option value="pending">Pending</option>
                    <option value="approved">Approve for publication</option>
                    <option value="rejected">Reject publication</option>
                </select>
            </label>
            <Field name="moderation_note" label="Moderation reason (private)" type="textarea" value={review.moderation_note ?? ''} />
            {decision === 'approved' && (
                <label className="cv-check">
                    <input type="checkbox" name="content_checked" value="1" required />I reviewed the text and provider reply for publication,
                    including personal information and unsupported allegations.
                </label>
            )}
            <p>
                Approval publishes the original ratings. A moderation decision must be based on the content and evidence, not whether the rating is
                positive or negative.
            </p>
        </ActionForm>
    );
}
function CaseModeration({ providerId, item }: { providerId: number; item: Case }) {
    const [decision, setDecision] = useState(item.moderation_status);
    return (
        <ActionForm action={`/providers/${providerId}/cases/${item.id}/moderate`} label="Save case moderation">
            <label className="cv-field">
                <span>Case moderation</span>
                <select name="moderation_status" value={decision} onChange={(e) => setDecision(e.target.value as Case['moderation_status'])}>
                    {decisions.map((d) => (
                        <option key={d.value} value={d.value}>
                            {d.value === 'approved' ? 'Approve case for review' : d.label}
                        </option>
                    ))}
                </select>
            </label>
            <Field
                name="moderation_note"
                label="Evidence considered / moderation reason (private)"
                type="textarea"
                value={item.moderation_note ?? ''}
            />
            {decision === 'approved' && (
                <label className="cv-check">
                    <input type="checkbox" name="evidence_checked" value="1" required />I reviewed the available supporting evidence.
                </label>
            )}
            <p>Approval accepts the case for review. It does not establish the allegation as true or publish its contents.</p>
        </ActionForm>
    );
}
export default function ProviderFeedback({ data: f }: { data: Feedback }) {
    const provider = f.provider;
    const base = `/providers/${provider.id}`;
    const link = (section: string) =>
        '/app/admin?' +
        new URLSearchParams({
            tab: 'approvals',
            provider_status: 'approved',
            provider_id: String(provider.id),
            feedback: section,
            feedback_status: f.filter,
        }).toString();
    return (
        <>
            <Link href="/app/admin?tab=approvals&provider_status=approved" className="cv-link">
                ← Back to approved providers
            </Link>
            <div className="cv-section-heading">
                <div className="cv-provider-heading">
                    {provider.logo_url && <img className="cv-provider-logo" src={provider.logo_url} alt={`${provider.name} logo`} />}
                    <h2>{provider.name}</h2>
                    <Badge tone="green">Approved provider</Badge>
                </div>
            </div>
            <nav className="cv-catalog-tabs" aria-label="Garage feedback">
                <Link href={link('reviews')} className={f.section === 'reviews' ? 'active' : ''}>
                    Reviews
                </Link>
                <Link href={link('cases')} className={f.section === 'cases' ? 'active' : ''}>
                    Cases
                </Link>
            </nav>
            <Card>
                <form action="/app/admin" method="get" className="cv-form wide">
                    <input type="hidden" name="tab" value="approvals" />
                    <input type="hidden" name="provider_status" value="approved" />
                    <input type="hidden" name="provider_id" value={provider.id} />
                    <input type="hidden" name="feedback" value={f.section} />
                    <Field
                        name="feedback_status"
                        label="Moderation status"
                        value={f.filter}
                        options={[{ value: 'all', label: 'All statuses' }, ...decisions]}
                    />
                    <button className="cv-button">Filter</button>
                </form>
            </Card>
            {f.section === 'reviews' ? (
                <>
                    <div className="cv-two-grid">
                        {f.reviews.data.map((review) => (
                            <Card key={review.id}>
                                <div className="cv-row">
                                    <span className="cv-eyebrow">REVIEW #{review.id}</span>
                                    <Badge
                                        tone={
                                            review.moderation_status === 'approved' ? 'green' : review.moderation_status === 'pending' ? 'amber' : ''
                                        }
                                    >
                                        {review.moderation_status}
                                    </Badge>
                                </div>
                                <h3>{review.author?.name ?? 'Customer'}</h3>
                                <small>
                                    {date(review.created_at)} · Transaction #{review.transaction.id} ·{' '}
                                    {review.transaction.status.replaceAll('_', ' ')}
                                </small>
                                <p className="cv-article">{review.body}</p>
                                <div className="cv-tags">
                                    {[
                                        ['Quality', review.quality],
                                        ['Communication', review.communication],
                                        ['Timeliness', review.timeliness],
                                        ['Value', review.value],
                                    ].map(([label, value]) => (
                                        <Badge key={label}>
                                            {label}: {value}/5
                                        </Badge>
                                    ))}
                                </div>
                                {review.response && (
                                    <div className="cv-note">
                                        <strong>Provider reply · {review.response_status}</strong>
                                        <p>{review.response}</p>
                                    </div>
                                )}
                                {review.reviewed_at && (
                                    <small>
                                        Last reviewed {date(review.reviewed_at)} by {review.reviewer?.name ?? 'Administrator'}
                                    </small>
                                )}
                                <Details title="Approve or moderate review">
                                    <ReviewDecision key={`${review.id}-${review.reviewed_at}`} providerId={provider.id} review={review} />
                                </Details>
                            </Card>
                        ))}
                    </div>
                    {!f.reviews.data.length && <Empty>No reviews match this moderation status.</Empty>}
                    <Pager links={f.reviews.links} />
                </>
            ) : (
                <>
                    {f.cases.data.map((item) => (
                        <Card key={item.id}>
                            <div className="cv-row">
                                <span className="cv-eyebrow">
                                    CASE #{item.id} · TRANSACTION #{item.transaction.id}
                                </span>
                                <div className="cv-tags">
                                    <Badge
                                        tone={item.moderation_status === 'approved' ? 'green' : item.moderation_status === 'pending' ? 'amber' : ''}
                                    >
                                        Moderation: {item.moderation_status}
                                    </Badge>
                                    <Badge>Resolution: {item.status}</Badge>
                                </div>
                            </div>
                            <h3>{item.author?.name ?? 'Customer'}</h3>
                            <small>Submitted {date(item.created_at)}</small>
                            <p className="cv-article">{item.body}</p>
                            <div className="cv-tags">
                                {item.attachments.map((a) => (
                                    <a key={a.id} href={`/attachments/${a.id}`} className="cv-link">
                                        Download private evidence: {a.name}
                                    </a>
                                ))}
                            </div>
                            {!item.attachments.length && <p>No supporting evidence is attached.</p>}
                            {item.provider_response && (
                                <div className="cv-note">
                                    <strong>Provider response</strong>
                                    <p>{item.provider_response}</p>
                                </div>
                            )}
                            {item.appeal && (
                                <div className="cv-note">
                                    <strong>Appeal</strong>
                                    <p>{item.appeal}</p>
                                </div>
                            )}
                            {item.decision && (
                                <div className="cv-note">
                                    <strong>Recorded decision</strong>
                                    <p>{item.decision}</p>
                                </div>
                            )}
                            {item.reviewed_at && (
                                <small>
                                    Last moderated {date(item.reviewed_at)} by {item.reviewer?.name ?? 'Administrator'}
                                </small>
                            )}
                            <Details title="Approve or moderate case">
                                <CaseModeration key={`${item.id}-${item.reviewed_at}`} providerId={provider.id} item={item} />
                            </Details>
                            {item.moderation_status === 'approved' && (
                                <Details title="Record case resolution">
                                    <ActionForm action={`${base}/cases/${item.id}/decision`} label="Save case resolution">
                                        <Field
                                            name="status"
                                            label="Resolution"
                                            value={['resolved', 'dismissed'].includes(item.status) ? item.status : 'resolved'}
                                            options={[
                                                { value: 'resolved', label: 'Resolved' },
                                                { value: 'dismissed', label: 'Dismissed' },
                                            ]}
                                        />
                                        <Field name="decision" label="Decision and evidence considered" type="textarea" value={item.decision ?? ''} />
                                    </ActionForm>
                                </Details>
                            )}
                            {item.moderation_status === 'approved' && ['resolved', 'dismissed'].includes(item.status) && (
                                <Details title="Approve a redacted public summary">
                                    <ActionForm action={`${base}/cases/${item.id}/publication`} label="Approve summary for publication">
                                        <input type="hidden" name="action" value="publish" />
                                        <Field
                                            name="public_summary"
                                            label="Reviewed, redacted summary"
                                            type="textarea"
                                            value={item.public_summary ?? ''}
                                        />
                                        <Field name="reason" label="Publication reason (private)" type="textarea" />
                                        <label className="cv-check">
                                            <input type="checkbox" name="redaction_confirmed" value="1" required />I checked this summary and removed
                                            personal contact details, vehicle identifiers, and unsupported allegations.
                                        </label>
                                    </ActionForm>
                                </Details>
                            )}
                            {item.public_summary && item.public_approved_at && (
                                <div className="cv-note">
                                    <strong>Approved public summary · {date(item.public_approved_at)}</strong>
                                    <p>{item.public_summary}</p>
                                    <Details title="Withdraw public summary">
                                        <ActionForm action={`${base}/cases/${item.id}/publication`} label="Withdraw summary">
                                            <input type="hidden" name="action" value="unpublish" />
                                            <Field name="reason" label="Withdrawal reason (private)" type="textarea" />
                                        </ActionForm>
                                    </Details>
                                </div>
                            )}
                        </Card>
                    ))}
                    {!f.cases.data.length && <Empty>No cases match this moderation status.</Empty>}
                    <Pager links={f.cases.links} />
                </>
            )}
            {f.events.length > 0 && (
                <Card title="Recent moderation history">
                    {f.events.map((event) => (
                        <div className="cv-audit-row" key={event.id}>
                            <strong>{event.action}</strong>
                            <span>Actor #{event.actor_id ?? 'system'}</span>
                            <small>{date(event.created_at)}</small>
                        </div>
                    ))}
                </Card>
            )}
        </>
    );
}
