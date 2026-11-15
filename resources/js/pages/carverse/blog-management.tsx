import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { BlogPost } from './blog';
import type { Paginated } from './types';
import { ActionForm, Badge, Card, Field, Pager } from './ui';
export default function BlogManagement({ posts, featureMode = false }: { posts: Paginated<BlogPost>; featureMode?: boolean }) {
    const [editing, setEditing] = useState<BlogPost>();
    return (
        <div className="cv-two-grid">
            <Card>
                <h2>{featureMode ? 'Car features' : 'Blog posts'}</h2>
                <p>Published posts appear on the blog and the landing page. Drafts stay private.</p>
                <div className="cv-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Article</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {posts.data.map((post) => (
                                <tr key={post.id}>
                                    <td>
                                        <strong>{post.title}</strong>
                                        <br />
                                        <small>
                                            {post.category} · {post.author}
                                        </small>
                                    </td>
                                    <td>
                                        <Badge>{post.status}</Badge>
                                    </td>
                                    <td>
                                        <button className="cv-link" onClick={() => setEditing(post)}>
                                            Edit
                                        </button>
                                        {post.status === 'published' && (
                                            <>
                                                {' '}
                                                · <Link href={`/blog/${post.slug}`}>View</Link>
                                            </>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {!posts.data.length && <p>No blog posts yet. Write your first article.</p>}
                </div>
                <Pager links={posts.links} />
            </Card>
            <Card>
                <h2>{editing ? 'Edit article' : featureMode ? 'New car feature' : 'New article'}</h2>
                {editing && (
                    <button className="cv-link" onClick={() => setEditing(undefined)}>
                        Write a new article
                    </button>
                )}
                <ActionForm
                    key={editing?.id || 'new'}
                    action={editing ? `/articles/${editing.id}` : '/articles'}
                    method={editing ? 'put' : undefined}
                    label="Save article"
                >
                    <Field name="title" label="Title" value={editing?.title} />
                    <Field name="author" label="Author" value={editing?.author} />
                    <Field name="category" label="Category" value={editing?.category || (featureMode ? 'Car features' : 'Guides')} />
                    <fieldset className="cv-feature-editor">
                        <legend>Car feature</legend>
                        <label className="cv-check">
                            <input type="checkbox" name="is_car_feature" value="1" defaultChecked={editing?.is_car_feature ?? featureMode} /> Show in
                            Car features
                        </label>
                        <p>For owner stories and curated builds. Publish only details and photos you have permission to share.</p>
                        <Field name="feature_vehicle" label="Vehicle / build name" value={editing?.feature_vehicle || ''} required={false} />
                        <div className="cv-two-grid">
                            <Field
                                name="feature_year"
                                label="Year"
                                type="number"
                                value={editing?.feature_year || ''}
                                required={false}
                                min={1900}
                                max={new Date().getFullYear() + 1}
                            />
                            <Field name="feature_location" label="City / region" value={editing?.feature_location || ''} required={false} />
                        </div>
                        <Field name="feature_engine" label="Engine" value={editing?.feature_engine || ''} required={false} />
                        <Field name="feature_transmission" label="Transmission" value={editing?.feature_transmission || ''} required={false} />
                        <Field
                            name="feature_credit"
                            label="Public build credit (with permission)"
                            value={editing?.feature_credit || ''}
                            required={false}
                        />
                        <Field
                            name="feature_modifications"
                            label="Modifications & build notes"
                            type="textarea"
                            value={editing?.feature_modifications || ''}
                            required={false}
                        />
                    </fieldset>
                    <Field name="excerpt" label="Short introduction" type="textarea" value={editing?.excerpt || ''} required={false} />
                    {editing?.featured_image_url && (
                        <>
                            <img className="cv-blog-cover" src={editing.featured_image_url} alt={editing.featured_image_alt || ''} />
                            <label>
                                <input type="checkbox" name="remove_featured_image" value="1" /> Remove current featured image
                            </label>
                        </>
                    )}
                    <Field name="featured_image" label="Featured image (JPG, PNG or WebP, up to 5 MB)" type="file" required={false} />
                    <Field name="featured_image_alt" label="Image description" value={editing?.featured_image_alt || ''} required={false} />
                    <p className="cv-muted">Describe the image for readers using screen readers. Required when uploading an image.</p>
                    <Field name="body" label="Article text" type="textarea" value={editing?.body} />
                    <p className="cv-muted">Use plain text and blank lines for paragraphs. HTML is displayed as text.</p>
                    <Field name="sources" label="Sources & references" type="textarea" value={editing?.sources} required={false} />
                    <Field name="video_url" label="YouTube or Vimeo link (optional)" type="url" value={editing?.video_url || ''} required={false} />
                    <Field
                        name="reviewed_at"
                        label="Last reviewed"
                        type="date"
                        value={editing?.reviewed_at?.slice(0, 10) || new Date().toLocaleDateString('en-CA', { timeZone: 'Africa/Nairobi' })}
                    />
                    <Field
                        name="status"
                        label="Publication"
                        value={editing?.status || 'draft'}
                        options={[
                            { value: 'draft', label: 'Draft — private' },
                            { value: 'published', label: 'Published — public' },
                        ]}
                    />
                </ActionForm>
            </Card>
        </div>
    );
}
