export interface FormQuoteLine {
    id?: number;
    product_service_id: number | '';
    line_type: 'product' | 'service';
    description: string;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_rate: number;
}

export interface Project {
    id: number;
    name: string;
    code: string;
}

export interface IssuingCompany {
    id: number;
    name: string;
}

export interface Carrier {
    id: number;
    name: string;
    code: string;
}

export interface Division {
    id: number;
    name: string;
    code: string;
}
