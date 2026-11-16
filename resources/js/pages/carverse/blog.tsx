import { Head, Link } from '@inertiajs/react';
import { BookOpen, Share2 } from 'lucide-react';
import './culture.css';
import PublicHeader from './public-header';
import { Paginated } from './types';
import { Badge, Card, date, Empty, Pager } from './ui';
import './workspace.css';
export type BlogPost = {
    featured_image_url: string | null;
    featured_image_alt: string | null;
    is_car_feature: boolean;
    is_demo_feature: boolean;
    feature_vehicle: string | null;
    feature_year: number | null;
    feature_engine: string | null;
    feature_transmission: string | null;
    feature_location: string | null;
    feature_credit: string | null;
    feature_modifications: string | null;
    id: number;
    slug: string;
    title: string;
    author: string;
    body: string;
    excerpt: string | null;
    category: string;
    sources: string;
    video_url: string | null;
    status: string;
    published_at: string | null;
    reviewed_at: string | null;
    views_count: number;
};
export function BlogCards({ posts }: { posts: BlogPost[] }) {
    return posts.length ? (
        <div className="cv-blog-grid">
            {posts.map((post) => (
                <Card key={post.id}>
                    {post.featured_image_url ? (
                        <Link href={`/blog/${post.slug}`}>
                            <img className="cv-blog-cover" src={post.featured_image_url} alt={post.featured_image_alt || ''} loading="lazy" />
                        </Link>
                    ) : (
                        <div className="cv-blog-art">
                            <BookOpen size={32} />
                            <span>{post.category}</span>
                        </div>
                    )}
                    <Badge>{post.category}</Badge>
                    <h3>
                        <Link href={`/blog/${post.slug}`}>{post.title}</Link>
                    </h3>
                    <p>{post.excerpt || post.body.slice(0, 180)}</p>
                    <small>
                        {post.author} · {post.published_at && date(post.published_at)}
                    </small>
                    <p>
                        <Link className="cv-link" href={`/blog/${post.slug}`}>
                            Read article →
                        </Link>
                    </p>
                </Card>
            ))}
        </div>
    ) : (
        <Empty>Articles are on the way. Check back for motoring stories, guides and community news.</Empty>
    );
}
function BlogSidebar({ title, posts }: { title: string; posts?: BlogPost[] }) {
    if (!posts?.length) return null;

    return (
        <section className="cv-blog-sidebar-list">
            <p className="cv-culture-kicker">THE JOURNAL</p>
            <h2>{title}</h2>
            <ul>
                {posts.map((post) => (
                    <li key={post.id}>
                        <Link href={`/blog/${post.slug}`}>
                            <span>
                                {post.featured_image_url ? (
                                    <img src={post.featured_image_url} alt="" loading="lazy" />
                                ) : (
                                    <span className="cv-blog-sidebar-placeholder">
                                        <BookOpen size={17} />
                                    </span>
                                )}
                            </span>
                            <span>
                                <small>{post.category}</small>
                                <strong>{post.title}</strong>
                                {post.published_at && <time dateTime={post.published_at}>{date(post.published_at)}</time>}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}

export default function Blog({
    posts,
    article,
    relatedPosts,
    popularPosts,
    search = '',
}: {
    posts?: Paginated<BlogPost>;
    article?: BlogPost;
    relatedPosts?: BlogPost[];
    popularPosts?: BlogPost[];
    search?: string;
}) {
    return (
        <div className="cv-public cv-culture">
            <Head title={article?.title || 'Carverse blog'}>
                <meta name="description" content={article?.excerpt || 'Motoring stories, guides and community news from Carverse KE.'} />
            </Head>
            <PublicHeader />
            {article ? (
                <div className="cv-blog-layout has-sidebar">
                    <article className="cv-blog-article">
                        <Link href="/blog" className="cv-link">
                            ← All articles
                        </Link>
                        <p>
                            <Badge>{article.category}</Badge>
                        </p>
                        <h1>{article.title}</h1>
                        <p>
                            {article.author} · Published {article.published_at && date(article.published_at)}
                            {article.reviewed_at && ` · Reviewed ${date(article.reviewed_at)}`}
                            {' · '}{Number(article.views_count ?? 0).toLocaleString()} views
                        </p>
                        <div className="cv-blog-share">
                            <span><Share2 size={15} /> Share</span>
                            <a href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`} target="_blank" rel="noopener noreferrer">Facebook</a>
                            <a href={`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`} target="_blank" rel="noopener noreferrer">LinkedIn</a>
                            <a href={`https://wa.me/?text=${encodeURIComponent(`${article.title} ${window.location.href}`)}`} target="_blank" rel="noopener noreferrer">WhatsApp</a>
                        </div>
                        {article.excerpt && <p className="cv-blog-intro">{article.excerpt}</p>}
                        {article.featured_image_url && (
                            <img className="cv-blog-hero-image" src={article.featured_image_url} alt={article.featured_image_alt || ''} />
                        )}
                        <div className="cv-blog-body">{article.body}</div>
                        {article.sources && (
                            <section>
                                <h2>Sources & references</h2>
                                <p className="cv-blog-body">{article.sources}</p>
                            </section>
                        )}
                        {article.video_url && (
                            <a href={article.video_url} target="_blank" rel="noopener noreferrer" className="cv-button light">
                                Watch accompanying video ↗
                            </a>
                        )}
                    </article>
                    {article.is_car_feature && (
                        <aside className="cv-build-details cv-build-sidebar" aria-label="Inside the build">
                            <h2>Inside the build</h2>
                            <dl className="cv-build-specs">
                                {[
                                    ['Vehicle', article.feature_vehicle],
                                    ['Year', article.feature_year],
                                    ['Engine', article.feature_engine],
                                    ['Transmission', article.feature_transmission],
                                    ['Location', article.feature_location],
                                    ['Build credit', article.feature_credit],
                                ]
                                    .filter(([, value]) => value)
                                    .map(([label, value]) => (
                                        <div key={label}>
                                            <dt>{label}</dt>
                                            <dd>{value}</dd>
                                        </div>
                                    ))}
                            </dl>
                            {article.feature_modifications && (
                                <>
                                    <h3>Build notes</h3>
                                    <p className="cv-blog-body">{article.feature_modifications}</p>
                                </>
                            )}
                            <small>Editorial build information. Specifications and modifications are not independently certified.</small>
                            <BlogSidebar title="Related articles" posts={relatedPosts} />
                            <BlogSidebar title="Popular reads" posts={popularPosts} />
                        </aside>
                    )}
                    {!article.is_car_feature && (
                        <aside className="cv-blog-article-side">
                            <BlogSidebar title="Related articles" posts={relatedPosts} />
                            <BlogSidebar title="Popular reads" posts={popularPosts} />
                        </aside>
                    )}
                </div>
            ) : (
                <section>
                    <p className="cv-eyebrow">THE CARVERSE JOURNAL</p>
                    <h1>Stories for the road ahead.</h1>
                    <p>Motoring guides, community news and ideas for your next drive.</p>
                    <form action="/blog" className="cv-search">
                        <input name="q" aria-label="Search blog" placeholder="Search articles or categories" defaultValue={search} />
                        <button className="cv-button">Search</button>
                    </form>
                    {posts && (
                        <>
                            <BlogCards posts={posts.data} />
                            <Pager links={posts.links} />
                        </>
                    )}
                </section>
            )}
        </div>
    );
}
