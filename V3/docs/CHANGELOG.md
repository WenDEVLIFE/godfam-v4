# CHANGELOG

## [v2.3.3] - 2026-04-06
### Module Affected
QR Attendance System / Backend Infrastructure / Dependency Management

### Type of Change
Hotfix / Compatibility Overhaul

### Problem Description
The application encountered a `Fatal error` upon loading the member profile page: `Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.2.0". You are running 8.0.30.`

### Root Cause Analysis
The `endroid/qr-code` library was previously updated to version `^6.0`, which strictly requires PHP `8.2.0` or higher. The current XAMPP production environment is running PHP `8.0.30`. This triggered a platform check failure in Composer's autoloader, completely blocking the application.

### Solution Implemented
1.  **Dependency Downgrade**: Updated `composer.json` to require `endroid/qr-code: ^3.9`. This version is fully compatible with PHP `8.0.30` while still providing modern QR generation capabilities.
2.  **API Migration**: Rewrote the QR generation logic in both `QrController.php` and `public/profile.php` to use the version 3.x **fluent API** (setting size, margin, and writer directly on the `QrCode` object). This replaces the `Builder` pattern found in later versions.
3.  **Environment Stability**: Confirmed that `SvgWriter` remains the primary output method to avoid the missing `GD` extension issue identified in `v2.3.2`.

### Files Modified
- [`composer.json`](file:///c:/xampp/htdocs/godsfamchurch%20modified/composer.json)
- [`controllers/QrController.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/controllers/QrController.php)
- [`public/profile.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/profile.php)

### Database Changes
- None.

### Security Impact Assessment
- No negative impact. Version 4.x of the `endroid/qr-code` library is stable and widely used.

### Retesting Required
- Navigate to `profile.php?id=X`. Confirm the page loads without the "Fatal error".
- Verify that a valid, scannable QR SVG is rendered inline.
- Confirm any external calls to `generate_qr.php` still function with the updated controller.

### Deployment Impact
- **CRITICAL**: A `composer update` must be run on any environment where this change is deployed to synchronize the `vendor/` folder with the new `composer.json` requirements.

### Backward Compatibility Impact
- Improved the minimum PHP requirement from `8.2.0` down to `8.0.0`, making the system more portable across common XAMPP and shared hosting environments.

---

## [v2.3.2] - 2026-04-06
### Module Affected
QR Attendance System / QR Code Generation

### Type of Change
Hotfix / Environment Compatibility

### Problem Description
After the v2.3.0 fix switched `QrController` to use `endroid/qr-code`'s `PngWriter`, no QR image was displayed on the Digital Member ID card. The `<img>` tag rendered as a broken image with no visible error.

### Root Cause Analysis
The `PngWriter` (and `GifWriter`, `WebPWriter`) in `endroid/qr-code` all extend `AbstractGdWriter`, which requires the PHP **GD extension** (`extension=gd`) to be loaded. The XAMPP environment's active `php.ini` (`C:\php83\php.ini`) has GD disabled. The `PngWriter::write()` call threw an internal exception that was caught by the controller's `catch (\Throwable $e)` block, which sent a `500 Internal Server Error` response — but since the error was in the image request (not the page), the browser silently showed a broken image icon instead of displaying any error to the user.

Diagnostic check confirmed: PHP 8.3.29, GD: NO, Imagick: NO.

### Solution Implemented
Replaced `PngWriter` with `SvgWriter` in `QrController::generate()`. The `SvgWriter`:
- Is written entirely in pure PHP using `SimpleXMLElement` — **no GD, no Imagick, no system image library required**.
- Generates a **fully spec-compliant SVG QR code** via the same internal Bacon matrix factory (`endroid/qr-code`), encoding the raw `qr_token` with proper Reed-Solomon error correction, data masking, format information, and version data.
- **Inline Embedding**: In `profile.php`, the SVG is now generated and embedded directly into the HTML instead of using a separate `<img src="generate_qr.php">` request. This bypasses potential session/cookie isolation issues and cross-environment PHP configuration mismatches.
- **CSS Synchronization**: Updated `main.css` to target the inline `<svg>` element for consistent layout.
- Outputs `Content-Type: image/svg+xml` for the direct controller endpoint, which renders identically to PNG in any modern browser.

Verified via CLI: `SvgWriter` produces 6,573 bytes of valid SVG output with no errors on this PHP 8.3.29 environment.

### Files Modified
- [`controllers/QrController.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/controllers/QrController.php)

### Database Changes
- None.

### Security Impact Assessment
- No change to security posture. Token encoding is identical; only the output image format changed.

### Retesting Required
- Navigate to any member's profile page (`profile.php?id=X`) — the QR code image must load (no broken image icon).
- Scan the displayed QR with a phone camera — it must decode to the 32-char hex `qr_token`.
- Navigate to `generate_qr.php?id=X` directly — browser must display a valid QR SVG image.

### Deployment Impact
- None. `SvgWriter` has zero new dependencies.
- If GD is ever enabled in `php.ini` in the future, switching back to `PngWriter` is a 2-line change in `QrController.php`.

### Backward Compatibility Impact
- None. `<img>` tags render SVG and PNG identically in browsers.

---

## [v2.3.1] - 2026-04-06
### Module Affected
QR Attendance System / Member Management / Database Tooling

### Type of Change
Feature Addition / Operational Tooling

### Problem Description
Following the v2.3.0 fix that replaced the fake QR generator with the real `endroid/qr-code` library, it was necessary to ensure that all existing members in the database had valid, correctly-encoded QR tokens ready to use. While new members always receive a token on creation (`Member::create()`), older records created before the QR column was introduced may have `NULL` tokens. Additionally, there was no administrator workflow to rotate/invalidate a specific member's token if their ID card was lost or compromised.

### Root Cause Analysis
- `Member::generateMissingTokens()` existed but its return value was `void`, making it impossible for the caller to report how many rows were updated.
- No per-member token regeneration method existed in the model or controller.
- No browser-accessible admin UI existed for either operation; the only option was running a raw database query manually.

### Solution Implemented
1. **`Member::generateMissingTokens()` enhanced** — Now returns an integer count of tokens generated instead of `void`.
2. **`Member::regenerateToken($memberId)` added** — Issues a fresh `bin2hex(random_bytes(16))` token and atomically replaces the old one in the database. Old QR codes are immediately invalidated.
3. **`MemberController::backfillAllTokens()` added** — Administrator-only wrapper around `generateMissingTokens()`. Returns count for UI feedback.
4. **`MemberController::regenerateToken($memberId)` added** — Administrator-only wrapper for per-member token rotation.
5. **`public/members.php` updated with QR Token Tools card** — Displays only for Administrators. Contains:
   - **Backfill All Missing Tokens** button — runs one-click bulk backfill with confirmation dialog and count feedback.
   - **Regen QR** button per member row — issues a new token with a name-specific confirmation warning that the old QR will stop working.
6. **`database/backfill_qr_tokens.php` created** — Standalone CLI/browser script for use outside the web UI. Outputs per-member results (ID, name, new token), total success and failure counts. Works both in XAMPP shell (`php database/backfill_qr_tokens.php`) and via browser for one-time use.

### Files Modified
- [`models/Member.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/models/Member.php)
- [`controllers/MemberController.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/controllers/MemberController.php)
- [`public/members.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/members.php)

### Files Created
- [`database/backfill_qr_tokens.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/database/backfill_qr_tokens.php) *(new — one-shot backfill script)*

### Database Changes
- No schema changes. Only `UPDATE members SET qr_token = ?` statements on rows with `NULL` tokens.

### Security Impact Assessment
- **MEDIUM POSITIVE**: Administrators can now rotate any member's token in the event of card loss or suspected compromise. The old token is replaced atomically, blocking any prior printed QR immediately.
- Token regeneration is restricted to the `Administrator` role via `authorizeRoles(['Administrator'])`.
- All forms are protected by CSRF token validation.
- The standalone `database/backfill_qr_tokens.php` script has no authentication — it should be removed or moved outside the web root after initial use if run via browser.

### Retesting Required
- Log in as Administrator → Members page → "Backfill All Missing Tokens" button appears in QR Token Tools card.
- Click Backfill → confirm dialog → success message reporting count.
- Click "Regen QR" for a specific member → confirm dialog with member name → success message → old QR on printed card no longer scans.
- Confirm non-Administrator roles do NOT see the QR Token Tools card or Regen QR button.
- Run `database/backfill_qr_tokens.php` via CLI and confirm per-member output is correct.

### Deployment Impact
- No migrations needed. Backfill must be run once if any existing members have `NULL` tokens.
- Recommended step: run `database/backfill_qr_tokens.php` immediately after deploying v2.3.x to any environment.

### Backward Compatibility Impact
- Regenerating a member's token immediately invalidates their previously printed ID card. Staff should be notified to reprint the affected card(s).

---

## [v2.3.0] - 2026-04-06
### Module Affected
QR Attendance System / Attendance Scanner / QR Code Generation

### Type of Change
Critical Bug Fix / Feature Correction

### Problem Description
The QR code displayed on member ID cards was completely non-functional. Despite being visually similar to a real QR code, it could not be decoded by any external scanner — phone camera, hardware wedge, or QR scanning app. As a result, the entire QR Attendance System feature was silently broken: staff scanning member cards either received no result or a garbage string that never matched any database record. Additionally, all successful QR scan submissions were displayed as errors in the UI due to a type mismatch in the success-detection logic.

### Root Cause Analysis
Two independent bugs caused the complete failure of the QR attendance workflow:

1. **Fake QR Generator (`utils/QrGenerator.php`):**  
   The class drew QR finder and timing patterns correctly but encoded data by running `md5($qr_token)` on the token and cycling through the resulting 128 bits to fill data cells. This means the SVG encoded a pattern derived from the MD5 hash — not the actual token string. A real QR scanner reading this image would decode garbage, not the `qr_token`. The class also had no Reed-Solomon error correction, data masking, format information bits, or version information — all mandatory parts of the QR specification.

2. **Broken success detection in `scan_attendance.php` (lines 55–58):**  
   `AttendanceController::recordByQr()` returns `true` (PHP boolean) on success, but the view called `strpos(strtolower($result), 'successfully')` on that value. `strpos(strtolower(true), …)` evaluates as `strpos('1', …)` which is always `false`. Every valid scan was therefore routed to the error branch and shown to the operator as a failure.

### Solution Implemented
1. **Replaced `QrController` with `endroid/qr-code` PNG output:**  
   Removed the dependency on `utils/QrGenerator.php`. `QrController::generate()` now instantiates `Endroid\QrCode\QrCode` with the raw `qr_token` string and writes it via `Endroid\QrCode\Writer\PngWriter`, which produces a fully spec-compliant QR image with proper Reed-Solomon error correction (Medium level), data masking, and format/version information. Output is `image/png` for maximum compatibility with phone cameras and hardware scanners.

2. **Fixed type-safe success check in `scan_attendance.php`:**  
   Replaced the `strpos()` string match with a strict `=== true` comparison. A success now sets `$success = "Attendance recorded successfully."` and a failure passes the error string directly to `$error`.

3. **Retired `utils/QrGenerator.php`:**  
   Replaced the entire class body with a documented deprecation stub that explains why the class was removed and directs maintainers to the current solution. The file is preserved for historical audit purposes.

### Files Modified
- [`controllers/QrController.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/controllers/QrController.php)
- [`public/scan_attendance.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/scan_attendance.php)
- [`utils/QrGenerator.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/utils/QrGenerator.php) *(retired — replaced with deprecation stub)*

### Database Changes
- None. The `members.qr_token` column and `Member::findByQrToken()` logic are unchanged.

### Security Impact Assessment
- **MEDIUM POSITIVE**: QR codes now encode the raw opaque token (32-char hex, random), never a member name or primary key. The PNG output carries no additional metadata. The existing CSRF and RBAC protections on `scan_attendance.php` are unaffected.
- **NO NEW RISK**: All lookups continue to use parameterized `SELECT WHERE qr_token = ?` queries.

### Retesting Required
- Generate QR for any member via `generate_qr.php?id=X` — response must be `Content-Type: image/png` and scannable by a phone camera.
- Navigate to `scan_attendance.php?event_id=Y`, scan a valid member QR, and confirm a green success banner appears.
- Scan the same member a second time for the same event — confirm "Attendance already recorded" message.
- Scan a random / invalid token — confirm "Invalid QR Code." error message.
- Confirm all existing role-based access controls remain operational (Secretary, Committee Head, Administrator only).

### Deployment Impact
- No new Composer packages required. `endroid/qr-code` was already present in `vendor/`.
- No database migrations needed.
- The PHP GD extension must be enabled (required by `PngWriter`). On XAMPP this is typically enabled by default.

### Backward Compatibility Impact
- **VISUAL**: Member ID cards previously displayed an SVG; they will now display a PNG. Browsers handle both identically in `<img>` tags.
- **FUNCTIONAL**: Old SVG QR codes displayed on printed ID cards are now obsolete. Staff should regenerate/reprint any previously distributed ID cards to obtain scannable PNG QR codes.

---

## [v2.2.1] - 2026-04-06
### Module Affected
Global UI / Session Handling / User Experience

### Type of Change
Feature / UX Enhancement

### Problem Description
The Logout link in the sidebar navigation immediately redirected users to `logout.php` with no confirmation prompt. This created a risk of accidental session termination, particularly for staff entering attendance or announcements data.

### Root Cause Analysis
The logout `<a>` tag in `public/layout/sidebar.php` used a direct `href="logout.php"` with no interception, providing zero friction between intent and action.

### Solution Implemented
1. **Logout Trigger Intercept**: Changed the logout `<a>` tag to call `openLogoutModal(event)` via `onclick`, preventing the default navigation.
2. **Confirmation Modal**: Injected a centered modal dialog directly in `sidebar.php` (included on every authenticated page) containing:
   - A danger-styled circular icon with a logout glyph.
   - A clear "Sign Out?" heading and descriptive message.
   - Two action buttons: **Cancel** (dismisses, returns focus to trigger) and **Yes, Sign Out** (navigates to `logout.php`).
3. **Accessible Behavior**:
   - `role="dialog"` and `aria-modal="true"` attributes applied.
   - Focus is programmatically moved to the Cancel button on open.
   - `Escape` key closes the modal without logging out.
   - Clicking the dark overlay (outside the card) also dismisses the modal.
4. **CSS**: Added `.logout-modal-content`, `.logout-modal-icon`, `.logout-modal-title`, `.logout-modal-message`, `.logout-modal-actions` with a subtle scale-in animation (`logoutModalIn`).

### Files Modified
- [`public/layout/sidebar.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/layout/sidebar.php)
- [`public/assets/css/main.css`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/assets/css/main.css)

### Database Changes
- None.

### Security Impact Assessment
- **LOW POSITIVE**: Reduces the risk of unintentional session termination.
- The actual logout logic (`logout.php` → `AuthController::logout()`) is unchanged — session destruction and redirect remain secure.

### Retesting Required
- Clicking the sidebar Logout link should open the modal (not redirect immediately).
- Clicking **Cancel** dismisses the modal; user remains logged in.
- Pressing **Escape** dismisses the modal; user remains logged in.
- Clicking the dark overlay dismisses the modal; user remains logged in.
- Clicking **Yes, Sign Out** successfully logs the user out and redirects to login.
- Test on all role levels (Admin, Staff, Member).

### Deployment Impact
- No backend changes. CSS and HTML-only update; safe with zero downtime deployment.

### Backward Compatibility Impact
- None.

---

## [v2.2.0] - 2026-04-06
### Module Affected
Authentication / Password Reset / OTP System

### Type of Change
Security Hardening / Feature Enhancement

### Problem Description
The original password reset flow stored OTP codes as plain text in the `password_resets` table, had no attempt limiting, and returned generic error messages with no usage guidance. A brute-force attack could trivially enumerate valid 6-digit codes within 1,000,000 attempts. Additionally, the `MailService` constructor was incorrectly called with a `$pdo` argument it did not accept, and a duplicate `sendOTPMail` function caused a fatal PHP syntax error.

### Root Cause Analysis
- No attempt counter existed in the `password_resets` table schema.
- `verifyOTP()` used a plain string comparison (`otp = ?`) instead of `password_verify()`.
- `MailService::__construct()` accepts 0 parameters; `AuthController` was incorrectly passing `$pdo`.
- A prior incomplete edit left `sendOTPMail` duplicated inside `MailService.php`, causing a fatal parse error.
- `verify_otp.php` checked `verifyOTP()` as a boolean and showed a generic message regardless of reason.

### Solution Implemented
1. **Database Migration**: Added `attempts INT DEFAULT 0` column to `password_resets`; widened `otp` to `VARCHAR(255)` to store bcrypt hashes.
2. **Hashed OTP Storage**: `requestOTP()` now hashes the code using `password_hash()` before persisting it.
3. **3-Attempt Limit**: `verifyOTP()` fetches the record, checks `attempts >= 3`, increments the counter on each failure, and returns a descriptive message with remaining count.
4. **MailService Fix**: Rewrote `MailService.php` clean — consolidated `sendOTPMail()` method, removed duplicate code, fixed constructor signature.
5. **AuthController Fix**: Corrected `new MailService()` call (removed stale `$pdo` argument).
6. **UI Error Propagation**: `verify_otp.php` now captures the full string return value from `verifyOTP()` and displays it directly (e.g. "Incorrect OTP. You have 2 attempts left.").

### Files Modified
- `controllers/AuthController.php`
- `utils/MailService.php`
- `public/verify_otp.php`
- `database/schema.sql` (reference)
- `database/migrate_otp.php` (migration — new)

### Database Changes
```sql
ALTER TABLE password_resets ADD COLUMN attempts INT DEFAULT 0;
ALTER TABLE password_resets MODIFY COLUMN otp VARCHAR(255) NOT NULL;
```

### Security Impact Assessment
- **HIGH POSITIVE**: OTP brute-force window reduced from 1,000,000 to 3 attempts.
- **HIGH POSITIVE**: Database breach no longer exposes plain OTP codes.
- **MEDIUM POSITIVE**: Expired and locked OTPs return clear, specific reasons, reducing user confusion.

### Retesting Required
- Full end-to-end: request OTP → verify with wrong codes (3x) → verify block → request new OTP → correct verify → reset password → login.

### Deployment Impact
- Run migration script `database/migrate_otp.php` once before deploying on any environment.

### Backward Compatibility
- Existing plain-text OTPs in `password_resets` will fail verification (all users must re-request). Recommended to TRUNCATE `password_resets` on deploy.

---

## [v2.1.2] - 2026-04-06
### Module Affected
Member Management / Database Schema

### Type of Change
Bug Fix / Database Schema Update


### Problem Description
Encountered `PDOException` stating `Unknown column 'email' in 'field list'` when adding a new member. The `members` table lacked specific columns for email and phone contact details.

### Root Cause Analysis
The `Member` model and `members.php` form were designed to store granular contact details (`email`, `phone`), but the `members` table in `schema.sql` only contained a generic `contact_info` column.

### Solution Implemented
- **Schema Expansion**: Added `email` (VARCHAR 150) and `phone` (VARCHAR 20) columns to the `members` table using a migration script.
- **Legacy Preservation**: Maintained the `contact_info` column and repurposed it as an "Additional Info" field to satisfy the user's requirement to keep it working.
- **Model Synchronization**: Updated `models/Member.php` to include `contact_info` alongside `email` and `phone` in all CRUD operations.
- **UI Enhancement**: Refined the Member Management table to display both primary and additional contact details.

### Files Modified
- [`database/schema.sql`](file:///c:/xampp/htdocs/godsfamchurch%20modified/database/schema.sql)
- [`models/Member.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/models/Member.php)
- [`public/members.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/members.php)
- [`docs/DATABASE_CHANGES.md`](file:///c:/xampp/htdocs/godsfamchurch%20modified/docs/DATABASE_CHANGES.md)

### Database Changes
- Migration applied: `ALTER TABLE members ADD COLUMN email...` and `ALTER TABLE members ADD COLUMN phone...`.

### Security Impact Assessment
- None.

### Retesting Required
- Verification that adding a member from the UI successfully writes to `email`, `phone`, and `contact_info` columns.

### Deployment Impact
- Migration script executed.


## [v2.1.1] - 2026-04-06
### Module Affected
Events / Calendar Component

### Type of Change
Update UI/UX (Skeleton Refinement)

### Problem Description
The calendar component in the "Skeleton Backend" was lacking visual definition and did not follow the desired "narrow and centered" layout requested by the user.

### Root Cause Analysis
Initial "Skeleton" revert stripped all complex styling, including the grid layout for the calendar, leaving it in an un-styled state.

### Solution Implemented
- **Grid Layout**: Implemented a responsive 7-column CSS Grid for `.calendar-grid`.
- **Day Styling**: Added distinctive styles for `.calendar-day`, including `.today` highlighting and `.empty` states.
- **Centering**: Introduced `.calendar-container` with `max-width: 900px` and `margin: 0 auto` to center the calendar view.
- **Uniform Indicators**: Styled event indicators with a neutral primary color to maintain the "Skeleton Backend" aesthetic.

### Files Modified
- [`public/assets/css/main.css`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/assets/css/main.css)
- [`public/events.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/events.php)

### Database Changes
- N/A

### Security Impact Assessment
- No security logic altered.

### Retesting Required
- Visual confirmation of calendar alignment and "Today" highlighting.
- Verification of centered layout on various screen sizes.

### Deployment Impact
- New CSS classes added to `main.css`.


## [v2.1.0] - 2026-04-06
### Module Affected
Global UI / UX System Orientation

### Type of Change
Design Revert / Architectural Optimization (Skeleton Backend Focus)

### Problem Description
The previous "Modern Sanctuary" UI, while aesthetically rich, introduced unnecessary overhead and visual complexity for administrative tasks. The user required a transition back to a minimalist, high-performance "Skeleton Backend" style that prioritizes data density and functional clarity.

### Root Cause Analysis
The premium UI's reliance on glassmorphism, heavy CSS filters, and external font dependencies impacted perceived system snappiness and information density in an admin-focused environment.

### Solution Implemented
- **Minimalist CSS Overhaul**: Replaced the complex `main.css` with a lean, functional stylesheet utilizing a neutral palette (Gray/Navy/White) and system fonts (Helvetica/Arial/Sans-serif).
- **Layout Simplification**: Stripped visual flourishes from `header.php` and `sidebar.php`, focusing on high-contrast navigation and clear role-based indicators.
- **View De-cluttering**: 
    - **Login**: Removed 4K background and blur effects for an instant-loading, functional entrance.
    - **Dashboard**: Replaced rich stat cards with high-density, flat-design components.
    - **Digital ID**: Converted the premium dual-sided card into a functional, single-sided identification card optimized for clarity.
    - **CRUD Pages**: Simplified tables and modals in Members, Events, and Announcements to maximize usable workspace.
- **Performance Optimization**: Removed all Google Font API calls and reduced CSS selector complexity.
- **Main Feature Preservation**: Ensured 100% functional parity for the QR Attendance System (`scan_attendance.php`) during the UI transition.

### Files Modified
- [`public/assets/css/main.css`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/assets/css/main.css)
- [`public/layout/header.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/layout/header.php)
- [`public/layout/sidebar.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/layout/sidebar.php)
- [`public/login.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/login.php)
- [`public/dashboard.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/dashboard.php)
- [`public/members.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/members.php)
- [`public/events.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/events.php)
- [`public/announcements.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/announcements.php)
- [`public/profile.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/profile.php)
- [`public/scan_attendance.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/scan_attendance.php)

### Database Changes
- N/A

### Security Impact Assessment
- No changes to authentication or authorization logic. 
- UI simplification reduces the risk of obscured interface elements on lower-resolution devices.

### Retesting Required
- Verification of QR scanner functionality on mobile devices to ensure CSS changes did not impact camera viewport sizing.
- General smoke test of all navigation links.

### Deployment Impact
- Legacy icons or unused image assets in `public/assets/img/` can now be safely archived.

### Backward Compatibility Impact
- Visual interface has shifted from "Premium" to "Utility". User expectations should be managed regarding the transition from Sanctuary 2.0 to Skeleton Backend.

## [v2.0.0] - 2026-04-05
### Module Affected
Global UI / UX / System Design

### Type of Change
Major Architectural Overhaul (Redesign)

### Problem Description
The user required a full modernization of the Church Management System with a premium "Modern Sanctuary" aesthetic.

### Solution Implemented
- **Full UI/UX Redesign**: Transitioned from a generic dark theme to a high-end "Modern Sanctuary" Light Mode.
- **Palette Implementation**: White, Church Red, Yellow-Gold, and Navy Blue.
- **Custom Assets**: Generated and integrated a custom 4K background image for the login experience.
- **Component System**: Developed a cohesive design system for cards, table, forms, and navigation.
- **Navigation**: Redesigned the Sidebar and Header with glassmorphism and premium transitions.
- **Feature Finish**: Polished the Digital ID card view for members.

### Files Modified
- [`public/assets/css/main.css`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/assets/css/main.css)
- [`public/layout/sidebar.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/layout/sidebar.php)
- [`public/login.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/login.php)
- [`public/dashboard.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/dashboard.php)
- [`public/members.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/members.php)
- [`public/events.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/events.php)
- [`public/announcements.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/announcements.php)
- [`public/users.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/users.php)
- [`public/profile.php`](file:///c:/xampp/htdocs/godsfamchurch%20modified/public/profile.php)

### Database Changes
- N/A

### Security Impact Assessment
- UI improvements did not alter backend security logic.
- Improved visual separation of sensitive actions.

### Retesting Required
- Visual regression testing across all roles (Admin, Staff, Member).

### Deployment Impact
- New CSS and image assets required in `public/assets/`.

### Backward Compatibility Impact
- Significant visual changes; user training on the new layout may be required.
### Module Affected
Attendance System / Member Management

### Type of Change
Feature / Security Hardening

### Problem Description
Requirement for members to easily and securely check-in to events using a QR Code to speed up attendance taking.

### Root Cause Analysis
Manual attendance check is slow; QR scan is desired. Exposing raw member IDs in QR is insecure.

### Solution Implemented
- Augmented `members` table with `qr_token` (secure 16-byte hex token).
- Integrated `endroid/qr-code` to generate PNG representations of member QR tokens dynamically.
- Implemented `views/scan_attendance.php` supporting both hardware keyboard-wedge scanners and web camera scanning (via `html5-qrcode`).
- Authored logic in `AttendanceController::recordByQr` to lookup tokens, prevent duplication, and quickly record member presence.

### Files Modified
- [`models/Member.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/models/Member.php)
- [`controllers/AttendanceController.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/controllers/AttendanceController.php)
- [`public/index.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/public/index.php)
- [`views/members.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/members.php)
- [`views/attendance.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/attendance.php)
- [`views/scan_attendance.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/scan_attendance.php)
- [`controllers/QrController.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/controllers/QrController.php)

### Database Changes
- Modified `members` schema to include `qr_token VARCHAR(255) UNIQUE`.

### Security Impact Assessment
- Scanning interface verifies CSRF tokens and enforces RBAC (Secretary/Admin).
- `qr_token` does not reveal sequential IDs.

### Retesting Required
- Verification of token generation for new members.
- Testing of attendance QR Scanner logic.

### Deployment Impact
- Added composer requirement (`endroid/qr-code` via `vendor/`).

### Backward Compatibility Impact
- Pre-existing members lacking tokens require a backfill via migration script to ensure the token generation occurs.


## [v1.1.1] - 2026-04-02
### Module Affected
Database Configuration / System Initialization

### Type of Change
Bug Fix / System Setup

### Problem Description
The database connection was failing due to an incorrect host configuration (`localhost:8000`), and the database schema was not initialized on the local environment.

### Root Cause Analysis
- `DB_HOST` was incorrectly set to a web server port instead of the MySQL default.
- Missing local database instance `churchgods` and associated tables.

### Solution Implemented
- Updated [`config/database.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/config/database.php) to use `localhost` (port 3306).
- Automated database initialization using a temporary PHP script to execute [`database/schema.sql`](file:///c:/Users/Robert%20Martin/godsfamchurch/database/schema.sql).
- Verified connectivity and presence of seeded roles and administrator user.

### Files Modified
- [`config/database.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/config/database.php)

### Database Changes
- Created database `churchgods`.
- Created tables: `roles`, `users`, `members`, `events`, `attendance`, `announcements`.
- Seeded roles and initial administrator.

### Security Impact Assessment
- Successful connection ensures that prepared statements and PDO security measures can now be enforced.
- Database access is restricted to the local environment with standard credentials.

### Retesting Required
- Verification of database connection via `PDO`.
- Verification of seeded data in `users` and `roles` tables.

### Deployment Impact
- Database must be initialized for the system to function.

### Backward Compatibility Impact
- N/A


## [v1.1.0] - 2026-04-02
### Module Affected
Core Features (Members, Events, Attendance, Announcements)

### Type of Change
Feature Addition

### Problem Description
Phase 2 implementation: The system requires fully functional member management, event scheduling, attendance tracking, and a communication channel (announcements).

### Root Cause Analysis
Requirement for Phase 2 Core Features.

### Solution Implemented
- Created Model-Controller-View (MCV) pattern for new modules.
- Implemented full CRUD for Members and Events.
- Added Attendance recording system with duplicate prevention.
- Added Announcement system for church-wide updates.
- Refined routing in `public/index.php`.
- Integrated Role-Based Access Control (RBAC) across all new controllers.

### Files Modified
- [`database/schema.sql`](file:///c:/Users/Robert%20Martin/godsfamchurch/database/schema.sql)
- [`public/index.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/public/index.php)
- [`models/Member.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/models/Member.php)
- [`models/Event.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/models/Event.php)
- [`models/Attendance.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/models/Attendance.php)
- [`models/Announcement.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/models/Announcement.php)
- [`controllers/MemberController.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/controllers/MemberController.php)
- [`controllers/EventController.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/controllers/EventController.php)
- [`controllers/AttendanceController.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/controllers/AttendanceController.php)
- [`controllers/AnnouncementController.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/controllers/AnnouncementController.php)
- [`views/members.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/members.php)
- [`views/events.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/events.php)
- [`views/attendance.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/attendance.php)
- [`views/announcements.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/announcements.php)
- [`views/dashboard.php`](file:///c:/Users/Robert%20Martin/godsfamchurch/views/dashboard.php)

### Database Changes
- Added tables: `events`, `attendance`, `announcements`.
- Updated roles: `Secretary`, `Committee Head`.

### Security Impact Assessment
- Enforced strict RBAC: Secretary (Full Access), Committee Head (Limited CRUD), Staff (View).
- Protected new forms with CSRF tokens.
- Used prepared statements for all new database interactions.

### Retesting Required
- Verification of CRUD for members and events.
- Verification of attendance recording logic.
- Verification of RBAC permissions for each role.

### Deployment Impact
- Database schema update required (`schema.sql`).

### Backward Compatibility Impact
- N/A



## [v1.0.0] - 2026-04-02
### Module Affected
System Foundation / Authentication

### Type of Change
Feature (Initial Setup)

### Problem Description
New project initialization: The Church Management System requires a secure foundation, including an MVC structure, database schema, and authentication baseline.

### Root Cause Analysis
N/A (Initial Setup)

### Solution Implemented
- Defined directory structure (MVC).
- Created MySQL schema for roles, users, and members.
- Implemented Initial roles (Administrator, Pastor, Staff, Member).
- Seeded default Administrator user.

### Files Modified
- [`database/schema.sql`](file:///C:/Users/Robert%20Martin/godsfamchurch/database/schema.sql)

### Database Changes
- Tables: `roles`, `users`, `members`.

### Security Impact Assessment
Initial setup ensures password hashing using `password_hash()` and establishes the base for Role-Based Access Control (RBAC).

### Retesting Required
Manual verification of the schema creation and user seeding.

### Deployment Impact
Requires MySQL server setup and execution of `schema.sql`.

### Backward Compatibility Impact
N/A
