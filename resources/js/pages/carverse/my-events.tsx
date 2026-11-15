import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, CalendarDays, MapPin, Plus, ShieldCheck } from 'lucide-react';
import type { Paginated } from './types';
import { eventCategories, type MotoringEvent } from './event-types';
import './culture.css';
import './landing-glass.css';
import './my-events.css';
import PublicHeader from './public-header';
import { dateTime, Pager } from './ui';
import './workspace.css';

type Status = 'all' | MotoringEvent['status'];
const filters: { value: Status; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'In review' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Needs changes' },
    { value: 'withdrawn', label: 'Withdrawn' },
];
const statusLabels: Record<MotoringEvent['status'], string> = {
    pending: 'In review',
    approved: 'Approved',
    rejected: 'Needs changes',
    withdrawn: 'Withdrawn',
};

export default function MyEvents({
    events,
    counts,
    status,
    flash,
}: {
    events: Paginated<MotoringEvent>;
    counts: Partial<Record<MotoringEvent['status'], number>>;
    status: Status;
    flash: { message?: string };
}) {
    const total = Object.values(counts).reduce((sum, count) => sum + Number(count ?? 0), 0);

    function withdraw(event: MotoringEvent) {
        if (window.confirm(`Withdraw “${event.title}” from the event listings?`)) {
            router.post(`/events/${event.id}/withdraw`, {}, { preserveScroll: true });
        }
    }

    return (
        <div className="cv-public cv-culture cv-landing-glass cv-my-events-page">
            <Head title="Manage my events" />
            <PublicHeader />
            <main className="cv-my-events-shell">
                <Link href="/events" className="cv-link">← Explore events</Link>
                <div className="cv-my-events-heading">
                    <div>
                        <p className="cv-culture-kicker">YOUR EVENTS</p>
                        <h1>Manage my events</h1>
                        <p>Track reviews, update details and manage what appears in the event listings.</p>
                    </div>
                    <Link href="/events/submit" className="cv-button"><Plus size={17} /> Submit an event</Link>
                </div>
                {flash.message && <div className="cv-success" role="status">{flash.message}</div>}
                <nav className="cv-my-events-filters" aria-label="Filter your events">
                    {filters.map((filter) => (
                        <Link
                            key={filter.value}
                            href={filter.value === 'all' ? '/events/mine' : `/events/mine?status=${filter.value}`}
                            className={status === filter.value ? 'active' : ''}
                            aria-current={status === filter.value ? 'page' : undefined}
                        >
                            {filter.label}<span>{filter.value === 'all' ? total : Number(counts[filter.value] ?? 0)}</span>
                        </Link>
                    ))}
                </nav>
                {events.data.length ? (
                    <div className="cv-my-events-grid">
                        {events.data.map((event) => (
                            <article className="cv-my-event-card" key={event.id}>
                                <div className="cv-my-event-poster">
                                    {event.poster_url ? <img src={event.poster_url} alt={`${event.title} poster`} /> : <CalendarDays size={42} aria-hidden="true" />}
                                    <span className={`cv-my-event-status ${event.status}`}>{statusLabels[event.status]}</span>
                                </div>
                                <div className="cv-my-event-content">
                                    <p className="cv-culture-kicker">{eventCategories.find((category) => category.value === event.category)?.label ?? event.category}</p>
                                    <h2>{event.title}</h2>
                                    <p className="cv-my-event-meta"><CalendarDays size={15} /> {dateTime(event.starts_at)} · Nairobi time</p>
                                    <p className="cv-my-event-meta"><MapPin size={15} /> {event.venue}, {event.location}</p>
                                    {event.review_note && (
                                        <div className="cv-my-event-feedback">
                                            <strong>Review feedback</strong>
                                            <p>{event.review_note}</p>
                                        </div>
                                    )}
                                    {event.status === 'approved' && event.approval_scope && (
                                        <p className="cv-my-event-scope"><ShieldCheck size={15} /> {event.approval_scope}</p>
                                    )}
                                    <div className="cv-my-event-actions">
                                        <Link href={`/events/${event.id}/edit`} className="cv-button">
                                            {event.status === 'rejected' || event.status === 'withdrawn' ? 'Edit & resubmit' : 'Edit event'} <ArrowRight size={15} />
                                        </Link>
                                        {event.evidence_name && <a href={`/events/${event.id}/evidence`} className="cv-link">View evidence</a>}
                                        {event.status !== 'withdrawn' && <button type="button" className="cv-link cv-my-event-withdraw" onClick={() => withdraw(event)}>Withdraw</button>}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="cv-my-events-empty">
                        <CalendarDays size={32} />
                        <h2>{status === 'all' ? 'No events submitted yet' : `No ${statusLabels[status]} events`}</h2>
                        <p>{status === 'all' ? 'Share a meet, show or workshop with the community.' : 'Try another status to find your submission.'}</p>
                        <Link href={status === 'all' ? '/events/submit' : '/events/mine'} className="cv-button">
                            {status === 'all' ? 'Submit an event' : 'View all events'} <ArrowRight size={16} />
                        </Link>
                    </div>
                )}
                <Pager links={events.links} />
            </main>
        </div>
    );
}
