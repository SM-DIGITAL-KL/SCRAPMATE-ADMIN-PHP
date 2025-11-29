#!/bin/bash

# Fix login issues - ensure env.txt is read correctly and API URL is correct
# Usage: ./fix-login-issue.sh [EC2_IP]

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

echo -e "${YELLOW}Fixing login issues on EC2 server ($EC2_IP)...${NC}"

# Test SSH connection
if ! ssh -i "$PEM_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$EC2_IP" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${RED}Failed to connect to EC2 instance${NC}"
    exit 1
fi

# Fix login issues
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << FIX_LOGIN_EOF
    set -e
    cd $REMOTE_PATH
    
    echo "=== Fixing env.txt Configuration ==="
    
    CORRECT_NODE_API_URL="https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api"
    CORRECT_NODE_API_KEY="zyubkfzeumeoviaqzcsrvfwdzbiwnlnn"
    
    # Create/update env.txt with correct values
    if [ ! -f env.txt ]; then
        echo "Creating env.txt file..."
        sudo tee env.txt > /dev/null << 'ENV_TXT_CONTENT'
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
ENV_TXT_CONTENT
        sudo chown ubuntu:ubuntu env.txt
        sudo chmod 600 env.txt
        echo "✓ env.txt created"
    fi
    
    # Force update NODE_API_URL in env.txt
    echo "Updating NODE_API_URL in env.txt..."
    sed -i '/^NODE_API_URL=/d' env.txt
    echo "NODE_API_URL=\$CORRECT_NODE_API_URL" >> env.txt
    
    # Force update NODE_API_KEY in env.txt
    echo "Updating NODE_API_KEY in env.txt..."
    sed -i '/^NODE_API_KEY=/d' env.txt
    echo "NODE_API_KEY=\$CORRECT_NODE_API_KEY" >> env.txt
    
    # Sync to .env for Laravel compatibility
    sudo cp env.txt .env
    sudo chown ubuntu:ubuntu .env env.txt
    sudo chmod 600 .env env.txt
    
    echo "✓ env.txt updated"
    
    # Verify values
    echo ""
    echo "Verifying env.txt:"
    echo "  NODE_API_URL: \$(grep '^NODE_API_URL=' env.txt | cut -d'=' -f2-)"
    echo "  NODE_API_KEY: \$(grep '^NODE_API_KEY=' env.txt | cut -d'=' -f2- | cut -c1-10)..."
    
    # Generate APP_KEY if missing
    if ! grep -q "^APP_KEY=" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
        echo ""
        echo "Generating APP_KEY..."
        php artisan key:generate --force 2>/dev/null || true
        APP_KEY_VALUE=\$(grep "^APP_KEY=" .env | cut -d'=' -f2-)
        if [ -n "\$APP_KEY_VALUE" ]; then
            sed -i '/^APP_KEY=/d' env.txt
            echo "APP_KEY=\$APP_KEY_VALUE" >> env.txt
            sudo cp env.txt .env
        fi
        echo "✓ APP_KEY generated"
    fi
    
    echo ""
    echo "=== Fixing Permissions ==="
    
    # Determine web server user
    WEB_USER="www-data"
    if id "nginx" &>/dev/null 2>&1; then
        WEB_USER="nginx"
    elif id "www-data" &>/dev/null 2>&1; then
        WEB_USER="www-data"
    fi
    
    echo "Using web server user: \$WEB_USER"
    
    # Aggressive permission fix
    sudo rm -f storage/logs/laravel.log 2>/dev/null || true
    sudo mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache 2>/dev/null || true
    
    sudo chown -R \$WEB_USER:\$WEB_USER storage bootstrap/cache 2>/dev/null || true
    sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    
    sudo touch storage/logs/laravel.log 2>/dev/null || true
    sudo chown \$WEB_USER:\$WEB_USER storage/logs/laravel.log 2>/dev/null || true
    sudo chmod 664 storage/logs/laravel.log 2>/dev/null || true
    
    echo "✓ Permissions fixed"
    
    echo ""
    echo "=== Clearing All Caches ==="
    
    # Delete all cached files
    sudo rm -rf bootstrap/cache/*.php 2>/dev/null || true
    
    # Clear caches
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    
    # Rebuild caches
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
    
    # Fix permissions after cache operations
    sudo chown -R \$WEB_USER:\$WEB_USER storage bootstrap/cache 2>/dev/null || true
    sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    sudo chown \$WEB_USER:\$WEB_USER storage/logs/laravel.log 2>/dev/null || true
    sudo chmod 664 storage/logs/laravel.log 2>/dev/null || true
    
    echo "✓ Caches cleared and rebuilt"
    
    echo ""
    echo "=== Testing EnvReader ==="
    php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    use App\Helpers\EnvReader;
    
    \$nodeApiUrl = EnvReader::get('NODE_API_URL');
    \$nodeApiKey = EnvReader::get('NODE_API_KEY');
    
    echo 'EnvReader::get(\"NODE_API_URL\"): ' . \$nodeApiUrl . PHP_EOL;
    echo 'EnvReader::get(\"NODE_API_KEY\"): ' . substr(\$nodeApiKey, 0, 10) . '...' . PHP_EOL;
    
    if (strpos(\$nodeApiUrl, 'localhost:3000') !== false) {
        echo '⚠ ERROR: Still using localhost:3000!' . PHP_EOL;
        exit(1);
    } elseif (strpos(\$nodeApiUrl, 'uodttljjzj3nh3e4cjqardxip40btqef') !== false) {
        echo '✓ Correct Lambda URL detected' . PHP_EOL;
    } else {
        echo '⚠ Unknown URL format' . PHP_EOL;
    }
    " 2>&1 || echo "⚠ Could not test EnvReader"
    
    echo ""
    echo "=== Summary ==="
    echo "env.txt NODE_API_URL: \$(grep '^NODE_API_URL=' env.txt | cut -d'=' -f2-)"
    echo "env.txt NODE_API_KEY: \$(grep '^NODE_API_KEY=' env.txt | cut -d'=' -f2- | cut -c1-10)..."
    echo "Permissions: Storage=\$(stat -c '%U:%G %a' storage 2>/dev/null || echo 'N/A'), Log=\$(stat -c '%U:%G %a' storage/logs/laravel.log 2>/dev/null || echo 'N/A')"
FIX_LOGIN_EOF

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
echo -e "${GREEN}✓ Login issues fixed${NC}"
echo -e "${YELLOW}Try logging in again at: https://mono.scrapmate.co.in/login${NC}"

