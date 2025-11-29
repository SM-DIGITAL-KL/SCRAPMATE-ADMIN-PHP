#!/bin/bash
# Deployment commands to run on EC2 server
# Copy and run these commands on the EC2 instance

cd /var/www/scrapmate-admin

echo "📦 Step 1: Installing/Updating Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
else
    echo "❌ Composer not found. Please install Composer first."
    exit 1
fi

echo ""
echo "🔐 Step 2: Setting proper permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || sudo chown -R apache:apache storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "🧹 Step 3: Clearing Laravel caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

echo ""
echo "⚡ Step 4: Optimizing Laravel for production..."
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo ""
echo "🌐 Step 5: Reloading web server..."
if command -v nginx &> /dev/null; then
    sudo systemctl reload nginx 2>/dev/null || true
    echo "✅ Nginx reloaded"
elif command -v apache2 &> /dev/null || command -v httpd &> /dev/null; then
    sudo systemctl reload apache2 2>/dev/null || sudo systemctl reload httpd 2>/dev/null || true
    echo "✅ Apache reloaded"
else
    echo "⚠️  No web server found (Nginx/Apache)"
fi

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Next steps:"
echo "   1. Ensure .env file has correct NODE_API_URL:"
echo "      NODE_API_URL=https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api"
echo "   2. Verify website is accessible"
echo "   3. Test admin panel login"

