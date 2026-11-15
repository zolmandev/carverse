import { Head } from '@inertiajs/react';
import { ExternalLink, Search, Users } from 'lucide-react';
import { useState } from 'react';
import PublicFooter from './public-footer';
import PublicHeader from './public-header';
import './communities.css';
import './culture.css';
import './workspace.css';

type Community = {
    id: number;
    name: string;
    model: string;
    url: string;
    brand?: { name: string } | null;
    car_model?: { name: string } | null;
    trim?: { name: string } | null;
};

export default function Communities({ communities }: { communities: Community[] }) {
    const [search, setSearch] = useState('');
    const query = search.trim().toLowerCase();
    const visible = communities.filter((community) => `${community.name} ${community.model} ${community.brand?.name ?? ''} ${community.car_model?.name ?? ''}`.toLowerCase().includes(query));

    return (
        <div className="cv-public cv-culture cv-communities">
            <Head title="Car communities — Carverse KE">
                <meta name="description" content="Find car groups and automotive communities on Carverse KE." />
            </Head>
            <PublicHeader />
            <main className="cv-communities-main">
                <section className="cv-communities-hero">
                    <div>
                        <p className="cv-culture-kicker"><span /> FIND YOUR PEOPLE</p>
                        <h1>Every car has a <em>community.</em></h1>
                        <p>Discover vehicle-focused groups, meet fellow enthusiasts and find the conversations that keep your drive moving.</p>
                    </div>
                    <div className="cv-communities-hero-mark"><Users size={32} /></div>
                </section>

                <section className="cv-communities-list" aria-labelledby="communities-title">
                    <div className="cv-communities-list-heading">
                        <div>
                            <p className="cv-culture-kicker">CARVERSE GROUPS</p>
                            <h2 id="communities-title">Find a car group</h2>
                        </div>
                        <label className="cv-communities-search">
                            <Search size={17} aria-hidden="true" />
                            <span className="sr-only">Search communities</span>
                            <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search groups" />
                        </label>
                    </div>
                    <p className="cv-communities-note">These groups are independently managed on WhatsApp. Choose the communities you want to join.</p>
                    {visible.length ? (
                        <div className="cv-communities-grid">
                            {visible.map((community) => (
                                <article className="cv-community-card" key={community.id}>
                                    <div className="cv-community-card-icon"><Users size={21} /></div>
                                    <div className="cv-community-card-copy">
                                        <span className="cv-eyebrow">WHATSAPP GROUP</span>
                                        <h3>{community.name}</h3>
                                        <p>{community.model}</p>
                                    </div>
                                    <a className="cv-button cv-community-join" href={community.url} target="_blank" rel="noopener noreferrer">
                                        Join group <ExternalLink size={15} />
                                    </a>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="cv-communities-empty">
                            <Users size={25} />
                            <h3>No groups found</h3>
                            <p>Try a different search or check back as more communities are added.</p>
                        </div>
                    )}
                </section>
            </main>
            <PublicFooter />
        </div>
    );
}
