# FitFlow v2 visual direction and responsive system

## Current iteration override — tenant admin entry

The latest tenant-admin login/sidebar reference set uses a light visual system and supersedes the earlier dark palette below for this iteration: warm-white and soft gray surfaces, crisp near-black text, subtle gray borders, and restrained bright-blue accents. The sign-in and navigation shell use this light palette. This override is scoped to the current tenant admin entry experience; it does not finalize future dashboard styling.

Current shell palette tokens are maintained in `src/styles.css`: canvas `#f5f7fa`, white surface `#ffffff`, blue action `#2879e8`, and text `#17212f`. Keep responsive navigation parity: persistent desktop sidebar; tablet/phone menu button plus avatar popover; drawer contains the same generic Workspace entry and sign-out. Do not add a bottom navigation bar or old feature navigation.

## Reference-image reading

The supplied dashboard references define the initial visual language:

- Near-black canvas and dark, rounded dashboard cards.
- Bright acid-lime as the primary action/data-highlight color.
- Crisp white primary type, muted gray secondary type, thin low-contrast borders.
- Coral and teal as restrained secondary status accents.
- Compact data visualizations, pills, counters, avatars, and small icon controls.
- High-density desktop dashboard composition with generous rounded corners and soft tonal separation.

The images are inspiration, not a finalized dashboard information architecture. Do not copy their exact chart layout, labels, or metrics; use FitFlow business data and await page arrangement direction.

## Brand tokens from the reference

| Token | Initial value | Use |
|---|---|---|
| `brand-lime` | `#C1F429` | Primary actions, active state, key chart series, positive highlights |
| `brand-coral` | `#FF6D6D` | Alerts and selected attention states; use sparingly |
| `brand-teal` | `#18FFC8` | Secondary data/status accent; use sparingly |
| `white` | `#FFFFFF` | Main text and inverse surfaces when appropriate |
| `ink` | approximately `#08090A` | Main dark canvas |
| `surface-1` | approximately `#111214` | Cards/panels above the canvas |
| `surface-2` | approximately `#191A1C` | Elevated controls, selected rows, chips |
| `border-subtle` | translucent cool gray | Card divisions and control outlines |
| `text-muted` | gray with accessible contrast | Descriptions, timestamps, secondary labels |

Use CSS custom properties/semantic Tailwind tokens rather than scattering raw values. Values other than the four printed reference hex values are provisional estimates from the images and must be tuned after visual review. Confirm the final brand palette before shipping.

## Typography and shapes

- Reference typeface: **Inter**, with light/regular/medium weights shown. Confirm licensing and self-hosting/source before selecting the font package; use a system sans fallback during scaffolding.
- Prioritize legible numerical data and readable labels on tablets/phones; avoid thin weights for small or low-contrast text.
- Use rounded cards and pill controls as part of the visual identity, but keep radius/spacing tokens consistent rather than varying per screen.
- Charts require labels/legends and accessible textual summaries; color alone must not encode meaning.

## Responsive requirements

All business information and actions remain available across phone, tablet, desktop, and large display. Reflow rather than omit. The desktop dashboard's multi-column card grid can become a single-column stack on phones and a deliberate two-column composition on tablets. Preserve card order by task priority, not by arbitrary shrinking.

- **Phone:** one-column content, touch-sized controls, compact but complete navigation, drawers/sheets for secondary navigation, horizontally scroll only when a data visualization truly needs it and provide an alternate summary.
- **Tablet:** intentional layouts for portrait and landscape; support touch, keyboard, and stylus; use two-column cards where readable; keep primary navigation and key actions discoverable.
- **Desktop/large display:** multi-column dashboard, sensible max-widths, full navigation, no excessive empty stretching; charts and tables remain readable.
- Tables should become labeled cards or carefully designed horizontal scrollers on narrow screens; do not hide columns that represent meaningful business data.
- Respect safe areas, orientation changes, browser zoom, and reduced-motion preferences.

## Interaction and accessibility

- Provide hover, focus-visible, pressed, disabled, loading, success, and error states.
- Minimum touch target should be comfortably usable (aim for 44×44 CSS px where practical).
- Maintain clear focus rings, semantic headings/landmarks, keyboard operation, proper form labels, and screen-reader status announcements.
- Meet WCAG AA contrast, particularly lime on white and muted gray on near-black; select foregrounds based on contrast measurements, not appearance alone.
- Avoid using lime alone for success or coral alone for failure; pair status color with text/icon.
- Keep animation subtle and honor `prefers-reduced-motion`.

## CSS implementation guidance

The current app already uses Tailwind CSS 4 and Vite. Its existing `resources/css/management.css` has theme variables and dark-mode patterns, but is a legacy tenant-app stylesheet and currently defaults to a different crimson brand. V2 should define its own token layer in `v2_frontend/` rather than editing legacy global CSS during planning. Consider a shared token core plus surface-specific theme layers, so FitFlow product pages, the super-admin app, tenant landing pages, staff app, and member app can share primitives while allowing tenant brand customization where approved.

Do not invent complete page arrangements yet. The page layouts, dashboard metrics, navigation hierarchy, and component breakdown remain pending user direction.
