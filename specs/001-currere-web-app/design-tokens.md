# Design Tokens: Currere Web

**Source**: Android app theme (`nl.teunk.currere.ui.theme`)
**Approach**: Dark-first theme. Map Android Material 3 tokens to Tailwind CSS custom properties.

## Color Palette

### Backgrounds & Surfaces

| Token            | Android Hex | CSS Variable              | Usage                     |
|------------------|-------------|---------------------------|---------------------------|
| DarkBackground   | `#0D0D0D`   | `--background`            | Page background           |
| DarkSurface      | `#1A1A1A`   | `--card`, `--popover`     | Cards, panels             |
| DarkSurfaceVariant | `#242424` | `--muted`                 | Muted backgrounds         |
| DarkOutline      | `#3A3A3A`   | `--border`                | Borders, dividers         |
| DarkOutlineVariant | `#2E2E2E` | `--input`                 | Input borders             |

### Accent (Primary)

| Token          | Android Hex | CSS Variable              | Usage                     |
|----------------|-------------|---------------------------|---------------------------|
| LimeGreen      | `#CDDC39`   | `--primary`               | Buttons, links, highlights|
| LimeGreenDark  | `#99AA00`   | `--primary` (hover/ring)  | Hover states, focus rings |
| onPrimary      | `#0D0D0D`   | `--primary-foreground`    | Text on primary buttons   |

### Text

| Token         | Android Hex | CSS Variable              | Usage                     |
|---------------|-------------|---------------------------|---------------------------|
| TextPrimary   | `#FFFFFF`   | `--foreground`            | Primary text              |
| TextSecondary | `#9E9E9E`   | `--muted-foreground`      | Secondary text, labels    |
| TextTertiary  | `#616161`   | `--ring` (or custom)      | Tertiary, disabled text   |

### Chart Colors

| Token          | Android Hex | CSS Variable              | Usage                     |
|----------------|-------------|---------------------------|---------------------------|
| ChartHeartRate | `#E57373`   | `--chart-heart-rate`      | Heart rate line charts    |
| ChartPace      | `#64B5F6`   | `--chart-pace`            | Pace line charts          |

### Functional

| Token     | Android Hex | CSS Variable              | Usage                     |
|-----------|-------------|---------------------------|---------------------------|
| ErrorRed  | `#EF5350`   | `--destructive`           | Errors, destructive       |
| SplitFast | `#66BB6A`   | `--split-fast`            | Fast pace splits (green)  |
| SplitSlow | `#EF5350`   | `--split-slow`            | Slow pace splits (red)    |

## Typography

The web dashboard uses the system font stack from the starter kit (Inter/system).
Weight and size mappings from Android to Tailwind:

| Android Style     | Weight    | Size  | Tailwind Equivalent          |
|-------------------|-----------|-------|------------------------------|
| headlineLarge     | Bold      | 32sp  | `text-3xl font-bold`         |
| headlineMedium    | Bold      | 28sp  | `text-2xl font-bold`         |
| headlineSmall     | Bold      | 24sp  | `text-xl font-bold`          |
| titleLarge        | SemiBold  | 22sp  | `text-lg font-semibold`      |
| titleMedium       | SemiBold  | 16sp  | `text-base font-semibold`    |
| titleSmall        | SemiBold  | 14sp  | `text-sm font-semibold`      |

## Implementation Notes

- The starter kit already supports dark mode via `appearance` cookie and CSS variables.
- Override the shadcn-svelte dark theme CSS variables in `resources/css/app.css` to match these tokens.
- Chart colors are custom variables used by LayerChart components.
- The lime green accent (`#CDDC39`) replaces the default shadcn primary color in dark mode.
- Keep the starter kit's light mode as a secondary option, but dark mode is the default matching the Android app.
