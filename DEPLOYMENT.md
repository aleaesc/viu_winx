# VIU WinX Deployment Guide

## Prerequisites

-   Laravel Cloud account (https://cloud.laravel.com - includes Vapor serverless) **OR** Ubuntu VPS + Aiven MySQL
-   Domain name with DNS access

## Option 1: Laravel Cloud (Serverless AWS via Vapor)

### 1. Sign up for Laravel Cloud

-   Visit https://cloud.laravel.com and create an account
-   Add a payment method and create a team

### 2. Install and authenticate Vapor CLI

```powershell
composer global require laravel/vapor-cli
$env:PATH += ";$env:APPDATA\Composer\vendor\bin"
vapor login
```

### 3. Initialize and configure

```powershell
# The vapor.yml is already created in the repo
# Review and adjust if needed
vapor init
```

### 4. Create production environment

```powershell
vapor env production
```

### 5. Set environment secrets (required)

```powershell
# Generate a new production key
php artisan key:generate --show

# Set secrets in Vapor
vapor secrets production APP_KEY="base64:YOUR_GENERATED_KEY_HERE"
vapor secrets production APP_ENV="production"
vapor secrets production APP_DEBUG="false"
vapor secrets production APP_URL="https://yourdomain.com"

# Add only the AI keys you need in production
vapor secrets production OPENROUTER_API_KEY="your-key-here"
vapor secrets production GEMINI_API_KEY="your-key-here"
# ... add other keys as needed
```

### 6. Deploy

```powershell
vapor deploy production
```

```
### 8. Configure domain

-   In Vapor dashboard: Environments → production → Domain
-   Add your domain and enable SSL
-   Update DNS to point to Vapor's CNAME

### 9. Verify deployment

Visit: `https://yourdomain.com/admin`

Login with seeded accounts:

-   `superadminaleaa` / `alea12345`
-   `admineya` / `eya12345`
-   `adminwinx` / `winx12345`
-   `adminviu` / `viu12345`

---
## Option 2: VPS + Aiven MySQL

### 1. Create Aiven MySQL service
-   Sign up at https://aiven.io
-   Create a MySQL service (choose free tier or paid plan)
-   Note: `HOST`, `PORT`, `DATABASE`, `USERNAME`, `PASSWORD`
-   Download CA certificate if SSL is required
	-   If Aiven shows “SSL mode: REQUIRED”, click “CA certificate → Download” and save the PEM (starts with `-----BEGIN CERTIFICATE-----`).
### 2. Provision Ubuntu server

-   Use DigitalOcean, AWS EC2, or similar
-   Ubuntu 22.04 LTS recommended
-   Open ports 80, 443, and 22

### 3. SSH to server and install stack

```bash
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-zip php8.2-curl unzip git

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 4. Deploy application

```bash
sudo mkdir -p /var/www/viu_winx
sudo chown $USER:$USER /var/www/viu_winx

git clone https://github.com/aleaesc/viu_winx.git /var/www/viu_winx
cd /var/www/viu_winx

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

### 5. Configure .env with Aiven

Edit `/var/www/viu_winx/.env`:

```bash
nano .env
```

Update these values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=your-aiven-host.aivencloud.com
DB_PORT=12345
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=your-aiven-password

# If SSL is required by Aiven, also set the CA path used by Laravel's PDO
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/aiven-ca.pem

# Add your AI keys
OPENROUTER_API_KEY=your-key
GEMINI_API_KEY=your-key
```

### 6. Set permissions

```bash
sudo chown -R www-data:www-data /var/www/viu_winx/storage
sudo chown -R www-data:www-data /var/www/viu_winx/bootstrap/cache
sudo chmod -R 775 /var/www/viu_winx/storage
sudo chmod -R 775 /var/www/viu_winx/bootstrap/cache
```

### 7. Configure Nginx

```bash
sudo cp /var/www/viu_winx/nginx-viu-winx.conf /etc/nginx/sites-available/viu-winx
# Edit and replace 'yourdomain.com' with your actual domain
sudo nano /etc/nginx/sites-available/viu-winx

sudo ln -s /etc/nginx/sites-available/viu-winx /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### 8. Run migrations (auto-seeds admins)

```bash
cd /var/www/viu_winx
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. Install SSL with Certbot

```bash
sudo snap install --classic certbot
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 10. Verify deployment

Visit: `https://yourdomain.com/admin`

Login with seeded accounts (same as Vapor)

---

## Render (Docker) + Aiven

### 1. Create Render Web Service (Docker)
-   Runtime: Docker
-   Link this repo on branch `main`

### 2. Environment variables (copy from your `.env`)
-   `APP_ENV=production`
-   `APP_DEBUG=false`
-   `APP_URL=https://<your-render-url>`
-   `FRONTEND_URL=https://<your-render-url>`
-   `APP_KEY=<keep your existing key>`
-   `DB_CONNECTION=mysql`
-   `DB_HOST=<your-aiven-host>`
-   `DB_PORT=<your-aiven-port>`
-   `DB_DATABASE=<your-db-name>`
-   `DB_USERNAME=<your-db-user>`
-   `DB_PASSWORD=<your-db-pass>`
-   `MYSQL_ATTR_SSL_CA=/etc/ssl/certs/aiven-ca.pem` (Aiven SSL)

### 3. Secret File: mount Aiven CA certificate
-   Render → Service → Settings → Secret Files → “Add Secret File”
	-   Name: `aiven-ca.pem`
	-   Contents: paste the CA certificate from Aiven (the PEM you posted)
	-   Mount path: `/etc/ssl/certs/aiven-ca.pem`
-   Ensure the env `MYSQL_ATTR_SSL_CA` matches the same path.

### 4. Deploy and auto-migrate
-   First deploy builds the image and runs `php artisan migrate --force`.
-   If DB/SSL fail, fix envs or Secret File and redeploy.

### 5. Verify
-   Visit your Render URL and test login on `/admin`.
-   Run a quick DB check from Shell:
	```bash
	php artisan tinker
	>>> DB::select('SELECT 1')
	```

## Admin Credentials (Auto-seeded)

These accounts are automatically created when you run migrations:

| Username        | Password  | Role       | Email               |
| --------------- | --------- | ---------- | ------------------- |
| superadminaleaa | alea12345 | superadmin | adminalea@viu.com   |
| admineya        | eya12345  | admin      | admineya@local.viu  |
| adminwinx       | winx12345 | admin      | adminwinx@local.viu |
| adminviu        | viu12345  | admin      | adminviu@local.viu  |

**Important**: Change these passwords immediately after first login using the admin settings panel.

---

## Security Notes

-   Never commit `.env` or API keys to Git
-   For Vapor: use `vapor secrets` command
-   For VPS: keep `.env` on server only
-   Rotate admin passwords post-deployment
-   Keep `APP_DEBUG=false` in production
-   Regular backups of database

---

## Troubleshooting

### Vapor deployment fails

```powershell
vapor deploy production --debug
```

### VPS migration fails

```bash
# Check database connection
php artisan tinker
DB::connection()->getPdo();

# Check logs
tail -f storage/logs/laravel.log
```

### Admin login doesn't work

```bash
# Re-run migration (idempotent)
php artisan migrate

# Check users table
php artisan tinker
App\Models\User::count();
```
