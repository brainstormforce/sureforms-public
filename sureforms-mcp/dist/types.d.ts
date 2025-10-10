/**
 * Type definitions for SureForms MCP Server
 */
export interface WordPressConfig {
    url: string;
    username?: string;
    applicationPassword?: string;
    consumerKey?: string;
    consumerSecret?: string;
}
export interface SureForm {
    ID: number;
    post_title: string;
    post_name: string;
    post_status: string;
    post_date: string;
    post_modified: string;
    post_content: string;
    meta?: Record<string, any>;
}
export interface FormEntry {
    ID: number;
    form_id: number;
    user_id: number;
    status: 'read' | 'unread' | 'trash';
    type?: string;
    form_data: Record<string, any>;
    submission_info: Record<string, any>;
    notes: Array<any>;
    logs: Array<{
        title: string;
        messages: string[];
        timestamp: number;
    }>;
    created_at: string;
    extras: Record<string, any>;
}
export interface FormField {
    id: string;
    type: string;
    label: string;
    required?: boolean;
    options?: Array<{
        label: string;
        value: string;
    }>;
    attributes?: Record<string, any>;
}
export interface SearchFilters {
    form_id?: number;
    status?: string;
    user_id?: number;
    date_from?: string;
    date_to?: string;
    search_term?: string;
}
export interface PaginationOptions {
    limit?: number;
    offset?: number;
    orderby?: string;
    order?: 'ASC' | 'DESC';
}
export interface ToolResponse<T = any> {
    success: boolean;
    data?: T;
    error?: string;
    message?: string;
}
//# sourceMappingURL=types.d.ts.map