# tasks.md — Barcode Inventory System

Work top to bottom. Don't start a phase until the previous one's checklist is fully
checked. Each phase should end in a working, demo-able state. Refer to `docs/SPEC.md`
for field-level detail — this file only sequences the work.

## Phase 0 — Project setup
- [x] Laravel 12 project scaffolded (v12.64.0, pinned per CLAUDE.md — `composer create-project` alone pulls Laravel 13, had to pin explicitly)
- [x] Filament v3 installed, admin panel reachable (`/admin/login` returns 200)
- [x] MySQL connection configured (local MariaDB 10.11 `barcode_inventory` db + `barcode_app` user for dev; production should point at real MySQL 8 per SPEC)
- [x] Install `spatie/laravel-permission`, publish + run its migrations
- [x] Install `picqer/php-barcode-generator`
- [x] Install `barryvdh/laravel-dompdf`
- [x] Install `maatwebsite/excel`
- [x] Add `html5-qrcode` via npm, package resolves correctly — full in-browser confirmation deferred to Phase 7 when the scanning screen actually mounts it
- [ ] Confirm local dev can test camera access (localhost is exempt from the HTTPS
      requirement — verify this works before assuming it doesn't) — deferred to Phase 7
      manual QA on a real device per that phase's checklist

## Phase 1 — Data model
- [x] Migrations for: `branches`, `categories`, `products`, `product_branch_stock`,
      `inventory_sessions`, `inventory_session_counters`, `inventory_count_lines`,
      `scan_events`, `barcode_print_jobs` (SPEC §3), plus `users.branch_id`
- [x] Models with relationships, casts, and the `unique` constraints called out in
      SPEC §3 (especially `products.barcode`, `product_branch_stock` composite unique,
      `inventory_count_lines` composite unique). `variance` is a computed accessor, not
      a stored column. `scan_events` model throws on update/delete to enforce
      append-only at the Eloquent layer (not just "no UI for it").
- [x] Factories + seeders for local testing (2 branches, 3 categories, 20 products —
      15 with barcodes, 3 without, 2 pending_review — expected stock per branch).
      Users per role deferred to Phase 2 (needs roles to exist first).
- [x] `php artisan migrate:fresh --seed` runs clean

## Phase 2 — Auth & roles
- [x] Roles created: `super_admin`, `branch_manager`, `counter` (spatie) — `RoleSeeder`
- [x] Users table has role assignment + `branch_id` (nullable for super_admin)
- [x] Filament login works for all three roles — verified with real `Auth::attempt`
      and a headless-browser login for each seeded user (admin/manager/counter)
- [x] Policies scaffolded for every model, denying by default, with a `before()`
      hook granting `super_admin` everything except `ScanEvent` update/delete/restore
      (append-only holds even for admins). Only `UserPolicy` opened further this
      phase, per the explicit SPEC §2 note on Branch Manager + counter accounts;
      all other resources stay denied until their own phase builds them.
- [x] **[gap in this file, not in SPEC]** `tasks.md` never scheduled the "Users"
      screen from SPEC §4.2 as its own phase. Folded it into Phase 2 since it's
      core to auth/roles: Filament `UserResource`, Branch Manager scoped to
      counters in their own branch (query + policy), role field synced via
      spatie `syncRoles()` in the Create/Edit pages. Browser-verified: Branch
      Manager's list shows only their branch's counters, and creating a new
      counter correctly assigns the role (a real bug was caught and fixed here —
      the role `Select`'s `afterStateHydrated` was nulling out the default role
      on the create form, which would have blocked every Branch-Manager-created
      account; only reproducible in a real browser, not in tinker).

## Phase 3 — Branches & Categories
- [ ] Branches Filament resource (Super Admin only)
- [ ] Categories Filament resource (Super Admin + Branch Manager)
- [ ] Manual QA: Branch Manager cannot see the Branches resource at all

## Phase 4 — Products & barcode generation/printing
- [ ] Products Filament resource (Super Admin + Branch Manager), table + form per
      SPEC §4.4
- [ ] "Generate barcode" row action (sets `barcode`, `barcode_source = generated`)
- [ ] "Print label" single + bulk action → PDF via DomPDF with barcode image
      (label layout can be a simple first pass — refine only if requested)
- [ ] `pending_review` status filter/view for Branch Manager to triage
      scan-created products (this depends on Phase 6 existing, so the review UI can be
      built now but only meaningfully tested after Phase 6)

## Phase 5 — Product Branch Stock (expected quantities)
- [ ] Editable grid/table: product × expected_quantity, scoped by branch
- [ ] Branch Manager sees only their branch; Super Admin can switch branch
- [ ] Track `updated_by` on change

## Phase 6 — Inventory sessions (management side)
- [ ] Inventory Sessions Filament resource: create (draft), assign counters, start,
      view live line-item progress, submit, approve, close (SPEC §5.2 lifecycle)
- [ ] Enforce: only one `in_progress` session per branch at a time
- [ ] Session detail page: count lines table with expected / counted / variance,
      filter/sort by "variance ≠ 0"
- [ ] Role checks: Counter cannot approve/close; Branch Manager only sees own branch;
      Super Admin sees all

## Phase 7 — Scanning screen (the core feature)
- [ ] Custom Livewire page, mobile-first layout, reachable only for the session's
      assigned counters while it's `in_progress` (plus manager/admin for oversight)
- [ ] Camera integration via `html5-qrcode`, decode → Livewire action with raw barcode
      string (lookup logic lives server-side, not in JS)
- [ ] Known barcode → create/increment `inventory_count_lines`, log `scan_events`,
      update on-screen running list, give a scan confirmation cue (SPEC §5.4)
- [ ] Unknown barcode → prompt to quick-create a `pending_review` product inline,
      then count it (SPEC §5.5)
- [ ] Manual quantity adjustment path with required notes field, visually distinct
      from scan-derived counts (SPEC §5.6)
- [ ] Manual QA on an actual phone browser, not just desktop devtools emulation —
      camera behavior and layout both need a real-device check
- [ ] (Lower priority, do after the above is solid) keyboard-wedge text input
      fallback per SPEC §5.7

## Phase 8 — Reports & export
- [ ] Session detail report: on-screen + CSV export (`maatwebsite/excel`)
- [ ] Branch summary report across a date range: on-screen + CSV export
- [ ] Role-scoped visibility (Branch Manager = own branch only)

## Phase 9 — Bilingual EN/AR + RTL
- [ ] All new screens pass through translation files, no hardcoded English strings
- [ ] Arabic locale renders full RTL correctly, including the scanning screen and
      generated PDF labels/reports
- [ ] Spot-check Filament's own RTL mode is enabled and functioning

## Phase 10 — QA pass
- [ ] Full walkthrough as each of the three roles, confirming scope boundaries from
      SPEC §2 hold (a Counter genuinely cannot reach anything outside their assigned,
      in_progress session; a Branch Manager genuinely cannot see another branch)
- [ ] Confirm `scan_events` is truly append-only (no update/delete path exists)
- [ ] Confirm barcode uniqueness constraint holds and produces a sane error, not a
      500, when violated

## Phase 11 — Deployment (cPanel)
- [ ] Confirm SSL/HTTPS on the target domain (hard requirement for camera scanning —
      see SPEC §8)
- [ ] Standard Laravel cPanel deployment (no queue workers required for MVP per
      SPEC §9 point 8)
- [ ] Smoke test scanning flow on the live domain from an actual phone, not just
      locally
