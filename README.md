## News Crawler with Twig Templating

A comprehensive news crawler built with Slim PHP Framework and Twig templating engine that can crawl websites for news articles related to specified topics.

### 🌟 Key Features

#### **Templating System**
- **Twig Integration**: Professional templating with inheritance and components
- **Custom Helpers**: Time formatting, text excerpts, domain extraction
- **Responsive Design**: Mobile-first CSS with modern aesthetics
- **Template Separation**: Clean separation between logic and presentation

#### **Advanced Duplicate Detection**
- **Multi-Algorithm Approach**: URL matching, title similarity, content fingerprinting
- **Smart Thresholds**: 85% title similarity, 80% content similarity
- **Real-time Feedback**: Live statistics and duplicate prevention reports
- **Index Management**: Automatic cleanup and maintenance

#### **Enhanced User Experience**
- **Interactive Forms**: Live validation and progress indicators
- **Topic Suggestions**: Auto-complete for common search topics
- **Loading States**: Visual feedback during crawling operations
- **Error Handling**: Graceful error pages with helpful actions

### 🏗️ **Architecture**

```
├── public/               # Web root
│   ├── index.php        # Application entry point
│   ├── css/app.css      # Compiled styles
│   └── js/app.js        # Frontend JavaScript
├── src/
│   ├── Controllers/     # Request handlers
│   ├── Models/          # Data models
│   ├── Services/        # Business logic
│   └── TemplateHelper/  # Twig extensions
├── templates/           # Twig templates
│   ├── layout/         # Base layouts
│   ├── articles/       # Article templates
│   ├── crawl/          # Crawler templates
│   └── errors/         # Error pages
├── storage/            # Data storage
├── cache/             # Template cache
└── logs/              # Application logs
```

### 🎨 **Template Features**

#### **Base Template (layout/base.twig)**
- Responsive navigation with active states
- SEO-friendly meta tags and structure
- Progressive enhancement with JavaScript
- Accessibility features and ARIA labels

#### **Article Templates**
- **List View**: Grid layout with pagination support
- **Detail View**: Full article with breadcrumbs
- **Empty States**: Engaging prompts for new users
- **Time Formatting**: Human-readable timestamps

#### **Crawl Templates**
- **Configuration Form**: Interactive mode selection
- **Progress Indicators**: Real-time crawling feedback
- **Results Display**: Comprehensive statistics breakdown
- **Error Handling**: User-friendly error messages

### 📱 **Responsive Design**

- **Mobile-First**: Optimized for all screen sizes
- **Touch-Friendly**: Large tap targets and gestures
- **Performance**: Optimized CSS and JavaScript
- **Progressive**: Enhanced experience with JavaScript enabled

### 🔧 **Development Features**

#### **Template Development**
- **Hot Reload**: Templates update without clearing cache
- **Debug Mode**: Detailed error information in development
- **Template Inheritance**: DRY principle with base templates
- **Custom Filters**: Domain extraction, time formatting, text excerpts

#### **Asset Management**
- **CSS Organization**: Modular stylesheet architecture
- **JavaScript Enhancement**: Progressive functionality
- **Performance**: Minification and compression ready
- **Caching**: Template and asset caching for production

### 🚀 **Installation**

```bash
# Clone or create project
mkdir news-crawler && cd news-crawler

# Install dependencies
composer install

# Run setup script
php install.php

# Set permissions
chmod -R 755 storage/ cache/ logs/

# Configure web server (Apache/Nginx)
# Point document root to public/ directory
```

### ⚙️ **Configuration**

#### **Web Server Setup**

**Apache (.htaccess included):**
```apache
DocumentRoot /path/to/news-crawler/public
```

**Nginx:**
```nginx
server {
    listen 80;
    root /path/to/news-crawler/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

#### **Environment Setup**
- **Development**: Debug mode enabled, template auto-reload
- **Production**: Template caching, error logging, asset minification

### 📊 **Template Customization**

#### **Adding Custom Filters**
```php
// src/Services/TemplateHelper.php
public function getFilters(): array {
    return [
        new TwigFilter('custom_filter', [$this, 'customMethod']),
    ];
}
```

#### **Custom Page Templates**
```twig
{% extends "layout/base.twig" %}
{% block title %}Custom Page{% endblock %}
{% block content %}
    <!-- Your content here -->
{% endblock %}
```

### 🛡️ **Security Features**

- **XSS Protection**: Automatic HTML escaping in templates
- **CSRF Protection**: Form token validation ready
- **Content Security**: Secure headers and configurations
- **Input Validation**: Server-side validation with template feedback

### 📈 **Performance**

- **Template Caching**: Compiled templates for production speed
- **Asset Optimization**: Minified CSS/JS for faster loading
- **Database Efficiency**: Indexed duplicate detection
- **Memory Management**: Efficient article processing

### 🧪 **Testing Templates**

Templates can be tested in isolation:
```php
$twig->render('articles/list.twig', ['articles' => $testData]);
```

│   │   └── error.twig        # Error handling
│   └── errors/
│       └── 404.twig          # Not found page
├── cache/twig/               # Template cache
└── storage/                  # Data storage
```

## 🚀 **Key Benefits of the Template System**

### **Developer Experience**
- **Clean Separation**: Logic separated from presentation
- **Maintainable**: Easy to update designs without touching PHP
- **Scalable**: Template inheritance prevents code duplication
- **Debug-Friendly**: Clear error messages and hot reloading

### **User Experience**
- **Fast Loading**: Template caching and optimized assets
- **Interactive**: Progressive enhancement with JavaScript
- **Accessible**: Semantic HTML and ARIA support
- **Mobile-Optimized**: Responsive design patterns

### **Production Ready**
- **SEO Optimized**: Proper meta tags and structured HTML
- **Security**: XSS protection and secure headers
- **Performance**: Caching strategies and asset optimization
- **Monitoring**: Error logging and performance tracking

## 🎯 **New Template Features**

### **Custom Twig Filters**
```twig
{{ article.date|timeago }}          // "2 hours ago"
{{ article.summary|excerpt(100) }}  // Truncated text
{{ article.sourceLink|domain }}     // Extract domain
```

### **Asset Helpers**
```twig
{{ asset('css/app.css') }}          // /css/app.css
{{ url('articles') }}               // /articles
```

### **Interactive Components**
- **Mode Selection**: Visual radio buttons with descriptions
- **Topic Suggestions**: Auto-complete with popular topics
- **Progress Tracking**: Real-time crawling progress
- **Form Validation**: Client-side validation with error display

### **Enhanced CSS Features**
- **CSS Grid**: Modern layout system
- **Animations**: Smooth transitions and hover effects
- **Dark Mode Ready**: CSS custom properties for theming
- **Component System**: Reusable UI components

### **JavaScript Enhancements**
- **Progressive Enhancement**: Works without JS, better with it
- **Form Handling**: Advanced validation and submission
- **UI Interactions**: Smooth animations and feedback
- **Error Handling**: Graceful degradation

## 📝 **Template Usage Examples**

### **Creating New Pages**
```twig
{% extends "layout/base.twig" %}
{% set active_page = 'custom' %}
{% block title %}Custom Page{% endblock %}
{% block content %}
    <!-- Your content -->
{% endblock %}
```

### **Adding Custom Styles**
```twig
{% block head %}
<style>
.custom-component {
    /* Custom styles */
}
</style>
{% endblock %}
```

### **Custom JavaScript**
```twig
{% block scripts %}
<script>
// Page-specific JavaScript
</script>
{% endblock %}
```

## 🔧 **Configuration & Setup**

### **Installation Steps**
1. **Dependencies**: `composer install` (includes Twig)
2. **Directory Setup**: Run `php install.php`
3. **Permissions**: Set write access for `cache/` and `storage/`
4. **Web Server**: Point to `public/` directory

### **Development Mode**
- Template auto-reload enabled
- Debug information displayed
- Source maps for CSS/JS
- Error details in browser

### **Production Mode**
- Template compilation and caching
- Asset minification
- Error logging only
- Security headers enabled

## 🎨 **Customization Guide**

### **Changing Themes**
Modify CSS custom properties in `public/css/app.css`:
```css
:root {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --success-color: #27ae60;
    --warning-color: #f39c12;
}
```

### **Adding New Templates**
1. Create template file in appropriate directory
2. Extend base layout
3. Define blocks for content
4. Add route in `public/index.php`
5. Create controller method

### **Custom Components**
Create reusable template components:
```twig
<!-- templates/components/article-card.twig -->
<article class="article-card">
    <h2>{{ article.title }}</h2>
    <p>{{ article.summary|excerpt(150) }}</p>
</article>
```

Include in other templates:
```twig
{% include 'components/article-card.twig' with {'article': article} %}
```

## 🛡️ **Security & Performance**

### **Template Security**
- Automatic HTML escaping
- XSS prevention built-in
- CSRF token ready
- Input sanitization

### **Performance Optimizations**
- Template compilation caching
- Asset bundling ready
- Image lazy loading
- Progressive web app ready

## 📊 **Monitoring & Analytics**

### **Error Tracking**
- Template compilation errors logged
- Runtime errors with context
- Performance metrics available
- User interaction tracking ready

### **SEO Features**
- Semantic HTML structure
- Meta tag management
- Open Graph ready
- Schema.org markup ready

This comprehensive template system provides a solid foundation for building professional web applications with clean architecture, excellent user experience, and production-ready features. The separation of concerns makes the codebase maintainable and scalable while providing modern web development best practices.
## Duplicate Detection Features

The system includes comprehensive duplicate detection to prevent saving duplicate articles:

### Detection Methods
- **Exact URL Matching**: Compares normalized URLs (removes protocol, www, tracking parameters)
- **Title Similarity**: Uses Levenshtein distance with 85% similarity threshold
- **Content Fingerprinting**: SHA-256 hash of title + summary content
- **Content Similarity**: Jaccard coefficient with 80% similarity threshold for article content

### How It Works
1. **Index Maintenance**: Maintains a JSON index of all saved articles with their hashes
2. **Pre-save Checking**: Every article is checked against existing articles before saving
3. **Multi-layer Detection**: Uses multiple algorithms to catch different types of duplicates
4. **Automatic Cleanup**: Removes orphaned index entries for deleted articles

### Duplicate Statistics
The system tracks and displays:
- Total articles found during crawl
- Number of duplicates detected and skipped
- Breakdown by duplicate type (URL, title, content)
- Overall database statistics

### Configuration
Duplicate detection thresholds can be adjusted in `DuplicateChecker.php`:
- Title similarity threshold: Currently 85%
- Content similarity threshold: Currently 80%
- Stop words list for title normalization

## API Endpoints for Duplicate Management

Add these routes to handle duplicate management:

```php
// Add to public/index.php routes
$app->get('/admin/duplicates', [AdminController::class, 'duplicateStats']);
$app->post('/admin/cleanup', [AdminController::class, 'cleanupDuplicates']);
```

## Maintenance Commands

Create a maintenance script for duplicate management:

```bash
# Clean up orphaned index entries
php maintenance.php cleanup-index

# Rebuild duplicate index
php maintenance.php rebuild-index

# Show duplicate statistics
php maintenance.php show-stats
```