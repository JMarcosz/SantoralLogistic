import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface Company {
    id: number;
    name: string;
    rnc: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    logo_url: string | null;
    is_active: boolean;
}

export interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface Port {
    id: number;
    code: string;
    name: string;
    country: string;
    city: string | null;
    unlocode: string | null;
    iata_code: string | null;
    type: 'air' | 'ocean' | 'ground';
    timezone: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface ServiceType {
    id: number;
    code: string;
    name: string;
    description: string | null;
    scope: string | null;
    default_incoterm: string | null;
    is_active: boolean;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface PackageType {
    id: number;
    code: string;
    name: string;
    description: string | null;
    category: 'box' | 'pallet' | 'container' | 'envelope' | 'other' | null;
    length_cm: number | null;
    width_cm: number | null;
    height_cm: number | null;
    max_weight_kg: number | null;
    is_container: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface TransportMode {
    id: number;
    code: string;
    name: string;
    description: string | null;
    supports_awb: boolean;
    supports_bl: boolean;
    supports_pod: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface ProductService {
    id: number;
    code: string;
    name: string;
    description: string | null;
    type: 'service' | 'product' | 'fee';
    uom: string | null;
    default_currency_id: number | null;
    default_currency?: {
        id: number;
        code: string;
        name: string;
        symbol: string;
    };
    default_unit_price: number | null;
    taxable: boolean;
    gl_account_code: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface Rate {
    id: number;
    origin_port_id: number;
    destination_port_id: number;
    transport_mode_id: number;
    service_type_id: number;
    currency_id: number;
    origin_port?: { id: number; code: string; name: string };
    destination_port?: { id: number; code: string; name: string };
    transport_mode?: { id: number; code: string; name: string };
    service_type?: { id: number; code: string; name: string };
    currency?: { id: number; code: string; symbol: string };
    charge_basis: 'per_shipment' | 'per_kg' | 'per_cbm' | 'per_container';
    base_amount: number;
    min_amount: number | null;
    valid_from: string;
    valid_to: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface Division {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
}

export interface Customer {
    id: number;
    code: string | null;
    name: string;
    tax_id: string | null;
    tax_id_type: 'RNC' | 'CEDULA' | 'OTHER' | null; // Nuevo Campo
    fiscal_name: string | null; // Nuevo Campo
    ncf_type_default: 'B01' | 'B02' | 'B14' | null; // Nuevo Campo
    billing_address: string | null;
    shipping_address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    email_billing: string | null;
    phone: string | null;
    website: string | null;
    status: 'prospect' | 'active' | 'inactive';
    credit_limit: number | null;
    currency_id: number | null;
    currency?: { id: number; code: string; symbol: string; name?: string };
    payment_terms: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    contacts?: Contact[];
}

export interface Contact {
    id: number;
    customer_id: number;
    name: string;
    email: string | null;
    phone: string | null;
    position: string | null;
    contact_type: 'general' | 'billing' | 'operations' | 'sales' | null;
    is_primary: boolean;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
    permission?: string; // Optional permission to view entire group
}

export interface NavItem {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>; // Optional for parent items with subitems
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: NavItem[]; // Optional subitems for nested navigation
    permission?: string; // Optional permission required to view this item
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    company: Company | null;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    role_names?: string[]; // From backend $appends
    permission_names?: string[]; // From backend $appends
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface QuoteLine {
    id: number;
    quote_id: number;
    product_service_id: number;
    product_service?: { id: number; code: string; name: string; type?: string };
    line_type?: 'product' | 'service';
    description: string | null;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_rate: number;
    line_total: number;
    sort_order: number;
}

export interface Quote {
    id: number;
    quote_number: string;
    customer_id: number;
    customer?: {
        id: number;
        name: string;
        code: string | null;
        tax_id?: string | null;
        billing_address?: string | null;
    };
    contact_id: number | null;
    contact?: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
    };
    origin_port_id: number;
    origin_port?: { id: number; code: string; name: string; country: string };
    destination_port_id: number;
    destination_port?: {
        id: number;
        code: string;
        name: string;
        country: string;
    };
    transport_mode_id: number;
    transport_mode?: { id: number; code: string; name: string };
    service_type_id: number;
    service_type?: { id: number; code: string; name: string };
    currency_id: number;
    currency?: { id: number; code: string; symbol: string };
    status: 'draft' | 'sent' | 'approved' | 'rejected' | 'expired';
    total_pieces: number | null;
    total_weight_kg: number | null;
    total_volume_cbm: number | null;
    chargeable_weight_kg: number | null;
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    valid_until: string | null;
    notes: string | null;
    terms: string | null;
    payment_terms_id: number | null;
    payment_terms?: { id: number; code: string; name: string };
    footer_terms_id: number | null;
    footer_terms?: { id: number; code: string; name: string };
    payment_terms_snapshot: string | null;
    footer_terms_snapshot: string | null;
    created_by: number | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    lines?: QuoteLine[];
    lines_count?: number;
    has_shipping_order?: boolean;
    has_sales_order?: boolean;
    items?: QuoteItem[];
    // New fields
    shipper_id?: number | null;
    consignee_id?: number | null;
    project_id?: number | null;
    issuing_company_id?: number | null;
    carrier_id?: number | null;
    division_id?: number | null;
    division?: Division | null;
    pickup_address?: string | null;
    delivery_address?: string | null;
    transit_days?: number | null;
    incoterms?: string | null;
}

export interface QuoteItemLine {
    id?: number;
    quote_item_id?: number;
    pieces: number;
    description: string;
    weight_kg: number;
    volume_cbm: number;
    marks_numbers?: string;
    hs_code?: string;
}

export interface QuoteItem {
    id?: number;
    quote_id?: number;
    type: 'container' | 'vehicle' | 'loose_cargo';
    identifier?: string;
    seal_number?: string;
    properties?: Record<string, string | number | boolean | null>;
    lines: QuoteItemLine[];
}
