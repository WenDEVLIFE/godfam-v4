# DATABASE CHANGES

## [v2.4.0] - 2026-07-29 - Complete Financial, Audit & Permission Schema Consolidation

### Changes
- Updated full schema dump (`database/churchgods.sql`) to consolidate all 16 active database tables and views:
  - `collections`: Financial recording table (`member_id`, `amount`, `category`, `payment_method`, `collection_date`, `recorded_by`, `remarks`).
  - `expenses`: Church expenditure records (`amount`, `category`, `expense_date`, `description`, `recorded_by`).
  - `notifications`: Member notification & birthday alert tracking.
  - `audit_logs`: User activity and security audit trail.
  - `permissions`, `role_permissions`, `role_permission_matrix`: Dynamic role-based permission matrix infrastructure.
- Retrofitted `members` schema in dump with `birthday`, `birthdate`, `gender`, and `role_id` fields.
- Retrofitted `users` schema in dump with `status` and `is_deleted` flags.
- Updated `database/migrate_birthday_collections.sql` to align `collections` table columns (`category`, `payment_method`, `remarks`) directly with `Collection.php`.

### Security Impact Assessment
- Eliminates schema drift and guarantees clean deployment on fresh database installations with zero missing table exceptions.
- Establishes strict foreign key constraints across financial records, audit trails, and permission matrix tables.

### Deployment Impact
- Full database export consolidated in [`database/churchgods.sql`](file:///c:/xampp/htdocs/V3/database/churchgods.sql).
- Migration script updated in [`database/migrate_birthday_collections.sql`](file:///c:/xampp/htdocs/V3/database/migrate_birthday_collections.sql).



### Changes
- Updated `members` table to include `email VARCHAR(150)` and `phone VARCHAR(20)`.
- Maintained `contact_info` column as an "Additional Info" field for supplemental contact data.

### Security Impact Assessment
- Email isolation in the `members` table allows for independent communication tracking regardless of whether a member has a linked user account.

### Retesting Required
- Verification that `INSERT INTO members` successfully populates all three contact fields.

### Deployment Impact
- Migration script `database/update_members_table.php` executed to retrofit existing production/local schema.


## [v1.2.0] - 2026-04-02 - QR Authentication Tokens

### Changes
- Updated `members` table to include `qr_token VARCHAR(255) UNIQUE` explicitly.

### Security Impact Assessment
- Unique Index guarantees exact token delivery and lookup avoiding duplication mapping.

### Retesting Required
- Verification `qr_token` constraint limits double assignment.

### Deployment Impact
- Migration script added for retrofitting existing schema models with the QR token logic explicitly.


## [2026-04-02] - System Initialization and Connection

### Changes
- Updated [`config/database.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/config/database.php) to use `localhost` (port 3306) and empty password for local development.
- Initialized local database `churchgods` using [`database/schema.sql`](file:///c:/Users/Robert%20Martin/godsfamchurch/database/schema.sql).
- Verified table creation for `roles`, `users`, `members`, `events`, `attendance`, and `announcements`.
- Seeded default roles and administrator user (`admin@church.com`).

### Security Impact Assessment
- Secure connection established using PDO with error mode exception.
- Prepared statements enabled for all queries (emulate prepares set to false).

### Retesting Required
- Verification of seeded data via `PDO`.
- Verification of database presence and schema integrity.

### Deployment Impact
- Database server must be active and accessible at `localhost`.
- Schema execution required for initial setup.


## [2026-04-02] - Phase 2 Schema Update

### New Tables
#### 1. `events`
- `event_id` (INT, PK, AI)
- `title` (VARCHAR 150)
- `description` (TEXT)
- `date` (DATE)
- `time` (TIME)
- `location` (VARCHAR 200)
- `created_at` (TIMESTAMP)

#### 2. `attendance`
- `attendance_id` (INT, PK, AI)
- `member_id` (INT, FK -> members)
- `event_id` (INT, FK -> events)
- `date` (DATE)
- `status` (ENUM: Present, Absent)
- `created_at` (TIMESTAMP)
- **Constraint**: UNIQUE(member_id, event_id, date)

#### 3. `announcements`
- `announcement_id` (INT, PK, AI)
- `title` (VARCHAR 200)
- `content` (TEXT)
- `created_by` (INT, FK -> users)
- `created_at` (TIMESTAMP)

### Seeds Updated
- Roles: Added `Secretary`, `Committee Head`.


## [v1.0.0] - 2026-04-02
### Module Affected
System Foundation / Authentication

### Type of Change
Feature (Initial Setup)

### Problem Description
Initial database creation for roles, users, and member management.

### Solution Implemented
Created schema for:
- `roles`: ID and name.
- `users`: Standard user fields (name, email, password, role_id).
- `members`: Member-specific details (full name, contact info, address, status).
- Seeded roles: Administrator, Pastor, Staff, Member.
- Seeded initial admin.

### Database Changes
Creation of tables and foreign key constraints.

### Security Impact Assessment
Data isolation and primary key integrity established. Password hashing handled by bcrypt.

### Deployment Impact
Requires MySQL 5.7+ or MariaDB 10.3+.
