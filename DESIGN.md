---
version: alpha
name: Recruivo
description: Design language for the Recruivo job-platform web app — Tailwind v3 utilities, one amber accent over stone neutrals, dark-first class theme.
colors:
  primary: "#d97706"
  primary-hover: "#f59e0b"
  primary-text: "#d97706"
  primary-subtle: "#fef3c7"
  secondary: "#0f766e"
  neutral: "#78716c"
  canvas: "#f5f5f4"
  surface: "#ffffff"
  foreground: "#1c1917"
  muted: "#57534e"
  border: "#e7e5e4"
  danger: "#dc2626"
  success: "#15803d"
typography:
  sans:
    fontFamily: Plus Jakarta Sans
  display:
    fontFamily: Space Grotesk
    letterSpacing: -0.02em
rounded:
  lg: 0.5rem
  xl: 0.75rem
  2xl: 1rem
  3xl: 1.5rem
  full: 9999px
spacing:
  container: 72rem
  container-wide: 80rem
  touch-target: 44px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#ffffff"
    rounded: "{rounded.2xl}"
    padding: 12px 24px
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.muted}"
    rounded: "{rounded.xl}"
    padding: "10px 24px"
  button-destructive:
    backgroundColor: "{colors.danger}"
    textColor: "#ffffff"
    rounded: "{rounded.2xl}"
    padding: "12px 24px"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.foreground}"
    rounded: "{rounded.xl}"
    padding: 24px
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.muted}"
    rounded: "{rounded.2xl}"
    padding: "12px 16px"
  chip-amber:
    backgroundColor: "{colors.primary-subtle}"
    textColor: "#b45309"
    rounded: "{rounded.full}"
    padding: "2px 10px"
  link:
    textColor: "{colors.primary-text}"
  status-badge:
    rounded: "{rounded.full}"
    padding: "4px 12px"
  status-badge-pending:
    backgroundColor: "#fef9c3"
    textColor: "#a16207"
  status-badge-shortlisted:
    backgroundColor: "#dbeafe"
    textColor: "#1d4ed8"
  status-badge-interview:
    backgroundColor: "#ede9fe"
    textColor: "#6d28d9"
  status-badge-accepted:
    backgroundColor: "#dcfce7"
    textColor: "{colors.success}"
  status-badge-rejected:
    backgroundColor: "#fee2e2"
    textColor: "#b91c1c"
  status-badge-withdrawn:
    backgroundColor: "#e7e5e4"
    textColor: "#44403c"
---

## Overview

Recruivo is a job platform — candidates find and apply to roles, recruiters post jobs and run
application pipelines, admins operate the platform. The web product is one Inertia 3 + Vue 3 app
styled exclusively with Tailwind CSS v3 utilities plus the `@tailwindcss/forms` reset; there is no
component library and no CSS-in-JS layer.

The evidenced direction: a single warm amber accent on a `stone` neutral ramp, laid on translucent
blurred surfaces over a soft radial wash, with hierarchy carried by tinted translucency and hairline
borders rather than heavy elevation. The theme is class-based on `<html>` and dark-first — the server
renders `dark` unless the `recruivo:theme` cookie says `light` — so light and dark utilities are
always written together at the same call site.

## Colors

The palette is one accent, one neutral ramp, and semantic feedback colors. `stone` is the only
neutral family; the admin shell hardcodes a zinc canvas instead of a stone step — the single off-ramp
outlier in the product.

- **Primary (`{colors.primary}`):** the amber-600 fill for every solid action — primary buttons,
  active tabs, the skip link, the Inertia progress bar — with `hover:bg-amber-500`
  (`{colors.primary-hover}`). It does not change between themes.
- **Primary text (`{colors.primary-text}`):** the accent for interactive text and icons. Amber-600 in
  light, amber-400 in dark. Use it for nav active state, links, link hover (stone → amber), and
  icon accents — never for body copy.
- **Primary subtle (`{colors.primary-subtle}`):** tinted chip, badge and icon-circle backgrounds
  (amber-100 light, amber-500/10 dark). Text sitting on an amber tint uses the amber-700 step
  (amber-300 in dark), one step deeper than `{colors.primary-text}`.
- **Secondary (`{colors.secondary}`):** teal is decorative, never interactive — the logo mark
  gradient, the guest-shell wash, the admin chart's second series (teal-400 in dark).
- **Neutral:** the stone ramp. `{colors.canvas}` stone-100 backs the app shell (the guest/auth shell
  narrows to stone-50); `{colors.surface}` is card/panel white, always used at partial alpha over the
  canvas; `{colors.foreground}` stone-900 for headings and body; `{colors.muted}` stone-600 for
  secondary copy; `{colors.border}` stone-200 for every hairline. Stone-400/500 carry decorative and
  muted icons (empty states, metadata).
- **Danger (`{colors.danger}`):** red-600 for destructive fills and error text; secondary error copy
  and error text in dark use red-400.
- **Success (`{colors.success}`):** the green family. Success alerts and applied states pair a
  green-50 surface with green-200 borders and green-800 text; badges use green-100 on green-700.
- **Application statuses:** each status owns one flat family — a `*-100` background with a `*-700`
  text and a solid dot, inverting to `*-500/10` on `*-400` in dark. Pending is yellow, shortlisted
  blue, interview violet, accepted green, rejected red, withdrawn stone. Status color is data
  semantics, not brand: never color an inert surface with a status family.

## Themes

Both themes ship from one class switch on `<html>` (`darkMode: 'class'`), never from duplicated
components. `ThemeToggle` keeps the class, `localStorage` and the `recruivo:theme` cookie in sync;
the cookie is what the server reads to render the initial class. Write each surface as a light
utility plus its `dark:` counterpart on the same element.

| Token | Light | Dark |
| --- | --- | --- |
| `colors.canvas` | `#f5f5f4` (stone-100; guest shell `#fafaf9`) | `#0c0a09` (stone-950) |
| `colors.surface` | `#ffffff` at 80% over canvas | `#1c1917` (stone-900) at 60% over canvas |
| `colors.foreground` | `#1c1917` (stone-900) | `#f5f5f4` (stone-100); display headings use white |
| `colors.muted` | `#57534e` (stone-600) | `#a8a29e` (stone-400) |
| `colors.border` | `#e7e5e4` (stone-200) | `#44403c` (stone-700) |
| `colors.neutral` | `#78716c` (stone-500) | `#a8a29e` (stone-400) |
| `colors.primary-text` | `#d97706` (amber-600) | `#fbbf24` (amber-400) |
| `colors.primary-subtle` | `#fef3c7` (amber-100) | `#f59e0b` at 10% |
| `colors.secondary` | `#0f766e` (teal-700) | `#2dd4bf` (teal-400) |
| `colors.primary` | unchanged | unchanged |
| `colors.primary-hover` | unchanged | unchanged |
| `colors.danger` | unchanged as a fill | error text switches to `#f87171` (red-400) |
| `colors.success` | unchanged as a fill | success text switches to `#4ade80` (green-400) |

Dark surfaces use two floors: panels and cards sit on `dark:bg-stone-900/60`, while bars, popovers
and modal panels sit on `dark:bg-stone-950/80`–`/95`.

## Typography

Two self-hosted variable fonts, both OFL, served from this origin.

- **Sans (`{typography.sans}`):** Plus Jakarta Sans (weights 200–800) is the canvas default — body
  copy, labels, buttons, nav, and the Chart.js canvas font. Never set a fallback chain inline; the
  `font-sans` theme value owns it.
- **Display (`{typography.display}`):** Space Grotesk, applied globally to `h1`–`h4` with the
  `-0.02em` tracking in `resources/css/app.css`. Do not re-state `font-display`/`tracking-tight` on
  a heading; the global rule already covers it.
- **Hierarchy:** page title `text-3xl font-bold`, hero title `text-4xl font-bold sm:text-5xl`,
  section heading `text-xl`/`text-2xl font-semibold`, lede `text-lg text-stone-600 dark:text-stone-400`,
  body `text-sm`/`text-base`, metadata and eyebrows `text-xs` (or `text-[11px] uppercase tracking-wider`
  for labels). Size steps come from the Tailwind scale; do not add custom `text-[…]` sizes.
- Weight is limited to `font-medium`, `font-semibold` and `font-bold`; `font-bold` is reserved for
  titles and emphasis numerals.

## Layout

- **Content column:** `{spacing.container}` (`max-w-6xl`) with `px-4 sm:px-6` and main padding
  `pb-20 pt-10 sm:pb-16`. The admin shell uses `{spacing.container-wide}` (`max-w-7xl`); reading and
  form surfaces re-narrow the same column with `max-w-4xl`/`3xl`/`2xl`.
- **Chrome bars:** `h-16` for the site header, admin header and admin sidebar logo row.
- **Page rhythm:** `space-y-8` between page sections (Home `space-y-12`, Search `space-y-6`), and
  `gap-6` grids — `grid gap-6 md:grid-cols-2` for card lists, `grid gap-4 sm:grid-cols-2 sm:gap-6
  lg:grid-cols-3` for results.
- **Mobile:** the fixed bottom nav reserves `pb-16 sm:pb-0` on the shell root.
- **Touch targets:** `{spacing.touch-target}` (`min-h-11`) is the minimum height for buttons, tabs,
  disclosure summaries and checkbox rows; compact chips may use `min-h-9`.

## Elevation & Depth

Depth is translucency, blur and hairlines, not shadow stacks.

- Cards and panels: `shadow-sm` lifting to `hover:shadow-md` on hover, with `backdrop-blur` and a
  `border-stone-200/60` hairline; panel/hover states change border color toward amber rather than
  adding elevation.
- Chrome bars and overlays: `bg-white/75`–`/95` with `backdrop-blur`/`-sm`/`-xl`; modals add a
  `bg-stone-900/75 backdrop-blur-sm` scrim and a `shadow-2xl` panel (popovers `shadow-xl`/`shadow-2xl`).
- The only colored shadow is the primary-CTA glow: `shadow-lg shadow-amber-500/30`, reserved for
  large page CTAs. Chrome-level primary buttons use a plain `shadow-sm`; do not promote the glow into
  compact controls. Destructive CTAs mirror it with `shadow-red-500/30`.

## Shapes

Radius is a fixed five-step vocabulary resolved through the Tailwind theme:

- `{rounded.lg}` inputs, small controls, disclosure summaries and neutral tags.
- `{rounded.xl}` cards, panels, popovers, list rows and secondary buttons.
- `{rounded.2xl}` primary buttons, panel-level inputs, dialog panels and large CTAs.
- `{rounded.3xl}` auth cards and the site-wide search modal only.
- `{rounded.full}` all chips, pills, status badges, avatars, icon buttons, progress tracks and dots.

Two deliberate splits: page CTAs are `rounded-2xl` while header chrome controls are `rounded-full`;
chips and pills are always `rounded-full` regardless of size. The remaining two radii are
incidental — `rounded-md` on the admin segmented control, inline link hovers and file-input buttons,
and the framework default `rounded` on checkboxes. New containers and controls must use one of the
five steps above.

Logo and avatar frames are square, `overflow-hidden`, with `object-cover` images; when no logo exists,
fall back to the initial letter on `bg-gradient-to-br from-amber-500 to-amber-600 text-white
font-semibold`, sized to match the frame.

## Components

- **Primary button:** `bg-amber-600` + `hover:bg-amber-500`, `text-white font-semibold`,
  `{rounded.2xl}`, and `shadow-lg shadow-amber-500/30` on page-level CTAs. Keyboard focus is
  `focus-visible:ring-2 focus-visible:ring-amber-500` with `ring-offset-2` (plus
  `dark:focus-visible:ring-offset-stone-950`); the lighter `focus:ring-2 focus:ring-amber-200` is the
  form-submit spelling. Disabled is `opacity-60` (`opacity-50` in auth forms) with the label swapped for the
  loading label, never a spinner.
- **Secondary button:** neutral outline `border border-stone-200 bg-white text-stone-700
  hover:bg-stone-50` (`dark:border-stone-700 dark:bg-stone-800 dark:hover:bg-stone-700`), used for
  load-more, cancel and clear-filters; the solid `bg-stone-100 dark:bg-stone-800` sibling is the
  in-panel cancel. The amber outline variant (`border-amber-600 text-amber-600 hover:bg-amber-50`) is
  for the second action of a hero or empty state. Do not add a third neutral treatment.
- **Destructive button:** `bg-red-600 hover:bg-red-500 text-white` with `shadow-red-500/30` — the
  confirming action inside a dialog, and the danger-zone trigger on the profile page. Deleting from
  a list row uses the tinted or outlined red chip instead.
- **Form controls:** canonical input is `w-full rounded-2xl border border-stone-200/80 bg-white/80
  px-4 py-3 text-sm text-stone-700 shadow-sm` with `focus:border-amber-400 focus:outline-none
  focus:ring-2 focus:ring-amber-200` and the paired `dark:` values (`dark:border-stone-700
  dark:bg-stone-900/70`). Labels are `text-sm font-medium text-stone-700 dark:text-stone-200`; help
  text is `text-xs text-stone-500 dark:text-stone-400`; field errors are `mt-1 text-xs text-red-600
  dark:text-red-400` rendered as a sibling paragraph — never restyle the input border on error.
  Checkboxes and radios keep the `@tailwindcss/forms` base and only override `text-amber-600`,
  `border-stone-300`, `dark:bg-stone-800`.
- **Cards and panels:** `rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur
  dark:border-stone-700/60 dark:bg-stone-900/60`, with `shadow-sm transition hover:shadow-md` when the
  whole card is a link. Muted tiles and empty states drop to `bg-white/60` / `dark:bg-stone-900/40`.
- **Status badge:** `rounded-full px-3 py-1 text-xs font-medium` with a `h-3 w-3` dot in the status
  color; text switches to `font-semibold` when the dot is omitted. Exact per-status values live in the
  `status-badge-*` tokens; a status with no branch falls back to a stone badge.
- **Chips:** amber tints (`bg-amber-100` / `dark:bg-amber-500/10` with amber-700 / amber-300 text) for
  job attributes and filters, `bg-stone-100 dark:bg-stone-800` for neutral tags, and an amber outline
  variant for active filter chips. All chips are `rounded-full` with amber-ringed focus.
- **Alerts:** `rounded-xl border px-4 py-2.5` — success `border-green-200 bg-green-50 text-green-800`,
  error `border-red-200 bg-red-50 text-red-800`, each with the `dark:border-{color}-800
  dark:bg-{color}-900/20 dark:text-{color}-200` inversion and `role="status"`/`role="alert"`. Toasts
  are fixed at the bottom with a 180 ms translateY transition.
- **Navigation:** the header is `sticky top-0` with `border-b border-stone-200/60 bg-white/75
  backdrop-blur-xl` (`dark:bg-stone-950/80`); links hover to amber, the active item takes
  `text-amber-600 dark:text-amber-400` plus a 2px `bg-amber-500` underline, and ghost controls use
  `hover:bg-stone-100 dark:hover:bg-stone-800` with amber focus rings.

## Do's and Don'ts

- Do pair every surface, border and text utility with its `dark:` counterpart on the same element.
- Do keep header chrome controls quieter than page content: `rounded-full` pills with `shadow-sm` in
  chrome, `rounded-2xl` CTAs with the amber glow in page bodies.
- Do keep tinted amber surfaces for chips, icon tiles and hover states — the solid amber fill is
  reserved for actions and the current tab.
- Do keep the `{spacing.touch-target}` minimum on every interactive control, including icon buttons
  and tabs.
- Do use `focus-visible:ring-2 focus-visible:ring-amber-500` for keyboard focus and reserve
  `focus:ring-2 focus:ring-amber-200` for text inputs.
- Don't space siblings with margins — use `gap` utilities in flex and grid containers.
- Don't ship a component with light-mode-only styling; new pages and components must support both
  themes the same way the existing ones do.
- Don't hardcode UI strings in Vue components — copy comes from the `translations` page prop.
- Don't add a parallel Blade-rendered version of any page; web pages render through
  `Inertia::render(...)`.
