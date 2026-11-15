import PublicHeader from '@/pages/carverse/public-header';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, CircleCheck, Users, Wrench } from 'lucide-react';
import './carverse-auth.css';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="cv-auth">
            <PublicHeader />
            <main className="cv-auth-grid">
                <aside className="cv-auth-story" aria-label="About Carverse KE">
                    <div className="cv-auth-story-content">
                        <span className="cv-auth-eyebrow">BUILT FOR THE NAIROBI DRIVER</span>
                        <h2>
                            Your car.
                            <br />A little more
                            <br />
                            <span>confidence.</span>
                        </h2>
                        <p>Find the right parts, connect with specialists, and meet people who share your drive.</p>
                        <div className="cv-auth-highlights">
                            <div>
                                <Wrench size={19} />
                                <span>Discover garages & skilled mechanics</span>
                            </div>
                            <div>
                                <CircleCheck size={19} />
                                <span>See what was checked for compatibility</span>
                            </div>
                            <div>
                                <Users size={19} />
                                <span>Find your car community</span>
                            </div>
                        </div>
                        <Link href="/directory" className="cv-auth-explore">
                            Explore specialists <ArrowUpRight size={17} />
                        </Link>
                    </div>
                    <div className="cv-auth-road" aria-hidden="true">
                        <span />
                        <span />
                        <span />
                    </div>
                    <span className="cv-auth-story-footer">
                        CARVERSE KE <span>YOUR NEXT CHAPTER STARTS HERE</span>
                    </span>
                </aside>
                <section className="cv-auth-form-panel" aria-labelledby="auth-title">
                    <div className="cv-auth-form-inner">
                        <div className="cv-auth-form-heading">
                            <h1 id="auth-title">{title}</h1>
                            <p>{description}</p>
                        </div>
                        {children}
                    </div>
                    <p className="cv-auth-help">Your car. Your community. Carverse KE.</p>
                </section>
            </main>
        </div>
    );
}
