# Activity Log

## 2026-04-19

- Added course-subject assignment management with bidirectional checklist editing:
  - Courses: edit assigned subjects
  - Subjects: edit assigned courses
- Added aggregate course metrics in the courses table:
  - Assigned Subjects count
  - Total Units
- Added ID columns to catalog tables:
  - Courses
  - Subjects
  - Departments
- Updated subjects add flow to include:
  - Units input
  - Course checklist assignment
- Updated dashboard student home details card bindings:
  - Name
  - Course
  - Year Level
  - GWA (fallback to "Not available")
- Updated project documentation in README.md.

## 2026-04-20

- Integrated Cloudinary as the default filesystem disk for file storage.
  - Added Cloudinary package dependency.
  - Added Cloudinary disk settings in config filesystems.
  - Added Cloudinary service credentials mapping in config services.
  - Added Cloudinary environment keys to env example.
- Integrated Brevo for email delivery and API-based messaging support.
  - Configured Brevo SMTP defaults in env example.
  - Added Brevo API key mapping in config services.
- Implemented full forgot password flow using Brevo API.
  - Added Forgot Password link on login page.
  - Added forgot-password request page (email input).
  - Added reset-password page (new password and confirmation).
  - Added web routes for forgot-password and reset-password.
  - Added Auth ForgotPasswordController to:
    - request password reset links,
    - send reset emails through Brevo API,
    - process password reset submission.
- Verified route registration for forgot-password and reset-password endpoints.
- Cleared and reloaded Laravel config to validate Cloudinary and Brevo runtime config.
