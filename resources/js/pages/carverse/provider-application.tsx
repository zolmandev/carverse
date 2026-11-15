import { Head, Link } from '@inertiajs/react';
import './culture.css';
import './landing-glass.css';
import PublicHeader from './public-header';
import { ActionForm, Card, Field } from './ui';
import './workspace.css';

const labels = { garage: 'List your Garage', mechanic: 'List as specialist', supplier: 'Become a Parts Supplier' };

export default function ProviderApplication({ kind }: { kind: keyof typeof labels }) {
    return (
        <div className="cv-public cv-culture cv-landing-glass cv-application-page">
            <Head title={labels[kind]} />
            <PublicHeader />
            <main className="cv-application-shell">
                <Link href="/directory" className="cv-link">
                    ← Back to specialists
                </Link>
                <div className="cv-application-heading">
                    <p className="cv-culture-kicker">JOIN THE DIRECTORY</p>
                    <h1>{labels[kind]}</h1>
                    <p>Tell us about your work. Your application and supporting evidence will be reviewed before a public listing appears.</p>
                </div>
                <Card className="cv-application-card">
                    <ActionForm action="/providers/apply" label="Submit application" wide>
                        <Field
                            name="kind"
                            label="Provider type"
                            value={kind}
                            options={[
                                { value: 'garage', label: 'Garage' },
                                { value: 'mechanic', label: 'Skilled mechanic' },
                                { value: 'supplier', label: 'Parts supplier' },
                            ]}
                        />
                        <Field name="name" label="Garage, business or professional name" />
                        <Field name="logo" label="Business logo (optional, PNG, JPEG or WebP, up to 2 MB)" type="file" required={false} />
                        <Field name="photo" label="Provider photo (optional, JPEG / PNG, up to 5 MB)" type="file" required={false} />
                        <Field name="location" label="Location" />
                        <Field name="phone" label="Kenyan phone number" type="tel" />
                        <Field name="description" label="About your services" type="textarea" />
                        <Field
                            name="years_experience"
                            label="Years of experience (self-reported, optional)"
                            type="number"
                            min={0}
                            max={80}
                            required={false}
                        />
                        <Field name="qualifications" label="Qualifications / training (private, optional)" type="textarea" required={false} />
                        <Field name="specialties[]" label="Supported make (optional)" required={false} />
                        <Field name="specialties[]" label="Specialist service (optional)" required={false} />
                        <Field name="opening_hours" label="Opening hours" />
                        <Field name="evidence" label="Identity, business or trade qualification evidence (private, up to 5 MB)" type="file" />
                    </ActionForm>
                </Card>
            </main>
        </div>
    );
}
