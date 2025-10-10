/**
 * MCP Tools definition for SureForms
 */
export const FORM_TOOLS = [
    {
        name: 'sf_list_forms',
        description: 'Get a list of all SureForms in the WordPress site',
        inputSchema: {
            type: 'object',
            properties: {
                include_meta: {
                    type: 'boolean',
                    description: 'Whether to include form metadata',
                    default: false
                }
            }
        }
    },
    {
        name: 'sf_get_form',
        description: 'Get detailed information about a specific form by ID',
        inputSchema: {
            type: 'object',
            properties: {
                form_id: {
                    type: 'number',
                    description: 'The ID of the form to retrieve'
                }
            },
            required: ['form_id']
        }
    },
    {
        name: 'sf_create_form',
        description: 'Create a new SureForm. IMPORTANT: This tool should only be used for manual form creation with pre-built content. For AI-generated forms, use sf_preview_form first, then sf_create_form_from_preview.',
        inputSchema: {
            type: 'object',
            properties: {
                title: {
                    type: 'string',
                    description: 'The title of the new form'
                },
                content: {
                    type: 'string',
                    description: 'The form content (Gutenberg blocks JSON) - must be valid SureForms blocks',
                    default: ''
                },
                meta: {
                    type: 'object',
                    description: 'Form metadata and settings',
                    default: {}
                }
            },
            required: ['title']
        }
    },
    {
        name: 'sf_update_form',
        description: 'Update an existing form',
        inputSchema: {
            type: 'object',
            properties: {
                form_id: {
                    type: 'number',
                    description: 'The ID of the form to update'
                },
                title: {
                    type: 'string',
                    description: 'New title for the form'
                },
                content: {
                    type: 'string',
                    description: 'New form content (Gutenberg blocks JSON)'
                },
                meta: {
                    type: 'object',
                    description: 'Form metadata and settings to update'
                }
            },
            required: ['form_id']
        }
    },
    {
        name: 'sf_delete_form',
        description: 'Delete a form (move to trash or permanently delete)',
        inputSchema: {
            type: 'object',
            properties: {
                form_id: {
                    type: 'number',
                    description: 'The ID of the form to delete'
                },
                permanent: {
                    type: 'boolean',
                    description: 'Whether to permanently delete (true) or move to trash (false)',
                    default: false
                }
            },
            required: ['form_id']
        }
    },
    {
        name: 'sf_generate_ai_form',
        description: 'DEPRECATED: Use sf_preview_form instead. This tool directly generates forms without preview and should be avoided. Always use sf_preview_form first to show the user what will be created.',
        inputSchema: {
            type: 'object',
            properties: {
                prompt: {
                    type: 'string',
                    description: 'Natural language description of the form to generate'
                },
                use_system_message: {
                    type: 'boolean',
                    description: 'Whether to use system message for AI generation',
                    default: false
                }
            },
            required: ['prompt']
        }
    },
    {
        name: 'sf_preview_form',
        description: 'Preview all available SureForms field types with their properties, labels, options, and requirements. Shows what fields are available in SureForms including free and pro versions.',
        inputSchema: {
            type: 'object',
            properties: {
                include_pro: {
                    type: 'boolean',
                    description: 'Whether to include SureForms Pro field types in the preview',
                    default: false
                }
            }
        }
    },
    {
        name: 'sf_create_form_from_preview',
        description: 'Create a form using previously generated preview data',
        inputSchema: {
            type: 'object',
            properties: {
                form_title: {
                    type: 'string',
                    description: 'The title for the new form'
                },
                gutenberg_content: {
                    type: 'string',
                    description: 'The Gutenberg block content from the preview'
                }
            },
            required: ['form_title', 'gutenberg_content']
        }
    }
];
export const ENTRY_TOOLS = [
    {
        name: 'sf_list_entries',
        description: 'Get a list of form entries with filtering and pagination',
        inputSchema: {
            type: 'object',
            properties: {
                form_id: {
                    type: 'number',
                    description: 'Filter entries by form ID'
                },
                status: {
                    type: 'string',
                    description: 'Filter by entry status',
                    enum: ['read', 'unread', 'trash', 'all']
                },
                user_id: {
                    type: 'number',
                    description: 'Filter entries by user ID'
                },
                date_from: {
                    type: 'string',
                    description: 'Filter entries from this date (YYYY-MM-DD format)'
                },
                date_to: {
                    type: 'string',
                    description: 'Filter entries to this date (YYYY-MM-DD format)'
                },
                search_term: {
                    type: 'string',
                    description: 'Search term to filter entries'
                },
                limit: {
                    type: 'number',
                    description: 'Maximum number of entries to return',
                    default: 10
                },
                offset: {
                    type: 'number',
                    description: 'Number of entries to skip',
                    default: 0
                },
                orderby: {
                    type: 'string',
                    description: 'Field to order by',
                    default: 'created_at'
                },
                order: {
                    type: 'string',
                    description: 'Order direction',
                    enum: ['ASC', 'DESC'],
                    default: 'DESC'
                }
            }
        }
    },
    {
        name: 'sf_get_entry',
        description: 'Get detailed information about a specific entry by ID',
        inputSchema: {
            type: 'object',
            properties: {
                entry_id: {
                    type: 'number',
                    description: 'The ID of the entry to retrieve'
                }
            },
            required: ['entry_id']
        }
    },
    {
        name: 'sf_update_entry_status',
        description: 'Update the status of an entry (read, unread, trash)',
        inputSchema: {
            type: 'object',
            properties: {
                entry_id: {
                    type: 'number',
                    description: 'The ID of the entry to update'
                },
                status: {
                    type: 'string',
                    description: 'New status for the entry',
                    enum: ['read', 'unread', 'trash']
                }
            },
            required: ['entry_id', 'status']
        }
    },
    {
        name: 'sf_delete_entry',
        description: 'Delete an entry (move to trash or permanently delete)',
        inputSchema: {
            type: 'object',
            properties: {
                entry_id: {
                    type: 'number',
                    description: 'The ID of the entry to delete'
                },
                permanent: {
                    type: 'boolean',
                    description: 'Whether to permanently delete (true) or move to trash (false)',
                    default: false
                }
            },
            required: ['entry_id']
        }
    },
    {
        name: 'sf_get_entries_chart_data',
        description: 'Get entries data for charts and analytics',
        inputSchema: {
            type: 'object',
            properties: {
                after: {
                    type: 'string',
                    description: 'Start date for chart data (YYYY-MM-DD format)'
                },
                before: {
                    type: 'string',
                    description: 'End date for chart data (YYYY-MM-DD format)'
                },
                form_id: {
                    type: 'string',
                    description: 'Optional form ID to filter chart data'
                }
            },
            required: ['after', 'before']
        }
    }
];
export const ANALYTICS_TOOLS = [
    {
        name: 'sf_get_form_analytics',
        description: 'Get analytics and statistics for a specific form',
        inputSchema: {
            type: 'object',
            properties: {
                form_id: {
                    type: 'number',
                    description: 'The ID of the form to get analytics for'
                },
                period: {
                    type: 'string',
                    description: 'Time period for analytics',
                    enum: ['7days', '30days', '90days', '1year'],
                    default: '30days'
                }
            },
            required: ['form_id']
        }
    },
    {
        name: 'sf_get_site_analytics',
        description: 'Get overall site analytics for all forms',
        inputSchema: {
            type: 'object',
            properties: {
                period: {
                    type: 'string',
                    description: 'Time period for analytics',
                    enum: ['7days', '30days', '90days', '1year'],
                    default: '30days'
                }
            }
        }
    }
];
export const ALL_TOOLS = [
    ...FORM_TOOLS,
    ...ENTRY_TOOLS,
    ...ANALYTICS_TOOLS
];
//# sourceMappingURL=tools.js.map