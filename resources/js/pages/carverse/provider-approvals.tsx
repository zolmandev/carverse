import { Link } from '@inertiajs/react';
import { useState } from 'react';
import ProviderFeedback, { Feedback } from './provider-feedback';
import { Paginated } from './types';
import { ActionForm, Badge, Card, date, Empty, Field, Pager } from './ui';
type Application = {
    reviews_count: number;
    complaints_count: number;
    pending_reviews_count: number;
    pending_cases_count: number;
    logo_url: string | null;
    id: number;
    name: string;
    kind: 'garage' | 'mechanic' | 'supplier';
    location: string;
    phone: string;
    description: string;
    status: 'pending' | 'approved' | 'rejected';
    opening_hours: string;
    specialties: string[] | null;
    years_experience: number | null;
    qualifications: string | null;
    verification_scope: string | null;
    verified_at: string | null;
    review_note: string | null;
    reviewed_at: string | null;
    created_at: string;
    reviewer: { id: number; name: string } | null;
    members: { id: number; name: string; email: string }[];
    attachments: { id: number; name: string }[];
};
export type Applications = { feedback: Feedback | null; status: string; counts: Record<string, number | string>; providers: Paginated<Application> };
export type ApplicationFilters = { q?: string; provider_kind?: string; provider_status?: string };
const kindLabel = (kind: string) => (kind === 'garage' ? 'Garage' : kind === 'mechanic' ? 'Skilled mechanic' : 'Parts supplier');
function ReviewForm({ provider }: { provider: Application }) {
    const [decision, setDecision] = useState(provider.status === 'pending' && provider.attachments.length ? 'approved' : provider.status);
    return (
        <ActionForm action={`/providers/${provider.id}/review`} label="Save decision">
            <label className="cv-field">
                <span>Decision</span>
                <select name="status" value={decision} onChange={(e) => setDecision(e.target.value as Application['status'])}>
                    <option value="approved" disabled={!provider.attachments.length}>
                        Approve
                    </option>
                    <option value="rejected">Reject</option>
                    <option value="pending">Keep pending</option>
                </select>
            </label>
            <Field
                name="verification_scope"
                label="Public verification scope: exactly what was checked"
                type="textarea"
                required={decision === 'approved'}
                value={provider.verification_scope ?? ''}
            />
            <Field name="review_note" label="Decision notes / feedback to applicant (private)" type="textarea" value={provider.review_note ?? ''} />
            {decision === 'approved' && (
                <label className="cv-check">
                    <input type="checkbox" name="evidence_checked" value="1" required />I reviewed the supporting evidence for the checks described
                    above.
                </label>
            )}
            <p>
                {provider.kind === 'supplier'
                    ? 'Approval publishes the parts supplier and upgrades its applicant accounts to Seller. It covers only the recorded checks and does not guarantee every product or future order.'
                    : 'Approval publishes the profile and enables provider access. It covers only the recorded checks and does not guarantee every skill, qualification, or future job.'}
            </p>
        </ActionForm>
    );
}
export default function ProviderApprovals({ applications: a, filters }: { applications: Applications; filters: ApplicationFilters }) {
    const [selectedId, setSelected] = useState<number>();
    const selected = a.providers.data.find((o) => o.id === selectedId);
    const statusUrl = (status: string) =>
        '/app/admin?' +
        new URLSearchParams({
            tab: 'approvals',
            provider_status: status,
            ...(filters.provider_kind ? { provider_kind: filters.provider_kind } : {}),
            ...(filters.q ? { q: filters.q } : {}),
        }).toString();
    if (a.feedback) return <ProviderFeedback data={a.feedback} />;
    return (
        <>
            <details className="cv-admin-event-edit">
                <summary>Add garage or specialist</summary>
                <ActionForm action="/admin/providers" label="Publish provider listing" wide>
                    <Field
                        name="kind"
                        label="Provider type"
                        value="garage"
                        options={[
                            { value: 'garage', label: 'Garage' },
                            { value: 'mechanic', label: 'Skilled specialist' },
                        ]}
                    />
                    <Field name="name" label="Business or professional name" />
                    <Field name="logo" label="Logo (optional, PNG/JPEG/WebP, up to 2 MB)" type="file" required={false} />
                    <Field name="photo" label="Provider photo (optional, JPEG/PNG, up to 5 MB)" type="file" required={false} />
                    <Field name="location" label="Location" />
                    <Field name="phone" label="Kenyan phone number" type="tel" />
                    <Field name="description" label="About the provider" type="textarea" />
                    <Field name="years_experience" label="Years of experience (optional)" type="number" min={0} max={80} required={false} />
                    <Field name="qualifications" label="Qualifications / training (optional)" type="textarea" required={false} />
                    <Field name="specialties[]" label="Specialty or supported make (optional)" required={false} />
                    <Field name="specialties[]" label="Additional specialty (optional)" required={false} />
                    <Field name="opening_hours" label="Opening hours" />
                    <Field name="evidence" label="Supporting evidence (private, PDF/image, up to 5 MB)" type="file" />
                    <p className="cv-muted">Manual listings are approved and published immediately by the administrator creating them.</p>
                </ActionForm>
            </details>
            <nav className="cv-catalog-tabs" aria-label="Provider application status">
                {['pending', 'approved', 'rejected'].map((s) => (
                    <Link key={s} href={statusUrl(s)} className={a.status === s ? 'active' : ''}>
                        {s === 'pending' ? 'Pending applications' : s} <Badge>{a.counts[s] ?? 0}</Badge>
                    </Link>
                ))}
            </nav>
            <div className="cv-group-layout">
                <section className="cv-group-list">
                    <Card title={`${a.status === 'pending' ? 'Pending' : a.status === 'approved' ? 'Approved' : 'Rejected'} applications`}>
                        <form action="/app/admin" method="get" className="cv-form wide">
                            <input type="hidden" name="tab" value="approvals" />
                            <input type="hidden" name="provider_status" value={a.status} />
                            <Field
                                name="provider_kind"
                                label="Provider type"
                                required={false}
                                value={filters.provider_kind ?? ''}
                                options={[
                                    { value: 'garage', label: 'Garages' },
                                    { value: 'mechanic', label: 'Skilled mechanics' },
                                    { value: 'supplier', label: 'Parts suppliers' },
                                ]}
                            />
                            <Field name="q" label="Search name or location" required={false} value={filters.q ?? ''} />
                            <button className="cv-button">Search</button>
                        </form>
                        {a.providers.data.length ? (
                            <div className="cv-table-scroll">
                                <table className="cv-group-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Submitted</th>
                                            <th>Action</th>
                                            {a.status === 'approved' && <th>Reviews & cases</th>}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {a.providers.data.map((o) => (
                                            <tr key={o.id} className={selectedId === o.id ? 'cv-group-editing' : ''}>
                                                <td>
                                                    <strong>{o.name}</strong>
                                                </td>
                                                <td>{kindLabel(o.kind)}</td>
                                                <td>{o.location}</td>
                                                <td>{date(o.created_at)}</td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        className="cv-link"
                                                        aria-pressed={selectedId === o.id}
                                                        onClick={() => setSelected(o.id)}
                                                    >
                                                        Review
                                                    </button>
                                                </td>
                                                {a.status === 'approved' && (
                                                    <td>
                                                        <div className="cv-group-actions">
                                                            <Link
                                                                className="cv-link"
                                                                href={`/app/admin?tab=approvals&provider_status=approved&provider_id=${o.id}&feedback=reviews`}
                                                            >
                                                                Reviews ({o.reviews_count})
                                                                {o.pending_reviews_count > 0 && (
                                                                    <Badge tone="amber">{o.pending_reviews_count} pending</Badge>
                                                                )}
                                                            </Link>
                                                            <Link
                                                                className="cv-link"
                                                                href={`/app/admin?tab=approvals&provider_status=approved&provider_id=${o.id}&feedback=cases`}
                                                            >
                                                                Cases ({o.complaints_count})
                                                                {o.pending_cases_count > 0 && (
                                                                    <Badge tone="amber">{o.pending_cases_count} pending</Badge>
                                                                )}
                                                            </Link>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <Empty>No {a.status} applications match your filters.</Empty>
                        )}
                        <Pager links={a.providers.links} />
                    </Card>
                </section>
                <aside className="cv-group-form-sidebar cv-provider-review">
                    {selected ? (
                        <Card title={selected.name}>
                            {selected.logo_url && <img className="cv-provider-logo" src={selected.logo_url} alt={`${selected.name} logo`} />}
                            <Badge tone={selected.status === 'approved' ? 'green' : selected.status === 'pending' ? 'amber' : ''}>
                                {selected.status}
                            </Badge>
                            <p>
                                {kindLabel(selected.kind)} · {selected.location}
                                <br />
                                {selected.phone} · {selected.opening_hours}
                            </p>
                            <p>{selected.description}</p>
                            <div className="cv-tags">{selected.specialties?.filter(Boolean).map((s, i) => <Badge key={i}>{s}</Badge>)}</div>
                            {selected.years_experience !== null && <p>Experience claimed: {selected.years_experience} years</p>}
                            {selected.qualifications && (
                                <div className="cv-note">
                                    <strong>Applicant-reported qualifications</strong>
                                    <p className="cv-article">{selected.qualifications}</p>
                                </div>
                            )}
                            <h3>Applicant contacts</h3>
                            {selected.members.map((m) => (
                                <p key={m.id}>
                                    {m.name}
                                    <br />
                                    {m.email}
                                </p>
                            ))}
                            <h3>Supporting evidence</h3>
                            {selected.attachments.length ? (
                                <div className="cv-tags">
                                    {selected.attachments.map((f) => (
                                        <a key={f.id} href={`/attachments/${f.id}`} className="cv-link">
                                            Download {f.name}
                                        </a>
                                    ))}
                                </div>
                            ) : (
                                <div className="cv-note">
                                    No supporting documents are attached. Approval is unavailable until evidence is provided.
                                </div>
                            )}
                            {selected.reviewed_at && (
                                <small>
                                    Last reviewed {date(selected.reviewed_at)} by {selected.reviewer?.name ?? 'Administrator'}
                                </small>
                            )}
                            <ReviewForm key={selected.id} provider={selected} />
                        </Card>
                    ) : (
                        <Card title="Review an application">
                            <p>
                                Select Review beside a garage or mechanic to inspect their application and private evidence, then record your
                                decision.
                            </p>
                            <p>
                                Only administrators can approve or reject providers. Pending and rejected applicants are excluded from the public
                                directory.
                            </p>
                        </Card>
                    )}
                </aside>
            </div>
        </>
    );
}
