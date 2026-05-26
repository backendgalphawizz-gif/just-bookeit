# Just Book IT — Local Setup

Admin panel backend built with **Laravel 12 (PHP)**, **MySQL**, and **Tailwind CSS**, aligned with the Alphawizz project proposal (User/Vendor Flutter apps + PHP website + PHP admin panel).

## Prerequisites

- PHP 8.2+
- Composer
- MySQL 8+ (database: `justbookit`)
- Node.js 20+

## 1. Environment

Copy `.env.example` to `.env` if needed, then configure MySQL:

```env
APP_NAME="Just Book IT"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=justbookit
DB_USERNAME=root
DB_PASSWORD=your_password
```

Generate app key (if missing):

```powershell
php artisan key:generate
```

## 2. Database

Create the MySQL database:

```sql
CREATE DATABASE justbookit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run migrations and seed default admin + RBAC roles:

```powershell
php artisan migrate
php artisan db:seed
```

## 3. Frontend assets

```powershell
npm install
npm run build
```

For development with hot reload:

```powershell
npm run dev
```

## 4. Run the application

**Option A — PHP server only**

```powershell
php artisan serve
```

**Option B — Full dev stack** (server + queue + logs + Vite)

```powershell
composer run dev
```

## 5. Admin CRUD modules

| Module | URL | Features |
|--------|-----|----------|
| Customers | `/admin/customers` | List, search, create, edit, view, delete |
| Vendors | `/admin/vendors` | CRUD + approve / reject / suspend |
| Categories | `/admin/categories` | CRUD (main & service types) |
| Orders | `/admin/orders` | CRUD + filters by status, vendor, date |
| Payments | `/admin/payments` | List & view (from orders) |
| Refunds | `/admin/refunds` | CRUD + status workflow |
| Disputes | `/admin/disputes` | CRUD + resolution statuses |
| Banners | `/admin/banners` | CRUD + scheduling |

Sidebar links and actions respect **RBAC** (role permissions).

## 6. Access admin panel

| URL | http://127.0.0.1:8000/admin/login |
| Email | `admin@justbookit.com` |
| Username | `superadmin` |
| Password | `password` |

Change the default password after first login in production.

## Project scope (from proposal)

| Component | Technology | Status |
|-----------|------------|--------|
| Admin Panel | Laravel / PHP / MySQL | Phase 1 — Auth & dashboard scaffolded |
| User Website | PHP | Not in this repo |
| User App | Flutter | Separate repo |
| Vendor App | Flutter | Separate repo |
| Delivery App | Flutter | Separate repo |

See `docs/ADMIN_MODULES.md` for the 18 admin modules and implementation order.

## Common commands

```powershell
php artisan migrate:fresh --seed   # Reset DB
php artisan route:list --path=admin
php artisan test
```
