#!/bin/bash

# Check and fix Nginx/PHP log errors
# Usage: ./check-and-fix-logs.sh [EC2_IP]

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PEM_FILE="${PEM_FILE:-${SCRIPT_DIR}/scrapmate-admin.pem}"
INSTANCE_ID="i-00ca58fa9dc19d34d"
AWS_REGION="ap-south-1"
REMOTE_USER="ubuntu"
REMOTE_PATH="/var/www/scrapmate-admin"

# Export AWS credentials
export AWS_ACCESS_KEY_ID=AKIASY6OQMSMLTUQAOTS
export AWS_SECRET_ACCESS_KEY=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
export AWS_REGION=$AWS_REGION

# Get EC2 IP address
if [ -z "$1" ]; then
    EC2_IP=$(aws ec2 describe-instances \
        --instance-ids $INSTANCE_ID \
        --region $AWS_REGION \
        --query 'Reservations[0].Instances[0].PublicIpAddress' \
        --output text 2>/dev/null)
    
    if [ -z "$EC2_IP" ] || [ "$EC2_IP" == "None" ] || [ "$EC2_IP" == "null" ]; then
        EC2_IP="13.217.224.15"
        echo -e "${YELLOW}Using fallback EC2 IP: $EC2_IP${NC}"
    else
        echo -e "${GREEN}Retrieved EC2 IP from AWS: $EC2_IP${NC}"
    fi
else
    EC2_IP="$1"
    echo -e "${GREEN}Using provided EC2 IP: $EC2_IP${NC}"
fi

# Verify PEM file exists
if [ ! -f "$PEM_FILE" ]; then
    echo -e "${RED}Error: PEM file not found at $PEM_FILE${NC}"
    exit 1
fi

chmod 400 "$PEM_FILE" 2>/dev/null || true

echo -e "${YELLOW}Checking and fixing Nginx/PHP log errors on EC2 server ($EC2_IP)...${NC}"

# Test SSH connection
if ! ssh -i "$PEM_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$EC2_IP" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${RED}Failed to connect to EC2 instance${NC}"
    exit 1
fi

# Check logs and fix issues
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << CHECK_LOGS_EOF
    set -e
    cd $REMOTE_PATH
    
    echo "=== Checking Nginx Error Log ==="
    if [ -f /var/log/nginx/error.log ]; then
        echo "Recent Nginx errors (last 20 lines):"
        sudo tail -n 20 /var/log/nginx/error.log | grep -i error || echo "No recent errors found"
    else
        echo "⚠ Nginx error log not found"
    fi
    
    echo ""
    echo "=== Checking Laravel Log ==="
    if [ -f storage/logs/laravel.log ]; then
        echo "Recent Laravel errors (last 20 lines):"
        sudo tail -n 20 storage/logs/laravel.log | grep -i "error\|exception\|fatal" || echo "No recent errors found"
    else
        echo "⚠ Laravel log not found"
    fi
    
    echo ""
    echo "=== Checking PHP-FPM Error Log ==="
    PHP_FPM_LOG=""
    if [ -f /var/log/php8.3-fpm.log ]; then
        PHP_FPM_LOG="/var/log/php8.3-fpm.log"
    elif [ -f /var/log/php8.2-fpm.log ]; then
        PHP_FPM_LOG="/var/log/php8.2-fpm.log"
    elif [ -f /var/log/php8.1-fpm.log ]; then
        PHP_FPM_LOG="/var/log/php8.1-fpm.log"
    elif [ -f /var/log/php-fpm.log ]; then
        PHP_FPM_LOG="/var/log/php-fpm.log"
    fi
    
    if [ -n "$PHP_FPM_LOG" ]; then
        echo "Recent PHP-FPM errors (last 20 lines):"
        sudo tail -n 20 $PHP_FPM_LOG | grep -i error || echo "No recent errors found"
    else
        echo "⚠ PHP-FPM log not found"
    fi
    
    echo ""
    echo "=== Fixing Common Issues ==="
    
    # Determine web server user
    WEB_USER="www-data"
    if id "nginx" &>/dev/null 2>&1; then
        WEB_USER="nginx"
    elif id "www-data" &>/dev/null 2>&1; then
        WEB_USER="www-data"
    fi
    
    echo "Using web server user: \$WEB_USER"
    
    # Fix permissions - more aggressive approach
    echo "Fixing permissions (aggressive)..."
    
    # Remove any existing files that might have wrong permissions
    sudo rm -f storage/logs/laravel.log 2>/dev/null || true
    sudo rm -rf bootstrap/cache/*.php 2>/dev/null || true
    
    # Recreate directories with correct permissions
    sudo mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache 2>/dev/null || true
    
    # Set ownership first
    sudo chown -R \$WEB_USER:\$WEB_USER storage bootstrap/cache 2>/dev/null || true
    
    # Set permissions
    sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    
    # Create log file with correct permissions
    sudo touch storage/logs/laravel.log 2>/dev/null || true
    sudo chown \$WEB_USER:\$WEB_USER storage/logs/laravel.log 2>/dev/null || true
    sudo chmod 664 storage/logs/laravel.log 2>/dev/null || true
    
    # Verify permissions
    echo "Verifying permissions..."
    echo "  Storage: \$(stat -c '%U:%G %a' storage 2>/dev/null || echo 'N/A')"
    echo "  Log file: \$(stat -c '%U:%G %a' storage/logs/laravel.log 2>/dev/null || echo 'N/A')"
    echo "  Cache: \$(stat -c '%U:%G %a' bootstrap/cache 2>/dev/null || echo 'N/A')"
    
    # Ensure env.txt exists and has correct values
    if [ ! -f env.txt ]; then
        echo "Creating env.txt..."
        sudo tee env.txt > /dev/null << 'ENV_CONTENT'
APP_NAME=ScrapMate
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://mono.scrapmate.co.in

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scrapmate1
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

NODE_API_URL=https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api
NODE_API_KEY=zyubkfzeumeoviaqzcsrvfwdzbiwnlnn
ENV_CONTENT
        sudo chown ubuntu:ubuntu env.txt
        sudo chmod 600 env.txt
        sudo cp env.txt .env
        sudo chown ubuntu:ubuntu .env
        sudo chmod 600 .env
        echo "✓ env.txt created"
    fi
    
    # Update NODE_API_URL in env.txt if needed
    CORRECT_URL="https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api"
    if ! grep -q "^NODE_API_URL=\$CORRECT_URL" env.txt 2>/dev/null; then
        sed -i '/^NODE_API_URL=/d' env.txt
        echo "NODE_API_URL=\$CORRECT_URL" >> env.txt
        sudo cp env.txt .env
        echo "✓ NODE_API_URL updated"
    fi
    
    # Generate APP_KEY if missing
    if ! grep -q "^APP_KEY=" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
        echo "Generating APP_KEY..."
        php artisan key:generate --force 2>/dev/null || true
        # Update env.txt with new APP_KEY
        APP_KEY_VALUE=\$(grep "^APP_KEY=" .env | cut -d'=' -f2-)
        if [ -n "\$APP_KEY_VALUE" ]; then
            sed -i '/^APP_KEY=/d' env.txt
            echo "APP_KEY=\$APP_KEY_VALUE" >> env.txt
        fi
        echo "✓ APP_KEY generated"
    fi
    
    # Check if vendor directory is complete
    echo "Checking vendor directory..."
    if [ ! -f vendor/composer/autoload_real.php ] || [ ! -d vendor/spatie ]; then
        echo "⚠ Vendor directory incomplete, reinstalling..."
        composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true
        echo "✓ Vendor dependencies reinstalled"
    else
        echo "✓ Vendor directory looks complete"
    fi
    
    # Clear and rebuild caches (run as www-data to ensure proper permissions)
    echo "Clearing caches..."
    sudo rm -rf bootstrap/cache/*.php 2>/dev/null || true
    
    # Run artisan commands as www-data user to ensure they can write
    sudo -u \$WEB_USER php artisan config:clear 2>/dev/null || php artisan config:clear 2>/dev/null || true
    sudo -u \$WEB_USER php artisan cache:clear 2>/dev/null || php artisan cache:clear 2>/dev/null || true
    sudo -u \$WEB_USER php artisan route:clear 2>/dev/null || php artisan route:clear 2>/dev/null || true
    sudo -u \$WEB_USER php artisan view:clear 2>/dev/null || php artisan view:clear 2>/dev/null || true
    
    echo "Rebuilding caches..."
    sudo -u \$WEB_USER php artisan config:cache 2>/dev/null || php artisan config:cache 2>/dev/null || true
    sudo -u \$WEB_USER php artisan route:cache 2>/dev/null || php artisan route:cache 2>/dev/null || true
    sudo -u \$WEB_USER php artisan view:cache 2>/dev/null || php artisan view:cache 2>/dev/null || true
    
    # Fix permissions again after cache operations
    sudo chown -R \$WEB_USER:\$WEB_USER storage bootstrap/cache 2>/dev/null || true
    sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    sudo chown \$WEB_USER:\$WEB_USER storage/logs/laravel.log 2>/dev/null || true
    sudo chmod 664 storage/logs/laravel.log 2>/dev/null || true
    
    echo "✓ Caches rebuilt"
    
    echo ""
    echo "=== Fixing Nginx Duplicate Server Name ==="
    # Check for duplicate server blocks
    if [ -f /etc/nginx/sites-enabled/default ]; then
        echo "Disabling default Nginx site to avoid conflicts..."
        sudo rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
    fi
    
    # Check for other conflicting configs
    CONFLICTING_CONFIGS=\$(sudo ls /etc/nginx/sites-enabled/*.conf 2>/dev/null | grep -v mono.scrapmate.co.in || echo "")
    if [ -n "\$CONFLICTING_CONFIGS" ]; then
        echo "Found other Nginx configs, checking for conflicts..."
        for config in \$CONFLICTING_CONFIGS; do
            if sudo grep -q "server_name mono.scrapmate.co.in" "\$config" 2>/dev/null; then
                echo "  Found duplicate config: \$config"
                sudo rm -f "\$config" 2>/dev/null || true
            fi
        done
    fi
    
    echo ""
    echo "=== Testing Nginx Configuration ==="
    if sudo nginx -t 2>&1; then
        echo "✓ Nginx configuration is valid"
    else
        echo "⚠ Nginx configuration has errors"
        echo "  Run: sudo nginx -t"
    fi
    
    echo ""
    echo "=== Service Status ==="
    echo "Nginx: \$(sudo systemctl is-active nginx 2>/dev/null || echo 'inactive')"
    echo "PHP-FPM: \$(sudo systemctl is-active php8.3-fpm php8.2-fpm php8.1-fpm php-fpm 2>/dev/null | head -1 || echo 'inactive')"
CHECK_LOGS_EOF

# Reload services
echo ""
echo -e "${YELLOW}Reloading services...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << RELOAD_EOF
    # Reload PHP-FPM
    sudo systemctl reload php8.3-fpm 2>/dev/null || \
    sudo systemctl reload php8.2-fpm 2>/dev/null || \
    sudo systemctl reload php8.1-fpm 2>/dev/null || \
    sudo systemctl reload php-fpm 2>/dev/null || true
    
    # Reload Nginx
    if command -v nginx &> /dev/null; then
        if sudo nginx -t 2>/dev/null; then
            sudo systemctl reload nginx 2>/dev/null || sudo systemctl restart nginx 2>/dev/null || true
        fi
    fi
RELOAD_EOF

echo ""
echo -e "${GREEN}✓ Log check and fixes completed${NC}"

