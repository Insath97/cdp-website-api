# CDP Empire API 🚀

The official backend API for **CDP Empire** (cdp.lk). This is a proprietary system used for internal operations and management.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 13.x
- **Language**: PHP 8.3
- **Database**: MySQL/PostgreSQL
- **Auth**: JWT (JSON Web Tokens)

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

## 🔒 Security

This API implements industry-standard security practices, including:
- JWT Stateless Authentication
- Granular Role-Based Access Control (RBAC)
- Rate Limiting and Brute-force protection
- Secure Input Validation

---

## 📑 License

Copyright © 2026 CDP Empire. All rights reserved. 
This software is proprietary and confidential. Unauthorized copying, modification, or distribution is strictly prohibited.
