# ARCHITECTURE DOCUMENTATION

## [v2.1.0] - 2026-04-06 - Skeleton Backend Focus
The system has shifted its design philosophy from an aesthetically-rich "Modern Sanctuary" to a performance-oriented "Skeleton Backend Focus". This architectural decision prioritizes administrative efficiency over visual flourishes.

### 1. Minimalist Presentation Layer
The UI has been stripped of complex glassmorphism effects, heavy CSS filters, and external typography dependencies.
- **Performance**: Instant page loads facilitated by reduced CSS complexity and zero external asset requests (Google Fonts).
- **Data Density**: Transitioned to high-contrast, compact table-based layouts for member and event management.
- **Admin Focus**: Neutral color palette (Gray/Navy/White) minimizes distraction and improves visual endurance for long-duration management sessions.

### 2. Functional Component System
Replaced the "Modern Sanctuary" component system with a lean, utility-first CSS framework.
- **Cards**: Simplified to flat borders and clear headers.
- **Buttons**: Replaced gradients and shadows with high-contrast, flat focus styles.
- **Modals**: Lightweight, instant-activation overlays without heavy transition animations.

### 3. Core Feature Immersion
Despite the UI simplification, the core **QR Attendance System** remains the centerpiece. The "Skeleton" approach surrounds the functional scanner with a distraction-free environment to ensure maximum focus on scanning operations.

## [2026-04-02] - Architectural Evolution (Phase 2)
The system has transitioned from a basic procedural-like MVC to a more structured Model-Controller-View (MCV) pattern to improve scalability and maintainability.

### 1. Model Layer (`/models`)
The Model layer abstracts all database logic using PDO. Each entity (Member, Event, Attendance, Announcement) has its own class providing consistent data access methods.
- Separates database queries from business logic.
- Ensures consistent security via prepared statements in one location.

### 2. Controller Layer (`/controllers`)
Controllers handle application logic, validation, and role-based authorization.
- Uses `AuthMiddleware` to enforce granular role permissions (Secretary, Committee Head, Staff).
- Leverages models for data operations.

### 3. View Layer (`/views`)
Views are simplified to handle presentation and user input.
- Communicates exclusively with Controllers.
- Integrated CSRF protection for all modify-capable forms.

### 4. Routing (`/public/index.php`)
A centralized entry point handles URL routing and initialization, ensuring that sensitive directories (`config`, `models`, `controllers`) are not directly accessible.


## [v1.0.0] - 2026-04-02
### Module Affected
System Foundation / Authentication

### Type of Change
Feature (Initial Setup)

### Problem Description
Requirement for a secure and organized foundation for the CMS.

### Solution Implemented
Implemented a clean, modular MVC-like structure for organization:
- `/config`: Database connection and global settings.
- `/controllers`: Request processing and logic handling.
- `/models`: Data interaction layers.
- `/views`: Plain HTML user interface.
- `/middleware`: Authentication checks and security (CSRF/RBAC).
- `/public`: Web server entry point (`index.php`).

### Security Impact Assessment
Design includes explicit separation of middleware for security, ensuring all protected routes can be gated consistently.

### Deployment Impact
Structure requires `public/` to be the document root or handled via `.htaccess`.
