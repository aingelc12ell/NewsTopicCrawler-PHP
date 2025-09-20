<?php
echo "News Crawler Installation & Setup Script\n";
echo "========================================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.1.0') < 0) {
    echo "❌ PHP 8.1 or higher is required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}
echo "✅ PHP version check passed (" . PHP_VERSION . ")\n";

// Check required extensions
$required_extensions = ['curl', 'dom', 'mbstring', 'json'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "❌ Missing required PHP extensions: " . implode(', ', $missing_extensions) . "\n";
    exit(1);
}
echo "✅ All required PHP extensions are installed\n";

// Create directories
$directories = [
    'storage/articles',
    'logs',
    'cache/twig',
    'public/css',
    'public/js',
    'templates/layout',
    'templates/articles',
    'templates/crawl',
    'templates/errors'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: {$dir}\n";
        } else {
            echo "❌ Failed to create directory: {$dir}\n";
            exit(1);
        }
    } else {
        echo "✅ Directory exists: {$dir}\n";
    }
}

// Check composer
if (!file_exists('vendor/autoload.php')) {
    echo "⚠️  Composer dependencies not installed. Run 'composer install'\n";
} else {
    echo "✅ Composer dependencies installed\n";
}

// Test write permissions
$test_dirs = ['storage/articles', 'logs', 'cache/twig'];
foreach ($test_dirs as $dir) {
    $test_file = "{$dir}/.test";
    if (file_put_contents($test_file, 'test') !== false) {
        unlink($test_file);
        echo "✅ Write permissions OK for: {$dir}\n";
    } else {
        echo "❌ Cannot write to {$dir} directory\n";
        exit(1);
    }
}

// Create basic .htaccess if it doesn't exist
if (!file_exists('public/.htaccess')) {
    $htaccess = 'RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>';

    if (file_put_contents('public/.htaccess', $htaccess)) {
        echo "✅ Created .htaccess file\n";
    } else {
        echo "⚠️  Could not create .htaccess file\n";
    }
}

echo "\n🎉 Installation complete!\n";
echo "Next steps:\n";
echo "1. Run 'composer install' if you haven't already\n";
echo "2. Configure your web server to point to the 'public' directory\n";
echo "3. Ensure mod_rewrite (Apache) or proper Nginx configuration\n";
echo "4. Access the application through your browser\n";
echo "5. Start crawling news articles!\n\n";