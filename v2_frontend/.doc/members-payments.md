# Members and payments implementation

## Navigation

The shared desktop sidebar and tablet/phone drawer use hash routes so refresh and browser history work without new server rewrites. Dashboard, Members and Payments are available according to permissions. POS, Inventory management, Coach Portal and Admin Portal are displayed as unavailable until their destinations and functionality are defined. No bottom navigation is used.

## Members

`src/features/members/` implements paginated list/search/status filtering, profile, edit and **Onboard Member**. It uses `/api/members`, `/api/members/{id}`, `/api/members/meta` and `/api/members/form/payment-plans`; create/update validation and server-side field errors are displayed. Member access uses context permissions and backend response permissions. The listing uses one row per member at every size, with a keyboard-focusable horizontal scroll region; select a member name to view the full profile.

## Payments

`src/features/payments/` implements the five requested tabs and separate payment category actions. Membership and Other use `/api/payments` and `/api/payments/meta`; their create forms use the existing server business logic. Sales are separately loaded from `/api/sales` when permitted and shown as sale records, not assumed paid transactions. Payments and sales use six-column horizontally scrollable tables; selecting an ID opens the accessible record detail dialog.

**Backend constraints:** The payment and sales APIs paginate independently and do not accept date range filters. The date selector therefore filters only the currently fetched page, and the UI explicitly warns that matching records may exist on other pages. Unrecognized dates remain visible. The admission tab and Add admission action are unavailable because `members.admission_fee` is not evidence of payment and the backend does not expose a distinct admission-payment category. Add sale remains unavailable until a POS sale flow exists; no synthetic admission or sale payment is created.

Date presets use the browser's local calendar; weeks begin Monday, the current period ends today, and the last-three-months preset includes the current calendar month plus the previous two months. Display formatting follows tenant settings.

## Validation

Run `npm test` for context/API error/date-format/date-range regression checks and `npm run build` for strict TypeScript and Vite production validation. Test live changes only in an authorized non-production tenant; integration UI inspection can use mocked API responses.
