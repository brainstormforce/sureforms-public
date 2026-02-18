# SureForms Wiki

Welcome to the SureForms developer wiki. This documentation covers the architecture, APIs, development workflows, and internals of the SureForms WordPress form builder plugin.

**Version:** 2.5.1 | **WordPress:** 6.4+ | **PHP:** 7.4+ | **Node:** 18.15.0

## Quick Links

- [Getting Started](Getting-Started) -- Set up your development environment
- [Architecture Overview](Architecture-Overview) -- Understand the codebase structure
- [Contributing Guide](Contributing-Guide) -- How to contribute

## Architecture & Core

| Page | Description |
|------|-------------|
| [Architecture Overview](Architecture-Overview) | Plugin bootstrap, namespaces, directory layout, design patterns |
| [Database Schema](Database-Schema) | Custom tables (`wp_srfm_entries`, `wp_srfm_payments`), post meta keys |
| [Environment Configuration](Environment-Configuration) | Constants, build tools, linting, development setup |
| [Form Submission Flow](Form-Submission-Flow) | End-to-end submission pipeline from frontend to storage |

## WordPress / Backend

| Page | Description |
|------|-------------|
| [Gutenberg Blocks](Gutenberg-Blocks) | Block types, registration, `block.json` structure |
| [Form Fields Architecture](Form-Fields-Architecture) | Field type hierarchy, validation, rendering pipeline |
| [WordPress Hooks Reference](WordPress-Hooks-Reference) | Custom actions, filters, and execution order |
| [Payment Integration](Payment-Integration) | Stripe setup, payment flow, webhooks, refunds |
| [Email Notifications](Email-Notifications) | Email templates, smart tags, notification settings |
| [AI Form Builder](AI-Form-Builder) | AI-powered form generation process |
| [Data Export & Import](Data-Export-Import) | Form/entry export, import, duplication |

## Frontend / React

| Page | Description |
|------|-------------|
| [Admin Dashboard](Admin-Dashboard) | React SPA architecture, routing, data fetching |
| [State Management](State-Management) | WordPress data store, actions, selectors |
| [Frontend Assets](Frontend-Assets) | Asset loading, build pipeline, SASS, Tailwind |
| [Block Editor Controls](Block-Editor-Controls) | Custom inspector controls, CSS generation, icons |

## API

| Page | Description |
|------|-------------|
| [REST API Reference](REST-API-Reference) | All endpoints with methods, parameters, authentication |

## Development Workflow

| Page | Description |
|------|-------------|
| [Getting Started](Getting-Started) | Prerequisites, installation, first build |
| [Contributing Guide](Contributing-Guide) | Git workflow, coding standards, PR process |
| [Testing Guide](Testing-Guide) | PHPUnit, Playwright, Jest setup and execution |
| [Deployment Guide](Deployment-Guide) | CI/CD pipelines, release process, version bumping |
| [Troubleshooting & FAQ](Troubleshooting-FAQ) | Common issues and debugging tips |
| [Changelog](Changelog) | Recent version history |
