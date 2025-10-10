import { CallToolRequest, CallToolResult } from '@modelcontextprotocol/sdk/types.js';
import { WordPressClient } from './wordpress-client.js';
export declare class ToolHandlers {
    private wpClient;
    constructor(wpClient: WordPressClient);
    handleToolCall(request: CallToolRequest): Promise<CallToolResult>;
    private handleListForms;
    private handleGetForm;
    private handleCreateForm;
    private handleUpdateForm;
    private handleDeleteForm;
    private handleGenerateAIForm;
    private handlePreviewForm;
    private handleCreateFormFromPreview;
    private handleListEntries;
    private handleGetEntry;
    private handleUpdateEntryStatus;
    private handleDeleteEntry;
    private handleGetEntriesChartData;
    private handleGetFormAnalytics;
    private handleGetSiteAnalytics;
}
//# sourceMappingURL=handlers.d.ts.map