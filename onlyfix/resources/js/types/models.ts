// Car model
export interface Car {
    id: number;
    user_id: number;
    make: string;
    model: string;
    year: number;
    license_plate: string;
    vin?: string | null;
    color?: string | null;
    created_at: string;
    updated_at: string;
    user?: User;
    tickets?: Ticket[];
    tickets_count?: number;
}

export interface CarInput {
    make: string;
    model: string;
    year: number;
    license_plate: string;
    vin?: string;
    color?: string;
}

// Problem model
export interface Problem {
    id: number;
    name: string;
    category: string;
    description?: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    pivot?: {
        notes?: string;
    };
}

// Ticket model
export type TicketStatus = 'open' | 'assigned' | 'in_progress' | 'completed' | 'closed';
export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface Ticket {
    id: number;
    user_id: number;
    mechanic_id?: number | null;
    car_id: number;
    status: TicketStatus;
    priority: TicketPriority;
    description: string;
    accepted_at?: string | null;
    completed_at?: string | null;
    created_at: string;
    updated_at: string;
    user?: User;
    mechanic?: User | null;
    car?: Car;
    problems?: Problem[];
}

export interface TicketInput {
    car_id: number;
    description: string;
    priority?: TicketPriority;
    problem_ids: number[];
    problem_notes?: string[];
}

// User model (extended from types/index.d.ts)
export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: string[];
    permissions: string[];
    cars?: Car[];
    tickets?: Ticket[];
}

// Pagination
export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Dashboard stats
export interface DashboardStats {
    total_cars: number;
    total_tickets: number;
    open_tickets: number;
    completed_tickets: number;
    recent_tickets: Ticket[];
    cars: Car[];
}
