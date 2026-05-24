# نظام الغسيل (Washing System)

Arabic washing-machine management system built with Laravel 10 and Filament 3.

## Features

- **Customers** — register customers with opening balance (الرصيد الافتتاحي) only at signup
- **Wash orders** — worker enters laundry weight (kg); cost = weight × price per kilo with minimum charge
- **Payments** — record customer payments and balance tracking
- **SMS notifications** — configurable messages sent manually from each wash order row
- **Field PWA** — offline-capable mobile app for field staff (`/field-app`)
- **Admin panel** — Filament dashboard at `/admin` with roles and permissions (Filament Shield)

## Requirements

- PHP 8.1+
- MySQL / MariaDB
- Composer

## Setup

```bash
cd E:\laravel_project\washing-system
composer install
copy .env.example .env
php artisan key:generate
```

Create the database:

```sql
CREATE DATABASE washing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configure `.env`:

```env
APP_NAME="نظام الغسيل"
DB_DATABASE=washing_system
```

Run migrations and seed:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

## Access

| URL | Description |
|-----|-------------|
| http://localhost:8000/admin | Admin panel |
| http://localhost:8000/field-app | Field mobile app |

### Test accounts

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | admin123 | super_admin |
| admin2@example.com | admin123 | admin |
| editor@example.com | editor123 | editor |
| viewer@example.com | viewer123 | viewer |

## Documentation

See [README_INDEX.md](README_INDEX.md) for the full documentation index.

## License

MIT
