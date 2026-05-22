# Vehicle Registration System - Laravel Backend

A clean, production-ready Laravel backend for the Vehicle Registration System, migrated from legacy PHP with exact schema preservation.

---

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Hash admin password (if importing SQL dump)
php artisan tinker
>>> $admin = \App\Models\Admin::find(1);
>>> $admin->password = bcrypt(env('SEED_ADMIN_PASSWORD'));
>>> $admin->save();
>>> exit

# Start development server
php artisan serve
```

Visit: `http://localhost:8000`

---

## 📋 Features

### Authentication System
- **Multi-guard authentication** (Applicants + Admins)
- **Session-based** with configurable timeout
- **Role-based access control** (Student, Staff, Guest, Admin)
- **Legacy URL compatibility** (all `.php` URLs preserved)

### User Management
- Student registration with 6-digit reg numbers
- Staff registration with formatted IDs (e.g., AU2025-0456)
- Guest accounts
- License information tracking

### Vehicle Management
- Vehicle registration with owner details
- Plate number tracking
- Disk number assignment
- Status management (active/inactive)
- Registration history

### Authorized Drivers
- Multiple drivers per vehicle
- License number and contact tracking
- CRUD operations via API

### Admin Features
- User management
- Vehicle status control
- Report generation with categories
- Global notifications
- Search logs tracking

---

## 🗄️ Database Schema

### Tables (11 Total)

1. **applicants** - Users with license information
2. **vehicles** - Vehicle registration records
3. **authorized_driver** - Authorized drivers
4. **admins** - Admin users
5. **admin_reports** - Reports with categories
6. **notifications** - Global system notifications
7. **search_logs** - Vehicle search history
8. **password_reset_tokens** - Password reset tokens
9. **colleges** - College/department list
10. **unregistered_plates** - Unregistered plate logs
11. **registration_drafts** - Draft registrations
12. **personal_access_tokens** - Sanctum API tokens

---

## 🔐 Authentication

### Login Endpoints

**Admin Login:**
- URL: `/admin-login.php` or `/admin/login`
- Credentials: username + password
- No default password is shipped. Set `SEED_ADMIN_PASSWORD` before seeding or create an admin manually.

**User Login:**
- URL: `/login.php` or `/login`
- Credentials: studentRegNo/staffsRegNo/email + password + userType
- Types: `student`, `staff`, `guest`

### Guards

- `web` → Applicants (session-based)
- `admin` → Admins (session-based)

### Middleware

- `auth.user` → Protects user routes
- `auth.admin` → Protects admin routes

---

## 📡 API Routes

### Public Routes
```
GET  /login.php              - User login form
POST /login.php              - User login
GET  /admin-login.php        - Admin login form
POST /admin-login.php        - Admin login
GET  /logout.php             - User logout
GET  /admin-logout.php       - Admin logout
```

### User Routes (auth.user middleware)
```
GET  /user-dashboard.php              - User dashboard
GET  /vehicle-registration-form.php   - Vehicle registration form
POST /register_vehicle.php            - Submit vehicle registration
GET  /vehicle-list.php                - List user vehicles
GET  /vehicle-details.php             - Vehicle details
GET  /search-vehicle.php              - Search vehicles
POST /vehicle_operations.php          - Update vehicle
DELETE /vehicle_operations.php        - Delete vehicle

POST /driver_operations.php           - Add driver
PUT  /driver_operations.php/{id}      - Update driver
DELETE /driver_operations.php/{id}    - Delete driver

GET  /get_notifications.php           - Get notifications
POST /mark_notification_read.php/{id} - Mark notification read
```

### Admin Routes (auth.admin middleware)
```
GET  /admin-dashboard.php          - Admin dashboard
GET  /owner-list.php               - List all owners
GET  /owner-details.php            - Owner details
GET  /edit-owner.php               - Edit owner form
POST /update-owner-info.php        - Update owner

GET  /admin-users.php              - User management
GET  /view_user.php                - View user details
POST /update_user.php              - Update user status
DELETE /delete_user.php            - Delete user

GET  /admin_reports.php            - List reports
GET  /edit_report.php              - Edit report form
POST /edit_report.php              - Update report
DELETE /delete_report.php          - Delete report
```

### Modern Laravel Routes
All legacy `.php` URLs also have clean modern equivalents:
- `/login`, `/dashboard`, `/vehicles`, `/admin/dashboard`, etc.

---

## 🏗️ Project Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DriverController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── OwnerController.php
│   │   │   ├── ReportController.php
│   │   │   ├── UserController.php
│   │   │   └── VehicleController.php
│   │   └── Middleware/
│   │       ├── EnsureUserAuthenticated.php
│   │       └── EnsureAdminAuthenticated.php
│   └── Models/
│       ├── Admin.php
│       ├── Applicant.php
│       ├── AuthorizedDriver.php
│       ├── College.php
│       ├── Notification.php
│       ├── RegistrationDraft.php
│       ├── Report.php
│       ├── SearchLog.php
│       ├── UnregisteredPlate.php
│       └── Vehicle.php
├── config/
│   └── auth.php (Multi-guard configuration)
├── database/
│   └── migrations/ (11 custom migrations)
├── routes/
│   └── web.php (Legacy + modern routes)
└── Documentation/
    ├── FINAL_SETUP.md
    ├── LARAVEL_MIGRATION_MAP.md
    ├── MIGRATION_COMPLETE.md
    └── SETUP_GUIDE.md
```

---

## 🔧 Configuration

### Environment Variables

```env
APP_NAME="Vehicle Registration System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vehicleregistrationsystem
DB_USERNAME=root
DB_PASSWORD=

SESSION_LIFETIME=120
SESSION_DRIVER=file
```

### Database Connection

Update `.env` with your MySQL credentials. The system uses:
- Database: `vehicleregistrationsystem`
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`

---

## 📦 Installation

### Requirements
- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Apache/Nginx

### Step-by-Step

1. **Clone/Navigate to project**
   ```bash
   cd backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Create database**
   ```sql
   CREATE DATABASE vehicleregistrationsystem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed admin user** (or import SQL dump)
   ```bash
   php artisan tinker
   ```
   ```php
   // Ensure SEED_ADMIN_PASSWORD is set in .env first
   \App\Models\Admin::create([
       'username' => env('SEED_ADMIN_USERNAME', 'admin'),
       'password' => bcrypt(env('SEED_ADMIN_PASSWORD'))
   ]);
   exit
   ```

8. **Start server**
   ```bash
   php artisan serve
   ```

---

## 📥 Importing Existing Data

If you have an existing SQL dump:

```bash
# Option 1: Import before migrations
mysql -u root -p vehicleregistrationsystem < path/to/dump.sql

# Then hash passwords
php artisan tinker
>>> $admin = \App\Models\Admin::find(1);
>>> $admin->password = bcrypt(env('SEED_ADMIN_PASSWORD'));
>>> $admin->save();

# Option 2: Run migrations first, then import
php artisan migrate
mysql -u root -p vehicleregistrationsystem < path/to/dump.sql
```

---

## 🧪 Testing

### Run Tests
```bash
php artisan test
```

### Test Authentication
```bash
# Test user login
curl -X POST http://localhost:8000/login.php \
  -d "identifier=230518&password=yourpass&userType=student"

# Test admin login
curl -X POST http://localhost:8000/admin-login.php \
  -d "username=admin&password=<your-admin-password>"
```

---

## 🛠️ Development

### Artisan Commands

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Database
php artisan migrate:fresh      # Fresh migration
php artisan migrate:rollback   # Rollback last migration
php artisan db:seed            # Run seeders

# Tinker (REPL)
php artisan tinker
```

### Adding Blade Views

Create views in `resources/views/`:
```
resources/views/
├── auth/
│   ├── login.blade.php
│   └── admin-login.blade.php
├── dashboard/
│   ├── user.blade.php
│   └── admin.blade.php
├── vehicles/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── show.blade.php
└── layouts/
    └── app.blade.php
```

---

## 📝 Business Logic

### Vehicle Registration Rules

**Students:**
- Maximum 1 active vehicle
- New registration auto-deactivates others
- Status set to `active` immediately

**Staff:**
- Maximum 5 vehicles
- Status set to `inactive` (requires admin approval)

**Guests:**
- Read-only access
- Cannot register vehicles

### Validation Rules

- **Student Reg No:** 6 digits (e.g., `230518`)
- **Staff Reg No:** Format `AU2025-0456`
- **Plate Number:** Alphanumeric (e.g., `ABC 123`)
- **Email:** Valid email format
- **License Number:** Integer (authorized drivers)

---

## 🔒 Security

- Passwords hashed with bcrypt
- CSRF protection enabled
- Session-based authentication
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade templating)
- Input validation on all forms

---

## 📚 Documentation

Detailed documentation available in:
- **FINAL_SETUP.md** - Complete setup guide
- **MIGRATION_COMPLETE.md** - Schema corrections applied
- **LARAVEL_MIGRATION_MAP.md** - Legacy to Laravel mapping
- **SETUP_GUIDE.md** - Installation instructions

---

## 🐛 Troubleshooting

### Migration Errors
```bash
php artisan migrate:fresh
```

### Permission Issues
```bash
chmod -R 775 storage bootstrap/cache
```

### Database Connection
- Verify MySQL is running
- Check `.env` credentials
- Ensure database exists

### Clear All Caches
```bash
php artisan optimize:clear
```

---

## 📄 License

This project is proprietary software for the Vehicle Registration System.

---

## 👥 Support

For issues or questions:
1. Check documentation files
2. Review Laravel logs: `storage/logs/laravel.log`
3. Verify `.env` configuration
4. Ensure all migrations ran successfully

---

## ✅ Status

**Phase 1: Complete** ✅
- All migrations created
- All models with relationships
- All controllers with business logic
- Multi-guard authentication
- Legacy URL compatibility
- Clean backend structure

**Next: Create Blade Views**
- Convert legacy PHP views to Blade templates
- Implement UI matching legacy system
- Test all workflows

---

**Version:** 1.0.0  
**Laravel Version:** 10.x  
**PHP Version:** 8.1+  
**Database:** MySQL 5.7+
