# CLAUDE.md — Barcode Inventory System

This file is the entry point for any AI coding agent (Claude Code, Codex CLI, etc.)
implementing this project. Read this first, then `docs/SPEC.md`, then work through
`tasks.md` in order. Do not skip ahead to later phases before earlier ones pass review.

## 1. Project summary

A single-company, multi-branch web inventory counting system. Staff scan product
barcodes using their phone's browser camera (no native app) to perform stock counts
per branch. The system tracks expected vs. counted quantities, flags variances, and
exports CSV/PDF reports. It also generates and prints barcode labels for products
that don't already have one.

**Out of scope (do not build):** POS, Sales, Accounting/Finance, hardware integrations
beyond a simple keyboard-wedge fallback (see SPEC §6.6).

## 2. Tech stack (do not substitute without asking)

| Layer | Choice |
|---|---|
| Backend framework | Laravel 12 |
| Admin/app panel | Filament v3 |
| Database | MySQL 8 |
| Auth & roles | Laravel auth + `spatie/laravel-permission` |
| Barcode image generation | `picqer/php-barcode-generator` |
| PDF (labels + reports) | `barryvdh/laravel-dompdf` |
| CSV/XLSX export | `maatwebsite/excel` |
| Browser barcode scanning | `html5-qrcode` (JS, loaded via CDN or npm), inside a Livewire/Filament custom page |
| Hosting target | cPanel shared hosting — keep deployment footprint simple (no queue workers required for MVP; use sync driver unless a feature explicitly needs a queue) |

**Hard constraint:** camera-based scanning requires a secure context (HTTPS). The
staging/production domain must have SSL. Call this out if local dev is over plain HTTP —
`localhost` is exempt from this browser restriction, but any other host is not.

## 3. Conventions

- **Bilingual EN/AR everywhere.** Any user-facing text field on a model (product name,
  branch name, category name) is stored as two columns: `name_en`, `name_ar` — not a
  JSON translation blob, unless SPEC says otherwise. UI must support full RTL layout
  when locale is Arabic, including Filament's own RTL mode.
- **Filament resources** follow standard conventions: one Resource per entity, use
  Filament's built-in RelationManagers instead of custom Livewire components unless
  SPEC explicitly calls for a custom page (e.g. the scanning screen).
- **Migrations** are additive and reversible. Every migration has a working `down()`.
- **Authorization** is enforced via Filament Policies backed by `spatie/laravel-permission`
  roles — never hardcode role checks like `if ($user->email === '...')`.
- **Money/quantities** use integers or `decimal(10,2)` as specified per field — never
  floats.
- **No silent scope creep.** If an implementation detail isn't in SPEC.md, either pick
  the smallest reasonable default and note it in the PR description, or flag it as an
  open question — don't design new features unprompted.

## 4. Definition of done (per task)

A task in `tasks.md` is complete only when:
1. Migration/model/resource builds and runs (`php artisan migrate:fresh --seed` works).
2. Filament UI is reachable and functions for all three roles it applies to
   (Super Admin, Branch Manager, Counter), respecting the permission boundaries in
   SPEC §3.
3. Arabic locale renders correctly in RTL for any new screen.
4. No `POS`/`Sales`/`Accounting` concepts were introduced.

## 5. How the spec files are organized

- `docs/SPEC.md` — the full functional & data spec. This is the source of truth for
  *what* to build.
- `tasks.md` — the phased build checklist. This is the source of truth for *order*
  and *scope per step*. Work top to bottom; don't jump ahead.

If SPEC.md and tasks.md ever conflict, SPEC.md wins — tasks.md is just a sequencing
aid.

## 6. Assumptions already made for you

The original PRD was a one-page outline. The following decisions were made to fill
gaps and are treated as final unless the product owner says otherwise (see SPEC §9
for the full list, this is the short version):
- Single company, multiple branches (not multi-tenant SaaS).
- Scanning is browser-camera based, no installed app.
- The system tracks an editable "expected/system quantity" per product per branch so
  that counts can be compared against it and variances flagged.
- Barcodes can be generated and printed for products that don't have one yet.
