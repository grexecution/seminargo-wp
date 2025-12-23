# seminargo WordPress Theme

A modern, clean, and performant WordPress theme built with best practices in mind.

## Features

- **Modern Design**: Clean and minimalist design that works for any type of website
- **Responsive**: Mobile-first responsive design that looks great on all devices
- **Performance Optimized**: Lightweight and fast-loading
- **SEO Friendly**: Built with SEO best practices
- **Accessibility Ready**: WCAG 2.1 compliant
- **Translation Ready**: Fully translatable with included POT file
- **WooCommerce Support**: Full compatibility with WooCommerce
- **Elementor Compatible**: Works seamlessly with Elementor page builder
- **Customizable**: Extensive customization options via WordPress Customizer

## Theme Structure

```
seminargo/
├── assets/              # Compiled assets (CSS, JS, images)
│   ├── css/
│   ├── js/
│   ├── images/
│   └── fonts/
├── inc/                 # PHP includes
├── template-parts/      # Template partials
├── src/                 # Source files (for development)
│   ├── js/
│   └── scss/
├── languages/           # Translation files
├── style.css           # Theme information
├── functions.php       # Theme functions
├── index.php           # Main template
├── header.php          # Header template
├── footer.php          # Footer template
├── sidebar.php         # Sidebar template
├── single.php          # Single post template
├── page.php            # Page template
├── archive.php         # Archive template
├── search.php          # Search results template
├── 404.php             # 404 error page
└── comments.php        # Comments template
```

## Installation

1. Download the theme zip file
2. Go to WordPress Admin > Appearance > Themes
3. Click "Add New" and then "Upload Theme"
4. Choose the zip file and click "Install Now"
5. After installation, click "Activate"

## Development Setup

If you want to customize the theme further:

1. Navigate to the theme directory
2. Install dependencies: `npm install`
3. For development: `npm run dev`
4. For production build: `npm run build`

## Customization

### Using the WordPress Customizer

Navigate to **Appearance > Customize** in your WordPress admin to access:

- Site Identity (logo, site title, tagline)
- Colors
- Typography
- Header Settings
- Footer Settings
- Layout Options
- And more...

### Custom CSS

You can add custom CSS in:
1. WordPress Customizer > Additional CSS
2. Create a child theme (recommended for extensive modifications)

## Theme Support

This theme includes support for:

- **Post Thumbnails**: Featured images for posts and pages
- **Custom Logo**: Upload your own logo
- **Custom Header**: Customizable header image
- **Custom Background**: Set a custom background
- **Navigation Menus**: Multiple menu locations
  - Primary Menu
  - Mobile Menu
  - Footer Menu
  - Social Links Menu
- **Widget Areas**:
  - Primary Sidebar
  - Footer Widget Areas (4 columns)
- **Post Formats**: Standard, Aside, Gallery, Video, Quote, Link
- **Block Editor**: Full support for Gutenberg blocks
- **Wide Alignment**: Support for wide and full-width blocks

## Browser Support

- Chrome (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Edge (last 2 versions)
- Opera (last 2 versions)

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- MySQL 5.6 or higher

## Credits

- Normalize.css - MIT License
- Font Awesome Icons - Font Awesome Free License

## License

This theme is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0 (2024-11-18)
- Initial release

## Support

For support, please visit [seminargo.com](https://seminargo.com) or create an issue on our GitHub repository.