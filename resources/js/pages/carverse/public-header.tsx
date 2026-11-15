import ThemeSwitcher from '@/components/theme-switcher';
import AppLogoIcon from '@/components/app-logo-icon';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, BookOpen, CalendarDays, Car, ChevronDown, House, Menu, UserRound, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import PublicFooter from './public-footer';
import './public-header.css';
import './public-layout.css';
import './workspace.css';

export default function PublicHeader() {
    const page = usePage<SharedData>();
    const { auth } = page.props;
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);
    const [mobileAccountOpen, setMobileAccountOpen] = useState(false);
    const isAdmin = auth.user?.role === 'admin';
    const currentPath = page.url || (typeof window !== 'undefined' ? window.location.pathname : '/');
    const isActive = (path: string) => currentPath === path || currentPath.startsWith(`${path}/`);

    const navClass = (path: string) => `cv-link${isActive(path) ? ' active' : ''}`;
    useEffect(() => {
        if (!drawerOpen) return;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setDrawerOpen(false);
                setAccountOpen(false);
            }
        };
        document.addEventListener('keydown', closeOnEscape);
        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            document.body.style.overflow = previousOverflow;
        };
    }, [drawerOpen]);

    const accountMenu = () => (
        <div className="cv-account-menu">
            <button
                type="button"
                className="cv-account-trigger"
                aria-label="Open account menu"
                aria-expanded={accountOpen}
                onClick={() => setAccountOpen((open) => !open)}
            >
                <span className="cv-account-avatar">
                    {auth.user?.profile_image_url ? <img src={auth.user.profile_image_url} alt="" /> : auth.user?.name.slice(0, 1)}
                </span>
                <span className="cv-account-name">{auth.user?.name}</span>
                <ChevronDown size={15} aria-hidden="true" />
            </button>
            {accountOpen && (
                <div className="cv-account-dropdown">
                    <Link
                        href="/profile"
                        onClick={() => {
                            setAccountOpen(false);
                            setDrawerOpen(false);
                        }}
                    >
                        Profile
                    </Link>
                    <Link href="/profile#garage" onClick={() => setAccountOpen(false)}>
                        My garage
                    </Link>
                    {isAdmin && (
                        <Link
                            href="/app/admin"
                            onClick={() => {
                                setAccountOpen(false);
                                setDrawerOpen(false);
                            }}
                        >
                            Admin dashboard <ArrowRight size={15} />
                        </Link>
                    )}
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        onClick={() => {
                            setAccountOpen(false);
                            setDrawerOpen(false);
                        }}
                    >
                        Sign out
                    </Link>
                </div>
            )}
        </div>
    );

    const closeDrawer = () => {
        setDrawerOpen(false);
        setMobileAccountOpen(false);
    };
    const mobileDrawer =
        typeof document !== 'undefined' &&
        createPortal(
            <div className={drawerOpen ? 'cv-mobile-menu-layer open' : 'cv-mobile-menu-layer'} aria-hidden={!drawerOpen}>
                <button
                    type="button"
                    className="cv-mobile-menu-backdrop"
                    aria-label="Close menu"
                    onClick={closeDrawer}
                    tabIndex={drawerOpen ? 0 : -1}
                />
                <aside className="cv-mobile-menu-panel" role="dialog" aria-modal={drawerOpen} aria-label="Mobile navigation">
                    <div className="cv-mobile-menu-heading">
                        <span>Menu</span>
                        <button type="button" aria-label="Close menu" onClick={closeDrawer} tabIndex={drawerOpen ? 0 : -1}>
                            <X size={20} />
                        </button>
                    </div>
                    <nav aria-label="Mobile navigation">
                        {(
                            [
                                ['/features', 'Car features'],
                                ['/directory', 'Specialists'],
                                ['/blog', 'Blog'],
                                ['/events', 'Events'],
                            ] as const
                        ).map(([path, label]) => (
                            <Link key={path} href={path} className={navClass(path)} onClick={closeDrawer} tabIndex={drawerOpen ? 0 : -1}>
                                {label}
                            </Link>
                        ))}
                    </nav>
                    <div className="cv-mobile-menu-footer">
                        <ThemeSwitcher />
                        {auth.user ? (
                            <div className="cv-mobile-account">
                                <button
                                    type="button"
                                    className="cv-mobile-menu-user"
                                    aria-expanded={mobileAccountOpen}
                                    aria-controls="cv-mobile-account-links"
                                    onClick={() => setMobileAccountOpen((open) => !open)}
                                    tabIndex={drawerOpen ? 0 : -1}
                                >
                                    <span className="cv-account-avatar">
                                        {auth.user.profile_image_url ? <img src={auth.user.profile_image_url} alt="" /> : auth.user.name.slice(0, 1)}
                                    </span>
                                    <span>{auth.user.name}</span>
                                    <span aria-hidden="true">⌄</span>
                                </button>
                                {mobileAccountOpen && (
                                    <div id="cv-mobile-account-links" className="cv-mobile-account-links">
                                        <Link href="/profile" onClick={closeDrawer}>
                                            Profile
                                        </Link>
                                        <Link href="/profile#garage" onClick={closeDrawer}>
                                            My garage
                                        </Link>
                                        {isAdmin && (
                                            <Link href="/app/admin" onClick={closeDrawer}>
                                                Admin dashboard <ArrowRight size={15} />
                                            </Link>
                                        )}
                                        <Link href="/logout" method="post" as="button" onClick={closeDrawer}>
                                            Sign out
                                        </Link>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <>
                                <Link href="/login" onClick={closeDrawer} tabIndex={drawerOpen ? 0 : -1}>
                                    Sign in
                                </Link>
                                <Link href="/register" className="cv-button" onClick={closeDrawer} tabIndex={drawerOpen ? 0 : -1}>
                                    Join <ArrowRight size={16} />
                                </Link>
                            </>
                        )}
                    </div>
                </aside>
            </div>,
            document.body,
        );

    return (
        <>
            <header className="cv-public-header cv-global-header">
                <Link href="/" className="cv-logo">
                    <AppLogoIcon className="cv-logo-mark" aria-hidden="true" />
                    carverse<span className="cv-ke">KE</span>
                </Link>
                <button
                    type="button"
                    className="cv-public-menu-toggle"
                    aria-label={drawerOpen ? 'Close menu' : 'Open menu'}
                    aria-expanded={drawerOpen}
                    onClick={() => {
                        setDrawerOpen((open) => !open);
                        setAccountOpen(false);
                    }}
                >
                    {drawerOpen ? <X size={22} /> : <Menu size={22} />}
                </button>
                <nav aria-label="Main navigation" className="cv-public-nav">
                    <Link href="/features" className={navClass('/features')} aria-current={isActive('/features') ? 'page' : undefined}>
                        Car features
                    </Link>
                    <Link href="/directory" className={navClass('/directory')} aria-current={isActive('/directory') ? 'page' : undefined}>
                        Specialists
                    </Link>
                    <Link href="/blog" className={navClass('/blog')} aria-current={isActive('/blog') ? 'page' : undefined}>
                        Blog
                    </Link>
                    <Link href="/events" className={navClass('/events')} aria-current={isActive('/events') ? 'page' : undefined}>
                        Events
                    </Link>
                    <ThemeSwitcher />
                    {auth.user ? (
                        accountMenu()
                    ) : (
                        <>
                            <Link href="/login" className="cv-link">
                                Sign in
                            </Link>
                            <Link href="/register" className="cv-button">
                                Join <ArrowRight size={16} />
                            </Link>
                        </>
                    )}
                </nav>
            </header>
            {typeof document !== 'undefined' &&
                createPortal(
                    <nav className="cv-mobile-bottom-nav" aria-label="Quick navigation">
                        <Link href="/" aria-current={currentPath === '/' ? 'page' : undefined}>
                            <House size={20} />
                            <span>Home</span>
                        </Link>
                        <Link href="/features" aria-current={isActive('/features') ? 'page' : undefined}>
                            <Car size={20} />
                            <span>Features</span>
                        </Link>
                        <Link href="/events" aria-current={isActive('/events') || isActive('/app/events') ? 'page' : undefined}>
                            <CalendarDays size={20} />
                            <span>Events</span>
                        </Link>
                        <Link href="/blog" aria-current={isActive('/blog') ? 'page' : undefined}>
                            <BookOpen size={20} />
                            <span>Journal</span>
                        </Link>
                        <Link href={auth.user ? '/profile' : '/login'} aria-current={isActive('/profile') ? 'page' : undefined}>
                            {auth.user?.profile_image_url ? (
                                <img className="cv-bottom-nav-avatar" src={auth.user.profile_image_url} alt="" />
                            ) : (
                                <UserRound size={20} />
                            )}
                            <span>Profile</span>
                        </Link>
                    </nav>,
                    document.body,
                )}
            {mobileDrawer}
            {typeof document !== 'undefined' && createPortal(<PublicFooter />, document.body)}
        </>
    );
}
