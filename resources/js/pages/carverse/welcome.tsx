import { Head, Link } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, BookOpen, CalendarDays, MapPin } from 'lucide-react';
import type { BlogPost } from './blog';
import { FeatureCards } from './car-features';
import './culture.css';
import EventBanner, { BannerEvent } from './event-banner';
import './landing-glass.css';
import LandingLeaderboard, { LandingRacing } from './landing-leaderboard';
import PublicHeader from './public-header';
import { Badge, date, dateTime } from './ui';
import './workspace.css';
export default function Welcome({
    posts = [],
    upcomingEvents = [],
    racing,
    featuredCars = [],
}: {
    posts?: BlogPost[];
    upcomingEvents?: BannerEvent[];
    racing: LandingRacing;
    featuredCars?: BlogPost[];
}) {
    return (
        <div className="cv-public cv-landing-glass cv-culture">
            <Head title="Kenyan car culture. Built together.">
                <meta name="description" content="Car features, community meets, racing leaderboards and stories from Kenya’s automotive scene." />
            </Head>
            <PublicHeader />
            <nav className="cv-culture-topics" aria-label="Explore the scene">
                <Link href="/features">01 / Car features</Link>
                <Link href="/events">02 / Meets & events</Link>
                <a href="#landing-leaderboard-title">03 / On the clock</a>
                <Link href="/blog">04 / The journal</Link>
            </nav>
            <div className="cv-landing-with-sidebar">
                <LandingLeaderboard racing={racing} />
                <div className="cv-landing-primary">
                    {upcomingEvents.length ? (
                        <EventBanner events={upcomingEvents} />
                    ) : (
                        <section className="cv-public-hero cv-culture-cover">
                            <img
                                src="/images/culture/crimson-coupe-concept.png"
                                alt="AI-generated crimson sports coupe parked outside an industrial workshop at dusk"
                                fetchPriority="high"
                            />
                            <div className="cv-culture-cover-shade" />
                            <div className="cv-culture-cover-copy">
                                <Badge tone="hero">THE CARVERSE EDIT</Badge>
                                <h2>
                                    Every build.
                                    <br />A different story.
                                </h2>
                                <p>Meet the machines and the people shaping the scene.</p>
                                <Link href="/features" className="cv-button">
                                    Explore car features <ArrowUpRight size={17} />
                                </Link>
                            </div>
                            <span className="cv-culture-image-credit">AI-GENERATED EDITORIAL IMAGE</span>
                        </section>
                    )}
                    <section className="cv-culture-builds" aria-labelledby="home-builds-title">
                        <div className="cv-culture-section-heading">
                            <div>
                                <p className="cv-culture-kicker">IN THE SPOTLIGHT</p>
                                <h2 id="home-builds-title">
                                    Car features<span>.</span>
                                </h2>
                            </div>
                            <Link href="/features" className="cv-link">
                                All builds <ArrowUpRight size={16} />
                            </Link>
                        </div>
                        <FeatureCards features={featuredCars} />
                    </section>
                </div>
                <aside className="cv-landing-events" aria-labelledby="landing-events-title">
                    <div className="cv-events-sidebar-heading">
                        <span className="cv-events-sidebar-icon">
                            <CalendarDays size={21} />
                        </span>
                        <div>
                            <p className="cv-eyebrow">OUT & ABOUT</p>
                            <h2 id="landing-events-title">Upcoming events</h2>
                        </div>
                    </div>
                    <p className="cv-events-sidebar-intro">Meet the community. Find your next drive.</p>
                    {upcomingEvents.length ? (
                        <ul className="cv-event-preview-list">
                            {upcomingEvents.map((event) => (
                                <li key={event.id}>
                                    <time dateTime={event.starts_at} className="cv-event-preview-date">
                                        <strong>
                                            {new Date(event.starts_at).toLocaleDateString('en-KE', {
                                                timeZone: 'Africa/Nairobi',
                                                day: '2-digit',
                                            })}
                                        </strong>
                                        <span>
                                            {new Date(event.starts_at).toLocaleDateString('en-KE', {
                                                timeZone: 'Africa/Nairobi',
                                                month: 'short',
                                            })}
                                        </span>
                                    </time>
                                    <div>
                                        <h3>
                                            <Link href={`/events?event_search=${encodeURIComponent(event.title)}`}>{event.title}</Link>
                                        </h3>
                                        <p>
                                            <MapPin size={13} />
                                            {event.venue}, {event.location}
                                        </p>
                                        <small>{dateTime(event.starts_at)} EAT</small>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="cv-events-sidebar-empty">
                            <CalendarDays size={28} />
                            <h3>No upcoming events yet</h3>
                            <p>Approved community events will appear here. Check back soon or submit your own.</p>
                        </div>
                    )}
                    <Link href="/events" className="cv-button light cv-events-sidebar-link">
                        Explore events <ArrowRight size={16} />
                    </Link>
                    <section className="cv-sidebar-blog" aria-labelledby="sidebar-blog-title">
                        <div className="cv-events-sidebar-heading">
                            <span className="cv-events-sidebar-icon">
                                <BookOpen size={21} />
                            </span>
                            <div>
                                <p className="cv-eyebrow">THE CARVERSE JOURNAL</p>
                                <h2 id="sidebar-blog-title">Latest articles</h2>
                            </div>
                        </div>
                        {posts.length ? (
                            <ul className="cv-sidebar-blog-list">
                                {posts.map((post) => (
                                    <li key={post.id}>
                                        <Link href={`/blog/${post.slug}`} className="cv-sidebar-blog-item">
                                            {post.featured_image_url ? (
                                                <img src={post.featured_image_url} alt="" loading="lazy" />
                                            ) : (
                                                <span className="cv-sidebar-blog-placeholder" aria-hidden="true">
                                                    <BookOpen size={20} />
                                                </span>
                                            )}
                                            <div>
                                                <span className="cv-sidebar-blog-category">{post.category}</span>
                                                <h3>{post.title}</h3>
                                                <small>
                                                    {post.published_at && <time dateTime={post.published_at}>{date(post.published_at)}</time>}
                                                </small>
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="cv-events-sidebar-intro">New articles are on the way. Check back soon.</p>
                        )}
                        <Link href="/blog" className="cv-button light cv-events-sidebar-link">
                            All articles <ArrowRight size={16} />
                        </Link>
                    </section>
                </aside>
            </div>
            <section className="cv-provider-join" aria-labelledby="provider-join-title">
                <div>
                    <p className="cv-culture-kicker">FOR THE PEOPLE BEHIND THE SCENE</p>
                    <h2 id="provider-join-title">Bring your work to Carverse.</h2>
                    <p>Apply to be listed in the specialist directory. Applications are reviewed before they appear publicly.</p>
                </div>
                <div className="cv-provider-join-actions">
                    <Link href="/app/provider/apply/supplier">
                        Become a Parts Supplier <ArrowRight size={16} />
                    </Link>
                    <Link href="/app/provider/apply/garage">
                        List your Garage <ArrowRight size={16} />
                    </Link>
                    <Link href="/app/provider/apply/mechanic">
                        List as specialist <ArrowRight size={16} />
                    </Link>
                </div>
            </section>
        </div>
    );
}
