import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, Car } from 'lucide-react';
import type { BlogPost } from './blog';
import './culture.css';
import PublicHeader from './public-header';
import type { Paginated } from './types';
import { Badge, Empty, Pager } from './ui';
import './workspace.css';
export function FeatureCards({ features }: { features: BlogPost[] }) {
    return features.length ? (
        <div className="cv-feature-grid">
            {features.map((car, index) => (
                <Link className="cv-feature-card" href={`/blog/${car.slug}`} key={car.id}>
                    <div className="cv-feature-visual">
                        {car.featured_image_url ? (
                            <img src={car.featured_image_url} alt={car.featured_image_alt || ''} loading="lazy" />
                        ) : (
                            <Car size={60} />
                        )}
                        <span className="cv-feature-number">{String(index + 1).padStart(2, '0')}</span>
                        <span className="cv-feature-arrow">
                            <ArrowUpRight size={20} />
                        </span>
                    </div>
                    <div className="cv-feature-copy">
                        <p>
                            {car.feature_location || 'CARVERSE FEATURES'} {car.feature_year && ` / ${car.feature_year}`}
                        </p>
                        <h3>{car.title}</h3>
                        <span>{car.feature_vehicle}</span>
                        <p className="cv-feature-excerpt">{car.excerpt}</p>
                        <div className="cv-feature-spec-line">
                            {car.feature_engine && <Badge>{car.feature_engine}</Badge>}
                            {car.feature_transmission && <Badge>{car.feature_transmission}</Badge>}
                        </div>
                    </div>
                </Link>
            ))}
        </div>
    ) : (
        <Empty>Every car has a story. Featured builds will appear here when published.</Empty>
    );
}
export default function CarFeatures({ features, search }: { features: Paginated<BlogPost>; search: string }) {
    return (
        <div className="cv-public cv-culture">
            <Head title="Car features — builds with a story" />
            <PublicHeader />
            <main className="cv-features-main">
                <p className="cv-culture-kicker">THE MACHINES. THE PEOPLE. THE DETAILS.</p>
                <h1 className="cv-culture-page-title">
                    Built with
                    <br />
                    <em>personality.</em>
                </h1>
                <p className="cv-culture-intro">A closer look at the cars, ideas and individual touches behind the scene.</p>
                <form className="cv-search" action="/features">
                    <input aria-label="Search car features" name="q" defaultValue={search} placeholder="Search a car or build story" />
                    <button className="cv-button">Find a build</button>
                </form>
                <FeatureCards features={features.data} />
                <Pager links={features.links} />
            </main>
        </div>
    );
}
