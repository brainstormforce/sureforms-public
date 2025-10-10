export class ToolHandlers {
    constructor(wpClient) {
        this.wpClient = wpClient;
    }
    async handleToolCall(request) {
        try {
            const { name, arguments: args } = request.params;
            let result;
            switch (name) {
                // Form Management Tools
                case 'sf_list_forms':
                    result = await this.handleListForms();
                    break;
                case 'sf_get_form':
                    result = await this.handleGetForm(args);
                    break;
                case 'sf_create_form':
                    result = await this.handleCreateForm(args);
                    break;
                case 'sf_update_form':
                    result = await this.handleUpdateForm(args);
                    break;
                case 'sf_delete_form':
                    result = await this.handleDeleteForm(args);
                    break;
                case 'sf_generate_ai_form':
                    result = await this.handleGenerateAIForm();
                    break;
                case 'sf_preview_form':
                    result = await this.handlePreviewForm(args);
                    break;
                case 'sf_create_form_from_preview':
                    result = await this.handleCreateFormFromPreview(args);
                    break;
                // Entry Management Tools
                case 'sf_list_entries':
                    result = await this.handleListEntries(args);
                    break;
                case 'sf_get_entry':
                    result = await this.handleGetEntry(args);
                    break;
                case 'sf_update_entry_status':
                    result = await this.handleUpdateEntryStatus(args);
                    break;
                case 'sf_delete_entry':
                    result = await this.handleDeleteEntry(args);
                    break;
                case 'sf_get_entries_chart_data':
                    result = await this.handleGetEntriesChartData(args);
                    break;
                // Analytics Tools
                case 'sf_get_form_analytics':
                    result = await this.handleGetFormAnalytics(args);
                    break;
                case 'sf_get_site_analytics':
                    result = await this.handleGetSiteAnalytics(args);
                    break;
                default:
                    result = {
                        success: false,
                        error: `Unknown tool: ${name}`
                    };
            }
            return {
                content: [{
                        type: 'text',
                        text: JSON.stringify(result, null, 2)
                    }],
                isError: false
            };
        }
        catch (error) {
            return {
                content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: false,
                            error: error instanceof Error ? error.message : 'Unknown error'
                        }, null, 2)
                    }],
                isError: true
            };
        }
    }
    // Form Management Handlers
    async handleListForms() {
        try {
            const forms = await this.wpClient.getForms();
            return {
                success: true,
                data: forms,
                message: `Found ${forms.length} forms`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch forms'
            };
        }
    }
    async handleGetForm(args) {
        try {
            const { form_id } = args;
            if (!form_id) {
                return { success: false, error: 'form_id is required' };
            }
            const form = await this.wpClient.getForm(form_id);
            if (!form) {
                return { success: false, error: `Form with ID ${form_id} not found` };
            }
            return {
                success: true,
                data: form,
                message: `Retrieved form: ${form.post_title}`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch form'
            };
        }
    }
    async handleCreateForm(args) {
        try {
            const { title, content = '', meta = {} } = args;
            if (!title) {
                return { success: false, error: 'title is required' };
            }
            // Warn if content is empty - this suggests they should use preview workflow
            if (!content || content.trim() === '') {
                return {
                    success: false,
                    error: 'Form content is empty. For AI-generated forms, use sf_preview_form first to generate the form structure, then use sf_create_form_from_preview to create the actual form.'
                };
            }
            // Validate that content contains valid SureForms blocks
            if (!content.includes('wp:srfm/')) {
                return {
                    success: false,
                    error: 'Form content must contain valid SureForms blocks (wp:srfm/). Use sf_preview_form to generate proper SureForms block content.'
                };
            }
            const form = await this.wpClient.createForm(title, content, meta);
            return {
                success: true,
                data: form,
                message: `Created form: ${form.post_title} (ID: ${form.ID})`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to create form'
            };
        }
    }
    async handleUpdateForm(args) {
        try {
            const { form_id, ...updates } = args;
            if (!form_id) {
                return { success: false, error: 'form_id is required' };
            }
            const form = await this.wpClient.updateForm(form_id, updates);
            return {
                success: true,
                data: form,
                message: `Updated form: ${form.post_title} (ID: ${form.ID})`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to update form'
            };
        }
    }
    async handleDeleteForm(args) {
        try {
            const { form_id, permanent = false } = args;
            if (!form_id) {
                return { success: false, error: 'form_id is required' };
            }
            const success = await this.wpClient.deleteForm(form_id, permanent);
            if (success) {
                return {
                    success: true,
                    message: `Form ${form_id} ${permanent ? 'permanently deleted' : 'moved to trash'}`
                };
            }
            else {
                return {
                    success: false,
                    error: `Failed to delete form ${form_id}`
                };
            }
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to delete form'
            };
        }
    }
    async handleGenerateAIForm() {
        return {
            success: false,
            error: 'DEPRECATED: Direct AI form generation is not allowed. Use sf_preview_form first to show the user what will be created, then use sf_create_form_from_preview to create the actual form after user confirmation.'
        };
    }
    async handlePreviewForm(args) {
        try {
            const { include_pro = false } = args;
            // Define all available SureForms field types
            const freeFields = [
                {
                    type: 'input',
                    label: 'Text Input',
                    blockType: 'wp:srfm/input',
                    required: 'Optional',
                    description: 'Single line text field for short text input',
                    properties: ['label', 'required', 'help', 'slug', 'placeholder']
                },
                {
                    type: 'email',
                    label: 'Email Field',
                    blockType: 'wp:srfm/email',
                    required: 'Optional',
                    description: 'Email input with validation',
                    properties: ['label', 'required', 'help', 'slug', 'placeholder']
                },
                {
                    type: 'number',
                    label: 'Number Field',
                    blockType: 'wp:srfm/number',
                    required: 'Optional',
                    description: 'Numeric input field',
                    properties: ['label', 'required', 'help', 'slug', 'placeholder', 'min', 'max']
                },
                {
                    type: 'textarea',
                    label: 'Textarea',
                    blockType: 'wp:srfm/textarea',
                    required: 'Optional',
                    description: 'Multi-line text field for longer text input',
                    properties: ['label', 'required', 'help', 'slug', 'placeholder', 'rows']
                },
                {
                    type: 'dropdown',
                    label: 'Dropdown Select',
                    blockType: 'wp:srfm/dropdown',
                    required: 'Optional',
                    description: 'Dropdown selection with multiple options',
                    properties: ['label', 'required', 'help', 'slug', 'options', 'showValues'],
                    options: 'Array of objects with label and value properties'
                },
                {
                    type: 'checkbox',
                    label: 'Checkbox',
                    blockType: 'wp:srfm/checkbox',
                    required: 'Optional',
                    description: 'Single checkbox for yes/no or agreement',
                    properties: ['label', 'required', 'help', 'slug']
                },
                {
                    type: 'multi-choice',
                    label: 'Multiple Choice',
                    blockType: 'wp:srfm/multi-choice',
                    required: 'Optional',
                    description: 'Radio buttons or checkboxes for multiple options',
                    properties: ['label', 'required', 'help', 'slug', 'options', 'singleSelection', 'verticalLayout', 'choiceWidth'],
                    options: 'Array of objects with optionTitle and optional icon properties'
                },
                {
                    type: 'url',
                    label: 'URL Field',
                    blockType: 'wp:srfm/url',
                    required: 'Optional',
                    description: 'URL input with validation',
                    properties: ['label', 'required', 'help', 'slug', 'placeholder']
                },
                {
                    type: 'phone',
                    label: 'Phone Number',
                    blockType: 'wp:srfm/phone',
                    required: 'Optional',
                    description: 'Phone number input with country code',
                    properties: ['label', 'required', 'help', 'slug', 'autoCountry']
                },
                {
                    type: 'address',
                    label: 'Address Field',
                    blockType: 'wp:srfm/address',
                    required: 'Optional',
                    description: 'Address input field',
                    properties: ['label', 'required', 'help', 'slug']
                },
                {
                    type: 'gdpr',
                    label: 'GDPR Compliance',
                    blockType: 'wp:srfm/gdpr',
                    required: 'Optional',
                    description: 'GDPR compliance checkbox',
                    properties: ['label', 'required', 'help', 'slug']
                },
                {
                    type: 'inline-button',
                    label: 'Submit Button',
                    blockType: 'wp:srfm/inline-button',
                    required: 'N/A',
                    description: 'Form submission button',
                    properties: ['label', 'buttonText', 'buttonStyle']
                }
            ];
            const proFields = [
                {
                    type: 'slider',
                    label: 'Range Slider',
                    blockType: 'wp:srfm/slider',
                    required: 'Optional',
                    description: 'Numeric range slider input',
                    properties: ['label', 'required', 'help', 'slug', 'min', 'max', 'step', 'prefixTooltip', 'suffixTooltip', 'numberDefaultValue'],
                    proOnly: true
                },
                {
                    type: 'date-picker',
                    label: 'Date Picker',
                    blockType: 'wp:srfm/date-picker',
                    required: 'Optional',
                    description: 'Date selection field',
                    properties: ['label', 'required', 'help', 'slug', 'dateFormat', 'min', 'max'],
                    proOnly: true
                },
                {
                    type: 'time-picker',
                    label: 'Time Picker',
                    blockType: 'wp:srfm/time-picker',
                    required: 'Optional',
                    description: 'Time selection field',
                    properties: ['label', 'required', 'help', 'slug', 'increment', 'showTwelveHourFormat', 'min', 'max'],
                    proOnly: true
                },
                {
                    type: 'upload',
                    label: 'File Upload',
                    blockType: 'wp:srfm/upload',
                    required: 'Optional',
                    description: 'File upload field with format restrictions',
                    properties: ['label', 'required', 'help', 'slug', 'allowedFormats', 'fileSizeLimit', 'multiple', 'maxFiles'],
                    proOnly: true
                },
                {
                    type: 'rating',
                    label: 'Rating Field',
                    blockType: 'wp:srfm/rating',
                    required: 'Optional',
                    description: 'Star or custom icon rating field',
                    properties: ['label', 'required', 'help', 'slug', 'iconShape', 'showText', 'defaultRating', 'ratingText'],
                    proOnly: true
                },
                {
                    type: 'signature',
                    label: 'Signature Field',
                    blockType: 'wp:srfm/signature',
                    required: 'Optional',
                    description: 'Digital signature capture (not available in SureForms Starter)',
                    properties: ['label', 'required', 'help', 'slug'],
                    proOnly: true,
                    note: 'Not available in SureForms Starter package'
                },
                {
                    type: 'hidden',
                    label: 'Hidden Field',
                    blockType: 'wp:srfm/hidden',
                    required: 'N/A',
                    description: 'Hidden field for storing data',
                    properties: ['slug', 'value'],
                    proOnly: true
                },
                {
                    type: 'page-break',
                    label: 'Page Break',
                    blockType: 'wp:srfm/page-break',
                    required: 'N/A',
                    description: 'Multi-step form page separator',
                    properties: ['label'],
                    proOnly: true
                }
            ];
            const availableFields = include_pro ? [...freeFields, ...proFields] : freeFields;
            return {
                success: true,
                data: {
                    fieldTypes: availableFields,
                    summary: {
                        totalFields: availableFields.length,
                        freeFields: freeFields.length,
                        proFields: proFields.length,
                        includesPro: include_pro
                    },
                    commonProperties: {
                        required: 'All form fields (except buttons and utility fields) can be marked as required or optional',
                        blockId: 'Each field gets a unique block_id generated automatically',
                        formId: 'Each field is associated with the parent form ID',
                        slug: 'Used for field identification and data storage'
                    },
                    instructions: {
                        description: 'This preview shows all available SureForms field types and their properties. Each field type has specific attributes that control its behavior and appearance.',
                        usage: 'Use these field types when creating forms manually or as reference for understanding SureForms capabilities.',
                        note: include_pro ? 'Includes both free and pro field types. Pro fields require SureForms Pro plugin.' : 'Showing only free field types. Use include_pro: true to see pro fields.'
                    }
                },
                message: `Preview of ${availableFields.length} available SureForms field types (${freeFields.length} free, ${include_pro ? proFields.length + ' pro' : 'pro fields excluded'}). Each field shows its block type, properties, and usage.`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to generate field preview'
            };
        }
    }
    async handleCreateFormFromPreview(args) {
        try {
            const { form_title, gutenberg_content } = args;
            if (!form_title || !gutenberg_content) {
                return { success: false, error: 'form_title and gutenberg_content are required' };
            }
            const form = await this.wpClient.createFormFromPreview({
                formTitle: form_title,
                gutenbergContent: gutenberg_content
            });
            return {
                success: true,
                data: form,
                message: `Successfully created form: "${form.post_title}" (ID: ${form.ID}) from preview`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to create form from preview'
            };
        }
    }
    // Entry Management Handlers
    async handleListEntries(args) {
        try {
            const filters = {
                form_id: args.form_id,
                status: args.status,
                user_id: args.user_id,
                date_from: args.date_from,
                date_to: args.date_to,
                search_term: args.search_term
            };
            const pagination = {
                limit: args.limit || 10,
                offset: args.offset || 0,
                orderby: args.orderby || 'created_at',
                order: args.order || 'DESC'
            };
            const result = await this.wpClient.getEntries(filters, pagination);
            return {
                success: true,
                data: result,
                message: `Found ${result.total} entries (showing ${result.entries.length})`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch entries'
            };
        }
    }
    async handleGetEntry(args) {
        try {
            const { entry_id } = args;
            if (!entry_id) {
                return { success: false, error: 'entry_id is required' };
            }
            const entry = await this.wpClient.getEntry(entry_id);
            if (!entry) {
                return { success: false, error: `Entry with ID ${entry_id} not found` };
            }
            return {
                success: true,
                data: entry,
                message: `Retrieved entry ID ${entry_id}`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch entry'
            };
        }
    }
    async handleUpdateEntryStatus(args) {
        try {
            const { entry_id, status } = args;
            if (!entry_id || !status) {
                return { success: false, error: 'entry_id and status are required' };
            }
            const success = await this.wpClient.updateEntryStatus(entry_id, status);
            if (success) {
                return {
                    success: true,
                    message: `Entry ${entry_id} status updated to: ${status}`
                };
            }
            else {
                return {
                    success: false,
                    error: `Failed to update entry ${entry_id} status`
                };
            }
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to update entry status'
            };
        }
    }
    async handleDeleteEntry(args) {
        try {
            const { entry_id, permanent = false } = args;
            if (!entry_id) {
                return { success: false, error: 'entry_id is required' };
            }
            const success = await this.wpClient.deleteEntry(entry_id, permanent);
            if (success) {
                return {
                    success: true,
                    message: `Entry ${entry_id} ${permanent ? 'permanently deleted' : 'moved to trash'}`
                };
            }
            else {
                return {
                    success: false,
                    error: `Failed to delete entry ${entry_id}`
                };
            }
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to delete entry'
            };
        }
    }
    async handleGetEntriesChartData(args) {
        try {
            const { after, before, form_id } = args;
            if (!after || !before) {
                return { success: false, error: 'after and before dates are required' };
            }
            const chartData = await this.wpClient.getEntriesChartData({
                after,
                before,
                form: form_id
            });
            return {
                success: true,
                data: chartData,
                message: `Retrieved chart data from ${after} to ${before}`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch chart data'
            };
        }
    }
    // Analytics Handlers
    async handleGetFormAnalytics(args) {
        try {
            const { form_id, period = '30days' } = args;
            if (!form_id) {
                return { success: false, error: 'form_id is required' };
            }
            // Calculate date range based on period
            const endDate = new Date();
            const startDate = new Date();
            switch (period) {
                case '7days':
                    startDate.setDate(endDate.getDate() - 7);
                    break;
                case '90days':
                    startDate.setDate(endDate.getDate() - 90);
                    break;
                case '1year':
                    startDate.setFullYear(endDate.getFullYear() - 1);
                    break;
                default: // 30days
                    startDate.setDate(endDate.getDate() - 30);
            }
            const chartData = await this.wpClient.getEntriesChartData({
                after: startDate.toISOString().split('T')[0],
                before: endDate.toISOString().split('T')[0],
                form: form_id.toString()
            });
            return {
                success: true,
                data: {
                    form_id,
                    period,
                    entries: chartData,
                    total_entries: Array.isArray(chartData) ? chartData.length : 0
                },
                message: `Retrieved analytics for form ${form_id} (${period})`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch form analytics'
            };
        }
    }
    async handleGetSiteAnalytics(args) {
        try {
            const { period = '30days' } = args;
            // Calculate date range based on period
            const endDate = new Date();
            const startDate = new Date();
            switch (period) {
                case '7days':
                    startDate.setDate(endDate.getDate() - 7);
                    break;
                case '90days':
                    startDate.setDate(endDate.getDate() - 90);
                    break;
                case '1year':
                    startDate.setFullYear(endDate.getFullYear() - 1);
                    break;
                default: // 30days
                    startDate.setDate(endDate.getDate() - 30);
            }
            const [forms, chartData] = await Promise.all([
                this.wpClient.getForms(),
                this.wpClient.getEntriesChartData({
                    after: startDate.toISOString().split('T')[0],
                    before: endDate.toISOString().split('T')[0]
                })
            ]);
            return {
                success: true,
                data: {
                    period,
                    total_forms: forms.length,
                    total_entries: Array.isArray(chartData) ? chartData.length : 0,
                    forms,
                    entries: chartData
                },
                message: `Retrieved site analytics (${period})`
            };
        }
        catch (error) {
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Failed to fetch site analytics'
            };
        }
    }
}
//# sourceMappingURL=handlers.js.map