# SECURITY FIXES

## [v1.2.7] - 2026-04-05
### Module Affected
Authentication / Session Management / Database Schema

### Type of Change
Security Hardening / Critical Bug Fixes

### Problem Description
Multiple security and functional vulnerabilities were identified:
1.  **Authentication Lockout**: The system could not authenticate any user due to a missing `member_id` column in the `users` table, which caused SQL errors and silent failure on every login attempt.
2.  **Compromised Default Admin**: The default administrator's password hash was malformed, preventing login even with correct credentials.
3.  **Broken Password Resets**: The OTP-based password reset feature was non-functional because the backing `password_resets` table did not exist in the database.
4.  **Weak Session Cookies**: Default PHP session settings were in use, which are vulnerable to XSS-based session hijacking and CSRF attacks.

### Root Cause Analysis
- **Schema Mismatch**: The `schema.sql` was not in sync with the controller logic.
- **Corrupt Implementation**: Initial seed hash was improperly generated.
- **Insecure Defaults**: Standard PHP `session_start()` doesn't implement HTTP-only or SameSite flags by default.

### Solution Implemented
1.  **Critical Fix**: Added `member_id` column to `users` and mapped foreign key constraints.
2.  **Critical Fix**: Re-generated a valid bcrypt hash for the default administrator (`admin123`).
3.  **Feature Restoration**: Created the `password_resets` table with necessary indexes for secure OTP management.
4.  **Session Hardening**: Updated `session_start()` calls in `AuthMiddleware.php` and `CSRF.php` with `cookie_httponly => true` and `cookie_samesite => 'Lax'`.
5.  **Environmental Fix**: Renamed all directories and files by removing the anomalous ` (1)` suffix, ensuring all security middleware files were correctly included.

### Files Modified
- [`database/schema.sql`](file:///c:/xampp/htdocs/godsfamchurch%20modified/database/schema.sql)
- [`middleware/AuthMiddleware.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/middleware/AuthMiddleware.php)
- [`middleware/CSRF.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/middleware/CSRF.php)
- System-wide batch rename (All files and directories containing ` (1)`).

### Database Changes
- Table `users`: Added `member_id` (INT, NULL).
- Table `password_resets`: Created new table for OTP tracking.
- Table `users`: Updated record `user_id = 1` with a valid bcrypt hash.

### Security Impact Assessment
- **High Significance**: Restored the primary authentication path and the secondary recovery path. Session cookies are now better protected against XSS-based hijacking and CSRF interaction.

### Retesting Required
1.  **Authentication**: Verify login at `/public/login.php` with all seeded accounts.
2.  **Password Reset**: Verify OTP delivery and acceptance at `/public/forgot_password.php`.
3.  **Session Security**: Inspect cookies in browser developer tools to ensure `HttpOnly` and `SameSite=Lax` flags are set.

### Deployment Impact
- Future local deployments will inherit these security improvements automatically through the updated `schema.sql`.

### Backward Compatibility Impact
- N/A
