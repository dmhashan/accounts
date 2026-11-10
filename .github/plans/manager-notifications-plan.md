# Manager Notifications — Implementation Plan

## Goal

Add a dedicated Settings experience for manager alerts, independently configurable from member notifications. Managers can enable email and SMS, manage channel-specific recipient lists, and select the business events that generate alerts. Scheduled report sharing is explicitly deferred to a later phase.

## Product decisions / assumptions

- Keep manager alerts distinct from the existing member-facing `notifications.email.*` / `notifications.sms.*` switches and member broadcasts.
- Event matrix: payments, expenses, sales, and stock entries each support created/updated/deleted. Display is represented by the real business action, **stock released to display**; the current domain has no independent display edit/delete CRUD, so do not present non-existent actions as configurable events.
- Scheduled reporting, report selection, cadence, and report recipients are out of scope for this implementation phase.
- Send only concise business metadata (event, record/reference, amount or quantity, actor, time); avoid member/customer personal details in SMS.
- Use tenant-local timezone for report schedules; if tenant timezone is not available, require an IANA timezone selection (default `Asia/Colombo`).

## Recommended architecture

1. **Dedicated tenant-owned persistence**, rather than a JSON blob in `TenantConfigurationService`:
   - `manager_notification_settings`: one row per tenant, channel enable flags, and structured per-channel event selections (or normalized event preference rows).
   - `manager_notification_recipients`: tenant, channel (`email`/`sms`), label, normalized address/number, active flag; unique tenant + channel + normalized destination.
   - Add a minimal delivery log with event, channel, destination, status, attempts, and error summary (never credentials or full sensitive payload).
   - Explicitly scope every query and mutation by `tenant_id`, even where tenant database isolation is enabled.
2. **Settings API** under `auth` + `permission:settings.configuration,settings.manage`: load/update channel settings and event selections; recipient CRUD. Validate email, phone format, unique recipient, and event keys; clearly warn if a channel is enabled without an active recipient.
3. **Dedicated manager notifications page** at `/settings/manager-notifications`, linked from Settings navigation and lazy-loaded. Use accessible switches, clear disabled states, channel sections, checkbox event matrix, inline recipient management, save state, empty states, and delivery status feedback. Preserve all actions and information on mobile/tablet/desktop.
4. **Notification dispatcher + queued channel jobs**. Integrate after successful mutations at service layer: `PaymentService`, `ExpenseService`, `SaleProcessingService`, and `InventoryService`. Capture before/after snapshots for edits/deletes. Inventory notifications distinguish stock-entry CRUD from `releaseToDisplay`. Queue only after DB commit (queue `after_commit` is currently false); delivery errors must not fail the original business operation. Reuse `TenantMailService` and existing `SmsService` delivery conventions.
5. **Access, audit, reliability**: use settings permission already used by Settings APIs; tenant scope all records; protect recipient changes with audit logs; normalize destinations; escape/render mail content safely; retry transient provider failures with bounded retries; never expose SMTP/SMS credentials in API responses or delivery logs.

## Likely files/areas

- New tenant migrations and models for settings, recipient, schedules, and (if adopted) delivery history.
- New API controller/service and routes in `routes/api/settings.php`.
- New Vue page, router import/route, and Settings navigation entry in `resources/js/spa/pages/`, `router.js`, and `useNavigation.js`.
- New event catalog/dispatcher and queued email/SMS jobs under `app/Services/` and `app/Jobs/`.
- Mutation hooks in `app/Services/PaymentService.php`, `ExpenseService.php`, `SaleProcessingService.php`, and `InventoryService.php`.
- Feature tests under `tests/Feature/Api`, service/job tests, and Vue tests where applicable.

## Implementation sequence

1. Confirm exact report types/cadences, then implement tenant-scoped schema/models and event catalog.
2. Implement settings/recipient/schedule API with authorization, validation, tenant isolation, and tests.
3. Build responsive Manager Notifications settings page and navigation.
4. Implement dispatcher/jobs and add after-commit service hooks for payment, expense, sale, stock, and display release.
5. Add delivery status UX and run targeted plus full relevant test suites/build.

## Deferred follow-up

- Scheduled email reporting (schedule persistence, report type/cadence/timezone, idempotent scheduler, and report delivery to managed email recipients) is intentionally not part of this phase.

## Acceptance criteria

- Email and SMS can be independently switched off/on; disabled channel or unselected event never sends.
- Authorized managers can add/edit/remove channel-specific recipients; invalid/duplicate destinations are rejected; one tenant cannot read/change another tenant's data.
- Every supported mutation generates exactly the configured channel alerts only after success/commit; update/delete notifications contain the appropriate prior/current business snapshot; SMS contains no member/customer PII.
- Stock display release has its own understandable event label and triggers only that event selection.
- Delivery failure is visible/retryable and never rolls back the financial/inventory operation.
- All settings, recipient, and schedule actions remain available at mobile, tablet, and large-screen widths.

## Verification

- Laravel feature tests for permissions, validation, recipient CRUD, tenant isolation, event-rule matching, disabled channels, and settings persistence.
- Queue/mail/SMS fakes for each event/action, edit/delete snapshots, post-commit dispatch, retries, and provider failures.
- Vue tests for toggles, recipient forms, event matrix, schedule validation/status, and responsive parity.
- Run targeted PHPUnit suites, full `composer test`, frontend `npm test`, and `npm run build`.
