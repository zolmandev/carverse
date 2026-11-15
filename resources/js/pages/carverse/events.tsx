import { Head } from '@inertiajs/react';
import './culture.css';
import { EventsData } from './event-types';
import EventsWorkspace from './events-workspace';
import PublicHeader from './public-header';
import './workspace.css';
export default function Events({ data, flash }: { data: EventsData; flash: { message?: string } }) {
    return (
        <div className="cv-public cv-culture">
            <Head title="Motoring events" />
            <PublicHeader />
            <section>
                <h1 className="cv-public-heading">Motoring events</h1>
                <p>Explore approved events and submit yours for review.</p>
                {flash.message && (
                    <div className="cv-success" role="status">
                        {flash.message}
                    </div>
                )}
                <EventsWorkspace data={data} searchAction="/events" />
            </section>
        </div>
    );
}
