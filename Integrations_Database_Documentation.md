# Integrations Database Documentation & Operations Guide

## Table of Contents
1. [Table Overview](#table-overview)
2. [Column Specifications](#column-specifications)
3. [SQL Structure](#sql-structure)
4. [Database Operations - Complete CRUD](#database-operations---complete-crud)
5. [Column-Specific Operations](#column-specific-operations)
6. [Required Field Operations](#required-field-operations)
7. [Table Creation Reference](#table-creation-reference)
8. [Practical Examples](#practical-examples)

## Table Overview

**Table Name:** `wp_srfm_integrations` (with WordPress prefix)  
**Purpose:** Stores integration configurations for SureForms Pro native integrations (MailChimp, Brevo, etc.)  
**Source File:** `/inc/pro/database/tables/integrations.php`  
**Database Engine:** InnoDB (WordPress default)  
**Charset:** utf8mb4_unicode_ci (WordPress default)  
**Table Version:** 1  
**Last Modified:** Current development version

## Column Specifications

| Column Name | Data Type | Length/Size | Constraints | Default Value | Description |
|-------------|-----------|-------------|-------------|---------------|-------------|
| `id` | BIGINT | 20 UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | N/A | Unique integration identifier |
| `type` | VARCHAR | 100 | NOT NULL, UNIQUE | N/A | Integration type/slug (e.g., 'mailchimp', 'brevo') |
| `name` | VARCHAR | 100 | NULL | NULL | Human-readable integration display name |
| `data` | LONGTEXT | N/A | NULL | NULL | JSON configuration data (encrypted sensitive fields) |
| `status` | TINYINT | 1 | NULL | 0 | Integration status (1=enabled, 0=disabled) |
| `created_at` | TIMESTAMP | N/A | NOT NULL | CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | TIMESTAMP | N/A | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | Last modification timestamp |

**Required Fields:** `id` (auto-generated), `type`, `created_at`, `updated_at`  
**Optional Fields:** `name`, `data`, `status`

## SQL Structure

### Complete CREATE TABLE Statement

```sql
CREATE TABLE wp_srfm_integrations (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    name VARCHAR(100),
    data LONGTEXT,
    status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Indexes and Constraints

```sql
-- Primary Key
ALTER TABLE wp_srfm_integrations ADD PRIMARY KEY (id);

-- Unique Constraint on type
ALTER TABLE wp_srfm_integrations ADD UNIQUE KEY idx_type (type);

-- Index on status for filtering
ALTER TABLE wp_srfm_integrations ADD INDEX idx_status (status);

-- Auto increment
ALTER TABLE wp_srfm_integrations MODIFY id BIGINT(20) UNSIGNED AUTO_INCREMENT;
```

## Database Operations - Complete CRUD

### CREATE Operations

#### Insert New Integration (All Fields)
```sql
INSERT INTO wp_srfm_integrations (type, name, data, status, created_at, updated_at) 
VALUES (
    'mailchimp',
    'MailChimp Integration',
    '{"api_key":"encrypted_key","list_id":"abc123","double_optin":true}',
    1,
    NOW(),
    NOW()
);
```

#### Insert with Required Fields Only
```sql
INSERT INTO wp_srfm_integrations (type) 
VALUES ('brevo');
```

#### Bulk Insert Example
```sql
INSERT INTO wp_srfm_integrations (type, name, status) 
VALUES 
    ('mailchimp', 'MailChimp Integration', 1),
    ('brevo', 'Brevo Integration', 0),
    ('webhook', 'Custom Webhook', 1);
```

#### Validation Rules for INSERT
```sql
-- Ensure type is not empty
INSERT INTO wp_srfm_integrations (type, name) 
SELECT 'new_integration', 'New Integration'
WHERE LENGTH(TRIM('new_integration')) > 0;

-- Check for duplicate type before insert
INSERT INTO wp_srfm_integrations (type, name, status)
SELECT 'mailchimp', 'MailChimp', 1
WHERE NOT EXISTS (
    SELECT 1 FROM wp_srfm_integrations WHERE type = 'mailchimp'
);
```

### READ Operations

#### Select All Records
```sql
SELECT id, type, name, data, status, created_at, updated_at 
FROM wp_srfm_integrations 
ORDER BY created_at DESC;
```

#### Select by Primary Key
```sql
SELECT * FROM wp_srfm_integrations WHERE id = 1;
```

#### Select by Integration Type
```sql
SELECT * FROM wp_srfm_integrations WHERE type = 'mailchimp';
```

#### Select Enabled Integrations Only
```sql
SELECT * FROM wp_srfm_integrations 
WHERE status = 1 
ORDER BY name ASC;
```

#### Select with Pagination
```sql
-- Page 1 (10 records per page)
SELECT * FROM wp_srfm_integrations 
ORDER BY created_at DESC 
LIMIT 10 OFFSET 0;

-- Page 2
SELECT * FROM wp_srfm_integrations 
ORDER BY created_at DESC 
LIMIT 10 OFFSET 10;
```

#### Search and Filter Patterns
```sql
-- Search by name pattern
SELECT * FROM wp_srfm_integrations 
WHERE name LIKE '%mail%' 
OR type LIKE '%mail%';

-- Filter by date range
SELECT * FROM wp_srfm_integrations 
WHERE created_at BETWEEN '2024-01-01' AND '2024-12-31';

-- Complex filtering
SELECT * FROM wp_srfm_integrations 
WHERE status = 1 
AND type IN ('mailchimp', 'brevo') 
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

#### Count Operations
```sql
-- Count all integrations
SELECT COUNT(*) as total FROM wp_srfm_integrations;

-- Count by status
SELECT 
    status,
    COUNT(*) as count 
FROM wp_srfm_integrations 
GROUP BY status;

-- Count enabled integrations
SELECT COUNT(*) as enabled_count 
FROM wp_srfm_integrations 
WHERE status = 1;
```

### UPDATE Operations

#### Update Single Record by ID
```sql
UPDATE wp_srfm_integrations 
SET 
    name = 'Updated MailChimp Integration',
    status = 1,
    updated_at = NOW()
WHERE id = 1;
```

#### Update by Type
```sql
UPDATE wp_srfm_integrations 
SET 
    status = 1,
    updated_at = NOW()
WHERE type = 'mailchimp';
```

#### Partial Update (Specific Columns)
```sql
-- Update only status
UPDATE wp_srfm_integrations 
SET status = 0 
WHERE type = 'brevo';

-- Update only configuration data
UPDATE wp_srfm_integrations 
SET data = '{"api_key":"new_encrypted_key","list_id":"xyz789"}' 
WHERE id = 1;
```

#### Bulk Update Examples
```sql
-- Enable all integrations
UPDATE wp_srfm_integrations 
SET status = 1, updated_at = NOW();

-- Disable old integrations
UPDATE wp_srfm_integrations 
SET status = 0 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Update multiple specific types
UPDATE wp_srfm_integrations 
SET status = 1 
WHERE type IN ('mailchimp', 'brevo', 'webhook');
```

### DELETE Operations

#### Delete by Primary Key
```sql
DELETE FROM wp_srfm_integrations WHERE id = 1;
```

#### Delete by Type
```sql
DELETE FROM wp_srfm_integrations WHERE type = 'old_integration';
```

#### Delete with Conditions
```sql
-- Delete disabled integrations older than 1 year
DELETE FROM wp_srfm_integrations 
WHERE status = 0 
AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Delete specific integration types
DELETE FROM wp_srfm_integrations 
WHERE type IN ('deprecated_service', 'old_webhook');
```

#### Soft Delete Consideration
```sql
-- This table doesn't have soft delete, but you could add a deleted_at column
-- ALTER TABLE wp_srfm_integrations ADD COLUMN deleted_at TIMESTAMP NULL;

-- Then use UPDATE instead of DELETE
-- UPDATE wp_srfm_integrations SET deleted_at = NOW() WHERE id = 1;
```

## Column-Specific Operations

### ID Column Operations
```sql
-- Get highest ID
SELECT MAX(id) as max_id FROM wp_srfm_integrations;

-- Get next auto-increment value
SELECT AUTO_INCREMENT 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'wp_srfm_integrations';

-- Find gaps in ID sequence
SELECT id + 1 as gap_start 
FROM wp_srfm_integrations 
WHERE id + 1 NOT IN (SELECT id FROM wp_srfm_integrations) 
ORDER BY id;
```

### Type Column Operations
```sql
-- Get all unique integration types
SELECT DISTINCT type FROM wp_srfm_integrations ORDER BY type;

-- Check if integration type exists
SELECT EXISTS(
    SELECT 1 FROM wp_srfm_integrations WHERE type = 'mailchimp'
) as type_exists;

-- Get count by type
SELECT type, COUNT(*) as count 
FROM wp_srfm_integrations 
GROUP BY type 
ORDER BY count DESC;
```

### Name Column Operations
```sql
-- Find integrations without names
SELECT * FROM wp_srfm_integrations WHERE name IS NULL OR name = '';

-- Search integrations by name
SELECT * FROM wp_srfm_integrations 
WHERE name LIKE '%Integration%' 
ORDER BY name;

-- Update blank names with type-based names
UPDATE wp_srfm_integrations 
SET name = CONCAT(UPPER(SUBSTRING(type, 1, 1)), SUBSTRING(type, 2), ' Integration')
WHERE name IS NULL OR name = '';
```

### Data Column Operations
```sql
-- Find integrations with configuration data
SELECT id, type, name 
FROM wp_srfm_integrations 
WHERE data IS NOT NULL AND data != '';

-- Get data length for each integration
SELECT 
    id, 
    type, 
    CHAR_LENGTH(data) as data_size 
FROM wp_srfm_integrations 
WHERE data IS NOT NULL 
ORDER BY data_size DESC;

-- Search within JSON data (MySQL 5.7+)
SELECT * FROM wp_srfm_integrations 
WHERE JSON_VALID(data) 
AND JSON_EXTRACT(data, '$.api_key') IS NOT NULL;
```

### Status Column Operations
```sql
-- Toggle integration status
UPDATE wp_srfm_integrations 
SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END 
WHERE type = 'mailchimp';

-- Get status distribution
SELECT 
    CASE status 
        WHEN 1 THEN 'Enabled' 
        WHEN 0 THEN 'Disabled' 
        ELSE 'Unknown' 
    END as status_label,
    COUNT(*) as count
FROM wp_srfm_integrations 
GROUP BY status;

-- Find recently activated integrations
SELECT * FROM wp_srfm_integrations 
WHERE status = 1 
AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Timestamp Column Operations
```sql
-- Get newest integrations
SELECT * FROM wp_srfm_integrations 
ORDER BY created_at DESC 
LIMIT 5;

-- Get recently updated integrations
SELECT * FROM wp_srfm_integrations 
ORDER BY updated_at DESC 
LIMIT 10;

-- Find integrations created in the last 30 days
SELECT * FROM wp_srfm_integrations 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Get integration age
SELECT 
    id,
    type,
    name,
    DATEDIFF(NOW(), created_at) as days_old
FROM wp_srfm_integrations 
ORDER BY days_old DESC;
```

## Required Field Operations

### Type Field Validation
```sql
-- Ensure type is not empty before operations
SELECT * FROM wp_srfm_integrations 
WHERE type IS NOT NULL 
AND TRIM(type) != '' 
AND LENGTH(type) > 0;

-- Validate type format (lowercase, alphanumeric with underscores)
SELECT * FROM wp_srfm_integrations 
WHERE type REGEXP '^[a-z0-9_]+$';

-- Check for duplicate types (should not exist due to UNIQUE constraint)
SELECT type, COUNT(*) as count 
FROM wp_srfm_integrations 
GROUP BY type 
HAVING count > 1;
```

### Timestamp Integrity Checks
```sql
-- Ensure created_at is not in the future
SELECT * FROM wp_srfm_integrations 
WHERE created_at > NOW();

-- Ensure updated_at is not before created_at
SELECT * FROM wp_srfm_integrations 
WHERE updated_at < created_at;

-- Find records with missing timestamps
SELECT * FROM wp_srfm_integrations 
WHERE created_at IS NULL OR updated_at IS NULL;
```

### Data Integrity Validation
```sql
-- Validate JSON in data column
SELECT 
    id, 
    type, 
    CASE 
        WHEN data IS NULL THEN 'NULL'
        WHEN data = '' THEN 'Empty'
        WHEN JSON_VALID(data) THEN 'Valid JSON'
        ELSE 'Invalid JSON'
    END as data_status
FROM wp_srfm_integrations;

-- Check for required data fields per integration type
SELECT * FROM wp_srfm_integrations 
WHERE type = 'mailchimp' 
AND (data IS NULL OR NOT JSON_VALID(data) OR JSON_EXTRACT(data, '$.api_key') IS NULL);
```

## Table Creation Reference

### Complete Table Creation Script
```sql
-- Drop table if exists (use with caution)
DROP TABLE IF EXISTS wp_srfm_integrations;

-- Create the integrations table
CREATE TABLE wp_srfm_integrations (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    name VARCHAR(100),
    data LONGTEXT,
    status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Migration Notes
- **Dependencies:** Requires WordPress database with proper prefixes
- **Permissions:** Requires CREATE, ALTER, INSERT, UPDATE, DELETE, SELECT privileges
- **Storage:** LONGTEXT can store up to 4GB of data per row
- **Indexing:** Unique constraint on `type` prevents duplicate integrations

### Related Tables and Relationships
This table operates independently but may relate to:
- WordPress `wp_options` table for global settings
- SureForms entries table for integration mapping
- WordPress `wp_posts` table for form configurations

## Practical Examples

### Real-World Usage Scenarios

#### 1. Setting up MailChimp Integration
```sql
-- Check if MailChimp integration exists
SELECT * FROM wp_srfm_integrations WHERE type = 'mailchimp';

-- Insert or update MailChimp configuration
INSERT INTO wp_srfm_integrations (type, name, data, status) 
VALUES (
    'mailchimp',
    'MailChimp Marketing',
    '{"api_key":"encrypted_api_key_here","default_list":"abc123","double_optin":true}',
    1
) ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    data = VALUES(data),
    status = VALUES(status),
    updated_at = NOW();
```

#### 2. Integration Health Check
```sql
-- Get integration overview
SELECT 
    type,
    name,
    status,
    CASE WHEN status = 1 THEN 'Active' ELSE 'Inactive' END as status_label,
    created_at,
    DATEDIFF(NOW(), updated_at) as days_since_update
FROM wp_srfm_integrations 
ORDER BY status DESC, type ASC;
```

#### 3. Bulk Integration Management
```sql
-- Disable all integrations for maintenance
UPDATE wp_srfm_integrations SET status = 0;

-- Re-enable specific integrations
UPDATE wp_srfm_integrations 
SET status = 1 
WHERE type IN ('mailchimp', 'brevo');

-- Clean up old, unused integrations
DELETE FROM wp_srfm_integrations 
WHERE status = 0 
AND updated_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### Performance Optimization Tips

1. **Use the type index:** Always filter by `type` when possible
2. **Leverage status index:** Filter by `status` for enabled/disabled queries
3. **Limit LONGTEXT operations:** Avoid selecting `data` column unless needed
4. **Use prepared statements:** For security and performance in PHP
5. **Consider JSON operations:** Use MySQL JSON functions for complex data queries

### Best Practices for This Table Structure

1. **Data Encryption:** Sensitive data is automatically encrypted before storage
2. **Status Management:** Use boolean/integer values (0/1) for status
3. **Type Uniqueness:** Leverage unique constraint to prevent duplicates
4. **Timestamp Tracking:** Automatic created_at and updated_at handling
5. **JSON Validation:** Validate JSON before storing in data column
6. **Regular Cleanup:** Implement periodic cleanup of unused integrations

This documentation provides a complete reference for all database operations on the integrations table, ensuring consistent and secure handling of integration configurations in SureForms Pro.