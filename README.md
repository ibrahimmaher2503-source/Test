# Barcode Inventory System

A single-company, multi-branch web inventory counting system. Staff scan product
barcodes with their phone's browser camera (no native app) to perform stock counts
per branch. Tracks expected vs. counted quantities, flags variances, and exports
CSV/PDF reports. Also generates and prints barcode labels for products that don't
already have one.

See `CLAUDE.md` for the agent build guide, `docs/SPEC.md` for the full functional
spec, and `tasks.md` for the phased build checklist.

## Tech stack

- Laravel 12
- Filament v3 (admin/app panel)
- MySQL 8
- `spatie/laravel-permission` for roles
- `picqer/php-barcode-generator` for barcode images
- `barryvdh/laravel-dompdf` for PDF labels/reports
- `maatwebsite/excel` for CSV/XLSX export
- `html5-qrcode` for browser-based camera scanning

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# configure DB_* in .env for your local MySQL instance
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Visit `/admin` and log in with a seeded user (see `database/seeders`).

## Deployment

Target is cPanel shared hosting. See `tasks.md` Phase 11 and SPEC §8 — HTTPS is a
hard requirement in production because camera-based scanning needs a secure context.
