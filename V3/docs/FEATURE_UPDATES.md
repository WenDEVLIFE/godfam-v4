# FEATURE UPDATES

## [v2.3.0] - 2026-04-06 - QR Attendance System — Fully Operational
### Module Affected
QR Attendance System (QR Generation + Attendance Scanner)

### Type of Change
Critical Bug Fix / Feature Restoration

### Problem Description
The QR Attendance System was silently and completely non-functional despite appearing correctly wired in code. Member ID cards displayed a QR image that no real scanner could decode, and every valid scan submitted to the attendance form was incorrectly shown as an error. The feature was unusable in any real-world deployment.

### Solution Implemented
- **Spec-Compliant QR Generation**: `QrController::generate()` now uses `endroid/qr-code` (`PngWriter`) to encode the raw `qr_token` into a fully standard QR code PNG image. The token is embedded directly — not hashed or transformed — ensuring the scanner reads back the exact 32-char hex value that `Member::findByQrToken()` expects.
- **PNG Output for Maximum Compatibility**: Output changed from `image/svg+xml` to `image/png`, ensuring compatibility with all phone cameras, hardware barcode wedge scanners, and QR scanner apps without any additional configuration.
- **Correct Success Feedback**: The `scan_attendance.php` success detection now uses a strict `=== true` comparison against the controller's return value, so successful scans display a green "Attendance recorded successfully." banner to the operator.
- **Fake Generator Retired**: `utils/QrGenerator.php` has been replaced with a deprecation stub, removing a misleading class that silently produced non-functional output.

### Security Impact Assessment
- QR tokens remain opaque 32-char random hex strings — no PII or primary keys exposed.
- All existing CSRF token validation and RBAC role checks on the scanner are untouched.

### Retesting Required
- Full end-to-end: view member profile → QR image loads as PNG → scan with phone camera → attendance recorded → green banner displayed.
- Duplicate scan attempt returns "Attendance already recorded" message.
- Invalid token returns "Invalid QR Code." message.

---

## [v2.2.1] - 2026-04-06 - Logout Confirmation Modal
### Module Affected
Global UI / Session Management

### Type of Change
UX Enhancement / Feature

### Problem Description
Users could accidentally log out by clicking the sidebar Logout link, risking loss of in-progress form data (e.g., while entering attendance records or announcements).

### Solution Implemented
- **Confirmation Gate**: Replaced the direct logout hyperlink with a JavaScript-intercepted click handler that opens a contextual confirmation modal before any session is terminated.
- **Accessible Modal**: Built using ARIA attributes (`role="dialog"`, `aria-modal`, `aria-labelledby`) with programmatic focus management and keyboard `Escape` support.
- **Consistent Styling**: Styled using existing design tokens (`--danger`, `--gray-*`) with a crisp entry animation aligned to the Skeleton Backend aesthetic.
- **No Backend Changes**: The actual `logout.php` → `AuthController::logout()` chain is unmodified; the confirmation is entirely a frontend guard.

### Security Impact Assessment
- Reduces accidental session loss with no impact on session security mechanics.

### Retesting Required
- Open/close modal via trigger, Escape key, and overlay click.
- Confirm logout proceeds normally on "Yes, Sign Out" across all roles.

---

## [v2.1.0] - 2026-04-06 - Skeleton Backend Focus
### Module Affected
Admin UI / Dashboard Utility

### Type of Change
Performance Optimization / UX Simplification

### Problem Description
The system required a transition from a "Sanctuary" aesthetic to a "Skeleton Backend" focus to improve operational speed and clear the visual path for administrators.

### Solution Implemented
- **Functional UI Revert**: Stripped glassmorphism, 4K backgrounds, and Google Font dependencies.
- **Data Density Optimization**: Simplified all page layouts (Dashboard, Members, Events, Announcements) to prioritize tables and clear form groups over complex cards and flourishes.
- **Attendance Continuity**: Retained 100% of the QR Scanner functionality while simplifying its presentation.
- **Asset Lean-out**: Redefined `:root` variables in `main.css` to use system defaults, reducing CSS load size.

### Security Impact Assessment
- No code logic changes. Maintaining all existing verification and audit trails.

### Retesting Required
- Verification of QR scanning integration with the new minimalist UI.
- Browser test across various viewport sizes.

## [v2.0.0] - 2026-04-05 - The Modern Sanctuary UI/UX 2.0
### Module Affected
Global UI / UX Design System

### Type of Change
Major Architectural / Design Overhaul

### Problem Description
The previous system lacked a premium, cohesive, and spiritually welcoming aesthetic. The user requested a "Modern Sanctuary" redesign using a specific palette: White, Red, Yellow-Gold, and Blue.

### Solution Implemented
- **Design System**: Replaced the dark theme with a Light Mode "Modern Sanctuary" aesthetic.
- **Color Palette**: 
    - Primary: Church Red (`#D22630`)
    - Accents: Yellow-Gold (`#D4AF37`) & Navy Blue (`#1E3A8A`)
    - Base: Soft Ivory (`#FDFCFA`) & Pure White.
- **Glassmorphism**: Implemented premium backdrop filters and subtle borders for sidebars, headers, and cards.
- **Typography**: Integrated Montserrat (Headings) and Outfit (Body) for a high-end feel.
- **Component Overhaul**: Redesigned all global UI elements including Buttons, Forms, Tables, Alerts, and Badges.
- **Login Experience**: Generated a custom 4K "Modern Sanctuary" background and redesigned the login card.
- **Page Redesigns**: Fully overhauled Dashboard, Members Directory, Events Calendar, Announcements Feed, and User Management.
- **Digital ID Card**: Implemented a premium dual-sided Digital Member ID with integrated QR code.

### Security Impact Assessment
- Maintained all existing RBAC and CSRF protections.
- Improved visual hierarchy for security-sensitive actions (e.g., Delete buttons).

### Retesting Required
- Verification of mobile responsiveness across all redesigned pages.
- Visual audit for color contrast and accessibility.
### Module Affected
Attendance System

### Type of Change
Feature

### Problem Description
Requirement for members to easily check in to events using a QR Code.

### Solution Implemented
- Generated unique QR tokens per member (`qr_token`).
- Added QR Code Generation endpoint using `endroid/qr-code`.
- Implemented `scan_attendance.php` feature to allow both web-camera scanning (via `html5-qrcode`) and hardware scanner (keyboard emulator) workflows inside event attendance context.
- Modified attendance logs to accept scan actions directly.

### Security Impact Assessment
- Implemented `qr_token` validation limiting token exposure instead of direct un-obfuscated primary IDs.

### Retesting Required
- Testing scanning function using test QR codes to ensure token translates correctly to Member record without causing duplicates.


## [2026-04-02] - Phase 2: Core Modules
The following modules have been implemented to support church operations:

### 1. Member Management
- Fully functional CRUD system for maintaining the congregation database.
- Input validation and duplicate prevention logic.
- Role-based permissions ensuring only authorized staff can modify member data.

### 2. Event Management
- System for creating and managing church events/services.
- Details captured: Title, Description, Date, Time, Location.
- Integrated with the attendance system.

### 3. Attendance System
- Capability to record attendance for specific events.
- Validates that a member is not recorded multiple times for the same event on the same day.
- Selective access: Only Secretaries and Committee Heads can record attendance.

### 4. Church Announcements
- Centralized communication portal.
- Allows administrators to publish updates visible to all authenticated users.
- Automatic attribution of announcements to the creator.


## [v1.0.0] - 2026-04-02
### Module Affected
System Foundation / Authentication

### Type of Change
Feature (Initial Setup)

### Problem Description
Requirement for a secure and organized foundation for the CMS.

### Solution Implemented
Implemented:
- Clean MVC-like structure.
- Database Schema and initial seed.
- Roles (Administrator, Pastor, Staff, Member).
- Initial password-base authentication logic foundation.

### Security Impact Assessment
Established password hashing best practices and role-based access baseline.

### Retesting Required
Unit testing of authentication logic once implemented.
