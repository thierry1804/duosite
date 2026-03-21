# Animation Patterns Library

Production-ready animation patterns for memorable web experiences.

## Philosophy

**Quality over Quantity**: One well-orchestrated moment beats scattered micro-interactions.

**Performance First**: Animate transform and opacity only. Avoid width, height, top, left.

**Respect Preferences**: Always include reduced-motion fallbacks.

## Page Load Animations

### Staggered Fade-In

```css
.fade-in-stagger > * {
  animation: fadeInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) backwards;
}

.fade-in-stagger > *:nth-child(1) { animation-delay: 0.1s; }
.fade-in-stagger > *:nth-child(2) { animation-delay: 0.2s; }
.fade-in-stagger > *:nth-child(3) { animation-delay: 0.3s; }

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
```

### Scale-In Reveal

```css
.scale-in {
  animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1); }
}
```

## Hover Effects

### Lift & Shadow

```css
.card-lift {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-lift:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15), 0 10px 10px rgba(0, 0, 0, 0.1);
}
```

### Underline Expansion

```css
.link-underline {
  position: relative;
  text-decoration: none;
}

.link-underline::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 2px;
  background: currentColor;
  transition: width 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.link-underline:hover::after {
  width: 100%;
}
```

## Scroll-Triggered

### Intersection Observer

```javascript
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
```

```css
.reveal {
  opacity: 0;
  transform: translateY(50px);
  transition: opacity 0.8s ease, transform 0.8s ease;
}

.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}
```

## Framer Motion (React)

### Stagger Children

```jsx
const containerVariants = {
  hidden: { opacity: 0 },
  visible: {
    opacity: 1,
    transition: { staggerChildren: 0.1, delayChildren: 0.2 }
  }
};

const itemVariants = {
  hidden: { opacity: 0, y: 20 },
  visible: { opacity: 1, y: 0 }
};

<motion.ul variants={containerVariants} initial="hidden" animate="visible">
  {items.map(item => (
    <motion.li key={item.id} variants={itemVariants}>{item.text}</motion.li>
  ))}
</motion.ul>
```

### Gesture Animations

```jsx
<motion.button
  whileHover={{ scale: 1.05, boxShadow: "0 10px 30px rgba(0,0,0,0.2)" }}
  whileTap={{ scale: 0.95 }}
  transition={{ type: "spring", stiffness: 400, damping: 17 }}
>
  Interactive Button
</motion.button>
```

## Loading Animations

### Skeleton Shimmer

```css
.skeleton {
  background: linear-gradient(
    90deg,
    #f0f0f0 0%,
    #e0e0e0 50%,
    #f0f0f0 100%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
```

## Performance

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

### Easing

```css
:root {
  --ease-spring: cubic-bezier(0.68, -0.55, 0.265, 1.55);
  --ease-smooth: cubic-bezier(0.16, 1, 0.3, 1);
}
```

**Checklist**: Animate only transform/opacity; use will-change sparingly; include reduced-motion; debounce scroll listeners.
