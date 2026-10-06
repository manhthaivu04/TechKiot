export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
    links?: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
}

export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
}

export interface ApiResponse<T> {
    data: T;
    message?: string;
}
