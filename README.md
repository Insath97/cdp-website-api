# CDP Empire API 🚀

[![Laravel 13](https://img.shields.io/badge/Laravel-v13-FF2D20?logo=laravel)](https://laravel.com)
[![PHP 8.3](https://img.shields.io/badge/PHP-v8.3-777BB4?logo=php)](https://php.net)
[![JWT Auth](https://img.shields.io/badge/Auth-JWT-000000?logo=json-web-tokens)](https://github.com/php-open-source-saver/jwt-auth)
[![RBAC-Spatie](https://img.shields.io/badge/RBAC-Spatie-blue)](https://spatie.be/docs/laravel-permission)
[![License-MIT](https://img.shields.io/badge/License-MIT-green)](https://opensource.org/licenses/MIT)

The official backend API for **CDP Empire** (cdp.lk). This API powers the learning management system, handling courses, certifications, and administrative configurations with high performance and security.

---

## ✨ Key Modules

- 🎓 **Course Management**: Robust API for managing course catalogs, images, videos, and tags.
- 📜 **Certifications**: Automated certification issuance and verification system.
- 🔐 **JWT Authentication**: Secure, stateless authentication using `php-open-source-saver/jwt-auth`.
- 🛡️ **Granular RBAC**: Full Role-Based Access Control powered by `spatie/laravel-permission`.
- ⚙️ **System Settings**: Dynamic configuration module for managing office contacts and notification toggles.
- 📁 **File Upload Trait**: Specialized handling for profile images and course media.
- ⚡ **API Versioning**: Scalable `v1` structure for frontend integration.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 13.x
- **Language**: PHP 8.3
- **Database**: MySQL/PostgreSQL/SQLite
- **Auth**: JWT (JSON Web Tokens)
- **Permissions**: Spatie Laravel Permissions

---

## 🚀 Quick Start

### 1. Installation

Clone the repository and install dependencies:

```bash
composer install
npm install
```

### 2. Environment Setup

Copy the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 3. Database Migration & Seeding

Configure your `.env` database settings, then run:

```bash
php artisan migrate --seed
```

### 4. Run the Application

Start the development server:

```bash
npm run dev
```

---

## 🏗️ Architecture Overview

The project follow a modular and scalable structure:

- **Routes**: Separated into `api.php`, `v1.php`, and `public.php` for clean organization.
- **Controllers**: Organized under `App\Http\Controllers\V1`.
- **Traits**: Shared logic (File Uploads, Activity Logs) resides in `App\Traits`.
- **Middleware**: Custom security and permission enforcement layers.

---

## 📡 API Endpoints (v1)

### Authentication
- `POST /api/v1/login` - Authenticate user and receive token.
- `POST /api/v1/logout` - Invalidate current session (Auth required).
- `GET /api/v1/me` - Get current authenticated user details (Auth required).

### User Management (Protected)
- `GET /api/v1/users` - List all users (with search/filter).
- `POST /api/v1/users` - Create a new user.
- `PATCH /api/v1/users/{id}/activate` - Activate account.
- `PATCH /api/v1/users/{id}/deactivate` - Deactivate account.

### RBAC (Protected)
- `GET /api/v1/roles` - Manage user roles.
- `GET /api/v1/permissions` - Manage system permissions.

---

## 🔒 Security Best Practices

This starter kit implements several security layers:

1. **Rate Limiting**: Throttling applied to `login` (5 attempts/min) and general `api` (60 requests/min).
2. **Input Validation**: Strict validation via `FormRequests`.
3. **Password Hashing**: Bcrypt/Argon2 hashing by default.
4. **CSRF Protection**: For cookie-based sessions.
5. **CORS Configuration**: Controlled origins in `config/cors.php`.

---

## 📜 License

The Demo API Starter Kit is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
