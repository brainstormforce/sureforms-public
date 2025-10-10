import axios from 'axios';
export class WordPressClient {
    constructor(config) {
        const auth = {};
        // Set up authentication
        if (config.username && config.applicationPassword) {
            auth.username = config.username;
            auth.password = config.applicationPassword;
        }
        else if (config.consumerKey && config.consumerSecret) {
            auth.username = config.consumerKey;
            auth.password = config.consumerSecret;
        }
        this.client = axios.create({
            baseURL: config.url.replace(/\/$/, ''),
            auth,
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'SureForms-MCP/1.0.0'
            },
            timeout: 30000
        });
    }
    /**
     * Test the connection to WordPress
     */
    async testConnection() {
        try {
            const response = await this.client.get('/wp-json/wp/v2/users/me');
            return response.status === 200;
        }
        catch (error) {
            console.error('Connection test failed:', error);
            return false;
        }
    }
    /**
     * Get all SureForms
     */
    async getForms() {
        try {
            // Use WordPress REST API instead of custom endpoint to avoid nonce issues
            const response = await this.client.get('/wp-json/wp/v2/sureforms_form');
            // Transform WordPress post format to SureForm interface
            const forms = Array.isArray(response.data) ? response.data.map((form) => ({
                ID: form.id,
                post_title: form.title?.rendered || form.title,
                post_name: form.slug || '',
                post_status: form.status,
                post_date: form.date,
                post_modified: form.modified,
                post_content: form.content?.rendered || form.content || '',
                meta: form.meta || {}
            })) : [];
            return forms;
        }
        catch (error) {
            console.error('Error fetching forms:', error);
            throw new Error(`Failed to fetch forms: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Get a specific form by ID
     */
    async getForm(formId) {
        try {
            const response = await this.client.get(`/wp-json/wp/v2/sureforms_form/${formId}`);
            return response.data || null;
        }
        catch (error) {
            if (axios.isAxiosError(error) && error.response?.status === 404) {
                return null;
            }
            throw new Error(`Failed to fetch form ${formId}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Create a new form
     */
    async createForm(title, content = '', meta = {}) {
        try {
            const response = await this.client.post('/wp-json/wp/v2/sureforms_form', {
                title,
                content,
                status: 'publish',
                meta
            });
            return response.data;
        }
        catch (error) {
            throw new Error(`Failed to create form: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Update an existing form
     */
    async updateForm(formId, updates) {
        try {
            const response = await this.client.post(`/wp-json/wp/v2/sureforms_form/${formId}`, updates);
            return response.data;
        }
        catch (error) {
            throw new Error(`Failed to update form ${formId}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Delete a form
     */
    async deleteForm(formId, force = false) {
        try {
            const response = await this.client.delete(`/wp-json/wp/v2/sureforms_form/${formId}`, {
                params: { force }
            });
            return response.status === 200;
        }
        catch (error) {
            throw new Error(`Failed to delete form ${formId}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Get entries with filtering and pagination
     * Note: This would require custom endpoint implementation in SureForms
     */
    async getEntries(filters = {}, pagination = {}) {
        try {
            const params = {
                ...filters,
                ...pagination,
                per_page: pagination.limit || 10,
                page: pagination.offset ? Math.floor(pagination.offset / (pagination.limit || 10)) + 1 : 1
            };
            // This would need a custom endpoint in SureForms REST API
            const response = await this.client.get('/wp-json/sureforms/v1/entries', { params });
            return {
                entries: response.data || [],
                total: parseInt(response.headers['x-wp-total'] || '0'),
                pages: parseInt(response.headers['x-wp-totalpages'] || '1')
            };
        }
        catch (error) {
            throw new Error(`Failed to fetch entries: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Get chart data for entries
     */
    async getEntriesChartData(params) {
        try {
            const response = await this.client.get('/wp-json/sureforms/v1/entries-chart-data', { params });
            return response.data;
        }
        catch (error) {
            throw new Error(`Failed to fetch chart data: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Get a specific entry by ID
     * Note: This would require custom endpoint implementation in SureForms
     */
    async getEntry(entryId) {
        try {
            const response = await this.client.get(`/wp-json/sureforms/v1/entries/${entryId}`);
            return response.data || null;
        }
        catch (error) {
            if (axios.isAxiosError(error) && error.response?.status === 404) {
                return null;
            }
            throw new Error(`Failed to fetch entry ${entryId}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Update entry status
     * Note: This would require custom endpoint implementation in SureForms
     */
    async updateEntryStatus(entryId, status) {
        try {
            const response = await this.client.patch(`/wp-json/sureforms/v1/entries/${entryId}`, { status });
            return response.status === 200;
        }
        catch (error) {
            throw new Error(`Failed to update entry ${entryId}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Delete an entry
     * Note: This would require custom endpoint implementation in SureForms
     */
    async deleteEntry(entryId, permanent = false) {
        try {
            if (permanent) {
                const response = await this.client.delete(`/wp-json/sureforms/v1/entries/${entryId}`, {
                    params: { force: true }
                });
                return response.status === 200;
            }
            else {
                return await this.updateEntryStatus(entryId, 'trash');
            }
        }
        catch (error) {
            throw new Error(`Failed to delete entry ${entryId}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Generate AI form using SureForms AI endpoint
     */
    async generateAIForm(prompt, useSystemMessage = false) {
        try {
            const response = await this.client.post('/wp-json/sureforms/v1/generate-form', {
                prompt,
                use_system_message: useSystemMessage
            });
            return response.data;
        }
        catch (error) {
            throw new Error(`Failed to generate AI form: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Preview how a form will be created from a prompt before actually creating it
     * Shows the block structure, field types, labels, required fields, and values
     */
    async previewFormFromPrompt(prompt, useSystemMessage = false) {
        try {
            // First, generate the AI form data
            const aiResponse = await this.generateAIForm(prompt, useSystemMessage);
            if (!aiResponse.form || !aiResponse.form.formFields) {
                throw new Error('Invalid AI response: missing form or formFields');
            }
            const formData = aiResponse.form;
            const formTitle = formData.formTitle || 'Untitled Form';
            const formFields = formData.formFields;
            // Transform AI response into preview format
            const blocks = [];
            let gutenbergContent = '';
            const fieldTypeCounts = {};
            let requiredFieldsCount = 0;
            let hasProFields = false;
            // Pro field types that require SureForms Pro
            const proFieldTypes = ['slider', 'page-break', 'date-picker', 'time-picker', 'upload', 'hidden', 'rating', 'signature'];
            for (const field of formFields) {
                const fieldType = field.fieldType;
                const isRequired = field.required || false;
                const label = field.label || '';
                const helpText = field.helpText || '';
                // Check if this is a pro field
                if (proFieldTypes.includes(fieldType)) {
                    hasProFields = true;
                }
                // Count field types
                fieldTypeCounts[fieldType] = (fieldTypeCounts[fieldType] || 0) + 1;
                if (isRequired) {
                    requiredFieldsCount++;
                }
                // Generate block attributes exactly like SureForms field mapping
                const blockId = Array.from(crypto.getRandomValues(new Uint8Array(4)))
                    .map(b => b.toString(16).padStart(2, '0'))
                    .join('');
                const attributes = {
                    block_id: blockId,
                    formId: 0,
                    label: label,
                    required: isRequired,
                    help: helpText,
                    slug: field.slug || ''
                };
                // Add field-specific attributes exactly like SureForms field mapping
                if (fieldType === 'dropdown' && field.fieldOptions && Array.isArray(field.fieldOptions) &&
                    field.fieldOptions[0]?.label) {
                    attributes.options = field.fieldOptions;
                    if (field.showValues !== undefined) {
                        attributes.showValues = field.showValues;
                    }
                    // Remove icon from options for the dropdown field
                    attributes.options = attributes.options.map((option) => ({
                        ...option,
                        icon: ''
                    }));
                }
                if (fieldType === 'multi-choice' && field.fieldOptions) {
                    // Remove duplicate icons and clear icons if all are the same
                    const icons = field.fieldOptions.map((opt) => opt.icon);
                    const options = field.fieldOptions.map((opt) => opt.optionTitle);
                    const uniqueIcons = [...new Set(icons)];
                    let processedOptions = [...field.fieldOptions];
                    if (uniqueIcons.length === 1 || options.length !== icons.length) {
                        processedOptions = processedOptions.map(option => ({ ...option, icon: '' }));
                    }
                    // Set options if they are valid
                    if (field.fieldOptions[0]?.optionTitle) {
                        attributes.options = processedOptions;
                    }
                    // Determine vertical layout based on icons
                    if (attributes.options) {
                        attributes.verticalLayout = attributes.options.every((option) => option.icon && option.icon !== '');
                    }
                    if (field.showValues !== undefined) {
                        attributes.showValues = field.showValues;
                    }
                    if (field.singleSelection !== undefined) {
                        attributes.singleSelection = field.singleSelection;
                    }
                    // Set choiceWidth for options divisible by 3
                    if (attributes.options && attributes.options.length % 3 === 0) {
                        attributes.choiceWidth = 33.33;
                    }
                }
                if (fieldType === 'phone') {
                    attributes.autoCountry = true;
                }
                // Add pro field attributes
                if (fieldType === 'slider') {
                    attributes.min = field.min || 0;
                    attributes.max = field.max || 100;
                    attributes.step = field.step || 1;
                    attributes.prefixTooltip = field.prefixTooltip || '';
                    attributes.suffixTooltip = field.suffixTooltip || '';
                }
                if (fieldType === 'date-picker') {
                    attributes.dateFormat = field.dateFormat || 'mm/dd/yy';
                    attributes.min = field.minDate || '';
                    attributes.max = field.maxDate || '';
                }
                if (fieldType === 'time-picker') {
                    attributes.increment = field.increment || 30;
                    attributes.showTwelveHourFormat = field.useTwelveHourFormat || false;
                    attributes.min = field.minTime || '';
                    attributes.max = field.maxTime || '';
                }
                if (fieldType === 'rating') {
                    attributes.iconShape = field.iconShape || 'star';
                    attributes.showText = field.showTooltip || false;
                    attributes.defaultRating = field.defaultRating || 0;
                }
                if (fieldType === 'upload') {
                    attributes.fileSizeLimit = field.uploadSize || 10;
                    attributes.multiple = field.multiUpload || false;
                    attributes.maxFiles = field.multiFilesNumber || 2;
                    if (field.allowedTypes) {
                        const types = field.allowedTypes.replace(/\./g, '').split(',');
                        attributes.allowedFormats = types.map((type) => ({
                            value: type.trim(),
                            label: type.trim()
                        }));
                    }
                }
                // Create preview object
                const preview = {
                    label: label,
                    fieldType: fieldType,
                    required: isRequired,
                    helpText: helpText || undefined,
                    options: field.fieldOptions || undefined,
                    validation: {}
                };
                // Add validation info to preview
                if (fieldType === 'email') {
                    preview.validation.type = 'email';
                }
                if (fieldType === 'number') {
                    preview.validation.type = 'number';
                }
                if (fieldType === 'url') {
                    preview.validation.type = 'url';
                }
                blocks.push({
                    blockType: `srfm/${fieldType}`,
                    attributes,
                    preview
                });
                // Generate Gutenberg block content exactly like SureForms
                // Using JSON format that matches wp_json_encode with JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                const attributesJson = JSON.stringify(attributes, null, 0)
                    .replace(/\\\//g, '/') // Unescape slashes like JSON_UNESCAPED_SLASHES
                    .replace(/"/g, '&quot;'); // HTML encode quotes for block comment
                gutenbergContent += `<!-- wp:srfm/${fieldType} ${attributesJson} /-->\n`;
            }
            return {
                formTitle,
                blocks,
                gutenbergContent,
                summary: {
                    totalFields: formFields.length,
                    requiredFields: requiredFieldsCount,
                    fieldTypes: fieldTypeCounts,
                    hasProFields
                }
            };
        }
        catch (error) {
            throw new Error(`Failed to preview form: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
    /**
     * Create a form using previewed data
     */
    async createFormFromPreview(preview) {
        try {
            return await this.createForm(preview.formTitle, preview.gutenbergContent);
        }
        catch (error) {
            throw new Error(`Failed to create form from preview: ${error instanceof Error ? error.message : 'Unknown error'}`);
        }
    }
}
//# sourceMappingURL=wordpress-client.js.map