# Copilot Instructions

## Device Support Rule
- Every UI implementation must support **mobile**, **tablet**, and **large display** layouts.
- Mobile/tablet UI can use a different structure from desktop, but all business details and actions must remain available in both experiences.
- Do not remove data points on smaller devices; reflow/reorder content instead.
- Use responsive Tailwind classes and verify parity of visible information across breakpoints.
- For tenant app pages, maintain navigation parity: if a module exists on desktop navigation, it must also be accessible on mobile/tablet navigation.

## SPA Rule
- Frontend tenant application is Vue SPA with route-level chunk loading.
- Backend integrations for SPA should use `/api/*` REST endpoints.
