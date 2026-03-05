# IGA Migration to MyHR Portal (MVC & RBAC)

This document outlines the high-level plan for migrating the IGA (Individual Gap Assessment) application from its current structure to the MVC architecture within the `MyHR` portal, located at `/srv/myhr/dev/Modules/IGA`.

## 1. Goal Description

The objective is to rewrite the IGA system to integrate fully with the `MyHR Portal Framework`. This entails:
- Retaining all existing business logic.
- Adopting the framework's MVC (Model-View-Controller) pattern.
- Updating the UI/UX to match the portal's design system.
- Replacing the current hardcoded Role-Based Access Control (RBAC) with the portal's dynamic RBAC system.

## 2. Current Architecture vs Target Architecture

**Current (Legacy IGA)**:
- Procedural/script-based PHP (`public/admin/*.php`, `public/user/*.php`).
- Direct SQL queries scattered in views/scripts.
- Hardcoded roles (e.g., `has_role('admin')`, `has_role('editor')`).
- Custom header/footer includes.

**Target (MyHR Portal - `Modules/IGA`)**:
- **Controllers** (`/srv/myhr/dev/Modules/IGA/Controllers/`): Handle HTTP requests, routing logic, and RBAC authorization checks.
- **Models** (`/srv/myhr/dev/Modules/IGA/Models/`): Encapsulate domain logic and database interactions using the portal's ORM/Query Builder.
- **Views** (`/srv/myhr/dev/Modules/IGA/Views/`): Presentation layer utilizing the portal's templating engine (or layout structure).
- **Routing** (`/srv/myhr/dev/Modules/IGA/index.php` or `main.php`): Module specific routing.

## 3. Migration Strategy (Phase by Phase)

### Phase 1: Foundation & RBAC Integration
1. **Module Initialization**: Set up the entry points (`index.php`, `main.php`) and register the IGA module within the portal.
2. **Database Models Setup**: Create Model classes for core entities:
   - `User`, `Test`, `Question`, `Option`, `UserAttempt`, `Report`, etc.
3. **RBAC Mapping**: Define the necessary permissions/capabilities in the portal's RBAC system (e.g., `manage_tests`, `view_reports`, `take_tests`) instead of checking `role_id` directly.

### Phase 2: User Interface (Frontend)
1. **Dashboard & Test Listing**: Migrate `dashboard.php` logic to a `UserController` and `dashboard` view. Ensure test visibility adheres to RBAC and target audience rules.
2. **Taking Tests**: Refactor `take_test.php`, `save_answer.php`, and `submit_test.php` into a dedicated `TestController` to handle the test-taking flow via AJAX/Form submissions securely.
3. **Test History & Results**: Migrate `test_history.php` and `view_results.php` using the new Models.

### Phase 3: Admin Backend Interface
1. **Test Management (CRUD)**: Create `AdminTestController` to handle creation, editing, publishing, and configuration of tests.
2. **Question Management**: Build views/controllers for managing sections, questions (MC, TF, Short Answer), and random logic.
3. **Reporting & Analytics**: Replicate the logic from `view_reports.php` and export functionalities (`export_individual_report_pdf.php`, Excel exports) using the new structure.

### Phase 4: Data Migration & Refinement
1. **Design Overhaul**: Apply modern CSS/JS frameworks supported by the portal to modernize the legacy views.
2. **Logic Extraction**: Ensure all complex queries (like the multi-join statements seen in reports or dashboards) are abstracted cleanly into Model methods.
3. **Language/I18N**: Migrate hardcoded language files (`/languages/*.php`) into the portal's translation system if applicable.

## 4. Key Considerations for Refactoring

- **Database Connections**: Transition from using `$conn` (global `mysqli`) to the portal's database abstraction layer.
- **Session Management**: Migrate `$_SESSION` usage to the portal's session handling mechanism.
- **Security Check**: Enforce CSRF protection and centralized input validation, standard in MVC frameworks.

## 5. Next Steps for Implementation

1. **Review Portal Framework**: Understand the specific routing, Model conventions, and RBAC API of the `MyHR` framework located at `/srv/myhr/dev/`.
2. **Setup Base Controllers/Models**: Create skeleton files in `Modules/IGA/Controllers` and `Modules/IGA/Models`.
3. **Map Old Routes to New Routes**: Create a routing table mapping old URLs (e.g., `/admin/manage_tests.php`) to new Controller actions (e.g., `IGA\Controllers\AdminTestController::index`).
