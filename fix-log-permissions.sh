#!/bin/bash

# Fix Laravel log file permissions and update config
# Usage: ./fix-log-permissions.sh [EC2_IP]

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

echo -e "${YELLOW}Fixing log file permissions and config on EC2 server ($EC2_IP)...${NC}"

# Test SSH connection
if ! ssh -i "$PEM_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$EC2_IP" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${RED}Failed to connect to EC2 instance${NC}"
    exit 1
fi

# Fix permissions and config
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << FIX_LOGS_EOF
    set -e
    cd /var/www/scrapmate-admin
    
    echo "=== Fixing Permissions ==="
    
    # Determine web server user
    WEB_USER="www-data"
    if id "nginx" &>/dev/null 2>&1; then
        WEB_USER="nginx"
    elif id "www-data" &>/dev/null 2>&1; then
        WEB_USER="www-data"
    else
        # Default to www-data
        WEB_USER="www-data"
    fi
    
    echo "Using web server user: \$WEB_USER"
    
    # Make sure www-data owns storage & bootstrap/cache
    echo "Setting ownership to \$WEB_USER:\$WEB_USER..."
    sudo chown -R \$WEB_USER:\$WEB_USER storage bootstrap/cache
    
    # Give read/write/execute permissions for owner & group
    echo "Setting permissions to 775..."
    sudo chmod -R 775 storage bootstrap/cache
    
    # Also ensure ubuntu user can write (for manual operations)
    sudo chmod g+w storage bootstrap/cache 2>/dev/null || true
    
    # Ensure the log file exists and is writable
    echo "Creating/ensuring log file exists..."
    sudo touch storage/logs/laravel.log
    sudo chown \$WEB_USER:\$WEB_USER storage/logs/laravel.log
    sudo chmod 664 storage/logs/laravel.log
    
    # Ensure all storage directories exist
    sudo mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views 2>/dev/null || true
    sudo chown -R \$WEB_USER:\$WEB_USER storage/framework 2>/dev/null || true
    sudo chmod -R 775 storage/framework 2>/dev/null || true
    
    echo "✓ Permissions fixed"
    
    echo ""
    echo "=== Updating .env Configuration ==="
    
    # Ensure .env exists
    if [ ! -f .env ]; then
        echo "Creating .env file..."
        sudo tee .env > /dev/null << 'ENV_BASIC'
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
ENV_BASIC
        sudo chown ubuntu:ubuntu .env
        sudo chmod 600 .env
    fi
    
    # Update NODE_API_URL (force update)
    CORRECT_NODE_API_URL="https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api"
    echo "Updating NODE_API_URL to: \$CORRECT_NODE_API_URL"
    
    # Remove any existing NODE_API_URL line
    sed -i '/^NODE_API_URL=/d' .env 2>/dev/null || true
    
    # Add the correct NODE_API_URL
    echo "NODE_API_URL=\$CORRECT_NODE_API_URL" >> .env
    echo "✓ NODE_API_URL set"
    
    # Update NODE_API_KEY (force update)
    NODE_API_KEY="zyubkfzeumeoviaqzcsrvfwdzbiwnlnn"
    echo "Updating NODE_API_KEY..."
    
    # Remove any existing NODE_API_KEY line
    sed -i '/^NODE_API_KEY=/d' .env 2>/dev/null || true
    
    # Add the correct NODE_API_KEY
    echo "NODE_API_KEY=\$NODE_API_KEY" >> .env
    echo "✓ NODE_API_KEY set"
    
    # Verify the values were added
    echo ""
    echo "Verifying .env file:"
    if grep -q "^NODE_API_URL=" .env; then
        ENV_URL=\$(grep "^NODE_API_URL=" .env | cut -d'=' -f2-)
        echo "NODE_API_URL=\$ENV_URL"
    else
        echo "⚠ NODE_API_URL not found in .env"
    fi
    if grep -q "^NODE_API_KEY=" .env; then
        ENV_KEY=\$(grep "^NODE_API_KEY=" .env | cut -d'=' -f2- | cut -c1-10)
        echo "NODE_API_KEY=\$ENV_KEY..."
    else
        echo "⚠ NODE_API_KEY not found in .env"
    fi
    
    # Generate APP_KEY if missing
    if ! grep -q "^APP_KEY=" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
        echo "Generating APP_KEY..."
        php artisan key:generate --force 2>/dev/null || true
        echo "✓ APP_KEY generated"
    fi
    
    echo ""
    echo "=== Clearing Laravel Caches ==="
    
    # Delete ALL cached config files to force complete rebuild
    echo "Deleting cached config files..."
    sudo rm -rf bootstrap/cache/*.php 2>/dev/null || true
    sudo rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/services.php 2>/dev/null || true
    echo "✓ Cached files deleted"
    
    # Clear all caches
    echo "Clearing Laravel caches..."
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    echo "✓ Caches cleared"
    
    # Rebuild caches
    echo "Rebuilding Laravel caches..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    echo "✓ Caches rebuilt"
    
    # Verify the cached config contains the correct URL
    echo ""
    echo "Verifying cached config..."
    if [ -f bootstrap/cache/config.php ]; then
        if grep -q "uodttljjzj3nh3e4cjqardxip40btqef" bootstrap/cache/config.php 2>/dev/null; then
            echo "✓ Cached config contains correct NODE_API_URL"
        else
            echo "⚠ Cached config may not contain correct NODE_API_URL"
            echo "  Forcing config cache rebuild..."
            sudo rm -f bootstrap/cache/config.php
            php artisan config:cache
        fi
    else
        echo "⚠ Config cache file not found, rebuilding..."
        php artisan config:cache
    fi
    
    # Fix permissions again after cache operations
    sudo chown -R \$WEB_USER:\$WEB_USER storage bootstrap/cache
    sudo chmod -R 775 storage bootstrap/cache
    sudo chown \$WEB_USER:\$WEB_USER storage/logs/laravel.log
    sudo chmod 664 storage/logs/laravel.log
    
    echo "✓ Caches cleared and rebuilt"
    
    echo ""
    echo "=== Verifying Permissions ==="
    echo "Log file: \$(stat -c '%U:%G %a' storage/logs/laravel.log 2>/dev/null || echo 'N/A')"
    echo "Storage dir: \$(stat -c '%U:%G %a' storage 2>/dev/null || echo 'N/A')"
    echo "Cache dir: \$(stat -c '%U:%G %a' bootstrap/cache 2>/dev/null || echo 'N/A')"
    
    echo ""
    echo "=== Summary ==="
    ENV_NODE_API_URL=\$(grep '^NODE_API_URL=' .env 2>/dev/null | cut -d'=' -f2- || echo "NOT FOUND")
    ENV_NODE_API_KEY=\$(grep '^NODE_API_KEY=' .env 2>/dev/null | cut -d'=' -f2- | cut -c1-10 || echo "NOT FOUND")
    echo ".env NODE_API_URL: \$ENV_NODE_API_URL"
    echo ".env NODE_API_KEY: \$ENV_NODE_API_KEY..."
    
    # Show what's in the cached config
    if [ -f bootstrap/cache/config.php ]; then
        echo ""
        echo "Checking cached config for NODE_API_URL..."
        CACHED_URL=\$(grep -o "NODE_API_URL[^;]*" bootstrap/cache/config.php 2>/dev/null | head -1 || echo "Not found in cache")
        echo "Cached config: \$CACHED_URL"
    fi
FIX_LOGS_EOF

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
        sudo nginx -t 2>/dev/null && sudo systemctl reload nginx 2>/dev/null || true
    fi
RELOAD_EOF

echo ""
echo -e "${GREEN}✓ Log file permissions fixed${NC}"
echo -e "${GREEN}✓ Configuration updated${NC}"
echo -e "${GREEN}✓ Services reloaded${NC}"
echo ""
echo -e "${YELLOW}The application should now be able to write to the log file${NC}"

