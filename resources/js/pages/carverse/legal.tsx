import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import './culture.css';
import './landing-glass.css';
import './legal.css';
import PublicHeader from './public-header';

type LegalPage = 'terms' | 'privacy' | 'community-guidelines';

const content: Record<LegalPage, { title: string; eyebrow: string; intro: string }> = {
    terms: {
        title: 'Terms & Conditions',
        eyebrow: 'THE CARVERSE AGREEMENT',
        intro: 'These terms explain how Carverse works, what you can expect from the platform and what we expect from everyone who uses it.',
    },
    privacy: {
        title: 'Privacy Policy',
        eyebrow: 'YOUR DATA, HANDLED WITH CARE',
        intro: 'This policy explains the information Carverse collects, why we use it and the choices available to you.',
    },
    'community-guidelines': {
        title: 'Community Guidelines',
        eyebrow: 'KEEPING THE SCENE WORTH SHOWING UP FOR',
        intro: 'Carverse is for useful knowledge, honest listings and a respectful automotive community. These guidelines keep it that way.',
    },
};

function Terms() {
    return (
        <>
            <h2>1. About Carverse</h2>
            <p>Carverse is a Kenya-focused automotive community and directory. We help people discover car culture, approved events, garages, mechanics, parts suppliers and useful stories.</p>
            <h2>2. Your account</h2>
            <p>You are responsible for the information in your account and for keeping your sign-in details private. Do not impersonate another person, share access, or create an account for someone else without permission.</p>
            <h2>3. Directory listings and events</h2>
            <p>Provider profiles and event listings are reviewed before publication, but a listing is not a guarantee of quality, safety, availability, licensing, workmanship or future performance. Confirm important details directly before visiting, booking or paying anyone.</p>
            <p>Only administrators may create and manage official event listings. Provider applicants must submit accurate information and supporting evidence when requested.</p>
            <h2>4. Your content</h2>
            <p>You keep ownership of content you submit. You give Carverse permission to store, review, display and format it as needed to operate the platform. Do not submit content you do not have the right to use, or anything unlawful, misleading, abusive or unsafe.</p>
            <h2>5. Acceptable use</h2>
            <p>Do not attempt to bypass access controls, upload malware, scrape private information, manipulate reviews, misuse contact details, or use Carverse to facilitate fraud, harassment or unsafe activity.</p>
            <h2>6. Availability and responsibility</h2>
            <p>Carverse is provided as a pilot service and may change or be unavailable from time to time. Carverse does not provide mechanical, legal, financial or safety advice and is not a party to arrangements you make with a provider or event organizer.</p>
            <h2>7. Changes and contact</h2>
            <p>We may update these terms as the service develops. Continued use after an update means you accept the revised terms. If you need help with an account or listing, contact the Carverse team through the details provided in the service.</p>
        </>
    );
}

function Privacy() {
    return (
        <>
            <h2>1. Information we collect</h2>
            <p>Depending on how you use Carverse, we may collect your name, email address, phone number, location, account details, vehicle information, provider application details, uploaded evidence, event information and messages or feedback you submit.</p>
            <h2>2. How we use information</h2>
            <p>We use information to provide accounts and profiles, verify phone numbers, review provider and event submissions, publish approved directory content, protect the platform, respond to support requests and maintain audit records.</p>
            <h2>3. Public and private information</h2>
            <p>Only information intended for a public profile, event or article is published. Contact details, supporting evidence, account identifiers and review notes are kept private and are shown only where needed for an authorized workflow.</p>
            <h2>4. Storage and sharing</h2>
            <p>We limit access to personal information to people and service providers who need it to operate Carverse. We do not sell personal information. We may disclose information when required by law, to protect rights and safety, or as part of a necessary service operation.</p>
            <h2>5. Security and retention</h2>
            <p>We use access controls, private file storage and audit records appropriate to this pilot. No online service is risk-free. We retain information while it is needed for the account, review, safety, legal or operational purpose for which it was collected.</p>
            <h2>6. Your choices</h2>
            <p>You may update profile information, request an account export or request account deletion through the available account tools. Some records may need to be retained for security, fraud prevention, legal or audit reasons.</p>
            <h2>7. Cookies and similar storage</h2>
            <p>Carverse uses essential session and preference storage to keep you signed in, protect forms and remember appearance settings. We do not use these pages to promise that every third-party link or service has the same privacy practices.</p>
        </>
    );
}

function Guidelines() {
    return (
        <>
            <h2>Be honest and useful</h2>
            <p>Share accurate vehicle details, provider information, event details, reviews and photos. Make it clear when something is an opinion, estimate, sponsored placement or personal experience.</p>
            <h2>Respect people</h2>
            <p>No harassment, threats, hate, doxxing, sexual exploitation, discriminatory abuse or targeted personal attacks. Critique work and service fairly without attacking people.</p>
            <h2>Keep listings safe</h2>
            <p>Do not submit fake businesses, misleading qualifications, stolen images, unsafe event instructions, counterfeit parts or claims that imply Carverse guarantees a provider or event.</p>
            <h2>Protect private information</h2>
            <p>Do not post phone numbers, addresses, identity documents, vehicle identifiers or private conversations without permission. Supporting evidence should be uploaded only through the private review flow.</p>
            <h2>Moderation</h2>
            <p>We may remove content, pause a listing, restrict an account or ask for more evidence when content appears misleading, unsafe, abusive or outside the purpose of Carverse. Decisions may be reviewed where the relevant workflow provides an appeal or response path.</p>
            <h2>Report a concern</h2>
            <p>If you see a listing or contribution that appears unsafe, fraudulent, abusive or materially inaccurate, report it to the Carverse team with enough detail for us to investigate.</p>
        </>
    );
}

export default function Legal({ page, updatedAt }: { page: LegalPage; updatedAt: string }) {
    const details = content[page];
    return (
        <div className="cv-public cv-culture cv-landing-glass cv-legal-page">
            <Head title={details.title} />
            <PublicHeader />
            <main className="cv-legal-shell">
                <Link href="/" className="cv-link cv-legal-back"><ArrowLeft size={15} /> Back to Carverse</Link>
                <header className="cv-legal-heading">
                    <p className="cv-culture-kicker">{details.eyebrow}</p>
                    <h1>{details.title}</h1>
                    <p>{details.intro}</p>
                    <small>Last updated {updatedAt}</small>
                </header>
                <article className="cv-legal-card">
                    {page === 'terms' ? <Terms /> : page === 'privacy' ? <Privacy /> : <Guidelines />}
                </article>
            </main>
        </div>
    );
}
