import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { EventApplications, eventCategories, MotoringEvent } from './event-types';
import { EventFields } from './events-workspace';
import { ActionForm, Badge, Card, dateTime, Empty, Field, Pager } from './ui';
function EventReview({ event }: { event: MotoringEvent }) {
    const [decision, setDecision] = useState<MotoringEvent['status']>(event.status === 'pending' ? 'approved' : event.status);
    if (event.status === 'withdrawn')
        return <div className="cv-note">The organizer withdrew this listing. It must be resubmitted before approval.</div>;
    return (
        <ActionForm action={`/events/${event.id}/review`} label="Save event decision">
            <input type="hidden" name="version" value={event.version} />
            <label className="cv-field">
                <span>Decision</span>
                <select name="status" value={decision} onChange={(e) => setDecision(e.target.value as MotoringEvent['status'])}>
                    <option value="approved">Approve for listing</option>
                    <option value="rejected">Reject</option>
                    <option value="pending">Keep pending</option>
                </select>
            </label>
            <Field name="review_note" label="Decision notes / feedback to organizer (private)" type="textarea" value={event.review_note ?? ''} />
            <Field
                name="approval_scope"
                label="Public scope: event details and evidence checked"
                type="textarea"
                required={decision === 'approved'}
                value={event.approval_scope ?? ''}
            />
            {decision === 'approved' && (
                <label className="cv-check">
                    <input type="checkbox" name="evidence_checked" value="1" required />I reviewed the supporting evidence, organizer and event
                    details, including the schedule and venue.
                </label>
            )}
            <p>Approval covers the recorded checks. It does not provide ticketing, admission, refunds, or a guarantee about the event.</p>
        </ActionForm>
    );
}
function EventEdit({ event }: { event: MotoringEvent }) {
    return (
        <details className="cv-admin-event-edit">
            <summary>Edit event and poster</summary>
            <ActionForm action={`/admin/events/${event.id}`} method="put" label="Save event changes">
                <EventFields event={event} email={event.contact_email ?? null} />
                <p className="cv-muted">Admin edits keep the current approval state and create a new event version.</p>
            </ActionForm>
        </details>
    );
}
export default function EventApprovals({ applications: a, search }: { applications: EventApplications; search?: string }) {
    const [selectedId, setSelected] = useState<number>();
    const selected = a.events.data.find((e) => e.id === selectedId);
    const link = (status: string) =>
        '/app/admin?' + new URLSearchParams({ tab: 'events', event_status: status, ...(search ? { q: search } : {}) }).toString();
    return (
        <>
            <nav className="cv-catalog-tabs" aria-label="Event approval status">
                {['pending', 'approved', 'rejected', 'withdrawn'].map((s) => (
                    <Link key={s} href={link(s)} className={a.status === s ? 'active' : ''}>
                        {s} <Badge>{a.counts[s] ?? 0}</Badge>
                    </Link>
                ))}
            </nav>
            <div className="cv-group-layout">
                <section className="cv-group-list">
                    <Card
                        title={`${a.status === 'pending' ? 'Pending' : a.status === 'approved' ? 'Approved' : a.status === 'rejected' ? 'Rejected' : 'Withdrawn'} events`}
                    >
                        <form action="/app/admin" method="get" className="cv-form wide">
                            <input type="hidden" name="tab" value="events" />
                            <input type="hidden" name="event_status" value={a.status} />
                            <Field name="q" label="Search title or location" required={false} value={search ?? ''} />
                            <button className="cv-button">Search</button>
                        </form>
                        {a.events.data.length ? (
                            <div className="cv-table-scroll">
                                <table className="cv-group-table">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Organizer</th>
                                            <th>Start (Nairobi)</th>
                                            <th>Venue</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {a.events.data.map((event) => (
                                            <tr key={event.id} className={selectedId === event.id ? 'cv-group-editing' : ''}>
                                                <td>
                                                    <strong>{event.title}</strong>
                                                </td>
                                                <td>{event.organizer_name}</td>
                                                <td>{dateTime(event.starts_at)}</td>
                                                <td>
                                                    {event.venue}
                                                    <br />
                                                    {event.location}
                                                </td>
                                                <td>
                                                    <button type="button" className="cv-link" onClick={() => setSelected(event.id)}>
                                                        Review
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <Empty>
                                No {a.status} events match your search. Organizers can submit from the{' '}
                                <Link className="cv-link" href="/events">
                                    Events page
                                </Link>
                                .
                            </Empty>
                        )}
                        <Pager links={a.events.links} />
                    </Card>
                </section>
                <aside className="cv-group-form-sidebar cv-provider-review">
                    {selected ? (
                        <Card title={selected.title}>
                            {selected.poster_url && <img className="cv-event-poster" src={selected.poster_url} alt={`${selected.title} poster`} />}
                            <Badge>{eventCategories.find((c) => c.value === selected.category)?.label ?? selected.category}</Badge>
                            <p>
                                <strong>
                                    {dateTime(selected.starts_at)} – {dateTime(selected.ends_at)}
                                </strong>
                                <br />
                                {selected.venue} · {selected.location}
                            </p>
                            <p className="cv-article">{selected.description}</p>
                            <p>
                                Organizer: {selected.organizer_name}
                                <br />
                                {selected.contact_email} · {selected.contact_phone}
                            </p>
                            {selected.author && (
                                <small>
                                    Submitted by {selected.author.name} · {selected.author.email}
                                </small>
                            )}
                            <p>
                                <a className="cv-link" href={`/events/${selected.id}/evidence`}>
                                    Download private evidence: {selected.evidence_name}
                                </a>
                            </p>
                            {selected.website_url && (
                                <p>
                                    <a className="cv-link" href={selected.website_url} target="_blank" rel="noopener noreferrer">
                                        Open event website ↗
                                    </a>
                                </p>
                            )}
                            <EventEdit key={`${selected.id}-${selected.version}`} event={selected} />
                            {selected.reviewed_at && (
                                <small>
                                    Last reviewed {dateTime(selected.reviewed_at)} by {selected.reviewer?.name ?? 'Administrator'} · Version{' '}
                                    {selected.version}
                                </small>
                            )}
                            <EventReview key={`${selected.id}-${selected.version}-${selected.reviewed_at}`} event={selected} />
                        </Card>
                    ) : (
                        <Card title="Review an event">
                            <p>Select an event to inspect its organizer details, schedule, venue and private supporting evidence.</p>
                            <p>Only approved, current submissions are listed. Organizer edits remove the listing and require a fresh review.</p>
                        </Card>
                    )}
                </aside>
            </div>
        </>
    );
}
