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
- [x] Custom Livewire page (`ScanningScreen`, route `scanning-screen/{session}`),
      mobile-first layout, reachable only for the session's assigned counters while
      it's `in_progress` (plus manager/admin for oversight per branch). A separate
      "My Sessions" page (`MyInventorySessions`) lists a counter's assigned
      in-progress sessions with a "Scan" link, per SPEC §5.1. Browser-verified: an
      unassigned/other-branch counter gets 403 on the scan URL; the assigned
      counter and the branch's manager both get 200.
- [x] Camera integration via `html5-qrcode` (bundled through Vite as a dedicated
      `scanner.js` entry, since Filament pages don't pull in the app's default
      `app.js`), decode → Livewire `scan()` action with the raw barcode string
      (lookup logic lives server-side in the Livewire component, not in JS).
      Browser-verified with Chromium's fake video device
      (`--use-fake-device-for-media-stream`) — the camera genuinely initializes
      and html5-qrcode's viewfinder renders, not just a mock.
- [x] Known barcode → create/increment `inventory_count_lines`, log `scan_events`,
      update on-screen running list, scan confirmation cue (beep via Web Audio API
      client-side + a Filament notification). Browser-verified: scanning the same
      barcode twice correctly incremented counted_quantity to 2 with two
      notifications and a live-updating list.
- [x] Unknown barcode → prompt to quick-create a `pending_review` product inline,
      then count it (SPEC §5.5). Browser-verified full round trip: unknown barcode
      → quick-create form → product created with `status=pending_review`,
      `barcode_source=existing` → count line created and incremented → confirmed
      the product then surfaces under the Products "pending_review" filter for a
      Branch Manager to triage.
- [x] Manual quantity adjustment path with required notes field, visually distinct
      from scan-derived counts (SPEC §5.6). Browser-verified: set an item to 40 with
      a required note — confirmed in the DB that `notes` was saved and, importantly,
      that manual adjustment does **not** create a `scan_events` row (only real
      scans do) — `scan_events` stayed append-only-clean, confirmed by attempting a
      direct update via tinker and getting the model-level exception.
- [ ] Manual QA on an actual phone browser, not just desktop devtools emulation —
      **not done**: this sandbox has no real phone available. Everything above was
      verified with a real (fake-device) camera stream and real Livewire round trips
      in a headless browser, which covers the business logic and camera wiring, but
      real-device touch/layout ergonomics (one-handed use, actual camera autofocus
      behavior, viewport quirks) genuinely need a physical phone before shipping.
- [x] Keyboard-wedge text input fallback per SPEC §5.7 — built alongside the camera
      (not strictly "after it was solid" as the file suggested, since it turned out
      to be the most practical way to drive the scan logic in an automated browser
      test without real camera hardware). This was the actual input used for all
      the verifications above.

## Phase 8 — Reports & export
- [x] Session detail report: on-screen + CSV export (`maatwebsite/excel`) —
      `SessionDetailReport` page + `SessionDetailExport`. Browser-verified: picked
      a session, saw the on-screen table (expected/counted/variance, variance
      highlighted when non-zero), downloaded the CSV, confirmed its contents match.
- [x] Branch summary report across a date range: on-screen + CSV export —
      `BranchSummaryReport` page + `BranchSummaryExport` +
      `BranchSummaryReportService`. **[assumption]** "pending review products...
      per branch" is read as "pending_review products with a count line in a
      session belonging to that branch in range" — products aren't branch-scoped
      themselves (catalog is shared, SPEC §2), so this is the closest sensible
      mapping; SPEC doesn't spell out the alternative.
- [x] Role-scoped visibility (Branch Manager = own branch only, no branch filter
      shown; Super Admin gets an "All branches" dropdown). Browser-verified for
      both report pages, plus Counter gets 403 on both entirely (not part of
      SPEC §4.8's role list for this screen).
- **Bug caught and fixed during testing:** `maatwebsite/excel`'s CSV writer
  rendered integer `0` as a blank cell in the exported file — indistinguishable
  from missing data (e.g. a branch with 0 sessions in range showed an entirely
  blank row instead of zeros). The on-screen Blade table never had this problem
  since it doesn't go through the exporter. Fixed by casting numeric fields to
  strings in both `WithMapping::map()` implementations before they reach
  PhpSpreadsheet. Re-verified the downloaded CSV shows `"0"` correctly afterward.
- **Bug caught and fixed during testing:** both `export()` methods were typed to
  return `StreamedResponse`, but `Excel::download(..., Excel::CSV)` actually
  returns `BinaryFileResponse` — a `TypeError` on every export attempt. Confirmed
  Livewire's `SupportFileDownloads` feature natively accepts either response
  type before fixing the type hints, so no other change was needed.

## Phase 9 — Bilingual EN/AR + RTL
- [x] All new screens pass through translation files, no hardcoded English strings —
      `lang/en/app.php` + `lang/ar/app.php` cover every nav label, field label,
      status/role value, action label, and user-facing notification across all
      9 resources/pages built in Phases 3–8. Locale switching via `SetLocale`
      middleware (session-backed) + a topbar link (`renderHook` on
      `PanelsRenderHook::TOPBAR_END`) + `/locale/{locale}` route.
- [x] Arabic locale renders full RTL correctly, including the scanning screen and
      generated PDF labels/reports. Browser-verified across every screen type
      built so far (standard Resource, custom table Page, custom Livewire Page,
      RelationManager, dashboard/nav chrome, both Report pages): full RTL layout
      flip (sidebar to the right, `dir="rtl"`), all labels/badges/notifications in
      Arabic, and the scanning screen specifically — camera still initializes with
      a real (fake-device) stream in RTL, keyboard-wedge input, running list, and
      notifications all correctly translated. Barcode label PDF: Arabic product
      name rendered in its own `dir="rtl"` block separate from the English name
      (DomPDF handles mixed-direction text poorly inline, so kept them as two
      separate lines rather than one bidi string).
- [x] Spot-check Filament's own RTL mode is enabled and functioning — confirmed
      Filament ships full built-in Arabic translations + `dir="rtl"` for its own
      chrome (`vendor/filament/*/resources/lang/ar/*.php`), which activates
      automatically once `app()->setLocale('ar')` is called; no Filament-side
      configuration needed beyond that.
- **Known minor gap:** a handful of Filament-auto-generated fallback strings
  (e.g. a relation manager's default empty-state text before I added an explicit
  `emptyStateHeading()`) can still fall back to an untranslated English default
  if a screen path wasn't exercised during testing. Caught and fixed one instance
  (`CountLinesRelationManager`'s empty state); there could be others in edge
  states not covered by the browser passes above — worth a dedicated sweep before
  shipping if time allows, flagged here rather than silently claimed complete.

## Phase 10 — QA pass
- [x] Full walkthrough as each of the three roles — final consolidated matrix,
      every screen built across Phases 3–8, browser-verified with real HTTP status
      codes per role:
      - **Super Admin**: 200 on all 9 screens.
      - **Branch Manager**: 403 only on `/admin/branches`; 200 on Categories,
        Products, Expected Stock, Inventory Sessions, My Sessions, Users, and both
        Reports — exactly matching SPEC §2's role table.
      - **Counter**: 200 only on `/admin/my-inventory-sessions`; 403 on all 8 other
        screens. An other-branch/unassigned counter also gets 403 specifically on
        `scanning-screen/{session}` for a session they're not assigned to
        (re-verified from Phase 7).
      Cross-branch data isolation (not just screen access) was verified per-phase
      as each resource was built: Product Branch Stock, Inventory Sessions, Users,
      and both Reports all hard-scope Branch Manager to their own branch's rows,
      not just gate the screen.
- [x] Confirm `scan_events` is truly append-only (no update/delete path exists) —
      re-verified directly: a tinker `update()` and `delete()` against a real
      `ScanEvent` row both throw the model-level `LogicException`; there is also no
      Filament resource for `ScanEvent` at all, so no admin UI surface exists to
      even attempt it.
- [x] Confirm barcode uniqueness constraint holds and produces a sane error, not a
      500, when violated. **Found and fixed two real bugs here:**
      1. `ProductResource`'s `sku`, `barcode` fields and `BranchResource`'s `code`
         field had DB-level `unique` constraints but no matching Filament
         `->unique()` form validation — a duplicate would have hit the DB
         constraint raw and thrown an uncaught `QueryException` (500) instead of
         a friendly inline error. Fixed by adding `->unique(ignoreRecord: true)`
         to all three. Browser-verified: submitting a duplicate barcode now shows
         "The barcode has already been taken." inline, no crash.
      2. The scanning screen's barcode lookup filtered to `status = 'active'`
         only, so re-scanning a barcode belonging to an existing
         `pending_review` (or `inactive`) product looked "unknown" again — and
         confirming the quick-create form a second time would have tried to
         insert a second product with the same barcode and crashed on the unique
         constraint. Fixed by matching on barcode alone (any status counts as
         known); added a second defense-in-depth re-check right before insert for
         the genuine race case (two counters scanning the same brand-new barcode
         at once). Browser-verified: re-scanning a `pending_review` product's
         barcode now increments its count line directly, no prompt, no duplicate
         product created.

## Phase 11 — Deployment (cPanel)
- [ ] Confirm SSL/HTTPS on the target domain (hard requirement for camera scanning —
      see SPEC §8)
- [ ] Standard Laravel cPanel deployment (no queue workers required for MVP per
      SPEC §9 point 8)
- [ ] Smoke test scanning flow on the live domain from an actual phone, not just
      locally
