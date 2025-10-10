#!/usr/bin/env node
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { CallToolRequestSchema, ListToolsRequestSchema } from '@modelcontextprotocol/sdk/types.js';
import dotenv from 'dotenv';
import { WordPressClient } from './wordpress-client.js';
import { ToolHandlers } from './handlers.js';
import { ALL_TOOLS } from './tools.js';
// Load environment variables
dotenv.config();
class SureFormsMCPServer {
    constructor() {
        // Initialize WordPress client
        const config = {
            url: process.env.WORDPRESS_URL,
            username: process.env.WORDPRESS_USERNAME,
            applicationPassword: process.env.WORDPRESS_APPLICATION_PASSWORD,
            consumerKey: process.env.WORDPRESS_CONSUMER_KEY,
            consumerSecret: process.env.WORDPRESS_CONSUMER_SECRET
        };
        this.wpClient = new WordPressClient(config);
        this.toolHandlers = new ToolHandlers(this.wpClient);
        // Initialize MCP server
        this.server = new Server({
            name: 'sureforms-mcp',
            version: '1.0.0'
        });
        this.setupHandlers();
    }
    setupHandlers() {
        // List available tools
        this.server.setRequestHandler(ListToolsRequestSchema, async () => {
            return {
                tools: ALL_TOOLS
            };
        });
        // Handle tool calls
        this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
            return await this.toolHandlers.handleToolCall(request);
        });
    }
    async start() {
        // Test WordPress connection
        try {
            const connectionOk = await this.wpClient.testConnection();
            if (!connectionOk) {
                console.error('❌ Failed to connect to WordPress. Please check your configuration.');
                process.exit(1);
            }
            console.error('✅ Connected to WordPress successfully');
        }
        catch (error) {
            console.error('❌ Error connecting to WordPress:', error);
            process.exit(1);
        }
        // Start the server
        const transport = new StdioServerTransport();
        await this.server.connect(transport);
        console.error('🚀 SureForms MCP Server started successfully');
        console.error(`📋 Available tools: ${ALL_TOOLS.length}`);
        console.error(`🌐 WordPress URL: ${process.env.WORDPRESS_URL}`);
    }
}
// Validate required environment variables
function validateEnvironment() {
    const requiredVars = ['WORDPRESS_URL'];
    const missingVars = requiredVars.filter(varName => !process.env[varName]);
    if (missingVars.length > 0) {
        console.error('❌ Missing required environment variables:');
        missingVars.forEach(varName => {
            console.error(`   - ${varName}`);
        });
        console.error('Please check your .env file and ensure all required variables are set.');
        process.exit(1);
    }
    // Check for authentication credentials
    const hasBasicAuth = process.env.WORDPRESS_USERNAME && process.env.WORDPRESS_APPLICATION_PASSWORD;
    const hasOAuth = process.env.WORDPRESS_CONSUMER_KEY && process.env.WORDPRESS_CONSUMER_SECRET;
    if (!hasBasicAuth && !hasOAuth) {
        console.error('❌ No authentication credentials found.');
        console.error('Please provide either:');
        console.error('   - WORDPRESS_USERNAME and WORDPRESS_APPLICATION_PASSWORD');
        console.error('   - or WORDPRESS_CONSUMER_KEY and WORDPRESS_CONSUMER_SECRET');
        process.exit(1);
    }
}
// Error handling
process.on('uncaughtException', (error) => {
    console.error('❌ Uncaught Exception:', error);
    process.exit(1);
});
process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Unhandled Rejection at:', promise, 'reason:', reason);
    process.exit(1);
});
// Main execution
async function main() {
    try {
        validateEnvironment();
        const server = new SureFormsMCPServer();
        await server.start();
    }
    catch (error) {
        console.error('❌ Failed to start SureForms MCP Server:', error);
        process.exit(1);
    }
}
// Start the server
main();
//# sourceMappingURL=index.js.map