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
- [x] Branches Filament resource (Super Admin only — no policy grants needed
      beyond the existing `before()` bypass, since default-deny already covers it)
- [x] Categories Filament resource (Super Admin + Branch Manager — opened
      `CategoryPolicy` viewAny/view/create/update/delete for `branch_manager`)
- [x] Manual QA: Branch Manager cannot see the Branches resource at all —
      browser-verified: `/admin/branches` returns 403 for Branch Manager, nav
      sidebar has no "Branches" entry (screenshot), `/admin/categories` works
      and is in the nav. Counter gets 403 on both (not part of SPEC §2 scope
      for either resource). Super Admin gets 200 on both.

## Phase 4 — Products & barcode generation/printing
- [x] Products Filament resource (Super Admin + Branch Manager), table + form per
      SPEC §4.4. `ProductPolicy` opened for `branch_manager` only — Counters never
      reach this resource; their product access is entirely through the Phase 7
      scanning screen, a separate code path that doesn't go through this policy.
- [x] "Generate barcode" row action (sets `barcode`, `barcode_source = generated`) —
      `BarcodeService::generateUniqueValue()`, retries on collision.
- [x] "Print label" single + bulk action → PDF via DomPDF with barcode image
      (simple 3-per-row grid, `resources/views/pdf/barcode-labels.blade.php`).
      Records a `barcode_print_jobs` row per SPEC §3.9. Browser-verified end to end:
      clicked "Generate barcode" on a barcode-less product, then "Print label",
      got a real download — confirmed the PDF has a valid `/Catalog`, 1 page, and
      an embedded `/Image` `/XObject` (the barcode itself, not just placeholder text).
- [x] `pending_review` status filter/view — a `SelectFilter` on `status` (includes
      `pending_review`) plus a `has_barcode` ternary filter. Note: this file's
      original text said this depends on "Phase 6" (Inventory sessions); that looks
      like an off-by-one in this file — `pending_review` products are actually
      created by the Phase 7 scanning screen's unknown-barcode flow (SPEC §5.5), not
      Phase 6. Filter is built now either way; meaningful end-to-end testing (an
      actual pending_review product created via a scan) waits for Phase 7.

## Phase 5 — Product Branch Stock (expected quantities)
- [x] Editable grid/table: product × expected_quantity, scoped by branch — a custom
      Filament page (`ProductBranchStockGrid`), not a Resource, per SPEC §4.5's
      explicit note. Inline-editable via `TextInputColumn`.
- [x] Branch Manager sees only their branch (branch column hidden, query hard-scoped);
      Super Admin sees a `Branch` column + filter and can switch. Browser-verified
      both views, including a real bug caught and fixed: `defaultSort('product.name_en')`
      produced invalid SQL (`order by product.name_en` with no join) — Filament's
      dotted-relation sugar only applies to column-level `->sortable()`, not the
      table-level `defaultSort()`. Fixed by sorting on the column instead and
      dropping the table-level default.
- [x] Track `updated_by` on change — verified end to end: edited a quantity as
      Branch Manager, confirmed in the DB (`updated_by` = that user's id) and
      visually as Super Admin ("Last updated by: Branch Manager" on that row).

## Phase 6 — Inventory sessions (management side)
- [x] Inventory Sessions Filament resource: create (draft, with auto-generated
      `INV-{branch_code}-{date}-{seq}` reference via `InventorySessionReferenceGenerator`),
      assign counters (multi-select scoped to the branch's counter users), start,
      view live line-item progress (`CountLinesRelationManager`), submit, approve,
      close (SPEC §5.2 lifecycle) — each transition is its own table action, visible
      only in the state it applies from.
- [x] Enforce: only one `in_progress` session per branch at a time — checked inside
      the "Start" action, blocks with a Filament notification if violated.
      Browser-verified: started one session, tried starting a second draft for the
      same branch, got "This branch already has a session in progress." and the
      second session correctly stayed in `draft`.
- [x] Session detail page: count lines table with expected / counted / variance,
      filter/sort by "variance ≠ 0" (`whereColumn` filter, since `variance` is a
      computed accessor, not a DB column — can't filter on it directly).
- [x] Role checks: Counter cannot approve/close — enforced at the resource level,
      Counters get 403 on `/admin/inventory-sessions` entirely (browser-verified);
      Branch Manager only sees own branch (`getEloquentQuery` scoping + policy
      `branch_id` check); Super Admin sees all. Full lifecycle browser-tested
      end to end (create → start → submit → approve → close), confirmed in the DB
      after each transition.

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
