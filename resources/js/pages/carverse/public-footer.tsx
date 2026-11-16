import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';
import './public-footer.css';

export default function PublicFooter() {
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
                <div className="cv-global-footer-partner">
                    <small>Payments partner</small>
                    <a href="https://www.hashback.co.ke" target="_blank" rel="noopener noreferrer" aria-label="Hashback">
                        <img src="https://www.hashback.co.ke/assets/images/logo-on-dark.png" alt="Hashback" loading="lazy" />
                    </a>
                    <small>Nairobi pilot.</small>
                </div>
            </div>
        </footer>
    );
}
