import { Link } from '@inertiajs/react';
import './public-footer.css';

export default function PublicFooter() {
    return (
        <footer className="cv-global-footer">
            <div className="cv-global-footer-inner">
                <div className="cv-global-footer-brand">
                    <Link href="/" className="cv-global-footer-logo">
                        carverse <span>KE</span>
                    </Link>
                    <p>Driven by the culture. Built for Kenya.</p>
                </div>
                <nav aria-label="Footer">
                    <Link href="/features">Car features</Link>
                    <Link href="/events">Events</Link>
                    <Link href="/blog">Journal</Link>
                    <Link href="/directory">Specialists</Link>
                </nav>
                <small>Nairobi pilot.</small>
            </div>
        </footer>
    );
}
