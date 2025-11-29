#!/bin/bash

# Fix all permissions and add missing .env variables
# Usage: ./fix-all-permissions.sh [EC2_IP]

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

echo -e "${YELLOW}Fixing all permissions and .env configuration on EC2 server ($EC2_IP)...${NC}"

# Test SSH connection
if ! ssh -i "$PEM_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$EC2_IP" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${RED}Failed to connect to EC2 instance${NC}"
    exit 1
fi

# Fix everything
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << FIX_ALL_EOF
    set -e
    cd $REMOTE_PATH
    
    echo "=== Fixing Permissions ==="
    
    # Determine web server user
    WEB_USER="www-data"
    if id "nginx" &>/dev/null; then
        WEB_USER="nginx"
    elif id "www-data" &>/dev/null; then
        WEB_USER="www-data"
    else
        WEB_USER="ubuntu"
    fi
    
    echo "Using web server user: $WEB_USER"
    
    # Create storage directories if they don't exist
    sudo mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache 2>/dev/null || true
    
    # Set ownership to web server user with ubuntu group
    sudo chown -R $WEB_USER:ubuntu storage bootstrap/cache 2>/dev/null || \
    sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache 2>/dev/null || true
    
    # Set directory permissions (775)
    sudo find storage -type d -exec chmod 775 {} \; 2>/dev/null || true
    sudo find bootstrap/cache -type d -exec chmod 775 {} \; 2>/dev/null || true
    
    # Set file permissions (664)
    sudo find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
    sudo find bootstrap/cache -type f -exec chmod 664 {} \; 2>/dev/null || true
    
    # Ensure log file exists and is writable
    sudo touch storage/logs/laravel.log 2>/dev/null || true
    sudo chown $WEB_USER:ubuntu storage/logs/laravel.log 2>/dev/null || \
    sudo chown $WEB_USER:$WEB_USER storage/logs/laravel.log 2>/dev/null || true
    sudo chmod 664 storage/logs/laravel.log 2>/dev/null || true
    
    echo "✓ Permissions fixed"
    
    echo ""
    echo "=== Fixing .env Configuration ==="
    
    # Ensure .env exists - create with basic configuration if missing
    if [ ! -f .env ]; then
        echo "Creating .env file with basic configuration..."
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
        echo "✓ .env file created"
    else
        echo "✓ .env file exists"
    fi
    
    # Add/Update NODE_API_URL
    CORRECT_NODE_API_URL="https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api"
    if grep -q "^NODE_API_URL=" .env 2>/dev/null; then
        sed -i "s|^NODE_API_URL=.*|NODE_API_URL=$CORRECT_NODE_API_URL|" .env
        echo "✓ NODE_API_URL updated"
    else
        echo "NODE_API_URL=$CORRECT_NODE_API_URL" >> .env
        echo "✓ NODE_API_URL added"
    fi
    
    # Add/Update NODE_API_KEY
    NODE_API_KEY="zyubkfzeumeoviaqzcsrvfwdzbiwnlnn"
    if grep -q "^NODE_API_KEY=" .env 2>/dev/null; then
        sed -i "s|^NODE_API_KEY=.*|NODE_API_KEY=$NODE_API_KEY|" .env
        echo "✓ NODE_API_KEY updated"
    else
        echo "NODE_API_KEY=$NODE_API_KEY" >> .env
        echo "✓ NODE_API_KEY added"
    fi
    
    # Ensure APP_KEY exists
    if ! grep -q "^APP_KEY=" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
        echo "Generating APP_KEY..."
        php artisan key:generate --force 2>/dev/null || true
        echo "✓ APP_KEY generated"
    else
        echo "✓ APP_KEY exists"
    fi
    
    # Set .env permissions
    sudo chown ubuntu:ubuntu .env
    sudo chmod 600 .env
    
    echo ""
    echo "=== Clearing and Rebuilding Caches ==="
    
    # Delete cached config files
    sudo rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php 2>/dev/null || true
    
    # Clear caches as web server user
    sudo -u $WEB_USER php artisan config:clear 2>/dev/null || php artisan config:clear 2>/dev/null || true
    sudo -u $WEB_USER php artisan cache:clear 2>/dev/null || php artisan cache:clear 2>/dev/null || true
    sudo -u $WEB_USER php artisan route:clear 2>/dev/null || php artisan route:clear 2>/dev/null || true
    sudo -u $WEB_USER php artisan view:clear 2>/dev/null || php artisan view:clear 2>/dev/null || true
    
    # Rebuild caches
    sudo -u $WEB_USER php artisan config:cache 2>/dev/null || php artisan config:cache 2>/dev/null || true
    sudo -u $WEB_USER php artisan route:cache 2>/dev/null || php artisan route:cache 2>/dev/null || true
    sudo -u $WEB_USER php artisan view:cache 2>/dev/null || php artisan view:cache 2>/dev/null || true
    
    # Fix permissions again after cache operations
    sudo chown -R $WEB_USER:ubuntu storage bootstrap/cache 2>/dev/null || \
    sudo chown -R $WEB_USER:$WEB_USER storage bootstrap/cache 2>/dev/null || true
    sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    
    echo "✓ Caches cleared and rebuilt"
    
    echo ""
    echo "=== Summary ==="
    echo "NODE_API_URL: \$(grep '^NODE_API_URL=' .env | cut -d'=' -f2)"
    echo "NODE_API_KEY: \$(grep '^NODE_API_KEY=' .env | cut -d'=' -f2 | cut -c1-10)..."
    echo "APP_KEY: \$(grep '^APP_KEY=' .env | cut -d'=' -f2 | cut -c1-20)..."
    echo "Storage permissions: \$(stat -c '%U:%G %a' storage/logs 2>/dev/null || echo 'N/A')"
FIX_ALL_EOF

# Reload services
echo ""
echo -e "${YELLOW}Reloading services...${NC}"
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << RELOAD_EOF
    # Reload PHP-FPM
    if systemctl list-unit-files | grep -q php8.3-fpm; then
        sudo systemctl reload php8.3-fpm 2>/dev/null || true
    elif systemctl list-unit-files | grep -q php8.2-fpm; then
        sudo systemctl reload php8.2-fpm 2>/dev/null || true
    fi
    
    # Reload Nginx
    if command -v nginx &> /dev/null; then
        sudo nginx -t 2>/dev/null && sudo systemctl reload nginx 2>/dev/null || true
    fi
RELOAD_EOF

echo ""
echo -e "${GREEN}✓ All permissions and .env configuration fixed${NC}"
echo -e "${GREEN}✓ Services reloaded${NC}"

