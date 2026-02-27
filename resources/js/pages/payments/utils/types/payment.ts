export interface Payment {
    id: number;
    payment_number: string;
    type: 'inbound' | 'outbound';
    customer_id: number | null;
    supplier_id: number | null;
    payment_method_id: number;
    amount: number;
    currency_code: string;
    exchange_rate: number;
    base_amount: number;
    amount_allocated: number;
    amount_unapplied: number;
    payment_date: string;
    reference: string | null;
    notes: string | null;
    status: 'draft' | 'pending' | 'approved' | 'posted' | 'voided';
    bank_account_id: number | null;
    created_by: number;
    approved_by: number | null;
    approved_at: string | null;
    posted_by: number | null;
    posted_at: string | null;
    voided_by: number | null;
    voided_at: string | null;
    void_reason: string | null;
    created_at: string;
    updated_at: string;
    customer?: Customer;
    supplier?: Supplier;
    payment_method?: PaymentMethod;
    creator?: User;
    allocations?: PaymentAllocation[];
}

export interface PaymentAllocation {
    id: number;
    payment_id: number;
    invoice_id: number;
    amount_applied: number;
    invoice?: PendingInvoice;
}

export interface PendingInvoice {
    id: number;
    number: string;
    ncf: string | null;
    issue_date: string;
    total_amount: number;
    amount_paid: number;
    balance: number;
    currency_code: string;
}

export interface Customer {
    id: number;
    name: string;
    code: string | null;
    fiscal_name?: string;
}

export interface Supplier {
    id: number;
    name: string;
    code: string | null;
}

export interface PaymentMethod {
    id: number;
    name: string;
    code: string;
    type: string;
    is_active: boolean;
    sort_order: number;
}

export interface User {
    id: number;
    name: string;
    email: string;
}

export interface InvoiceAllocationData {
    invoice_id: number;
    invoice_number: string;
    balance: number;
    amount_applied: number;
    currency_code: string;
}

export interface WithholdingData {
    type: string;
    percentage: number;
    amount: number;
    description: string;
}
