import { Head } from '@inertiajs/react';
import { ServiceRecord, Vehicle } from './types';
import { Badge, Card, date } from './ui';
import './workspace.css';
export default function Shared({
    vehicle,
    records,
    expires,
}: {
    vehicle: Pick<Vehicle, 'make' | 'model' | 'year'>;
    records: ServiceRecord[];
    expires: string;
}) {
    return (
        <div className="cv-public">
            <Head title="Shared vehicle history" />
            <section>
                <Badge>READ-ONLY VEHICLE HISTORY</Badge>
                <h1 className="cv-public-heading">
                    {vehicle.year} {vehicle.make} {vehicle.model}
                </h1>
                <p>Selected records shared by the owner. This is not a guarantee of a complete or accurate history. Link expires {date(expires)}.</p>
                {records.map((r, i) => (
                    <Card key={i}>
                        <Badge>{r.provenance}</Badge>
                        <h2>{r.work}</h2>
                        <p>
                            {date(r.service_date)} · {r.mileage.toLocaleString()} km
                        </p>
                        <p>{r.parts_used}</p>
                        <small>Version {r.version}</small>
                    </Card>
                ))}
            </section>
        </div>
    );
}
