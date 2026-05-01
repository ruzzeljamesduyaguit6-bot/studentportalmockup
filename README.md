# Role Based System

Laravel-based role management system with custom token authentication, role-aware dashboards, admin user management, catalog setup pages, and real-time style messaging features.

## Roles

- Admin
- Student
- Professor

Admin has access to management pages and APIs. Student and Professor use role-filtered navigation and user views.

## User ID Format

The system generates role-based user IDs automatically:

- Student: S + year + space + 5-digit increment
	- Example: S2026 00001
- Professor: P + year + space + 5-digit increment
	- Example: P2026 00001
- Admin custom ID is supported (example: A6969).

## Major Features

- API token-based login/logout
- Role-aware sidebar rendering and role-specific dashboard titles
- Profile hero card with role and user metadata
- Student home User Details card (Name, Course, Year Level, GWA)
- Full User Management (search, edit modal, delete, bulk delete, pagination)
- Admin catalogs for Designations, Departments, Courses, and Subjects
- Global and Private messaging tabs without page refresh
- Emoji reactions on messages
- Cloudinary-backed storage support
- Brevo SMTP and API integration
- Forgot password flow with Brevo reset emails

## User Management (Admin)

### Table and Actions

- Search by name or user ID
- Pagination with 15 users per page
- Row-level edit and delete actions
- Multi-select checkboxes and Delete Selected action

### Add User Modal

Includes:

- Role (Student or Professor)
- Auto-generated ID preview
- Name, Birthday, Contact
- Email not auto generated from name
- Password + Confirm Password

Role-specific inputs:

- Professor: Designation, Department, Subject
- Student: Department, Course, Subject, Year Level

### Edit User Modal

- Opens from table action (modal-based, no prompt dialogs)
- Includes name, birthday, email, contact, role
- Shows role-dependent dropdowns
- Cancel and Update buttons are fully functional

### Dynamic Dropdown Sources

Designation, Department, Course, and Subject options in add/edit user modals are loaded from catalog tables via API.

## Catalog Management Pages (Admin)

Four admin pages are available in the sidebar:

- Designations
- Departments
- Courses
- Subjects

Each page includes:

- ID column for easier reference
- Total existing count indicator
- Search by name
- Table of records
- Pagination (15 records per page)
- Row action button (Delete)
- Add button with modal input form

Added records appear immediately in the table and are usable in User Management dropdowns.

Subject assignment editing also supports:

- Units input when adding a subject
- Course checkbox assignment (many-to-many subject-course mapping)

Courses page also supports:

- Assigned Subjects count column
- Total Units column (sum of units from assigned subjects)
- Edit action to update subject assignments using a checklist modal

Subjects page also supports:

- Edit action to update assigned courses using a checklist modal

## Messages Module

Messages page includes two tabs:

- Global Messages
- Private Messages

Behavior:

- Tabs switch views without full page refresh
- Global and private views are isolated by tab
- Global supports group-style conversations
- Private supports one-to-one conversations
- Private user list defaults to existing conversations
- Search in private tab finds users by name or ID
- Emoji reactions can be toggled per message

UI notes:

- Sender IDs are removed from message bubbles
- Sender icon/initial appears before sender name
- Message bubble width adapts to message length

## API Endpoints

### Authentication

- POST /api/auth/login
- POST /api/auth/logout
- GET /api/auth/me

### Dashboard

- GET /api/dashboard/data

### User Management

- GET /api/user-management/data
- GET /api/user-management/users
- POST /api/user-management/users
- PUT /api/user-management/users/{id}
- DELETE /api/user-management/users/{id}
- POST /api/user-management/users/bulk-delete

### Catalog Management

- GET /api/catalog/options
- GET /api/catalog/subjects/courses
- GET /api/catalog/courses/subjects
- GET /api/catalog/courses/{id}/subjects
- PUT /api/catalog/courses/{id}/subjects
- GET /api/catalog/subjects/{id}/courses
- PUT /api/catalog/subjects/{id}/courses
- GET /api/catalog/{type} where type = designations|departments|courses|subjects
- POST /api/catalog/{type}
- DELETE /api/catalog/{type}/{id}

### Messages

- GET /api/messages/bootstrap
- GET /api/messages/users/search
- GET /api/messages/global
- POST /api/messages/global
- GET /api/messages/private/{userId}
- POST /api/messages/private/{userId}
- POST /api/messages/{messageId}/react

## Web Routes

- /login
- /dashboard
- /admin/dashboard
- /professors/dashboard
- /users
- /messages
- /designations
- /departments
- /courses
- /subjects
- /forgot-password
- /reset-password/{token}

## Database Migrations Added

- chat_messages and message_reactions tables
- designations, departments, courses, and subjects tables
- users.subject column
- subjects.units column
- course_subject pivot table

## Local Setup

Prerequisites:

- PHP 8.2+
- Composer
- Node.js and npm
- MySQL

Install and run:

1. Install backend dependencies
	 composer install
2. Install frontend dependencies
	 npm install
3. Configure environment
	 - Copy .env.example to .env
	 - Set DB credentials
	 - Set Cloudinary and Brevo credentials
4. Generate app key
	 php artisan key:generate
5. Run migrations
	 php artisan migrate
6. Start services
	 php artisan serve
	 npm run dev

## Cloudinary Setup

Cloudinary is configured as the default storage disk.

Required environment keys:

- FILESYSTEM_DISK=cloudinary
- CLOUDINARY_CLOUD_NAME=
- CLOUDINARY_API_KEY=
- CLOUDINARY_API_SECRET=
- CLOUDINARY_SECURE=true
- CLOUDINARY_URL=

Implementation references:

- [composer.json](composer.json)
- [config/filesystems.php](config/filesystems.php)
- [config/services.php](config/services.php)
- [.env.example](.env.example)

## Brevo Setup

Brevo is used in two ways:

- SMTP relay configuration for framework-driven mail support
- Direct Brevo API integration for password reset email sending

Required environment keys:

- MAIL_MAILER=smtp
- MAIL_SCHEME=tls
- MAIL_HOST=smtp-relay.brevo.com
- MAIL_PORT=587
- MAIL_USERNAME=your_brevo_login_email@example.com
- MAIL_PASSWORD=your_brevo_smtp_key
- MAIL_FROM_ADDRESS=your_sender_email@example.com
- MAIL_FROM_NAME="${APP_NAME}"
- BREVO_API_KEY=your_brevo_api_key

Implementation references:

- [config/mail.php](config/mail.php)
- [config/services.php](config/services.php)
- [app/Http/Controllers/Auth/ForgotPasswordController.php](app/Http/Controllers/Auth/ForgotPasswordController.php)
- [.env.example](.env.example)

## Forgot Password Flow

User flow:

1. User clicks Forgot Password on login page.
2. User submits account email on forgot-password page.
3. System generates password reset token via Laravel broker.
4. System sends reset email through Brevo API.
5. User clicks reset link from inbox.
6. User submits new password on reset-password page.
7. System updates password and redirects back to login.

Routes:

- GET /forgot-password
- POST /forgot-password
- GET /reset-password/{token}
- POST /reset-password

Implementation references:

- [routes/web.php](routes/web.php)
- [resources/views/auth/login.blade.php](resources/views/auth/login.blade.php)
- [resources/views/auth/forgot-password.blade.php](resources/views/auth/forgot-password.blade.php)
- [resources/views/auth/reset-password.blade.php](resources/views/auth/reset-password.blade.php)
- [app/Http/Controllers/Auth/ForgotPasswordController.php](app/Http/Controllers/Auth/ForgotPasswordController.php)

## Seeded / Known Accounts

- admin@example.com / password
- user@example.com / password
- juan.delacruz@example.com / password

## Important Files

- [resources/views/admin/dashboard.blade.php](resources/views/admin/dashboard.blade.php)
- [resources/views/admin/users.blade.php](resources/views/admin/users.blade.php)
- [resources/views/admin/messages.blade.php](resources/views/admin/messages.blade.php)
- [resources/views/admin/designations.blade.php](resources/views/admin/designations.blade.php)
- [resources/views/admin/departments.blade.php](resources/views/admin/departments.blade.php)
- [resources/views/admin/courses.blade.php](resources/views/admin/courses.blade.php)
- [resources/views/admin/subjects.blade.php](resources/views/admin/subjects.blade.php)
- [resources/js/user-management-loader.js](resources/js/user-management-loader.js)
- [resources/js/messages-loader.js](resources/js/messages-loader.js)
- [resources/js/catalog-management-loader.js](resources/js/catalog-management-loader.js)
- [resources/css/views.css](resources/css/views.css)
- [app/Http/Controllers/UserManagementController.php](app/Http/Controllers/UserManagementController.php)
- [app/Http/Controllers/MessageController.php](app/Http/Controllers/MessageController.php)
- [app/Http/Controllers/CatalogManagementController.php](app/Http/Controllers/CatalogManagementController.php)
- [app/Http/Controllers/Auth/ForgotPasswordController.php](app/Http/Controllers/Auth/ForgotPasswordController.php)
- [resources/views/auth/login.blade.php](resources/views/auth/login.blade.php)
- [resources/views/auth/forgot-password.blade.php](resources/views/auth/forgot-password.blade.php)
- [resources/views/auth/reset-password.blade.php](resources/views/auth/reset-password.blade.php)
- [resources/css/login.css](resources/css/login.css)
- [routes/api.php](routes/api.php)
- [routes/web.php](routes/web.php)
- [instruction.md](instruction.md)
- [ACTIVITY_LOG.md](ACTIVITY_LOG.md)

## Notes

- User email is generated server-side from submitted name and kept unique.
- User code generation for student/professor is handled server-side.
- Catalog tables are now the source of truth for designation/department/course/subject dropdown values.

## Recent Updates (2026-04-19)

- Dashboard: user details panel now binds to Name, Course, Year Level, and GWA fields for student home.
- Catalog tables: ID column added for Courses, Subjects, and Departments.
- Courses table: now shows Assigned Subjects and Total Units, plus edit-assignments checklist modal.
- Subjects table: edit-assignments checklist modal added for assigned courses.

## Recent Updates (2026-04-20)

- Cloudinary integration added and configured as default filesystem disk.
- Brevo SMTP and API credentials added to environment template and services config.
- Forgot password flow implemented with Brevo API reset email delivery.