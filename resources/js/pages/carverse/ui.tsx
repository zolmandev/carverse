import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/react';
import { FormEvent, ReactNode, useState } from 'react';
export const money = (minor: number) =>
    new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(minor / 100);
export const date = (value: string) =>
    new Date(value).toLocaleDateString('en-KE', { timeZone: 'Africa/Nairobi', day: 'numeric', month: 'short', year: 'numeric' });
export function Badge({ children, tone = 'neutral' }: { children: ReactNode; tone?: string }) {
    return <span className={`cv-badge ${tone}`}>{children}</span>;
}
export function Field({
    name,
    label,
    type = 'text',
    value,
    options,
    required = true,
    min,
    max,
}: {
    name: string;
    label: string;
    type?: string;
    value?: string | number;
    options?: { value: string | number; label: string }[];
    required?: boolean;
    min?: number;
    max?: number;
}) {
    return (
        <label className="cv-field">
            <span>{label}</span>
            {options ? (
                <select name={name} defaultValue={value} required={required}>
                    {!required && <option value="">Any / not selected</option>}
                    {options.map((o) => (
                        <option value={o.value} key={o.value}>
                            {o.label}
                        </option>
                    ))}
                </select>
            ) : type === 'textarea' ? (
                <textarea name={name} defaultValue={value} required={required} rows={3} />
            ) : (
                <input
                    name={name}
                    type={type}
                    defaultValue={type === 'file' ? undefined : value}
                    required={required}
                    min={min}
                    max={max}
                    step={name === 'elapsed_seconds' ? '0.001' : name.includes('kes') ? '0.01' : undefined}
                    accept={
                        type === 'file'
                            ? name === 'photo'
                                ? '.jpg,.jpeg,.png'
                                : name === 'gps'
                                  ? '.gpx,.json,.csv,.pdf,.jpg,.jpeg,.png'
                                  : '.pdf,.jpg,.jpeg,.png'
                            : undefined
                    }
                />
            )}
        </label>
    );
}
export function ActionForm({
    action,
    children,
    label = 'Save',
    method,
    wide = false,
}: {
    action: string;
    children?: ReactNode;
    label?: string;
    method?: string;
    wide?: boolean;
}) {
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);
    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        const data = new FormData(e.currentTarget);
        for (const [key, value] of [...data.entries()]) {
            if (key.includes('_kes') && typeof value === 'string') {
                const target = key.replace('_kes', '_minor');
                data.delete(key);
                data.set(target, value.trim() === '' ? '' : String(Math.round(Number(value) * 100)));
            }
        }
        for (let index = 1; index < 30; index++) {
            const prefix = `items[${index}]`;
            if (String(data.get(`${prefix}[description]`) ?? '').trim() === '' && String(data.get(`${prefix}[amount_minor]`) ?? '').trim() === '') {
                data.delete(`${prefix}[description]`);
                data.delete(`${prefix}[amount_minor]`);
            }
        }
        if (method) data.set('_method', method);
        setBusy(true);
        router.post(action, data, { preserveScroll: true, onError: setErrors, onSuccess: () => setErrors({}), onFinish: () => setBusy(false) });
    }
    return (
        <form onSubmit={submit} className={`cv-form ${wide ? 'wide' : ''}`}>
            {children}
            {Object.keys(errors).length > 0 && (
                <div role="alert" className="cv-errors">
                    {Object.entries(errors).map(([k, v]) => (
                        <p key={k}>{v}</p>
                    ))}
                </div>
            )}
            <Button type="submit" className="cv-button" disabled={busy}>
                {busy ? 'Saving…' : label}
            </Button>
        </form>
    );
}
export function Card({ title, children, className = '' }: { title?: string; children: ReactNode; className?: string }) {
    return (
        <section className={`cv-card ${className}`}>
            {title && <h2>{title}</h2>}
            {children}
        </section>
    );
}
export function Empty({ children }: { children: ReactNode }) {
    return <div className="cv-empty">{children}</div>;
}
export function Pager({ links }: { links: { url: string | null; label: string; active: boolean }[] }) {
    return (
        <nav className="cv-pager" aria-label="Pagination">
            {links.map((l, i) =>
                l.url ? (
                    <Link key={i} href={l.url} className={l.active ? 'active' : ''}>
                        {l.label.replace(/&laquo;/g, '‹').replace(/&raquo;/g, '›')}
                    </Link>
                ) : null,
            )}
        </nav>
    );
}
export function Details({ title, children }: { title: string; children: ReactNode }) {
    return (
        <details className="cv-details">
            <summary>{title}</summary>
            <div>{children}</div>
        </details>
    );
}
