# Tenant POS

The staff POS is available at `#/pos` from the desktop sidebar or mobile/tablet drawer. Landscape layouts show the catalog and checkout side by side; narrow and portrait layouts stack them and provide a sticky shortcut to the cart. The Payments page's Add sale button opens the POS for users with `salesCreate` permission.

## Existing API contract

- `GET /api/sales/meta` provides product variations, displayed stock, local/foreign price estimates, customers, and payment methods. The route requires `sales.process`, `sales.create`, or `sales.edit`.
- `GET /api/sales/member-wallet/{member}` provides the selected member's current wallet balance for authorized staff.
- `POST /api/sales` creates the sale using the selected pricing tier, variation IDs/quantities, customer, payment status/method, and optional reference. Creation requires `sales.create`; inventory and payment validation remain server-side.
- `GET /api/sales/{id}` retrieves final totals and payment status only for users with a sales viewing/edit permission. If the user cannot read the sale, the UI shows the returned sale ID without inventing a receipt.

The metadata price may come from the first non-expired stock entry even if that batch has no display stock; checkout chooses the first saleable batch and calculates the authoritative total. The displayed prices and totals are estimates. Both local and foreign tier amounts are displayed in LKR because the API does not provide currency conversion. Full payment is a record of payment, not a payment-gateway charge. Outstanding sales have no amount collected at checkout. Discounts, service items, check-in, order notes, and held sales from the visual reference are not implemented because no corresponding API contract is available.

## Safety and verification

A submission is never automatically retried; the POS preserves its state when navigating within the staff app. For network/timeout/server errors with an ambiguous result, staff must verify the sale before allowing another attempt. Browser unload is guarded while a submission is pending or ambiguous, but the backend does not expose a documented idempotency key and browser crashes cannot be fully protected by client state. Test with mocked responses or an authorized non-production tenant.

Run `npm test` for cart/validation tests and `npm run build` for strict TypeScript and production asset validation.
