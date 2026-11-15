import { Head, Link } from '@inertiajs/react';
import './culture.css';
import type { MotoringEvent } from './event-types';
import { EventFields } from './events-workspace';
import './landing-glass.css';
import PublicHeader from './public-header';
import { ActionForm, Card } from './ui';
import './workspace.css';

export default function EventSubmission({ email, event }: { email: string; event: MotoringEvent | null }) {
    return (
        <div className="cv-public cv-culture cv-landing-glass cv-application-page">
            <Head title={event ? 'Edit event' : 'Submit an event'} />
            <PublicHeader />
            <main className="cv-application-shell">
                <Link href="/events/mine" className="cv-link">
                    ← Manage my events
                </Link>
                <div className="cv-application-heading">
                    <p className="cv-culture-kicker">CARVERSE EVENTS</p>
                    <h1>{event ? 'Edit and resubmit your event' : 'Submit your event'}</h1>
                    <p>Share the details with the community. Submissions and changes are reviewed before they appear publicly.</p>
                </div>
                <Card className="cv-application-card">
                    <ActionForm
                        action={event ? `/events/${event.id}` : '/events'}
                        method={event ? 'PUT' : undefined}
                        label={event ? 'Resubmit for approval' : 'Submit for approval'}
                        wide
                    >
                        <EventFields event={event ?? undefined} email={email} />
                    </ActionForm>
                    <p className="cv-application-note">Organizer contact details and supporting evidence stay private.</p>
                </Card>
            </main>
        </div>
    );
}
