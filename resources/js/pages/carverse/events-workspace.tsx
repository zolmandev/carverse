import { Link } from '@inertiajs/react';
import { eventCategories, EventsData, localDateTime, MotoringEvent } from './event-types';
import { Badge, Card, dateTime, Empty, Field, Pager } from './ui';
export function EventFields({ event, email }: { event?: MotoringEvent; email: string | null }) {
    return (
        <>
            <Field name="title" label="Event title" value={event?.title} />
            <Field name="category" label="Event type" value={event?.category ?? 'meetup'} options={eventCategories} />
            <Field name="description" label="Event description (public)" type="textarea" value={event?.description} />
            <Field name="organizer_name" label="Organizer name (public)" value={event?.organizer_name} />
            <Field name="contact_email" label="Organizer email (private)" type="email" value={event?.contact_email ?? email ?? ''} />
            <Field name="contact_phone" label="Organizer phone (private)" type="tel" value={event?.contact_phone} />
            <Field name="venue" label="Venue (public)" value={event?.venue} />
            <Field name="location" label="Location / address (public)" value={event?.location} />
            <Field
                name="starts_at_local"
                label="Start date & time (Nairobi)"
                type="datetime-local"
                value={event ? localDateTime(event.starts_at) : ''}
            />
            <Field name="ends_at_local" label="End date & time (Nairobi)" type="datetime-local" value={event ? localDateTime(event.ends_at) : ''} />
            <Field name="website_url" label="Event website (HTTPS, optional)" type="url" required={false} value={event?.website_url ?? ''} />
            <Field name="poster" label="Poster (optional, PNG/JPEG/WebP, up to 5 MB)" type="file" required={false} />
            <Field
                name="evidence"
                label={
                    event
                        ? 'Replacement supporting evidence (optional, up to 5 MB)'
                        : 'Organizer / venue supporting evidence (private, PDF or image, up to 5 MB)'
                }
                type="file"
                required={!event}
            />
        </>
    );
}
export default function EventsWorkspace({ data: d, searchAction = '/app/events' }: { data: EventsData; searchAction?: string }) {
    return (
        <>
            <div className="cv-note">All event times are shown in Nairobi time (EAT). Only approved submissions are listed.</div>
            <div className="cv-events-listing">
                <div className="cv-events-listing-heading">
                    <h2>Discover upcoming events</h2>
                    <div className="cv-provider-application-links">
                        {d.mine && <Link href="/events/mine" className="cv-link">Manage my events</Link>}
                        <Link href="/events/submit" className="cv-button">Submit your event →</Link>
                    </div>
                </div>
                <section className="cv-group-list">
                    <Card title="Upcoming approved events">
                        <form method="get" action={searchAction} className="cv-form wide">
                            <Field name="event_search" label="Search event or location" required={false} value={d.filters.event_search ?? ''} />
                            <Field
                                name="event_category"
                                label="Event type"
                                required={false}
                                value={d.filters.event_category ?? ''}
                                options={eventCategories}
                            />
                            <button className="cv-button">Search</button>
                        </form>
                    </Card>
                    <div className="cv-event-card-grid">
                        {d.upcoming.data.map((event) => (
                            <Card key={event.id} className="cv-event-list-card">
                                {event.poster_url && <img className="cv-event-poster" src={event.poster_url} alt={`${event.title} poster`} />}
                                <div className="cv-row">
                                    <Badge tone="green">Application approved</Badge>
                                    <Badge>{eventCategories.find((c) => c.value === event.category)?.label ?? event.category}</Badge>
                                </div>
                                <h2>{event.title}</h2>
                                <p className="cv-event-card-date">
                                    <strong>{dateTime(event.starts_at)}</strong> · {event.venue}
                                </p>
                                <p className="cv-event-card-location">{event.location}</p>
                                <p className="cv-article">{event.description}</p>
                                <small>Organized by {event.organizer_name}</small>
                                {event.website_url && (
                                    <p>
                                        <a href={event.website_url} target="_blank" rel="noopener noreferrer" className="cv-button">
                                            Open event website ↗
                                        </a>
                                    </p>
                                )}
                            </Card>
                        ))}
                    </div>
                    {!d.upcoming.data.length && <Empty>No approved upcoming events match your search.</Empty>}
                    <Pager links={d.upcoming.links} />
                </section>
            </div>
        </>
    );
}
