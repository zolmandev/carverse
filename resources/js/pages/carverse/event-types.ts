import type { Paginated } from './types';
export type MotoringEvent = {
    id: number;
    title: string;
    category: string;
    description: string;
    organizer_name: string;
    venue: string;
    location: string;
    starts_at: string;
    ends_at: string;
    website_url: string | null;
    poster_url: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'withdrawn';
    version: number;
    approved_version?: number | null;
    review_note?: string | null;
    approval_scope: string | null;
    reviewed_at?: string | null;
    contact_email?: string;
    contact_phone?: string;
    evidence_name?: string;
    author?: { name: string; email: string };
    reviewer?: { name: string } | null;
};
export type EventsData = {
    upcoming: Paginated<MotoringEvent>;
    mine: Paginated<MotoringEvent> | null;
    canSubmit: boolean;
    defaultEmail: string | null;
    filters: { event_search?: string; event_category?: string };
};
export type EventApplications = { status: string; events: Paginated<MotoringEvent>; counts: Record<string, number | string> };
export const eventCategories = [
    { value: 'meetup', label: 'Meetup' },
    { value: 'car_show', label: 'Car show' },
    { value: 'track_day', label: 'Track day' },
    { value: 'motorsport', label: 'Motorsport' },
    { value: 'workshop', label: 'Workshop / training' },
    { value: 'other', label: 'Other' },
];
export const localDateTime = (value: string) => {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Africa/Nairobi',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(new Date(value))
            .map((p) => [p.type, p.value]),
    );
    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
};
