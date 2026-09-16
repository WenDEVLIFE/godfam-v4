# Pending Tasks & Revisions Specification

This document details the pending revision tasks for the God's Family United Methodist Church Web & Mobile Management System.

---

## Task Breakdown & Implementation Roadmap - TODO

### 1. Church Background Image
- **Description**: Add a responsive, high-resolution church background image with modern dark overlay/glassmorphism styling on authentication screens (login, forgot password, reset password) and dashboard headers.
- **Affected Files**: `public/login.php`, `public/forgot_password.php`, `public/assets/css/style.css`.
- **Status**: Planned.

### 2. Privacy Statement Modal Before Submission - TODO
- **Description**: Display a mandatory Privacy Statement & Data Protection Consent modal before completing member registration, login checks, or data forms.
- **Affected Files**: `public/login.php`, `public/members.php`, `public/assets/js/app.js`.
- **Status**: Planned.

### 3. Forgot Email & OTP Recovery Flow - TODO
- **Description**: Provide account lookup by phone/name for forgotten emails, alongside the existing email OTP password reset flow.
- **Affected Files**: `public/forgot_email.php`, `public/forgot_password.php`, `public/verify_otp.php`, `controllers/AuthController.php`.
- **Status**: Planned.

### 4. Interactive & Clickable Dashboard - TODO
- **Description**: Make all dashboard stat cards (Total Members, Upcoming Events, Attended Today, Collection widgets) directly clickable to navigate to their corresponding detailed management views.
- **Affected Files**: `public/dashboard.php`.
- **Status**: Planned.

### 5. File & Image Upload Validation (PNG, JPEG) - TODO
- **Description**: Enforce strict server-side & client-side validation for photo uploads, accepting only valid image MIME types (`image/jpeg`, `image/png`, `image/jpg`) with maximum file size limits (e.g. 5MB).
- **Affected Files**: `public/members.php`, `public/profile.php`, `utils/FileUpload.php`.
- **Status**: Planned.

### 6. Default Phone Number Prefix (+63) - TODO
- **Description**: Set default Philippine phone number prefix `+63` with formatting helpers across all phone input fields.
- **Affected Files**: `public/members.php`, `public/profile.php`.
- **Status**: Planned.

### 7. Automatic Member Status Recalculation - TODO
- **Description**: Recalculate member status (Active / Inactive) automatically based on consecutive event absences.
- **Affected Files**: `models/Member.php`, `models/Attendance.php`.
- **Status**: Planned.

### 8. Full Digital Member ID Card Generator - TODO
- **Description**: Generate printable and downloadable whole digital ID cards featuring member details, photo, QR token, and church branding.
- **Affected Files**: `public/generate_id_card.php`, `models/Member.php`.
- **Status**: Planned.

### 9. Decouple Events & Member Management - TODO
- **Description**: Ensure complete separation between member directory features and event scheduling/management logic.
- **Affected Files**: `public/members.php`, `public/events.php`.
- **Status**: Planned.

### 10. Event Delete Confirmation with Admin Password Re-verification - TODO
- **Description**: Require re-entering the logged-in administrator's password in a modal confirmation before permanently deleting any church event.
- **Affected Files**: `public/events.php`, `controllers/EventController.php`.
- **Status**: Planned.
 
### 11. System Admin Name Display - TODO
- **Description**: Dynamically render the authenticated administrator/user's full name across header banners, navigation bars, and generated PDF/Excel reports.
- **Affected Files**: `public/layout/header.php`, `public/dashboard.php`, `public/attendance_export.php`.
- **Status**: Planned.

### 12. Protected System Roles (Secretary & Core Roles) - TODO
- **Description**: Prevent editing, deleting, or modifying system-defined roles (Administrator, Pastor, Secretary, Staff) in role management settings.
- **Affected Files**: `public/roles.php`, `models/Role.php`.
- **Status**: Planned.

### 13. Birthday, Anniversary & Church Event Reminders / Greetings - TODO
- **Description**: Automated & manual trigger for birthday greetings, wedding anniversary reminders, and upcoming church event notifications.
- **Affected Files**: `utils/birthday_reminder.php`, `public/api/trigger_birthday_reminder.php`, `models/Notification.php`.
- **Status**: In Progress / Enhanced.

### 14. Comprehensive Audit Log & Login Reports - TODO
- **Description**: Track all authentication attempts (logins, logouts, failed attempts), IP addresses, user agents, and administrative actions with detailed audit log reporting views.
- **Affected Files**: `public/audit_logs.php`, `models/AuditLog.php`.
- **Status**: Planned.

### 15. Security Hashing & CSRF Protection - TODO
- **Description**: Maintain strong `PASSWORD_DEFAULT` (BCRYPT) hashing, secure random tokens for QR/OTP, and universal CSRF token verification across all POST forms.
- **Affected Files**: `middleware/CSRF.php`, `controllers/AuthController.php`.
- **Status**: Implemented / Active.
 
### 16. Mobile Adaptive Design & PWA / App Manifest - TODO
- **Description**: Ensure fully responsive layout across mobile screens, add web app manifest (`manifest.json`) and service worker (`sw.js`) for PWA installability on mobile devices.
- **Affected Files**: `public/manifest.json`, `public/sw.js`, `public/assets/css/style.css`, `public/layout/header.php`.
- **Status**: Planned.

### 17. CAPTCHA Protection   - TODO
- **Description**: Integrate visual/algebraic CAPTCHA verification on login and public authentication forms to prevent automated brute-force attacks.
- **Affected Files**: `public/captcha.php`, `public/login.php`, `public/forgot_password.php`.
- **Status**: Planned.
