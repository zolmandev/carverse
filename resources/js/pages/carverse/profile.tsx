import { Head } from '@inertiajs/react';
import { Car, Fuel, Gauge, MapPin, Pencil, UserRound } from 'lucide-react';
import { ChangeEvent, useEffect, useState } from 'react';
import './culture.css';
import PublicHeader from './public-header';
import type { Vehicle } from './types';
import { ActionForm, Badge, Card, Details, Field, date } from './ui';
import './workspace.css';

type ProfileData = {
    id: number;
    name: string;
    role: string;
    location: string | null;
    profile_image_url: string | null;
    created_at: string;
};

type CatalogOption = { value: number; label: string };

function SearchableCatalogField({
    name,
    label,
    value,
    type,
    brandId,
    disabled = false,
    onChange,
    onSelect,
}: {
    name: string;
    label: string;
    value: string;
    type: 'brands' | 'models';
    brandId?: string;
    disabled?: boolean;
    onChange: (value: string) => void;
    onSelect: (option: CatalogOption) => void;
}) {
    const [options, setOptions] = useState<CatalogOption[]>([]);
    const [open, setOpen] = useState(false);
    useEffect(() => {
        if (disabled) {
            setOptions([]);
            return;
        }
        const controller = new AbortController();
        const params = new URLSearchParams({ type, ...(value ? { q: value } : {}), ...(brandId ? { brand_id: brandId } : {}) });
        fetch(`/cars/options?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal })
            .then((response) => (response.ok ? response.json() : Promise.reject(new Error('Unable to load catalog choices.'))))
            .then((data: { options: CatalogOption[] }) => setOptions(data.options))
            .catch(() => {
                if (!controller.signal.aborted) setOptions([]);
            });
        return () => controller.abort();
    }, [brandId, disabled, type, value]);
    return (
        <label className="cv-field cv-combobox">
            <span>{label}</span>
            <input
                name={name}
                value={value}
                autoComplete="off"
                disabled={disabled}
                placeholder={disabled ? 'Select a make first' : `Search ${label.toLowerCase()}`}
                onFocus={() => setOpen(true)}
                onBlur={() => window.setTimeout(() => setOpen(false), 150)}
                onChange={(event) => onChange(event.target.value)}
            />
            {open && options.length > 0 && (
                <div className="cv-combobox-menu" role="listbox">
                    {options.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            role="option"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => {
                                onSelect(option);
                                setOpen(false);
                            }}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>
            )}
        </label>
    );
}

function VehicleFields({ vehicle }: { vehicle?: Vehicle }) {
    const [make, setMake] = useState(vehicle?.make ?? '');
    const [model, setModel] = useState(vehicle?.model ?? '');
    const [brandId, setBrandId] = useState('');
    useEffect(() => {
        if (!make) return;
        const controller = new AbortController();
        fetch(`/cars/options?type=brands&q=${encodeURIComponent(make)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal })
            .then((response) => response.json())
            .then((data: { options: CatalogOption[] }) => {
                const match = data.options.find((option) => option.label.toLowerCase() === make.toLowerCase());
                if (match) setBrandId(String(match.value));
            })
            .catch(() => undefined);
        return () => controller.abort();
    }, [make]);
    return (
        <>
            <Field name="photo" label="Vehicle photo (optional, JPEG / PNG, up to 5 MB)" type="file" required={false} />
            <SearchableCatalogField
                name="make"
                label="Make"
                type="brands"
                value={make}
                onChange={(value) => {
                    setMake(value);
                    setBrandId('');
                    setModel('');
                }}
                onSelect={(option) => {
                    setMake(option.label);
                    setBrandId(String(option.value));
                    setModel('');
                }}
            />
            <SearchableCatalogField
                name="model"
                label="Model"
                type="models"
                value={model}
                brandId={brandId}
                disabled={!brandId}
                onChange={setModel}
                onSelect={(option) => setModel(option.label)}
            />
            <Field name="year" label="Year" type="number" min={1950} max={new Date().getFullYear() + 1} value={vehicle?.year ?? 2016} />
            <Field name="variant" label="Variant (optional)" required={false} value={vehicle?.variant} />
            <Field name="engine_code" label="Engine code (optional)" required={false} value={vehicle?.engine_code} />
            <Field name="fuel_type" label="Fuel" value={vehicle?.fuel_type ?? 'Petrol'} options={['Petrol', 'Diesel', 'Hybrid', 'Electric'].map((x) => ({ value: x, label: x }))} />
            <Field name="transmission" label="Transmission" value={vehicle?.transmission ?? 'Automatic'} options={['Automatic', 'Manual', 'CVT'].map((x) => ({ value: x, label: x }))} />
            <Field name="identifier" label="VIN / chassis / frame number (private)" required={false} value={vehicle?.identifier} />
            <Field name="mileage" label="Odometer (km)" type="number" min={vehicle?.mileage ?? 0} value={vehicle?.mileage ?? 0} />
            <Field name="mileage_date" label="Reading date" type="date" value={vehicle?.mileage_date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10)} />
        </>
    );
}

export default function Profile({ profile, isOwner, counties, vehicles = [], activeVehicleId }: { profile: ProfileData; isOwner: boolean; counties: string[]; vehicles?: Vehicle[]; activeVehicleId?: number | null }) {
    const memberType: Record<string, string> = { enthusiast: 'Enthusiast', seller: 'Seller', admin: 'Admin' };
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    useEffect(() => {
        return () => {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
        };
    }, [previewUrl]);

    function previewImage(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0];
        if (!file) return;
        setPreviewUrl((current) => {
            if (current) URL.revokeObjectURL(current);
            return URL.createObjectURL(file);
        });
    }

    return (
        <div className="cv-public cv-culture cv-profile-page">
            <Head title={`${profile.name} · Profile`} />
            <PublicHeader />
            <main className="cv-profile-shell">
                <div className="cv-profile-card">
                    <div className="cv-profile-avatar">
                        {previewUrl || profile.profile_image_url ? (
                            <img src={previewUrl ?? profile.profile_image_url ?? undefined} alt={`${profile.name} profile`} />
                        ) : (
                            <UserRound size={54} />
                        )}
                        {isOwner && (
                            <label className="cv-profile-avatar-edit" htmlFor="profile-image-upload">
                                <Pencil size={15} />
                                <span className="sr-only">Set profile picture</span>
                            </label>
                        )}
                    </div>
                    <div className="cv-profile-copy">
                        <p className="cv-culture-kicker">{memberType[profile.role] ?? 'Member'}</p>
                        <h1>{profile.name}</h1>
                        {profile.location && (
                            <p className="cv-profile-location">
                                <MapPin size={15} /> {profile.location}
                            </p>
                        )}
                        <p className="cv-muted">Joined {date(profile.created_at)}</p>
                    </div>
                </div>
                {isOwner ? (
                    <Card title="Your public profile" className="cv-profile-edit-card">
                        <p className="cv-muted">Update the details and image other enthusiasts see on your profile.</p>
                        <ActionForm action="/profile" label="Save profile" wide>
                            <Field name="name" label="Display name" value={profile.name} />
                            <label className="cv-field">
                                <span>County</span>
                                <select name="location" defaultValue={profile.location === 'Nairobi' ? 'Nairobi City' : (profile.location ?? '')}>
                                    <option value="">Select a county</option>
                                    {counties.map((county) => (
                                        <option key={county} value={county}>
                                            {county}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <input
                                id="profile-image-upload"
                                className="cv-profile-image-input"
                                name="profile_image"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp"
                                onChange={previewImage}
                            />
                        </ActionForm>
                    </Card>
                ) : null}
                {isOwner && (
                    <section className="cv-profile-garage" id="garage" aria-labelledby="cv-profile-garage-title">
                        <div className="cv-profile-garage-heading">
                            <div>
                                <p className="cv-culture-kicker">PRIVATE TO YOU</p>
                                <h2 id="cv-profile-garage-title">My garage</h2>
                                <p>Your saved vehicles help you browse parts and specialists with the right context.</p>
                            </div>
                            <span>{vehicles.length} {vehicles.length === 1 ? 'vehicle' : 'vehicles'}</span>
                        </div>
                        <div className="cv-profile-garage-grid">
                            {vehicles.map((vehicle, index) => (
                                <Card key={vehicle.id} className="cv-profile-vehicle-card">
                                    <div className="cv-profile-vehicle-visual">
                                        {vehicle.photo_url ? <img src={vehicle.photo_url} alt={`${vehicle.make} ${vehicle.model}`} /> : <Car size={42} aria-hidden="true" />}
                                    </div>
                                    <div className="cv-profile-vehicle-heading">
                                        <div>
                                            <p className="cv-culture-kicker">{vehicle.year} · {vehicle.fuel_type}</p>
                                            <h3>{vehicle.make} {vehicle.model}</h3>
                                        </div>
                                        {(activeVehicleId ? activeVehicleId === vehicle.id : index === 0) && <Badge tone="green">Active</Badge>}
                                    </div>
                                    <p className="cv-profile-vehicle-subtitle">{vehicle.variant || 'Vehicle details saved'}</p>
                                    <div className="cv-profile-vehicle-facts">
                                        <span><Gauge size={16} /> {vehicle.mileage.toLocaleString()} km</span>
                                        <span><Fuel size={16} /> {vehicle.transmission}</span>
                                    </div>
                                    <p className="cv-profile-vehicle-reading">Odometer recorded {date(vehicle.mileage_date)}</p>
                                    <Details title="Edit vehicle / update mileage">
                                        <ActionForm action={`/vehicles/${vehicle.id}`} method="PUT" wide>
                                            <VehicleFields vehicle={vehicle} />
                                        </ActionForm>
                                    </Details>
                                    {(activeVehicleId ? activeVehicleId !== vehicle.id : index !== 0) && <ActionForm action={`/vehicles/${vehicle.id}/active`} label="Set as active" />}
                                </Card>
                            ))}
                            <Card className="cv-profile-add-vehicle">
                                <span className="cv-profile-add-icon"><Car size={27} /></span>
                                <h3>Add a vehicle</h3>
                                <p>Save your car once to keep its details ready across Carverse.</p>
                                <Details title="Add to my garage">
                                    <ActionForm action="/vehicles" label="Save vehicle" wide>
                                        <VehicleFields />
                                    </ActionForm>
                                </Details>
                            </Card>
                        </div>
                    </section>
                )}
            </main>
        </div>
    );
}
