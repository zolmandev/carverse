import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, ChevronLeft, ChevronRight, MapPin, Pause, Play } from 'lucide-react';
import { useEffect, useState } from 'react';
import { MotoringEvent } from './event-types';
import { Badge, dateTime } from './ui';
export type BannerEvent = Pick<
    MotoringEvent,
    'id' | 'title' | 'category' | 'venue' | 'location' | 'starts_at' | 'ends_at' | 'description' | 'poster_url'
>;
export default function EventBanner({ events }: { events: BannerEvent[] }) {
    const [index, setIndex] = useState(0);
    const [playing, setPlaying] = useState(false);
    const [hovered, setHovered] = useState(false);
    useEffect(() => {
        setPlaying(!window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }, []);
    useEffect(() => {
        if (!playing || hovered || events.length < 2) return;
        const timer = window.setInterval(() => setIndex((i) => (i + 1) % events.length), 7000);
        return () => window.clearInterval(timer);
    }, [playing, hovered, events.length]);
    const active = index % events.length;
    const event = events[active];
    const eventDate = Object.fromEntries(
        new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Africa/Nairobi',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        }).formatToParts(new Date(event.starts_at)).map((part) => [part.type, part.value]),
    );
    function move(step: number) {
        setIndex((active + step + events.length) % events.length);
        setPlaying(false);
    }
    return (
        <section
            className="cv-public-hero cv-event-banner"
            aria-roledescription="carousel"
            aria-label="Upcoming car events"
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            onFocusCapture={() => setPlaying(false)}
        >
            {event.poster_url && <img className="cv-event-banner-poster" src={event.poster_url} alt="" />}
            <div className="cv-event-banner-shade" />
            <div className="cv-event-banner-meta">
                <div className="cv-event-banner-location">
                    <MapPin size={16} aria-hidden="true" />
                    <span><strong>{event.venue}</strong><em>{event.location}</em></span>
                </div>
                <time className="cv-event-banner-date" dateTime={event.starts_at} aria-label={`Event date and time: ${dateTime(event.starts_at)}, Nairobi time`}>
                    <span className="cv-event-banner-date-day"><CalendarDays size={14} aria-hidden="true" /><strong>{eventDate.day}</strong></span>
                    <span className="cv-event-banner-date-copy"><strong>{eventDate.month} {eventDate.year}</strong><small>{eventDate.hour}:{eventDate.minute} <span>· EAT</span></small></span>
                </time>
            </div>
            <div className="cv-event-banner-content" aria-live={playing ? 'off' : 'polite'} aria-atomic="true">
                <Badge tone="hero">UPCOMING CAR EVENTS · {event.category.replaceAll('_', ' ').toUpperCase()}</Badge>
                <h2>{event.title}</h2>
                <p className="cv-event-banner-description">
                    {event.description.slice(0, 180)}
                    {event.description.length > 180 ? '…' : ''}
                </p>
                <Link className="cv-button" href={`/events?event_search=${encodeURIComponent(event.title)}`}>
                    Explore event <ArrowRight size={16} />
                </Link>
            </div>
            {events.length > 1 && (
                <div className="cv-event-banner-controls">
                    <div>
                        <button onClick={() => move(-1)} aria-label="Previous event">
                            <ChevronLeft size={18} />
                        </button>
                        <span>
                            {active + 1} / {events.length}
                        </span>
                        <button onClick={() => move(1)} aria-label="Next event">
                            <ChevronRight size={18} />
                        </button>
                    </div>
                    <button onClick={() => setPlaying((p) => !p)} aria-label={playing ? 'Pause slideshow' : 'Play slideshow'}>
                        {playing ? <Pause size={16} /> : <Play size={16} />}
                    </button>
                </div>
            )}
        </section>
    );
}
