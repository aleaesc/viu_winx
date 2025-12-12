# Quick Start for Collaborators

If you cloned this repo and need to get it running locally, follow these steps:

## Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ (optional, for asset compilation)

## Setup Steps

### 1. Clone and install dependencies
```powershell
git clone https://github.com/aleaesc/viu_winx.git
cd viu_winx
composer install
```

### 2. Configure environment
```powershell
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure database in .env
Edit `.env` and set your MySQL credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=viu_winx
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Create database
In MySQL:
```sql
CREATE DATABASE viu_winx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run migrations (this creates admin accounts automatically)
```powershell
php artisan migrate
```

**Important**: This step creates the admin accounts. Without running migrations, you cannot log in.

### 6. Verify admin accounts were created
```powershell
php artisan tinker
App\Models\User::count();  # Should return 4
exit
```

### 7. Start development server
```powershell
php artisan serve
```

Visit: `http://127.0.0.1:8000/admin`

## Admin Credentials (Auto-created by Migration)

| Username | Password | Role |
|----------|----------|------|
| superadminaleaa | alea12345 | superadmin |
| admineya | eya12345 | admin |
| adminwinx | winx12345 | admin |
| adminviu | viu12345 | admin |

**Note**: These credentials are automatically inserted when you run `php artisan migrate`. They are stored as bcrypt hashes in the migration file, not in plaintext.

## Troubleshooting

### "Invalid credentials" error
**Cause**: Database is empty or migrations weren't run.

**Solution**:
```powershell
# Check if database has users
php artisan tinker
App\Models\User::count();  # If 0, run:
exit

php artisan migrate
```

### Database connection error
**Cause**: Wrong database credentials in `.env`

**Solution**:
1. Check MySQL is running
2. Verify credentials in `.env` match your MySQL setup
3. Ensure database exists:
   ```sql
   SHOW DATABASES LIKE 'viu_winx';
   ```

### "Class not found" errors
**Cause**: Composer dependencies not installed

**Solution**:
```powershell
composer install
php artisan clear-compiled
composer dump-autoload
```

### Migration already ran but no users
**Cause**: Migration was interrupted or database was cleared

**Solution**:
```powershell
# Re-run the admin seeding migration specifically
php artisan migrate:refresh --path=database/migrations/2025_12_06_140000_seed_admins_on_migrate.php

# Or reset everything (CAUTION: deletes all data)
php artisan migrate:fresh
```

## Optional: Asset Compilation
If you need to build frontend assets:
```powershell
npm install
npm run build
```

## Need Help?
- Check Laravel logs: `storage/logs/laravel.log`
- Verify environment: `php artisan about`
- See full deployment guide: `DEPLOYMENT.md`
