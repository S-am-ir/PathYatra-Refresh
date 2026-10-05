# YatraPath — Asset & Photography Credits

Developed for Nepal Tourism as a 6th Semester BCA Capstone Project at Tribhuvan University.

---

## 1. Brand & Vector Identity

| Asset | Source / Credit | License / Notes |
|---|---|---|
| **logo.svg** | Client / Project Identity | Protected Trademark. Unmodified original vector asset. |
| **Icons** | [Lucide Icons](https://lucide.dev/) | ISC License |
| **Topo Pattern** | Procedural SVG contour lines | Custom MIT / Project Original |
| **Mountain Silhouettes** | Custom layered vector paths | Custom MIT / Project Original |
| **Prayer Flags SVG** | Himalayan traditional 5-tone vector | Custom MIT / Project Original |

---

## 2. Typography

| Typeface | Classification | Source | License |
|---|---|---|---|
| **Fraunces** | Editorial Serif (Headlines) | Google Fonts / Phaedra Charles & Flavia Zimbardi | SIL Open Font License 1.1 |
| **Inter** | Modern Sans-Serif (Body & UI) | Google Fonts / Rasmus Andersson | SIL Open Font License 1.1 |
| **Noto Serif Devanagari** | Nepali / Devanagari Accent | Google Fonts | SIL Open Font License 1.1 |

---

## 3. Map & Geographic Data

| Layer | Source | Terms |
|---|---|---|
| **Leaflet.js** | Leaflet contributors | BSD 2-Clause |
| **CartoDB Voyager Tiles** | © [OpenStreetMap](https://www.openstreetmap.org/copyright) contributors, © [CARTO](https://carto.com/attributions) | CC BY-SA / CARTO Free Tier |
| **Coordinates** | Nepal Tourism Board reference points | Public Domain |

---

## 4. Photography Placeholder Tones & Replacement Guide

The current build uses palette-accurate tonal color blocks matching the design tokens (`--ink`, `--pine`, `--slate`, `--paper`, `--marigold`).

When dropping real photographs into `src/assets/` or `public/`:
- **Aspect Ratios**: 16:9 or 3:2 for heroes and cards.
- **Color Grading**: Warm, natural grading with slightly lifted blacks and muted saturation; avoid neon oversaturation.
- **Formats**: AVIF / WebP with JPG fallback.
- **Resolution**: 2400px wide for hero images (< 250 KB), 1200px wide for destination cards (< 120 KB).
