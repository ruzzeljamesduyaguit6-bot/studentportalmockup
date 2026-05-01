# Detailed Setup Instructions

This document provides the full setup process for local development, Cloudinary storage integration, and Brevo email integration, including forgot-password reset flow testing.

## 1) Prerequisites

- PHP 8.2 or newer
- Composer
- Node.js and npm
- MySQL (or another supported database)
- A Cloudinary account
- A Brevo account

## 2) Install Project Dependencies

From the project root:

1. Install PHP dependencies

   composer install

2. Install frontend dependencies

   npm install

## 3) Configure Environment File

1. Copy environment template

   copy .env.example .env

2. Set base app values

   - APP_NAME
   - APP_URL (must match your local URL, including port if used)
   - APP_ENV
   - APP_DEBUG

3. Set database credentials

   - DB_CONNECTION
   - DB_HOST
   - DB_PORT
   - DB_DATABASE
   - DB_USERNAME
   - DB_PASSWORD

## 4) Configure Cloudinary

Cloudinary is used as the default storage backend.

Set these values in .env:

- FILESYSTEM_DISK=cloudinary
- CLOUDINARY_CLOUD_NAME=your_cloud_name
- CLOUDINARY_API_KEY=your_api_key
- CLOUDINARY_API_SECRET=your_api_secret
- CLOUDINARY_SECURE=true
- CLOUDINARY_URL=cloudinary://your_api_key:your_api_secret@your_cloud_name

Related implementation files:

- config/filesystems.php
- config/services.php
- composer.json

## 5) Configure Brevo

Brevo is used for SMTP settings and for direct API email sends in forgot-password flow.

Set these values in .env:

- MAIL_MAILER=smtp
- MAIL_SCHEME=tls
- MAIL_HOST=smtp-relay.brevo.com
- MAIL_PORT=587
- MAIL_USERNAME=your_brevo_login_email@example.com
- MAIL_PASSWORD=your_brevo_smtp_key
- MAIL_FROM_ADDRESS=your_sender_email@example.com
- MAIL_FROM_NAME="${APP_NAME}"
- BREVO_API_KEY=your_brevo_api_key

Related implementation files:

- config/mail.php
- config/services.php
- app/Http/Controllers/Auth/ForgotPasswordController.php

## 6) Run Laravel Setup

1. Generate app key

   php artisan key:generate

2. Run migrations

   php artisan migrate

3. Clear config cache after env changes

   php artisan config:clear

## 7) Run the Application Locally

Use two terminals:

1. Backend

   php artisan serve

2. Frontend assets

   npm run dev

Open login page in browser:

- http://localhost:8000/login

If your app is served from another URL or port, update APP_URL to match.

## 8) Forgot Password Flow (End-to-End)

1. Open login page.
2. Click Forgot Password?.
3. Enter a registered user email and submit.
4. Check inbox for reset email sent through Brevo API.
5. Click the reset link from the email.
6. Enter new password and confirmation.
7. Submit and verify redirect back to login page.

Routes involved:

- GET /forgot-password
- POST /forgot-password
- GET /reset-password/{token}
- POST /reset-password

Core files involved:

- routes/web.php
- resources/views/auth/login.blade.php
- resources/views/auth/forgot-password.blade.php
- resources/views/auth/reset-password.blade.php
- app/Http/Controllers/Auth/ForgotPasswordController.php

## 9) Quick Validation Checklist

- Cloudinary package exists in composer dependencies.
- FILESYSTEM_DISK is set to cloudinary.
- Brevo API key is present in environment.
- Forgot-password routes appear in route list.
- Reset email arrives in inbox and contains valid reset link.

Helpful command:

- php artisan route:list --path=forgot-password
- php artisan route:list --path=reset-password

## 10) Security and Operations Notes

- Never commit real .env credentials to source control.
- Rotate Brevo and Cloudinary keys if they were exposed.
- Keep APP_DEBUG=false in production.
- Use HTTPS in production and update APP_URL accordingly.
- Keep MAIL_FROM_ADDRESS aligned with your Brevo verified sender/domain.

## 11) Troubleshooting

1. Reset email is not received

- Confirm BREVO_API_KEY in .env.
- Confirm sender email/domain is verified in Brevo.
- Check Laravel logs in storage/logs.

2. Reset link points to wrong host/port

- Set APP_URL to the exact local URL and clear config cache.

3. Storage uploads fail

- Recheck Cloudinary credentials and CLOUDINARY_URL format.
- Ensure FILESYSTEM_DISK=cloudinary.

4. Env changes do not apply

- Run php artisan config:clear.

5. Frontend does not load updated pages

- Make sure npm run dev is running and Vite is healthy.
