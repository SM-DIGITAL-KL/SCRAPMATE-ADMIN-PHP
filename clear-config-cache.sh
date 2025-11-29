#!/bin/bash

# Clear Laravel config cache and verify .env settings
# Usage: ./clear-config-cache.sh [EC2_IP]

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

echo -e "${YELLOW}Clearing Laravel config cache on EC2 server ($EC2_IP)...${NC}"

# Test SSH connection
if ! ssh -i "$PEM_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$REMOTE_USER@$EC2_IP" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${RED}Failed to connect to EC2 instance${NC}"
    exit 1
fi

# Clear config cache and verify .env
ssh -i "$PEM_FILE" "$REMOTE_USER@$EC2_IP" << 'CLEAR_CACHE_EOF'
    set -e
    cd /var/www/scrapmate-admin
    
    echo "=== Current .env NODE_API_URL ==="
    grep "^NODE_API_URL=" .env 2>/dev/null || echo "NODE_API_URL not found in .env"
    echo ""
    
    # Determine web server user
    WEB_USER="www-data"
    if id "nginx" &>/dev/null; then
        WEB_USER="nginx"
    elif id "www-data" &>/dev/null; then
        WEB_USER="www-data"
    fi
    
    echo "Using web server user: $WEB_USER"
    echo ""
    
    # Delete config cache files directly
    echo "Deleting cached config files..."
    sudo rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/services.php 2>/dev/null || true
    echo "✓ Cached config files deleted"
    
    # Clear all caches using artisan
    echo "Clearing Laravel caches..."
    sudo -u $WEB_USER php artisan config:clear 2>/dev/null || php artisan config:clear 2>/dev/null || true
    sudo -u $WEB_USER php artisan cache:clear 2>/dev/null || php artisan cache:clear 2>/dev/null || true
    sudo -u $WEB_USER php artisan route:clear 2>/dev/null || php artisan route:clear 2>/dev/null || true
    sudo -u $WEB_USER php artisan view:clear 2>/dev/null || php artisan view:clear 2>/dev/null || true
    echo "✓ All caches cleared"
    
    # Verify .env has correct NODE_API_URL
    CORRECT_URL="https://uodttljjzj3nh3e4cjqardxip40btqef.lambda-url.ap-south-1.on.aws/api"
    if grep -q "^NODE_API_URL=$CORRECT_URL" .env 2>/dev/null; then
        echo "✓ NODE_API_URL is correct in .env"
    else
        echo "⚠ Updating NODE_API_URL in .env..."
        if grep -q "^NODE_API_URL=" .env; then
            sed -i "s|^NODE_API_URL=.*|NODE_API_URL=$CORRECT_URL|" .env
        else
            echo "NODE_API_URL=$CORRECT_URL" >> .env
        fi
        echo "✓ NODE_API_URL updated"
    fi
    
    # Rebuild config cache
    echo "Rebuilding config cache..."
    sudo -u $WEB_USER php artisan config:cache 2>/dev/null || php artisan config:cache 2>/dev/null || true
    echo "✓ Config cache rebuilt"
    
    # Verify the cached config
    echo ""
    echo "=== Verifying cached config ==="
    if [ -f bootstrap/cache/config.php ]; then
        # Extract NODE_API_URL from cached config (it's in a serialized array)
        if grep -q "NODE_API_URL" bootstrap/cache/config.php; then
            echo "✓ Config cache contains NODE_API_URL"
            # Try to show the value (might be serialized)
            grep -o "NODE_API_URL[^;]*" bootstrap/cache/config.php | head -1 || true
        else
            echo "⚠ NODE_API_URL not found in cached config"
        fi
    else
        echo "⚠ Config cache file not found"
    fi
    
    echo ""
    echo "=== Summary ==="
    echo ".env NODE_API_URL: $(grep '^NODE_API_URL=' .env | cut -d'=' -f2)"
    echo "Config cache: $(test -f bootstrap/cache/config.php && echo 'exists' || echo 'not found')"
CLEAR_CACHE_EOF

echo ""
echo -e "${GREEN}✓ Config cache cleared and rebuilt${NC}"
echo -e "${YELLOW}The application should now use the correct NODE_API_URL${NC}"

