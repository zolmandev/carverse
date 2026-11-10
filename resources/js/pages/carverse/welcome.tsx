import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Car, ShieldCheck, ShoppingBag, Wrench } from 'lucide-react';
import { Badge, Card } from './ui';
import './workspace.css';
export default function Welcome() {
    return (
        <div className="cv-public">
            <Head title="Your car. In good hands." />
            <header className="cv-public-header">
                <Link href="/" className="cv-logo">
                    <span className="cv-logo-mark">
                        <Car size={24} />
                    </span>
                    carverse<span className="cv-ke">KE</span>
                </Link>
                <div>
                    <Link href="/login" className="cv-link">
                        Sign in
                    </Link>{' '}
                    <Link href="/register" className="cv-button">
                        Create account <ArrowRight size={16} />
                    </Link>
                </div>
            </header>
            <section className="cv-public-hero">
                <Badge tone="hero">BUILT FOR THE NAIROBI DRIVER</Badge>
                <h1>
                    Your car.
                    <br />A little more confidence.
                </h1>
                <p>Find the right parts and trusted specialists for your car.</p>
                <Link href="/register" className="cv-button">
                    Start with your vehicle <ArrowRight size={16} />
                </Link>{' '}
                <Link href="/directory" className="cv-button light">
                    Explore specialists
                </Link>
            </section>
            <section className="cv-two-grid">
                {[
                    [ShoppingBag, 'Parts that fit your car', 'Browse parts and see exactly what was checked for compatibility.'],
                    [Wrench, 'Specialists with context', 'Explore garages, their specialisms, locations, and contact details.'],
                ].map(([Icon, t, d]) => {
                    const I = Icon as typeof Car;
                    return (
                        <Card key={String(t)}>
                            <I size={27} color="#d32f2f" />
                            <h3>{String(t)}</h3>
                            <p>{String(d)}</p>
                        </Card>
                    );
                })}
            </section>
            <section className="cv-trust-note">
                <ShieldCheck />
                <p>Carverse KE is a focused Nairobi pilot. Provider identity checks and compatibility assessments are separate.</p>
            </section>
        </div>
    );
}
