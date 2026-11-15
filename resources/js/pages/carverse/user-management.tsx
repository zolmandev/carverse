import { Link } from '@inertiajs/react';
import { ArrowUpRight, Search, ShieldCheck, Users } from 'lucide-react';
import type { Paginated } from './types';
import { ActionForm, Badge, Card, date, Empty, Field, Pager } from './ui';
import './user-management.css';

type Account = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: 'enthusiast' | 'seller' | 'admin';
    location: string | null;
    phone_verified_at: string | null;
    profile_image_url: string | null;
    created_at: string;
    vehicles_count: number;
    organizations_count: number;
};

export type UsersData = {
    accounts: Paginated<Account>;
    counts: Partial<Record<Account['role'], number>>;
};

export default function UserManagement({ data, filters, currentUserId }: { data: UsersData; filters: { q?: string; user_role?: string }; currentUserId: number }) {
    const total = Number(data.counts.enthusiast ?? 0) + Number(data.counts.seller ?? 0) + Number(data.counts.admin ?? 0);

    return (
        <div className="cv-user-management">
            <div className="cv-user-metrics">
                <div><Users size={19} /><span>All accounts</span><strong>{total}</strong></div>
                <div><Users size={19} /><span>Enthusiasts</span><strong>{Number(data.counts.enthusiast ?? 0)}</strong></div>
                <div><Users size={19} /><span>Sellers</span><strong>{Number(data.counts.seller ?? 0)}</strong></div>
                <div><ShieldCheck size={19} /><span>Admins</span><strong>{Number(data.counts.admin ?? 0)}</strong></div>
            </div>
            <Card className="cv-user-search-card">
                <form method="get" action="/app/admin" className="cv-user-search">
                    <input type="hidden" name="tab" value="users" />
                    <label>
                        <span>Search accounts</span>
                        <input type="search" name="q" defaultValue={filters.q ?? ''} placeholder="Name or email" />
                    </label>
                    <label>
                        <span>Account type</span>
                        <select name="user_role" defaultValue={filters.user_role ?? ''}>
                            <option value="">All types</option>
                            <option value="enthusiast">Enthusiast</option>
                            <option value="seller">Seller</option>
                            <option value="admin">Admin</option>
                        </select>
                    </label>
                    <button type="submit" className="cv-button"><Search size={16} /> Search</button>
                </form>
            </Card>
            {data.accounts.data.length ? (
                <div className="cv-user-list">
                    {data.accounts.data.map((account) => (
                        <Card key={account.id} className="cv-user-card">
                            <div className="cv-user-card-main">
                                <span className="cv-user-avatar">
                                    {account.profile_image_url ? <img src={account.profile_image_url} alt="" /> : account.name.slice(0, 1).toUpperCase()}
                                </span>
                                <div className="cv-user-identity">
                                    <strong>{account.name}</strong>
                                    <span>{account.email}</span>
                                    {account.phone && <span>{account.phone}</span>}
                                    <small>Joined {date(account.created_at)}{account.location ? ` · ${account.location}` : ''}</small>
                                </div>
                                <div className="cv-user-tags">
                                    <Badge tone={account.role === 'admin' ? 'red' : account.role === 'seller' ? 'green' : 'neutral'}>
                                        {account.role === 'admin' ? 'Admin' : account.role === 'seller' ? 'Seller' : 'Enthusiast'}
                                    </Badge>
                                    <Badge tone={account.phone_verified_at ? 'green' : 'amber'}>{account.phone_verified_at ? 'Phone verified' : 'Phone unverified'}</Badge>
                                </div>
                            </div>
                            <div className="cv-user-card-footer">
                                <div className="cv-user-card-stats">
                                    <span>{account.vehicles_count} {account.vehicles_count === 1 ? 'vehicle' : 'vehicles'}</span>
                                    <span>{account.organizations_count} {account.organizations_count === 1 ? 'provider' : 'providers'}</span>
                                </div>
                                <div className="cv-user-card-actions">
                                    <Link href={`/profile/${account.id}`} className="cv-link">View profile <ArrowUpRight size={15} /></Link>
                                    {account.id === currentUserId ? (
                                        <span className="cv-user-self-note">Your account</span>
                                    ) : (
                                        <ActionForm action={`/users/${account.id}/role`} label="Save role">
                                            <Field name="role" label="Account type" value={account.role} options={[{ value: 'enthusiast', label: 'Enthusiast' }, { value: 'seller', label: 'Seller' }, { value: 'admin', label: 'Admin' }]} />
                                        </ActionForm>
                                    )}
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>
            ) : <Empty>No accounts match this search.</Empty>}
            <Pager links={data.accounts.links} />
        </div>
    );
}
