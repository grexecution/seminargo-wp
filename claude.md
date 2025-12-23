# seminargo Theme - UI/UX Design System & Guidelines

## 🎯 CRITICAL: Layout Width Consistency

### Maximum Width Container Rules
**ALL sections must adhere to the same max-width for visual consistency:**

```css
/* Global container max-width */
--container-max-width: 1440px;
--container-padding: 2rem; /* 32px on each side */
```

### Section Width Requirements
1. **Header/Navbar**: Must align with content max-width
2. **Hero Search Widget**: Must align with content max-width
3. **Hero Image**: Must align with content max-width (with border-radius)
4. **Feature Sections**: Must align with content max-width
5. **Footer**: Must align with content max-width

### Implementation Pattern
```css
.container,
.search-widget-container,
.hero-image-section,
.features-grid {
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 2rem;
}
```

**IMPORTANT**: No section should exceed or be narrower than the global max-width. All content must align vertically when scrolling.

---

## 🎯 CRITICAL: Section Headers Pattern

### Standard Section Header Structure
**ALL content sections must use the consistent section header pattern with tagline, title, and accent line:**

```html
<div class="section-header">
    <span class="section-tagline">Tagline Text</span>
    <h2 class="section-title">Main Title</h2>
</div>
```

### Section Header Styling
```css
.section-header {
    text-align: center;
    margin-bottom: var(--spacing-3xl);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.section-tagline {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #AC2A6E;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.75rem;
}

.section-header .section-title {
    color: var(--color-text);
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    position: relative;
    display: inline-block;
}

.section-header .section-title::after {
    content: '';
    display: block;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #AC2A6E, #d64a94);
    margin: 1rem auto 0;
    border-radius: 2px;
}
```

### Usage Guidelines
1. **Always use this pattern** for major content sections (featured content, listings, categories, etc.)
2. **Tagline**: Short, descriptive text (2-4 words) in uppercase berry color
3. **Title**: Main heading describing the section content
4. **Accent line**: Gradient underline automatically added via CSS ::after

### Examples
- Tagline: "Unsere Empfehlungen" / Title: "Entdecken Sie unsere Top-Veranstaltungsorte"
- Tagline: "Für jeden Anlass" / Title: "Finden Sie Ihre perfekte Veranstaltungsart"
- Tagline: "Beliebte Regionen" / Title: "Angesagte Locations"
- Tagline: "Weitere Optionen" / Title: "Ähnliche Hotels"

---

## 📐 Design Philosophy

Our design system follows a **mobile-first**, **content-first**, and **accessibility-first** approach. Every design decision prioritizes clarity, usability, and performance across all devices and user abilities.

### Core Principles
1. **Clarity Over Cleverness** - Simple, clear interfaces that users understand instantly
2. **Consistency is Key** - Predictable patterns reduce cognitive load
3. **Performance Matters** - Fast load times improve user experience
4. **Accessibility for All** - WCAG 2.1 AA compliance minimum

---

## 🖥️ Responsive Breakpoints & Device Specifications

### Breakpoint System
```css
/* Mobile First Approach */
$breakpoint-xs: 320px;   /* Small phones */
$breakpoint-sm: 480px;   /* Phones */
$breakpoint-md: 768px;   /* Tablets */
$breakpoint-lg: 1024px;  /* Desktops */
$breakpoint-xl: 1200px;  /* Large desktops */
$breakpoint-2xl: 1440px; /* Extra large screens */
$breakpoint-3xl: 1920px; /* Full HD+ */
```

### Device-Specific Guidelines

#### 📱 Mobile (320px - 767px)
- **Container**: 100% width with 16px padding
- **Content width**: 288px - 735px
- **Columns**: Single column layout
- **Navigation**: Hamburger menu
- **Font size base**: 16px (never smaller)
- **Touch targets**: Minimum 44x44px
- **Line length**: 45-75 characters

#### 📱 Tablet (768px - 1023px)
- **Container**: 100% width with 24px padding
- **Content width**: 720px - 975px
- **Columns**: 2-column layouts possible
- **Navigation**: Horizontal or hamburger
- **Font size base**: 16px
- **Touch targets**: Minimum 44x44px
- **Line length**: 60-80 characters

#### 💻 Desktop (1024px - 1439px)
- **Container**: 1200px max-width
- **Content width**: 1024px - 1200px
- **Columns**: Up to 3-4 columns
- **Navigation**: Full horizontal menu
- **Font size base**: 16px
- **Click targets**: Minimum 32x32px
- **Line length**: 65-85 characters

#### 🖥️ Large Desktop (1440px+)
- **Container**: 1200px or 1440px max-width
- **Content width**: 1200px - 1440px
- **Columns**: Up to 4-6 columns
- **Navigation**: Full horizontal with mega-menus
- **Font size base**: 18px optional
- **Click targets**: Standard mouse precision
- **Line length**: 65-85 characters

---

## 📏 Spacing System (8px Grid)

### Base Unit: 8px
All spacing follows an 8-pixel grid system for consistency and harmony.

```css
--spacing-xs:   4px;   /* 0.25rem - Tight spacing */
--spacing-sm:   8px;   /* 0.5rem  - Small elements */
--spacing-md:   16px;  /* 1rem    - Default spacing */
--spacing-lg:   24px;  /* 1.5rem  - Section spacing */
--spacing-xl:   32px;  /* 2rem    - Large gaps */
--spacing-2xl:  48px;  /* 3rem    - Section breaks */
--spacing-3xl:  64px;  /* 4rem    - Major sections */
--spacing-4xl:  80px;  /* 5rem    - Hero sections */
--spacing-5xl:  96px;  /* 6rem    - Page sections */
--spacing-6xl:  128px; /* 8rem    - Large heroes */
```

### Practical Application

#### Component Padding
- **Buttons**: 12px 24px (md/lg)
- **Cards**: 24px (lg)
- **Form inputs**: 12px 16px
- **Modal padding**: 32px (xl)
- **Section padding**: 80px vertical (5xl)

#### Component Margins
- **Paragraph spacing**: 16px bottom (md)
- **Heading spacing**: 24px bottom (lg)
- **Section spacing**: 64px between (3xl)
- **Card grid gaps**: 24px (lg)

---

## 🎨 Typography System

### Font Stack
```css
--font-primary: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
                Oxygen, Ubuntu, Cantarell, sans-serif;
--font-heading: var(--font-primary);
--font-mono: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
```

### Type Scale (Mobile)
```css
--text-xs:    0.75rem;  /* 12px - Captions */
--text-sm:    0.875rem; /* 14px - Small text */
--text-base:  1rem;     /* 16px - Body text */
--text-lg:    1.125rem; /* 18px - Large body */
--text-xl:    1.25rem;  /* 20px - Small heading */
--text-2xl:   1.5rem;   /* 24px - H5 */
--text-3xl:   1.75rem;  /* 28px - H4 */
--text-4xl:   2rem;     /* 32px - H3 */
--text-5xl:   2.5rem;   /* 40px - H2 */
--text-6xl:   3rem;     /* 48px - H1 */
```

### Type Scale (Desktop)
```css
--text-xs:    0.75rem;   /* 12px */
--text-sm:    0.875rem;  /* 14px */
--text-base:  1rem;      /* 16px */
--text-lg:    1.125rem;  /* 18px */
--text-xl:    1.25rem;   /* 20px */
--text-2xl:   1.75rem;   /* 28px - H5 */
--text-3xl:   2rem;      /* 32px - H4 */
--text-4xl:   2.5rem;    /* 40px - H3 */
--text-5xl:   3rem;      /* 48px - H2 */
--text-6xl:   3.5rem;    /* 56px - H1 */
--text-7xl:   4rem;      /* 64px - Hero */
```

### Line Heights
- **Headings**: 1.1 - 1.2
- **Body text**: 1.6 - 1.8
- **UI elements**: 1.4 - 1.5
- **Code blocks**: 1.4

### Font Weights
- **Light**: 300 (optional, avoid for body text)
- **Normal**: 400 (body text)
- **Medium**: 500 (UI elements)
- **Semibold**: 600 (emphasis)
- **Bold**: 700 (headings)
- **Black**: 900 (special cases only)

---

## 🎨 Color System

### Primary Palette (seminargo Berry)
```css
--color-primary:        #AC2A6E;  /* seminargo Berry */
--color-primary-light:  #D93A8A;  /* Berry Light */
--color-primary-dark:   #8A1F56;  /* Berry Dark */

--color-secondary:      #10b981;  /* Emerald 500 */
--color-secondary-light:#34d399;  /* Emerald 400 */
--color-secondary-dark: #059669;  /* Emerald 600 */

--color-accent:         #f59e0b;  /* Amber 500 */
--color-accent-light:   #fbbf24;  /* Amber 400 */
--color-accent-dark:    #d97706;  /* Amber 600 */
```

### Neutral Palette
```css
--color-white:          #ffffff;
--color-gray-50:        #f9fafb;
--color-gray-100:       #f3f4f6;
--color-gray-200:       #e5e7eb;
--color-gray-300:       #d1d5db;
--color-gray-400:       #9ca3af;
--color-gray-500:       #6b7280;
--color-gray-600:       #4b5563;
--color-gray-700:       #374151;
--color-gray-800:       #1f2937;
--color-gray-900:       #111827;
--color-black:          #000000;
```

### Semantic Colors
```css
--color-success:        #10b981;  /* Green */
--color-warning:        #f59e0b;  /* Amber */
--color-error:          #ef4444;  /* Red */
--color-info:          #3b82f6;  /* Blue */
```

### Color Usage Guidelines
- **Primary**: CTAs, links, primary buttons
- **Secondary**: Supporting actions, success states
- **Accent**: Highlights, badges, special offers
- **Text**: Gray 800 on white, white on dark
- **Borders**: Gray 200-300 for subtle, Gray 400 for defined

---

## 🗂️ Grid System

### Container Widths
```css
.container {
    width: 100%;
    margin: 0 auto;
    padding: 0 16px;  /* Mobile */
}

@media (min-width: 768px) {
    .container { padding: 0 24px; }
}

@media (min-width: 1024px) {
    .container {
        max-width: 1200px;
        padding: 0 32px;
    }
}

@media (min-width: 1440px) {
    .container {
        max-width: 1440px;
    }
}
```

### Column System
```css
.grid {
    display: grid;
    gap: 24px;
}

/* Mobile: 1 column */
.grid-cols-1 { grid-template-columns: 1fr; }

/* Tablet: 2 columns */
@media (min-width: 768px) {
    .grid-cols-md-2 { grid-template-columns: repeat(2, 1fr); }
}

/* Desktop: 3-4 columns */
@media (min-width: 1024px) {
    .grid-cols-lg-3 { grid-template-columns: repeat(3, 1fr); }
    .grid-cols-lg-4 { grid-template-columns: repeat(4, 1fr); }
}

/* Sidebar layouts */
.layout-sidebar {
    display: grid;
    gap: 32px;
    grid-template-columns: 1fr;
}

@media (min-width: 1024px) {
    .layout-sidebar {
        grid-template-columns: 1fr 300px; /* Content + Sidebar */
    }

    .layout-sidebar-left {
        grid-template-columns: 300px 1fr; /* Sidebar + Content */
    }
}
```

---

## 📦 Component Specifications

### Buttons
```css
.button {
    /* Sizing */
    padding: 12px 24px;
    min-height: 44px;  /* Touch target */
    min-width: 120px;

    /* Typography */
    font-size: 16px;
    font-weight: 600;
    line-height: 1.5;

    /* Style */
    border-radius: 6px;
    transition: all 250ms ease;

    /* States */
    &:hover { transform: translateY(-2px); }
    &:active { transform: translateY(0); }
    &:focus { outline: 2px solid var(--color-primary); }
}

/* Button Sizes */
.button-sm { padding: 8px 16px; min-height: 36px; }
.button-lg { padding: 16px 32px; min-height: 52px; }
```

### Cards
```css
.card {
    /* Layout */
    padding: 24px;

    /* Style */
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

    /* Interactive */
    transition: all 250ms ease;

    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
}

/* Card variations */
.card-compact { padding: 16px; }
.card-large { padding: 32px; }
```

### Forms
```css
.form-input {
    /* Sizing */
    width: 100%;
    padding: 12px 16px;
    min-height: 44px;

    /* Style */
    border: 1px solid var(--color-gray-300);
    border-radius: 6px;
    font-size: 16px;  /* Prevents zoom on mobile */

    /* States */
    &:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
}

/* Label spacing */
.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

/* Form group spacing */
.form-group {
    margin-bottom: 24px;
}
```

### Navigation
```css
/* Desktop Navigation */
.nav-desktop {
    height: 80px;
    padding: 0 32px;

    .nav-link {
        padding: 8px 16px;
        margin: 0 4px;
        font-weight: 500;
    }
}

/* Mobile Navigation */
.nav-mobile {
    .menu-toggle {
        width: 44px;
        height: 44px;
        padding: 10px;
    }

    .nav-drawer {
        width: 100%;
        max-width: 320px;
        padding: 24px;
    }

    .nav-link {
        display: block;
        padding: 12px 16px;
        width: 100%;
    }
}
```

---

## 🎯 Interaction Patterns

### Touch Targets
- **Minimum size**: 44x44px (iOS) / 48x48px (Android)
- **Spacing**: 8px minimum between targets
- **Padding**: Use padding to increase target size, not margin

### Hover States
```css
/* Subtle hover */
&:hover {
    opacity: 0.8;
    transition: opacity 250ms ease;
}

/* Elevation hover */
&:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Color hover */
&:hover {
    background: var(--color-primary-dark);
}
```

### Focus States
```css
/* Keyboard focus */
&:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* Remove default outline */
&:focus:not(:focus-visible) {
    outline: none;
}
```

### Loading States
```css
.skeleton {
    background: linear-gradient(
        90deg,
        var(--color-gray-200) 25%,
        var(--color-gray-100) 50%,
        var(--color-gray-200) 75%
    );
    background-size: 200% 100%;
    animation: loading 1.5s ease-in-out infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
```

---

## ♿ Accessibility Guidelines

### Color Contrast
- **Normal text**: 4.5:1 minimum
- **Large text** (18px+): 3:1 minimum
- **UI components**: 3:1 minimum
- **Decorative**: No requirement

### Keyboard Navigation
- All interactive elements must be keyboard accessible
- Tab order must follow visual hierarchy
- Focus indicators must be visible
- Skip links for main content

### Screen Readers
- Semantic HTML structure
- ARIA labels for icons
- Alt text for images
- Proper heading hierarchy

### Motion
```css
/* Respect user preferences */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

---

## 📱 Mobile-Specific Guidelines

### iOS Considerations
- Safe area insets for notched devices
- Avoid fixed positioning near screen edges
- -webkit-touch-callout for touch interactions
- Momentum scrolling: -webkit-overflow-scrolling: touch

### Android Considerations
- Material Design influences
- System font scaling support
- Edge-to-edge content capability
- Dynamic color theming compatibility

### Performance
- Lazy load images below the fold
- Use srcset for responsive images
- Minimize JavaScript on mobile
- Optimize Critical Rendering Path
- Target 60fps for animations

---

## 🚀 Performance Budget

### Page Load Targets
- **First Contentful Paint**: < 1.8s
- **Largest Contentful Paint**: < 2.5s
- **Time to Interactive**: < 3.8s
- **Cumulative Layout Shift**: < 0.1
- **First Input Delay**: < 100ms

### Asset Budgets
- **HTML**: < 25KB (compressed)
- **CSS**: < 50KB (compressed)
- **JavaScript**: < 100KB (compressed)
- **Images**: < 200KB per image
- **Total page weight**: < 1.5MB

---

## 📋 Component Checklist

Before releasing any component:

- [ ] Mobile-first responsive design
- [ ] Touch-friendly (44px targets)
- [ ] Keyboard navigable
- [ ] Screen reader tested
- [ ] Color contrast passed
- [ ] Focus states defined
- [ ] Error states designed
- [ ] Loading states included
- [ ] Dark mode compatible
- [ ] Performance optimized
- [ ] Cross-browser tested
- [ ] Documentation complete

---

## 🔧 Developer Guidelines

### CSS Architecture
```css
/* BEM Methodology */
.block {}
.block__element {}
.block--modifier {}

/* Utility Classes */
.u-margin-bottom-lg { margin-bottom: 24px !important; }
.u-text-center { text-align: center !important; }
```

### Naming Conventions
- **CSS Classes**: kebab-case
- **JavaScript**: camelCase
- **PHP Functions**: snake_case
- **Constants**: UPPER_SNAKE_CASE

### Z-Index Scale
```css
--z-index-dropdown:  1000;
--z-index-sticky:    1020;
--z-index-fixed:     1030;
--z-index-backdrop:  1040;
--z-index-modal:     1050;
--z-index-popover:   1060;
--z-index-tooltip:   1070;
```

---

## 📊 Testing Requirements

### Browser Support
- Chrome (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Edge (last 2 versions)
- iOS Safari 14+
- Chrome Mobile

### Device Testing
- iPhone SE (375px)
- iPhone 12/13 (390px)
- iPad (768px)
- iPad Pro (1024px)
- Desktop (1440px)
- 4K Display (2560px)

### Accessibility Testing
- NVDA (Windows)
- JAWS (Windows)
- VoiceOver (macOS/iOS)
- TalkBack (Android)
- Keyboard-only navigation
- Color blindness simulators

---

## 📚 Resources & Tools

### Design Tools
- Figma for design systems
- Adobe XD for prototyping
- Sketch for UI design

### Development Tools
- Chrome DevTools
- Lighthouse for performance
- axe DevTools for accessibility
- BrowserStack for testing

### References
- [Material Design Guidelines](https://material.io)
- [Human Interface Guidelines](https://developer.apple.com/design/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Web.dev Performance](https://web.dev/performance/)

---

## 🎨 Quick Reference

### Most Used Values
```css
/* Spacing */
8px, 16px, 24px, 32px, 48px, 64px, 80px

/* Border Radius */
4px (small), 6px (default), 8px (large), 16px (extra), 50% (circle)

/* Shadows */
0 1px 2px rgba(0,0,0,0.05)   /* Subtle */
0 4px 6px rgba(0,0,0,0.1)    /* Small */
0 10px 15px rgba(0,0,0,0.1)  /* Medium */
0 20px 25px rgba(0,0,0,0.1)  /* Large */

/* Transitions */
150ms (fast), 250ms (normal), 350ms (slow)

/* Common Breakpoints */
768px (tablet), 1024px (desktop), 1440px (large)
```

---

*Last Updated: November 2024*
*Version: 1.0.0*