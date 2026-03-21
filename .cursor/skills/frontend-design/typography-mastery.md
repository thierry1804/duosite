# Typography Mastery

Advanced typography patterns for memorable web design.

## Font Psychology

### Serif Fonts - Trust, Tradition, Elegance

**Playfair Display**
- Use for: Luxury brands, editorial content, high-end products
- Pairs with: DM Sans, Source Sans Pro
- Emotional tone: Sophisticated, refined, established

**Crimson Pro**
- Use for: Publishing, reading-heavy sites, academic content
- Pairs with: Work Sans, IBM Plex Sans
- Emotional tone: Intellectual, trustworthy, classic

**Lora**
- Use for: Blogs, articles, storytelling
- Pairs with: Lato, Nunito Sans
- Emotional tone: Warm, friendly, readable

**Fraunces**
- Use for: Creative agencies, unique brands, art platforms
- Pairs with: Outfit, Cabinet Grotesk
- Emotional tone: Quirky, distinctive, memorable

### Sans-Serif Fonts - Modern, Clean, Approachable

**DM Sans**
- Use for: SaaS products, modern brands, tech companies
- Pairs with: Playfair Display, Spectral
- Emotional tone: Professional, geometric, clean

**Manrope**
- Use for: Startups, apps, digital products
- Pairs with: Lora, Merriweather
- Emotional tone: Friendly, open, modern

**Outfit**
- Use for: Fashion, lifestyle, creative projects
- Pairs with: Fraunces, Crimson Pro
- Emotional tone: Contemporary, stylish, versatile

**Sora**
- Use for: Tech products, futuristic brands, AI/ML companies
- Pairs with: JetBrains Mono, Fira Code
- Emotional tone: Technical, forward-thinking, precise

**Cabinet Grotesk**
- Use for: Design studios, portfolios, agencies
- Pairs with: Spectral, Libre Baskerville
- Emotional tone: Refined, sophisticated, modern

### Display Fonts - Impact, Personality, Headlines

**Anton**
- Use for: Headlines, impact statements, sports brands
- Pairs with: Roboto Condensed, Source Sans Pro
- Emotional tone: Bold, powerful, condensed

**Bebas Neue**
- Use for: Posters, banners, attention-grabbing headers
- Pairs with: Montserrat, Open Sans
- Emotional tone: Strong, condensed, impactful

**Righteous**
- Use for: Fun brands, gaming, youth-oriented products
- Pairs with: Quicksand, Varela Round
- Emotional tone: Playful, rounded, energetic

**Unbounded**
- Use for: Tech brands, futuristic products, innovation
- Pairs with: IBM Plex Sans, JetBrains Mono
- Emotional tone: Modern, geometric, tech-forward

## Typography Scale Systems

### Modular Scale (Musical Harmony)

```css
/* Major Third (1.25) */
:root {
  --text-xs: 0.64rem;
  --text-sm: 0.8rem;
  --text-base: 1rem;
  --text-lg: 1.25rem;
  --text-xl: 1.563rem;
  --text-2xl: 1.953rem;
  --text-3xl: 2.441rem;
  --text-4xl: 3.052rem;
  --text-5xl: 3.815rem;
}

/* Golden Ratio (1.618) - Maximum drama */
:root {
  --text-xs: 0.382rem;
  --text-sm: 0.618rem;
  --text-base: 1rem;
  --text-lg: 1.618rem;
  --text-xl: 2.618rem;
  --text-2xl: 4.236rem;
  --text-3xl: 6.854rem;
}
```

### Fluid Typography

```css
.title {
  font-size: clamp(2rem, 5vw, 5rem);
}

.heading {
  font-size: calc(1.5rem + 2vw);
  line-height: 1.2;
}
```

## Advanced Techniques

### Optical Alignment

```css
.display-title {
  font-size: 8rem;
  letter-spacing: -0.04em;
}

.caption {
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
```

### Text Rendering

```css
body {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
  font-feature-settings: "kern" 1, "liga" 1;
}
```

### Line Length & Height

```css
.article-content {
  max-width: 65ch;
  margin-inline: auto;
}

.body-text {
  font-size: 1.125rem;
  line-height: 1.7;
}
```

## Typography by Style

### Brutalist

```css
:root {
  --font-display: 'Arial Black', sans-serif;
  --font-body: 'Courier New', monospace;
}

.brutalist-title {
  font-family: var(--font-display);
  font-size: clamp(3rem, 10vw, 10rem);
  text-transform: uppercase;
  line-height: 0.9;
  font-weight: 900;
}
```

### Editorial

```css
:root {
  --font-display: 'Playfair Display', serif;
  --font-body: 'Source Serif Pro', serif;
}

.editorial-title {
  font-family: var(--font-display);
  font-size: clamp(2.5rem, 6vw, 6rem);
  font-weight: 700;
  line-height: 1.1;
}
```

### Tech/Futuristic

```css
:root {
  --font-display: 'Unbounded', sans-serif;
  --font-body: 'IBM Plex Sans', sans-serif;
  --font-mono: 'JetBrains Mono', monospace;
}
```

## Variable Fonts

```css
@font-face {
  font-family: 'Recursive';
  src: url('Recursive-VariableFont.woff2') format('woff2-variations');
  font-weight: 300 1000;
  font-style: oblique 0deg 15deg;
}

.variable-text {
  font-family: 'Recursive', sans-serif;
  font-variation-settings: 'MONO' 0, 'CASL' 0, 'wght' 700, 'slnt' 0, 'CRSV' 0.5;
}
```

## Typography Checklist

- [ ] Fonts are distinctive (not Inter/Roboto/Arial)
- [ ] Display and body fonts pair well
- [ ] Font sizes have dramatic contrast
- [ ] Line length 45–75 characters
- [ ] Letter-spacing adjusted for large/small text
- [ ] Hierarchy is immediately clear
- [ ] Fallback fonts specified
