import { WordPressConfig, SureForm, FormEntry, SearchFilters, PaginationOptions } from './types.js';
export declare class WordPressClient {
    private client;
    constructor(config: WordPressConfig);
    /**
     * Test the connection to WordPress
     */
    testConnection(): Promise<boolean>;
    /**
     * Get all SureForms
     */
    getForms(): Promise<SureForm[]>;
    /**
     * Get a specific form by ID
     */
    getForm(formId: number): Promise<SureForm | null>;
    /**
     * Create a new form
     */
    createForm(title: string, content?: string, meta?: Record<string, any>): Promise<SureForm>;
    /**
     * Update an existing form
     */
    updateForm(formId: number, updates: Partial<{
        title: string;
        content: string;
        meta: Record<string, any>;
    }>): Promise<SureForm>;
    /**
     * Delete a form
     */
    deleteForm(formId: number, force?: boolean): Promise<boolean>;
    /**
     * Get entries with filtering and pagination
     * Note: This would require custom endpoint implementation in SureForms
     */
    getEntries(filters?: SearchFilters, pagination?: PaginationOptions): Promise<{
        entries: FormEntry[];
        total: number;
        pages: number;
    }>;
    /**
     * Get chart data for entries
     */
    getEntriesChartData(params: {
        after: string;
        before: string;
        form?: string;
    }): Promise<any>;
    /**
     * Get a specific entry by ID
     * Note: This would require custom endpoint implementation in SureForms
     */
    getEntry(entryId: number): Promise<FormEntry | null>;
    /**
     * Update entry status
     * Note: This would require custom endpoint implementation in SureForms
     */
    updateEntryStatus(entryId: number, status: 'read' | 'unread' | 'trash'): Promise<boolean>;
    /**
     * Delete an entry
     * Note: This would require custom endpoint implementation in SureForms
     */
    deleteEntry(entryId: number, permanent?: boolean): Promise<boolean>;
    /**
     * Generate AI form using SureForms AI endpoint
     */
    generateAIForm(prompt: string, useSystemMessage?: boolean): Promise<any>;
    /**
     * Preview how a form will be created from a prompt before actually creating it
     * Shows the block structure, field types, labels, required fields, and values
     */
    previewFormFromPrompt(prompt: string, useSystemMessage?: boolean): Promise<{
        formTitle: string;
        blocks: Array<{
            blockType: string;
            attributes: Record<string, any>;
            preview: {
                label: string;
                fieldType: string;
                required: boolean;
                helpText?: string;
                options?: Array<{
                    label: string;
                    value: string;
                }>;
                validation?: Record<string, any>;
            };
        }>;
        gutenbergContent: string;
        summary: {
            totalFields: number;
            requiredFields: number;
            fieldTypes: Record<string, number>;
            hasProFields: boolean;
        };
    }>;
    /**
     * Create a form using previewed data
     */
    createFormFromPreview(preview: {
        formTitle: string;
        gutenbergContent: string;
    }): Promise<SureForm>;
}
//# sourceMappingURL=wordpress-client.d.ts.map