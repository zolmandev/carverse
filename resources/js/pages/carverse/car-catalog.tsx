import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Paginated } from './types';
import { ActionForm, Badge, Card, Empty, Field, Pager } from './ui';
export type Brand = {
    id: number;
    name: string;
    is_active: boolean;
    logo_url: string | null;
    source: string | null;
    source_url: string | null;
    models_count?: number;
};
export type Model = {
    id: number;
    car_brand_id: number;
    name: string;
    is_active: boolean;
    source: string | null;
    source_url: string | null;
    brand: Brand;
    trims_count?: number;
};
export type Trim = {
    id: number;
    car_model_id: number;
    name: string;
    model_year: number | null;
    market: string;
    kind: 'grade' | 'variant';
    is_active: boolean;
    source: string | null;
    source_url: string | null;
    model: Model;
};
export type Catalog = {
    level: 'brands' | 'models' | 'trims';
    filters: { q?: string; catalog_brand_id?: string; catalog_model_id?: string };
    stats: { brands: number; models: number; trims: number; logos: number };
    brands: Paginated<Brand>;
    models: Paginated<Model>;
    trims: Paginated<Trim>;
};
type Option = { value: number; label: string };
function useOptions(url: string | null) {
    const [options, setOptions] = useState<Option[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    useEffect(() => {
        const abort = new AbortController();
        setOptions([]);
        setError('');
        if (!url) {
            setLoading(false);
            return;
        }
        setLoading(true);
        fetch(url, { signal: abort.signal, headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(async (r) => {
                if (!r.ok) throw new Error('Unable to load choices. Please refresh and try again.');
                return r.json() as Promise<{ options: Option[] }>;
            })
            .then((data) => setOptions(data.options))
            .catch((e) => {
                if (!abort.signal.aborted) setError(e instanceof Error ? e.message : 'Unable to load choices.');
            })
            .finally(() => {
                if (!abort.signal.aborted) setLoading(false);
            });
        return () => abort.abort();
    }, [url]);
    return { options, loading, error };
}
export function BrandMark({ brand }: { brand: Brand }) {
    const [failed, setFailed] = useState(false);
    useEffect(() => setFailed(false), [brand.logo_url]);
    return (
        <span className="cv-brand-mark">
            {brand.logo_url && !failed ? (
                <img src={brand.logo_url} alt={`${brand.name} logo`} loading="lazy" onError={() => setFailed(true)} />
            ) : (
                <span title="Brand initials">
                    {brand.name
                        .replace(/[^A-Za-z0-9 ]/g, '')
                        .split(' ')
                        .map((s) => s[0])
                        .join('')
                        .slice(0, 2)
                        .toUpperCase()}
                </span>
            )}
        </span>
    );
}
export function CatalogAttribution() {
    return (
        <p className="cv-catalog-attribution">
            Vehicle data by{' '}
            <a href="https://vehiclesdb.com" target="_blank" rel="noopener noreferrer">
                VehiclesDB
            </a>{' '}
            · Trims/variants by{' '}
            <a href="https://trimslate.com/data" target="_blank" rel="noopener noreferrer">
                Trimslate
            </a>{' '}
            ·{' '}
            <a href="https://github.com/vehiclesdb/vehiclesdb/blob/v2026.11.6/ATTRIBUTION.md" target="_blank" rel="noopener noreferrer">
                Source notices
            </a>
        </p>
    );
}
export function CarSelectFields({
    brands,
    initialBrandId,
    initialModelId,
    initialTrimId,
    showModel = true,
    showTrim = true,
    required = true,
    prefix = 'car',
    includeArchived = false,
}: {
    brands: Brand[];
    initialBrandId?: number | string | null;
    initialModelId?: number | string | null;
    initialTrimId?: number | string | null;
    showModel?: boolean;
    showTrim?: boolean;
    required?: boolean;
    prefix?: string;
    includeArchived?: boolean;
}) {
    const [brand, setBrand] = useState(String(initialBrandId ?? ''));
    const [model, setModel] = useState(String(initialModelId ?? ''));
    const [trim, setTrim] = useState(String(initialTrimId ?? ''));
    const suffix = includeArchived ? '?all=1' : '';
    const models = useOptions(brand && showModel ? `/cars/brands/${brand}/models${suffix}` : null);
    const trims = useOptions(model && showTrim ? `/cars/models/${model}/trims${suffix}` : null);
    return (
        <>
            <label className="cv-field">
                <span>Brand</span>
                <select
                    name={`${prefix}_brand_id`}
                    required={required}
                    value={brand}
                    onChange={(e) => {
                        setBrand(e.target.value);
                        setModel('');
                        setTrim('');
                    }}
                >
                    <option value="">{required ? 'Select a brand' : 'All brands'}</option>
                    {brands
                        .filter((b) => includeArchived || b.is_active)
                        .map((b) => (
                            <option key={b.id} value={b.id}>
                                {b.name}
                                {!b.is_active ? ' (archived)' : ''}
                            </option>
                        ))}
                </select>
            </label>
            {showModel && (
                <label className="cv-field">
                    <span>Model</span>
                    <select
                        name={`${prefix}_model_id`}
                        required={required}
                        value={model}
                        disabled={!brand || models.loading}
                        onChange={(e) => {
                            setModel(e.target.value);
                            setTrim('');
                        }}
                    >
                        <option value="">
                            {models.loading ? 'Loading models…' : !brand ? 'Select a brand first' : required ? 'Select a model' : 'All models'}
                        </option>
                        {models.options.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                    {brand && !models.loading && !models.options.length && !models.error && <small>No models recorded for this brand yet.</small>}
                </label>
            )}
            {showModel && showTrim && (
                <label className="cv-field">
                    <span>Trim / variant (optional)</span>
                    <select name={`${prefix}_trim_id`} value={trim} disabled={!model || trims.loading} onChange={(e) => setTrim(e.target.value)}>
                        <option value="">{trims.loading ? 'Loading trims…' : !model ? 'Select a model first' : 'All trims / not specified'}</option>
                        {trims.options.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                    {model && !trims.loading && !trims.options.length && !trims.error && <small>No trims recorded for this model yet.</small>}
                </label>
            )}
            {(models.loading || trims.loading) && (
                <span role="status" className="cv-catalog-hint">
                    Loading catalog choices…
                </span>
            )}
            {(models.error || trims.error) && (
                <p role="alert" className="cv-errors">
                    {models.error || trims.error}
                </p>
            )}
        </>
    );
}
function MetaFields({ row }: { row?: Brand | Model | Trim }) {
    return (
        <>
            <Field name="name" label="Name" value={row?.name} />
            <Field name="source" label="Source / reference" value={row?.source ?? 'Administrator maintained'} required={false} />
            <Field name="source_url" label="Source URL (optional)" type="url" value={row?.source_url ?? ''} required={false} />
            <Field
                name="is_active"
                label="Availability"
                value={row?.is_active === false ? '0' : '1'}
                options={[
                    { value: '1', label: 'Active' },
                    { value: '0', label: 'Archived' },
                ]}
            />
        </>
    );
}
export default function CarCatalog({ catalog: c, brands }: { catalog: Catalog; brands: Brand[] }) {
    const [editingBrand, setBrand] = useState<Brand>();
    const [editingModel, setModel] = useState<Model>();
    const [editingTrim, setTrim] = useState<Trim>();
    const level = c.level;
    const edit = level === 'brands' ? editingBrand : level === 'models' ? editingModel : editingTrim;
    const base = '/app/admin?tab=cars';
    const link = (l: string, b?: number, m?: number) =>
        `${base}&catalog_level=${l}${b ? `&catalog_brand_id=${b}` : ''}${m ? `&catalog_model_id=${m}` : ''}`;
    const formKey = edit ? `${level}-${edit.id}` : `new-${level}`;
    return (
        <>
            <div className="cv-stat-grid">
                {Object.entries(c.stats).map(([key, value]) => (
                    <div className="cv-stat" key={key}>
                        <span>{key === 'trims' ? 'Trims & variants' : key === 'logos' ? 'Brand logos' : key}</span>
                        <strong>{value.toLocaleString()}</strong>
                    </div>
                ))}
            </div>
            <div className="cv-note">
                Catalog coverage varies by market and year. Imported variant labels may describe a powertrain configuration rather than an equipment
                grade. Missing data can be added below.
            </div>
            <nav className="cv-catalog-tabs" aria-label="Car catalog sections">
                {['brands', 'models', 'trims'].map((l) => (
                    <Link key={l} href={link(l)} className={level === l ? 'active' : ''}>
                        {l === 'trims' ? 'Trims & variants' : l}
                    </Link>
                ))}
            </nav>
            <div className="cv-group-layout">
                <section className="cv-group-list">
                    <Card title={`Manage ${level === 'trims' ? 'trims & variants' : level}`}>
                        <form method="get" action="/app/admin" className="cv-form wide">
                            <input type="hidden" name="tab" value="cars" />
                            <input type="hidden" name="catalog_level" value={level} />
                            {level !== 'brands' && (
                                <CarSelectFields
                                    key={`filter-${c.filters.catalog_brand_id}-${c.filters.catalog_model_id}`}
                                    brands={brands}
                                    initialBrandId={c.filters.catalog_brand_id}
                                    initialModelId={c.filters.catalog_model_id}
                                    showModel={level === 'trims'}
                                    showTrim={false}
                                    required={false}
                                    prefix="catalog"
                                    includeArchived
                                />
                            )}
                            <Field name="q" label="Search names" value={c.filters.q ?? ''} required={false} />
                            <button className="cv-button">Search</button>
                        </form>
                        <div className="cv-table-scroll">
                            <table className="cv-group-table">
                                <thead>
                                    <tr>
                                        {level === 'brands' ? (
                                            <>
                                                <th>Brand</th>
                                                <th>Models</th>
                                            </>
                                        ) : level === 'models' ? (
                                            <>
                                                <th>Brand</th>
                                                <th>Model</th>
                                                <th>Trims</th>
                                            </>
                                        ) : (
                                            <>
                                                <th>Brand / model</th>
                                                <th>Trim / variant</th>
                                                <th>Year / market</th>
                                            </>
                                        )}
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {level === 'brands' &&
                                        c.brands.data.map((b) => (
                                            <tr key={b.id}>
                                                <td>
                                                    <div className="cv-brand-cell">
                                                        <BrandMark brand={b} />
                                                        <strong>{b.name}</strong>
                                                    </div>
                                                </td>
                                                <td>{b.models_count}</td>
                                                <td>
                                                    <Badge tone={b.is_active ? 'green' : ''}>{b.is_active ? 'Active' : 'Archived'}</Badge>
                                                </td>
                                                <td>
                                                    <div className="cv-group-actions">
                                                        <button className="cv-link" type="button" onClick={() => setBrand(b)}>
                                                            Edit
                                                        </button>
                                                        <Link className="cv-link" href={link('models', b.id)}>
                                                            Models
                                                        </Link>
                                                        {b.is_active && (
                                                            <ActionForm action={`/cars/catalog/brands/${b.id}`} method="DELETE" label="Archive" />
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    {level === 'models' &&
                                        c.models.data.map((m) => (
                                            <tr key={m.id}>
                                                <td>{m.brand.name}</td>
                                                <td>
                                                    <strong>{m.name}</strong>
                                                </td>
                                                <td>{m.trims_count}</td>
                                                <td>
                                                    <Badge tone={m.is_active ? 'green' : ''}>{m.is_active ? 'Active' : 'Archived'}</Badge>
                                                </td>
                                                <td>
                                                    <div className="cv-group-actions">
                                                        <button className="cv-link" type="button" onClick={() => setModel(m)}>
                                                            Edit
                                                        </button>
                                                        <Link className="cv-link" href={link('trims', m.car_brand_id, m.id)}>
                                                            Trims
                                                        </Link>
                                                        {m.is_active && (
                                                            <ActionForm action={`/cars/catalog/models/${m.id}`} method="DELETE" label="Archive" />
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    {level === 'trims' &&
                                        c.trims.data.map((t) => (
                                            <tr key={t.id}>
                                                <td>
                                                    {t.model.brand.name}
                                                    <br />
                                                    <strong>{t.model.name}</strong>
                                                </td>
                                                <td>
                                                    {t.name}
                                                    <br />
                                                    <Badge>{t.kind === 'grade' ? 'Equipment grade' : 'Variant / configuration'}</Badge>
                                                </td>
                                                <td>
                                                    {t.model_year ?? 'Not specified'}
                                                    <br />
                                                    {t.market}
                                                </td>
                                                <td>
                                                    <Badge tone={t.is_active ? 'green' : ''}>{t.is_active ? 'Active' : 'Archived'}</Badge>
                                                </td>
                                                <td>
                                                    <div className="cv-group-actions">
                                                        <button className="cv-link" type="button" onClick={() => setTrim(t)}>
                                                            Edit
                                                        </button>
                                                        {t.is_active && (
                                                            <ActionForm action={`/cars/catalog/trims/${t.id}`} method="DELETE" label="Archive" />
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                </tbody>
                            </table>
                        </div>
                        {!c[level].data.length && <Empty>No matching {level}. Add an entry using the form.</Empty>}
                        <Pager links={c[level].links} />
                    </Card>
                </section>
                <aside className="cv-group-form-sidebar">
                    <Card title={`${edit ? 'Edit' : 'Add'} ${level === 'brands' ? 'brand' : level === 'models' ? 'model' : 'trim / variant'}`}>
                        <ActionForm
                            key={formKey}
                            action={`/cars/${level}${edit ? `/${edit.id}` : ''}`}
                            method={edit ? 'PUT' : undefined}
                            label={edit ? 'Save changes' : 'Add entry'}
                        >
                            {level === 'models' && (
                                <CarSelectFields
                                    brands={brands}
                                    initialBrandId={editingModel?.car_brand_id ?? c.filters.catalog_brand_id}
                                    showModel={false}
                                    includeArchived
                                />
                            )}
                            {level === 'trims' && (
                                <CarSelectFields
                                    brands={brands}
                                    initialBrandId={editingTrim?.model.car_brand_id ?? c.filters.catalog_brand_id}
                                    initialModelId={editingTrim?.car_model_id ?? c.filters.catalog_model_id}
                                    showTrim={false}
                                    includeArchived
                                />
                            )}
                            <MetaFields row={edit} />
                            {level === 'brands' && (
                                <>
                                    <Field name="logo" label="Logo (PNG, JPEG or WebP, up to 2 MB)" type="file" required={false} />
                                    {editingBrand && <BrandMark brand={editingBrand} />}
                                    <small>Brands without a logo display their initials.</small>
                                </>
                            )}
                            {level === 'trims' && (
                                <>
                                    <Field
                                        name="kind"
                                        label="Record type"
                                        value={editingTrim?.kind ?? 'grade'}
                                        options={[
                                            { value: 'grade', label: 'Equipment grade / trim' },
                                            { value: 'variant', label: 'Variant / configuration' },
                                        ]}
                                    />
                                    <Field
                                        name="model_year"
                                        label="Model year (optional)"
                                        type="number"
                                        required={false}
                                        min={1886}
                                        max={new Date().getFullYear() + 2}
                                        value={editingTrim?.model_year ?? ''}
                                    />
                                    <Field name="market" label="Market" value={editingTrim?.market ?? 'Kenya'} />
                                </>
                            )}
                        </ActionForm>
                        {edit && (
                            <button
                                className="cv-link cv-group-cancel"
                                type="button"
                                onClick={() => {
                                    setBrand(undefined);
                                    setModel(undefined);
                                    setTrim(undefined);
                                }}
                            >
                                Cancel editing / add new
                            </button>
                        )}
                        <p>
                            Archive entries to remove them from new selections while preserving existing groups. Edit an archived entry to reactivate
                            it.
                        </p>
                    </Card>
                </aside>
            </div>
        </>
    );
}
