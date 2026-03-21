---
name: frontend-design
description: Create distinctive, production-grade frontend interfaces with high design quality. Use this skill when the user asks to build web components, pages, artifacts, posters, or applications (examples include websites, landing pages, dashboards, React components, HTML/CSS layouts, or when styling/beautifying any web UI). Generates creative, polished code and UI design that avoids generic AI aesthetics.
---

# Frontend Design

Transform web interfaces into bold, memorable experiences with distinctive aesthetics and production-grade implementation.

## When to Use This Skill

Use when:
- User uploads a screenshot asking for design improvements
- User requests to build a website, landing page, or web app
- User asks to "make it look better" or "improve the design"
- User wants to create a dashboard, portfolio, or marketing site
- User needs to transform existing UI into something memorable
- User asks for creative or bold design direction

## Design Thinking Process

Before writing any code, analyze and commit to a design direction:

### 1. Understand Context (30 seconds)

Ask yourself:
- **Purpose**: What problem does this interface solve? Who uses it?
- **Users**: Technical? Non-technical? Age group? Context of use?
- **Content**: Heavy text? Visual-first? Data-driven? E-commerce?
- **Brand**: Startup? Enterprise? Creative? Professional? Playful?

### 2. Choose Bold Aesthetic Direction (CRITICAL)

Pick ONE extreme direction and commit fully:

**Minimalist Directions:**
- **Brutally Minimal** - Stark contrast, system fonts, zero decoration, maximum whitespace
- **Swiss Modernism** - Grid-based, geometric, structured, limited color
- **Japanese Zen** - Natural materials, subtle shadows, breathing room, muted tones
- **Scandinavian** - Clean lines, natural light, soft colors, functional beauty

**Maximalist Directions:**
- **Maximalist Chaos** - Layered elements, bold typography, explosive color, controlled chaos
- **Neo-Brutalism** - Bold shapes, thick borders, shadow extrusion, high contrast
- **Y2K/Cyber** - Metallic gradients, chrome effects, futuristic UI, neon accents
- **Retro-Futuristic** - 70s/80s sci-fi, analog/digital fusion, warm gradients

**Distinctive Styles:**
- **Editorial/Magazine** - Bold typography hierarchy, generous images, asymmetric grids
- **Art Deco/Geometric** - Symmetry, gold accents, geometric patterns, luxury feel
- **Organic/Natural** - Curves, earth tones, textures, hand-drawn elements
- **Soft/Pastel** - Gentle gradients, rounded corners, light colors, dreamy atmosphere
- **Industrial/Utilitarian** - Raw materials, monospace fonts, technical aesthetic
- **Glassmorphism** - Frosted glass effects, transparency, layered depth, subtle blur
- **Neumorphism** - Soft shadows, subtle extrusion, tactile interfaces
- **Claymorphism** - 3D clay-like textures, soft shadows, playful depth

**NEVER** choose generic, context-free aesthetics. The direction must fit the purpose.

### 3. Define Differentiation

What makes this design UNFORGETTABLE?

Examples:
- "The hero section uses diagonal type layout with aggressive cropping"
- "Navigation dissolves into view with particle effects"
- "Cards cast dramatic shadows that respond to cursor position"
- "Typography scales dramatically from 12px to 120px in one scroll"
- "Background has animated gradient mesh that shifts with scroll"
- "Interactive elements have tactile, physics-based feedback"

**One Memorable Thing Rule**: Every design needs ONE standout feature people will remember.

### 4. Technical Constraints

Consider:
- Framework (HTML/CSS, React, Vue, etc.)
- Performance requirements
- Accessibility (WCAG AA minimum)
- Browser support
- Animation performance
- Responsive behavior

## Typography: The Foundation

Typography is 80% of design. Get this right first.

### Font Selection Strategy

**AVOID THESE OVERUSED FONTS:**
- ❌ Inter
- ❌ Roboto
- ❌ Arial
- ❌ Helvetica
- ❌ System fonts (unless intentionally brutalist)
- ❌ Space Grotesk (overused in AI designs)

**RECOMMENDED FONT CATEGORIES:**

**Display/Heading Fonts (Bold Personality):**
- Serif: Playfair Display, Crimson Pro, Spectral, Lora, Fraunces
- Sans-serif: DM Sans, Manrope, Outfit, Sora, Cabinet Grotesk, Archivo
- Geometric: Montserrat, Raleway, Poppins (use sparingly)
- Condensed: Anton, Bebas Neue, Oswald, Barlow Condensed
- Editorial: Bodoni Moda, Libre Baskerville, Cormorant
- Experimental: Righteous, Unbounded, Rubik, Recursive

**Body/Text Fonts (Readable Comfort):**
- Classic: Source Sans Pro, Public Sans, IBM Plex Sans, Work Sans
- Modern: Instrument Sans, Geist, Satoshi, Plus Jakarta Sans
- Editorial: Lora, Merriweather, Source Serif Pro, Bitter
- Technical: JetBrains Mono, Fira Code, IBM Plex Mono

### Typography Hierarchy

Create dramatic contrast in scale:

```css
/* GOOD - Dramatic contrast */
.hero-title { font-size: clamp(48px, 8vw, 120px); }
.section-title { font-size: clamp(32px, 4vw, 64px); }
.body-text { font-size: 18px; }
.caption { font-size: 14px; }

/* BAD - Timid scaling */
.hero-title { font-size: 36px; }
.section-title { font-size: 24px; }
.body-text { font-size: 16px; }
```

### Font Pairing Patterns

**Pattern 1: Contrast** (Serif + Sans)
```css
:root {
  --font-display: 'Playfair Display', serif;
  --font-body: 'DM Sans', sans-serif;
}
```

**Pattern 2: Harmony** (Same family, different weights)
```css
:root {
  --font-display: 'Outfit', sans-serif;
  --font-body: 'Outfit', sans-serif;
  --weight-display: 700;
  --weight-body: 400;
}
```

**Pattern 3: Editorial** (Display Serif + Text Serif)
```css
:root {
  --font-display: 'Fraunces', serif;
  --font-body: 'Lora', serif;
}
```

For advanced typography (font psychology, modular scale, variable fonts), see [typography-mastery.md](typography-mastery.md).

## Color Theory & Themes

### Avoid Generic Palettes

**NEVER USE:**
- ❌ Purple gradients on white background
- ❌ Blue (#0070f3) + White (Vercel clone)
- ❌ Generic rainbow gradients
- ❌ Equal distribution of colors

### Color Strategy

**Dominant + Accent Pattern:**
```css
:root {
  --primary: #1a1a1a;
  --accent: #ff6b35;
  --highlight: #f7f052;
  --background: #fafafa;
  --text: #2d2d2d;
}
```

**High Contrast/Bold:**
```css
:root {
  --black: #000000;
  --white: #ffffff;
  --red: #ff0000;
  --yellow: #ffff00;
}
```

### Dark Mode Excellence

Don't just invert colors:

```css
:root[data-theme="dark"] {
  --background: #0a0a0a;
  --surface: #1a1a1a;
  --text: #e5e5e5;
  --text-muted: #a3a3a3;
  --border: rgba(255,255,255,0.1);
  --accent: #ff6b35;
}
```

## Motion & Animation

### Animation Philosophy

**One Orchestrated Moment > Many Scattered Micro-interactions**

Focus on: Page load (staggered reveals), scroll triggers, hover states, state changes.

### CSS-First Approach

Prefer CSS animations for performance. For full pattern library (staggered fade-in, scroll reveals, Framer Motion), see [animation-patterns.md](animation-patterns.md).

```css
.hero-content > * {
  animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) backwards;
}
.hero-content > *:nth-child(1) { animation-delay: 0.1s; }
.hero-content > *:nth-child(2) { animation-delay: 0.2s; }

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
```

### Respect Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

## Spatial Composition

Break the grid. Asymmetric layouts, diagonal flow, generous negative space.

```css
.grid-asymmetric {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 60px;
}

.hero { padding: 15vh 10vw; min-height: 100vh; }
```

## Backgrounds & Visual Details

Gradient meshes, noise texture, glassmorphism, dramatic shadows — create atmosphere. Use `backdrop-filter` for glass; layered `box-shadow` for depth.

## Production-Grade Implementation

- Semantic HTML5, BEM or utility-class, CSS custom properties, mobile-first
- Optimize images (WebP, lazy loading), CSS transforms for animations
- ARIA, alt text, labels; sufficient contrast (WCAG AA)
- Fluid typography: `font-size: clamp(32px, 5vw, 80px);`

## Implementation Workflow

1. **Analyze** (if screenshot): current aesthetic, palette, typography, layout, weaknesses
2. **Propose direction**: suggest 2–3 bold directions based on context
3. **Implement**: production-ready code, responsive, accessible, copy-paste usable
4. **Explain**: typography choice, color psychology, animation strategy, memorability

## Critical Reminders

1. **Never converge** on the same aesthetic twice
2. **Avoid generic** fonts (Inter, Roboto, Arial, Space Grotesk)
3. **One memorable thing** — every design needs a standout feature
4. **Match complexity to vision** — maximalist = elaborate, minimalist = restraint
5. **Bold choices** — commit fully to the aesthetic direction
6. **Context matters** — design for the actual use case
7. **Production-ready** — code should work, not just look pretty
8. **Accessibility** — beautiful AND usable by everyone

## Additional Resources

- For font psychology, modular scale, variable fonts, and typography by style: [typography-mastery.md](typography-mastery.md)
- For page load, hover, scroll-triggered, and Framer Motion patterns: [animation-patterns.md](animation-patterns.md)
