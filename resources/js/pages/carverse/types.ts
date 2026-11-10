export interface Vehicle {
    photo_url: string | null;
    id: number;
    make: string;
    model: string;
    year: number;
    variant: string;
    engine_code: string;
    fuel_type: string;
    transmission: string;
    registration: string;
    identifier: string;
    mileage: number;
    mileage_date: string;
    user_id: number;
}
export interface Attachment {
    id: number;
    name: string;
}
export interface Organization {
    photo_url: string | null;
    id: number;
    name: string;
    kind: string;
    location: string;
    phone: string;
    description: string;
    status: string;
    verification_scope: string;
    verified_at: string;
    specialties: string[];
    opening_hours: string;
    review_count?: number;
    ratings?: Record<string, number | null>;
    public_reviews?: Review[];
}
export interface Part {
    fitments?: { id: number; vehicle_id: number; status: string; evidence: string; assessed_at: string; assessor: { name: string } }[];
    photo_url: string | null;
    id: number;
    title: string;
    brand: string;
    category: string;
    description: string;
    oem_reference: string;
    condition: string;
    price_minor: number | null;
    stock: string;
    organization: Organization;
    fitment_status: string;
    fitment_assessor?: string;
    fitment_assessed_at?: string;
    fitment_evidence?: string;
}
export interface Quote {
    id: number;
    organization: Organization;
    items: { description: string; amount_minor: number }[];
    total_minor: number;
    valid_until: string;
    duration: string;
}
export interface ServiceRequest {
    attachments: Attachment[];
    id: number;
    user_id: number;
    organization_id: number | null;
    vehicle: Vehicle;
    kind: string;
    title: string;
    notes: string;
    status: string;
    quotes: Quote[];
    transaction: Transaction | null;
}
export interface Review {
    id: number;
    quality: number;
    communication: number;
    timeliness: number;
    value: number;
    body: string;
    response: string | null;
}
export interface Complaint {
    id: number;
    body: string;
    status: string;
    provider_response: string | null;
    decision: string | null;
    appeal: string | null;
    public_summary: string | null;
    attachments: Attachment[];
    transaction: Transaction;
}
export interface Transaction {
    commission_minor?: number;
    variable_cost_minor?: number | null;
    fitment_return?: boolean;
    id: number;
    user_id: number;
    organization_id: number;
    request: ServiceRequest;
    organization: Organization;
    total_minor: number;
    status: string;
    payment_status: string;
    quote_snapshot: { items: { description: string; amount_minor: number }[]; duration: string; provider: string };
    review: Review | null;
    complaints: Complaint[];
}
export interface ServiceRecord {
    provider_name: string | null;
    id: number;
    vehicle_id: number;
    service_date: string;
    mileage: number;
    work: string;
    parts_used: string;
    provenance: string;
    version: number;
    attachments: Attachment[];
    revisions: { id: number; reason: string; created_at: string; previous: { work: string; version: number } }[];
}
export interface Reminder {
    id: number;
    vehicle: Vehicle;
    title: string;
    due_date: string | null;
    due_mileage: number | null;
    source: string;
    notified_at: string | null;
}
export interface Article {
    video_url: string | null;
    id: number;
    title: string;
    body: string;
    author: string;
    sources: string;
    status: string;
    published_at: string;
    reviewed_at: string;
}
export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}
export interface WorkspaceProps {
    disputes: Transaction[];
    providerParts: Paginated<Part>;
    demoData: boolean;
    section: string;
    vehicles: Vehicle[];
    activeVehicle: Vehicle | null;
    requests: Paginated<ServiceRequest>;
    transactions: Paginated<Transaction>;
    parts: Paginated<Part>;
    providers: Organization[];
    organizations: Organization[];
    records: ServiceRecord[];
    reminders: Reminder[];
    shares: { id: number; vehicle_id: number; record_ids: number[]; expires_at: string; revoked_at: string | null }[];
    articles: Article[];
    communities: { id: number; name: string; model: string; url: string }[];
    notifications: { id: string; data: { title: string; vehicle: string; source: string; mileage_stale: boolean } }[];
    admin: null | {
        organizations: Organization[];
        parts: Part[];
        pilot_requests: Paginated<ServiceRequest>;
        attachments: (Attachment & { organization_id: number })[];
        records: ServiceRecord[];
        transactions: Paginated<Transaction>;
        deletions: { id: number; status: string; retention_note: string | null; user: { name: string; email: string } }[];
        users: Paginated<{ id: number; name: string; email: string; role: string }>;
        audits: { id: number; action: string; subject_type: string; subject_id: number; created_at: string }[];
        articles: Article[];
        metrics: Record<string, number | boolean | null>;
    };
    moderation: Paginated<Complaint> | null;
    paymentMode: string;
    filters: { q?: string; category?: string };
    flash: { message?: string; share_url?: string };
}
