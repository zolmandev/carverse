import ThemeSwitcher from '@/components/theme-switcher';
import AppLogoIcon from '@/components/app-logo-icon';
import { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Car, ExternalLink, Flag, LogOut, ShieldCheck, Users } from 'lucide-react';
import { useState } from 'react';
import type { BlogPost } from './blog';
import BlogManagement from './blog-management';
import CarCatalog, { Brand, BrandMark, CarSelectFields, Catalog, CatalogAttribution, Model, Trim } from './car-catalog';
import EventApprovals from './event-approvals';
import { EventApplications } from './event-types';
import ProviderApprovals, { Applications } from './provider-approvals';
import { Paginated } from './types';
import { ActionForm, Badge, Card, date, Details, Empty, Field, Pager } from './ui';
import UserManagement, { type UsersData } from './user-management';
import './workspace.css';
type Group = {
    id: number;
    name: string;
    model: string;
    url: string;
    car_brand_id: number | null;
    car_model_id: number | null;
    car_trim_id: number | null;
    brand: Brand | null;
    car_model: Model | null;
    trim: Trim | null;
};
type Run = {
    is_demo: boolean;
    id: number;
    driver: string;
    vehicle: string;
    event: string;
    venue: string;
    race_date: string;
    distance_metres: number;
    elapsed_ms: number;
    status: string;
    review_note: string | null;
    reviewed_at: string | null;
    reviewer: { name: string } | null;
};
interface Props {
    tab: 'groups' | 'racing' | 'cars' | 'approvals' | 'events' | 'blog' | 'users';
    users: UsersData | null;
    blogPosts: Paginated<BlogPost> | null;
    eventApplications: EventApplications | null;
    applications: Applications | null;
    carBrands: Brand[];
    catalog: Catalog | null;
    groups: Paginated<Group>;
    entries: Paginated<Run>;
    leaderboard: Run[];
    events: string[];
    filters: { content?: string; q?: string; event?: string; distance?: string; provider_status?: string; provider_kind?: string; user_role?: string };
    flash: { message?: string };
}
function GroupFields({ group, brands }: { group?: Group; brands: Brand[] }) {
    return (
        <>
            <Field name="name" label="Group name" value={group?.name} />
            <CarSelectFields
                brands={brands}
                initialBrandId={group?.car_brand_id}
                initialModelId={group?.car_model_id}
                initialTrimId={group?.car_trim_id}
            />
            <Field name="url" label="WhatsApp invitation link" type="url" value={group?.url} />
        </>
    );
}
export default function SuperUser(p: Props) {
    const { auth } = usePage<SharedData>().props;
    const racing = p.tab === 'racing';
    const cars = p.tab === 'cars';
    const approvals = p.tab === 'approvals';
    const eventApprovals = p.tab === 'events';
    const blog = p.tab === 'blog';
    const userManagement = p.tab === 'users';
    const pageTitle = userManagement
        ? 'User management'
        : blog
        ? p.filters.content === 'features'
            ? 'Car features'
            : 'Blog'
        : eventApprovals
          ? 'Event approvals'
          : approvals
            ? 'Garage & mechanic approvals'
            : cars
              ? 'Cars management'
              : racing
                ? 'Drag Racing Leaderboards'
                : 'WhatsApp group links';
    const [editingGroupId, setEditingGroupId] = useState<number>();
    const editingGroup = p.groups.data.find((group) => group.id === editingGroupId);
    return (
        <div className="cv-app">
            <Head title={pageTitle} />
            <aside className="cv-sidebar cv-super-sidebar">
                <Link href="/app/admin" className="cv-logo">
                    <AppLogoIcon className="cv-logo-mark" aria-hidden="true" />
                    carverse<span className="cv-ke">KE</span>
                </Link>
                <span className="cv-pilot">SUPER USER</span>
                <nav>
                    <Link href="/app/admin" className={p.tab === 'groups' ? 'selected' : ''}>
                        <Users size={19} />
                        WhatsApp group links
                    </Link>
                    <Link href="/app/admin?tab=cars" className={cars ? 'selected' : ''}>
                        <Car size={19} />
                        Cars management
                    </Link>
                    <Link href="/app/admin?tab=approvals" className={approvals ? 'selected' : ''}>
                        <ShieldCheck size={19} />
                        Provider approvals
                    </Link>
                    <Link href="/admin/blog?content=features" className={blog && p.filters.content === 'features' ? 'selected' : ''}>
                        <Car size={19} />
                        Car features
                    </Link>
                    <Link href="/admin/blog" className={blog && p.filters.content !== 'features' ? 'selected' : ''}>
                        <CalendarDays size={19} />
                        Blog
                    </Link>
                    <Link href="/app/admin?tab=events" className={eventApprovals ? 'selected' : ''}>
                        <CalendarDays size={19} />
                        Event approvals
                    </Link>
                    <Link href="/app/admin?tab=racing" className={racing ? 'selected' : ''}>
                        <Flag size={19} />
                        Drag racing leaderboards
                    </Link>
                    <Link href="/app/admin?tab=users" className={userManagement ? 'selected' : ''}>
                        <Users size={19} />
                        User management
                    </Link>
                </nav>
                <div className="cv-sidebar-bottom">
                    <div className="cv-person">
                        <span>{auth.user.name.slice(0, 1)}</span>
                        <div>
                            <strong>{auth.user.name}</strong>
                            <small>Super user</small>
                        </div>
                        <button aria-label="Sign out" onClick={() => router.post('/logout')}>
                            <LogOut size={17} />
                        </button>
                    </div>
                </div>
            </aside>
            <div className="cv-main">
                <header className="cv-topbar">
                    <span className="cv-eyebrow">CARVERSE KE / SUPER USER</span>
                    <ThemeSwitcher />
                    <button className="cv-link" onClick={() => router.post('/logout')}>
                        Sign out <LogOut size={15} />
                    </button>
                </header>
                <main className="cv-content">
                    <div className="cv-page-heading">
                        <div>
                            <p className="cv-eyebrow">
                                {userManagement
                                    ? 'ACCOUNT MANAGEMENT'
                                    : blog
                                    ? 'Write and publish stories for the Carverse community.'
                                    : eventApprovals
                                      ? 'EVENT SUBMISSIONS'
                                      : approvals
                                        ? 'PROVIDER APPLICATIONS'
                                        : cars
                                          ? 'VEHICLE CATALOG'
                                          : racing
                                            ? 'GPS & SLIP VERIFIED'
                                            : 'COMMUNITY MANAGEMENT'}
                            </p>
                            <h1>{pageTitle}</h1>
                            <p>
                                {userManagement
                                    ? 'Find accounts, review their access and manage roles.'
                                    : blog
                                    ? 'Write and publish stories for the Carverse community.'
                                    : eventApprovals
                                      ? 'Review submitted events before they appear in the event listings.'
                                      : approvals
                                        ? 'Review new garages and skilled mechanics before they join the directory.'
                                        : cars
                                          ? 'Manage brands, models, trims and brand logos.'
                                          : racing
                                            ? 'Manage recorded runs and review GPS evidence and timing slips.'
                                            : 'Add, edit, and remove the group links shown to your community.'}
                            </p>
                        </div>
                    </div>
                    {p.flash.message && (
                        <div className="cv-success" role="status">
                            {p.flash.message}
                        </div>
                    )}
                    {userManagement && p.users ? (
                        <UserManagement data={p.users} filters={p.filters} currentUserId={auth.user.id} />
                    ) : blog && p.blogPosts ? (
                        <BlogManagement key={p.filters.content || 'blog'} posts={p.blogPosts} featureMode={p.filters.content === 'features'} />
                    ) : eventApprovals && p.eventApplications ? (
                        <EventApprovals
                            key={`${p.eventApplications.status}-${p.filters.q}`}
                            applications={p.eventApplications}
                            search={p.filters.q}
                        />
                    ) : approvals && p.applications ? (
                        <ProviderApprovals
                            key={`${p.applications.status}-${p.filters.provider_kind}-${p.filters.q}`}
                            applications={p.applications}
                            filters={p.filters}
                        />
                    ) : cars && p.catalog ? (
                        <CarCatalog
                            key={`${p.catalog.level}-${p.catalog.filters.catalog_brand_id}-${p.catalog.filters.catalog_model_id}`}
                            catalog={p.catalog}
                            brands={p.carBrands}
                        />
                    ) : !racing ? (
                        <div className="cv-group-layout">
                            <section className="cv-group-list" aria-label="WhatsApp groups">
                                <form method="get" action="/app/admin" className="cv-search">
                                    <input
                                        name="q"
                                        aria-label="Search groups"
                                        placeholder="Search by group or vehicle model"
                                        defaultValue={p.filters.q}
                                    />
                                    <button className="cv-button">Search</button>
                                </form>
                                <Card title="Your group links">
                                    {p.groups.data.length ? (
                                        <div className="cv-table-scroll">
                                            <table className="cv-group-table">
                                                <caption className="sr-only">WhatsApp groups and management actions</caption>
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Group name</th>
                                                        <th scope="col">Brand</th>
                                                        <th scope="col">Model</th>
                                                        <th scope="col">Trim / variant</th>
                                                        <th scope="col">Join</th>
                                                        <th scope="col">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {p.groups.data.map((g) => (
                                                        <tr key={g.id} className={editingGroup?.id === g.id ? 'cv-group-editing' : ''}>
                                                            <td>
                                                                <strong>{g.name}</strong>
                                                            </td>
                                                            <td>
                                                                <div className="cv-brand-cell">
                                                                    {g.brand && <BrandMark brand={g.brand} />}
                                                                    <span>{g.brand?.name ?? 'Not assigned'}</span>
                                                                </div>
                                                            </td>
                                                            <td>{g.car_model?.name ?? g.model}</td>
                                                            <td>
                                                                {g.trim ? (
                                                                    <>
                                                                        {g.trim.name}
                                                                        {g.trim.model_year && <small> · {g.trim.model_year}</small>}
                                                                    </>
                                                                ) : (
                                                                    'All trims'
                                                                )}
                                                            </td>
                                                            <td>
                                                                <a
                                                                    className="cv-button cv-join-button"
                                                                    href={g.url}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    aria-label={`Join ${g.name} on WhatsApp (opens in a new tab)`}
                                                                >
                                                                    Join <ExternalLink size={13} />
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <div className="cv-group-actions">
                                                                    <button
                                                                        type="button"
                                                                        className="cv-link"
                                                                        aria-pressed={editingGroup?.id === g.id}
                                                                        onClick={() => setEditingGroupId(g.id)}
                                                                    >
                                                                        Edit
                                                                    </button>
                                                                    <Details title="Remove">
                                                                        <p>
                                                                            This removes the invitation from Carverse. It does not delete the WhatsApp
                                                                            group.
                                                                        </p>
                                                                        <ActionForm
                                                                            action={`/communities/${g.id}`}
                                                                            method="DELETE"
                                                                            label="Remove link"
                                                                        />
                                                                    </Details>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <Empty>
                                            {p.filters.q
                                                ? 'No groups match your search.'
                                                : 'No group links yet. Add your first group using the form.'}
                                        </Empty>
                                    )}
                                    <Pager links={p.groups.links} />
                                </Card>
                            </section>
                            <aside className="cv-group-form-sidebar" aria-label={editingGroup ? 'Edit WhatsApp group' : 'Add WhatsApp group'}>
                                <Card title={editingGroup ? 'Edit WhatsApp group' : 'Add a WhatsApp group'}>
                                    <p>
                                        {editingGroup
                                            ? 'Update the group details and invitation link.'
                                            : 'Add a group for your community to discover and join.'}
                                    </p>
                                    <ActionForm
                                        key={editingGroup?.id ?? 'new-group'}
                                        action={editingGroup ? `/communities/${editingGroup.id}` : '/communities'}
                                        method={editingGroup ? 'PUT' : undefined}
                                        label={editingGroup ? 'Save changes' : 'Add group link'}
                                    >
                                        <GroupFields group={editingGroup} brands={p.carBrands} />
                                    </ActionForm>
                                    {editingGroup && (
                                        <button type="button" className="cv-link cv-group-cancel" onClick={() => setEditingGroupId(undefined)}>
                                            Cancel editing / add new group
                                        </button>
                                    )}
                                    <p>Use a WhatsApp group invitation link beginning with https://chat.whatsapp.com/.</p>
                                </Card>
                            </aside>
                        </div>
                    ) : (
                        <>
                            <Card title="Add a recorded run">
                                <p>
                                    Upload GPS evidence and the timing slip for the same run. New entries appear on the leaderboard immediately; admin
                                    moderation can still reject a disputed run.
                                </p>
                                <ActionForm action="/racing" label="Publish run" wide>
                                    <Field name="driver" label="Driver display name" />
                                    <Field name="vehicle" label="Vehicle" />
                                    <Field name="event" label="Event name" />
                                    <Field name="venue" label="Track / venue" />
                                    <Field name="race_date" label="Run date" type="date" />
                                    <Field
                                        name="distance_metres"
                                        label="Race distance"
                                        options={[
                                            { value: '201', label: '⅛ mile (201 m)' },
                                            { value: '402', label: '¼ mile (402 m)' },
                                            { value: '805', label: '½ mile (805 m)' },
                                        ]}
                                    />
                                    <Field
                                        name="elapsed_seconds"
                                        label="Elapsed time in seconds (up to 3 decimal places)"
                                        type="number"
                                        min={1}
                                        max={120}
                                    />
                                    <Field name="gps" label="GPS evidence (GPX, JSON, CSV, PDF or image; up to 5 MB)" type="file" />
                                    <Field name="slip" label="Timing slip (PDF or image; up to 5 MB)" type="file" />
                                </ActionForm>
                            </Card>
                            <Card title="Leaderboard filters">
                                <form method="get" action="/app/admin" className="cv-form wide">
                                    <input type="hidden" name="tab" value="racing" />
                                    <Field
                                        name="event"
                                        label="Event"
                                        value={p.filters.event ?? ''}
                                        required={false}
                                        options={p.events.map((e) => ({ value: e, label: e }))}
                                    />
                                    <Field
                                        name="distance"
                                        label="Distance"
                                        value={p.filters.distance ?? ''}
                                        required={false}
                                        options={[
                                            { value: '201', label: '⅛ mile (201 m)' },
                                            { value: '402', label: '¼ mile (402 m)' },
                                            { value: '805', label: '½ mile (805 m)' },
                                        ]}
                                    />
                                    <button className="cv-button">Show results</button>
                                </form>
                            </Card>
                            <Card title="Drag racing leaderboard">
                                <p>
                                    Runs are published immediately and ranked by elapsed time within the same event and distance; tied times share a
                                    rank. Rejected runs are excluded. Top 100 runs are shown.
                                </p>
                                {!p.filters.event || !p.filters.distance ? (
                                    <Empty>Select an event and distance to view comparable results.</Empty>
                                ) : !p.leaderboard.length ? (
                                    <Empty>No runs for this event and distance.</Empty>
                                ) : (
                                    <div className="cv-table-scroll">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Driver / vehicle</th>
                                                    <th>Elapsed time</th>
                                                    <th>Evidence</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {p.leaderboard.map((run) => (
                                                    <tr key={run.id}>
                                                        <td>{p.leaderboard.findIndex((r) => r.elapsed_ms === run.elapsed_ms) + 1}</td>
                                                        <td>
                                                            <strong>{run.driver}</strong>
                                                            <br />
                                                            {run.vehicle}
                                                        </td>
                                                        <td>{(run.elapsed_ms / 1000).toFixed(3)} s</td>
                                                        <td>
                                                            <Badge tone="green">Published</Badge>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                                <div className="cv-note">
                                    Admin moderation is optional. Carverse does not automatically measure GPS runs or certify timing equipment.
                                </div>
                            </Card>
                            <Card title="Runs & evidence review">
                                {p.entries.data.map((run) => (
                                    <Details
                                        key={run.id}
                                        title={`${run.driver} · ${run.vehicle} · ${(run.elapsed_ms / 1000).toFixed(3)} s · ${run.status}`}
                                    >
                                        <p>
                                            {run.event} · {run.venue} · {date(run.race_date)} · {run.distance_metres} m
                                        </p>
                                        <div className="cv-tags">
                                            <a href={`/racing/${run.id}/evidence/gps`} className="cv-link">
                                                Download GPS evidence
                                            </a>
                                            <a href={`/racing/${run.id}/evidence/slip`} className="cv-link">
                                                Download timing slip
                                            </a>
                                        </div>
                                        {run.review_note && (
                                            <p>
                                                Review: {run.review_note} · {run.reviewer?.name}
                                                {run.reviewed_at ? ` · ${date(run.reviewed_at)}` : ''}
                                            </p>
                                        )}
                                        <ActionForm action={`/racing/${run.id}/review`} label="Save evidence review">
                                            <Field
                                                name="status"
                                                label="Decision"
                                                value={run.status}
                                                options={[
                                                    { value: 'pending', label: 'Pending review' },
                                                    { value: 'verified', label: 'Verified — both evidence sources reviewed' },
                                                    { value: 'rejected', label: 'Rejected' },
                                                ]}
                                            />
                                            <label className="cv-check">
                                                <input name="gps_checked" type="checkbox" value="1" />I reviewed the GPS evidence and its
                                                distance/time details.
                                            </label>
                                            <label className="cv-check">
                                                <input name="slip_checked" type="checkbox" value="1" />I reviewed the timing slip and its elapsed
                                                time.
                                            </label>
                                            <label className="cv-check">
                                                <input name="matching_run" type="checkbox" value="1" />
                                                Both sources match this driver, vehicle, event, distance and run.
                                            </label>
                                            <Field
                                                name="review_note"
                                                label="Evidence references, findings, and limitations"
                                                type="textarea"
                                                value={run.review_note ?? ''}
                                            />
                                        </ActionForm>
                                    </Details>
                                ))}
                                {!p.entries.data.length && <Empty>No recorded runs yet.</Empty>}
                                <Pager links={p.entries.links} />
                            </Card>
                        </>
                    )}
                    <CatalogAttribution />
                    <footer className="cv-footer">Carverse KE · Super user</footer>
                </main>
            </div>
        </div>
    );
}
