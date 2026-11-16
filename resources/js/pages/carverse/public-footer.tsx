import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';
import './public-footer.css';

export default function PublicFooter() {
    const partners = [
        { name: 'Hashback', url: 'https://www.hashback.co.ke', logo: 'https://www.hashback.co.ke/assets/images/logo-on-dark.png' },
    ];

    return (
        <footer className="cv-global-footer">
            <div className="cv-global-footer-inner">
                <div className="cv-global-footer-brand">
                    <Link href="/" className="cv-global-footer-logo">
                        <AppLogoIcon aria-hidden="true" />
                        carverse <span>KE</span>
                    </Link>
                    <p>Driven by the culture. Built for Kenya.</p>
                </div>
                <nav aria-label="Footer">
                    <Link href="/features">Car features</Link>
                    <Link href="/events">Events</Link>
                    <Link href="/blog">Journal</Link>
                    <Link href="/directory">Specialists</Link>
                    <Link href="/communities">Communities</Link>
                    <Link href="/terms">Terms</Link>
                    <Link href="/privacy">Privacy</Link>
                    <Link href="/community-guidelines">Guidelines</Link>
                </nav>
            </div>
            <div className="cv-global-footer-partners">
                <div className="cv-global-footer-partners-inner">
                    <div className="cv-global-footer-partners-heading">
                        <span>IN GOOD COMPANY</span>
                        <small>Partners who keep us moving</small>
                    </div>
                    <div className="cv-global-footer-partner-list">
                        {partners.map((partner) => (
                            <a className="cv-global-footer-partner" href={partner.url} target="_blank" rel="noopener noreferrer" aria-label={partner.name} key={partner.name}>
                                <img src={partner.logo} alt={partner.name} loading="lazy" />
                            </a>
                        ))}
                    </div>
                </div>
            </div>
            <div className="cv-global-footer-note">Nairobi pilot.</div>
        </footer>
    );
}
